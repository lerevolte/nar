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
        <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 16px;">
            <tr>
                <td style="border-radius:10px;background:#7c3aed;">
                    <a href="{{ $retryUrl }}" style="display:inline-block;padding:14px 28px;font-size:15px;font-weight:700;color:#ffffff;text-decoration:none;">🔄 Повторить генерацию</a>
                </td>
            </tr>
        </table>
    @endif

    <p style="font-size:14px;line-height:1.6;margin:12px 0 0;color:#718096;">
        Песня на балансе ждёт тебя в личном кабинете:
        <a href="{{ $cabinetUrl }}" style="color:#7c3aed;">{{ $cabinetUrl }}</a><br>
        Если ошибка повторяется — напиши на <a href="mailto:help@narepite.site" style="color:#7c3aed;">help@narepite.site</a>, поможем.
    </p>
@endcomponent
