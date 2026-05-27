<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DetectMaxApp
{
    /**
     * Определяет, работает ли приложение в контексте MAX Mini App
     */
    public function handle(Request $request, Closure $next)
    {
        $isMaxApp = false;

        // Cookie max_app=1 (устанавливается при авторизации через MAX)
        if ($request->cookie('max_app') === '1') {
            $isMaxApp = true;
        }

        // Параметр max_app=1 в URL
        if ($request->input('max_app') === '1') {
            $isMaxApp = true;
        }

        // Referer содержит max.ru
        $referer = $request->header('Referer', '');
        if (str_contains($referer, 'max.ru')) {
            $isMaxApp = true;
        }

        $request->attributes->set('is_max_app', $isMaxApp);
        view()->share('isMaxApp', $isMaxApp);

        $response = $next($request);

        if ($isMaxApp && !$request->cookie('max_app')) {
            $response->cookie('max_app', '1', 60 * 24 * 7);
        }

        return $response;
    }
}