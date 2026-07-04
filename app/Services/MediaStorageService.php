<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Единая точка хранения пользовательских медиа (музыка, обложки, загрузки).
 *
 * Управляется флагом config('services.media.driver'):
 *  - 'local' — пишем в public_path() и отдаём по MEDIA_LOCAL_URL (как раньше);
 *  - 's3'    — кладём в бакет S3-диска и отдаём по его публичному URL.
 *
 * Логический путь (например, "music/track.mp3") одинаков для обоих режимов,
 * поэтому переключение провайдера не требует правок в вызывающем коде.
 */
class MediaStorageService
{
    private string $driver;

    public function __construct()
    {
        $this->driver = (string) config('services.media.driver', 'local');
    }

    public function isS3(): bool
    {
        return $this->driver === 's3';
    }

    /**
     * Сохранить сырое содержимое по логическому пути. Возвращает публичный URL.
     */
    public function put(string $path, string $contents): string
    {
        $path = ltrim($path, '/');

        if ($this->isS3()) {
            Storage::disk('s3')->put($path, $contents, 'public');

            return $this->url($path);
        }

        $full = public_path($path);
        $dir = dirname($full);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($full, $contents);

        return $this->url($path);
    }

    /**
     * Сохранить загруженный пользователем файл.
     */
    public function putUploadedFile(UploadedFile $file, string $path): string
    {
        return $this->put($path, (string) file_get_contents($file->getRealPath()));
    }

    /**
     * Скачать удалённый файл (например, готовый трек из Suno) и сохранить.
     * Возвращает публичный URL либо null при неудаче.
     */
    public function putFromUrl(string $sourceUrl, string $path, int $minBytes = 1000): ?string
    {
        try {
            $response = Http::timeout(60)->get($sourceUrl);

            if ($response->successful() && strlen($response->body()) > $minBytes) {
                return $this->put($path, $response->body());
            }

            Log::warning("MediaStorage: download failed or too small for URL: {$sourceUrl}");

            return null;
        } catch (\Throwable $e) {
            Log::error('MediaStorage putFromUrl error: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Публичный URL для логического пути.
     */
    public function url(string $path): string
    {
        $path = ltrim($path, '/');

        if ($this->isS3()) {
            return Storage::disk('s3')->url($path);
        }

        $base = rtrim((string) config('services.media.local_url', config('app.url')), '/');

        return $base.'/'.$path;
    }
}
