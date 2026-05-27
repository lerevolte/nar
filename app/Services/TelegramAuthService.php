<?php

namespace App\Services;

use App\Models\TelegramSession;
use App\Models\User;
use App\Services\TelegramNotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TelegramAuthService
{
    private string $botToken;

    public function __construct()
    {
        $this->botToken = config('telegram.bot_token');
        
        if (empty($this->botToken)) {
            Log::error('TelegramAuthService: bot_token is empty! Check config/telegram.php');
        }
    }

    /**
     * Верификация данных от Telegram Login Widget
     */
    public function verifyLoginWidget(array $data): bool
    {
        if (!isset($data['hash'])) {
            Log::warning('verifyLoginWidget: no hash');
            return false;
        }

        $checkHash = $data['hash'];
        unset($data['hash']);

        // Сортируем параметры
        ksort($data);

        // Формируем строку для проверки
        $dataCheckString = collect($data)
            ->map(fn($value, $key) => "{$key}={$value}")
            ->implode("\n");

        // Вычисляем хэш
        $secretKey = hash('sha256', $this->botToken, true);
        $hash = hash_hmac('sha256', $dataCheckString, $secretKey);

        // Проверяем
        if (!hash_equals($hash, $checkHash)) {
            Log::warning('verifyLoginWidget: hash mismatch', [
                'expected' => $checkHash,
                'calculated' => $hash,
            ]);
            return false;
        }

        // Проверяем срок годности (не старше 24 часов)
        if (isset($data['auth_date'])) {
            $authDate = (int) $data['auth_date'];
            $age = time() - $authDate;
            if ($age > 86400) {
                Log::warning('verifyLoginWidget: expired', ['age_seconds' => $age]);
                return false;
            }
        }

        return true;
    }

    /**
     * Верификация initData от Telegram Mini App
     */
    public function verifyMiniApp(string $initData): ?array
    {
        // Парсим initData
        parse_str($initData, $data);

        if (!isset($data['hash'])) {
            Log::warning('verifyMiniApp: no hash');
            return null;
        }

        $checkHash = $data['hash'];
        unset($data['hash']);

        // Сортируем параметры
        ksort($data);

        // Формируем строку для проверки
        $dataCheckString = collect($data)
            ->map(fn($value, $key) => "{$key}={$value}")
            ->implode("\n");

        // Секретный ключ для Mini App (отличается от Login Widget!)
        $secretKey = hash_hmac('sha256', $this->botToken, 'WebAppData', true);
        $hash = hash_hmac('sha256', $dataCheckString, $secretKey);

        if (!hash_equals($hash, $checkHash)) {
            Log::warning('verifyMiniApp: hash mismatch');
            return null;
        }

        // Проверяем срок годности
        if (isset($data['auth_date'])) {
            $authDate = (int) $data['auth_date'];
            if (time() - $authDate > 86400) {
                Log::warning('verifyMiniApp: expired');
                return null;
            }
        }

        // Декодируем user
        if (isset($data['user'])) {
            $userData = json_decode($data['user'], true);
            if ($userData) {
                return $userData;
            }
        }

        return null;
    }

    /**
     * Получить или создать пользователя
     */
    public function getOrCreateUser(array $data): User
    {
        $userId = $data['id'];

        $user = User::where('user_id', $userId)->first();

        if (!$user) {
            Log::info('Creating new user', ['user_id' => $userId]);
            
            $user = User::create([
                'user_id' => $userId,
                'username' => $data['username'] ?? null,
                'first_name' => $data['first_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                'balance' => 1, // Бонусная песня для новых
            ]);
        } else {
            // Обновляем данные
            $user->update([
                'username' => $data['username'] ?? $user->username,
                'first_name' => $data['first_name'] ?? $user->first_name,
                'last_name' => $data['last_name'] ?? $user->last_name,
            ]);
        }

        // Обновляем аватар (если нет или раз в сутки)
        $this->updateUserAvatar($user);

        return $user;
    }

    /**
     * Обновить аватар пользователя из Telegram
     */
    protected function updateUserAvatar(User $user): void
    {
        try {
            // Обновляем аватар не чаще раза в сутки
            $lastUpdate = $user->updated_at;
            if ($user->avatar_url && $lastUpdate && $lastUpdate->diffInHours(now()) < 24) {
                return;
            }

            $telegramService = app(TelegramNotificationService::class);
            $avatarUrl = $telegramService->getUserAvatar($user->user_id);

            if ($avatarUrl) {
                $user->update(['avatar_url' => $avatarUrl]);
                Log::info("Updated avatar for user {$user->user_id}");
            }
        } catch (\Exception $e) {
            Log::warning("Failed to update avatar for user {$user->user_id}: " . $e->getMessage());
        }
    }

    /**
     * Создать сессию
     */
    public function createSession(User $user): TelegramSession
    {
        // Удаляем старые сессии пользователя (оставляем последние 5)
        $oldSessions = TelegramSession::where('user_id', $user->user_id)
            ->orderByDesc('created_at')
            ->skip(5)
            ->take(100)
            ->pluck('id');

        if ($oldSessions->isNotEmpty()) {
            TelegramSession::whereIn('id', $oldSessions)->delete();
        }

        // Создаём новую сессию
        $token = Str::random(64);
        $expiresAt = Carbon::now()->addDays(7);

        $session = TelegramSession::create([
            'user_id' => $user->user_id,
            'session_token' => $token,
            'expires_at' => $expiresAt,
        ]);

        Log::info('Session created', [
            'user_id' => $user->user_id,
            'session_id' => $session->id,
            'expires_at' => $expiresAt->toDateTimeString(),
        ]);

        return $session;
    }

    /**
     * Получить пользователя по токену сессии
     */
    public function getUserBySessionToken(string $token): ?User
    {
        $session = TelegramSession::where('session_token', $token)->first();

        if (!$session) {
            Log::debug('getUserBySessionToken: session not found');
            return null;
        }

        // Проверяем срок действия
        if (Carbon::now()->gt($session->expires_at)) {
            Log::info('getUserBySessionToken: session expired', [
                'expired_at' => $session->expires_at,
            ]);
            $session->delete();
            return null;
        }

        // Продлеваем сессию
        $session->update([
            'expires_at' => Carbon::now()->addDays(7),
        ]);

        $user = User::where('user_id', $session->user_id)->first();
        
        if (!$user) {
            Log::warning('getUserBySessionToken: user not found', [
                'user_id' => $session->user_id,
            ]);
        }

        return $user;
    }
}