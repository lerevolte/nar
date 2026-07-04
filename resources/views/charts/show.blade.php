@extends('layouts.app')

@section('title', $chart->name . ' — На Репите')

@push('styles')
<style>
    .chart-status { display: inline-block; padding: 4px 12px; border-radius: var(--radius-full); font-size: 12px; font-weight: 600; margin-top: 8px; }
    .chart-status.active { background: var(--success-soft); color: var(--success); }
    .chart-status.closed { background: var(--danger-soft); color: var(--danger); }
    .rewards-section { background: var(--gold-soft); border: 1px solid var(--gold-border); border-radius: var(--radius-lg); padding: 16px; margin-bottom: 24px; }
    .rewards-section h4 { font-size: 14px; margin-bottom: 12px; }
    .reward-row { display: flex; align-items: center; gap: 8px; padding: 8px 0; border-bottom: 1px solid var(--border); }
    .reward-row:last-child { border-bottom: none; }
    .reward-place { font-size: 20px; }
    .reward-info { flex: 1; }
    .reward-song { font-weight: 600; font-size: 14px; }
    .reward-author { font-size: 12px; color: var(--text-secondary); }
    .reward-prize { font-size: 14px; color: var(--gold); font-weight: 700; }
</style>
@endpush

@section('content')
<div class="back-link">
    <a href="{{ route('charts.archive') }}">← К архиву чартов</a>
</div>

<div class="chart-header">
    <h1 class="chart-title">🏆 {{ $chart->name }}</h1>
    <div class="chart-period">{{ $chart->starts_at->format('d.m') }} — {{ $chart->ends_at->format('d.m.Y') }}</div>
    <span class="chart-status {{ $chart->is_active ? 'active' : 'closed' }}">
        {{ $chart->is_active ? '🟢 Активный' : '🔴 Завершён' }}
    </span>
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

@if(!$chart->is_active && $chartRewards->isNotEmpty())
    <div class="rewards-section">
        <h4>🎁 Награды победителям:</h4>
        @foreach($chartRewards->sortBy('position') as $reward)
            <div class="reward-row">
                <span class="reward-place">
                    @if($reward->position == 1) 🥇 @elseif($reward->position == 2) 🥈 @elseif($reward->position == 3) 🥉 @elseif($reward->position == 4) 4️⃣ @elseif($reward->position == 5) 5️⃣ @endif
                </span>
                <div class="reward-info">
                    <div class="reward-song">{{ $reward->entry->song->title ?? 'Без названия' }}</div>
                    <div class="reward-author">{{ $reward->user->first_name ?? $reward->user->username ?? 'Автор' }}</div>
                </div>
                <span class="reward-prize">+{{ $reward->songs_reward }} песен</span>
            </div>
        @endforeach
    </div>
@endif

@if($entries->isEmpty())
    <div class="empty-chart">
        <div class="empty-chart-icon">🎵</div>
        <p>В этом чарте не было участников</p>
    </div>
@else
    <div class="chart-list">
        @foreach($entries as $entry)
            @php
                $rank = $entry->position;
                $rankClass = $rank <= 3 ? 'top-'.$rank : '';
                $isVoted = in_array($entry->id, $votedEntryIds);
                $isOwn = $entry->user_id === $authUser->user_id;
                $audioUrl = ($entry->variant == 2) ? $entry->song->file_path_2 : $entry->song->file_path;
            @endphp
            @if($audioUrl)
            <div class="chart-entry {{ $rankClass }}" id="entry-{{ $entry->id }}">
                <div class="position">{{ $rank }}</div>
                
                @if($audioUrl)
                <button class="play-mini-btn" data-play-track data-url="{{ $audioUrl }}" data-title="{{ $entry->song->title ?? 'Без названия' }}" data-author="{{ $entry->user->first_name ?? 'Автор' }}" data-cover="{{ $entry->song->cover_url ?? '🎶' }}">
                    <svg class="icon-play" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                    <svg class="icon-pause" viewBox="0 0 24 24"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
                    <div class="spinner icon-loading"></div>
                </button>
                @endif

                <div class="entry-info">
                    <span class="entry-title">{{ $entry->song->title ?? 'Без названия' }}</span>
                    <div class="entry-author">
                        {{ $entry->user->first_name ?? $entry->user->username ?? 'Автор' }}
                        @if($isOwn) <span style="color: var(--accent);">(вы)</span> @endif
                    </div>
                </div>

                <div style="display: flex; align-items: center; gap: 4px;">
                    <button class="action-btn fav-mini-btn {{ in_array($entry->song_id, $favoriteSongIds) ? 'active' : '' }}" onclick="toggleFavorite(this, {{ $entry->song_id }}, {{ $entry->variant ?? 1 }})" title="В избранное">❤️</button>
                    <button class="action-btn info-mini-btn" onclick="toggleEntryInfo({{ $entry->id }})" title="Информация" id="info-btn-{{ $entry->id }}">ℹ️</button>
                    @if($audioUrl)
                    <button class="action-btn download-mini-btn" onclick="downloadChartSong(this, {{ $entry->song_id }}, {{ $entry->variant ?? 1 }})" title="Скачать">⬇</button>
                    @endif
                </div>

                <div class="vote-section">
                    @if($chart->is_active)
                        <button class="vote-btn {{ $isVoted ? 'voted' : '' }}" @if(!$isOwn) onclick="toggleVote(this, {{ $entry->id }}, false)" @else style="cursor:default;opacity:1;" disabled @endif><svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"/></svg></button>
                    @else
                        <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"/></svg>
                    @endif
                    <span class="vote-count" id="votes-{{ $entry->id }}">{{ $entry->votes_count }}</span>
                </div>
            </div>
            
            <div class="entry-details" id="details-{{ $entry->id }}">
                @if($entry->comment)
                    <div class="entry-comment">
                        <div class="entry-comment-label">💬 Комментарий:</div>
                        <div class="entry-comment-text">{{ $entry->comment }}</div>
                    </div>
                @endif
                <div class="entry-details-row"><span class="entry-details-label">🎸 Жанр:</span><span class="entry-details-value">{{ $entry->song->genre ?: 'Не указан' }}</span></div>
                <div class="entry-details-row"><span class="entry-details-label">📅 Создан:</span><span class="entry-details-value">{{ $entry->song->created_at ? $entry->song->created_at->format('d.m.Y') : '—' }}</span></div>
                @if($entry->song->lyrics)
                    <div class="entry-lyrics"><div class="entry-lyrics-title">📝 Текст:</div><div class="entry-lyrics-text">{{ $entry->song->lyrics }}</div></div>
                @endif
            </div>
            @endif
        @endforeach
    </div>
@endif

<div style="text-align:center; margin-top:30px; display:flex; flex-direction:column; gap:12px; align-items:center;">
    <a href="{{ route('charts.index') }}" style="color:var(--accent); font-size:14px; font-weight:600;">🏆 К текущему чарту</a>
    <a href="{{ route('charts.archive') }}" style="color:var(--text-tertiary); font-size:14px;">📚 Архив чартов</a>
</div>
@endsection

@push('scripts')
<script>
    function toggleEntryInfo(entryId) {
        const details = document.getElementById('details-' + entryId);
        const btn = document.getElementById('info-btn-' + entryId);
        document.querySelectorAll('.entry-details.show').forEach(el => { if (el.id !== 'details-' + entryId) el.classList.remove('show'); });
        document.querySelectorAll('.info-mini-btn.active').forEach(el => { if (el.id !== 'info-btn-' + entryId) el.classList.remove('active'); });
        details.classList.toggle('show');
        btn.classList.toggle('active');
    }
    async function downloadChartSong(btn, songId, variant) {
        const orig = btn.innerHTML; btn.disabled = true; btn.innerHTML = '⏳';
        try {
            const r = await fetch(`/songs/${songId}/download/${variant}`, { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'} });
            const d = await r.json();
            if (!r.ok || !d.success) { alert(d.error||'Ошибка'); return; }
            const tg = window.Telegram?.WebApp;
            tg && tg.initData ? tg.openLink(d.download_url) : (window.location.href = d.download_url);
        } catch(e) { alert('Ошибка: '+e.message); }
        finally { setTimeout(()=>{btn.disabled=false;btn.innerHTML=orig;},1000); }
    }
    async function toggleFavorite(btn, songId, variant) {
        try {
            const r = await fetch('/api/favorites/toggle', { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'}, body:JSON.stringify({song_id:songId,variant}) });
            if ((await r.json()).success) btn.classList.toggle('active');
        } catch(e) { alert('Ошибка: '+e.message); }
    }
    @if($chart->is_active)
    async function toggleVote(btn, entryId, isOwn) {
        if (isOwn) return;
        try {
            const countEl = document.getElementById(`votes-${entryId}`);
            const isVoted = btn.classList.contains('voted');
            btn.classList.toggle('voted');
            countEl.textContent = parseInt(countEl.textContent) + (isVoted ? -1 : 1);
            const r = await fetch(`/api/charts/${isVoted?'unvote':'vote'}`, { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'}, body:JSON.stringify({entry_id:entryId}) });
            const d = await r.json().catch(() => ({}));
            if (!r.ok || !d.success) { throw new Error(d.error || 'Ошибка'); }
        } catch(e) {
            // откатываем оптимистичное обновление
            btn.classList.toggle('voted');
            const countEl = document.getElementById(`votes-${entryId}`);
            const isVotedNow = btn.classList.contains('voted');
            countEl.textContent = parseInt(countEl.textContent) + (isVotedNow ? 1 : -1);
            alert(e.message || 'Ошибка');
        }
    }
    @endif
</script>
@endpush