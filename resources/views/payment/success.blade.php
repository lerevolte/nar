@extends('layouts.app')

@section('title', 'Оплата — На Репите')

@section('content')
<div class="card" style="text-align: center; padding: 40px 24px;">
    @if($processed && $songsAdded > 0)
        <div style="font-size: 64px; margin-bottom: 16px;">🎉</div>
        <h2 style="margin-bottom: 12px;">Спасибо за покупку!</h2>
        <p style="color: var(--text-secondary); margin-bottom: 24px;">+{{ $songsAdded }} песен зачислено на ваш баланс</p>
    @else
        <div style="font-size: 64px; margin-bottom: 16px;">⏳</div>
        <h2 style="margin-bottom: 12px;">Обрабатываем платёж...</h2>
        <p style="color: var(--text-secondary); margin-bottom: 24px;">Если песни не появились — обновите страницу через минуту</p>
    @endif
    <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
        <a href="{{ route('dashboard') }}" class="btn btn-secondary">На главную</a>
        <a href="{{ route('generate.create') }}" class="btn btn-primary">Создать песню</a>
    </div>
</div>
@endsection
@push('scripts')
@if($processed && $songsAdded > 0)
<script>
    try {
        @if($isMaxApp ?? false)
            //ym(105879987,'reachGoal','oplata-max');
        @elseif($isMiniApp ?? false)
            //ym(105879987,'reachGoal','payment');
        @else
            //ym(105879987,'reachGoal','oplata-site');
        @endif
    } catch(e) {}
</script>
@endif
@endpush