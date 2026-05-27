<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\TelegramSession;
use App\Services\TelegramAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
     * Страница входа
     */
    public function login(Request $request)
    {
        $token = $request->cookie('tg_session');
        
        if ($token) {
            $authService = app(TelegramAuthService::class);
            $user = $authService->getUserBySessionToken($token);
            
            if ($user) {
                return redirect()->route('dashboard');
            }
        }

        $error = session('error');
        $success = session('success');
        return view('auth.login', compact('error', 'success'));
    }

    /**
     * Страница регистрации
     */
    public function showRegister(Request $request)
    {
        $token = $request->cookie('tg_session');
        
        if ($token) {
            $authService = app(TelegramAuthService::class);
            $user = $authService->getUserBySessionToken($token);
            if ($user) {
                return redirect()->route('dashboard');
            }
        }

        return view('auth.register');
    }

    /**
     * Регистрация по email
     */
    public function register(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'first_name' => 'nullable|string|max:100',
        ], [
            'email.required' => 'Введите email',
            'email.email' => 'Некорректный email',
            'email.unique' => 'Этот email уже зарегистрирован',
            'password.required' => 'Введите пароль',
            'password.min' => 'Пароль минимум 6 символов',
            'password.confirmed' => 'Пароли не совпадают',
        ]);

        // Генерируем уникальный user_id для email-пользователя
        // Диапазон 900_000_000+ чтобы не пересекаться с Telegram ID
        $userId = $this->generateEmailUserId();

        $user = User::create([
            'user_id' => $userId,
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
            'first_name' => $request->input('first_name') ?: null,
            'balance' => 1, // бонусная песня
        ]);

        Log::info('Email registration', ['user_id' => $userId, 'email' => $user->email]);

        // Создаём сессию
        $authService = app(TelegramAuthService::class);
        $session = $authService->createSession($user);

        $cookie = cookie('tg_session', $session->session_token, 60 * 24 * 7, '/', null, true, true, false, 'Lax');

        return redirect()->route('dashboard')
            ->withCookie($cookie)
            ->with('success', 'Добро пожаловать! 🎵');
    }

    /**
     * Вход по email/user_id + пароль
     */
    public function passwordLogin(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $login = trim($request->input('login'));
        $password = $request->input('password');

        // Ищем по email
        $user = User::where('email', $login)->first();

        // Или по user_id
        if (!$user && is_numeric($login)) {
            $user = User::where('user_id', (int) $login)->first();
        }

        if (!$user) {
            return redirect('/login')->with('error', 'Пользователь не найден');
        }

        if (!$user->password || !Hash::check($password, $user->password)) {
            return redirect('/login')->with('error', 'Неверный пароль');
        }

        $authService = app(TelegramAuthService::class);
        $session = $authService->createSession($user);

        $cookie = cookie('tg_session', $session->session_token, 60 * 24 * 7, '/', null, true, true, false, 'Lax');

        return redirect()->route('dashboard')->withCookie($cookie);
    }

    /**
     * Callback от Telegram Login Widget (вход)
     */
    public function telegramCallback(Request $request, TelegramAuthService $authService)
    {
        Log::info('Telegram callback received', $request->all());

        $data = $request->all();

        if (!$authService->verifyLoginWidget($data)) {
            Log::warning('Telegram callback: verification failed');
            return redirect('/login')->with('error', 'Ошибка верификации Telegram');
        }

        $user = $authService->getOrCreateUser($data);

        if (empty($user->password)) {
            $user->update([
                'password' => Hash::make((string) $user->user_id),
            ]);
        }

        $session = $authService->createSession($user);

        $cookie = cookie('tg_session', $session->session_token, 60 * 24 * 7, '/', null, true, true, false, 'Lax');

        return redirect()->route('dashboard')->withCookie($cookie);
    }

    /**
     * Привязка Telegram к существующему аккаунту (callback)
     */
    public function linkTelegramCallback(Request $request, TelegramAuthService $authService)
    {
        // Текущий пользователь (авторизован по cookie)
        $currentUser = $request->get('auth_user');

        if (!$currentUser) {
            return redirect('/login')->with('error', 'Требуется авторизация');
        }

        $data = $request->all();

        if (!$authService->verifyLoginWidget($data)) {
            return redirect()->route('profile')->with('error', 'Ошибка верификации Telegram');
        }

        $telegramId = (int) $data['id'];

        // Проверяем не привязан ли уже этот Telegram к другому аккаунту
        $existingUser = User::where('user_id', $telegramId)->first();

        if ($existingUser && $existingUser->user_id !== $currentUser->user_id) {
            // Telegram уже привязан к другому аккаунту
            // Мёрджим: переносим данные с email-аккаунта на Telegram-аккаунт
            $this->mergeAccounts($currentUser, $existingUser, $data);

            // Создаём новую сессию для Telegram-аккаунта
            $session = $authService->createSession($existingUser);
            $cookie = cookie('tg_session', $session->session_token, 60 * 24 * 7, '/', null, true, true, false, 'Lax');

            return redirect()->route('profile')
                ->withCookie($cookie)
                ->with('success', 'Telegram подключён! Аккаунты объединены.');
        }

        if ($existingUser && $existingUser->user_id === $currentUser->user_id) {
            return redirect()->route('profile')->with('success', 'Telegram уже подключён!');
        }

        // Telegram ID свободен — обновляем user_id текущего пользователя
        $oldUserId = $currentUser->user_id;
        $this->migrateUserId($oldUserId, $telegramId);

        // Обновляем данные из Telegram
        $currentUser->update([
            'user_id' => $telegramId,
            'username' => $data['username'] ?? $currentUser->username,
            'first_name' => $data['first_name'] ?? $currentUser->first_name,
            'last_name' => $data['last_name'] ?? $currentUser->last_name,
        ]);

        Log::info("Telegram linked", ['old_user_id' => $oldUserId, 'new_user_id' => $telegramId]);

        // Новая сессия
        $session = $authService->createSession($currentUser->fresh());
        $cookie = cookie('tg_session', $session->session_token, 60 * 24 * 7, '/', null, true, true, false, 'Lax');

        return redirect()->route('profile')
            ->withCookie($cookie)
            ->with('success', 'Telegram успешно подключён!');
    }

    /**
     * Выход
     */
    public function logout(Request $request)
    {
        $token = $request->cookie('tg_session');
        
        if ($token) {
            TelegramSession::where('session_token', $token)->delete();
        }

        $cookie = cookie()->forget('tg_session');
        return redirect('/login')->withCookie($cookie);
    }

    /**
     * Страница входа (алиас)
     */
    public function showLogin()
    {
        return view('auth.login');
    }

    // ========================================
    // PRIVATE HELPERS
    // ========================================

    /**
     * Генерация уникального user_id для email-пользователя
     * Диапазон: 9_000_000_000+ (не пересекается с Telegram ID)
     */
    private function generateEmailUserId(): int
    {
        // Telegram user_id обычно до ~7 млрд, берём 9 млрд+
        $maxExisting = User::where('user_id', '>=', 9000000000)->max('user_id');
        return $maxExisting ? $maxExisting + 1 : 9000000001;
    }

    /**
     * Перенести все записи с одного user_id на другой
     */
    private function migrateUserId(int $oldId, int $newId): void
    {
        $tables = [
            'songs' => 'user_id',
            'drafts' => 'user_id',
            'payments' => 'user_id',
            'chart_entries' => 'user_id',
            'chart_votes' => 'user_id',
            'chart_rewards' => 'user_id',
            'favorite_songs' => 'user_id',
            'telegram_sessions' => 'user_id',
            'transactions' => 'user_id',
            'web_notifications' => 'user_id',
            'used_promo_codes' => 'user_id',
        ];

        foreach ($tables as $table => $column) {
            try {
                DB::table($table)->where($column, $oldId)->update([$column => $newId]);
            } catch (\Exception $e) {
                Log::warning("migrateUserId: skip {$table}: " . $e->getMessage());
            }
        }

        Log::info("Migrated user_id {$oldId} -> {$newId}");
    }

    /**
     * Объединить email-аккаунт с существующим Telegram-аккаунтом
     */
    private function mergeAccounts(User $emailUser, User $telegramUser, array $telegramData): void
    {
        $oldId = $emailUser->user_id;
        $newId = $telegramUser->user_id;

        // Переносим данные с email-аккаунта
        $this->migrateUserId($oldId, $newId);

        // Переносим баланс
        $telegramUser->increment('balance', $emailUser->balance);

        // Обновляем email если у Telegram-аккаунта нет
        if (!$telegramUser->email && $emailUser->email) {
            $telegramUser->update(['email' => $emailUser->email]);
        }

        // Обновляем пароль если у Telegram-аккаунта нет
        if (!$telegramUser->password && $emailUser->password) {
            $telegramUser->update(['password' => $emailUser->password]);
        }

        // Удаляем email-аккаунт
        $emailUser->delete();

        Log::info("Accounts merged", [
            'email_user_id' => $oldId,
            'telegram_user_id' => $newId,
            'balance_added' => $emailUser->balance,
        ]);
    }
}