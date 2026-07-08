<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">



    <title>@yield('title', 'На Репите — Нейросеть для генерации песен онлайн | ИИ для создания музыки')</title>
    @yield('meta')

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: { fontFamily: { sans: ['MyriadPro','Helvetica','Inter','sans-serif'] } } }
        }
    </script>
    <meta name="mailru-domain" content="6EtAEkhlMzrTtqmC" />
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">
    <link rel="stylesheet" href="/css/landing.css?v={{ time() }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css">
    <!-- Yandex.Metrika -->
    <script type="text/javascript">
        (function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
        m[i].l=1*new Date();
        for(var j=0;j<document.scripts.length;j++){if(document.scripts[j].src===r){return;}}
        k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)})
        (window,document,"script","https://mc.yandex.ru/metrika/tag.js","ym");
        ym(105879987,"init",{clickmap:true,trackLinks:true,accurateTrackBounce:true,webvisor:true});
    </script>
    <noscript><div><img src="https://mc.yandex.ru/watch/105879987" style="position:absolute;left:-9999px;" alt=""/></div></noscript>

    <style>
        @font-face { font-family:'MyriadPro'; src:local('Myriad Pro'),local('MyriadPro-Regular'); font-weight:normal; }
        @font-face { font-family:'MyriadPro'; src:local('Myriad Pro Bold'),local('MyriadPro-Bold'); font-weight:bold; }
        body { font-family:'MyriadPro','Helvetica','Inter',sans-serif; }
    </style>

    @stack('styles')

    @yield('jsonld')
    @include('partials.seo.json-ld', ['include' => ['site-nav']])
</head>
<body class="bg-white font-sans text-gray-800 antialiased page-flex">

    <!-- Header -->
    <header class="header-gradient text-white sticky top-0 z-50" style="height:60px;">
        <div class="max-w-7xl mx-auto px-4 md:px-8 h-full flex justify-between items-center">
            <div class="flex items-center gap-4">
                <button id="menu-toggle" class="menu-toggler"><span></span><span></span><span></span></button>
                <a href="/" class="flex items-center gap-2 font-bold text-xl tracking-wider text-white">
                    <img src="/img/logo1.svg" style="max-width:95%">
                </a>
            </div>

            <!-- <nav class="header-main-menu">
                <a href="/articles">Статьи</a>
                @if(isset($menuPages))
                    @foreach($menuPages as $mp)
                        <a href="{{ route('public.pages.show', $mp->slug) }}">{{ $mp->title }}</a>
                    @endforeach
                @endif
            </nav> -->

            <div class="flex items-center gap-4">
                @if(isset($authUser) && $authUser)
                    <span class="text-sm text-gray-300 hidden md:inline">{{ $authUser->first_name ?? $authUser->username ?? '' }}</span>
                    <a href="{{ route('dashboard') }}" class="btn-blue">Личный кабинет</a>
                @else
                    <a href="{{ route('login') }}" class="login-btn green-btn">Вход</a>
                    <a href="{{ route('register') }}" class="btn-blue hidden md:inline-flex">Регистрация</a>
                @endif
            </div>
        </div>
    </header>

    <!-- Sidebar (mobile menu) -->
    <div id="sidebar-overlay" class="sidebar-overlay"></div>
    @php
        $navCurrent = url()->current();
        $navAriaCurrent = fn ($url) => $navCurrent === $url ? ' aria-current="page"' : '';
    @endphp
    <nav id="sidebar-menu" class="sidebar-menu" aria-label="Главное меню">
        <button id="sidebar-close" class="sidebar-menu-close">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
        <div class="sidebar-nav">
            <a href="/"{!! $navAriaCurrent(url('/')) !!}>Главная</a>
            <a href="/articles"{!! $navAriaCurrent(url('/articles')) !!}>Статьи</a>
            @if(isset($menuPages))
                @foreach($menuPages as $p)
                    @php
                        $pUrl = $p->children->isNotEmpty()
                            ? route('public.pages.show.child', [$p->slug, $p->children->first()->slug])
                            : route('public.pages.show', $p->slug);
                    @endphp
                    <a href="{{ $pUrl }}"{!! $navAriaCurrent($pUrl) !!}>{{ $p->title }}</a>
                @endforeach
            @endif
            @if(isset($menuStaticPages))
                @foreach($menuStaticPages as $sp)
                    @php $spUrl = route('public.static-pages.show', $sp->slug); @endphp
                    <a href="{{ $spUrl }}"{!! $navAriaCurrent($spUrl) !!}>{{ $sp->title }}</a>
                @endforeach
            @endif
            <a href="/create-song"{!! $navAriaCurrent(url('/create-song')) !!}>Создать трек</a>
            @if(isset($authUser) && $authUser)
                <a href="{{ route('charts.index') }}"{!! $navAriaCurrent(route('charts.index')) !!}>Чарты</a>
                <a href="{{ route('dashboard') }}"{!! $navAriaCurrent(route('dashboard')) !!}>Личный кабинет</a>
            @else
                <a href="{{ route('login') }}"{!! $navAriaCurrent(route('login')) !!}>Вход</a>
                <a href="{{ route('register') }}"{!! $navAriaCurrent(route('register')) !!}>Регистрация</a>
            @endif

        </div>
    </nav>

    <!-- Content -->
    <main class="page-main">
        @yield('content')
    </main>

    <!-- CTA -->
    <section class="cta-section mt-12 py-16 md-py-30 px-4 text-center border-t border-gray-100 relative">
        <div class="absolute inset-0 bg-gradient-to-b from-transparent to-white/90 z-0" style="    background: url(/img/foot_bg.jpg);
    background-size: cover;
    background-position: bottom;background-repeat: no-repeat;"></div>
        <div class="relative z-10 max-w-2xl mx-auto">
            <h2 style="font-size:42px;font-weight:bold;color:#111;margin-bottom:16px;line-height:1.1;">Создай свою песню прямо сейчас</h2>
            <!-- <p style="font-size:18px;color:#8f8f8f;margin-bottom:32px;">Первая песня в подарок</p> -->
            <a href="/create-song"  class="btn-blue px-8 py-3" style="height:auto;box-shadow:0 0 20px rgba(47,140,255,0.4);">Создать трек</a>
        </div>
    </section>
    <!-- Footer -->
    <footer class="footer-gradient text-gray-300 px-6 md:px-16" style="padding-top:25px;padding-bottom:48px;">
        <div class="max-w-7xl mx-auto px-4 md:px-8 grid grid-cols-1 md:grid-cols-3 gap-8 text-center md:text-left">
            <div class="flex flex-col items-center md:items-start">
                <div class="flex items-center gap-2 font-bold text-xl tracking-wider text-white mb-2">
                    <img src="/img/logo1.svg">
                </div>
            </div>
            <div>
                <h4 style="font-size:18px;font-weight:600;color:#fff;margin-bottom:16px;">Навигация</h4>
                <ul>
                    <li style="margin:10px 0;"><a href="/articles" style="font-size:16px;color:#dedede;text-decoration:none;">Статьи</a></li>
                    @if(isset($menuPages))
                        @foreach($menuPages as $mp)
                            <li style="margin:10px 0;"><a href="{{ route('public.pages.show', $mp->slug) }}" style="font-size:16px;color:#dedede;text-decoration:none;">{{ $mp->title }}</a></li>
                        @endforeach
                    @endif
                    <li style="margin:10px 0;"><a href="{{ route('login') }}" style="font-size:16px;color:#dedede;text-decoration:none;">Вход</a></li>
                    <li style="margin:10px 0;"><a href="{{ route('register') }}" style="font-size:16px;color:#dedede;text-decoration:none;">Регистрация</a></li>
                    <li style="margin:10px 0;"><a href="/oferta" style="font-size:16px;color:#dedede;text-decoration:none;">Оферта</a></li>
                </ul>
            </div>
            <div>
                <h4 style="font-size:18px;font-weight:600;color:#fff;margin-bottom:16px;">Мессенджеры</h4>
                <ul>
                    <li style="margin:10px 0;"><a href="https://t.me/na_repitebot" target="_blank" style="font-size:16px;color:#dedede;text-decoration:none;">Telegram бот</a></li>
                    <li style="margin:10px 0;"><a href="https://max.ru/id501216944367_bot" target="_blank" style="font-size:16px;color:#dedede;text-decoration:none;">MAX бот</a></li>
                </ul>
            </div>
        </div>
    </footer>

    <!-- Track Info Modal -->
    <div id="track-modal" class="modal-overlay">
        <div class="modal-box">
            <button class="modal-close" onclick="closeTrackModal()">&times;</button>
            <h3 id="modal-title" style="font-size:21px;font-weight:bold;margin-bottom:4px;"></h3>
            <p id="modal-author" style="font-size:16px;color:#8f8f8f;margin-bottom:16px;"></p>
            <div id="modal-body"></div>
        </div>
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

    <!-- Auth Tooltip -->
    <div class="auth-tooltip" id="auth-tooltip">
        Войдите, чтобы голосовать &nbsp; <a href="{{ route('login') }}">Вход</a>
    </div>

    <script src="/js/player.js"></script>
     <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js"></script>
    <script>
        // Глобальные переменные
        window.isAuthed = {{ isset($authUser) && $authUser ? 'true' : 'false' }};
        window.csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        window.authUserId = {{ isset($authUser) && $authUser ? $authUser->user_id : 'null' }};

        // Mobile sidebar menu
        (function() {
            var toggle = document.getElementById('menu-toggle');
            var menu = document.getElementById('sidebar-menu');
            var overlay = document.getElementById('sidebar-overlay');
            var close = document.getElementById('sidebar-close');
            if (!toggle || !menu || !overlay) return;
            toggle.addEventListener('click', function() {
                menu.classList.add('active');
                overlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            });
            function closeMenu() {
                menu.classList.remove('active');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            }
            if (close) close.addEventListener('click', closeMenu);
            overlay.addEventListener('click', closeMenu);
        })();

        // Auth tooltip helper
        var authTooltipTimer = null;
        window.showAuthTooltip = function() {
            var tip = document.getElementById('auth-tooltip');
            if (!tip) return;
            tip.classList.add('show');
            if (authTooltipTimer) clearTimeout(authTooltipTimer);
            authTooltipTimer = setTimeout(function(){ tip.classList.remove('show'); }, 3000);
        };

        // === Play count (универсально для всех страниц) ===
        var playedSongs = new Set();
        document.addEventListener('click', function(e) {
            var btn = e.target.closest('[data-play-track]');
            if (btn && btn.dataset.songId) {
                var songId = parseInt(btn.dataset.songId);
                if (!playedSongs.has(songId)) {
                    playedSongs.add(songId);
                    fetch('/api/landing/play', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken },
                        body: JSON.stringify({ song_id: songId }),
                    }).then(function(r){ return r.json(); }).then(function(d) {
                        if (d.success) {
                            document.querySelectorAll('.plays-count-' + songId).forEach(function(el){ el.textContent = d.plays_count; });
                        }
                    }).catch(function(){});
                }
            }
        });

        // === Like (универсально) ===
        window.toggleLike = function(songId, btn) {
            if (btn.classList.contains('own-song')) return;
            if (!window.isAuthed) { window.showAuthTooltip(); return; }
            btn.disabled = true;
            fetch('/api/landing/toggle-like', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken },
                credentials: 'same-origin',
                body: JSON.stringify({ song_id: songId }),
            }).then(function(r){ return r.json(); }).then(function(d) {
                if (d.success) {
                    btn.classList.toggle('liked', d.action === 'liked');
                    document.querySelectorAll('.likes-count-' + songId).forEach(function(el){ el.textContent = d.votes_count; });
                } else { alert(d.error || 'Ошибка'); }
            }).catch(function(e){ alert('Ошибка: ' + e.message); })
            .finally(function(){ btn.disabled = false; });
        };
        // === Track info modal (универсально через data-атрибуты) ===
        function escapeHtml(text) { var d = document.createElement('div'); d.textContent = text || ''; return d.innerHTML; }

        document.addEventListener('click', function(e) {
            var btn = e.target.closest('[data-track-info]');
            if (!btn) return;
            var d = btn.dataset;
            var modal = document.getElementById('track-modal');
            if (!modal) return;

            document.getElementById('modal-title').textContent = d.title || '';
            document.getElementById('modal-author').textContent = d.author || '';

            var h = '';
            h += '<div class="track-detail-row"><span class="track-detail-label">🎸 Жанр:</span><span class="track-detail-value">' + (d.genre || 'Не указан') + '</span></div>';
            h += '<div class="track-detail-row"><span class="track-detail-label">📌 Повод:</span><span class="track-detail-value">' + (d.occasion || 'Не указан') + '</span></div>';
            h += '<div class="track-detail-row"><span class="track-detail-label">📅 Создан:</span><span class="track-detail-value">' + (d.created || '—') + '</span></div>';
            h += '<div class="track-detail-row"><span class="track-detail-label">▶ Прослушиваний:</span><span class="track-detail-value">' + (d.plays || 0) + '</span></div>';
            h += '<div class="track-detail-row"><span class="track-detail-label">❤️ Голосов:</span><span class="track-detail-value">' + (d.votes || 0) + '</span></div>';
            if (d.lyrics) {
                h += '<div class="track-lyrics"><div class="track-lyrics-title">📝 Текст песни:</div><div class="track-lyrics-text">' + escapeHtml(d.lyrics) + '</div></div>';
            }
            document.getElementById('modal-body').innerHTML = h;
            modal.classList.add('active');
        });

        window.closeTrackModal = function() {
            var m = document.getElementById('track-modal');
            if (m) m.classList.remove('active');
        };
        document.addEventListener('click', function(e) {
            var modal = document.getElementById('track-modal');
            if (modal && e.target === modal) closeTrackModal();
        });
        window.toggleBlockItems = function(blockId, btn) {
            var block = document.getElementById(blockId);
            if (!block) return;
            var grid = block.querySelector('.article-block-songs-grid, .article-block-articles-grid');
            if (!grid) return;
            grid.classList.toggle('expanded');
            btn.style.display = 'none';
        };
        if (window.Fancybox) {
            (function() {
                var containers = document.querySelectorAll('.article-content, .static-page-content, .page-content, .article-block-gradient, .article-block-image');
                var galleryIdx = 0;

                containers.forEach(function(container) {
                    var gallery = 'gallery-' + (galleryIdx++);
                    var imgs = container.querySelectorAll('img:not([data-no-fancybox])');
                    imgs = Array.from(imgs).filter(function(img) {
                        return !img.closest('.article-card, .article-card-cover, .track-card, .track-card-cover');
                    });

                    imgs.forEach(function(img) {
                        // Если уже в ссылке — пропускаем (кроме случая когда ссылка на тот же src)
                        var parent = img.parentElement;
                        if (parent && parent.tagName === 'A') {
                            var href = parent.getAttribute('href') || '';
                            if (href && href !== img.src) return; // осмысленная ссылка — не трогаем
                            parent.setAttribute('data-fancybox', gallery);
                            if (img.alt) parent.setAttribute('data-caption', img.alt);
                            return;
                        }

                        // Оборачиваем
                        var link = document.createElement('a');
                        link.href = img.src;
                        link.setAttribute('data-fancybox', gallery);
                        if (img.alt) link.setAttribute('data-caption', img.alt);
                        img.parentNode.insertBefore(link, img);
                        link.appendChild(img);
                    });
                });
            })();
            Fancybox.bind('[data-fancybox]', {
                Toolbar: {
                    display: {
                        left: ['infobar'],
                        middle: [],
                        right: ['slideshow', 'zoomIn', 'zoomOut', 'close'],
                    },
                },
            });
        }

        try {
            if (window.isAuthed && window.authUserId) {
                ym(105879987, 'setUserID', String(window.authUserId));
                ym(105879987, 'getClientID', function(clientID) {
                    fetch('/api/save-ym-client', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': window.csrfToken
                        },
                        body: JSON.stringify({ ym_client_id: clientID })
                    }).catch(function(){});
                });
            }
        } catch(e) {}

        document.addEventListener('DOMContentLoaded', function() {
            var containers = document.querySelectorAll('.article-content, .article-block-gradient, .page-content, .static-page-content');
            containers.forEach(function(container) {
                var tables = container.querySelectorAll('table');
                tables.forEach(function(table) {
                    if (table.closest('.table-scroll-wrap')) return;
                    var wrap = document.createElement('div');
                    wrap.className = 'table-scroll-wrap';
                    wrap.innerHTML = '<div class="table-scroll-hint"><svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40" fill="none"><g clip-path="url(#clip0)"><path d="M35.6524 3.47705H25.2173C24.7373 3.47705 24.3477 3.86666 24.3477 4.34667C24.3477 4.82669 24.7373 5.2163 25.2173 5.2163H35.6524C36.1324 5.2163 36.522 4.82669 36.522 4.34667C36.522 3.86666 36.1324 3.47705 35.6524 3.47705Z" fill="#222f3e"/><path d="M36.2688 3.73275L32.7904 0.254356C32.4512 -0.0847853 31.8999 -0.0847853 31.5608 0.254356C31.2216 0.593497 31.2216 1.14485 31.5608 1.48399L34.4235 4.34671L31.559 7.21115C31.2199 7.55029 31.2199 8.10164 31.559 8.44079C31.7295 8.60946 31.9521 8.69473 32.1747 8.69473C32.3974 8.69473 32.6199 8.60954 32.7904 8.44079L36.2688 4.96239C36.6079 4.62316 36.6079 4.0719 36.2688 3.73275Z" fill="#222f3e"/><path d="M13.043 3.47705H2.6079C2.12789 3.47705 1.73828 3.86666 1.73828 4.34667C1.73828 4.82669 2.12789 5.2163 2.6079 5.2163H13.043C13.523 5.2163 13.9126 4.82669 13.9126 4.34667C13.9126 3.86666 13.523 3.47705 13.043 3.47705Z" fill="#222f3e"/><path d="M3.83786 4.34671L6.70059 1.48399C7.03973 1.14485 7.03973 0.593497 6.70059 0.254356C6.36145 -0.0847853 5.8101 -0.0847853 5.47095 0.254356L1.99264 3.73284C1.6535 4.07198 1.6535 4.62333 1.99264 4.96247L5.47104 8.44087C5.6415 8.60954 5.86407 8.69482 6.08671 8.69482C6.30935 8.69482 6.53191 8.60962 6.70067 8.43916C7.03981 8.10001 7.03981 7.54866 6.70067 7.20952L3.83786 4.34671Z" fill="#222f3e"/><path d="M34.7832 17.3906C34.0684 17.3906 33.4022 17.608 32.8492 17.9785C32.3727 16.6254 31.0804 15.6514 29.5656 15.6514C28.8508 15.6514 28.1847 15.8689 27.6316 16.2393C27.1551 14.8862 25.8629 13.9123 24.3481 13.9123C23.715 13.9123 23.1202 14.0827 22.6089 14.3801V8.69471C22.6089 6.77636 21.0489 5.21631 19.1305 5.21631C17.2122 5.21631 15.6521 6.77636 15.6521 8.69471V23.4778L12.6294 21.2116C10.8381 19.8672 8.28842 20.0464 6.70057 21.6325C5.68314 22.65 5.68314 24.3039 6.70057 25.3214L19.0853 37.7078C20.5636 39.1861 22.5306 40.0001 24.6212 40.0001H28.6961C33.9693 40.0001 38.2616 35.7095 38.2616 30.4346V20.869C38.2616 18.9506 36.7015 17.3906 34.7832 17.3906ZM36.5224 30.4345C36.5224 34.7494 33.011 38.2608 28.6961 38.2608H24.6212C22.9933 38.2608 21.4645 37.6278 20.315 36.4781L7.9302 24.0917C7.59106 23.7526 7.59106 23.2012 7.9302 22.8621C8.46932 22.3247 9.18934 22.0482 9.91287 22.0482C10.5007 22.0482 11.092 22.2308 11.5877 22.6029L16 25.9126C16.2644 26.1108 16.6192 26.1404 16.9114 25.9943C17.2053 25.8465 17.3914 25.5456 17.3914 25.2169V8.69471C17.3914 7.73639 18.1706 6.95555 19.1306 6.95555C20.0906 6.95555 20.8697 7.73647 20.8697 8.69471V21.7386C20.8697 22.2186 21.2593 22.6082 21.7394 22.6082C22.2194 22.6082 22.609 22.2186 22.609 21.7386V17.3907C22.609 16.4323 23.3881 15.6515 24.3481 15.6515C25.3082 15.6515 26.0873 16.4324 26.0873 17.3907V21.7386C26.0873 22.2186 26.4769 22.6082 26.9569 22.6082C27.4369 22.6082 27.8265 22.2186 27.8265 21.7386V19.1298C27.8265 18.1715 28.6057 17.3907 29.5657 17.3907C30.5257 17.3907 31.3049 18.1716 31.3049 19.1298V21.7386C31.3049 22.2186 31.6945 22.6082 32.1745 22.6082C32.6545 22.6082 33.0441 22.2186 33.0441 21.7386V20.869C33.0441 19.9107 33.8232 19.1298 34.7833 19.1298C35.7433 19.1298 36.5224 19.9107 36.5224 20.869V30.4345Z" fill="#222f3e"/></g><defs><clipPath id="clip0"><rect width="40" height="40" fill="white"/></clipPath></defs></svg></div>';
                    var inner = document.createElement('div');
                    inner.className = 'table-scroll-inner';
                    table.parentNode.insertBefore(wrap, table);
                    wrap.appendChild(inner);
                    inner.appendChild(table);
                });
            });
        });

    </script>
    @stack('scripts')
</body>
</html>