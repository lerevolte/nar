<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TelegramNotificationService
{
    protected string $botToken;
    protected string $apiUrl;

    public function __construct()
    {
        $this->botToken = config('telegram.bot_token');
        $this->apiUrl = "https://api.telegram.org/bot{$this->botToken}";
    }

    /**
     * Отправить сообщение пользователю
     */
    public function sendMessage(int|string $chatId, string $message, array $options = []): bool
    {
        try {
            $response = Http::post("{$this->apiUrl}/sendMessage", array_merge([
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
            ], $options));

            if ($response->successful()) {
                Log::info("Telegram message sent to {$chatId}");
                return true;
            }

            Log::error("Telegram API error: " . $response->body());
            return false;

        } catch (\Exception $e) {
            Log::error("Telegram send error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Отправить аудио файл
     */
    public function sendAudio(int|string $chatId, string $audioUrl, string $caption = '', string $title = '', string $performer = ''): bool
    {
        try {
            // Скачиваем файл во временную директорию
            $tempFile = tempnam(sys_get_temp_dir(), 'audio_');
            $audioContent = file_get_contents($audioUrl);
            
            if ($audioContent === false) {
                Log::error("Failed to download audio: {$audioUrl}");
                return false;
            }
            
            file_put_contents($tempFile, $audioContent);

            $response = Http::attach(
                'audio',
                file_get_contents($tempFile),
                $title ? "{$title}.mp3" : 'audio.mp3'
            )->post("{$this->apiUrl}/sendAudio", [
                'chat_id' => $chatId,
                'caption' => $caption,
                'parse_mode' => 'HTML',
                'title' => $title,
                'performer' => $performer,
            ]);

            // Удаляем временный файл
            @unlink($tempFile);

            if ($response->successful()) {
                Log::info("Telegram audio sent to {$chatId}");
                return true;
            }

            Log::error("Telegram audio API error: " . $response->body());
            return false;

        } catch (\Exception $e) {
            Log::error("Telegram audio send error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Уведомить победителя чарта
     */
    public function notifyChartWinner(int $userId, int $position, string $songTitle, int $songsReward, string $chartName, ?string $audioUrl = null): bool
    {
        $medals = [
            1 => '🥇',
            2 => '🥈', 
            3 => '🥉',
        ];

        $medal = $medals[$position] ?? '🏆';

        $message = "{$medal} <b>Поздравляем!</b>\n\n";
        $message .= "Твоя песня «<b>{$songTitle}</b>» заняла <b>{$position} место</b> в чарте «{$chartName}»!\n\n";
        $message .= "🎁 Награда: <b>+{$songsReward}</b> " . $this->pluralize($songsReward, 'песня', 'песни', 'песен') . "\n\n";
        $message .= "Продолжай создавать музыку! 🎵";

        return $this->sendMessage($userId, $message);
    }

    /**
     * Отправить результаты чарта с аудио победителей
     */
    public function sendChartResultsWithAudio(int|string $chatId, string $chartName, array $winners): bool
    {
        $medals = ['🥇', '🥈', '🥉'];

        // Сначала отправляем текстовое сообщение
        $message = "🏆 <b>Чарт «{$chartName}» завершён!</b>\n\n";
        $message .= "🎧 Слушай победителей:\n\n";

        foreach ($winners as $index => $winner) {
            $medal = $medals[$index] ?? '•';
            $position = $index + 1;
            $message .= "{$medal} <b>{$position} место</b>: {$winner['song']}\n";
            $message .= "    👤 {$winner['author']} • ❤️ {$winner['votes']} голосов\n\n";
        }

        $message .= "📊 Участвуй в новом недельном чарте!";

        $this->sendMessage($chatId, $message);

        // Затем отправляем аудио каждого победителя
        foreach ($winners as $index => $winner) {
            if (!empty($winner['audio_url'])) {
                $medal = $medals[$index] ?? '';
                $position = $index + 1;
                
                $caption = "{$medal} {$position} место — {$winner['votes']} голосов";
                
                $this->sendAudio(
                    $chatId,
                    $winner['audio_url'],
                    $caption,
                    $winner['song'],
                    $winner['author']
                );

                // Задержка между отправками
                usleep(100000); // 100ms
            }
        }

        return true;
    }

    /**
     * Уведомить всех участников о закрытии чарта (с аудио)
     */
    public function notifyChartResults(array $userIds, string $chartName, array $winners): bool
    {
        $successCount = 0;
        
        foreach ($userIds as $userId) {
            if ($this->sendChartResultsWithAudio($userId, $chartName, $winners)) {
                $successCount++;
            }
            // Задержка чтобы не превысить лимиты Telegram
            usleep(100000); // 100ms
        }

        Log::info("Chart results sent to {$successCount}/" . count($userIds) . " users", [
            'chart' => $chartName,
        ]);

        return $successCount > 0;
    }

    /**
     * Уведомить пользователя о готовой песне
     */
    public function notifySongReady(int $userId, string $title, ?string $audioUrl1, ?string $audioUrl2, int $balance): bool
    {
        try {
            // Отправляем текстовое сообщение
            $message = "✅ <b>Песня готова!</b>\n";
            $message .= "Второй вариант в подарок! 🎁";
            
            $this->sendMessage($userId, $message);

            // Отправляем первый вариант
            if ($audioUrl1) {
                $this->sendAudioWithRetry(
                    $userId,
                    $audioUrl1,
                    '',
                    "{$title} (вариант 1)",
                    'На Репите'
                );
            }

            // Отправляем второй вариант
            if ($audioUrl2) {
                usleep(500000); // 500ms задержка
                $this->sendAudioWithRetry(
                    $userId,
                    $audioUrl2,
                    '',
                    "{$title} (вариант 2)",
                    'На Репите'
                );
            }

            // Отправляем баланс
            usleep(300000); // 300ms задержка
            $balanceMessage = "💰 Твой баланс: {$balance} " . $this->pluralize($balance, 'песня', 'песни', 'песен');
            $this->sendMessage($userId, $balanceMessage);

            Log::info("Song ready notification sent to user {$userId}", [
                'title' => $title,
                'balance' => $balance
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error("Failed to send song ready notification: " . $e->getMessage(), [
                'user_id' => $userId,
                'title' => $title
            ]);
            return false;
        }
    }


    /**
     * Отправить аудио с повторной попыткой при ошибке
     */
    protected function sendAudioWithRetry(int|string $chatId, string $audioUrl, string $caption, string $title, string $performer, int $maxRetries = 2): bool
    {
        // Формируем имя файла для скачивания
        $filename = Str::slug($title, '_') . '.mp3';
        
        // Сразу скачиваем и загружаем файл — так title и performer точно применятся
        Log::info("Downloading and uploading audio file for {$chatId}", ['title' => $title]);
        
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $audioContent = Http::timeout(60)->get($audioUrl)->body();
                
                if (strlen($audioContent) < 1000) {
                    Log::error("Downloaded audio file too small, attempt {$attempt}");
                    usleep(500000);
                    continue;
                }

                $response = Http::timeout(60)
                    ->attach('audio', $audioContent, $filename)
                    ->post("{$this->apiUrl}/sendAudio", [
                        'chat_id' => $chatId,
                        'caption' => $caption,
                        'parse_mode' => 'HTML',
                        'title' => $title,
                        'performer' => $performer,
                    ]);

                if ($response->successful()) {
                    Log::info("Telegram audio uploaded successfully to {$chatId}", ['title' => $title]);
                    return true;
                }

                Log::warning("Telegram audio upload attempt {$attempt} failed: " . $response->body());

            } catch (\Exception $e) {
                Log::warning("Telegram audio upload attempt {$attempt} error: " . $e->getMessage());
            }

            usleep(500000); // 500ms между попытками
        }

        // Fallback — отправляем ссылку
        Log::error("All attempts failed, sending link as fallback");
        $this->sendMessage($chatId, "🎵 {$title}: {$audioUrl}");
        return false;
    }

    /**
     * Получить URL аватара пользователя из Telegram
     */
    public function getUserAvatar(int $userId): ?string
    {
        try {
            // Получаем фотографии профиля
            $response = Http::post("{$this->apiUrl}/getUserProfilePhotos", [
                'user_id' => $userId,
                'limit' => 1,
            ]);

            if (!$response->successful()) {
                Log::warning("Failed to get profile photos for user {$userId}: " . $response->body());
                return null;
            }

            $data = $response->json();
            
            if (empty($data['result']['photos'][0])) {
                Log::info("No profile photo for user {$userId}");
                return null;
            }

            // Берём самое большое фото (последнее в массиве)
            $photos = $data['result']['photos'][0];
            $photo = end($photos);
            $fileId = $photo['file_id'];

            // Получаем путь к файлу
            $fileResponse = Http::post("{$this->apiUrl}/getFile", [
                'file_id' => $fileId,
            ]);

            if (!$fileResponse->successful()) {
                Log::warning("Failed to get file path for user {$userId}: " . $fileResponse->body());
                return null;
            }

            $fileData = $fileResponse->json();
            $filePath = $fileData['result']['file_path'] ?? null;

            if (!$filePath) {
                return null;
            }

            // Формируем URL для скачивания
            $avatarUrl = "https://api.telegram.org/file/bot{$this->botToken}/{$filePath}";
            
            Log::info("Got avatar URL for user {$userId}");
            return $avatarUrl;

        } catch (\Exception $e) {
            Log::error("Error getting avatar for user {$userId}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Склонение слов
     */
    protected function pluralize(int $number, string $one, string $few, string $many): string
    {
        $n = abs($number) % 100;
        $n1 = $n % 10;

        if ($n > 10 && $n < 20) return $many;
        if ($n1 > 1 && $n1 < 5) return $few;
        if ($n1 == 1) return $one;

        return $many;
    }
}