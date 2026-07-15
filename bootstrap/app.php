<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // SEO: единое зеркало (www → non-www), слеши, регистр, /blog/ → /articles/.
        // Глобально (не в web-группе), чтобы редиректить и URL без совпавшего маршрута (/blog/*)
        $middleware->prepend(\App\Http\Middleware\SeoRedirect::class);

        $middleware->alias([
            'tg.auth' => \App\Http\Middleware\TelegramAuth::class,
            'miniapp' => \App\Http\Middleware\DetectMiniApp::class,
            'maxapp' => \App\Http\Middleware\DetectMaxApp::class,
            'telegram.auth.optional' => \App\Http\Middleware\OptionalTelegramAuth::class,
        ]);

        // Идентификатор устройства для защиты голосования по чартам от накрутки
        $middleware->append(\App\Http\Middleware\EnsureDeviceId::class);

        $middleware->encryptCookies(except: ['tg_session', 'miniapp', 'maxapp', 'did']);

    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
