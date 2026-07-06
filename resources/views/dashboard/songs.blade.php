@extends('layouts.app')

@section('title', 'Все треки — На Репите')

@section('content')
    @php
        $trackOpsAllowedIds = config('services.track_ops.allowed_user_ids', []);
        $trackOpsAllowed = empty($trackOpsAllowedIds)
            || in_array('*', $trackOpsAllowedIds, true)
            || in_array((string) ($authUser->user_id ?? ''), $trackOpsAllowedIds, true);
    @endphp
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:20px;flex-wrap:wrap;">
        <h2 style="font-size: 20px; font-weight: 700; margin:0;">🎵 Все мои треки</h2>
        @if($trackOpsAllowed)
            <a href="{{ route('studio') }}" class="btn btn-secondary btn-sm">Студия: обработать треки</a>
        @endif
    </div>
    
    @if($songs->count() > 0)
        <div class="songs-grid">
            @foreach($songs as $song)
                <div class="song-card-wrapper">
                    <a href="{{ route('songs.show', $song->id) }}" class="song-card">
                        <div class="song-cover">
                            @if($song->cover_url)
                                <img src="{{ $song->cover_url }}" alt="Обложка" style="width:100%;height:100%;object-fit:cover;">
                            @else
                                🎶
                            @endif
                            @if(isset($chartSongIds) && in_array($song->id, $chartSongIds))
                                <span class="chart-badge">В чарте</span>
                            @endif
                        </div>
                        <div class="song-info">
                            <div class="song-title">{{ $song->title ?: 'Без названия' }}</div>
                            <div class="song-date">{{ $song->created_at->timezone('Asia/Irkutsk')->format('d.m.Y') }}</div>
                        </div>
                    </a>
                    <button class="song-delete-btn" onclick="deleteSong(event, {{ $song->id }})" title="Удалить трек">🗑</button>
                </div>
            @endforeach
        </div>

        <div class="pagination">
            {{ $songs->links('pagination::bootstrap-4') }}
        </div>
    @else
        <div class="empty-state">
            <div class="empty-state-icon">🎤</div>
            <h3>Пока нет треков</h3>
            <p style="color: var(--text-secondary); margin: 10px 0 15px;">Создай свой первый хит!</p>
            <a href="{{ route('generate.create') }}" class="btn btn-primary">Создать песню</a>
        </div>
    @endif
@endsection

@push('scripts')
<script>
async function deleteSong(event, songId) {
    event.preventDefault();
    event.stopPropagation();
    if (!confirm('Удалить трек?')) return;
    try {
        const response = await fetch(`/api/songs/${songId}/delete`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        });
        const data = await response.json();
        if (response.ok && data.success) {
            event.target.closest('.song-card-wrapper').remove();
        } else { alert(data.error || 'Ошибка при удалении'); }
    } catch (error) { alert('Ошибка: ' + error.message); }
}
</script>
@endpush