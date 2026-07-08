@extends('layouts.app')

@section('title', 'Рассылки — Админ')

@push('styles')
<style>
    .seg-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: 12px; margin-bottom: 28px; }
    .seg-card {
        background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-md);
        padding: 14px; display: flex; flex-direction: column; gap: 8px;
    }
    .seg-card .seg-head { display: flex; align-items: center; gap: 8px; font-weight: 700; font-size: 14px; }
    .seg-card .seg-count { font-size: 26px; font-weight: 800; line-height: 1; }
    .seg-card .seg-split { font-size: 12px; color: var(--text-secondary); }
    .seg-card .seg-desc { font-size: 12px; color: var(--text-tertiary); }
    .seg-card .seg-action { font-size: 12px; color: var(--text-secondary); border-top: 1px dashed var(--border); padding-top: 8px; }
    .seg-card .seg-action b { color: var(--text-primary); }
    .seg-card .btn-seg {
        margin-top: auto; padding: 8px; border: 1.5px solid var(--accent); color: var(--accent);
        background: transparent; border-radius: var(--radius-md); font-size: 12px; font-weight: 700; cursor: pointer;
    }
    .seg-card .btn-seg:hover { background: var(--accent); color: #fff; }
    .seg-card.blocked { opacity: 0.7; }

    .channel-options { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 8px; }
    .channel-btn {
        flex: 1; min-width: 110px; padding: 12px; border: 2px solid var(--border); border-radius: var(--radius-md);
        background: var(--bg-card); cursor: pointer; text-align: center; font-size: 13px; font-weight: 600;
        transition: all var(--duration) var(--ease); user-select: none;
    }
    .channel-btn:hover { border-color: var(--accent); }
    .channel-btn.selected { border-color: var(--accent); background: var(--accent); color: white; }
    .channel-btn.disabled { opacity: 0.45; cursor: not-allowed; }

    .segment-select { width: 100%; padding: 12px; border: 1.5px solid var(--border); border-radius: var(--radius-md); background: var(--bg-card); font-size: 14px; color: var(--text-primary); }
    .segment-count { font-size: 13px; color: var(--text-secondary); margin-top: 6px; }

    .editor-toolbar { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 6px; }
    .tbtn { padding: 6px 10px; border: 1px solid var(--border); background: var(--bg-card); border-radius: 8px; cursor: pointer; font-size: 13px; }
    .tbtn:hover { border-color: var(--accent); }
    .emoji-row { display: flex; gap: 4px; flex-wrap: wrap; }
    .emoji-row span { cursor: pointer; font-size: 18px; padding: 2px 4px; border-radius: 6px; }
    .emoji-row span:hover { background: var(--bg-input); }
    .preview-box {
        margin-top: 8px; padding: 12px; border: 1px dashed var(--border); border-radius: var(--radius-md);
        background: var(--bg-input); font-size: 14px; white-space: pre-wrap; word-break: break-word; min-height: 40px;
    }
    .preview-box:empty::before { content: 'Предпросмотр появится здесь'; color: var(--text-tertiary); }
    .field-block { margin-top: 16px; padding: 16px; background: var(--bg-input); border-radius: var(--radius-md); }
    .field-label { font-size: 12px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px; }
    .hint { font-size: 12px; color: var(--text-tertiary); margin-top: 4px; }

    .broadcast-item {
        background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-md);
        padding: 14px; margin-bottom: 8px; display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;
    }
    .broadcast-id { font-weight: 700; font-size: 14px; }
    .broadcast-meta { font-size: 12px; color: var(--text-secondary); margin-top: 2px; }
    .broadcast-stats { display: flex; gap: 12px; font-size: 12px; align-items: center; }
    .broadcast-badge { padding: 3px 10px; border-radius: var(--radius-full); font-size: 11px; font-weight: 700; }
    .badge-pending { background: var(--warning-soft); color: var(--warning); }
    .badge-running { background: var(--accent-soft); color: var(--accent); }
    .badge-completed { background: var(--success-soft); color: var(--success); }
    .badge-failed { background: var(--danger-soft); color: var(--danger); }
    .badge-paused { background: var(--surface-glass); color: var(--text-secondary); }
    .mini-btn { padding: 5px 10px; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-card); cursor: pointer; font-size: 12px; font-weight: 600; }
    .mini-btn:hover { border-color: var(--accent); }
    .test-results { font-size: 13px; margin-top: 8px; }
    .test-results div { padding: 2px 0; }
</style>
@endpush

@section('content')
<h2 style="font-size: 22px; font-weight: 800; margin-bottom: 6px;">📢 Рассылки</h2>
<p style="color: var(--text-tertiary); font-size: 13px; margin-bottom: 20px;">Сегменты «зависших», рассылки по Telegram / MAX / сайту, тест на одном пользователе.</p>

{{-- ДАШБОРД СЕГМЕНТОВ --}}
<h3 style="font-size: 16px; font-weight: 700; margin-bottom: 4px;">🔍 Сегменты пользователей</h3>
<p style="color: var(--text-tertiary); font-size: 12px; margin-bottom: 12px;">
    Непересекающиеся: каждый пользователь ровно в одном сегменте — рассылки по ним не дублируют получателей.
    «Спящих» внутри любого сегмента можно взять галочкой «только неактивные».
</p>
<div class="seg-grid" id="seg-grid">
    <div style="color: var(--text-tertiary); padding: 10px;">Загрузка сегментов…</div>
</div>

{{-- КОМПОЗЕР --}}
<div class="card broadcast-form" id="composer">
    <h3 class="card-title">✍️ Новая рассылка</h3>

    {{-- Каналы --}}
    <div class="form-group">
        <label class="form-label">Каналы отправки</label>
        <div class="channel-options">
            <div class="channel-btn selected" data-channel="telegram" onclick="toggleChannel(this)">📱 Telegram</div>
            <div class="channel-btn {{ $maxConfigured ? '' : 'disabled' }}" data-channel="max" onclick="toggleChannel(this)">🟣 MAX</div>
            <div class="channel-btn" data-channel="web" onclick="toggleChannel(this)">🌐 Сайт</div>
        </div>
        @unless($maxConfigured)
            <div class="hint">⚠️ MAX_BOT_TOKEN не настроен — канал MAX недоступен.</div>
        @endunless
    </div>

    {{-- Сегмент --}}
    <div class="form-group">
        <label class="form-label">Аудитория</label>
        <select class="segment-select" id="segment-select" onchange="countSegment()">
            <optgroup label="Непересекающиеся сегменты (без дублей)">
                @foreach($segments as $key => $meta)
                    @if(($meta['partition'] ?? false) && ($meta['sendable'] ?? true))
                        <option value="{{ $key }}">{{ $meta['emoji'] }} {{ $meta['label'] }}</option>
                    @endif
                @endforeach
            </optgroup>
            <optgroup label="Широкие (пересекаются с другими)">
                @foreach($segments as $key => $meta)
                    @if(! ($meta['partition'] ?? false) && ($meta['sendable'] ?? true))
                        <option value="{{ $key }}">{{ $meta['emoji'] }} {{ $meta['label'] }}</option>
                    @endif
                @endforeach
            </optgroup>
        </select>
        <label style="display:flex; gap:8px; align-items:center; margin-top:8px; font-size:13px; cursor:pointer;">
            <input type="checkbox" id="only-inactive" onchange="countSegment()">
            🕒 Только неактивные {{ \App\Services\BroadcastService::SLEEP_DAYS }}+ дней
        </label>
        <div class="segment-count" id="segment-count">…</div>
    </div>

    {{-- Сообщение (TG/MAX) --}}
    <div class="field-block" id="msg-block">
        <div class="field-label">💬 Текст сообщения (Telegram / MAX)</div>
        <div class="editor-toolbar">
            <button type="button" class="tbtn" onclick="wrap('tg-text','<b>','</b>')"><b>Ж</b></button>
            <button type="button" class="tbtn" onclick="wrap('tg-text','<i>','</i>')"><i>К</i></button>
            <button type="button" class="tbtn" onclick="wrap('tg-text','<u>','</u>')"><u>П</u></button>
            <button type="button" class="tbtn" onclick="wrap('tg-text','<s>','</s>')"><s>З</s></button>
            <button type="button" class="tbtn" onclick="wrapLink('tg-text')">🔗 Ссылка</button>
        </div>
        <div class="emoji-row" id="emoji-row"></div>
        <textarea class="form-textarea" id="tg-text" rows="6" oninput="renderPreview()" placeholder="Привет! 🎵&#10;&#10;<b>У нас новинка…</b>" style="margin-top:6px;"></textarea>
        <div class="hint">Поддерживается HTML: &lt;b&gt; жирный, &lt;i&gt; курсив, &lt;u&gt;, &lt;s&gt;, &lt;a href="…"&gt;ссылка&lt;/a&gt;. Переносы строк сохраняются.</div>
        <div class="field-label" style="margin-top:12px;">👁 Предпросмотр</div>
        <div class="preview-box" id="preview"></div>
    </div>

    {{-- Веб-поля --}}
    <div class="field-block" id="web-fields" style="display:none;">
        <div class="field-label">🌐 Уведомление на сайте</div>
        <input type="text" class="form-input" id="web-title" placeholder="Заголовок уведомления" style="margin-bottom:8px;">
        <textarea class="form-textarea" id="web-message" rows="3" placeholder="Текст уведомления (если пусто — возьмётся текст выше)"></textarea>
        <div class="hint">Форматирование и переносы строк сохраняются.</div>
    </div>

    {{-- Тест --}}
    <div class="field-block" style="border:1px solid var(--border);">
        <div class="field-label">🧪 Тест на одном пользователе</div>
        <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
            <input type="number" class="form-input" id="test-user-id" placeholder="user_id (напр. 288559694)" style="max-width:260px;">
            <button class="btn btn-secondary" id="test-btn" onclick="sendTest()">Отправить тест</button>
        </div>
        <div class="hint">MAX-пользователи: user_id ≥ 10000000000. Отправляет по выбранным выше каналам с текущим текстом.</div>
        <div class="test-results" id="test-results"></div>
    </div>

    <div style="display: flex; gap: 10px; margin-top: 20px;">
        <button class="btn btn-primary btn-block" id="create-btn" onclick="createBroadcast()">🚀 Запустить рассылку</button>
    </div>
    <div id="result-message" style="display:none; margin-top:12px;"></div>
</div>

{{-- История --}}
<div class="broadcast-history" style="margin-top: 28px;">
    <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 12px;">📋 История рассылок</h3>
    <div id="history-list">
        @forelse($broadcasts as $b)
            <div class="broadcast-item" data-id="{{ $b->id }}" data-status="{{ $b->status }}">
                <div class="broadcast-info">
                    <div class="broadcast-id">#{{ $b->id }}
                        <span class="broadcast-badge badge-{{ $b->status }}" data-role="badge">{{ $b->status }}</span>
                    </div>
                    <div class="broadcast-meta">{{ $b->channel }} · {{ $b->segment }} · {{ $b->created_at->format('d.m.Y H:i') }}</div>
                </div>
                <div class="broadcast-stats">
                    <span data-role="sent">✅ {{ $b->sent_count }}</span>
                    <span data-role="failed">❌ {{ $b->failed_count }}</span>
                    <span data-role="blocked">🚫 {{ $b->blocked_count }}</span>
                    <span>/ {{ $b->total_users }}</span>
                    @if(in_array($b->status, ['pending','paused']))
                        <button class="mini-btn" onclick="startBroadcast({{ $b->id }}, this)">▶ Запустить</button>
                    @elseif($b->status === 'running')
                        <button class="mini-btn" onclick="pauseBroadcast({{ $b->id }}, this)">⏸ Пауза</button>
                    @endif
                </div>
            </div>
        @empty
            <p style="color: var(--text-tertiary); text-align: center; padding: 20px;">Рассылок пока нет</p>
        @endforelse
    </div>
</div>
@endsection

@push('scripts')
<script>
    const CSRF = '{{ csrf_token() }}';
    const MAX_OK = @json($maxConfigured);
    const SEG_META = {};
    function onlyInactive() { return document.getElementById('only-inactive')?.checked || false; }

    // ---------- Каналы ----------
    function selectedChannels() {
        return [...document.querySelectorAll('.channel-btn.selected')].map(b => b.dataset.channel);
    }
    function toggleChannel(el) {
        if (el.classList.contains('disabled')) return;
        el.classList.toggle('selected');
        const ch = selectedChannels();
        document.getElementById('web-fields').style.display = ch.includes('web') ? 'block' : 'none';
        document.getElementById('msg-block').style.display = (ch.includes('telegram') || ch.includes('max')) ? 'block' : 'none';
        countSegment();
    }

    // ---------- Редактор ----------
    const EMOJIS = ['🎵','🎶','🔥','✨','🎁','💥','❤️','👋','🚀','💰','🎉','⚡','👇','✅','🎤','🥳'];
    function initEmojis() {
        document.getElementById('emoji-row').innerHTML = EMOJIS.map(e => `<span onclick="insertAtCursor('tg-text','${e}')">${e}</span>`).join('');
    }
    function insertAtCursor(id, text) {
        const ta = document.getElementById(id);
        const s = ta.selectionStart, e = ta.selectionEnd;
        ta.value = ta.value.slice(0, s) + text + ta.value.slice(e);
        ta.selectionStart = ta.selectionEnd = s + text.length;
        ta.focus(); renderPreview();
    }
    function wrap(id, before, after) {
        const ta = document.getElementById(id);
        const s = ta.selectionStart, e = ta.selectionEnd;
        const sel = ta.value.slice(s, e) || 'текст';
        ta.value = ta.value.slice(0, s) + before + sel + after + ta.value.slice(e);
        ta.selectionStart = s + before.length; ta.selectionEnd = s + before.length + sel.length;
        ta.focus(); renderPreview();
    }
    function wrapLink(id) {
        const url = prompt('URL ссылки:', 'https://');
        if (!url) return;
        const ta = document.getElementById(id);
        const s = ta.selectionStart, e = ta.selectionEnd;
        const sel = ta.value.slice(s, e) || 'ссылка';
        const html = `<a href="${url}">${sel}</a>`;
        ta.value = ta.value.slice(0, s) + html + ta.value.slice(e);
        ta.focus(); renderPreview();
    }
    function renderPreview() {
        // Показываем как в мессенджере: разрешённые теги + переносы строк
        const raw = document.getElementById('tg-text').value;
        const allowed = raw.replace(/<(?!\/?(b|i|u|s|a)(\s[^>]*)?>)/gi, '&lt;');
        document.getElementById('preview').innerHTML = allowed;
    }

    // ---------- Подсчёт сегмента ----------
    async function countSegment() {
        const seg = document.getElementById('segment-select').value;
        const el = document.getElementById('segment-count');
        el.textContent = 'Считаю…';
        try {
            const r = await fetch('/api/admin/broadcast/count-segment', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                credentials: 'same-origin',
                body: JSON.stringify({ segment: seg, channels: selectedChannels(), only_inactive: onlyInactive() }),
            });
            const d = await r.json();
            if (!r.ok) throw new Error(d.error || 'Ошибка');
            const b = d.breakdown || {};
            el.textContent = `👥 ${d.count} получателей по выбранным каналам · всего в сегменте ${b.total} (TG ${b.tg}, MAX ${b.max_reachable}/${b.max})`;
        } catch (e) { el.textContent = 'Ошибка подсчёта: ' + e.message; }
    }

    // ---------- Дашборд сегментов ----------
    async function loadSegments() {
        try {
            const r = await fetch('/api/admin/broadcast/segments', { credentials: 'same-origin' });
            const d = await r.json();
            const grid = document.getElementById('seg-grid');
            d.segments.forEach(s => SEG_META[s.key] = s);
            grid.innerHTML = d.segments.filter(s => s.partition).map(s => {
                const b = s.breakdown;
                const sendable = s.sendable !== false;
                const buttons = sendable ? `<div style="display:flex; gap:6px;">
                        <button class="btn-seg" onclick="pickSegment('${s.key}')">✍️ Рассылка</button>
                        ${s.template ? `<button class="btn-seg" onclick="useTemplate('${s.key}')">📋 Шаблон</button>` : ''}
                    </div>` : '';
                return `<div class="seg-card ${sendable ? '' : 'blocked'}">
                    <div class="seg-head">${s.emoji} ${s.label}</div>
                    <div class="seg-count">${b.total}</div>
                    <div class="seg-split">TG ${b.tg} · MAX ${b.max_reachable}/${b.max} · 💤 неактивных ${b.inactive}</div>
                    <div class="seg-desc">${s.desc}</div>
                    <div class="seg-action"><b>Что делать:</b> ${s.action}</div>
                    ${buttons}
                </div>`;
            }).join('');
        } catch (e) {
            document.getElementById('seg-grid').innerHTML = '<div style="color:var(--danger)">Ошибка загрузки сегментов</div>';
        }
    }
    function pickSegment(key) {
        document.getElementById('segment-select').value = key;
        countSegment();
        document.getElementById('composer').scrollIntoView({ behavior: 'smooth' });
    }
    function useTemplate(key) {
        const m = SEG_META[key];
        if (!m) return;
        pickSegment(key);
        // Каналы под шаблон не трогаем — оставляем выбор админа
        if (m.template) {
            const ta = document.getElementById('tg-text');
            if (!ta.value.trim() || confirm('Заменить текущий текст шаблоном?')) {
                ta.value = m.template;
                renderPreview();
                const wt = document.getElementById('web-title');
                if (wt && !wt.value) wt.value = m.label;
                if (m.template.includes('[ПРОМОКОД]')) {
                    alert('В шаблоне есть [ПРОМОКОД] — замени на свой созданный промокод.');
                }
            }
        }
    }

    // ---------- Тест ----------
    async function sendTest() {
        const uid = document.getElementById('test-user-id').value.trim();
        const box = document.getElementById('test-results');
        const btn = document.getElementById('test-btn');
        if (!uid) { box.innerHTML = '<div style="color:var(--danger)">Укажи user_id</div>'; return; }
        btn.disabled = true; btn.textContent = 'Отправляю…'; box.innerHTML = '';
        try {
            const r = await fetch('/api/admin/broadcast/test', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                credentials: 'same-origin',
                body: JSON.stringify({
                    user_id: parseInt(uid, 10),
                    channels: selectedChannels(),
                    text_content: document.getElementById('tg-text').value || null,
                    web_title: document.getElementById('web-title')?.value || null,
                    web_message: document.getElementById('web-message')?.value || null,
                }),
            });
            const d = await r.json();
            if (!r.ok) throw new Error(d.error || 'Ошибка');
            box.innerHTML = Object.entries(d.results).map(([k, v]) =>
                `<div>${v === 'ok' ? '✅' : (String(v).startsWith('skip') ? '➖' : '❌')} <b>${k}</b>: ${v}</div>`).join('');
        } catch (e) {
            box.innerHTML = '<div style="color:var(--danger)">❌ ' + e.message + '</div>';
        } finally { btn.disabled = false; btn.textContent = 'Отправить тест'; }
    }

    // ---------- Создание + запуск ----------
    async function createBroadcast() {
        const btn = document.getElementById('create-btn');
        const resultEl = document.getElementById('result-message');
        const ch = selectedChannels();
        if (ch.length === 0) { showResult(false, 'Выбери хотя бы один канал'); return; }
        if (!confirm('Запустить рассылку по сегменту на выбранные каналы?')) return;
        btn.disabled = true; btn.textContent = 'Запускаю…'; resultEl.style.display = 'none';
        try {
            const r = await fetch('/api/admin/broadcast/create', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                credentials: 'same-origin',
                body: JSON.stringify({
                    segment: document.getElementById('segment-select').value,
                    channels: ch,
                    only_inactive: onlyInactive(),
                    text_content: document.getElementById('tg-text').value || null,
                    web_title: document.getElementById('web-title')?.value || null,
                    web_message: document.getElementById('web-message')?.value || null,
                    start: true,
                }),
            });
            const d = await r.json();
            if (!r.ok) throw new Error(d.error || 'Ошибка');
            showResult(true, d.message);
            setTimeout(() => window.location.reload(), 1800);
        } catch (e) {
            showResult(false, e.message);
            btn.disabled = false; btn.textContent = '🚀 Запустить рассылку';
        }
    }
    function showResult(ok, msg) {
        const el = document.getElementById('result-message');
        el.className = 'alert ' + (ok ? 'alert-success' : 'alert-error');
        el.textContent = (ok ? '✅ ' : '❌ ') + msg;
        el.style.display = 'block';
    }

    async function startBroadcast(id, btn) {
        btn.disabled = true; btn.textContent = '…';
        try {
            const r = await fetch(`/api/admin/broadcast/${id}/start`, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF }, credentials: 'same-origin' });
            const d = await r.json(); if (!r.ok) throw new Error(d.error);
            setTimeout(() => window.location.reload(), 800);
        } catch (e) { btn.disabled = false; btn.textContent = '▶ Запустить'; alert(e.message); }
    }
    async function pauseBroadcast(id, btn) {
        btn.disabled = true; btn.textContent = '…';
        try {
            await fetch(`/api/admin/broadcast/${id}/pause`, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF }, credentials: 'same-origin' });
            setTimeout(() => window.location.reload(), 800);
        } catch (e) { btn.disabled = false; btn.textContent = '⏸ Пауза'; }
    }

    // ---------- Live-прогресс запущенных ----------
    async function pollRunning() {
        const items = [...document.querySelectorAll('.broadcast-item[data-status="running"]')];
        for (const item of items) {
            try {
                const id = item.dataset.id;
                const r = await fetch(`/api/admin/broadcast/${id}/status`, { credentials: 'same-origin' });
                const d = await r.json();
                item.querySelector('[data-role="sent"]').textContent = '✅ ' + d.sent_count;
                item.querySelector('[data-role="failed"]').textContent = '❌ ' + d.failed_count;
                item.querySelector('[data-role="blocked"]').textContent = '🚫 ' + d.blocked_count;
                const badge = item.querySelector('[data-role="badge"]');
                badge.textContent = d.status; badge.className = 'broadcast-badge badge-' + d.status;
                if (d.status !== 'running') { item.dataset.status = d.status; setTimeout(() => window.location.reload(), 1000); }
            } catch (e) {}
        }
    }

    // init
    initEmojis();
    renderPreview();
    loadSegments();
    countSegment();
    setInterval(pollRunning, 3000);
</script>
@endpush
