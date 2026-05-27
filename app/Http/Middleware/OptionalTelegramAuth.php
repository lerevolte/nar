<?php

namespace App\Http\Middleware;

use App\Services\TelegramAuthService;
use Closure;
use Illuminate\Http\Request;

class OptionalTelegramAuth
{
    private TelegramAuthService $authService;

    public function __construct(TelegramAuthService $authService)
    {
        $this->authService = $authService;
    }

    public function handle(Request $request, Closure $next)
    {
        $token = $request->cookie('tg_session') ?? $request->input('tg_token');

        if ($token) {
            $user = $this->authService->getUserBySessionToken($token);
            if ($user) {
                $request->attributes->set('auth_user', $user);
                view()->share('authUser', $user);

                if (!$user->last_activity || $user->last_activity->lt(now()->subMinutes(5))) {
                    $user->update(['last_activity' => now()]);
                }
            }
        }

        // Гарантируем, что authUser всегда определён в лейауте (для гостя — null)
        if (!view()->shared('authUser')) {
            view()->share('authUser', null);
        }

        return $next($request);
    }
}