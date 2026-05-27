<?php

namespace App\Console\Commands;

use App\Services\ChartService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestChartNotifications extends Command
{
    protected $signature = 'charts:test-notifications {--send : Actually send notifications}';
    protected $description = 'Test chart notifications - show how many inactive users would receive notifications';

    public function handle(ChartService $chartService)
    {
        $this->info('=== Тестирование рассылки результатов чарта ===');
        $this->newLine();

        // Получаем текущий или последний закрытый чарт
        $chart = DB::table('charts')
            ->orderByDesc('ends_at')
            ->first();

        if (!$chart) {
            $this->error('Нет чартов в базе данных');
            return 1;
        }

        $this->info("Чарт: {$chart->name}");
        $this->info("Статус: " . ($chart->is_active ? 'Активный' : 'Завершён'));
        $this->info("Период: {$chart->starts_at} — {$chart->ends_at}");
        $this->newLine();

        // Получаем победителей
        $winners = DB::table('chart_entries as ce')
            ->join('songs as s', 'ce.song_id', '=', 's.id')
            ->join('users as u', 'ce.user_id', '=', 'u.user_id')
            ->where('ce.chart_id', $chart->id)
            ->where('ce.votes_count', '>=', 1)
            ->orderByDesc('ce.votes_count')
            ->orderBy('ce.created_at')
            ->take(3)
            ->select('ce.user_id', 'u.first_name', 'u.username', 's.title', 'ce.votes_count')
            ->get();

        // if ($winners->isEmpty()) {
        //     $this->warn('Нет победителей (нет участников с >= 1 голосом)');
        //     return 0;
        // }

        $this->info('🏆 Победители:');
        foreach ($winners as $i => $winner) {
            $position = $i + 1;
            $name = $winner->first_name ?? $winner->username ?? 'ID:' . $winner->user_id;
            $this->line("  {$position}. {$winner->title} — {$name} ({$winner->votes_count} голосов)");
        }
        $this->newLine();

        $winnerIds = $winners->pluck('user_id')->toArray();

        // Тестируем каждый сегмент отдельно
        $this->info('📊 Сегменты неактивных пользователей:');
        $this->newLine();

        // 1. Черновик > 7 дней без готовых песен
        $draftOnlyUsers = DB::table('drafts as d')
            ->leftJoin('songs as s', 'd.user_id', '=', 's.user_id')
            ->join('users as u', 'd.user_id', '=', 'u.user_id')
            ->whereNull('s.id')
            ->where('u.is_blocked', 0)
            ->where('d.updated_at', '<', now()->subDays(7))
            ->whereNotIn('d.user_id', $winnerIds)
            ->pluck('d.user_id')
            ->unique();

        $this->line("1️⃣  Черновик > 7 дней (без готовых песен): {$draftOnlyUsers->count()} чел.");

        // 2. Последняя активность > 7 дней
        $inactiveUsers = DB::table('users')
            ->where('is_blocked', 0)
            ->where('last_activity', '<', now()->subDays(7))
            ->whereNotIn('user_id', $winnerIds)
            ->pluck('user_id');

        $this->line("2️⃣  Неактивны > 7 дней (last_activity): {$inactiveUsers->count()} чел.");

        // 3. Последняя оплата > 10 дней
        $churnUsers = DB::table('users as u')
            ->join('payments as p', 'u.user_id', '=', 'p.user_id')
            ->where('u.is_blocked', 0)
            ->where('p.status', 'succeeded')
            ->whereNotIn('u.user_id', $winnerIds)
            ->groupBy('u.user_id')
            ->havingRaw('MAX(p.created_at) < ?', [now()->subDays(10)])
            ->pluck('u.user_id');

        $this->line("3️⃣  Оплата > 10 дней назад: {$churnUsers->count()} чел.");

        $this->newLine();

        // Объединяем все сегменты
        $allInactiveIds = collect()
            ->merge($draftOnlyUsers)
            ->merge($inactiveUsers)
            ->merge($churnUsers)
            ->unique()
            ->values();

        $this->info("📬 ИТОГО уникальных пользователей для рассылки: {$allInactiveIds->count()}");
        $this->newLine();

        // Показываем примеры
        if ($allInactiveIds->count() > 0) {
            $examples = DB::table('users')
                ->whereIn('user_id', $allInactiveIds->take(10))
                ->select('user_id', 'first_name', 'username', 'last_activity')
                ->get();

            $this->info('Примеры (первые 10):');
            foreach ($examples as $user) {
                $name = $user->first_name ?? $user->username ?? 'Без имени';
                $lastActivity = $user->last_activity ?? 'никогда';
                $this->line("  • {$user->user_id} — {$name} (активность: {$lastActivity})");
            }
        }

        $this->newLine();

        // Опция реальной отправки
        if ($this->option('send')) {
            if (!$this->confirm('Вы уверены, что хотите отправить уведомления ' . $allInactiveIds->count() . ' пользователям?')) {
                $this->info('Отменено.');
                return 0;
            }

            $this->info('Отправка уведомлений...');
            
            $telegram = app(\App\Services\TelegramNotificationService::class);
            
            $winnersData = $winners->map(fn($w) => [
                'song' => $w->title,
                'author' => $w->first_name ?? $w->username ?? 'Автор',
                'votes' => $w->votes_count,
                'audio_url' => null, // можно добавить если нужно
            ])->toArray();

            $telegram->notifyChartResults(
                $allInactiveIds->toArray(),
                $chart->name,
                $winnersData
            );

            $this->info('✅ Отправлено!');
        } else {
            $this->line('💡 Для реальной отправки используй: php artisan charts:test-notifications --send');
        }

        return 0;
    }
}