@extends('layouts.app')

@section('title', 'Чарт за всё время — На Репите')

@section('content')
<div class="chart-header">
    <div class="chart-tabs">
        <a href="{{ route('charts.index') }}" class="chart-tab">Недельный</a>
        <span class="chart-tab active">За всё время</span>
    </div>
    <h1 class="chart-title">🏆 Чарт за всё время</h1>
    <div class="chart-subtitle">Лучшие песни по общему числу голосов</div>
    <div class="chart-stats">
        <div class="chart-stat">
            <div class="chart-stat-value">{{ $entries->count() }}</div>
            <div class="chart-stat-label">треков</div>
        </div>
        <div class="chart-stat">
            <div class="chart-stat-value">{{ $entries->sum('votes_count') }}</div>
            <div class="chart-stat-label">голосов</div>
        </div>
    </div>
</div>

@if($entries->isEmpty())
    <div class="empty-chart">
        <div class="empty-chart-icon">🎵</div>
        <p>Пока нет песен</p>
        <p style="margin-top:8px; color: var(--text-tertiary);">Добавьте песню в недельный чарт!</p>
    </div>
@else
    <div class="chart-list">
        @foreach($entries as $entry)
            @php
                $rank = $entry->position;
                $rankClass = $rank <= 3 ? 'top-'.$rank : '';
                $isOwn = $entry->user_id === $authUser->user_id;
                $entryId = $songEntryMap[$entry->song_id] ?? null;
                $isVoted = $entryId && in_array($entryId, $votedEntryIds);
                $audioUrl = $entry->song->file_path ?? '';
            @endphp
            <div class="chart-entry {{ $rankClass }}" id="entry-{{ $entry->song_id }}">
                <div class="position">{{ $rank }}</div>
                
                @if($audioUrl)
                <button class="play-mini-btn" 
                    data-play-track 
                    data-url="{{ $audioUrl }}"
                    data-title="{{ $entry->song->title ?? 'Без названия' }}"
                    data-author="{{ $entry->user->first_name ?? 'Автор' }}"
                    data-cover="{{ $entry->song->cover_url ?? '🎶' }}"
                >
                    <svg class="icon-play" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                    <svg class="icon-pause" viewBox="0 0 24 24"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
                    <div class="spinner icon-loading"></div>
                </button>
                @endif

                <div class="entry-info">
                    <a href="javascript:;" class="entry-title">{{ $entry->song->title ?? 'Без названия' }}</a>
                    <div class="entry-author">
                        {{ $entry->user->first_name ?? $entry->user->username ?? 'Автор' }}
                        @if($isOwn) <span style="color: var(--accent);">(вы)</span> @endif
                    </div>
                </div>

                <div style="display: flex; align-items: center; gap: 4px;">
                    <button class="action-btn fav-mini-btn {{ in_array($entry->song_id, $favoriteSongIds) ? 'active' : '' }}" 
                        onclick="toggleFavorite(this, {{ $entry->song_id }}, 1)"
                        title="В избранное"
                    >❤️</button>

                    <button class="action-btn info-mini-btn" 
                        onclick="toggleEntryInfo({{ $entry->song_id }})"
                        title="Информация"
                        id="info-btn-{{ $entry->song_id }}"
                    >ℹ️</button>

                    @if($audioUrl)
                    <button class="action-btn download-mini-btn" 
                        onclick="downloadChartSong(this, {{ $entry->song_id }}, 1)"
                        title="Скачать"
                    >⬇</button>
                    @endif
                </div>

                <div class="vote-section">
                    <button class="vote-btn {{ $isVoted ? 'voted' : '' }} {{ $isOwn ? 'own-song' : '' }}" 
                        @if(!$isOwn)
                            onclick="toggleVote(this, {{ $entry->song_id }}, false)"
                        @else
                            disabled
                        @endif
                        data-song-id="{{ $entry->song_id }}"
                    ><svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"/></svg></button>
                    <span class="vote-count" id="votes-{{ $entry->song_id }}">{{ $entry->votes_count }}</span>
                </div>
            </div>
            
            <div class="entry-details" id="details-{{ $entry->song_id }}">
                <div class="entry-details-row">
                    <span class="entry-details-label">🎸 Жанр:</span>
                    <span class="entry-details-value">{{ $entry->song->genre ?: 'Не указан' }}</span>
                </div>
                <div class="entry-details-row">
                    <span class="entry-details-label">🎤 Стиль:</span>
                    <span class="entry-details-value">{{ $entry->song->occasion ?: 'Не указан' }}</span>
                </div>
                <div class="entry-details-row">
                    <span class="entry-details-label">📅 Создан:</span>
                    <span class="entry-details-value">{{ $entry->song->created_at ? $entry->song->created_at->format('d.m.Y') : '—' }}</span>
                </div>
                @if($entry->song->lyrics)
                    <div class="entry-lyrics">
                        <div class="entry-lyrics-title">📝 Текст песни:</div>
                        <div class="entry-lyrics-text">{{ $entry->song->lyrics }}</div>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
@endif

<div style="text-align:center; margin-top:30px;">
    <a href="{{ route('charts.archive') }}" style="color: var(--text-tertiary); font-size: 14px;">
        📚 Архив недельных чартов
    </a>
</div>
@endsection

@push('scripts')
<script>
    function toggleEntryInfo(songId) {
        const details = document.getElementById('details-' + songId);
        const btn = document.getElementById('info-btn-' + songId);
        document.querySelectorAll('.entry-details.show').forEach(el => {
            if (el.id !== 'details-' + songId) el.classList.remove('show');
        });
        document.querySelectorAll('.info-mini-btn.active').forEach(el => {
            if (el.id !== 'info-btn-' + songId) el.classList.remove('active');
        });
        details.classList.toggle('show');
        btn.classList.toggle('active');
    }

    async function toggleVote(btn, entryId, isOwn) {
        if (isOwn) { alert('Нельзя голосовать за себя!'); return; }
        try {
            const countEl = document.getElementById(`votes-${entryId}`);
            const isVoted = btn.classList.contains('voted');
            btn.classList.toggle('voted');
            let currentCount = parseInt(countEl.textContent);
            countEl.textContent = currentCount + (isVoted ? -1 : 1);
            const response = await fetch(`/api/charts/${isVoted ? 'unvote' : 'vote'}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ entry_id: entryId }),
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok || !data.success) throw new Error(data.error || 'Ошибка при голосовании');
        } catch (e) {
            console.error(e);
            // откат оптимистичного обновления
            btn.classList.toggle('voted');
            const cEl = document.getElementById(`votes-${entryId}`);
            const votedNow = btn.classList.contains('voted');
            if (cEl) cEl.textContent = parseInt(cEl.textContent) + (votedNow ? 1 : -1);
            alert(e.message || 'Ошибка при голосовании');
        }
    }

    async function downloadChartSong(btn, songId, variant) {
        const originalContent = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '⏳';
        try {
            const response = await fetch(`/songs/${songId}/download/${variant}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            });
            const data = await response.json();
            if (!response.ok || !data.success) { alert(data.error || 'Ошибка'); return; }
            const tg = window.Telegram?.WebApp;
            if (tg && tg.initData) { tg.openLink(data.download_url); }
            else { window.location.href = data.download_url; }
        } catch (error) { alert('Ошибка: ' + error.message); }
        finally { setTimeout(() => { btn.disabled = false; btn.innerHTML = originalContent; }, 1000); }
    }

    async function toggleFavorite(btn, songId, variant) {
        try {
            const response = await fetch('/api/favorites/toggle', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ song_id: songId, variant: variant }),
            });
            const data = await response.json();
            if (response.ok && data.success) { btn.classList.toggle('active'); }
            else { alert(data.error || 'Ошибка'); }
        } catch (error) { alert('Ошибка: ' + error.message); }
    }
</script>
@endpush