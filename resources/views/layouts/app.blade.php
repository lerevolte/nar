<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'На Репите')</title>
    
    @if($isMaxApp ?? false)
    <script src="https://st.max.ru/js/max-web-app.js"></script>
    @elseif($isMiniApp ?? false)
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    @endif
    
    <!-- Main Styles -->
    {{-- Версия по mtime файла: кэшируется браузером, сбрасывается только при изменении файла --}}
    <link rel="stylesheet" href="/css/app.css?v={{ @filemtime(public_path('css/app.css')) ?: 1 }}">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">
    @stack('styles')
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
</head>
<body>
    @if($isMiniApp ?? false)
    <!-- Mini App Header -->
    <div class="miniapp-header">
        <span class="miniapp-title">🎵 На Репите</span>
        <div style="display: flex; align-items: center; gap: 10px;">
            <button class="notif-bell" id="notif-bell" onclick="toggleNotifications()">
                🔔
                <span class="notif-badge" id="notif-badge" style="display:none;">0</span>
            </button>
            <a href="{{ route('payment.index') }}" onclick="navigateTo(event, '{{ route('payment.index') }}')" class="balance-badge">🎵 {{ $authUser->balance ?? 0 }}</a>
            <a href="{{ route('profile') }}" class="user-avatar">
                @if($authUser->avatar_url ?? false)
                    <img src="{{ $authUser->avatar_url }}" alt="Avatar">
                @else
                    {{ mb_substr($authUser->first_name ?? $authUser->username ?? 'U', 0, 1) }}
                @endif
            </a>
        </div>
    </div>
    @else
    <!-- Regular Header -->
    <header class="header">
        <div class="header-content">
            <a href="{{ route('dashboard') }}" class="logo">🎵 На Репите</a>
            <div class="user-panel">
                @if($authUser && in_array($authUser->user_id, [288559694, 154483653, 6231501485, 10276713030]))
                    <div class="admin-menu-wrap" data-admin="154483653">
                        <button type="button" class="btn-admin" onclick="toggleAdminMenu(event)">⚙ Админ ▾</button>
                        <div class="admin-menu" id="admin-menu">
                            @if(in_array($authUser->user_id, [288559694, 154483653]))
                                <a href="{{ route('admin.broadcast') }}">📢 Рассылки</a>
                            @endif
                            <a href="{{ route('admin.articles.index') }}">📝 Статьи</a>
                            <a href="{{ route('admin.pages.index') }}">📄 Страницы (с разделами)</a>
                            <a href="{{ route('admin.static-pages.index') }}">📃 Статические страницы</a>
                        </div>
                    </div>
                @endif
                <a href="{{ route('generate.create') }}" class="btn-create">+ Создать</a>
                <a href="{{ route('payment.index') }}" onclick="navigateTo(event, '{{ route('payment.index') }}')" class="balance-badge">
                    🎵 {{ $authUser->balance ?? 0 }}
                </a>
                <button class="notif-bell" id="notif-bell" onclick="toggleNotifications()">
                    🔔
                    <span class="notif-badge" id="notif-badge" style="display:none;">0</span>
                </button>
                <a href="{{ route('profile') }}" class="user-avatar">
                    @if($authUser->avatar_url)
                        <img src="{{ $authUser->avatar_url }}" alt="Avatar">
                    @else
                        {{ mb_substr($authUser->first_name ?? $authUser->username ?? 'U', 0, 1) }}
                    @endif
                </a>
            </div>
        </div>
    </header>
    @endif

    <!-- Notification Panel -->
    <div class="notif-overlay" id="notif-overlay" onclick="closeNotifications()"></div>
    <div class="notif-panel" id="notif-panel">
        <div class="notif-panel-header">
            <span class="notif-panel-title">Уведомления</span>
            <button class="notif-mark-all" id="notif-mark-all" onclick="markAllRead()">Прочитать все</button>
        </div>
        <div class="notif-list" id="notif-list">
            <div class="notif-empty">Загрузка...</div>
        </div>
    </div>

    <!-- Notification Popup (auto on page load) -->
    <div class="notif-popup-overlay" id="notif-popup-overlay" onclick="if(event.target===this)closeNotifPopup()">
        <div class="notif-popup">
            <div class="notif-popup-header">
                <span class="notif-popup-title">🔔 Новые уведомления</span>
                <button class="notif-popup-close" onclick="closeNotifPopup()" aria-label="Закрыть">&times;</button>
            </div>
            <div class="notif-popup-list" id="notif-popup-list"></div>
            <div class="notif-popup-footer">
                <button class="btn-create" onclick="markPopupRead()">Прочитано</button>
            </div>
        </div>
    </div>

    <main class="main-content">
        @yield('content')
    </main>

    <!-- Bottom Navigation -->
    <nav class="bottom-nav">
        <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <span class="nav-icon">🏠</span>
            <span>Главная</span>
        </a>
        <a href="{{ route('generate.create') }}" class="nav-item {{ request()->routeIs('generate.create') ? 'active' : '' }}">
            <span class="nav-icon">✨</span>
            <span>Создать</span>
        </a>
        <a href="{{ route('charts.index') }}" class="nav-item {{ request()->routeIs('charts.*') ? 'active' : '' }}">
            <span class="nav-icon">🏆</span>
            <span>Чарты</span>
        </a>
        <a href="/songs" class="nav-item {{ request()->is('songs') ? 'active' : '' }}">
            <span class="nav-icon">🎵</span>
            <span>Треки</span>
        </a>
        @if(isset($authUser))
        <a href="/voices" class="nav-item {{ request()->is('voices') ? 'active' : '' }}">
            <span class="nav-icon">🎙</span>
            <span>Голоса</span>
        </a>
        @endif
    </nav>

    <!-- GLOBAL STICKY PLAYER -->
    <div id="global-sticky-player">
        <div class="gsp-progress-container" id="gsp-progress-bar">
            <div class="gsp-progress-bg">
                <div class="gsp-progress-fill" id="gsp-progress-fill"></div>
            </div>
        </div>
        
        <div class="gsp-cover" id="gsp-cover">🎵</div>
        
        <div class="gsp-info">
            <div class="gsp-title" id="gsp-title">Название трека</div>
            <div class="gsp-author" id="gsp-author">Исполнитель</div>
            <div style="font-size: 10px; color: var(--text-tertiary); margin-top: 2px;">
                <span id="gsp-current-time">0:00</span> / <span id="gsp-duration">0:00</span>
            </div>
        </div>

        <div class="gsp-controls">
            <button class="btn gsp-btn" id="gsp-play-btn">
                <svg id="gsp-play-icon" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                <svg id="gsp-pause-icon" style="display:none;" viewBox="0 0 24 24"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
                <div class="spinner" id="gsp-loader-icon" style="display:none;"></div>
            </button>
            
            <button class="btn gsp-close" id="gsp-close-btn">
                <svg style="width:20px; height:20px; fill:currentColor;" viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
            </button>
        </div>
    </div>

    @if($isMaxApp ?? false)
    <script>
        // MAX Mini App
        const maxApp = window.WebApp;
        if (maxApp) {
            maxApp.ready();
            // BackButton
            if (maxApp.BackButton) {
                const rootPaths = ['/', '/dashboard', '/charts', '/login'];
                if (rootPaths.includes(window.location.pathname)) {
                    maxApp.BackButton.hide && maxApp.BackButton.hide();
                } else {
                    maxApp.BackButton.show && maxApp.BackButton.show();
                    maxApp.BackButton.onClick && maxApp.BackButton.onClick(() => window.history.back());
                }
            }
        }
    </script>
    @elseif($isMiniApp ?? false)
    <script>
        const tg = window.Telegram.WebApp;
        tg.expand();
        tg.ready();
        const handleBack = () => window.history.back();
        tg.BackButton.offClick(handleBack);
        tg.BackButton.onClick(handleBack);
        const rootPaths = ['/', '/dashboard', '/charts', '/login'];
        if (rootPaths.includes(window.location.pathname)) {
            tg.BackButton.hide();
        } else {
            tg.BackButton.show();
        }
    </script>
    @endif
    
    <script src="/js/player.js?v={{ @filemtime(public_path('js/player.js')) ?: 1 }}"></script>

    <!-- Notification JS -->
    <script>
    let notifOpen = false;
    let notifLoaded = false;

    function toggleNotifications() {
        notifOpen = !notifOpen;
        document.getElementById('notif-panel').classList.toggle('open', notifOpen);
        document.getElementById('notif-overlay').classList.toggle('open', notifOpen);
        if (notifOpen && !notifLoaded) {
            loadNotifications();
        }
    }

    function closeNotifications() {
        notifOpen = false;
        document.getElementById('notif-panel').classList.remove('open');
        document.getElementById('notif-overlay').classList.remove('open');
    }

    async function loadNotifications() {
        try {
            const r = await fetch('/api/notifications', {
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                credentials: 'same-origin',
            });
            const d = await r.json();
            notifLoaded = true;
            renderNotifications(d.notifications, d.unread_count);
        } catch (e) {
            document.getElementById('notif-list').innerHTML = '<div class="notif-empty">Ошибка загрузки</div>';
        }
    }

    function renderNotifications(list, unreadCount) {
        const container = document.getElementById('notif-list');
        if (!list || list.length === 0) {
            container.innerHTML = '<div class="notif-empty">Уведомлений пока нет</div>';
            return;
        }
        container.innerHTML = list.map(n => `
            <div class="notif-item ${n.is_read ? '' : 'unread'}" data-id="${n.id}">
                <div class="notif-item-title">${n.title}</div>
                <div class="notif-item-text clamped" onclick="this.classList.toggle('clamped')">${n.message}</div>
                <div class="notif-item-date">${n.created_at || ''}</div>
            </div>
        `).join('');
        updateBadge(unreadCount);
    }

    function updateBadge(count) {
        const badge = document.getElementById('notif-badge');
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
    }

    async function markAllRead() {
        try {
            await fetch('/api/notifications/read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                credentials: 'same-origin',
                body: JSON.stringify({ mark_all: true }),
            });
            document.querySelectorAll('.notif-item.unread').forEach(el => el.classList.remove('unread'));
            updateBadge(0);
        } catch (e) { console.error(e); }
    }

    // Check unread count on page load (+ auto popup for new notifications)
    async function checkUnread() {
        try {
            const r = await fetch('/api/notifications', {
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                credentials: 'same-origin',
            });
            const d = await r.json();
            updateBadge(d.unread_count);
            maybeShowNotifPopup(d.notifications);
        } catch (e) {}
    }
    document.addEventListener('DOMContentLoaded', checkUnread);

    // === Auto popup ===
    // Показываем попапом при загрузке только непрочитанные уведомления
    // за последние 24 часа. Каждое уведомление всплывает один раз на устройство
    // (чтобы не появлялось на каждой странице). Кнопка «Прочитано» помечает
    // показанные уведомления прочитанными — после этого они больше не выскакивают.
    const POPUP_SEEN_KEY = 'narepite_popup_seen_notifs';
    const POPUP_MAX_AGE_SEC = 24 * 60 * 60; // 24 часа
    let popupNotifIds = [];

    function getPopupSeen() {
        try { return JSON.parse(localStorage.getItem(POPUP_SEEN_KEY) || '[]'); }
        catch (e) { return []; }
    }
    function savePopupSeen(ids) {
        try { localStorage.setItem(POPUP_SEEN_KEY, JSON.stringify(ids.slice(-300))); }
        catch (e) {}
    }

    function maybeShowNotifPopup(list) {
        if (!Array.isArray(list) || list.length === 0) return;
        const seen = getPopupSeen();
        const nowSec = Date.now() / 1000;
        const fresh = list.filter(n =>
            !n.is_read &&
            !seen.includes(n.id) &&
            n.created_at_ts &&
            (nowSec - n.created_at_ts) <= POPUP_MAX_AGE_SEC
        );
        if (fresh.length === 0) return;

        popupNotifIds = fresh.map(n => n.id);
        const container = document.getElementById('notif-popup-list');
        container.innerHTML = fresh.map(n => `
            <div class="notif-item">
                <div class="notif-item-title">${n.title}</div>
                <div class="notif-item-text">${n.message}</div>
                <div class="notif-item-date">${n.created_at || ''}</div>
            </div>
        `).join('');
        document.getElementById('notif-popup-overlay').classList.add('open');

        savePopupSeen(seen.concat(popupNotifIds));
    }

    function closeNotifPopup() {
        document.getElementById('notif-popup-overlay').classList.remove('open');
    }

    // Кнопка «Прочитано»: помечаем показанные уведомления прочитанными на сервере,
    // обновляем бейдж и закрываем попап. Больше выскакивать не будут.
    async function markPopupRead() {
        const ids = popupNotifIds.slice();
        closeNotifPopup();
        if (ids.length === 0) return;
        try {
            await fetch('/api/notifications/read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                credentials: 'same-origin',
                body: JSON.stringify({ notification_ids: ids }),
            });
            // Сбросить выделение в боковой панели, если она уже загружена
            ids.forEach(id => {
                const el = document.querySelector(`.notif-item[data-id="${id}"]`);
                if (el) el.classList.remove('unread');
            });
            // Уменьшить бейдж
            const badge = document.getElementById('notif-badge');
            const cur = parseInt(badge.textContent, 10) || 0;
            updateBadge(Math.max(0, cur - ids.length));
        } catch (e) { console.error(e); }
    }
    </script>
    <script>
    function toggleAdminMenu(e) {
        e.stopPropagation();
        document.getElementById('admin-menu').classList.toggle('open');
    }
    document.addEventListener('click', function(e) {
        var menu = document.getElementById('admin-menu');
        if (menu && !e.target.closest('.admin-menu-wrap')) {
            menu.classList.remove('open');
        }
    });
    </script>
    @stack('scripts')
    <script>
    function navigateTo(event, url) {
        event.preventDefault();
        window.location.href = url;
    }
    </script>
    @if(session('success') && str_contains(session('success'), 'пожаловать'))
    <script>
        try { ym(105879987,'reachGoal','registration'); } catch(e) {}
    </script>
    @endif

    @if(isset($authUser) && $authUser->user_id)
    <script>
        try {
            ym(105879987, 'setUserID', '{{ $authUser->user_id }}');

            ym(105879987, 'getClientID', function(clientID) {
                fetch('/api/save-ym-client', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ ym_client_id: clientID })
                }).catch(function(){});
            });
        } catch(e) {}
    </script>
    @endif
</body>
</html>