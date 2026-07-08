<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\StemController;
use Illuminate\Support\Facades\Route;

// API для Mini App
Route::post('/auth/miniapp', [AuthController::class, 'miniAppAuth']);
Route::get('/auth/check', [AuthController::class, 'checkAuth']);
Route::post('/miniapp/auth', [App\Http\Controllers\MiniAppController::class, 'auth']);
Route::post('/max/miniapp/auth', [App\Http\Controllers\MaxAppController::class, 'auth']);

Route::post('/landing/play', [App\Http\Controllers\LandingController::class, 'incrementPlay']);
// Защищённые API роуты
Route::middleware(['tg.auth', 'miniapp', 'maxapp'])->group(function () {
    Route::get('/user', function (\Illuminate\Http\Request $request) {
        $user = $request->get('auth_user');

        return response()->json([
            'id' => $user->user_id,
            'username' => $user->username,
            'first_name' => $user->first_name,
            'balance' => $user->balance,
        ]);
    });
    Route::post('/landing/toggle-like', [App\Http\Controllers\LandingController::class, 'toggleLike']);
    Route::get('/songs', [App\Http\Controllers\DashboardController::class, 'apiSongs']);
    Route::post('/songs/{id}/delete', [App\Http\Controllers\DashboardController::class, 'deleteSong']);
    Route::post('/songs/{id}/update-title', [App\Http\Controllers\DashboardController::class, 'updateSongTitle']);
    Route::post('/song/restore', [App\Http\Controllers\GenerateController::class, 'restoreSong']);
    Route::post('/song/cover', [App\Http\Controllers\GenerateController::class, 'fetchCover']);

    Route::post('/generate/format-structure', [App\Http\Controllers\GenerateController::class, 'formatStructure']);
    Route::post('/generate/lyrics', [App\Http\Controllers\GenerateController::class, 'generateLyrics']);
    Route::post('/generate/translate', [App\Http\Controllers\GenerateController::class, 'translateLyrics']);
    Route::post('/generate/improve', [App\Http\Controllers\GenerateController::class, 'improveLyrics']);
    Route::post('/generate/music', [App\Http\Controllers\GenerateController::class, 'generateMusic']);
    Route::get('/generate/status', [App\Http\Controllers\GenerateController::class, 'checkStatus']);

    Route::post('/stems/separate', [StemController::class, 'separate']);
    Route::get('/stems/status', [StemController::class, 'checkStatus']);

    // Операции над треками (extend / cover / instrumental / vocals / mashup / replace)
    Route::post('/track-ops/upload', [App\Http\Controllers\TrackEditController::class, 'upload']);
    Route::post('/track-ops/rephrase', [App\Http\Controllers\TrackEditController::class, 'rephrase']);
    Route::post('/track-ops/extend', [App\Http\Controllers\TrackEditController::class, 'extend']);
    Route::post('/track-ops/upload-cover', [App\Http\Controllers\TrackEditController::class, 'uploadCover']);
    Route::post('/track-ops/upload-extend', [App\Http\Controllers\TrackEditController::class, 'uploadExtend']);
    Route::post('/track-ops/add-instrumental', [App\Http\Controllers\TrackEditController::class, 'addInstrumental']);
    Route::post('/track-ops/add-vocals', [App\Http\Controllers\TrackEditController::class, 'addVocals']);
    Route::post('/track-ops/mashup', [App\Http\Controllers\TrackEditController::class, 'mashup']);
    Route::post('/track-ops/replace-section', [App\Http\Controllers\TrackEditController::class, 'replaceSection']);

    Route::post('/charts/add-song', [App\Http\Controllers\ChartController::class, 'addSong']);
    Route::post('/charts/vote', [App\Http\Controllers\ChartController::class, 'vote']);
    Route::post('/charts/unvote', [App\Http\Controllers\ChartController::class, 'unvote']);
    Route::post('/charts/remove-song', [App\Http\Controllers\ChartController::class, 'removeSong']);

    // Theme charts (Valentine)
    Route::post('/charts/theme/add-song', [App\Http\Controllers\ChartController::class, 'addSongToTheme']);
    Route::post('/charts/theme/remove-song', [App\Http\Controllers\ChartController::class, 'removeSongFromTheme']);

    // Charts all-time
    Route::post('/charts/all-time/vote', [App\Http\Controllers\ChartController::class, 'voteAllTime']);
    Route::post('/charts/all-time/unvote', [App\Http\Controllers\ChartController::class, 'unvoteAllTime']);

    Route::post('/favorites/toggle', [App\Http\Controllers\FavoriteController::class, 'toggle']);
    Route::get('/favorites', [App\Http\Controllers\FavoriteController::class, 'index']);

    // Payment
    Route::post('/payment/create', [App\Http\Controllers\PaymentController::class, 'create']);
    Route::get('/payment/status', [App\Http\Controllers\PaymentController::class, 'checkStatus']);

    Route::post('/promo/check', [App\Http\Controllers\PaymentController::class, 'checkPromo']);
    Route::post('/promo/apply', [App\Http\Controllers\PaymentController::class, 'applyPromo']);
    Route::post('/promo/pay', [App\Http\Controllers\PaymentController::class, 'createPromoPayment']);

    // Admin broadcast API
    Route::get('/admin/broadcast/segments', [App\Http\Controllers\BroadcastController::class, 'segments']);
    Route::post('/admin/broadcast/count-segment', [App\Http\Controllers\BroadcastController::class, 'countSegment']);
    Route::post('/admin/broadcast/create', [App\Http\Controllers\BroadcastController::class, 'create']);
    Route::post('/admin/broadcast/test', [App\Http\Controllers\BroadcastController::class, 'test']);
    Route::get('/admin/broadcast/{id}/status', [App\Http\Controllers\BroadcastController::class, 'status']);
    Route::post('/admin/broadcast/{id}/start', [App\Http\Controllers\BroadcastController::class, 'start']);
    Route::post('/admin/broadcast/{id}/pause', [App\Http\Controllers\BroadcastController::class, 'pause']);

    Route::post('/admin/promo/create', [App\Http\Controllers\PromoCodeController::class, 'create']);
    Route::post('/admin/promo/toggle', [App\Http\Controllers\PromoCodeController::class, 'toggle']);

    // Web notifications (для всех юзеров)
    Route::get('/notifications', [App\Http\Controllers\BroadcastController::class, 'getNotifications']);
    Route::post('/notifications/read', [App\Http\Controllers\BroadcastController::class, 'markRead']);

});

// Webhook от ЮKassa (без авторизации!)
Route::post('/payment/webhook', [App\Http\Controllers\PaymentController::class, 'webhook']);

Route::get('/public/top-tracks', [App\Http\Controllers\ChartController::class, 'publicTopTracks']);
