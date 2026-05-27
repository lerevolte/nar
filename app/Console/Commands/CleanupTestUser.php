<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupTestUser extends Command
{
    protected $signature = 'songs:cleanup-test 
                            {user_id : ID пользователя для теста}
                            {--dry-run : Только показать, не удалять}
                            {--days=7 : Возраст трека в днях}
                            {--restore : Откатить files_removed для этого юзера}';

    protected $description = 'Тест очистки на одном пользователе';

    private string $musicDir = '/var/www/narepite-web/public/music';

    public function handle()
    {
        $userId = (int) $this->argument('user_id');
        $dryRun = $this->option('dry-run');
        $days = (int) $this->option('days');

        if ($this->option('restore')) {
            return $this->restoreUser($userId);
        }

        $this->info("=== Тест очистки для user_id={$userId} " . ($dryRun ? '(DRY RUN)' : '') . " ===");

        // Защищённые песни
        $chartIds = DB::table('chart_entries')->pluck('song_id')->unique()->toArray();
        $favoriteIds = DB::table('favorite_songs')->pluck('song_id')->unique()->toArray();
        $protectedIds = array_unique(array_merge($chartIds, $favoriteIds));

        // Песни юзера — не удалённые юзером, не удалённые автоочисткой, с файлами, старше N дней
        $query = DB::table('songs')
            ->where('user_id', $userId)
            ->where('is_deleted', 0)
            ->where('files_removed', 0)
            ->whereNotNull('file_path')
            ->where('created_at', '<', now()->subDays($days));

        if (!empty($protectedIds)) {
            $query->whereNotIn('id', $protectedIds);
        }

        $songs = $query->select('id', 'title', 'file_path', 'file_path_2', 'suno_task_id',
                                'vocal_url_1', 'vocal_url_2', 'instrumental_url_1', 'instrumental_url_2',
                                'created_at')
            ->get();

        if ($songs->isEmpty()) {
            $this->info("Нет песен для удаления (старше {$days} дней, не в чартах/избранном).");
            $this->showAllSongs($userId, $protectedIds);
            return;
        }

        $this->info("Найдено {$songs->count()} песен для удаления:");
        $totalSize = 0;

        foreach ($songs as $song) {
            $size = 0;
            foreach ([$song->file_path, $song->file_path_2, $song->vocal_url_1, $song->vocal_url_2,
                       $song->instrumental_url_1, $song->instrumental_url_2] as $url) {
                if ($url) {
                    $path = $this->musicDir . '/' . basename(parse_url($url, PHP_URL_PATH));
                    if (file_exists($path)) $size += filesize($path);
                }
            }
            $sizeMB = round($size / 1024 / 1024, 1);
            $restorable = $song->suno_task_id ? '✅ восстановимо' : '❌ невосстановимо';
            $this->line("  #{$song->id} | {$song->title} | {$song->created_at} | {$sizeMB}МБ | {$restorable}");
            $totalSize += $size;
        }

        $totalMB = round($totalSize / 1024 / 1024, 1);
        $this->info("Итого: {$songs->count()} песен, ~{$totalMB} МБ");

        if ($dryRun) {
            $this->info("DRY RUN — ничего не удалено.");
            return;
        }

        if (!$this->confirm("Удалить файлы {$songs->count()} песен? (треки останутся видны в интерфейсе)")) {
            $this->info("Отменено.");
            return;
        }

        $deleted = 0;
        foreach ($songs as $song) {
            foreach ([$song->file_path, $song->file_path_2, $song->vocal_url_1, $song->vocal_url_2,
                       $song->instrumental_url_1, $song->instrumental_url_2] as $url) {
                if ($url) {
                    $path = $this->musicDir . '/' . basename(parse_url($url, PHP_URL_PATH));
                    if (file_exists($path)) unlink($path);
                }
            }

            DB::table('songs')->where('id', $song->id)->update([
                'files_removed' => 1,
                'file_path' => null, 'file_path_2' => null,
                'vocal_url_1' => null, 'vocal_url_2' => null,
                'instrumental_url_1' => null, 'instrumental_url_2' => null,
            ]);
            $deleted++;
        }

        // Уведомление
        $titles = $songs->pluck('title')->take(3)->implode(', ');
        $num = $songs->count();
        DB::table('web_notifications')->insert([
            'user_id' => $userId,
            'title' => "🗑 Удалено {$num} " . $this->pluralSongs($num),
            'message' => "Файлы удалены: {$titles}. Нажмите «Восстановить» на странице песни, чтобы подкачать файл заново.",
            'is_read' => 0,
            'created_at' => now(),
        ]);

        $this->info("✅ Удалено файлов у {$deleted} песен. Уведомление отправлено.");
        $this->info("Треки остались в интерфейсе с кнопкой «Восстановить».");
        $this->info("Для отката: php artisan songs:cleanup-test {$userId} --restore");
    }

    private function showAllSongs(int $userId, array $protectedIds): void
    {
        $all = DB::table('songs')->where('user_id', $userId)
            ->select('id', 'title', 'is_deleted', 'files_removed', 'created_at', 'file_path')
            ->orderBy('created_at', 'desc')->get();

        $this->info("\nВсе песни юзера ({$all->count()}):");
        foreach ($all as $s) {
            if ($s->is_deleted) $status = '🗑 УДАЛЕНА юзером';
            elseif ($s->files_removed) $status = '📦 файлы удалены (восстановимо)';
            elseif ($s->file_path) $status = '✅ OK';
            else $status = '⚠️ нет файла';
            $protected = in_array($s->id, $protectedIds) ? ' [ЗАЩИЩЕНА]' : '';
            $this->line("  #{$s->id} | {$s->title} | {$s->created_at} | {$status}{$protected}");
        }
    }

    private function restoreUser(int $userId): void
    {
        $removed = DB::table('songs')
            ->where('user_id', $userId)
            ->where('files_removed', 1)
            ->count();

        if ($removed === 0) {
            $this->info("Нет песен с files_removed=1 у юзера {$userId}");
            return;
        }

        $this->info("Найдено {$removed} песен с files_removed=1. Сбрасываю флаг...");
        $this->info("(Файлов на диске нет — юзер увидит кнопку «Восстановить»)");

        DB::table('songs')
            ->where('user_id', $userId)
            ->where('files_removed', 1)
            ->update(['files_removed' => 0]);

        $this->info("✅ Откат выполнен.");
    }

    private function pluralSongs(int $n): string
    {
        $mod10 = $n % 10; $mod100 = $n % 100;
        if ($mod10 === 1 && $mod100 !== 11) return 'песня';
        if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 10 || $mod100 >= 20)) return 'песни';
        return 'песен';
    }
}