@component('emails.layout', ['title' => 'Восстановление пароля'])
    <h1 style="font-size:22px;font-weight:800;margin:0 0 16px;">Восстановление пароля</h1>

    <p style="font-size:15px;line-height:1.6;margin:0 0 20px;color:#2d3748;">
        Ты запросил сброс пароля для аккаунта на narepite.com.
        Нажми кнопку ниже, чтобы задать новый пароль:
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 20px;">
        <tr>
            <td style="border-radius:10px;background:#7c3aed;">
                <a href="{{ $resetUrl }}" style="display:inline-block;padding:14px 28px;font-size:15px;font-weight:700;color:#ffffff;text-decoration:none;">Сбросить пароль</a>
            </td>
        </tr>
    </table>

    <p style="font-size:13px;line-height:1.6;margin:0 0 12px;color:#718096;">
        Ссылка действительна {{ $expireMinutes }} минут. Если кнопка не работает, скопируй ссылку в браузер:<br>
        <a href="{{ $resetUrl }}" style="color:#7c3aed;word-break:break-all;">{{ $resetUrl }}</a>
    </p>

    <p style="font-size:13px;line-height:1.6;margin:0;color:#a0aec0;">
        Если ты не запрашивал сброс пароля — просто проигнорируй это письмо.
    </p>
@endcomponent
