@component('emails.layout', ['title' => 'Песня готова'])
    <h1 style="font-size:22px;font-weight:800;margin:0 0 16px;">Твоя песня готова! 🎉</h1>

    <p style="font-size:15px;line-height:1.6;margin:0 0 20px;color:#2d3748;">
        Песня «<b>{{ $title }}</b>» сгенерирована. Слушай, скачивай и делись:
    </p>

    @if($filePath)
        <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 12px;">
            <tr>
                <td style="border-radius:10px;background:#7c3aed;">
                    <a href="{{ $filePath }}" style="display:inline-block;padding:13px 26px;font-size:15px;font-weight:700;color:#ffffff;text-decoration:none;">⬇ Скачать вариант 1</a>
                </td>
            </tr>
        </table>
    @endif

    @if($filePath2)
        <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 20px;">
            <tr>
                <td style="border-radius:10px;border:1px solid #7c3aed;">
                    <a href="{{ $filePath2 }}" style="display:inline-block;padding:13px 26px;font-size:15px;font-weight:700;color:#7c3aed;text-decoration:none;">⬇ Скачать вариант 2</a>
                </td>
            </tr>
        </table>
    @endif

    <p style="font-size:14px;line-height:1.6;margin:20px 0 0;color:#718096;">
        Все твои песни всегда доступны в личном кабинете:
        <a href="{{ $cabinetUrl }}" style="color:#7c3aed;">{{ $cabinetUrl }}</a>
    </p>
@endcomponent
