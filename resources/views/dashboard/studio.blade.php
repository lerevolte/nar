@extends('layouts.app')
@section('title', 'Студия — На Репите')

@php
    $trackOpsAllowedIds = config('services.track_ops.allowed_user_ids', []);
    $trackOpsAllowed = empty($trackOpsAllowedIds)
        || in_array('*', $trackOpsAllowedIds, true)
        || in_array((string) ($authUser->user_id ?? ''), $trackOpsAllowedIds, true);
@endphp

@push('styles')
<style>
    .studio-card { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius-lg); padding:20px; margin-bottom:16px; box-shadow:var(--shadow-xs); }
    .studio-card h3 { font-size:16px; font-weight:700; margin-bottom:6px; }
    .studio-hint { font-size:13px; color:var(--text-secondary); margin-bottom:16px; line-height:1.5; }
    .studio-field { margin-bottom:14px; }
    .studio-field label { display:block; font-size:13px; font-weight:600; margin-bottom:6px; }
    .studio-input, .studio-textarea, .studio-select { width:100%; padding:11px; border:1.5px solid var(--border); border-radius:var(--radius-md); font-size:14px; font-family:inherit; background:var(--bg-input); color:var(--text-primary); }
    .studio-textarea { resize:vertical; min-height:70px; }
    .studio-input:focus, .studio-textarea:focus, .studio-select:focus { outline:none; border-color:var(--accent); }
    .op-tabs { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:18px; }
    .op-tab { padding:9px 14px; border:1.5px solid var(--border); border-radius:var(--radius-full); background:var(--bg-card); font-size:13px; font-weight:600; cursor:pointer; transition:all var(--duration) var(--ease); }
    .op-tab.active { border-color:var(--accent); background:var(--accent); color:white; }
    .op-form { display:none; }
    .op-form.active { display:block; }
    .checkbox-row { display:flex; align-items:center; gap:8px; font-size:14px; margin-bottom:14px; }
    .studio-submit { width:100%; padding:13px; border:none; background:var(--accent); color:white; border-radius:var(--radius-md); font-size:15px; font-weight:700; cursor:pointer; }
    .studio-submit:hover { background:var(--accent-hover); }
    .studio-submit:disabled { background:var(--text-tertiary); cursor:not-allowed; }
    .variant-row { display:flex; gap:8px; margin-top:10px; }
    .variant-pick { flex:1; padding:9px; border:1.5px solid var(--border); border-radius:var(--radius-md); background:var(--bg-card); font-size:13px; font-weight:600; cursor:pointer; text-align:center; }
    .variant-pick.active { border-color:var(--accent); background:var(--accent); color:white; }
    /* Searchable combobox */
    .combo { position:relative; }
    .combo-list { position:absolute; top:calc(100% + 4px); left:0; right:0; background:var(--bg-card); border:1.5px solid var(--border); border-radius:var(--radius-md); max-height:260px; overflow-y:auto; z-index:60; display:none; box-shadow:var(--shadow-md); }
    .combo-list.open { display:block; }
    .combo-item { padding:11px 14px; font-size:14px; cursor:pointer; border-bottom:1px solid var(--border); }
    .combo-item:last-child { border-bottom:none; }
    .combo-item:hover { background:var(--accent-soft); }
    .combo-item .combo-date { font-size:12px; color:var(--text-tertiary); margin-top:2px; }
    .combo-empty { padding:12px 14px; font-size:13px; color:var(--text-tertiary); }
    .translate-row { display:flex; gap:8px; margin-top:8px; }
    .translate-row select { flex:1; }
    .translate-btn { padding:10px 16px; border:1.5px solid var(--border); border-radius:var(--radius-md); background:var(--bg-card); font-size:13px; font-weight:600; cursor:pointer; white-space:nowrap; }
    .translate-btn:hover { border-color:var(--accent); color:var(--accent); }
    .translate-btn:disabled { opacity:0.6; cursor:wait; }
    .studio-error { display:none; background:var(--danger-soft); color:var(--danger); border:1px solid rgba(225,29,72,0.2); border-radius:var(--radius-md); padding:12px 14px; font-size:13px; margin-bottom:14px; line-height:1.5; }
    .studio-error.show { display:block; }
</style>
@endpush

@section('content')
<a href="{{ route('songs.index') }}" style="display:inline-flex; align-items:center; gap:6px; color:var(--text-tertiary); margin-bottom:16px; font-size:14px;">← Назад к трекам</a>

@if(!$trackOpsAllowed)
    <div class="studio-card" style="text-align:center;">
        <div style="font-size:40px;margin-bottom:8px;">🚧</div>
        <h3>Скоро будет доступно</h3>
        <p class="studio-hint" style="margin-bottom:0;">Эта функция пока в тестировании.</p>
    </div>
@else
<h2 style="font-size:20px;font-weight:700;margin-bottom:6px;">Студия</h2>
<p class="studio-hint">Обработка ваших треков: кавер, продление, минусовка, новый вокал и мэшап. Любая операция спишет 1 песню с баланса.</p>

<div class="studio-error" id="studioError"></div>

{{-- Track picker --}}
<div class="studio-card">
    <h3>Ваш трек</h3>
    <p class="studio-hint">Выберите трек, который хотите обработать. (Для «Ремейка по тексту» трек не нужен — достаточно вставить текст.)</p>
    <div class="studio-field">
        <div class="combo">
            <input type="text" class="studio-input" id="trackSearch" placeholder="Поиск по названию..." autocomplete="off">
            <div class="combo-list" id="trackList"></div>
        </div>
    </div>
    <div class="variant-row" id="variantRow" style="display:none;">
        <button class="variant-pick active" data-v="1" onclick="pickVariant(1)">Вариант 1</button>
        <button class="variant-pick" data-v="2" id="variantBtn2" onclick="pickVariant(2)">Вариант 2</button>
    </div>
</div>

{{-- Operations --}}
<div class="studio-card">
    <h3>Что сделать</h3>
    <div class="op-tabs">
        <button class="op-tab active" data-op="upload_cover" onclick="selectOp('upload_cover')">Кавер</button>
        <button class="op-tab" data-op="upload_extend" onclick="selectOp('upload_extend')">Продлить</button>
        <button class="op-tab" data-op="add_instrumental" onclick="selectOp('add_instrumental')">Сделать минус</button>
        <button class="op-tab" data-op="add_vocals" onclick="selectOp('add_vocals')">Добавить вокал</button>
        <button class="op-tab" data-op="mashup" onclick="selectOp('mashup')">Мэшап</button>
    </div>

    {{-- Cover --}}
    <div class="op-form active" id="form-upload_cover">
        <p class="studio-hint">Перепоём выбранный трек в новом стиле — новая аранжировка и вокал, тот же текст. Можно указать стиль артиста (например, «в стиле Eminem») — преобразуем в музыкальное описание. Для другого языка — переведите текст кнопкой.</p>
        <div class="studio-field"><label>Название</label><input type="text" class="studio-input" id="cover-title" maxlength="100" placeholder="Название трека"></div>
        <div class="studio-field"><label>Новый стиль</label><input type="text" class="studio-input" id="cover-style" maxlength="200" placeholder="например, рок-баллада, акустика, в стиле Eminem"></div>
        <div class="studio-field">
            <label>Текст кавера (что будет спето)</label>
            <textarea class="studio-textarea" id="cover-prompt" maxlength="5000" placeholder="При выборе трека текст подставится автоматически. Можно вставить текст любой песни для «Ремейка по тексту»." style="min-height:120px;"></textarea>
            <div class="translate-row">
                <select class="studio-select" id="cover-lang">
                    <option value="en">Перевести на английский</option>
                    <option value="ru">Перевести на русский</option>
                    <option value="de">Перевести на немецкий</option>
                    <option value="es">Перевести на испанский</option>
                    <option value="fr">Перевести на французский</option>
                    <option value="it">Перевести на итальянский</option>
                </select>
                <button class="translate-btn" id="cover-translate-btn" onclick="translateCoverLyrics()">Перевести</button>
            </div>
        </div>
        <label class="checkbox-row"><input type="checkbox" id="cover-instr"> Без вокала (инструментал)</label>
        <button class="studio-submit" onclick="submitOp('upload_cover')">Создать кавер по аудио · 1 песня</button>

        <div style="margin:14px 0 6px;padding-top:14px;border-top:1px solid var(--border);">
            <p class="studio-hint" style="font-size:12px;margin-bottom:10px;">Известные песни (из официальных каталогов) нельзя обработать напрямую — для них можно сделать только ремейк по тексту: слегка переработаем слова (смысл сохранится) и создадим новую версию в вашем стиле. Мелодия будет новая.</p>
            <button class="studio-submit" style="background:var(--bg-input);color:var(--text-primary);border:1.5px solid var(--border);" onclick="submitRemake(this)">Ремейк по тексту · 1 песня</button>
        </div>
    </div>

    {{-- Extend --}}
    <div class="op-form" id="form-upload_extend">
        <p class="studio-hint">Допишем продолжение к выбранному треку.</p>
        <div class="studio-field"><label>Название</label><input type="text" class="studio-input" id="ext-title" maxlength="100"></div>
        <div class="studio-field"><label>Стиль</label><input type="text" class="studio-input" id="ext-style" maxlength="200" placeholder="например, pop"></div>
        <div class="studio-field"><label>С какой секунды продолжить (необязательно)</label><input type="number" class="studio-input" id="ext-continue" min="0" step="1" placeholder="с конца, если пусто"></div>
        <div class="studio-field"><label>Что добавить (необязательно)</label><textarea class="studio-textarea" id="ext-prompt" maxlength="5000"></textarea></div>
        <button class="studio-submit" onclick="submitOp('upload_extend')">Продлить · 1 песня</button>
    </div>

    {{-- Add instrumental --}}
    <div class="op-form" id="form-add_instrumental">
        <p class="studio-hint">Создадим инструментальную версию — минусовку. Стиль по желанию (если пусто — возьмём жанр трека).</p>
        <div class="studio-field"><label>Название (необязательно)</label><input type="text" class="studio-input" id="instr-title" maxlength="100"></div>
        <div class="studio-field"><label>Стиль / инструменты (необязательно)</label><input type="text" class="studio-input" id="instr-tags" maxlength="200" placeholder="например, piano, ambient"></div>
        <button class="studio-submit" onclick="submitOp('add_instrumental')">Сделать минус · 1 песня</button>
    </div>

    {{-- Add vocals --}}
    <div class="op-form" id="form-add_vocals">
        <p class="studio-hint">Добавим вокал к треку. Лучше всего — к минусовке (стему), если она уже сделана.</p>
        <label class="checkbox-row" id="vocStemRow"><input type="checkbox" id="voc-use-stem" checked> Использовать минусовку трека (стем)</label>
        <div class="studio-field"><label>Название</label><input type="text" class="studio-input" id="voc-title" maxlength="100"></div>
        <div class="studio-field"><label>Стиль</label><input type="text" class="studio-input" id="voc-style" maxlength="200" placeholder="например, pop, soul"></div>
        <div class="studio-field"><label>Текст / описание вокала</label><textarea class="studio-textarea" id="voc-prompt" maxlength="5000"></textarea></div>
        <button class="studio-submit" onclick="submitOp('add_vocals')">Добавить вокал · 1 песня</button>
    </div>

    {{-- Mashup --}}
    <div class="op-form" id="form-mashup">
        <p class="studio-hint">Смешаем два ваших трека в один. Первый — выбранный выше, второй — здесь.</p>
        <div class="studio-field">
            <label>Второй трек (поиск по названию):</label>
            <div class="combo">
                <input type="text" class="studio-input" id="mashupSearch" placeholder="Поиск по названию..." autocomplete="off">
                <div class="combo-list" id="mashupList"></div>
            </div>
        </div>
        <div class="studio-field"><label>Название (необязательно)</label><input type="text" class="studio-input" id="mashup-title" maxlength="100"></div>
        <div class="studio-field"><label>Стиль (необязательно)</label><input type="text" class="studio-input" id="mashup-style" maxlength="200" placeholder="например, lo-fi, dance"></div>
        <button class="studio-submit" onclick="submitOp('mashup')">Смешать · 1 песня</button>
    </div>
</div>
@endif
@endsection

@push('scripts')
@if($trackOpsAllowed)
<script>
    const CSRF = '{{ csrf_token() }}';
    let myTracks = [];
    let pickedSongId = null;
    let pickedVariant = 1;
    let mashupSecondId = null;
    let currentOp = 'upload_cover';

    const errorEl = document.getElementById('studioError');
    function showError(msg) { errorEl.textContent = '⚠ ' + msg; errorEl.classList.add('show'); errorEl.scrollIntoView({behavior:'smooth', block:'center'}); }
    function clearError() { errorEl.classList.remove('show'); }

    // ===== TRACKS =====
    let tracksPromise = null;
    function loadTracks() {
        if (!tracksPromise) tracksPromise = (async () => {
            try {
                const r = await fetch('/api/songs', { headers:{'X-CSRF-TOKEN': CSRF}, credentials:'same-origin' });
                const d = await r.json();
                myTracks = (d.songs || []).filter(s => s.audio_url_1);
            } catch(e) { myTracks = []; }
        })();
        return tracksPromise;
    }

    function esc(s) { return (s || 'Без названия').replace(/&/g,'&amp;').replace(/</g,'&lt;'); }

    function makeCombo(inputId, listId, getItems, onPick) {
        const input = document.getElementById(inputId);
        const list = document.getElementById(listId);
        function render(query) {
            const q = (query || '').toLowerCase().trim();
            const items = getItems().filter(s => !q || (s.title || 'без названия').toLowerCase().includes(q));
            if (!items.length) {
                list.innerHTML = '<div class="combo-empty">Ничего не найдено</div>';
            } else {
                list.innerHTML = items.slice(0, 50).map(s =>
                    `<div class="combo-item" data-id="${s.id}"><div>${esc(s.title)}</div><div class="combo-date">${s.created_at || ''}</div></div>`
                ).join('');
                list.querySelectorAll('.combo-item').forEach(el => {
                    el.addEventListener('mousedown', e => {
                        e.preventDefault();
                        const id = parseInt(el.dataset.id);
                        const item = getItems().find(s => s.id === id);
                        input.value = item ? (item.title || 'Без названия') : '';
                        list.classList.remove('open');
                        onPick(item || null);
                    });
                });
            }
            list.classList.add('open');
        }
        input.addEventListener('focus', async () => { await loadTracks(); render(input.value); });
        input.addEventListener('input', async () => { await loadTracks(); onPick(null); render(input.value); });
        input.addEventListener('blur', () => setTimeout(() => list.classList.remove('open'), 150));
        return { render, input };
    }

    const trackCombo = makeCombo('trackSearch', 'trackList',
        () => myTracks,
        (item) => { pickedSongId = item ? item.id : null; onTrackPicked(item); });

    const mashupCombo = makeCombo('mashupSearch', 'mashupList',
        () => myTracks.filter(s => s.id !== pickedSongId),
        (item) => { mashupSecondId = item ? item.id : null; });

    function onTrackPicked(item) {
        pickedVariant = 1;
        const row = document.getElementById('variantRow');
        if (item) {
            row.style.display = '';
            document.getElementById('variantBtn2').style.display = item.audio_url_2 ? '' : 'none';
            document.querySelectorAll('.variant-pick').forEach(b => b.classList.toggle('active', b.dataset.v === '1'));
            prefillCoverLyrics(item);
        } else {
            row.style.display = 'none';
        }
    }

    function prefillCoverLyrics(item) {
        const ta = document.getElementById('cover-prompt');
        if (item && item.lyrics && (!ta.value.trim() || ta.dataset.autofilled === '1')) {
            ta.value = item.lyrics;
            ta.dataset.autofilled = '1';
        }
    }
    document.getElementById('cover-prompt').addEventListener('input', function() { this.dataset.autofilled = '0'; });

    function pickVariant(v) {
        pickedVariant = v;
        document.querySelectorAll('.variant-pick').forEach(b => b.classList.toggle('active', parseInt(b.dataset.v) === v));
    }

    // ===== TRANSLATE =====
    async function translateCoverLyrics() {
        const ta = document.getElementById('cover-prompt');
        const text = ta.value.trim();
        if (!text) { showError('Нет текста для перевода'); return; }
        const btn = document.getElementById('cover-translate-btn');
        const orig = btn.textContent; btn.disabled = true; btn.textContent = '⏳...';
        try {
            const r = await fetch('/api/generate/translate', {
                method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN': CSRF},
                credentials:'same-origin',
                body: JSON.stringify({ lyrics: text, target_language: document.getElementById('cover-lang').value })
            });
            const d = await r.json();
            if (r.ok && d.success) { ta.value = d.lyrics; ta.dataset.autofilled = '0'; clearError(); }
            else showError(d.error || 'Ошибка перевода');
        } catch(e) { showError(e.message); }
        finally { btn.disabled = false; btn.textContent = orig; }
    }

    async function rephraseLyrics(text) {
        const r = await fetch('/api/track-ops/rephrase', {
            method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN': CSRF},
            credentials:'same-origin', body: JSON.stringify({ lyrics: text })
        });
        const d = await r.json();
        if (r.ok && d.success) return d.lyrics;
        throw new Error(d.error || 'Не удалось переработать текст');
    }

    // ===== OPS =====
    function selectOp(op) {
        currentOp = op;
        clearError();
        document.querySelectorAll('.op-tab').forEach(t => t.classList.toggle('active', t.dataset.op === op));
        document.querySelectorAll('.op-form').forEach(f => f.classList.toggle('active', f.id === 'form-' + op));
    }

    function val(id) { const el = document.getElementById(id); return el ? el.value.trim() : ''; }

    async function submitOp(op) {
        clearError();
        if (!pickedSongId) {
            showError(op === 'upload_cover'
                ? 'Выберите трек для кавера по аудио — либо используйте «Ремейк по тексту» ниже.'
                : 'Сначала выберите трек выше.');
            return;
        }
        let url, body = { song_id: pickedSongId, variant: pickedVariant };

        if (op === 'mashup') {
            if (!mashupSecondId) { showError('Выберите второй трек для мэшапа'); return; }
            body = { song_ids: [pickedSongId, mashupSecondId] };
            if (val('mashup-title')) body.title = val('mashup-title');
            if (val('mashup-style')) body.style = val('mashup-style');
            url = '/api/track-ops/mashup';
        } else if (op === 'upload_cover') {
            url = '/api/track-ops/upload-cover';
            body.title = val('cover-title'); body.style = val('cover-style');
            body.prompt = val('cover-prompt'); body.instrumental = document.getElementById('cover-instr').checked;
            if (!body.style) { showError('Укажите новый стиль'); return; }
        } else if (op === 'upload_extend') {
            url = '/api/track-ops/upload-extend';
            body.title = val('ext-title'); body.style = val('ext-style'); body.prompt = val('ext-prompt');
            const c = val('ext-continue'); if (c !== '') body.continue_at = parseFloat(c);
        } else if (op === 'add_instrumental') {
            url = '/api/track-ops/add-instrumental';
            body.title = val('instr-title'); body.tags = val('instr-tags');
        } else if (op === 'add_vocals') {
            url = '/api/track-ops/add-vocals';
            body.title = val('voc-title'); body.style = val('voc-style'); body.prompt = val('voc-prompt');
            if (document.getElementById('voc-use-stem').checked) body.source = 'instrumental';
            if (!body.title || !body.style || !body.prompt) { showError('Заполните название, стиль и текст вокала'); return; }
        }

        const btn = document.querySelector('#form-' + op + ' .studio-submit');
        const orig = btn.textContent; btn.disabled = true; btn.textContent = '⏳ Запускаю...';
        try {
            const r = await fetch(url, {
                method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN': CSRF},
                credentials:'same-origin', body: JSON.stringify(body)
            });
            const d = await r.json();
            if (r.ok && d.success) { window.location.href = '/songs/' + d.song_id; return; }

            // Каталожное совпадение (маловероятно для своих треков) — предложить ремейк
            if (d.catalog_match && op === 'upload_cover' && val('cover-prompt')) {
                btn.disabled = false; btn.textContent = orig;
                if (confirm((d.error || 'Известная песня') + '\n\nСделать ремейк по тексту? Спишется 1 песня.')) {
                    btn.disabled = true; await remakeFromText(btn, orig);
                }
                return;
            }

            showError(d.error || 'Не удалось выполнить операцию'); btn.disabled = false; btn.textContent = orig;
        } catch(e) { showError(e.message); btn.disabled = false; btn.textContent = orig; }
    }

    // ===== REMAKE BY TEXT (без аудио) =====
    async function submitRemake(btn) {
        clearError();
        const lyricsText = val('cover-prompt');
        const style = val('cover-style');
        if (!lyricsText) { showError('Вставьте текст песни в поле «Текст кавера»'); return; }
        if (!style) { showError('Укажите стиль (например, «в стиле Eminem»)'); return; }
        const orig = btn.textContent; btn.disabled = true;
        await remakeFromText(btn, orig);
    }

    async function remakeFromText(btn, orig) {
        try {
            let lyricsText = val('cover-prompt');
            try {
                btn.textContent = '⏳ Перерабатываю текст...';
                lyricsText = await rephraseLyrics(lyricsText);
                document.getElementById('cover-prompt').value = lyricsText;
            } catch(e) { /* не критично — продолжим с исходным */ }

            btn.textContent = '⏳ Создаю ремейк...';
            const r = await fetch('/api/generate/music', {
                method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN': CSRF},
                credentials:'same-origin',
                body: JSON.stringify({
                    title: val('cover-title') || null,
                    lyrics: lyricsText,
                    genre: val('cover-style') || 'Поп'
                })
            });
            const d = await r.json();
            if (r.ok && d.success) { window.location.href = '/songs/' + d.song_id; return; }
            if (d.need_payment) { showError('Недостаточно песен на балансе. Пополните баланс.'); }
            else showError(d.error || 'Ошибка генерации');
            btn.disabled = false; btn.textContent = orig;
        } catch(e) { showError(e.message); btn.disabled = false; btn.textContent = orig; }
    }

    // Deep-link: /studio?op=upload_cover&song=123
    (function initFromQuery() {
        const q = new URLSearchParams(window.location.search);
        const song = parseInt(q.get('song')) || null;
        const op = q.get('op');
        if (op && document.querySelector(`.op-tab[data-op="${op}"]`)) selectOp(op);
        if (song) {
            loadTracks().then(() => {
                const item = myTracks.find(s => s.id === song);
                if (item) {
                    pickedSongId = item.id;
                    trackCombo.input.value = item.title || 'Без названия';
                    onTrackPicked(item);
                }
            });
        }
    })();
</script>
@endif
@endpush
