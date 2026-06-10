@extends('layouts.public')

@section('title', '🎵 Генерируем твою песню — НА РЕПИТЕ')

@push('styles')
<style>
    .pgs-wrap {
        max-width: 640px;
        margin: 0 auto;
        padding: 40px 20px;
        min-height: calc(100vh - 200px);
        font-family: 'Inter', -apple-system, sans-serif;
    }
    .pgs-card {
        background: white;
        border-radius: 24px;
        padding: 40px 32px;
        text-align: center;
        box-shadow: 0 10px 40px rgba(124, 58, 237, 0.1);
        border: 1px solid rgba(124, 58, 237, 0.08);
    }
    .pgs-icon {
        font-size: 64px;
        margin-bottom: 20px;
    }
    .pgs-title {
        font-size: 26px;
        font-weight: 800;
        color: #0f0a24;
        margin-bottom: 10px;
    }
    .pgs-subtitle {
        color: #6b7280;
        font-size: 15px;
        line-height: 1.6;
        margin-bottom: 24px;
    }

    /* ============ ЭТАП: СПИННЕР ============ */
    .pgs-spinner {
        width: 56px;
        height: 56px;
        margin: 0 auto 20px;
        border: 4px solid #e9d5ff;
        border-top-color: #a855f7;
        border-radius: 50%;
        animation: pgsSpin 1s linear infinite;
    }
    @keyframes pgsSpin { to { transform: rotate(360deg); } }

    /* ============ ПРОГРЕСС ============ */
    .pgs-progress-wrap {
        margin: 24px 0;
    }
    .pgs-progress-bar {
        height: 10px;
        background: #f3f4f6;
        border-radius: 100px;
        overflow: hidden;
        position: relative;
    }
    .pgs-progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #a855f7, #ec4899);
        border-radius: 100px;
        width: 0%;
        transition: width 1s ease;
        position: relative;
    }
    .pgs-progress-fill::after {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
        animation: pgsShimmer 2s infinite;
    }
    @keyframes pgsShimmer {
        to { transform: translateX(100%); }
    }
    .pgs-progress-text {
        font-size: 13px;
        color: #7c3aed;
        font-weight: 600;
        margin-top: 10px;
    }

    /* ============ АНИМИРОВАННЫЙ ORB (музыкальная волна) ============ */
    .pgs-orb {
        width: 120px;
        height: 120px;
        margin: 0 auto 24px;
        border-radius: 50%;
        background: conic-gradient(from 0deg, #a855f7, #ec4899, #fbbf24, #a855f7);
        animation: pgsOrbSpin 3s linear infinite;
        position: relative;
        box-shadow: 0 0 40px rgba(168, 85, 247, 0.4);
    }
    .pgs-orb::before {
        content: "🎵";
        position: absolute;
        inset: 10px;
        background: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 40px;
        animation: pgsOrbPulse 1.5s ease-in-out infinite;
    }
    @keyframes pgsOrbSpin { to { transform: rotate(360deg); } }
    @keyframes pgsOrbPulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(0.95); }
    }

    /* ============ СООБЩЕНИЯ ============ */
    .pgs-message {
        font-size: 16px;
        color: #374151;
        font-weight: 600;
        min-height: 24px;
        transition: opacity 0.4s ease;
    }

    /* ============ ИНФО О ЗАКАЗЕ ============ */
    .pgs-info {
        background: linear-gradient(135deg, #faf5ff, #fdf2f8);
        border-radius: 14px;
        padding: 14px 16px;
        margin: 20px 0;
        font-size: 13px;
        color: #6b7280;
        text-align: left;
    }
    .pgs-info b { color: #0f0a24; }
    .pgs-info-row {
        display: flex;
        justify-content: space-between;
        padding: 3px 0;
    }

    /* ============ ГОТОВАЯ ПЕСНЯ ============ */
    .pgs-song-card {
        background: linear-gradient(135deg, #0f0a24, #2d1b5e);
        color: white;
        border-radius: 20px;
        padding: 24px;
        margin: 20px 0;
    }
    .pgs-song-cover {
        width: 180px;
        height: 180px;
        border-radius: 16px;
        margin: 0 auto 20px;
        background: linear-gradient(135deg, #a855f7, #ec4899);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 80px;
        box-shadow: 0 10px 30px rgba(168, 85, 247, 0.4);
        overflow: hidden;
    }
    .pgs-song-cover img {
        width: 100%; height: 100%; object-fit: cover;
    }
    .pgs-song-title {
        font-size: 20px;
        font-weight: 800;
        margin-bottom: 8px;
    }
    .pgs-song-meta {
        font-size: 13px;
        opacity: 0.7;
        margin-bottom: 16px;
    }
    .pgs-player {
        width: 100%;
        margin: 12px 0;
        border-radius: 10px;
    }
    .pgs-btn-group {
        display: flex;
        gap: 10px;
        margin-top: 16px;
        flex-wrap: wrap;
        justify-content: center;
    }
    .pgs-btn {
        padding: 12px 20px;
        border-radius: 100px;
        font-weight: 700;
        font-size: 14px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s;
        cursor: pointer;
        border: none;
        font-family: inherit;
    }
    .pgs-btn-primary {
        background: linear-gradient(135deg, #fbbf24, #f59e0b);
        color: #0f0a24;
    }
    .pgs-btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 16px rgba(251, 191, 36, 0.3);
    }
    .pgs-btn-ghost {
        background: rgba(255, 255, 255, 0.1);
        color: white;
        backdrop-filter: blur(10px);
    }
    .pgs-btn-ghost:hover {
        background: rgba(255, 255, 255, 0.2);
    }

    /* ============ ОШИБКА ============ */
    .pgs-error {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #991b1b;
        padding: 16px;
        border-radius: 12px;
        margin: 16px 0;
        font-size: 14px;
    }

    /* ============ AD-HOC ============ */
    .pgs-version-label {
        display: inline-block;
        padding: 3px 10px;
        background: rgba(168, 85, 247, 0.15);
        color: #a855f7;
        border-radius: 100px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        margin-bottom: 6px;
    }

    @media (max-width: 640px) {
        .pgs-card { padding: 28px 20px; }
        .pgs-title { font-size: 22px; }
        .pgs-song-cover { width: 140px; height: 140px; font-size: 60px; }
    }

    /* ============ CREDENTIALS BLOCK ============ */
    .pgs-credentials {
        background: linear-gradient(135deg, #fef3c7, #fde68a);
        border: 2px solid #fbbf24;
        border-radius: 16px;
        padding: 20px;
        margin: 20px 0;
        text-align: left;
    }
    .pgs-credentials-title {
        font-size: 14px;
        font-weight: 800;
        color: #92400e;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .pgs-cred-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 10px 14px;
        background: white;
        border-radius: 10px;
        margin-bottom: 8px;
        font-size: 14px;
        border: 1px solid rgba(251, 191, 36, 0.3);
    }
    .pgs-cred-label {
        color: #6b7280;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
    }
    .pgs-cred-value {
        color: #0f0a24;
        font-weight: 700;
        font-family: 'SF Mono', Monaco, monospace;
        word-break: break-all;
    }
    .pgs-copy-btn {
        padding: 6px 12px;
        background: linear-gradient(135deg, #a855f7, #ec4899);
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        white-space: nowrap;
        transition: all 0.2s;
    }
    .pgs-copy-btn:hover { transform: translateY(-1px); }
    .pgs-copy-btn.copied {
        background: #10b981;
    }
    .pgs-cred-warning {
        background: rgba(239, 68, 68, 0.1);
        color: #991b1b;
        font-size: 12px;
        padding: 10px 12px;
        border-radius: 8px;
        margin-top: 10px;
        font-weight: 600;
    }
    .pgs-cabinet-btn {
        display: inline-block;
        width: 100%;
        padding: 16px;
        background: linear-gradient(135deg, #a855f7, #ec4899);
        color: white;
        text-decoration: none;
        border-radius: 12px;
        font-weight: 800;
        font-size: 15px;
        text-align: center;
        margin-top: 16px;
        box-shadow: 0 4px 16px rgba(168, 85, 247, 0.3);
        transition: all 0.2s;
    }
    .pgs-cabinet-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(168, 85, 247, 0.4);
    }
</style>
@endpush

@section('content')
<meta name="robots" content="noindex, nofollow"/>
<div class="pgs-wrap">
    <div class="pgs-card" id="pgs-card">

        {{-- ============ ФАЗА 1: ПРОВЕРКА ОПЛАТЫ ============ --}}
        <div id="phase-payment">
            <div class="pgs-spinner"></div>
            <h1 class="pgs-title">Проверяем оплату…</h1>
            <p class="pgs-subtitle">Обычно занимает 5–15 секунд. Не закрывай страницу.</p>
            <div class="pgs-info">
                <div class="pgs-info-row"><span>Заказ:</span><b>«{{ $order->title }}»</b></div>
                <div class="pgs-info-row"><span>Контакт:</span><b>{{ $order->contact }}</b></div>
                <div class="pgs-info-row"><span>Сумма:</span><b>{{ $order->amount }}₽</b></div>
            </div>
        </div>

        {{-- ============ ФАЗА 2: ГЕНЕРАЦИЯ ============ --}}
        <div id="phase-generation" style="display: none;">
            <div class="pgs-orb"></div>
            <div class="pgs-message" id="gen-message">Готовим магию…</div>
            <div class="pgs-progress-wrap">
                <div class="pgs-progress-bar">
                    <div class="pgs-progress-fill" id="gen-progress-fill"></div>
                </div>
                <div class="pgs-progress-text" id="gen-progress-text">0%</div>
            </div>
            <p class="pgs-subtitle" style="margin-top: 20px; font-size: 13px;">
                ⏱ Генерация занимает <b>2–5 минут</b>. <!--Можешь закрыть страницу —<br>
                мы отправим готовую песню на <b>{{ $order->contact }}</b>.-->
            </p>
        </div>

        {{-- ============ ФАЗА 3: ГОТОВО ============ --}}
        <div id="phase-done" style="display: none;">
            <div class="pgs-icon">🎉</div>
            <h1 class="pgs-title">Твоя песня готова!</h1>
            <p class="pgs-subtitle">Слушай, скачивай, делись</p>

            <div class="pgs-song-card">
                <div class="pgs-song-cover" id="song-cover">🎵</div>
                <div class="pgs-song-title" id="song-title-display">{{ $order->title }}</div>
                <div class="pgs-song-meta" id="song-meta">—</div>

                <div>
                    <div class="pgs-version-label">Вариант 1</div>
                    <audio class="pgs-player" controls id="player-1" preload="metadata"></audio>
                </div>

                <div id="version-2-wrap" style="display:none; margin-top: 16px;">
                    <div class="pgs-version-label">Вариант 2</div>
                    <audio class="pgs-player" controls id="player-2" preload="metadata"></audio>
                </div>

                <!-- <div class="pgs-btn-group">
                    <a id="btn-download-1" class="pgs-btn pgs-btn-primary" download>⬇ Скачать MP3</a>
                    <button class="pgs-btn pgs-btn-ghost" onclick="pgsShare()">🔗 Поделиться</button>
                </div> -->
            </div>

            <div class="pgs-credentials" id="credentials-block" style="display: none;">
                <div class="pgs-credentials-title">
                    🔑 Твой аккаунт создан
                </div>

                <div class="pgs-cred-row">
                    <div>
                        <div class="pgs-cred-label">Логин</div>
                        <div class="pgs-cred-value" id="cred-login">—</div>
                    </div>
                    <button class="pgs-copy-btn" onclick="pgsCopy('cred-login', this)">Копировать</button>
                </div>

                <div class="pgs-cred-row">
                    <div>
                        <div class="pgs-cred-label">Пароль</div>
                        <div class="pgs-cred-value" id="cred-password">—</div>
                    </div>
                    <button class="pgs-copy-btn" onclick="pgsCopy('cred-password', this)">Копировать</button>
                </div>

                <div class="pgs-cred-warning">
                    ⚠️ Сохрани пароль в надёжном месте. Мы не сможем его восстановить — только сбросить.
                </div>

                <a href="/" class="pgs-cabinet-btn">Перейти в личный кабинет →</a>
            </div>

            {{-- Блок для существующего юзера (пароль не показываем) --}}
            <div id="existing-user-block" style="display: none; margin-top: 16px;">
                <a href="/" class="pgs-cabinet-btn">Перейти в личный кабинет →</a>
                <p style="font-size: 12px; color: #6b7280; margin-top: 10px;">
                    Ты уже есть в системе — вошёл автоматически
                </p>
            </div>
        </div>

        {{-- ============ ФАЗА ОШИБКИ ============ --}}
        <div id="phase-error" style="display: none;">
            <div class="pgs-icon">⚠️</div>
            <h1 class="pgs-title" id="error-title">Что-то пошло не так</h1>
            <p class="pgs-subtitle" id="error-message">
                Не удалось сгенерировать песню.
            </p>
            <div class="pgs-error">
                Напиши нам на <a href="mailto:support@narepite.com" style="color:#7c3aed;">support@narepite.com</a>, укажи номер заказа <code>{{ $order->token }}</code>.
            </div>
            <button id="btn-retry-generation" class="pgs-btn pgs-btn-primary" style="margin-top:12px; display:none;" onclick="retryGeneration()">🔄 Повторить генерацию</button>
            <a href="/create-song" class="pgs-btn pgs-btn-ghost" style="margin-top:12px;">← На страницу создания</a>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
const COUNTER_ID = 105879987;
function pgsReachGoal(goal) {
    try { if (typeof ym !== 'undefined') ym(COUNTER_ID, 'reachGoal', goal); } catch(e) {}
}
const orderToken = @json($order->token);
const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

async function ensureAutoLogin(loginToken) {
    if (!loginToken) return;
    try {
        const r = await fetch('/api/public-generate/auto-login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            credentials: 'same-origin',
            body: JSON.stringify({
                order_token: orderToken,
                login_token: loginToken,
            }),
        });
        const d = await r.json();
        console.log('auto-login result', d);
    } catch (e) {
        console.error('auto-login error', e);
    }
}


// ============ СОСТОЯНИЕ ============
let paymentPollAttempts = 0;
const maxPaymentPolls = 40; // ~2 минуты
let songPollAttempts = 0;
const maxSongPolls = 120;    // ~6 минут
let generationStartTime = 0;
let genMessageInterval = null;

// ============ ФАЗА 1: ПРОВЕРКА ОПЛАТЫ ============
async function pollPayment() {
    paymentPollAttempts++;

    try {
        const r = await fetch(`/api/public-generate/order-status?token=${encodeURIComponent(orderToken)}`);
        const d = await r.json();

        if (!r.ok) throw new Error(d.error || 'Ошибка проверки');

        if (d.is_paid) {
            //pgsReachGoal('oplata-site');
            // Оплата подтверждена → запускаем генерацию

            if (d.login_token) {
                await ensureAutoLogin(d.login_token);
            }
            await startGeneration();
            return;
        }

        if (d.status === 'cancelled') {
            showError('Платёж отменён', 'Деньги не списаны. Попробуй оплатить ещё раз.');
            return;
        }

        if (paymentPollAttempts >= maxPaymentPolls) {
            showError('Оплата не подтвердилась за 2 минуты', 'Если деньги списаны — напиши в поддержку, создадим песню вручную.');
            return;
        }

        setTimeout(pollPayment, 3000);

    } catch (e) {
        console.error(e);
        if (paymentPollAttempts < maxPaymentPolls) {
            setTimeout(pollPayment, 3000);
        } else {
            showError('Ошибка проверки оплаты', e.message);
        }
    }
}

// ============ ФАЗА 2: ЗАПУСК ГЕНЕРАЦИИ ============
async function startGeneration() {
    // Плавный переход
    document.getElementById('phase-payment').style.display = 'none';
    document.getElementById('phase-generation').style.display = 'block';

    try {
        const r = await fetch(`/api/public-generate/start?token=${encodeURIComponent(orderToken)}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({ token: orderToken }),
        });
        const d = await r.json();

        if (!r.ok || !d.success) {
            throw new Error(d.error || 'Не удалось запустить генерацию');
        }

        // Если песня уже готова (после перезагрузки) — сразу polling без анимации прогресса
        if (d.is_ready) {
            pollSongStatus();
            return;
        }

        // Иначе стартуем визуал
        generationStartTime = Date.now();
        startProgressAnimation();
        startMessagesRotation();
        pollSongStatus();

    } catch (e) {
        showError('Ошибка запуска генерации', e.message);
    }
}

// ============ ВИЗУАЛЬНЫЙ ПРОГРЕСС (5 мин до 95%) ============
function startProgressAnimation() {
    const fill = document.getElementById('gen-progress-fill');
    const txt = document.getElementById('gen-progress-text');
    const totalMs = 5 * 60 * 1000; // 5 минут

    function tick() {
        const elapsed = Date.now() - generationStartTime;
        let pct = Math.min(95, (elapsed / totalMs) * 95);
        fill.style.width = pct.toFixed(1) + '%';
        txt.textContent = Math.floor(pct) + '%';
        if (pct < 95) requestAnimationFrame(tick);
    }
    tick();
}

// ============ РОТАЦИЯ СООБЩЕНИЙ ============
const genMessages = [
    '🎼 Подбираем мелодию…',
    '🎤 Настраиваем вокал…',
    '🥁 Добавляем ритм…',
    '🎹 Аранжируем инструменты…',
    '✨ Добавляем магию…',
    '🎧 Сводим треки…',
    '🎵 Почти готово…',
];

function startMessagesRotation() {
    const el = document.getElementById('gen-message');
    let i = 0;
    el.textContent = genMessages[0];
    genMessageInterval = setInterval(() => {
        i = (i + 1) % genMessages.length;
        el.style.opacity = '0';
        setTimeout(() => {
            el.textContent = genMessages[i];
            el.style.opacity = '1';
        }, 300);
    }, 4500);
}

// ============ POLLING СТАТУСА ПЕСНИ ============
async function pollSongStatus() {
    songPollAttempts++;

    try {
        const r = await fetch(`/api/public-generate/song-status?token=${encodeURIComponent(orderToken)}`);
        const d = await r.json();

        if (!r.ok) throw new Error(d.error || 'Ошибка');

        if (d.status === 'completed' && d.file_path) {
            showSong(d);
            return;
        }

        if (d.status === 'failed') {
            showError(
                'Ошибка генерации',
                'К сожалению, не удалось сгенерировать песню. Мы вернули 1 песню на твой баланс — можешь попробовать ещё раз.',
                true
            );
            return;
        }

        if (songPollAttempts >= maxSongPolls) {
            // Таймаут — но всё равно может быть готово, показываем "почти готово"
            showError('Генерация затянулась', 'Мы продолжаем работать над твоей песней. Как только будет готова — отправим на {{ $order->contact }}.');
            return;
        }

        setTimeout(pollSongStatus, 5000);

    } catch (e) {
        console.error(e);
        if (songPollAttempts < maxSongPolls) {
            setTimeout(pollSongStatus, 5000);
        }
    }
}

// ============ ПОКАЗ ГОТОВОЙ ПЕСНИ ============
function showSong(data) {
    if (genMessageInterval) clearInterval(genMessageInterval);

    // Добьём прогресс до 100% (только если элементы есть)
    const fill = document.getElementById('gen-progress-fill');
    const txt = document.getElementById('gen-progress-text');
    if (fill) fill.style.width = '100%';
    if (txt) txt.textContent = '100%';

    setTimeout(() => {
        // Переключение фаз
        const genPhase = document.getElementById('phase-generation');
        const donePhase = document.getElementById('phase-done');
        if (genPhase) genPhase.style.display = 'none';
        if (donePhase) donePhase.style.display = 'block';

        // Заголовок
        const titleEl = document.getElementById('song-title-display');
        if (titleEl && data.title) titleEl.textContent = data.title;

        // Обложка
        const coverEl = document.getElementById('song-cover');
        if (coverEl && data.cover_url) {
            coverEl.innerHTML = `<img src="${data.cover_url}" alt="cover">`;
        }

        // Плеер 1
        const player1 = document.getElementById('player-1');
        if (player1 && data.file_path) player1.src = data.file_path;

        const dlBtn1 = document.getElementById('btn-download-1');
        if (dlBtn1 && data.file_path) dlBtn1.href = data.file_path;

        // Плеер 2 (если есть)
        if (data.file_path_2) {
            const v2wrap = document.getElementById('version-2-wrap');
            const player2 = document.getElementById('player-2');
            if (v2wrap) v2wrap.style.display = 'block';
            if (player2) player2.src = data.file_path_2;

            const dlBtn2 = document.getElementById('btn-download-2');
            if (dlBtn2) dlBtn2.href = data.file_path_2;
        }

        // Скрыть meta (длительности нет в схеме)
        const metaEl = document.getElementById('song-meta');
        if (metaEl) metaEl.style.display = 'none';

        try { confetti(); } catch (e) {}
        try { pgsReachGoal('pesnya-gotova'); } catch (e) {}

        // Загружаем доступы
        loadCredentials();
    }, 800);
}

// ============ ОШИБКА ============
function showError(title, msg, canRetry = false) {
    if (genMessageInterval) clearInterval(genMessageInterval);
    document.getElementById('phase-payment').style.display = 'none';
    document.getElementById('phase-generation').style.display = 'none';
    document.getElementById('phase-done').style.display = 'none';
    document.getElementById('phase-error').style.display = 'block';
    if (title) document.getElementById('error-title').textContent = title;
    if (msg) document.getElementById('error-message').innerHTML = msg;

    const retryBtn = document.getElementById('btn-retry-generation');
    if (retryBtn) retryBtn.style.display = canRetry ? 'inline-block' : 'none';
}

// ============ ПОВТОР ГЕНЕРАЦИИ ============
async function retryGeneration() {
    const retryBtn = document.getElementById('btn-retry-generation');
    if (retryBtn) { retryBtn.disabled = true; retryBtn.textContent = 'Запускаем…'; }

    try {
        const r = await fetch('/api/public-generate/retry', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({ token: orderToken }),
        });
        const d = await r.json();

        if (!r.ok || !d.success) {
            throw new Error(d.error || 'Не удалось перезапустить генерацию');
        }

        // Сброс состояния и возврат к фазе генерации
        songPollAttempts = 0;
        document.getElementById('phase-error').style.display = 'none';
        document.getElementById('phase-generation').style.display = 'block';
        generationStartTime = Date.now();
        startProgressAnimation();
        startMessagesRotation();
        pollSongStatus();
    } catch (e) {
        showError('Не удалось повторить', e.message, true);
    } finally {
        if (retryBtn) { retryBtn.disabled = false; retryBtn.textContent = '🔄 Повторить генерацию'; }
    }
}

// ============ SHARE ============
function pgsShare() {
    const url = window.location.origin + '/create-song';
    if (navigator.share) {
        navigator.share({
            title: 'Моя песня на НА РЕПИТЕ',
            text: 'Сделал себе песню с помощью ИИ — зацени!',
            url: url,
        });
    } else {
        navigator.clipboard.writeText(url);
        alert('Ссылка скопирована!');
    }
}

// ============ ЛЁГКИЕ КОНФЕТТИ ============
function confetti() {
    const colors = ['#a855f7', '#ec4899', '#fbbf24', '#f59e0b', '#10b981'];
    for (let i = 0; i < 40; i++) {
        const p = document.createElement('div');
        p.style.cssText = `
            position: fixed;
            left: ${Math.random() * 100}vw;
            top: -20px;
            width: 10px; height: 10px;
            background: ${colors[Math.floor(Math.random() * colors.length)]};
            border-radius: ${Math.random() > 0.5 ? '50%' : '0'};
            pointer-events: none;
            z-index: 9999;
            animation: confFall ${2 + Math.random() * 2}s linear forwards;
        `;
        document.body.appendChild(p);
        setTimeout(() => p.remove(), 4000);
    }
}
const style = document.createElement('style');
style.textContent = `@keyframes confFall { to { transform: translateY(110vh) rotate(720deg); opacity: 0; } }`;
document.head.appendChild(style);

// ============ ЗАГРУЗКА УЧЁТНЫХ ДАННЫХ ============
async function loadCredentials() {
    const cacheKey = `guest_creds:${orderToken}`;
    const block = document.getElementById('credentials-block');
    const existingBlock = document.getElementById('existing-user-block');

    // 1. Пробуем взять из sessionStorage (на случай перезагрузки)
    try {
        const cached = sessionStorage.getItem(cacheKey);
        if (cached) {
            const d = JSON.parse(cached);
            renderCredentials(d);
            return;
        }
    } catch (e) { /* ignore */ }

    // 2. Запрашиваем с сервера
    try {
        const r = await fetch(`/api/public-generate/credentials?token=${encodeURIComponent(orderToken)}`);
        const d = await r.json();

        if (d.has_credentials) {
            // Кешируем в sessionStorage
            try { sessionStorage.setItem(cacheKey, JSON.stringify(d)); } catch (e) {}
            renderCredentials(d);
        } else {
            if (existingBlock) existingBlock.style.display = 'block';
        }
    } catch (e) {
        console.error('loadCredentials error', e);
        if (existingBlock) existingBlock.style.display = 'block';
    }
}

function renderCredentials(d) {
    const loginEl = document.getElementById('cred-login');
    const pwEl = document.getElementById('cred-password');
    const block = document.getElementById('credentials-block');
    if (loginEl) loginEl.textContent = d.login || '—';
    if (pwEl) pwEl.textContent = d.password || '—';
    if (block) block.style.display = 'block';
}

// ============ КОПИРОВАТЬ В БУФЕР ============
function pgsCopy(elementId, btn) {
    const text = document.getElementById(elementId).textContent;
    navigator.clipboard.writeText(text).then(() => {
        const original = btn.textContent;
        btn.textContent = '✓ Скопировано';
        btn.classList.add('copied');
        setTimeout(() => {
            btn.textContent = original;
            btn.classList.remove('copied');
        }, 2000);
    });
}
// ============ СТАРТ ============
@if($order->isPaid())
    startGeneration();
@else
    setTimeout(pollPayment, 1000);
@endif
</script>
@endpush