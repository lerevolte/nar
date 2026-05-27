<?php

namespace App\Jobs;

use App\Models\Song;
use App\Models\User;
use App\Services\SunoService;
use App\Services\TelegramNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CheckSongGenerationStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 60; // Максимум попыток (5 минут при интервале 5 сек)
    public int $backoff = 5; // Секунд между попытками

    protected int $songId;
    protected string $taskId;
    protected int $userId;

    public function __construct(int $songId, string $taskId, int $userId)
    {
        $this->songId = $songId;
        $this->taskId = $taskId;
        $this->userId = $userId;
    }

    public function handle(SunoService $sunoService, TelegramNotificationService $telegramService): void
    {
        $song = Song::find($this->songId);
        
        if (!$song) {
            Log::warning("CheckSongGenerationStatus: Song {$this->songId} not found");
            return;
        }

        // Если файлы уже есть — уведомление уже отправлено
        if ($song->file_path && $song->file_path_2) {
            Log::info("CheckSongGenerationStatus: Song {$this->songId} already completed");
            return;
        }

        $song = Song::find($this->songId);
        $result = $sunoService->checkStatus($this->taskId, $song->api_source ?? null);

        if ($result['status'] === 'completed' && !empty($result['songs'])) {
            $this->processCompletedSong($song, $result['songs'], $telegramService);
        } elseif ($result['status'] === 'failed') {
            Log::error("CheckSongGenerationStatus: Song {$this->songId} generation failed");
            $telegramService->sendMessage(
                $this->userId,
                "❌ К сожалению, генерация песни «{$song->title}» не удалась. Попробуй ещё раз."
            );
        } else {
            // Ещё не готово — повторяем
            Log::info("CheckSongGenerationStatus: Song {$this->songId} still processing, attempt {$this->attempts()}");
            $this->release($this->backoff);
        }
    }

    protected function processCompletedSong(Song $song, array $sunoSongs, TelegramNotificationService $telegramService): void
    {
        $updateData = [];

        // Скачиваем первый вариант
        if (!empty($sunoSongs[0]['audio_url']) && !$song->file_path) {
            $localUrl = $this->downloadSunoFile(
                $sunoSongs[0]['audio_url'],
                $song->user_id,
                $song->id,
                'v1'
            );
            if ($localUrl) {
                $updateData['file_path'] = $localUrl;
                $updateData['audio_id_1'] = $sunoSongs[0]['id'] ?? null;
            }
        }

        // Скачиваем второй вариант
        if (!empty($sunoSongs[1]['audio_url']) && !$song->file_path_2) {
            $localUrl = $this->downloadSunoFile(
                $sunoSongs[1]['audio_url'],
                $song->user_id,
                $song->id,
                'v2'
            );
            if ($localUrl) {
                $updateData['file_path_2'] = $localUrl;
                $updateData['audio_id_2'] = $sunoSongs[1]['id'] ?? null;
            }
        }

        if (!empty($updateData)) {
            $song->update($updateData);
            $song->refresh();
            Log::info("CheckSongGenerationStatus: Downloaded files for song {$song->id}", $updateData);

            // Получаем актуальный баланс пользователя
            $user = User::where('user_id', $this->userId)->first();
            $balance = $user ? $user->balance : 0;

            // Отправляем уведомление
            $telegramService->notifySongReady(
                $this->userId,
                $song->title,
                $song->file_path,
                $song->file_path_2,
                $balance
            );
        }
    }

    protected function downloadSunoFile(string $url, int $userId, int $songId, string $prefix): ?string
    {
        $targetDir = public_path('music');
        $baseUrl = 'https://narepite.site/music';

        if (!File::exists($targetDir)) {
            File::makeDirectory($targetDir, 0755, true);
        }

        try {
            $filename = "{$prefix}_{$songId}_{$userId}_" . Str::random(8) . ".mp3";
            $fullPath = $targetDir . '/' . $filename;

            Log::info("Job downloading Suno file: {$url} -> {$fullPath}");

            $response = Http::timeout(60)->get($url);

            if ($response->successful() && strlen($response->body()) > 1000) {
                file_put_contents($fullPath, $response->body());
                $localUrl = "{$baseUrl}/{$filename}";
                Log::info("Job downloaded successfully: {$localUrl}");
                return $localUrl;
            }

            Log::warning("Job download failed or file too small for URL: {$url}");
            return null;

        } catch (\Exception $e) {
            Log::error("Job download error: " . $e->getMessage());
            return null;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("CheckSongGenerationStatus job failed for song {$this->songId}: " . $exception->getMessage());
        
        // Уведомляем пользователя об ошибке
        try {
            $song = Song::find($this->songId);
            $title = $song ? $song->title : 'Неизвестная песня';
            
            app(TelegramNotificationService::class)->sendMessage(
                $this->userId,
                "❌ Произошла ошибка при генерации песни «{$title}». Попробуй ещё раз или обратись в поддержку."
            );
        } catch (\Exception $e) {
            Log::error("Failed to send error notification: " . $e->getMessage());
        }
    }
}