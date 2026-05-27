<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DetectMiniApp
{
    /**
     * Определяет, работает ли приложение в контексте Telegram Mini App
     */
    public function handle(Request $request, Closure $next)
    {
        // Проверяем признаки Mini App
        $isMiniApp = false;
        
        // 1. Referer содержит telegram
        $referer = $request->header('Referer', '');
        if (str_contains($referer, 't.me') || str_contains($referer, 'telegram')) {
            $isMiniApp = true;
        }

        // 2. User-Agent содержит TelegramBot или Telegram
        $userAgent = $request->header('User-Agent', '');
        if (str_contains($userAgent, 'Telegram')) {
            $isMiniApp = true;
        }

        // 3. Есть параметр tgWebAppData или initData
        if ($request->has('tgWebAppData') || $request->has('initData')) {
            $isMiniApp = true;
        }

        // 4. Cookie miniapp=1 (устанавливается при первом входе)
        if ($request->cookie('miniapp') === '1') {
            $isMiniApp = true;
        }

        // 5. Параметр miniapp=1 в URL
        if ($request->input('miniapp') === '1') {
            $isMiniApp = true;
        }

        // Передаём в request и view
        $request->attributes->set('is_miniapp', $isMiniApp);
        view()->share('isMiniApp', $isMiniApp);

        $response = $next($request);

        // Устанавливаем cookie если Mini App
        if ($isMiniApp && !$request->cookie('miniapp')) {
            $response->cookie('miniapp', '1', 60 * 24 * 7); // 7 дней
        }

        return $response;
    }
}