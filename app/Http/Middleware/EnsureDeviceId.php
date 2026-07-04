<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Гарантирует наличие постоянного идентификатора устройства (cookie `did`).
 * Используется для защиты голосования по чартам от накрутки с нескольких аккаунтов.
 * Кука не шифруется (см. encryptCookies except в bootstrap/app.php), чтобы её
 * можно было читать и на api-роутах, где нет middleware расшифровки cookie.
 */
class EnsureDeviceId
{
    /** Срок жизни куки, минут (2 года). */
    private const TTL_MINUTES = 60 * 24 * 365 * 2;

    public function handle(Request $request, Closure $next): Response
    {
        $deviceId = $request->cookie('did');

        if (! $deviceId || ! preg_match('/^[a-f0-9\-]{16,64}$/i', $deviceId)) {
            $deviceId = (string) Str::uuid();

            // Отдаём куку в ответе и сразу кладём её в текущий запрос,
            // чтобы контроллеры увидели device_id уже на первом запросе.
            Cookie::queue('did', $deviceId, self::TTL_MINUTES);
            $request->cookies->set('did', $deviceId);
        }

        $request->attributes->set('device_id', $deviceId);

        return $next($request);
    }
}
