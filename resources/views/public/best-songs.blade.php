<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Лучшие песни — НА РЕПИТЕ</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/css/landing.css?v={{ time() }}">
    <style>
        @font-face { font-family:'MyriadPro'; src:local('Myriad Pro'),local('MyriadPro-Regular'); font-weight:normal; }
        @font-face { font-family:'MyriadPro'; src:local('Myriad Pro Bold'),local('MyriadPro-Bold'); font-weight:bold; }
        body { font-family:'MyriadPro','Helvetica','Inter',sans-serif; background: #fff; }
    </style>
</head>
<body class="bg-white antialiased">

    <!-- Header -->
    <header class="header-gradient text-white px-6 md:px-12 flex justify-between items-center sticky top-0 z-50" style="height:60px;">
        <div class="flex items-center gap-4">
            <a href="/home" class="flex items-center gap-2 font-bold text-xl tracking-wider text-white" style="text-decoration:none;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" class="text-indigo-400">
                    <rect x="2" y="10" width="3" height="4" rx="1.5"/><rect x="7" y="6" width="3" height="12" rx="1.5"/>
                    <rect x="12" y="3" width="3" height="18" rx="1.5"/><rect x="17" y="8" width="3" height="8" rx="1.5"/>
                </svg>
                НА РЕПИТЕ
            </a>
        </div>
        <div class="flex items-center gap-4">
            @if($authUser)
                <span class="text-sm text-gray-300">{{ $authUser->first_name ?? $authUser->username ?? '' }}</span>
                <a href="{{ route('dashboard') }}" class="btn-blue">Личный кабинет</a>
            @else
                <a href="{{ route('login') }}" class="login-btn hidden md:inline">Вход</a>
                <a href="{{ route('register') }}" class="btn-blue">Регистрация</a>
            @endif
        </div>
    </header>

    <div class="best-songs-page">
        <h1 class="best-songs-title">Лучшие песни</h1>

        @if($entries->isEmpty())
            <div style="text-align:center;padding:60px 20px;color:#8f8f8f;">
                <div style="font-size:48px;margin-bottom:12px;">🎵</div>
                <p>Пока нет треков</p>
            </div>
        @else
            <div class="songs-public-grid">
                @foreach($entries as $entry)
                    @php
                        $song = $entry->song;
                        $user = $entry->user;
                        if (!$song || !$song->file_path) continue;
                        $isOwn = $authUser && $authUser->user_id === $entry->user_id;
                        $isLiked = in_array($entry->song_id, $votedSongIds);
                    @endphp
                    <div class="song-public-card">
                        <div class="song-public-cover">
                            @if($song->cover_url)
                                <img src="{{ $song->cover_url }}" alt="{{ $song->title }}" draggable="false">
                            @else
                                <div class="song-public-cover-placeholder">🎵</div>
                            @endif

                            <div class="song-public-play-overlay">
                                <button class="song-public-play-btn"
                                    data-play-track
                                    data-url="{{ $song->file_path }}"
                                    data-title="{{ $song->title }}"
                                    data-author="{{ $user->first_name ?? $user->username ?? 'Автор' }}"
                                    data-cover="{{ $song->cover_url ?? '' }}"
                                    data-song-id="{{ $song->id }}">
                                    <svg class="icon-play" viewBox="0 0 24 24"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                                    <svg class="icon-pause" viewBox="0 0 24 24" style="display:none;"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>
                                    <svg class="icon-loading" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" style="display:none;" class="spin"><path d="M12 2v4m0 12v4m10-10h-4M6 12H2m15.07-7.07l-2.83 2.83M9.76 14.24l-2.83 2.83m12.14 0l-2.83-2.83M9.76 9.76L6.93 6.93"/></svg>
                                </button>
                            </div>
                        </div>

                        <div class="song-public-info">
                            <div class="song-public-name">{{ $song->title ?: 'Без названия' }}</div>
                            <div class="song-public-meta">
                                <span class="song-public-meta-item">{{ $song->created_at ? $song->created_at->format('d.m.Y') : '—' }}</span>
                                <span class="song-public-meta-item">▶ <span class="plays-count-{{ $song->id }}">{{ $song->plays_count ?? 0 }}</span></span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            @if($entries->hasPages())
                <div class="pagination-public">
                    {{-- Назад --}}
                    @if($entries->onFirstPage())
                        <span class="page-nav disabled">
                            <svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
                            Назад
                        </span>
                    @else
                        <a href="{{ $entries->previousPageUrl() }}" class="page-nav">
                            <svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
                            Назад
                        </a>
                    @endif

                    {{-- Страницы (объединённый блок) --}}
                    @php
                        $current = $entries->currentPage();
                        $last = $entries->lastPage();
                        $from = max(1, $current - 2);
                        $to = min($last, $current + 2);
                    @endphp

                    <div class="pagination-pages">
                        @if($from > 1)
                            <a href="{{ $entries->url(1) }}">1</a>
                            @if($from > 2) <span class="page-dots">…</span> @endif
                        @endif

                        @for($i = $from; $i <= $to; $i++)
                            @if($i == $current)
                                <span class="page-num active">{{ $i }}</span>
                            @else
                                <a href="{{ $entries->url($i) }}">{{ $i }}</a>
                            @endif
                        @endfor

                        @if($to < $last)
                            @if($to < $last - 1) <span class="page-dots">…</span> @endif
                            <a href="{{ $entries->url($last) }}">{{ $last }}</a>
                        @endif
                    </div>

                    {{-- Вперёд --}}
                    @if($entries->hasMorePages())
                        <a href="{{ $entries->nextPageUrl() }}" class="page-nav">
                            Вперёд
                            <svg viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
                        </a>
                    @else
                        <span class="page-nav disabled">
                            Вперёд
                            <svg viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
                        </span>
                    @endif
                </div>
            @endif
        @endif
    </div>

    <!-- Auth Tooltip -->
    <div class="auth-tooltip" id="auth-tooltip">
        Войдите, чтобы голосовать &nbsp; <a href="{{ route('login') }}">Вход</a>
    </div>

    <!-- Sticky Player -->
    <div id="global-sticky-player">
        <div id="gsp-progress-bar" class="gsp-progress"><div id="gsp-progress-fill" class="gsp-progress-fill"></div></div>
        <div id="gsp-cover" class="gsp-cover">🎵</div>
        <div class="gsp-meta">
            <div id="gsp-title" class="gsp-title">—</div>
            <div id="gsp-author" class="gsp-author"></div>
        </div>
        <span id="gsp-current-time" class="gsp-time">0:00</span>
        <button id="gsp-play-btn" class="gsp-btn">
            <svg id="gsp-play-icon" width="18" height="18" viewBox="0 0 24 24" fill="white"><polygon points="5 3 19 12 5 21 5 3"/></svg>
            <svg id="gsp-pause-icon" width="18" height="18" viewBox="0 0 24 24" fill="white" style="display:none;"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>
            <svg id="gsp-loader-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" style="display:none;" class="spin"><path d="M12 2v4m0 12v4m10-10h-4M6 12H2m15.07-7.07l-2.83 2.83M9.76 14.24l-2.83 2.83m12.14 0l-2.83-2.83M9.76 9.76L6.93 6.93"/></svg>
        </button>
        <span id="gsp-duration" class="gsp-time">0:00</span>
        <button id="gsp-close-btn" class="gsp-btn" style="width:28px;height:28px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
    </div>

    <script src="/js/player.js"></script>
    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        // Play count
        const playedSongs = new Set();
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('[data-play-track]');
            if (btn && btn.dataset.songId) {
                const songId = parseInt(btn.dataset.songId);
                if (!playedSongs.has(songId)) {
                    playedSongs.add(songId);
                    fetch('/api/landing/play', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                        body: JSON.stringify({ song_id: songId }),
                    }).then(r => r.json()).then(d => {
                        if (d.success) {
                            document.querySelectorAll('.plays-count-' + songId).forEach(el => el.textContent = d.plays_count);
                        }
                    }).catch(() => {});
                }
            }
        });
    </script>
</body>
</html>