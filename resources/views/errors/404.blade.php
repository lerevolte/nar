@extends('layouts.public')

@section('title', 'Страница не найдена (404) | На Репите')
@section('meta')
    <meta name="robots" content="noindex">
@endsection

@section('content')
<div class="max-w-7xl mx-auto px-4 md:px-8" style="text-align:center;padding:70px 16px 90px;">
    <div style="font-size:100px;font-weight:800;line-height:1;color:#7c3aed;">404</div>
    <h1 style="font-size:30px;font-weight:800;margin-top:18px;color:#1f2937;">Страница не найдена</h1>
    <p style="color:#6b7280;margin:14px auto 0;max-width:520px;font-size:16px;line-height:1.6;">
        Возможно, страница была удалена или перемещена, либо в адресе допущена опечатка.
        Воспользуйтесь меню сайта или перейдите на главную.
    </p>
    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-top:28px;">
        <a href="/" class="btn-blue">На главную</a>
        <a href="/create-song" class="btn-blue" style="background-color:#7c3aed;">Создать песню</a>
        <a href="/articles" class="btn-blue" style="background-color:#374151;">Статьи</a>
    </div>
    <div style="margin-top:36px;font-size:14px;color:#9ca3af;">
        Не нашли что искали? Напишите нам: <a href="mailto:support@narepite.com" style="color:#7c3aed;">support@narepite.com</a>
    </div>
</div>
@endsection
