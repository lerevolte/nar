@component('emails.layout', ['title' => 'Ошибка генерации'])
    <h1 style="font-size:22px;font-weight:800;margin:0 0 16px;">Не получилось сгенерировать песню 😔</h1>

    <p style="font-size:15px;line-height:1.6;margin:0 0 16px;color:#2d3748;">
        К сожалению, при генерации песни «<b>{{ $title }}</b>» произошла ошибка.
        Мы <b>вернули 1 песню на твой баланс</b> — деньги не пропали.
    </p>

    <p style="font-size:15px;line-height:1.6;margin:0 0 20px;color:#2d3748;">
        Попробуй запустить генерацию ещё раз — обычно со второй попытки всё получается.
    </p>

    @if($retryUrl)
        <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 24px;">
            <tr>
                <td style="border-radius:10px;background:#7c3aed;">
                    <a href="{{ $retryUrl }}" style="display:inline-block;padding:14px 28px;font-size:15px;font-weight:700;color:#ffffff;text-decoration:none;">🔄 Повторить генерацию</a>
                </td>
            </tr>
        </table>
    @endif

    {{-- Доступ в личный кабинет --}}
    <div style="border-top:1px solid #edf2f7;padding-top:20px;">
        <h2 style="font-size:17px;font-weight:800;margin:0 0 10px;">Твой личный кабинет</h2>
        <p style="font-size:14px;line-height:1.6;margin:0 0 14px;color:#2d3748;">
            Аккаунт уже создан, и возвращённая песня лежит на балансе. Войди — и сможешь запустить генерацию заново в любой момент.
        </p>

        @if($login)
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f7fafc;border:1px solid #e2e8f0;border-radius:10px;margin:0 0 14px;">
                <tr>
                    <td style="padding:12px 16px;font-size:14px;color:#4a5568;">Логин</td>
                    <td style="padding:12px 16px;font-size:15px;font-weight:700;text-align:right;">{{ $login }}</td>
                </tr>
                @if($password)
                    <tr>
                        <td style="padding:12px 16px;border-top:1px solid #e2e8f0;font-size:14px;color:#4a5568;">Пароль</td>
                        <td style="padding:12px 16px;border-top:1px solid #e2e8f0;font-size:15px;font-weight:700;text-align:right;">{{ $password }}</td>
                    </tr>
                @endif
            </table>
        @endif

        <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 12px;">
            <tr>
                <td style="border-radius:10px;background:#7c3aed;">
                    <a href="{{ $loginUrl }}" style="display:inline-block;padding:13px 26px;font-size:15px;font-weight:700;color:#ffffff;text-decoration:none;">Войти в личный кабинет →</a>
                </td>
            </tr>
        </table>

        @unless($password)
            <p style="font-size:13px;line-height:1.6;margin:0;color:#718096;">
                Не помнишь пароль? <a href="{{ $resetUrl }}" style="color:#7c3aed;">Восстанови его здесь</a> — пришлём ссылку на email.
            </p>
        @endunless
    </div>

    <p style="font-size:13px;line-height:1.6;margin:18px 0 0;color:#a0aec0;">
        Если ошибка повторяется — напиши на <a href="mailto:support@narepite.com" style="color:#7c3aed;">support@narepite.com</a>, поможем.
    </p>
@endcomponent
