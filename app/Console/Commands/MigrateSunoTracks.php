<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class MigrateSunoTracks extends Command
{
    protected $signature = 'songs:migrate-files';
    protected $description = 'Refresh links via Suno API and download tracks locally';

    public function handle()
    {
        // Снимаем лимит времени выполнения (на всякий случай)
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $targetDir = public_path('music');
        if (!File::exists($targetDir)) {
            File::makeDirectory($targetDir, 0755, true);
        }

        $baseUrl = 'https://narepite.site/music';
        $apiKey = env('SUNO_API_KEY');

        if (!$apiKey) {
            $this->error("❌ SUNO_API_KEY not found in .env");
            return;
        }

        $this->info("🚀 Starting migration with link refresh...");
        $this->info("📂 Target dir: $targetDir");

        // Считаем общее количество (чтобы понимать масштаб)
        $count = DB::table('songs')
            ->where(function($q) use ($baseUrl) {
                $q->where('file_path', 'NOT LIKE', "$baseUrl%")
                  ->orWhere('file_path_2', 'NOT LIKE', "$baseUrl%");
            })
            ->count();
            
        $this->info("📊 Total songs to process: $count");

        if ($count === 0) {
            $this->info("✅ Nothing to migrate!");
            return;
        }

        $query = DB::table('songs')
            ->where(function($q) use ($baseUrl) {
                $q->where('file_path', 'NOT LIKE', "$baseUrl%")
                  ->orWhere('file_path_2', 'NOT LIKE', "$baseUrl%");
            })
            ->orderBy('id', 'desc');

        // Используем chunkById для надежности при обновлениях, но простой chunk тоже ок здесь
        $query->chunk(10, function ($songs, $page) use ($targetDir, $baseUrl, $apiKey) {
            $this->info("\n📦 Processing chunk (10 items)...");
            
            foreach ($songs as $song) {
                $this->info("▶️ Processing Song #{$song->id}...");
                $this->processSong($song, $targetDir, $baseUrl, $apiKey);
                
                // Небольшая пауза
                usleep(2000000);
            }
        });

        $this->info("\n🏁 Migration completed!");
    }

    private function processSong($song, $targetDir, $baseUrl, $apiKey)
    {
        $url1 = $song->file_path;
        $url2 = $song->file_path_2;
        $hasTaskId = !empty($song->suno_task_id);

        // 1. Обновляем ссылки через API Suno
        if ($hasTaskId) {
            $this->line("   🔄 Refreshing links (Task: {$song->suno_task_id})...");
            $freshLinks = $this->checkMusicStatus($song->suno_task_id, $apiKey);
            
            if ($freshLinks && $freshLinks['status'] === 'completed') {
                $freshSongs = $freshLinks['songs'];
                $newUrl1 = $this->findUrlByAudioId($freshSongs, $song->audio_id_1, 0);
                $newUrl2 = $this->findUrlByAudioId($freshSongs, $song->audio_id_2, 1);

                if ($newUrl1) $url1 = $newUrl1;
                if ($newUrl2) $url2 = $newUrl2;
            } else {
                $status = $freshLinks['status'] ?? 'error';
                $this->warn("   ⚠️ Failed to refresh links. Status: $status");
            }
        } else {
            $this->line("   ℹ️ No Task ID, using existing links.");
        }

        // 2. Скачивание
        $updates = [];
        $changed = false;

        // Обработка v1
        if ($url1 && !str_starts_with($url1, $baseUrl)) {
            $this->line("   ⬇️ Downloading v1...");
            $localUrl = $this->downloadFile($url1, $song->user_id, $targetDir, $baseUrl, "v1_{$song->id}");
            if ($localUrl) {
                $updates['file_path'] = $localUrl;
                $changed = true;
                $this->info("      ✅ OK");
            } else {
                $this->error("      ❌ Failed");
            }
        }

        // Обработка v2
        if ($url2 && !str_starts_with($url2, $baseUrl)) {
            $this->line("   ⬇️ Downloading v2...");
            $localUrl = $this->downloadFile($url2, $song->user_id, $targetDir, $baseUrl, "v2_{$song->id}");
            if ($localUrl) {
                $updates['file_path_2'] = $localUrl;
                $changed = true;
                $this->info("      ✅ OK");
            } else {
                $this->error("      ❌ Failed");
            }
        }

        // 3. Сохранение в БД
        if ($changed && !empty($updates)) {
            DB::table('songs')->where('id', $song->id)->update($updates);
            $this->info("   💾 DB Updated for Song #{$song->id}");
        } else {
            $this->line("   ⏭ Skipped (no changes)");
        }
    }

    private function checkMusicStatus($taskId, $apiKey)
    {
        $apiUrl = env('SUNO_API_URL', 'https://api.sunoapi.org/api/v1');

        try {
            // Таймаут 10 сек на проверку статуса
            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->get("$apiUrl/generate/record-info", [
                    'taskId' => $taskId
                ]);

            if ($response->failed()) return null;

            $data = $response->json();
            if (($data['code'] ?? 0) !== 200) return null;

            $taskData = $data['data'] ?? [];
            if (($taskData['status'] ?? '') === 'SUCCESS') {
                $sunoData = $taskData['response']['sunoData'] ?? [];
                $songs = [];
                foreach ($sunoData as $clip) {
                    if (!empty($clip['audioUrl'])) {
                        $songs[] = [
                            'id' => $clip['id'] ?? '',
                            'audio_url' => $clip['audioUrl']
                        ];
                    }
                }
                return ['status' => 'completed', 'songs' => $songs];
            }
            return ['status' => 'pending'];

        } catch (\Exception $e) {
            $this->error("   🔥 API Error: " . $e->getMessage());
            return null;
        }
    }

    private function findUrlByAudioId($freshSongs, $targetAudioId, $defaultIndex)
    {
        if ($targetAudioId) {
            foreach ($freshSongs as $s) {
                if (($s['id'] ?? '') == $targetAudioId) return $s['audio_url'];
            }
        }
        return $freshSongs[$defaultIndex]['audio_url'] ?? null;
    }

    private function downloadFile($url, $userId, $targetDir, $baseUrl, $prefix)
    {
        try {
            $filename = "{$prefix}_{$userId}_" . Str::random(8) . ".mp3";
            $fullPath = $targetDir . '/' . $filename;

            // Таймаут 60 сек на скачивание
            $response = Http::timeout(60)->get($url);

            if ($response->successful() && strlen($response->body()) > 1000) {
                file_put_contents($fullPath, $response->body());
                return "$baseUrl/$filename";
            }
            return null;

        } catch (\Exception $e) {
            $this->error("   🔥 Download Error: " . $e->getMessage());
            return null;
        }
    }
}