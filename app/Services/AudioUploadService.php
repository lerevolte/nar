<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Приём пользовательских аудиофайлов для операций над треками
 * (upload-cover / upload-extend / add-instrumental / add-vocals / mashup).
 *
 * Файл сохраняется в public/uploads/tracks и отдаётся по публичному URL,
 * чтобы Suno API мог его скачать по uploadUrl.
 */
class AudioUploadService
{
    /** Разрешённые расширения. */
    public const ALLOWED_EXT = ['mp3', 'wav', 'm4a', 'mp4', 'ogg', 'webm', 'flac'];

    /**
     * Сохраняет загруженный файл и возвращает публичный URL.
     *
     * @throws \RuntimeException при превышении лимитов
     */
    public function store(UploadedFile $file, int $userId): string
    {
        $maxMb = (int) config('services.track_ops.upload_max_mb', 20);
        $maxSeconds = (int) config('services.track_ops.upload_max_seconds', 480);

        // 1. Размер
        if ($file->getSize() > $maxMb * 1024 * 1024) {
            throw new \RuntimeException("Файл слишком большой. Максимум {$maxMb} МБ.");
        }

        // 2. Расширение
        $ext = strtolower($file->getClientOriginalExtension() ?: 'mp3');
        if (! in_array($ext, self::ALLOWED_EXT, true)) {
            throw new \RuntimeException('Неподдерживаемый формат. Загрузите MP3, WAV, M4A, OGG или FLAC.');
        }

        // 3. Длительность (best-effort: точно для mp3/wav, иначе пропускаем)
        $duration = $this->estimateDurationSeconds($file->getRealPath(), $ext);
        if ($duration !== null && $duration > $maxSeconds) {
            $maxMin = intdiv($maxSeconds, 60);
            throw new \RuntimeException("Аудио слишком длинное. Максимум {$maxMin} мин.");
        }

        $dir = public_path('uploads/tracks');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $name = $userId.'-'.time().'-'.Str::random(8).'.'.$ext;
        $file->move($dir, $name);

        return 'https://narepite.site/uploads/tracks/'.$name;
    }

    /**
     * Грубая оценка длительности без внешних зависимостей.
     * Возвращает секунды или null, если определить не удалось.
     */
    public function estimateDurationSeconds(string $path, string $ext): ?int
    {
        try {
            if ($ext === 'wav') {
                return $this->wavDuration($path);
            }
            if ($ext === 'mp3') {
                return $this->mp3Duration($path);
            }
        } catch (\Throwable $e) {
            Log::warning('Audio duration estimate failed: '.$e->getMessage());
        }

        return null;
    }

    private function wavDuration(string $path): ?int
    {
        $fh = fopen($path, 'rb');
        if (! $fh) {
            return null;
        }

        try {
            $riff = fread($fh, 12); // "RIFF"<size>"WAVE"
            if (substr($riff, 0, 4) !== 'RIFF' || substr($riff, 8, 4) !== 'WAVE') {
                return null;
            }

            $byteRate = null;
            $dataSize = null;

            while (! feof($fh)) {
                $header = fread($fh, 8);
                if (strlen($header) < 8) {
                    break;
                }
                $chunkId = substr($header, 0, 4);
                $chunkSize = unpack('V', substr($header, 4, 4))[1];

                if ($chunkId === 'fmt ') {
                    $fmt = fread($fh, $chunkSize);
                    // byteRate — байт 8..11 в fmt-чанке
                    $byteRate = unpack('V', substr($fmt, 8, 4))[1];
                } elseif ($chunkId === 'data') {
                    $dataSize = $chunkSize;
                    break;
                } else {
                    fseek($fh, $chunkSize, SEEK_CUR);
                }
            }

            if ($byteRate && $dataSize) {
                return (int) round($dataSize / $byteRate);
            }
        } finally {
            fclose($fh);
        }

        return null;
    }

    private function mp3Duration(string $path): ?int
    {
        $fileSize = filesize($path);
        $fh = fopen($path, 'rb');
        if (! $fh) {
            return null;
        }

        try {
            // Пропускаем ID3v2-тег, если есть
            $head = fread($fh, 10);
            $offset = 0;
            if (substr($head, 0, 3) === 'ID3') {
                $b = unpack('C4', substr($head, 6, 4));
                $tagSize = ($b[1] << 21) | ($b[2] << 14) | ($b[3] << 7) | $b[4];
                $offset = 10 + $tagSize;
            }

            fseek($fh, $offset);
            $buffer = fread($fh, 4096);

            // Ищем первый валидный MP3-фрейм
            for ($i = 0; $i < strlen($buffer) - 4; $i++) {
                if (ord($buffer[$i]) !== 0xFF || (ord($buffer[$i + 1]) & 0xE0) !== 0xE0) {
                    continue;
                }

                $frame = $this->parseMp3FrameHeader(substr($buffer, $i, 4));
                if (! $frame) {
                    continue;
                }

                // VBR: ищем заголовок Xing/Info внутри первого фрейма
                $vbr = $this->parseXing(substr($buffer, $i), $frame);
                if ($vbr !== null) {
                    return $vbr;
                }

                // CBR-оценка: длительность = (размер аудио * 8) / битрейт
                $audioBytes = $fileSize - $offset;

                return (int) round(($audioBytes * 8) / ($frame['bitrate'] * 1000));
            }
        } finally {
            fclose($fh);
        }

        return null;
    }

    private function parseMp3FrameHeader(string $bytes): ?array
    {
        if (strlen($bytes) < 4) {
            return null;
        }

        $b1 = ord($bytes[1]);
        $b2 = ord($bytes[2]);
        $b3 = ord($bytes[3]);

        $versionBits = ($b1 >> 3) & 0x03; // 3 = MPEG1, 2 = MPEG2
        $layerBits = ($b1 >> 1) & 0x03;   // 1 = Layer III
        $bitrateIdx = ($b2 >> 4) & 0x0F;
        $sampleIdx = ($b2 >> 2) & 0x03;

        if ($versionBits === 1 || $layerBits !== 1 || $bitrateIdx === 0 || $bitrateIdx === 15 || $sampleIdx === 3) {
            return null;
        }

        // Битрейты Layer III (kbps)
        $bitrateV1 = [0, 32, 40, 48, 56, 64, 80, 96, 112, 128, 160, 192, 224, 256, 320];
        $bitrateV2 = [0, 8, 16, 24, 32, 40, 48, 56, 64, 80, 96, 112, 128, 144, 160];
        $rates = ($versionBits === 3) ? $bitrateV1 : $bitrateV2;

        $sampleV1 = [44100, 48000, 32000];
        $sampleV2 = [22050, 24000, 16000];
        $sampleV25 = [11025, 12000, 8000];
        $samples = match ($versionBits) {
            3 => $sampleV1,
            2 => $sampleV2,
            default => $sampleV25,
        };

        return [
            'bitrate' => $rates[$bitrateIdx],
            'sampleRate' => $samples[$sampleIdx],
            'isV1' => $versionBits === 3,
            'channelMono' => ((ord($bytes[3]) >> 6) & 0x03) === 3,
        ] + ['_b3' => $b3];
    }

    private function parseXing(string $frameData, array $frame): ?int
    {
        // Смещение Xing/Info зависит от версии и числа каналов
        $offset = $frame['isV1'] ? ($frame['channelMono'] ? 21 : 36) : ($frame['channelMono'] ? 13 : 21);
        if (strlen($frameData) < $offset + 12) {
            return null;
        }

        $tag = substr($frameData, $offset, 4);
        if ($tag !== 'Xing' && $tag !== 'Info') {
            return null;
        }

        $flags = unpack('N', substr($frameData, $offset + 4, 4))[1];
        if (! ($flags & 0x01)) { // нет счётчика фреймов
            return null;
        }

        $frameCount = unpack('N', substr($frameData, $offset + 8, 4))[1];
        $samplesPerFrame = $frame['isV1'] ? 1152 : 576;

        if ($frame['sampleRate'] <= 0) {
            return null;
        }

        return (int) round(($frameCount * $samplesPerFrame) / $frame['sampleRate']);
    }
}
