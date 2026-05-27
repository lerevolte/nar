<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanupSongs extends Command
{
    protected $signature = 'songs:cleanup 
                            {--dry-run : Показать что будет удалено, не удаляя}
                            {--stems-only : Удалить только стемы}
                            {--dead-only : Удалить только треки мёртвых юзеров}
                            {--old-only : Удалить только старые треки активных юзеров}
                            {--warn-only : Только отправить предупреждения}
                            {--orphans-only : Удалить только файлы-сироты}
                            {--stem-hours=24 : Через сколько часов удалять стемы}
                            {--dead-days=30 : Неактивность юзера в днях}
                            {--old-days=7 : Возраст трека в днях}
                            {--warn-days=6 : За сколько дней предупреждать}';

    protected $description = 'Очистка старых аудиофайлов. Записи остаются, обнуляются file_path.';

    private string $musicDir;
    private int $deletedFiles = 0;
    private int $freedBytes = 0;
    private bool $dryRun = false;

    public function handle()
    {
        $this->musicDir = '/var/www/narepite-web/public/music';
        $this->dryRun = $this->option('dry-run');
        if ($this->dryRun) $this->info('=== DRY RUN ===');

        $stemsOnly = $this->option('stems-only');
        $deadOnly = $this->option('dead-only');
        $oldOnly = $this->option('old-only');
        $warnOnly = $this->option('warn-only');
        $orphansOnly = $this->option('orphans-only');
        $runAll = !$stemsOnly && !$deadOnly && !$oldOnly && !$warnOnly && !$orphansOnly;

        if ($runAll || $warnOnly) $this->sendWarnings();
        if ($warnOnly) return;
        if ($runAll || $stemsOnly) $this->cleanupStems();
        if ($runAll || $deadOnly) $this->cleanupDeadUserSongs();
        if ($runAll || $oldOnly) $this->cleanupOldSongs();
        if ($runAll || $orphansOnly) $this->cleanupOrphanFiles();

        $freedMB = round($this->freedBytes / 1024 / 1024, 1);
        $this->info("--- Итого: {$this->deletedFiles} файлов, {$freedMB} МБ ---");
        Log::info("songs:cleanup", ['dry' => $this->dryRun, 'files' => $this->deletedFiles, 'mb' => $freedMB]);
    }

    private function sendWarnings(): void
    {
        $warnDays = (int) $this->option('warn-days');
        $oldDays = (int) $this->option('old-days');
        $daysLeft = $oldDays - $warnDays;
        $this->info("--- Предупреждения (удаление через {$daysLeft} дн.) ---");

        $protectedIds = $this->getProtectedSongIds();

        $query = DB::table('songs as s')
            ->join('users as u', 's.user_id', '=', 'u.user_id')
            ->where('u.last_activity', '>=', now()->subDays(30))
            ->where('s.created_at', '<', now()->subDays($warnDays))
            ->where('s.created_at', '>=', now()->subDays($oldDays))
            ->where('s.is_deleted', 0)
            ->whereNotNull('s.file_path');
        if (!empty($protectedIds)) $query->whereNotIn('s.id', $protectedIds);

        $songs = $query->select('s.id', 's.user_id', 's.title')->get();
        $byUser = [];
        foreach ($songs as $s) $byUser[$s->user_id][] = $s;

        $notified = 0;
        foreach ($byUser as $userId => $userSongs) {
            $already = DB::table('web_notifications')
                ->where('user_id', $userId)
                ->where('title', 'like', '%будут удалены%')
                ->where('created_at', '>=', now()->startOfDay())
                ->exists();
            if ($already) continue;

            $count = count($userSongs);
            $titles = array_slice(array_map(fn($s) => $s->title ?? 'Без названия', $userSongs), 0, 3);
            $list = implode(', ', $titles);
            if ($count > 3) $list .= " и ещё " . ($count - 3);

            if (!$this->dryRun) {
                DB::table('web_notifications')->insert([
                    'user_id' => $userId,
                    'title' => "⚠️ {$count} " . $this->pluralSongs($count) . " будут удалены",
                    'message' => "Через {$daysLeft} дн. аудиофайлы будут удалены: {$list}. Скачайте их! Треки в чартах и избранном защищены.",
                    'is_read' => 0, 'created_at' => now(),
                ]);
            }
            $notified++;
        }
        $this->info("  Предупреждения: {$notified} юзеров");
    }

    private function cleanupStems(): void
    {
        $hours = (int) $this->option('stem-hours');
        $this->info("--- Стемы старше {$hours}ч ---");

        $songs = DB::table('songs')
            ->where(function ($q) {
                $q->whereNotNull('vocal_url_1')->orWhereNotNull('vocal_url_2')
                  ->orWhereNotNull('instrumental_url_1')->orWhereNotNull('instrumental_url_2');
            })
            ->where('created_at', '<', now()->subHours($hours))
            ->select('id', 'user_id', 'vocal_url_1', 'vocal_url_2', 'instrumental_url_1', 'instrumental_url_2')
            ->get();

        $count = 0; $users = [];
        foreach ($songs as $song) {
            foreach ([$song->vocal_url_1, $song->vocal_url_2, $song->instrumental_url_1, $song->instrumental_url_2] as $u) {
                if ($u) $this->deleteFileByUrl($u);
            }
            if (!$this->dryRun) {
                DB::table('songs')->where('id', $song->id)->update([
                    'vocal_url_1' => null, 'vocal_url_2' => null,
                    'instrumental_url_1' => null, 'instrumental_url_2' => null,
                ]);
                $users[$song->user_id] = true;
            }
            $count++;
        }
        if (!$this->dryRun) {
            foreach (array_keys($users) as $uid) {
                DB::table('web_notifications')->insert([
                    'user_id' => $uid, 'title' => '🎵 Стемы удалены',
                    'message' => 'Дорожки голоса/минусовки удалены. Можно разделить повторно на странице песни.',
                    'is_read' => 0, 'created_at' => now(),
                ]);
            }
        }
        $this->info("  Стемы: {$count} песен");
    }

    private function cleanupDeadUserSongs(): void
    {
        $days = (int) $this->option('dead-days');
        $this->info("--- Мёртвые юзеры ({$days}+ дней) ---");
        $protectedIds = $this->getProtectedSongIds();

        $query = DB::table('songs as s')
            ->join('users as u', 's.user_id', '=', 'u.user_id')
            ->where('u.last_activity', '<', now()->subDays($days))
            ->where('s.is_deleted', 0)->whereNotNull('s.file_path');
        if (!empty($protectedIds)) $query->whereNotIn('s.id', $protectedIds);

        $songs = $query->select('s.id', 's.file_path', 's.file_path_2',
            's.vocal_url_1', 's.vocal_url_2', 's.instrumental_url_1', 's.instrumental_url_2')->get();

        $count = 0;
        foreach ($songs as $song) {
            $this->deleteSongFiles($song);
            if (!$this->dryRun) {
                DB::table('songs')->where('id', $song->id)->update([
                    'file_path' => null, 'file_path_2' => null,
                    'vocal_url_1' => null, 'vocal_url_2' => null,
                    'instrumental_url_1' => null, 'instrumental_url_2' => null,
                ]);
            }
            $count++;
        }
        $this->info("  Мёртвые: {$count} песен");
    }

    private function cleanupOldSongs(): void
    {
        $days = (int) $this->option('old-days');
        $this->info("--- Треки старше {$days} дней ---");
        $protectedIds = $this->getProtectedSongIds();

        $query = DB::table('songs as s')
            ->join('users as u', 's.user_id', '=', 'u.user_id')
            ->where('u.last_activity', '>=', now()->subDays(30))
            ->where('s.created_at', '<', now()->subDays($days))
            ->where('s.is_deleted', 0)->whereNotNull('s.file_path');
        if (!empty($protectedIds)) $query->whereNotIn('s.id', $protectedIds);

        $songs = $query->select('s.id', 's.user_id', 's.title', 's.file_path', 's.file_path_2',
            's.vocal_url_1', 's.vocal_url_2', 's.instrumental_url_1', 's.instrumental_url_2')->get();

        $count = 0; $byUser = [];
        foreach ($songs as $song) {
            $this->deleteSongFiles($song);
            if (!$this->dryRun) {
                DB::table('songs')->where('id', $song->id)->update([
                    'file_path' => null, 'file_path_2' => null,
                    'vocal_url_1' => null, 'vocal_url_2' => null,
                    'instrumental_url_1' => null, 'instrumental_url_2' => null,
                ]);
            }
            $byUser[$song->user_id][] = $song->title ?? 'Без названия';
            $count++;
        }

        if (!$this->dryRun) {
            foreach ($byUser as $userId => $titles) {
                $num = count($titles);
                $list = implode(', ', array_slice($titles, 0, 3));
                if ($num > 3) $list .= " и ещё " . ($num - 3);
                // DB::table('web_notifications')->insert([
                //     'user_id' => $userId,
                //     'title' => "🗑 Удалено {$num} " . $this->pluralSongs($num),
                //     'message' => "Аудиофайлы удалены: {$list}. Текст сохранён. Добавляйте в избранное, чтобы защитить от удаления!",
                //     'is_read' => 0, 'created_at' => now(),
                // ]);
            }
        }
        $this->info("  Старые: {$count} песен");
    }

    private function cleanupOrphanFiles(): void
    {
        $this->info("--- Сироты ---");
        $dbFiles = collect();
        DB::table('songs')->where('is_deleted', 0)
            ->select('file_path','file_path_2','vocal_url_1','vocal_url_2','instrumental_url_1','instrumental_url_2')
            ->orderBy('id')->chunk(2000, function ($songs) use (&$dbFiles) {
                foreach ($songs as $song) {
                    foreach (['file_path','file_path_2','vocal_url_1','vocal_url_2','instrumental_url_1','instrumental_url_2'] as $f) {
                        if ($song->$f) $dbFiles->push(basename(parse_url($song->$f, PHP_URL_PATH)));
                    }
                }
            });
        $set = $dbFiles->flip(); $orphans = 0;
        foreach (scandir($this->musicDir) as $file) {
            if ($file === '.' || $file === '..' || !str_ends_with($file, '.mp3')) continue;
            if (!isset($set[$file])) {
                $path = $this->musicDir . '/' . $file; $size = filesize($path);
                if (!$this->dryRun) { if (unlink($path)) { $this->freedBytes += $size; $this->deletedFiles++; } }
                else { $this->freedBytes += $size; $this->deletedFiles++; }
                $orphans++;
            }
        }
        $this->info("  Сироты: {$orphans}");
    }

    private function getProtectedSongIds(): array
    {
        return array_unique(array_merge(
            DB::table('chart_entries')->pluck('song_id')->toArray(),
            DB::table('favorite_songs')->pluck('song_id')->toArray()
        ));
    }

    private function deleteSongFiles(object $song): void
    {
        foreach ([$song->file_path,$song->file_path_2,$song->vocal_url_1??null,$song->vocal_url_2??null,$song->instrumental_url_1??null,$song->instrumental_url_2??null] as $u) {
            if ($u) $this->deleteFileByUrl($u);
        }
    }

    private function deleteFileByUrl(string $url): void
    {
        $p = parse_url($url, PHP_URL_PATH); if (!$p) return;
        $path = $this->musicDir . '/' . basename($p);
        if (!file_exists($path)) return; $size = filesize($path);
        if (!$this->dryRun) { if (unlink($path)) { $this->freedBytes += $size; $this->deletedFiles++; } }
        else { $this->freedBytes += $size; $this->deletedFiles++; }
    }

    private function pluralSongs(int $n): string
    {
        $m10 = $n % 10; $m100 = $n % 100;
        if ($m10 === 1 && $m100 !== 11) return 'песня';
        if ($m10 >= 2 && $m10 <= 4 && ($m100 < 10 || $m100 >= 20)) return 'песни';
        return 'песен';
    }
}