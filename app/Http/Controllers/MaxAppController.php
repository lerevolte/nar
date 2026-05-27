<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TelegramAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MaxAppController extends Controller
{
    /**
     * Точка входа в MAX Mini App
     */
    public function entry(Request $request)
    {
        $redirect = $request->input('redirect', '/dashboard');
        return response()
            ->view('maxapp.init', compact('redirect'))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * API: Авторизация из MAX Mini App
     */
    public function auth(Request $request)
    {
        $initData = $request->input('initData');

        Log::info('MAX Mini App auth attempt', [
            'has_initData' => !empty($initData),
            'initData_length' => strlen($initData ?? ''),
        ]);

        if (!$initData) {
            return response()->json(['error' => 'initData required'], 400);
        }

        // Верификация подписи MAX
        $userData = $this->verifyMaxInitData($initData);

        if (!$userData) {
            Log::warning('MAX Mini App auth failed: invalid initData');
            return response()->json(['error' => 'Invalid initData'], 401);
        }

        Log::info('MAX Mini App auth success', ['user_id' => $userData['id']]);

        // Получаем или создаём пользователя
        $authService = app(TelegramAuthService::class);
        $user = $this->getOrCreateMaxUser($userData);
        $session = $authService->createSession($user);

        return response()->json([
            'success' => true,
            'token' => $session->session_token,
            'user' => [
                'id' => $user->user_id,
                'username' => $user->username,
                'first_name' => $user->first_name,
                'balance' => $user->balance,
            ],
        ]);
    }

    public function authAjax(Request $request)
    {
        $initData = $request->input('initData');

        if (!$initData) {
            return response()->json(['success' => false, 'error' => 'initData required']);
        }

        $userData = $this->verifyMaxInitData($initData);

        if (!$userData) {
            return response()->json(['success' => false, 'error' => 'Invalid initData']);
        }

        Log::info('MAX Mini App auth AJAX success', ['user_id' => $userData['id']]);

        $authService = app(TelegramAuthService::class);
        $user = $this->getOrCreateMaxUser($userData);
        $session = $authService->createSession($user);

        return response()->json([
            'success' => true,
            'token' => $session->session_token
        ]);
    }

    /**
     * API: Авторизация + серверный редирект (для MAX WebView)
     */
    public function authAndRedirect(Request $request)
    {
        $initData = $request->input('initData');
        $redirect = $request->input('redirect', '/dashboard');

        if (!$initData) {
            return redirect('/maxapp')->with('error', 'initData required');
        }

        $userData = $this->verifyMaxInitData($initData);

        if (!$userData) {
            return redirect('/maxapp')->with('error', 'Invalid initData');
        }

        Log::info('MAX Mini App auth+redirect success', ['user_id' => $userData['id']]);

        $authService = app(TelegramAuthService::class);
        $user = $this->getOrCreateMaxUser($userData);
        $session = $authService->createSession($user);

        $maxAge = 60 * 24 * 7;



        return redirect($redirect)
            ->cookie('tg_session', $session->session_token, $maxAge, '/', null, false, true, false, 'None')
            ->cookie('miniapp', '1', $maxAge, '/', null, false, false, false, 'None')
            ->cookie('max_app', '1', $maxAge, '/', null, false, false, false, 'None');
    }

    /**
     * Верификация initData от MAX
     * Алгоритм: https://dev.max.ru/docs/webapps/validation
     *
     * 1. Парсим initData (key=value&key=value)
     * 2. Сохраняем hash, убираем из параметров
     * 3. URL-декодируем значения
     * 4. Сортируем по ключам
     * 5. Формируем строку key1=value1\nkey2=value2
     * 6. secret_key = HMAC_SHA256("WebAppData", bot_token)
     * 7. calculated_hash = hex(HMAC_SHA256(secret_key, launch_params))
     * 8. Сравниваем с оригинальным hash
     */
    private function verifyMaxInitData(string $initData): ?array
    {
        $maxBotToken = config('max.bot_token');

        if (empty($maxBotToken)) {
            Log::error('MAX bot token not configured');
            return null;
        }

        // Парсим параметры
        $params = [];
        $pairs = explode('&', $initData);
        foreach ($pairs as $pair) {
            $parts = explode('=', $pair, 2);
            if (count($parts) === 2) {
                $params[$parts[0]] = $parts[1];
            }
        }

        // Проверяем наличие hash
        if (!isset($params['hash'])) {
            Log::warning('MAX initData: no hash');
            return null;
        }

        $originalHash = $params['hash'];
        unset($params['hash']);

        // URL-декодируем значения
        foreach ($params as $key => $value) {
            $params[$key] = urldecode($value);
        }

        // Сортируем по ключам
        ksort($params);

        // Формируем строку для проверки
        $launchParams = collect($params)
            ->map(fn($value, $key) => "{$key}={$value}")
            ->implode("\n");

        // Вычисляем secret_key: HMAC_SHA256("WebAppData", bot_token)
        $secretKey = hash_hmac('sha256', $maxBotToken, 'WebAppData', true);

        // Вычисляем hash: HMAC_SHA256(secret_key, launch_params)
        $calculatedHash = hash_hmac('sha256', $launchParams, $secretKey);

        // Сравниваем
        if (!hash_equals($calculatedHash, $originalHash)) {
            Log::warning('MAX initData: hash mismatch', [
                'expected' => $originalHash,
                'calculated' => $calculatedHash,
            ]);
            return null;
        }

        // Проверяем auth_date (не старше 24 часов)
        if (isset($params['auth_date'])) {
            $authDate = (int) $params['auth_date'];
            // MAX передаёт auth_date в секундах (или миллисекундах)
            if ($authDate > 1e12) {
                $authDate = (int) ($authDate / 1000); // миллисекунды → секунды
            }
            $age = time() - $authDate;
            if ($age > 86400) {
                Log::warning('MAX initData: expired', ['age_seconds' => $age]);
                return null;
            }
        }

        // Декодируем user
        if (isset($params['user'])) {
            $userData = json_decode($params['user'], true);
            if ($userData && isset($userData['id'])) {
                return $userData;
            }
        }

        Log::warning('MAX initData: no user data');
        return null;
    }

    /**
     * Получить или создать пользователя MAX
     */
    private function getOrCreateMaxUser(array $data): User
    {
        $maxUserId = $data['id'] + 10000000000;

        // Проверяем, есть ли склейка — MAX-юзер может быть linked к Telegram-аккаунту
        $linkedPrimaryId = \DB::table('linked_accounts')
            ->where('linked_user_id', $maxUserId)
            ->value('primary_user_id');

        if ($linkedPrimaryId) {
            // Аккаунт склеен — авторизуем под primary
            $primaryUser = User::where('user_id', $linkedPrimaryId)->first();
            if ($primaryUser) {
                Log::info('MAX user linked to primary', [
                    'max_user_id' => $maxUserId,
                    'primary_user_id' => $linkedPrimaryId,
                ]);
                return $primaryUser;
            }
        }

        // Обычная логика — ищем или создаём MAX-юзера
        $user = User::where('user_id', $maxUserId)->first();

        if (!$user) {
            Log::info('Creating new MAX user', ['user_id' => $maxUserId]);
            $user = User::create([
                'user_id' => $maxUserId,
                'username' => $data['username'] ?? null,
                'first_name' => $data['first_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                'balance' => 1,
                'utm_source' => 'max_webapp',
            ]);
        } else {
            $user->update([
                'username' => $data['username'] ?? $user->username,
                'first_name' => $data['first_name'] ?? $user->first_name,
                'last_name' => $data['last_name'] ?? $user->last_name,
            ]);
        }

        return $user;
    }
}