<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Models\ChartEntry;
use App\Models\Song;
use App\Services\ChartService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

Route::get('/', [App\Http\Controllers\LandingController::class, 'index'])->name('home');

Route::middleware('telegram.auth.optional')->group(function () {
    // Публичная страница генерации песни (посадочная)
    Route::get('/create-song', [App\Http\Controllers\PublicGenerateController::class, 'index'])->name('public.generate');

    // API для публичной генерации (без авторизации, с rate-limit)
    Route::post('/api/public-generate/lyrics', [App\Http\Controllers\PublicGenerateController::class, 'generateLyrics']);
    Route::post('/api/public-generate/prepare-lyrics', [App\Http\Controllers\PublicGenerateController::class, 'prepareUserLyrics']);
    Route::post('/api/public-generate/translate', [App\Http\Controllers\PublicGenerateController::class, 'translateLyrics']);
    Route::post('/api/public-generate/improve', [App\Http\Controllers\PublicGenerateController::class, 'improveLyrics']);
    Route::post('/api/public-generate/order', [App\Http\Controllers\PublicGenerateController::class, 'createOrder']);

    Route::get('/api/public-generate/order-status', [App\Http\Controllers\PublicGenerateController::class, 'checkOrderStatus']);
    Route::get('/create-song/success', [App\Http\Controllers\PublicGenerateController::class, 'success'])->name('public.generate.success');
    Route::post('/api/public-generate/start', [App\Http\Controllers\PublicGenerateController::class, 'startGeneration']);
    Route::post('/api/public-generate/retry', [App\Http\Controllers\PublicGenerateController::class, 'retryGeneration']);
    Route::get('/api/public-generate/song-status', [App\Http\Controllers\PublicGenerateController::class, 'songStatus']);
    Route::get('/api/public-generate/credentials', [App\Http\Controllers\PublicGenerateController::class, 'getCredentials']);
    Route::post('/api/public-generate/auto-login', [App\Http\Controllers\PublicGenerateController::class, 'autoLogin']);

    Route::post('/api/public-generate/create-free', [App\Http\Controllers\PublicGenerateController::class, 'createFreeOrder']);

    // «Свой голос» (гостевой, разовый) — обёртки над Kie VoiceService, без авторизации
    Route::post('/api/public-generate/voice/upload', [App\Http\Controllers\PublicGenerateController::class, 'voiceUpload']);
    Route::post('/api/public-generate/voice/create', [App\Http\Controllers\PublicGenerateController::class, 'voiceCreate']);
    Route::get('/api/public-generate/voice/phrase', [App\Http\Controllers\PublicGenerateController::class, 'voicePhrase']);
    Route::post('/api/public-generate/voice/generate', [App\Http\Controllers\PublicGenerateController::class, 'voiceGenerate']);
    Route::get('/api/public-generate/voice/status', [App\Http\Controllers\PublicGenerateController::class, 'voiceStatus']);

    Route::get('/support', [\App\Http\Controllers\SupportController::class, 'index'])->name('support.index');
    Route::post('/support/upload', [\App\Http\Controllers\SupportController::class, 'upload'])->name('support.upload');
    Route::post('/support/send', [\App\Http\Controllers\SupportController::class, 'send'])->name('support.send');

});
Route::get('/articles', [App\Http\Controllers\ArticleController::class, 'index'])->name('public.articles.index');
Route::get('/articles/{slug}', [App\Http\Controllers\ArticleController::class, 'show'])->name('public.articles.show');
Route::get('/pages/{slug}', [App\Http\Controllers\PageController::class, 'show'])->name('public.pages.show');
Route::get('/pages/{slug}/{childSlug}', [App\Http\Controllers\PageController::class, 'show'])->name('public.pages.show.child');
Route::get('/sitemap.xml', [\App\Http\Controllers\SitemapController::class, 'index']);
Route::get('/fix/generate-covers', function () {
    $sunoService = app(\App\Services\SunoService::class);
    $generateController = app(\App\Http\Controllers\GenerateController::class);

    // Берём все песни из чартов без обложки
    $songIds = \App\Models\ChartEntry::select('song_id')
        ->distinct()
        ->pluck('song_id');

    $songs = \App\Models\Song::whereIn('id', $songIds)
        ->whereNull('cover_url')
        ->whereNotNull('suno_task_id')
        ->get();

    $results = [];

    foreach ($songs as $song) {
        try {
            $result = $sunoService->checkStatus($song->suno_task_id);

            if ($result['status'] === 'completed' && ! empty($result['songs'][0]['image_url'])) {
                $imageUrl = $result['songs'][0]['image_url'];

                // Скачиваем обложку
                $coverUrl = $generateController->downloadSunoCover($imageUrl, $song->user_id, $song->id);

                if ($coverUrl) {
                    $song->update(['cover_url' => $coverUrl]);
                    $results[] = "✅ #{$song->id} {$song->title} — обложка сохранена";
                } else {
                    $results[] = "❌ #{$song->id} {$song->title} — не удалось скачать";
                }
            } else {
                $results[] = "⏭ #{$song->id} {$song->title} — нет image_url в Suno";
            }
        } catch (\Exception $e) {
            $results[] = "❌ #{$song->id} {$song->title} — ошибка: ".$e->getMessage();
        }

        usleep(200000); // 200ms между запросами
    }

    return '<pre>'.implode("\n", $results)."\n\nВсего: ".count($results).'</pre>';
});
// Публичные роуты
Route::get('/auth', function () {

    if (request()->n) {
        exit();
        $user = App\Models\User::where('email', 'truba77@mail.ru')->first();
        // $user->update([
        //     //'email' => 'reva.artem.olegovich@gmail.com',
        //   'password' => Hash::make('pass10301675281'),
        // ]);
        // dd($user);
        // $user = App\Models\User::where('user_id', 10188006520)->first();
        // $user->update([
        //     'email' => 'reva.artem.olegovich@gmail.com',
        //     'password' => Hash::make('pass10188006520'),
        // ]);
        // die();
        $protected = array_unique(array_merge(
            DB::table('chart_entries')->pluck('song_id')->toArray(),
            DB::table('favorite_songs')->pluck('song_id')->toArray()
        ));

        $songs = DB::table('songs')
            ->where('is_deleted', 0)
            ->whereNotNull('file_path')
            ->whereNotIn('id', $protected)
            ->whereIn('user_id', function ($q) {
                $q->select('user_id')->from('songs')
                    ->where('is_deleted', 0)->whereNotNull('file_path')
                    ->groupBy('user_id');
            })
            ->orderBy('created_at', 'asc')
            ->limit(2000)
            ->get();
        // foreach ($songs as $song) {
        //     echo $song->created_at.'<br>';
        // }/send 10188006520 reva.artem.olegovich@gmail.com
        echo 'Найдено: '.$songs->count()." песен\n";
        // die();
        $dir = '/var/www/narepite-web/public/music';
        $deleted = 0;
        $freed = 0;

        foreach ($songs as $song) {
            foreach ([$song->file_path, $song->file_path_2, $song->vocal_url_1, $song->vocal_url_2, $song->instrumental_url_1, $song->instrumental_url_2] as $url) {
                if ($url) {
                    $path = $dir.'/'.basename(parse_url($url, PHP_URL_PATH));
                    if (file_exists($path)) {
                        $freed += filesize($path);
                        unlink($path);
                    }
                }
            }
            DB::table('songs')->where('id', $song->id)->update([
                'file_path' => null, 'file_path_2' => null,
                'vocal_url_1' => null, 'vocal_url_2' => null,
                'instrumental_url_1' => null, 'instrumental_url_2' => null,
            ]);
            $deleted++;
        }

        echo "Удалено: {$deleted} песен, освобождено: ".round($freed / 1024 / 1024)." МБ\n";
        exit();
    }

    // dd(App\Models\Song::find(5938));
    // $baseUrl = 'https://narepite.site/music';
    // $query = DB::table('songs')
    //     ->where(function($q) use ($baseUrl) {
    //         $q->where('file_path', 'NOT LIKE', "$baseUrl%")
    //           ->orWhere('file_path_2', 'NOT LIKE', "$baseUrl%");
    //     })
    //     ->orderBy('id', 'desc')->count();
    // echo $query;
    return redirect()->route('login');
})->name('home');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login/password', [AuthController::class, 'passwordLogin'])->name('password.login');
Route::get('/register', [App\Http\Controllers\AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [App\Http\Controllers\AuthController::class, 'register'])->name('register.submit');
Route::get('/auth/telegram/callback', [AuthController::class, 'telegramCallback'])->name('telegram.callback');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Восстановление пароля по email
Route::get('/forgot-password', [App\Http\Controllers\PasswordResetController::class, 'showRequest'])->name('password.request');
Route::post('/forgot-password', [App\Http\Controllers\PasswordResetController::class, 'sendLink'])->name('password.email');
Route::get('/reset-password/{token}', [App\Http\Controllers\PasswordResetController::class, 'showReset'])->name('password.reset');
Route::post('/reset-password', [App\Http\Controllers\PasswordResetController::class, 'reset'])->name('password.update');
Route::get('/miniapp', [App\Http\Controllers\MiniAppController::class, 'entry']);
Route::get('/miniapp/init', [App\Http\Controllers\MiniAppController::class, 'init']);

Route::get('/maxapp', [App\Http\Controllers\MaxAppController::class, 'entry']);

// QR-коды — публичная страница
Route::get('/qr/{token}', [App\Http\Controllers\QrCodeController::class, 'show'])->name('qr.show');
Route::get('/qr/image/{code}', [App\Http\Controllers\QrCodeController::class, 'image'])->name('qr.image');
Route::get('/qr/{token}/download', [App\Http\Controllers\QrCodeController::class, 'downloadZip'])->name('qr.download');
Route::get('/qr/{token}/download-csv', [App\Http\Controllers\QrCodeController::class, 'downloadCsv'])->name('qr.download.csv');

Route::get('/dl/{token}', [DashboardController::class, 'downloadByToken']);

Route::post('/max/auth-redirect', [App\Http\Controllers\MaxAppController::class, 'authAndRedirect']);
Route::post('/max/auth-ajax', [App\Http\Controllers\MaxAppController::class, 'authAjax']);
// Защищённые роуты
Route::middleware(['tg.auth', 'miniapp', 'maxapp'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/voices', [\App\Http\Controllers\VoiceController::class, 'page'])->name('voices');
    // Voice cloning API
    Route::prefix('api/voice')->name('api.voice.')->group(function () {
        Route::get('/list', [\App\Http\Controllers\VoiceController::class, 'index'])->name('list');
        Route::post('/upload-audio', [\App\Http\Controllers\VoiceController::class, 'uploadAudio'])->name('upload-audio');
        Route::post('/create', [\App\Http\Controllers\VoiceController::class, 'create'])->name('create');
        Route::post('/recreate', [\App\Http\Controllers\VoiceController::class, 'recreate'])->name('recreate');
        Route::post('/check-phrase', [\App\Http\Controllers\VoiceController::class, 'checkPhraseStatus'])->name('check-phrase');
        Route::post('/submit-verify', [\App\Http\Controllers\VoiceController::class, 'submitVerifyAudio'])->name('submit-verify');
        Route::post('/check-status', [\App\Http\Controllers\VoiceController::class, 'checkVoiceStatus'])->name('check-status');
        Route::delete('/delete/{id}', [\App\Http\Controllers\VoiceController::class, 'destroy'])->name('delete');
    });

    Route::prefix('api/persona')->name('api.persona.')->group(function () {
        Route::get('/list', [\App\Http\Controllers\PersonaController::class, 'list'])->name('list');
        Route::post('/create', [\App\Http\Controllers\PersonaController::class, 'create'])->name('create');
        Route::delete('/delete/{id}', [\App\Http\Controllers\PersonaController::class, 'destroy'])->name('delete');
    });
    // Songs
    Route::get('/songs', function (\Illuminate\Http\Request $request, ChartService $chartService) {
        $user = $request->get('auth_user');

        $songs = Song::where('user_id', $user->user_id)
            ->notDeleted()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Получаем текущий чарт для бейджей
        $chart = $chartService->getOrCreateCurrentChart();

        // ID песен в текущем чарте (для бейджа "В чарте")
        $chartSongIds = ChartEntry::where('chart_id', $chart->id)
            ->pluck('song_id')
            ->toArray();

        return view('dashboard.songs', compact('songs', 'chartSongIds'));
    })->name('songs.index');

    Route::get('/songs/{id}', [DashboardController::class, 'showSong'])->name('songs.show');
    Route::post('/songs/{id}/download/{variant?}', [DashboardController::class, 'createDownloadLink'])
        ->where('variant', '[12]')
        ->name('songs.download');

    // Charts (заглушка)
    Route::get('/charts', function () {
        return view('dashboard.charts');
    })->name('charts.index');

    // Profile
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    Route::post('/profile/credentials', [ProfileController::class, 'updateCredentials'])->name('profile.update-credentials');

    Route::get('/auth/telegram/link', [App\Http\Controllers\AuthController::class, 'linkTelegramCallback'])->name('telegram.link');

    Route::get('/create', [App\Http\Controllers\GenerateController::class, 'create'])->name('generate.create');

    // Charts
    Route::get('/charts', [App\Http\Controllers\ChartController::class, 'index'])->name('charts.index');
    Route::get('/charts/valentine', [App\Http\Controllers\ChartController::class, 'valentine'])->name('charts.valentine');
    Route::get('/charts/all-time', [App\Http\Controllers\ChartController::class, 'allTime'])->name('charts.allTime');
    Route::get('/charts/archive', [App\Http\Controllers\ChartController::class, 'archive'])->name('charts.archive');
    Route::get('/charts/{id}', [App\Http\Controllers\ChartController::class, 'show'])->name('charts.show');

    // Payment
    Route::get('/buy', [App\Http\Controllers\PaymentController::class, 'index'])->name('payment.index');
    Route::get('/payment/success', [App\Http\Controllers\PaymentController::class, 'success'])->name('payment.success');

    Route::get('/admin/qr', [App\Http\Controllers\QrCodeController::class, 'adminForm'])->name('admin.qr.index');
    Route::post('/admin/qr/generate', [App\Http\Controllers\QrCodeController::class, 'generate'])->name('admin.qr.generate');
    Route::get('/admin/qr/{token}', [App\Http\Controllers\QrCodeController::class, 'show'])->name('admin.qr.show');

    Route::get('/admin/broadcast', [App\Http\Controllers\BroadcastController::class, 'index'])->name('admin.broadcast');

    Route::get('/admin/promo', [App\Http\Controllers\PromoCodeController::class, 'index'])->name('admin.promo');

    // Тестовые инструменты (только для одного админа) — прогон сценариев без реальной оплаты
    Route::get('/admin/test-tools', [App\Http\Controllers\TestToolsController::class, 'index'])->name('admin.test-tools');
    Route::post('/admin/test-tools/pay', [App\Http\Controllers\TestToolsController::class, 'pay'])->name('admin.test-tools.pay');
    Route::post('/admin/test-tools/fail', [App\Http\Controllers\TestToolsController::class, 'fail'])->name('admin.test-tools.fail');
    Route::post('/admin/test-tools/mail', [App\Http\Controllers\TestToolsController::class, 'mail'])->name('admin.test-tools.mail');
    Route::post('/admin/test-tools/reset', [App\Http\Controllers\TestToolsController::class, 'reset'])->name('admin.test-tools.reset');

    // Admin: articles
    Route::get('/admin/articles', [App\Http\Controllers\ArticleController::class, 'adminIndex'])->name('admin.articles.index');
    Route::get('/admin/articles/create', [App\Http\Controllers\ArticleController::class, 'adminCreate'])->name('admin.articles.create');
    Route::post('/admin/articles/create', [App\Http\Controllers\ArticleController::class, 'adminStore'])->name('admin.articles.store');
    Route::get('/admin/articles/{id}/edit', [App\Http\Controllers\ArticleController::class, 'adminEdit'])->name('admin.articles.edit');
    Route::post('/admin/articles/{id}/edit', [App\Http\Controllers\ArticleController::class, 'adminStore'])->name('admin.articles.update');
    Route::post('/admin/articles/{id}/delete', [App\Http\Controllers\ArticleController::class, 'adminDestroy'])->name('admin.articles.delete');
    Route::post('/admin/articles/upload-image', [App\Http\Controllers\ArticleController::class, 'adminUploadImage'])->name('admin.articles.upload-image');

    // Admin: pages
    Route::get('/admin/pages', [App\Http\Controllers\PageController::class, 'adminIndex'])->name('admin.pages.index');
    Route::get('/admin/pages/create', [App\Http\Controllers\PageController::class, 'adminCreate'])->name('admin.pages.create');
    Route::post('/admin/pages/create', [App\Http\Controllers\PageController::class, 'adminStore'])->name('admin.pages.store');
    Route::get('/admin/pages/{id}/edit', [App\Http\Controllers\PageController::class, 'adminEdit'])->name('admin.pages.edit');
    Route::post('/admin/pages/{id}/edit', [App\Http\Controllers\PageController::class, 'adminStore'])->name('admin.pages.update');
    Route::post('/admin/pages/{id}/delete', [App\Http\Controllers\PageController::class, 'adminDestroy'])->name('admin.pages.delete');

    Route::prefix('admin/static-pages')->name('admin.static-pages.')->group(function () {
        Route::get('/', [\App\Http\Controllers\StaticPageController::class, 'adminIndex'])->name('index');
        Route::get('/create', [\App\Http\Controllers\StaticPageController::class, 'adminCreate'])->name('create');
        Route::post('/', [\App\Http\Controllers\StaticPageController::class, 'adminStore'])->name('store');
        Route::post('/upload-image', [\App\Http\Controllers\StaticPageController::class, 'adminUploadImage'])->name('upload-image');
        Route::get('/{id}/edit', [\App\Http\Controllers\StaticPageController::class, 'adminEdit'])->name('edit');
        Route::post('/{id}', [\App\Http\Controllers\StaticPageController::class, 'adminStore'])->name('update');
        Route::delete('/{id}', [\App\Http\Controllers\StaticPageController::class, 'adminDestroy'])->name('destroy');

    });

    Route::post('/api/save-ym-client', function (\Illuminate\Http\Request $request) {
        $user = $request->get('auth_user');
        if (! $user) {
            return response()->json(['ok' => false], 401);
        }

        $ymClientId = $request->input('ym_client_id');
        if ($ymClientId && preg_match('/^\d{10,20}$/', $ymClientId)) {
            \Illuminate\Support\Facades\DB::table('users')
                ->where('user_id', $user->user_id)
                ->update(['ym_client_id' => $ymClientId]);
        }

        return response()->json(['ok' => true]);
    });
});

Route::middleware(['tg.auth', 'miniapp', 'maxapp'])->prefix('admin/support')->name('admin.support.')->group(function () {
    Route::get('/', [\App\Http\Controllers\SupportController::class, 'adminIndex'])->name('index');
    Route::get('/{id}', [\App\Http\Controllers\SupportController::class, 'adminShow'])->name('show');
    Route::post('/{id}', [\App\Http\Controllers\SupportController::class, 'adminUpdate'])->name('update');
    Route::delete('/{id}', [\App\Http\Controllers\SupportController::class, 'adminDestroy'])->name('destroy');
});
// Диагностика cookie
Route::get('/debug-cookie', function (\Illuminate\Http\Request $request) {
    return response()->json([
        'all_cookies' => $request->cookies->all(),
        'tg_session' => $request->cookie('tg_session'),
        'header_cookie' => $request->header('Cookie'),
    ]);
});

Route::get('/{slug}', [\App\Http\Controllers\StaticPageController::class, 'show'])
    ->name('public.static-pages.show');
