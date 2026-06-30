<?php

namespace App\Jobs;

use App\Mail\SongFailedMail;
use App\Mail\SongReadyMail;
use App\Models\GuestOrder;
use App\Models\Song;
use App\Models\User;
use App\Services\SunoService;
use App\Services\TelegramNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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

        if (! $song) {
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

        if ($result['status'] === 'completed' && ! empty($result['songs'])) {
            $this->processCompletedSong($song, $result['songs'], $telegramService);
        } elseif ($result['status'] === 'failed') {
            Log::error("CheckSongGenerationStatus: Song {$this->songId} generation failed");
            $this->refundAndNotify($song, $telegramService);
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
        if (! empty($sunoSongs[0]['audio_url']) && ! $song->file_path) {
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
        if (! empty($sunoSongs[1]['audio_url']) && ! $song->file_path_2) {
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

        if (! empty($updateData)) {
            $song->update($updateData);
            $song->refresh();
            Log::info("CheckSongGenerationStatus: Downloaded files for song {$song->id}", $updateData);

            // Получаем актуальный баланс пользователя
            $user = User::where('user_id', $this->userId)->first();
            $balance = $user ? $user->balance : 0;

            // Отправляем уведомление в Telegram
            $telegramService->notifySongReady(
                $this->userId,
                $song->title,
                $song->file_path,
                $song->file_path_2,
                $balance
            );

            // Письмо «песня готова» — если у пользователя есть email
            if ($user && ! empty($user->email)) {
                try {
                    Mail::to($user->email)->queue(new SongReadyMail(
                        title: (string) $song->title,
                        filePath: $song->file_path,
                        filePath2: $song->file_path_2,
                    ));
                } catch (\Exception $e) {
                    Log::error('SongReadyMail failed: '.$e->getMessage());
                }
            }
        }
    }

    /**
     * Возврат песни на баланс при ошибке генерации + уведомления.
     * Идемпотентно по полю songs.refunded_at — двойного возврата не будет.
     */
    protected function refundAndNotify(Song $song, TelegramNotificationService $telegramService): void
    {
        if ($song->refunded_at) {
            Log::info("CheckSongGenerationStatus: Song {$song->id} already refunded");

            return;
        }

        // replace_section оплачивается отдельно (вне баланса песен) —
        // возвращать +1 песню на баланс при его провале нельзя.
        $isOffBalance = ($song->operation_type ?? 'generate') === 'replace_section';

        $user = User::where('user_id', $this->userId)->first();
        if ($user && ! $isOffBalance) {
            $user->increment('balance', 1);
        }

        $song->update(['is_deleted' => 1, 'refunded_at' => now()]);

        // Связанный гостевой заказ помечаем как failed (для кнопки «Повторить»)
        $order = GuestOrder::where('song_id', $song->id)->first();
        if ($order) {
            $order->update(['status' => 'failed']);
        }

        Log::info("CheckSongGenerationStatus: refunded 1 song to user {$this->userId} for song {$song->id}");

        // Telegram-уведомление
        try {
            $refundNote = $isOffBalance
                ? 'Средства за операцию не списаны.'
                : 'Мы вернули 1 песню на твой баланс — попробуй ещё раз.';
            $telegramService->sendMessage(
                $this->userId,
                "❌ К сожалению, генерация песни «{$song->title}» не удалась. {$refundNote}"
            );
        } catch (\Exception $e) {
            Log::error('Refund telegram notify failed: '.$e->getMessage());
        }

        // Письмо об ошибке (пользователь мог уйти с сайта) — с доступами в ЛК
        if ($user && ! empty($user->email)) {
            try {
                $retryUrl = $order
                    ? rtrim((string) config('app.url'), '/').'/create-song/success?order='.$order->token
                    : null;

                // Если аккаунт только что создан — пароль ещё в кеше (15 мин), покажем его в письме.
                $password = $order ? (Cache::get("guest_credentials:{$order->token}")['password'] ?? null) : null;

                Mail::to($user->email)->queue(new SongFailedMail(
                    title: (string) $song->title,
                    retryUrl: $retryUrl,
                    login: $user->email,
                    password: $password,
                ));
            } catch (\Exception $e) {
                Log::error('SongFailedMail failed: '.$e->getMessage());
            }
        }
    }

    protected function downloadSunoFile(string $url, int $userId, int $songId, string $prefix): ?string
    {
        $targetDir = public_path('music');
        $baseUrl = 'https://narepite.site/music';

        if (! File::exists($targetDir)) {
            File::makeDirectory($targetDir, 0755, true);
        }

        try {
            $filename = "{$prefix}_{$songId}_{$userId}_".Str::random(8).'.mp3';
            $fullPath = $targetDir.'/'.$filename;

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
            Log::error('Job download error: '.$e->getMessage());

            return null;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("CheckSongGenerationStatus job failed for song {$this->songId}: ".$exception->getMessage());

        // Возврат на баланс + уведомления (идемпотентно по refunded_at)
        try {
            $song = Song::find($this->songId);

            if ($song && ! $song->file_path) {
                $this->refundAndNotify($song, app(TelegramNotificationService::class));
            }
        } catch (\Exception $e) {
            Log::error('Failed to refund/notify after job failure: '.$e->getMessage());
        }
    }
}
