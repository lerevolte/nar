<?php

namespace App\Http\Controllers;

use App\Services\TelegramAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MiniAppController extends Controller
{
    /**
     * Точка входа в Mini App
     */
    public function entry(Request $request)
    {
        Log::info('MiniApp entry called', [
            'user_agent' => $request->header('User-Agent'),
            'referer' => $request->header('Referer'),
            'all_params' => $request->all(),
            'has_tgWebAppData' => $request->has('tgWebAppData'),
        ]);

        // Всегда показываем страницу инициализации для Mini App
        // Она сама получит initData из Telegram JS SDK и авторизуется
        $redirect = $request->input('redirect', '/dashboard');
        
        return view('miniapp.init', compact('redirect'));
    }

    /**
     * API эндпоинт для авторизации из Mini App
     */
    public function auth(Request $request, TelegramAuthService $authService)
    {
        $initData = $request->input('initData');

        Log::info('Mini App auth attempt', [
            'has_initData' => !empty($initData),
            'initData_length' => strlen($initData ?? ''),
            'initData_preview' => substr($initData ?? '', 0, 100),
        ]);

        if (!$initData) {
            return response()->json(['error' => 'initData required'], 400);
        }

        $userData = $authService->verifyMiniApp($initData);

        if (!$userData) {
            Log::warning('Mini App auth failed: invalid initData', [
                'initData' => substr($initData, 0, 200),
            ]);
            return response()->json(['error' => 'Invalid initData'], 401);
        }

        Log::info('Mini App auth success', ['user_id' => $userData['id']]);

        $user = $authService->getOrCreateUser($userData);
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
}