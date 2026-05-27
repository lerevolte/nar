<?php

namespace App\Console\Commands;

use App\Models\Chart;
use App\Services\TelegramNotificationService;
use Illuminate\Console\Command;

class TestTelegramNotification extends Command
{
    protected $signature = 'telegram:test {user_id} {--with-audio : Send with real audio from latest chart}';
    protected $description = 'Send test notification to user';

    public function handle(TelegramNotificationService $telegram)
    {
        $userId = $this->argument('user_id');
        $withAudio = $this->option('with-audio');

        $this->info("Sending test messages to {$userId}...");

        // Тест 1: Простое сообщение
        $this->info("\n1. Sending simple message...");
        $result1 = $telegram->sendMessage($userId, "🎵 <b>Тестовое сообщение</b>\n\nЕсли ты видишь это — уведомления работают!");
        $this->line($result1 ? "   ✓ Sent" : "   ✗ Failed");

        if ($withAudio) {
            // Получаем реальные данные из последнего чарта
            $chart = Chart::with(['entries.song', 'entries.user'])
                ->latest()
                ->first();

            if ($chart) {
                $this->info("\n2. Sending chart results with audio from '{$chart->name}'...");

                $winners = $chart->entries()
                    ->with(['song', 'user'])
                    ->orderByDesc('votes_count')
                    ->take(3)
                    ->get()
                    ->map(fn($e) => [
                        'song' => $e->song->title ?? 'Без названия',
                        'author' => $e->user->first_name ?? $e->user->username ?? 'Автор',
                        'votes' => $e->votes_count,
                        'audio_url' => $e->song->file_path,
                    ])
                    ->toArray();

                if (!empty($winners)) {
                    $this->line("   Winners found: " . count($winners));
                    foreach ($winners as $i => $w) {
                        $this->line("   " . ($i + 1) . ". {$w['song']} by {$w['author']} ({$w['votes']} votes)");
                        $this->line("      Audio: " . ($w['audio_url'] ? 'Yes' : 'No'));
                    }

                    $result2 = $telegram->sendChartResultsWithAudio($userId, $chart->name, $winners);
                    $this->line($result2 ? "   ✓ Sent" : "   ✗ Failed");
                } else {
                    $this->warn("   No entries in chart");
                }
            } else {
                $this->warn("   No charts found");
            }
        } else {
            // Тест с фейковыми данными (без аудио)
            $this->info("\n2. Sending winner notification...");
            $result2 = $telegram->notifyChartWinner(
                $userId,
                1,
                'Тестовая песня',
                3,
                'Недельный чарт #1'
            );
            $this->line($result2 ? "   ✓ Sent" : "   ✗ Failed");

            $this->info("\n3. Sending chart results (without audio)...");
            $result3 = $telegram->sendChartResultsWithAudio(
                $userId,
                'Недельный чарт #1',
                [
                    ['song' => 'Первая песня', 'author' => 'Артист 1', 'votes' => 15, 'audio_url' => 'https://narepite.site/music/gen_0126b94e1d4baec6fa195e90e709e0b2_1_288559694_23a6f24b154be905.mp3'],
                    ['song' => 'Вторая песня', 'author' => 'Артист 2', 'votes' => 12, 'audio_url' => 'https://musicfile.removeai.ai/MWUxMjgzNDAtYTMxMS00MjJjLTg1NTItYzBkMjVlYjFhNWZi.mp3'],
                    ['song' => 'Третья песня', 'author' => 'Артист 3', 'votes' => 8, 'audio_url' => 'https://musicfile.removeai.ai/MWUxMjgzNDAtYTMxMS00MjJjLTg1NTItYzBkMjVlYjFhNWZi.mp3'],
                ]
            );
            $this->line($result3 ? "   ✓ Sent" : "   ✗ Failed");
        }

        $this->info("\n✓ Done! Check Telegram.");

        return 0;
    }
}