@component('emails.layout', ['title' => 'Доступ в личный кабинет'])
    <h1 style="font-size:22px;font-weight:800;margin:0 0 16px;">Спасибо за заказ! 🎉</h1>

    <p style="font-size:15px;line-height:1.6;margin:0 0 16px;color:#2d3748;">
        @if($title)
            Мы уже работаем над песней «<b>{{ $title }}</b>». Как только она будет готова,
        @else
            Мы уже работаем над твоей песней. Как только она будет готова,
        @endif
        пришлём ещё одно письмо со ссылками на скачивание.
    </p>

    @if($password)
        <p style="font-size:15px;line-height:1.6;margin:0 0 12px;color:#2d3748;">
            Мы создали для тебя личный кабинет — там хранятся все твои песни. Данные для входа:
        </p>
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f7fafc;border:1px solid #e2e8f0;border-radius:10px;margin:0 0 16px;">
            <tr>
                <td style="padding:14px 18px;font-size:14px;color:#4a5568;">Логин</td>
                <td style="padding:14px 18px;font-size:15px;font-weight:700;text-align:right;">{{ $login }}</td>
            </tr>
            <tr>
                <td style="padding:14px 18px;border-top:1px solid #e2e8f0;font-size:14px;color:#4a5568;">Пароль</td>
                <td style="padding:14px 18px;border-top:1px solid #e2e8f0;font-size:15px;font-weight:700;text-align:right;">{{ $password }}</td>
            </tr>
        </table>
        <p style="font-size:13px;line-height:1.5;margin:0 0 20px;color:#718096;">
            ⚠️ Сохрани пароль. Позже его можно сменить или восстановить по email на странице входа.
        </p>
    @else
        <p style="font-size:15px;line-height:1.6;margin:0 0 20px;color:#2d3748;">
            Песня сохранится в твоём личном кабинете — войди под своим логином <b>{{ $login }}</b>.
            Забыл пароль? Его можно восстановить на странице входа.
        </p>
    @endif

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto;">
        <tr>
            <td style="border-radius:10px;background:#7c3aed;">
                <a href="{{ $cabinetUrl }}" style="display:inline-block;padding:14px 28px;font-size:15px;font-weight:700;color:#ffffff;text-decoration:none;">Перейти в личный кабинет →</a>
            </td>
        </tr>
    </table>
@endcomponent
