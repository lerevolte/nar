<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class NotifyStoragePolicy extends Command
{
    protected $signature = 'notify:storage-policy 
                            {--dry-run : Только показать сколько юзеров}
                            {--days=7 : Через сколько дней удаляются треки}';

    protected $description = 'Разовая рассылка о политике хранения треков';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $days = (int) $this->option('days');

        // Активные юзеры с песнями (заходили за 60 дней)
        $users = DB::table('users as u')
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))->from('songs as s')
                  ->whereColumn('s.user_id', 'u.user_id')
                  ->where('s.is_deleted', 0);
            })
            //->where('user_id', 288559694)
            ->where('u.is_blocked', 0)
            ->where('u.last_activity', '>=', now()->subDays(60))
            ->pluck('u.user_id');

        $this->info("Найдено {$users->count()} юзеров с песнями");

        if ($dryRun) {
            $this->info("DRY RUN — не отправлено.");
            return;
        }

        if (!$this->confirm("Отправить уведомление {$users->count()} пользователям?")) {
            return;
        }

        $title = "📢 Хранение треков на сервере";
        $message = "Для стабильной работы сервиса аудиофайлы будут автоматически удаляться через {$days} дней после создания. "
                 . "Треки в чартах и избранном НЕ удаляются. "
                 . "Скачайте важные треки заранее! "
                 . "Стемы (голос/минусовка) хранятся 24 часа.";

        $count = 0;
        $batch = [];

        foreach ($users as $userId) {
            $batch[] = [
                'user_id' => $userId,
                'title' => $title,
                'message' => $message,
                'is_read' => 0,
                'created_at' => now(),
            ];

            if (count($batch) >= 500) {
                DB::table('web_notifications')->insert($batch);
                $count += count($batch);
                $batch = [];
                $this->line("  Отправлено {$count}...");
            }
        }

        if (!empty($batch)) {
            DB::table('web_notifications')->insert($batch);
            $count += count($batch);
        }

        $this->info("✅ Отправлено {$count} уведомлений.");
    }
}