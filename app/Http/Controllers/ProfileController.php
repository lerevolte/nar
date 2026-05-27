<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    /**
     * Страница профиля
     */
    public function index(Request $request)
    {
        return view('dashboard.profile');
    }

    /**
     * Обновление email и пароля
     */
    public function updateCredentials(Request $request)
    {
        $user = $request->get('auth_user');

        $rules = [
            'email' => 'nullable|email|max:255|unique:users,email,' . $user->user_id . ',user_id',
            'new_password' => 'nullable|string|min:6|confirmed',
        ];

        $request->validate($rules, [
            'email.unique' => 'Этот email уже используется другим пользователем.',
            'email.email' => 'Введите корректный email.',
            'new_password.min' => 'Пароль должен быть не менее 6 символов.',
            'new_password.confirmed' => 'Пароли не совпадают.',
        ]);

        $updated = false;

        // Обновляем email
        $newEmail = $request->input('email');
        if ($newEmail !== $user->email) {
            $user->email = $newEmail ?: null;
            $updated = true;
        }

        // Обновляем пароль
        $newPassword = $request->input('new_password');
        if (!empty($newPassword)) {
            $user->password = Hash::make($newPassword);
            $updated = true;
        }

        if ($updated) {
            $user->save();
            return redirect()->route('profile')->with('success', 'Данные успешно обновлены!');
        }

        return redirect()->route('profile')->with('success', 'Ничего не изменилось.');
    }
}