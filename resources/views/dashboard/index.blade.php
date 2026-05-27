@extends('layouts.app')

@section('title', 'Личный кабинет — На Репите')

@section('content')
    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value">{{ $stats['total_songs'] }}</div>
            <div class="stat-label">Треков</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">{{ $stats['balance'] }}</div>
            <div class="stat-label">Баланс</div>
        </div>
    </div>

    <!-- Избранные треки -->
    <div>
        <div class="section-title">
            <span>❤️ Избранное</span>
        </div>

        @if($favoriteSongs->count() > 0)
            <div class="favorites-list">
                @foreach($favoriteSongs as $favorite)
                    @php
                        $audioUrl = $favorite->variant == 1 
                            ? $favorite->song->file_path 
                            : $favorite->song->file_path_2;
                        $favId = 'fav-' . $favorite->id;
                    @endphp
                    <div class="favorite-item">
                        <button class="favorite-play-btn" 
                            data-play-track 
                            data-url="{{ $audioUrl }}"
                            data-title="{{ $favorite->song->title ?: 'Без названия' }}"
                            data-author="{{ $favorite->song->user->first_name ?? $favorite->song->user->username ?? 'Автор' }}"
                            data-cover="{{ $favorite->song->cover_url ?? '🎶' }}"
                        >
                            <svg class="icon-play" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                            <svg class="icon-pause" viewBox="0 0 24 24"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
                            <div class="spinner icon-loading"></div>
                        </button>
                        <div class="favorite-info">
                            @if($favorite->song->user_id === $authUser->user_id)
                                <a href="{{ route('songs.show', $favorite->song->id) }}" class="favorite-title">
                                    {{ $favorite->song->title ?: 'Без названия' }}
                                </a>
                            @else
                                <span class="favorite-title" style="cursor:default;">
                                    {{ $favorite->song->title ?: 'Без названия' }}
                                </span>
                            @endif
                            <div class="favorite-variant">
                                {{ $favorite->song->user->first_name ?? $favorite->song->user->username ?? 'Автор' }}
                                · Вариант {{ $favorite->variant }}
                            </div>
                        </div>
                        <button class="action-btn info-mini-btn" 
                            onclick="toggleFavInfo('{{ $favId }}')"
                            id="info-btn-{{ $favId }}"
                            title="Информация"
                        >ℹ️</button>
                        @if($audioUrl)
                        <button class="action-btn download-mini-btn" 
                            onclick="downloadFavSong(this, {{ $favorite->song->id }}, {{ $favorite->variant }})"
                            title="Скачать трек"
                        >⬇</button>
                        @endif
                        <span class="favorite-heart">❤️</span>
                    </div>

                    <div class="entry-details" id="details-{{ $favId }}">
                        <div class="entry-details-row">
                            <span class="entry-details-label">👤 Автор:</span>
                            <span class="entry-details-value">{{ $favorite->song->user->first_name ?? $favorite->song->user->username ?? 'Автор' }}</span>
                        </div>
                        <div class="entry-details-row">
                            <span class="entry-details-label">🎸 Жанр:</span>
                            <span class="entry-details-value">{{ $favorite->song->genre ?: 'Не указан' }}</span>
                        </div>
                        <div class="entry-details-row">
                            <span class="entry-details-label">🎤 Стиль:</span>
                            <span class="entry-details-value">{{ $favorite->song->occasion ?: 'Не указан' }}</span>
                        </div>
                        <div class="entry-details-row">
                            <span class="entry-details-label">📅 Создан:</span>
                            <span class="entry-details-value">{{ $favorite->song->created_at ? $favorite->song->created_at->format('d.m.Y') : '—' }}</span>
                        </div>
                        @if($favorite->song->lyrics)
                            <div class="entry-lyrics">
                                <div class="entry-lyrics-title">📝 Текст песни:</div>
                                <div class="entry-lyrics-text">{{ $favorite->song->lyrics }}</div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="empty-favorites">
                <p>💡 Добавляй треки в избранное, чтобы они отображались здесь</p>
            </div>
        @endif
    </div>
@endsection

@push('scripts')
<script>
function toggleFavInfo(favId) {
    const details = document.getElementById('details-' + favId);
    const btn = document.getElementById('info-btn-' + favId);
    
    document.querySelectorAll('.entry-details.show').forEach(el => {
        if (el.id !== 'details-' + favId) el.classList.remove('show');
    });
    document.querySelectorAll('.info-mini-btn.active').forEach(el => {
        if (el.id !== 'info-btn-' + favId) el.classList.remove('active');
    });
    
    details.classList.toggle('show');
    btn.classList.toggle('active');
}

async function downloadFavSong(btn, songId, variant) {
    const originalContent = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '⏳';
    try {
        const response = await fetch(`/songs/${songId}/download/${variant}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        });
        const data = await response.json();
        if (!response.ok || !data.success) {
            alert(data.error || 'Ошибка при создании ссылки');
            return;
        }
        const tg = window.Telegram?.WebApp;
        if (tg && tg.initData) { tg.openLink(data.download_url); }
        else { window.location.href = data.download_url; }
    } catch (error) {
        alert('Ошибка: ' + error.message);
    } finally {
        setTimeout(() => { btn.disabled = false; btn.innerHTML = originalContent; }, 1000);
    }
}
</script>
@endpush