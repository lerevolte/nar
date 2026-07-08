<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Отправка сообщений пользователям MAX-мессенджера через Bot API.
 * В отличие от Telegram, где chat_id == user_id, у MAX chat_id хранится
 * в отдельной колонке users.chat_id (добирается ботом при обращении).
 */
class MaxNotificationService
{
    protected string $botToken;

    protected string $apiUrl = 'https://platform-api.max.ru/messages';

    public function __construct()
    {
        $this->botToken = (string) config('max.bot_token', '');
    }

    public function isConfigured(): bool
    {
        return $this->botToken !== '';
    }

    /**
     * Отправить сообщение в MAX по chat_id.
     *
     * @param  string  $format  '' | 'html' | 'markdown' — формат разметки текста
     */
    public function sendMessage(int|string $chatId, string $message, string $format = 'html'): bool
    {
        if (! $this->isConfigured()) {
            Log::error('MAX send skipped: MAX_BOT_TOKEN not configured');

            return false;
        }

        $payload = ['text' => $message];
        if ($format !== '') {
            $payload['format'] = $format;
        }

        try {
            $response = Http::withHeaders(['Authorization' => $this->botToken])
                ->post($this->apiUrl.'?chat_id='.$chatId, $payload);

            if ($response->successful()) {
                return true;
            }

            Log::error("MAX API error for chat {$chatId}: ".$response->body());

            return false;
        } catch (\Exception $e) {
            Log::error("MAX send error for chat {$chatId}: ".$e->getMessage());

            return false;
        }
    }
}
