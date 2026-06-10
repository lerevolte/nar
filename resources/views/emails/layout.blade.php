<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'На Репите' }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f7;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#1a202c;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f7;padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,0.06);">
                    <tr>
                        <td style="background:#7c3aed;padding:24px 32px;text-align:center;">
                            <span style="font-size:24px;font-weight:800;color:#ffffff;letter-spacing:-0.02em;">🎵 На Репите</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            {!! $slot !!}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 32px;border-top:1px solid #edf2f7;text-align:center;color:#a0aec0;font-size:12px;">
                            narepite.com — генерация песен с помощью ИИ<br>
                            Нужна помощь? Напиши на <a href="mailto:help@narepite.site" style="color:#7c3aed;">help@narepite.site</a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
