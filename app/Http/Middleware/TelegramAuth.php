<?php

namespace App\Http\Middleware;

use App\Services\TelegramAuthService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramAuth
{
    private TelegramAuthService $authService;

    public function __construct(TelegramAuthService $authService)
    {
        $this->authService = $authService;
    }

    public function handle(Request $request, Closure $next)
    {
        // Получаем токен: cookie → URL параметр tg_token
        $token = $request->cookie('tg_session') ?? $request->input('tg_token');
        $fromUrl = !empty($request->input('tg_token')) && empty($request->cookie('tg_session'));
        
        Log::debug('TelegramAuth middleware', [
            'has_cookie' => !empty($request->cookie('tg_session')),
            'has_url_token' => !empty($request->input('tg_token')),
            'token_preview' => $token ? substr($token, 0, 10) . '...' : 'none',
        ]);

        if (!$token) {
            Log::info('TelegramAuth: no token, redirecting to login');
            
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            
            return redirect('/login')->with('error', 'Требуется авторизация');
        }

        // Проверяем сессию
        $user = $this->authService->getUserBySessionToken($token);

        if (!$user) {
            Log::info('TelegramAuth: invalid/expired session', [
                'token_preview' => substr($token, 0, 10) . '...',
            ]);
            
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Session expired'], 401);
            }
            
            $cookie = cookie()->forget('tg_session');
            return redirect('/login')->with('error', 'Сессия истекла')->withCookie($cookie);
        }

        Log::debug('TelegramAuth: user authenticated', ['user_id' => $user->user_id]);

        $request->attributes->set('auth_user', $user);

        if (!$user->last_activity || $user->last_activity->lt(now()->subMinutes(5))) {
            $user->update(['last_activity' => now()]);
        }
        
        view()->share('authUser', $user);

        $response = $next($request);

        // Если токен пришёл из URL — ставим cookie
        if ($fromUrl) {
            $cookie = cookie(
                'tg_session',
                $token,
                60 * 24 * 7,
                '/',
                null,
                false,
                true,
                false,
                'None'
            );
            $response = $response->withCookie($cookie);
            Log::debug('TelegramAuth: cookie set from URL token');
        }

        return $response;
    }
}