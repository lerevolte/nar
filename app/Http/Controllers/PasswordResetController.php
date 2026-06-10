<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;

class PasswordResetController extends Controller
{
    /**
     * Форма «Забыли пароль» — ввод email
     */
    public function showRequest()
    {
        return view('auth.forgot-password');
    }

    /**
     * Отправить ссылку для сброса пароля на email
     */
    public function sendLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ], [
            'email.required' => 'Введите email',
            'email.email' => 'Некорректный email',
        ]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_THROTTLED) {
            return back()->with('error', 'Слишком частые запросы. Попробуйте позже.');
        }

        Log::info('Password reset link requested', [
            'email' => $request->input('email'),
            'status' => $status,
        ]);

        // Нейтральное сообщение независимо от того, существует ли аккаунт с таким email
        return back()->with('success', 'Если аккаунт с таким email существует, мы отправили на него ссылку для сброса пароля.');
    }

    /**
     * Форма ввода нового пароля по ссылке из письма
     */
    public function showReset(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    /**
     * Установить новый пароль
     */
    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'password.required' => 'Введите новый пароль',
            'password.min' => 'Пароль минимум 6 символов',
            'password.confirmed' => 'Пароли не совпадают',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect('/login')->with('success', 'Пароль изменён. Теперь можешь войти.');
        }

        $message = match ($status) {
            Password::INVALID_TOKEN => 'Ссылка для сброса недействительна или устарела. Запросите новую.',
            Password::INVALID_USER => 'Аккаунт с таким email не найден.',
            default => 'Не удалось сбросить пароль. Попробуйте ещё раз.',
        };

        return back()->with('error', $message);
    }
}
