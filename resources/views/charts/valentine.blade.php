@extends('layouts.app')

@section('title', 'Песни о любви 💕 — На Репите')

@push('styles')
<style>
    .valentine-bg {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, #fff5f5 0%, #fed7d7 50%, #fecaca 100%);
        z-index: -1;
    }

    .floating-hearts {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        pointer-events: none;
        overflow: hidden;
        z-index: -1;
    }

    .heart {
        position: absolute;
        font-size: 20px;
        animation: floatHeart 6s ease-in-out infinite;
        opacity: 0.6;
    }

    @keyframes floatHeart {
        0%, 100% { transform: translateY(100vh) rotate(0deg); opacity: 0; }
        10% { opacity: 0.6; }
        90% { opacity: 0.6; }
        100% { transform: translateY(-100px) rotate(360deg); opacity: 0; }
    }

    .chart-header {
        text-align: center;
        margin-bottom: 24px;
        background: linear-gradient(135deg, #fff 0%, #fff5f5 100%);
        padding: 24px 20px;
        border-radius: 20px;
        box-shadow: 0 4px 20px rgba(239, 68, 68, 0.15);
        border: 2px solid #fecaca;
    }

    .chart-emoji {
        font-size: 48px;
        margin-bottom: 12px;
        animation: pulse 2s ease-in-out infinite;
    }

    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.1); }
    }

    .chart-title {
        font-size: 24px;
        font-weight: 700;
        color: #be185d;
        margin-bottom: 8px;
    }

    .chart-description {
        color: #9f1239;
        font-size: 14px;
        margin-bottom: 12px;
        line-height: 1.5;
    }

    .chart-timer {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: linear-gradient(135deg, #be185d 0%, #e11d48 100%);
        color: white;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 600;
    }

    .chart-stats {
        display: flex;
        justify-content: center;
        gap: 24px;
        margin-top: 16px;
    }

    .chart-stat { text-align: center; }
    .chart-stat-value { font-size: 24px; font-weight: 700; color: #be185d; }
    .chart-stat-label { font-size: 12px; color: #9f1239; }

    .prizes-info {
        background: linear-gradient(135deg, #fff1f2 0%, #ffe4e6 100%);
        border: 2px solid #fda4af;
        border-radius: 16px;
        padding: 16px;
        margin-bottom: 20px;
        display: flex;
        justify-content: space-around;
    }

    .prize-item { text-align: center; }
    .prize-icon { font-size: 24px; }
    .prize-text { font-size: 12px; color: #be185d; font-weight: 600; margin-top: 4px; }

    .add-song-section {
        background: linear-gradient(135deg, #fff 0%, #fff1f2 100%);
        border: 2px dashed #fda4af;
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 20px;
        text-align: center;
    }

    .add-song-section h3 {
        color: #be185d;
        font-size: 16px;
        margin-bottom: 8px;
    }

    .add-song-section p {
        color: #9f1239;
        font-size: 13px;
        margin-bottom: 16px;
    }

    .song-select {
        width: 100%;
        padding: 12px;
        border: 2px solid #fda4af;
        border-radius: 10px;
        font-size: 14px;
        margin-bottom: 12px;
        background: white;
    }

    .song-select:focus {
        outline: none;
        border-color: #be185d;
    }

    .comment-input {
        width: 100%;
        padding: 12px;
        border: 2px solid #fda4af;
        border-radius: 10px;
        font-size: 14px;
        margin-bottom: 12px;
        resize: vertical;
        min-height: 60px;
        font-family: inherit;
    }

    .comment-input:focus {
        outline: none;
        border-color: #be185d;
    }

    .comment-input::placeholder {
        color: #f9a8d4;
    }

    .add-song-btn {
        background: linear-gradient(135deg, #ec4899 0%, #be185d 100%);
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 25px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        box-shadow: 0 4px 15px rgba(236, 72, 153, 0.4);
    }

    .add-song-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(236, 72, 153, 0.5);
    }

    .add-song-btn:disabled {
        background: #ccc;
        cursor: not-allowed;
        box-shadow: none;
    }

    .user-entry-badge {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        padding: 12px 16px;
        border-radius: 12px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .user-entry-badge span {
        font-size: 14px;
    }

    .remove-entry-btn {
        background: rgba(255,255,255,0.2);
        border: none;
        color: white;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 12px;
        cursor: pointer;
    }

    .chart-list { display: flex; flex-direction: column; gap: 12px; }

    .chart-entry {
        display: flex;
        align-items: center;
        gap: 12px;
        background: white;
        border-radius: 12px;
        padding: 12px;
        box-shadow: 0 2px 8px rgba(239, 68, 68, 0.1);
        border: 1px solid #fecaca;
    }

    .chart-entry.top-1 {
        background: linear-gradient(135deg, #fff1f2 0%, #ffe4e6 100%);
        border: 2px solid #f43f5e;
        box-shadow: 0 4px 15px rgba(244, 63, 94, 0.2);
    }

    .chart-entry.top-2 {
        background: linear-gradient(135deg, #fff5f5 0%, #fed7d7 100%);
        border: 2px solid #fb7185;
    }

    .chart-entry.top-3 {
        background: linear-gradient(135deg, #fff 0%, #fff1f2 100%);
        border: 2px solid #fda4af;
    }

    .position {
        font-weight: 700;
        width: 28px;
        text-align: center;
        color: #be185d;
    }

    .top-1 .position { font-size: 20px; }

    .play-mini-btn {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, #ec4899 0%, #be185d 100%);
        color: white;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .play-mini-btn svg { width: 16px; height: 16px; fill: white; }
    .play-mini-btn.active { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }
    .play-mini-btn .icon-pause, .play-mini-btn .icon-loading { display: none; }

    .entry-info { flex: 1; min-width: 0; }

    .entry-title {
        font-weight: 600;
        font-size: 14px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        display: block;
        color: #be185d;
        text-decoration: none;
    }

    .entry-title:hover { color: #9f1239; }
    .entry-author { font-size: 12px; color: #9f1239; }

    .entry-comment-preview {
        font-size: 11px;
        color: #f472b6;
        font-style: italic;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        margin-top: 2px;
    }

    .vote-section { display: flex; flex-direction: column; align-items: center; min-width: 40px; }

    .vote-btn {
    background: none;
    border: none;
    cursor: pointer;
    transition: 0.2s;
    color: #999;
    padding: 4px;
}
.vote-btn:hover { color: #2f8cff; transform: scale(1.2); }
.vote-btn.voted { color: #2f8cff; }
.vote-btn svg { width: 20px; height: 20px; }

    @keyframes heartBeat {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.3); }
    }

    .vote-count { font-size: 12px; font-weight: 600; color: #be185d; }

    .download-mini-btn, .info-mini-btn {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: 0.2s;
        margin-right: 8px;
        font-size: 14px;
    }

    .download-mini-btn {
        background: #fff1f2;
        color: #be185d;
    }

    .download-mini-btn:hover {
        background: #be185d;
        color: white;
    }

    .info-mini-btn {
        background: #fce7f3;
        color: #be185d;
    }

    .info-mini-btn:hover, .info-mini-btn.active {
        background: #be185d;
        color: white;
    }

    .entry-details {
        display: none;
        background: #fff5f5;
        border-radius: 0 0 12px 12px;
        padding: 16px;
        margin-top: -12px;
        margin-bottom: 12px;
        border: 1px solid #fecaca;
        border-top: none;
    }

    .entry-details.show { display: block; }

    .entry-details-row { display: flex; margin-bottom: 8px; font-size: 13px; }
    .entry-details-label { color: #9f1239; min-width: 100px; }
    .entry-details-value { color: #be185d; }

    .entry-comment {
        background: #fff;
        border: 1px solid #fda4af;
        border-radius: 8px;
        padding: 10px 12px;
        margin-bottom: 12px;
    }

    .entry-comment-label { font-weight: 600; color: #be185d; margin-bottom: 4px; font-size: 12px; }
    .entry-comment-text { color: #9f1239; font-size: 13px; }

    .entry-lyrics { margin-top: 12px; padding-top: 12px; border-top: 1px solid #fecaca; }
    .entry-lyrics-title { font-weight: 600; font-size: 13px; margin-bottom: 8px; color: #be185d; }
    .entry-lyrics-text { font-size: 13px; line-height: 1.5; color: #9f1239; white-space: pre-wrap; max-height: 200px; overflow-y: auto; }

    .empty-chart {
        text-align: center;
        padding: 40px 20px;
        background: white;
        border-radius: 16px;
        border: 2px dashed #fda4af;
    }

    .empty-chart-icon { font-size: 48px; margin-bottom: 16px; }
    .empty-chart p { color: #9f1239; }

    .nav-links {
        display: flex;
        justify-content: center;
        gap: 16px;
        margin-top: 30px;
    }

    .nav-links a {
        color: #9f1239;
        text-decoration: none;
        font-size: 14px;
    }

    .nav-links a:hover {
        color: #be185d;
    }
    .chart-tabs {
        display: flex;
        justify-content: center;
        gap: 8px;
        margin-bottom: 16px;
        flex-wrap: wrap;
    }

    .chart-tab {
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 13px;
        text-decoration: none;
        transition: all 0.2s;
        white-space: nowrap;
    }

    .chart-tab:not(.active) {
        background: rgba(255,255,255,0.8);
        color: var(--text-secondary);
    }

    .chart-tab:not(.active):hover {
        background: white;
    }

    .chart-tab.active {
        font-weight: 600;
    }

    .chart-tab.valentine.active {
        background: linear-gradient(135deg, #ec4899 0%, #be185d 100%);
        color: white;
    }
</style>
@endpush

@section('content')
<div class="valentine-bg"></div>
<div class="floating-hearts" id="floating-hearts"></div>

<div class="chart-header">
    <div class="chart-emoji">💕</div>
    <div class="chart-tabs">
        <a href="{{ route('charts.index') }}" class="chart-tab">Недельный</a>
        <a href="{{ route('charts.allTime') }}" class="chart-tab">За всё время</a>
        <span class="chart-tab valentine active">💕 14 февраля</span>
    </div>
    <h1 class="chart-title">Песни о любви</h1>
    <p class="chart-description">{{ $chart->description }}</p>
    <div class="chart-timer">
        ⏰ До подведения итогов: {{ $timeLeft }}
    </div>
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

<div class="prizes-info">
    @foreach($rewards as $pos => $songs)
        <div class="prize-item">
            <div class="prize-icon">
                @if($pos == 1) 🥇💕 @elseif($pos == 2) 🥈💕 @elseif($pos == 3) 🥉💕 @elseif($pos == 4) 4️⃣💕 @elseif($pos == 5) 5️⃣💕 @endif
            </div>
            <div class="prize-text">+{{ $songs }} песен</div>
        </div>
    @endforeach
</div>



@if($entries->isEmpty())
    <div class="empty-chart">
        <div class="empty-chart-icon">💔</div>
        <p>Пока нет песен</p>
        <p style="font-size: 13px; margin-top: 8px;">Будьте первым, кто добавит песню о любви!</p>
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
            <div class="chart-entry {{ $rankClass }}" id="entry-{{ $entry->id }}">
                <div class="position">
                    @if($rank == 1) 👑
                    @elseif($rank == 2) 💕
                    @elseif($rank == 3) 💗
                    @else {{ $rank }}
                    @endif
                </div>
                
                @if($audioUrl)
                <button class="play-mini-btn" 
                    data-play-track 
                    data-url="{{ $audioUrl }}"
                    data-title="{{ $entry->song->title }}"
                    data-author="{{ $entry->user->first_name ?? 'Автор' }}"
                >
                    <svg class="icon-play" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                    <svg class="icon-pause" viewBox="0 0 24 24"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
                    <div class="spinner icon-loading"></div>
                </button>
                @endif

                <div class="entry-info">
                    <a href="javascript:;" class="entry-title">{{ $entry->song->title }}</a>
                    <div class="entry-author">
                        {{ $entry->user->first_name ?? $entry->user->username ?? 'Автор' }}
                        @if($isOwn) <span style="color: #ec4899;">(вы)</span> @endif
                    </div>
                    @if(request()->admin)
                    ID: {{ $entry->user->user_id }} 
                    @endif
                    @if($entry->comment)
                        <div class="entry-comment-preview">💌 {{ Str::limit($entry->comment, 40) }}</div>
                    @endif
                </div>

                <div style="display: flex; align-items: center;">
                    <button class="info-mini-btn" 
                        onclick="toggleEntryInfo({{ $entry->id }})"
                        title="Информация"
                        id="info-btn-{{ $entry->id }}"
                    >
                        ℹ️
                    </button>

                    @if($audioUrl)
                    <button class="download-mini-btn" 
                        onclick="downloadChartSong(this, {{ $entry->song_id }}, {{ $entry->variant ?? 1 }})"
                        title="Скачать"
                    >
                        ⬇
                    </button>
                    @endif
                </div>

                <div class="vote-section">
                    <button class="vote-btn {{ $isVoted ? 'voted' : '' }} {{ $isOwn ? 'own-song' : '' }}" 
                        @if(!$isOwn)
                            onclick="toggleVote(this, {{ $entry->id }}, false)"
                        @else
                            disabled
                        @endif
                        data-entry-id="{{ $entry->id }}"
                    >
                        <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"/></svg>
                    </button>
                    <span class="vote-count" id="votes-{{ $entry->id }}">{{ $entry->votes_count }}</span>
                </div>
            </div>
            
            <div class="entry-details" id="details-{{ $entry->id }}">
                @if($entry->comment)
                    <div class="entry-comment">
                        <div class="entry-comment-label">💌 История песни:</div>
                        <div class="entry-comment-text">{{ $entry->comment }}</div>
                    </div>
                @endif
                
                <div class="entry-details-row">
                    <span class="entry-details-label">🎸 Жанр:</span>
                    <span class="entry-details-value">{{ $entry->song->genre ?: 'Не указан' }}</span>
                </div>
                <div class="entry-details-row">
                    <span class="entry-details-label">📅 Создан:</span>
                    <span class="entry-details-value">{{ $entry->song->created_at->format('d.m.Y') }}</span>
                </div>
                
                @if($entry->song->lyrics)
                    <div class="entry-lyrics">
                        <div class="entry-lyrics-title">📝 Текст:</div>
                        <div class="entry-lyrics-text">{{ $entry->song->lyrics }}</div>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
@endif

@endsection

@push('scripts')
<script>
    // Плавающие сердечки
    function createHearts() {
        const container = document.getElementById('floating-hearts');
        const hearts = ['💕', '💗', '💖', '💘', '💝', '❤️'];
        
        for (let i = 0; i < 15; i++) {
            const heart = document.createElement('div');
            heart.className = 'heart';
            heart.textContent = hearts[Math.floor(Math.random() * hearts.length)];
            heart.style.left = Math.random() * 100 + '%';
            heart.style.animationDelay = Math.random() * 6 + 's';
            heart.style.animationDuration = (4 + Math.random() * 4) + 's';
            container.appendChild(heart);
        }
    }
    createHearts();

    // Показать/скрыть информацию
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

    // Добавить в чарт
    async function addToValentineChart() {
        const songId = document.getElementById('song-select').value;
        const comment = document.getElementById('song-comment').value;

        if (!songId) {
            alert('Выберите песню');
            return;
        }

        const btn = document.querySelector('.add-song-btn');
        btn.disabled = true;
        btn.textContent = 'Добавляю...';

        try {
            const response = await fetch('/api/charts/theme/add-song', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify({
                    song_id: songId,
                    theme: 'valentine',
                    variant: 1,
                    comment: comment
                }),
            });

            const data = await response.json();

            if (response.ok) {
                alert('💕 ' + data.message);
                window.location.reload();
            } else {
                alert(data.error || 'Ошибка');
                btn.disabled = false;
                btn.textContent = '💕 Добавить в чарт';
            }
        } catch (error) {
            alert('Ошибка: ' + error.message);
            btn.disabled = false;
            btn.textContent = '💕 Добавить в чарт';
        }
    }

    // Удалить из чарта
    async function removeFromChart(songId) {
        if (!confirm('Убрать песню из чарта?')) return;

        try {
            const response = await fetch('/api/charts/theme/remove-song', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify({
                    song_id: songId,
                    theme: 'valentine'
                }),
            });

            const data = await response.json();

            if (response.ok) {
                window.location.reload();
            } else {
                alert(data.error || 'Ошибка');
            }
        } catch (error) {
            alert('Ошибка: ' + error.message);
        }
    }

    // Голосование
    async function toggleVote(btn, entryId, isOwn) {
        if (isOwn) { alert('Нельзя голосовать за себя!'); return; }
        
        try {
            const countEl = document.getElementById(`votes-${entryId}`);
            
            const isVoted = btn.classList.contains('voted');
            
            btn.classList.toggle('voted');
            
            // Обновляем счетчик
            let currentCount = parseInt(countEl.textContent);
            countEl.textContent = currentCount + (isVoted ? -1 : 1);

            const response = await fetch(`/api/charts/${isVoted ? 'unvote' : 'vote'}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
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

    // Скачивание
    async function downloadChartSong(btn, songId, variant) {
        const originalContent = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '⏳';

        try {
            const response = await fetch(`/songs/${songId}/download/${variant}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                alert(data.error || 'Ошибка');
                return;
            }

            const tg = window.Telegram?.WebApp;
            if (tg && tg.initData) {
                tg.openLink(data.download_url);
            } else {
                window.location.href = data.download_url;
            }
        } catch (error) {
            alert('Ошибка: ' + error.message);
        } finally {
            setTimeout(() => {
                btn.disabled = false;
                btn.innerHTML = originalContent;
            }, 1000);
        }
    }
</script>
@endpush