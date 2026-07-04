@extends('layouts.app')

@section('title', 'Чарты — На Репите')

@section('content')
<div class="chart-header">
    <div class="chart-tabs">
        <span class="chart-tab active">Недельный</span>
        <a href="{{ route('charts.allTime') }}" class="chart-tab">За всё время</a>
    </div>
    <h1 class="chart-title">🏆 {{ $chart->name }}</h1>
    <div class="chart-period">
        {{ $chart->starts_at->format('d.m') }} — {{ $chart->ends_at->format('d.m.Y') }}
    </div>
</div>

<div class="prizes-info">
    @foreach($rewards as $pos => $songs)
        <div class="prize-item">
            <div class="prize-icon">
                @if($pos == 1) 🥇 @elseif($pos == 2) 🥈 @elseif($pos == 3) 🥉 @elseif($pos == 4) 4️⃣ @elseif($pos == 5) 5️⃣ @endif
            </div>
            <div class="prize-text">+{{ $songs }} песен</div>
        </div>
    @endforeach
</div>

@if($entries->isEmpty())
    <div class="empty-chart">
        <div class="empty-chart-icon">🎵</div>
        <p>Пока нет песен в чарте</p>
        <p style="font-size:13px; margin-top:8px; color: var(--text-tertiary);">Добавьте свою песню первым!</p>
    </div>
@else
    <div class="sort-bar">
        <button class="sort-btn active" data-sort="mix" onclick="sortEntries('mix')">🎲 Микс</button>
        <button class="sort-btn" data-sort="top" onclick="sortEntries('top')">🔥 Топ</button>
        <button class="sort-btn" data-sort="new" onclick="sortEntries('new')">🕐 Новые</button>
    </div>

    <div class="chart-list" id="chart-list">
        @foreach($entries as $entry)
            @php
                $rank = $entry->rank;
                $rankClass = $rank <= 3 ? 'top-'.$rank : '';
                $isVoted = in_array($entry->id, $votedEntryIds);
                $isOwn = $entry->user_id === $authUser->user_id;
                $audioUrl = ($entry->variant == 2) ? $entry->song->file_path_2 : $entry->song->file_path;
            @endphp
            @if($audioUrl)
            <div class="chart-entry {{ $rankClass }}" 
                id="entry-{{ $entry->id }}"
                data-rank="{{ $rank }}"
                data-votes="{{ $entry->votes_count }}"
                data-created="{{ $entry->created_at }}"
                data-entry-id="{{ $entry->id }}"
            >
                <div class="position">
                    @if($rank <= 3 && $entry->votes_count > 0)
                        @if($rank == 1) 🥇 @elseif($rank == 2) 🥈 @elseif($rank == 3) 🥉 @endif
                    @elseif($entry->votes_count > 0)
                        #{{ $rank }}
                    @else
                        🆕
                    @endif
                </div>
                
                @if($audioUrl)
                <button class="play-mini-btn" 
                    data-play-track 
                    data-url="{{ $audioUrl }}"
                    data-title="{{ $entry->song->title }}"
                    data-author="{{ $entry->user->first_name ?? 'Автор' }}"
                    data-cover="{{ $entry->song->cover_url ?? '🎶' }}"
                >
                    <svg class="icon-play" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                    <svg class="icon-pause" viewBox="0 0 24 24"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
                    <div class="spinner icon-loading"></div>
                </button>
                @endif

                <div class="entry-info">
                    @if($isOwn)
                        <a href="{{ route('songs.show', $entry->song_id) }}" class="entry-title">{{ $entry->song->title }}</a>
                    @else
                        <span class="entry-title" style="cursor:default;">{{ $entry->song->title }}</span>
                    @endif
                    <div class="entry-author" data-id="{{ $entry->user->user_id }}">
                        {{ $entry->user->first_name ?? $entry->user->username ?? 'Автор' }}
                        @if($isOwn) <span style="color: var(--accent);">(вы)</span> @endif
                    </div>
                </div>

                <div style="display: flex; align-items: center; gap: 4px;">
                    <button class="action-btn fav-mini-btn {{ in_array($entry->song_id, $favoriteSongIds) ? 'active' : '' }}" 
                        onclick="toggleFavorite(this, {{ $entry->song_id }}, {{ $entry->variant ?? 1 }})"
                        title="В избранное"
                    >❤️</button>

                    <button class="action-btn info-mini-btn" 
                        onclick="toggleEntryInfo({{ $entry->id }})"
                        title="Информация"
                        id="info-btn-{{ $entry->id }}"
                    >ℹ️</button>

                    <button class="action-btn download-mini-btn" 
                        onclick="downloadChartSong(this, {{ $entry->song_id }}, {{ $entry->variant ?? 1 }})"
                        title="Скачать"
                    >⬇</button>

                    @if($isOwn)
                        <button class="action-btn delete-mini-btn" 
                            onclick="removeFromChart({{ $entry->song_id }})" 
                            title="Убрать из чарта"
                        >🗑</button>
                    @endif
                </div>

                <div class="vote-section">
                    <button class="vote-btn {{ $isVoted ? 'voted' : '' }}" 
                        @if(!$isOwn)
                            onclick="toggleVote(this, {{ $entry->id }}, false)"
                        @else
                            style="cursor: default; opacity: 1;" disabled
                        @endif
                    ><svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"/></svg></button>
                    <span class="vote-count" id="votes-{{ $entry->id }}">{{ $entry->votes_count }}</span>
                </div>
            </div>
            
            <div class="entry-details" id="details-{{ $entry->id }}" data-details-for="{{ $entry->id }}">
                @if($entry->comment)
                    <div class="entry-comment">
                        <div class="entry-comment-label">💬 Комментарий автора:</div>
                        <div class="entry-comment-text">{{ $entry->comment }}</div>
                    </div>
                @endif
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
                    <span class="entry-details-value">{{ $entry->song->created_at->format('d.m.Y') }}</span>
                </div>
                @if($entry->song->lyrics)
                    <div class="entry-lyrics">
                        <div class="entry-lyrics-title">📝 Текст песни:</div>
                        <div class="entry-lyrics-text">{{ $entry->song->lyrics }}</div>
                    </div>
                @endif
            </div>
            @endif
        @endforeach
    </div>
@endif

<div style="text-align:center; margin-top:30px; display: flex; flex-direction: column; gap: 12px; align-items: center;">
    <a href="{{ route('charts.allTime') }}" class="btn btn-secondary" style="gap: 8px;">
        🏆 Чарт за всё время
    </a>
    <a href="{{ route('charts.archive') }}" style="color: var(--text-tertiary); font-size: 14px;">
        📚 Архив недельных чартов
    </a>
</div>
@endsection

@push('scripts')
<script>
    function toggleEntryInfo(entryId) {
        const details = document.getElementById('details-' + entryId);
        const btn = document.getElementById('info-btn-' + entryId);
        document.querySelectorAll('.entry-details.show').forEach(el => {
            if (el.id !== 'details-' + entryId) el.classList.remove('show');
        });
        document.querySelectorAll('.info-mini-btn.active').forEach(el => {
            if (el.id !== 'info-btn-' + entryId) el.classList.remove('active');
        });
        details.classList.toggle('show');
        btn.classList.toggle('active');
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

    async function toggleVote(btn, entryId, isOwn) {
        if (isOwn) { alert('Нельзя голосовать за себя!'); return; }
        try {
            const countEl = document.getElementById(`votes-${entryId}`);
            const isVoted = btn.classList.contains('voted');
            btn.classList.toggle('voted');
            let currentCount = parseInt(countEl.textContent);
            
            const response = await fetch(`/api/charts/${isVoted ? 'unvote' : 'vote'}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ entry_id: entryId }),
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok || !data.success) { throw new Error(data.error || 'Ошибка'); }
            countEl.textContent = currentCount + (isVoted ? -1 : 1);
        } catch (e) { alert(e.message || 'Ошибка'); btn.classList.toggle('voted'); }
    }

    async function removeFromChart(songId) {
        if (!confirm('Вы уверены? Песня будет удалена из чарта, а голоса сброшены.')) return;
        try {
            const response = await fetch('/api/charts/remove-song', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ song_id: songId }),
            });
            if ((await response.json()).success || response.ok) { window.location.reload(); }
            else { alert('Ошибка'); }
        } catch (error) { alert('Ошибка: ' + error.message); }
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

    function sortEntries(mode) {
        document.querySelectorAll('.sort-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.sort === mode);
        });
        const list = document.getElementById('chart-list');
        const entries = [];
        list.querySelectorAll('.chart-entry').forEach(entry => {
            const entryId = entry.dataset.entryId;
            const details = list.querySelector(`[data-details-for="${entryId}"]`);
            entries.push({
                entryEl: entry, detailsEl: details,
                rank: parseInt(entry.dataset.rank),
                votes: parseInt(entry.dataset.votes),
                created: entry.dataset.created,
            });
        });

        if (mode === 'top') {
            entries.sort((a, b) => b.votes !== a.votes ? b.votes - a.votes : a.rank - b.rank);
        } else if (mode === 'new') {
            entries.sort((a, b) => new Date(b.created) - new Date(a.created));
        } else {
            for (let i = entries.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [entries[i], entries[j]] = [entries[j], entries[i]];
            }
        }

        entries.forEach(item => {
            list.appendChild(item.entryEl);
            if (item.detailsEl) list.appendChild(item.detailsEl);
        });

        entries.forEach((item, index) => {
            const posEl = item.entryEl.querySelector('.position');
            item.entryEl.classList.remove('top-1', 'top-2', 'top-3');
            if (mode === 'top') {
                const pos = index + 1;
                if (item.votes > 0) {
                    if (pos === 1) posEl.textContent = '🥇';
                    else if (pos === 2) posEl.textContent = '🥈';
                    else if (pos === 3) posEl.textContent = '🥉';
                    else posEl.textContent = '#' + pos;
                    if (pos <= 3) item.entryEl.classList.add('top-' + pos);
                } else { posEl.textContent = '🆕'; }
            } else if (mode === 'new') {
                posEl.textContent = '🕐';
            } else {
                const rank = item.rank;
                if (item.votes > 0) {
                    if (rank === 1) posEl.textContent = '🥇';
                    else if (rank === 2) posEl.textContent = '🥈';
                    else if (rank === 3) posEl.textContent = '🥉';
                    else posEl.textContent = '#' + rank;
                    if (rank <= 3) item.entryEl.classList.add('top-' + rank);
                } else { posEl.textContent = '🆕'; }
            }
        });
    }
</script>
@endpush