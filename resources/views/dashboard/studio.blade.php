@extends('layouts.app')
@section('title', 'Студия — На Репите')

@php
    $trackOpsAllowedIds = config('services.track_ops.allowed_user_ids', []);
    $trackOpsAllowed = empty($trackOpsAllowedIds)
        || in_array('*', $trackOpsAllowedIds, true)
        || in_array((string) ($authUser->user_id ?? ''), $trackOpsAllowedIds, true);
    $maxMb = (int) config('services.track_ops.upload_max_mb', 20);
    $maxMin = intdiv((int) config('services.track_ops.upload_max_seconds', 480), 60);
@endphp

@push('styles')
<style>
    .studio-card { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius-lg); padding:20px; margin-bottom:16px; box-shadow:var(--shadow-xs); }
    .studio-card h3 { font-size:16px; font-weight:700; margin-bottom:6px; }
    .studio-hint { font-size:13px; color:var(--text-secondary); margin-bottom:16px; line-height:1.5; }
    .upload-drop { border:2px dashed var(--border); border-radius:var(--radius-md); padding:28px; text-align:center; cursor:pointer; transition:all var(--duration) var(--ease); color:var(--text-secondary); }
    .upload-drop:hover, .upload-drop.drag { border-color:var(--accent); background:var(--accent-soft); }
    .upload-drop .big { font-size:36px; margin-bottom:8px; }
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
    .upload-status { font-size:13px; margin-top:10px; }
    /* Source toggle */
    .source-tabs { display:flex; gap:8px; margin-bottom:16px; }
    .source-tab { flex:1; padding:11px; border:1.5px solid var(--border); border-radius:var(--radius-md); background:var(--bg-card); font-size:14px; font-weight:600; cursor:pointer; text-align:center; transition:all var(--duration) var(--ease); }
    .source-tab.active { border-color:var(--accent); background:var(--accent-soft); color:var(--accent); }
    .source-pane { display:none; }
    .source-pane.active { display:block; }
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
    /* Translate row */
    .translate-row { display:flex; gap:8px; margin-top:8px; }
    .translate-row select { flex:1; }
    .translate-btn { padding:10px 16px; border:1.5px solid var(--border); border-radius:var(--radius-md); background:var(--bg-card); font-size:13px; font-weight:600; cursor:pointer; white-space:nowrap; }
    .translate-btn:hover { border-color:var(--accent); color:var(--accent); }
    .translate-btn:disabled { opacity:0.6; cursor:wait; }
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
<p class="studio-hint">Кавер, продление, минусовка, новый вокал или мэшап — из вашего файла или из уже созданного трека. Любая операция спишет 1 песню с баланса.</p>

{{-- Step 1: source --}}
<div class="studio-card">
    <h3>1. Выберите источник</h3>
    <div class="source-tabs">
        <button class="source-tab active" data-src="file" onclick="selectSource('file')">Загрузить файл</button>
        <button class="source-tab" data-src="track" onclick="selectSource('track')">Из моих треков</button>
    </div>

    <div class="source-pane active" id="pane-file">
        <p class="studio-hint">MP3, WAV, M4A, OGG, FLAC. До {{ $maxMb }} МБ и {{ $maxMin }} мин.</p>
        <div class="upload-drop" id="dropZone">
            <div class="big">⬆️</div>
            <div id="dropText">Нажмите или перетащите файл сюда</div>
        </div>
        <input type="file" id="audioFile" accept="audio/*" style="display:none;">
        <div class="upload-status" id="uploadStatus"></div>
    </div>

    <div class="source-pane" id="pane-track">
        <div class="studio-field">
            <label>Трек (поиск по названию):</label>
            <div class="combo">
                <input type="text" class="studio-input" id="trackSearch" placeholder="Начните вводить название..." autocomplete="off">
                <div class="combo-list" id="trackList"></div>
            </div>
        </div>
        <div class="variant-row" id="variantRow" style="display:none;">
            <button class="variant-pick active" data-v="1" onclick="pickVariant(1)">Вариант 1</button>
            <button class="variant-pick" data-v="2" id="variantBtn2" onclick="pickVariant(2)">Вариант 2</button>
        </div>
    </div>
</div>

{{-- Step 2: operations --}}
<div class="studio-card" id="opsCard" style="display:none;">
    <h3>2. Выберите операцию</h3>
    <div class="op-tabs">
        <button class="op-tab active" data-op="upload_cover" onclick="selectOp('upload_cover')">Кавер</button>
        <button class="op-tab" data-op="upload_extend" onclick="selectOp('upload_extend')">Продлить</button>
        <button class="op-tab" data-op="add_instrumental" onclick="selectOp('add_instrumental')">Сделать минус</button>
        <button class="op-tab" data-op="add_vocals" onclick="selectOp('add_vocals')">Добавить вокал</button>
        <button class="op-tab" data-op="mashup" onclick="selectOp('mashup')">Мэшап</button>
    </div>

    {{-- Cover --}}
    <div class="op-form active" id="form-upload_cover">
        <p class="studio-hint">Перепоём выбранное аудио в новом стиле — новая аранжировка и вокал, тот же текст. Можно указать стиль артиста (например, «в стиле Eminem») — мы преобразуем его в музыкальное описание. Хотите кавер на другом языке — переведите текст кнопкой ниже.<br><br>Известные песни (из официальных каталогов) нельзя обработать напрямую — для них предложим ремейк: распознаем текст и создадим новую версию в вашем стиле.</p>
        <div class="studio-field"><label>Название</label><input type="text" class="studio-input" id="cover-title" maxlength="100" placeholder="Название трека"></div>
        <div class="studio-field"><label>Новый стиль</label><input type="text" class="studio-input" id="cover-style" maxlength="200" placeholder="например, рок-баллада, акустика, в стиле Eminem"></div>
        <div class="studio-field">
            <label>Текст кавера (что будет спето)</label>
            <textarea class="studio-textarea" id="cover-prompt" maxlength="5000" placeholder="Вставьте текст песни. При выборе своего трека текст подставится автоматически." style="min-height:120px;"></textarea>
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
        <p class="studio-hint" style="margin:-6px 0 14px;font-size:12px;">Известные песни защищены авторским правом — точный текст не пройдёт. Мы автоматически слегка переработаем формулировки (смысл и рифма сохранятся).</p>
        <label class="checkbox-row"><input type="checkbox" id="cover-instr"> Без вокала (инструментал)</label>
        <button class="studio-submit" onclick="submitOp('upload_cover')">Создать кавер · 1 песня</button>
    </div>

    {{-- Extend --}}
    <div class="op-form" id="form-upload_extend">
        <p class="studio-hint">Допишем продолжение к аудио.</p>
        <div class="studio-field"><label>Название</label><input type="text" class="studio-input" id="ext-title" maxlength="100"></div>
        <div class="studio-field"><label>Стиль</label><input type="text" class="studio-input" id="ext-style" maxlength="200" placeholder="например, pop"></div>
        <div class="studio-field"><label>С какой секунды продолжить (необязательно)</label><input type="number" class="studio-input" id="ext-continue" min="0" step="1" placeholder="с конца, если пусто"></div>
        <div class="studio-field"><label>Что добавить (необязательно)</label><textarea class="studio-textarea" id="ext-prompt" maxlength="5000"></textarea></div>
        <button class="studio-submit" onclick="submitOp('upload_extend')">Продлить · 1 песня</button>
    </div>

    {{-- Add instrumental --}}
    <div class="op-form" id="form-add_instrumental">
        <p class="studio-hint">Создадим инструментальную версию — минусовку в заданном стиле.</p>
        <div class="studio-field"><label>Название</label><input type="text" class="studio-input" id="instr-title" maxlength="100"></div>
        <div class="studio-field"><label>Стиль / инструменты</label><input type="text" class="studio-input" id="instr-tags" maxlength="200" placeholder="например, piano, ambient"></div>
        <div class="studio-field"><label>Исключить (необязательно)</label><input type="text" class="studio-input" id="instr-neg" maxlength="200" placeholder="например, heavy metal"></div>
        <button class="studio-submit" onclick="submitOp('add_instrumental')">Сделать минус · 1 песня</button>
    </div>

    {{-- Add vocals --}}
    <div class="op-form" id="form-add_vocals">
        <p class="studio-hint">Добавим вокал к инструменталу. Лучше всего работает с чистым минусом.</p>
        <label class="checkbox-row" id="vocStemRow" style="display:none;"><input type="checkbox" id="voc-use-stem" checked> Использовать минусовку трека (стем)</label>
        <div class="studio-field"><label>Название</label><input type="text" class="studio-input" id="voc-title" maxlength="100"></div>
        <div class="studio-field"><label>Стиль</label><input type="text" class="studio-input" id="voc-style" maxlength="200" placeholder="например, pop, soul"></div>
        <div class="studio-field"><label>Текст / описание вокала</label><textarea class="studio-textarea" id="voc-prompt" maxlength="5000"></textarea></div>
        <div class="studio-field"><label>Исключить (необязательно)</label><input type="text" class="studio-input" id="voc-neg" maxlength="200"></div>
        <button class="studio-submit" onclick="submitOp('add_vocals')">Добавить вокал · 1 песня</button>
    </div>

    {{-- Mashup --}}
    <div class="op-form" id="form-mashup">
        <p class="studio-hint">Смешаем два трека в один. Первый — выбранный источник, второй выберите ниже.</p>
        <div class="studio-field">
            <label>Второй трек (поиск по названию):</label>
            <div class="combo">
                <input type="text" class="studio-input" id="mashupSearch" placeholder="Начните вводить название..." autocomplete="off">
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
    let sourceMode = 'file';        // file | track
    let uploadedUrl = null;
    let myTracks = [];
    let pickedSongId = null;
    let pickedVariant = 1;
    let mashupSecondId = null;
    let currentOp = 'upload_cover';

    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('audioFile');
    const statusEl = document.getElementById('uploadStatus');

    dropZone.addEventListener('click', () => fileInput.click());
    dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('drag'); });
    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag'));
    dropZone.addEventListener('drop', e => {
        e.preventDefault(); dropZone.classList.remove('drag');
        if (e.dataTransfer.files.length) uploadFile(e.dataTransfer.files[0]);
    });
    fileInput.addEventListener('change', () => { if (fileInput.files.length) uploadFile(fileInput.files[0]); });

    async function uploadFile(file) {
        statusEl.style.color = 'var(--text-secondary)';
        statusEl.textContent = '⏳ Загружаю...';
        const fd = new FormData();
        fd.append('audio', file);
        try {
            const r = await fetch('/api/track-ops/upload', {
                method:'POST', headers:{'X-CSRF-TOKEN': CSRF}, credentials:'same-origin', body: fd
            });
            const d = await r.json();
            if (r.ok && d.success) {
                uploadedUrl = d.url;
                statusEl.style.color = 'var(--success)';
                statusEl.textContent = '✅ Файл загружен: ' + file.name;
                document.getElementById('dropText').textContent = '✅ ' + file.name + ' — нажмите, чтобы заменить';
                refreshOpsVisibility();
            } else {
                statusEl.style.color = 'var(--danger)';
                statusEl.textContent = '❌ ' + (d.error || 'Ошибка загрузки');
            }
        } catch(e) {
            statusEl.style.color = 'var(--danger)';
            statusEl.textContent = '❌ ' + e.message;
        }
    }

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

    // ===== SEARCHABLE COMBOBOX =====
    // makeCombo: input с выпадающим списком треков и поиском по названию
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
                    el.addEventListener('mousedown', e => {  // mousedown — раньше blur
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
        () => myTracks.filter(s => !(sourceMode === 'track' && s.id === pickedSongId)),
        (item) => { mashupSecondId = item ? item.id : null; });

    // ===== SOURCE =====
    function selectSource(mode) {
        sourceMode = mode;
        document.querySelectorAll('.source-tab').forEach(t => t.classList.toggle('active', t.dataset.src === mode));
        document.getElementById('pane-file').classList.toggle('active', mode === 'file');
        document.getElementById('pane-track').classList.toggle('active', mode === 'track');
        if (mode === 'track') loadTracks();
        refreshOpsVisibility();
    }

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
        refreshOpsVisibility();
    }

    // Автоподстановка текста трека в поле текста кавера
    // (перезаписываем, только если поле пустое или заполнено нами же)
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

    function sourceReady() {
        return sourceMode === 'file' ? !!uploadedUrl : !!pickedSongId;
    }

    function refreshOpsVisibility() {
        document.getElementById('opsCard').style.display = sourceReady() ? '' : 'none';
        document.getElementById('vocStemRow').style.display = (sourceMode === 'track' && pickedSongId) ? '' : 'none';
    }

    // Слегка переработать текст, чтобы обойти copyright-фильтр Suno (413)
    async function rephraseLyrics(text) {
        const r = await fetch('/api/track-ops/rephrase', {
            method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN': CSRF},
            credentials:'same-origin', body: JSON.stringify({ lyrics: text })
        });
        const d = await r.json();
        if (r.ok && d.success) return d.lyrics;
        throw new Error(d.error || 'Не удалось переработать текст');
    }

    // ===== TRANSLATE (как на /create) =====
    async function translateCoverLyrics() {
        const ta = document.getElementById('cover-prompt');
        const text = ta.value.trim();
        if (!text) { alert('Нет текста для перевода'); return; }
        const btn = document.getElementById('cover-translate-btn');
        const orig = btn.textContent; btn.disabled = true; btn.textContent = '⏳...';
        try {
            const r = await fetch('/api/generate/translate', {
                method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN': CSRF},
                credentials:'same-origin',
                body: JSON.stringify({ lyrics: text, target_language: document.getElementById('cover-lang').value })
            });
            const d = await r.json();
            if (r.ok && d.success) { ta.value = d.lyrics; ta.dataset.autofilled = '0'; }
            else alert('❌ ' + (d.error || 'Ошибка перевода'));
        } catch(e) { alert('Ошибка: ' + e.message); }
        finally { btn.disabled = false; btn.textContent = orig; }
    }

    // ===== OPS =====
    function selectOp(op) {
        currentOp = op;
        document.querySelectorAll('.op-tab').forEach(t => t.classList.toggle('active', t.dataset.op === op));
        document.querySelectorAll('.op-form').forEach(f => f.classList.toggle('active', f.id === 'form-' + op));
        if (op === 'mashup') loadTracks();
    }

    function val(id) { const el = document.getElementById(id); return el ? el.value.trim() : ''; }

    function sourceBody() {
        if (sourceMode === 'file') return { upload_url: uploadedUrl };
        return { song_id: pickedSongId, variant: pickedVariant };
    }

    async function submitOp(op) {
        if (!sourceReady()) { alert('Сначала выберите источник: файл или трек'); return; }
        let url, body;

        if (op === 'mashup') {
            if (!mashupSecondId) { alert('Выберите второй трек'); return; }
            body = { song_ids: [], upload_urls: [] };
            if (sourceMode === 'file') body.upload_urls.push(uploadedUrl); else body.song_ids.push(pickedSongId);
            body.song_ids.push(mashupSecondId);
            if (val('mashup-title')) body.title = val('mashup-title');
            if (val('mashup-style')) body.style = val('mashup-style');
            url = '/api/track-ops/mashup';
        } else {
            body = sourceBody();
            if (op === 'upload_cover') {
                url = '/api/track-ops/upload-cover';
                body.title = val('cover-title'); body.style = val('cover-style');
                body.prompt = val('cover-prompt'); body.instrumental = document.getElementById('cover-instr').checked;
                if (!body.style) { alert('Укажите новый стиль'); return; }
                if (!body.instrumental && !body.prompt && sourceMode === 'file') {
                    if (!confirm('Текст не указан — нейросеть сочинит слова сама по звучанию файла.\n\nСовет: нажмите «Распознать текст из файла», чтобы сохранить оригинальные слова.\n\nПродолжить без текста?')) return;
                }
            } else if (op === 'upload_extend') {
                url = '/api/track-ops/upload-extend';
                body.title = val('ext-title'); body.style = val('ext-style'); body.prompt = val('ext-prompt');
                const c = val('ext-continue'); if (c !== '') body.continue_at = parseFloat(c);
            } else if (op === 'add_instrumental') {
                url = '/api/track-ops/add-instrumental';
                body.title = val('instr-title'); body.tags = val('instr-tags'); body.negative_tags = val('instr-neg');
                if (!body.title || !body.tags) { alert('Заполните название и стиль'); return; }
            } else if (op === 'add_vocals') {
                url = '/api/track-ops/add-vocals';
                body.title = val('voc-title'); body.style = val('voc-style'); body.prompt = val('voc-prompt'); body.negative_tags = val('voc-neg');
                if (sourceMode === 'track' && document.getElementById('voc-use-stem').checked) body.source = 'instrumental';
                if (!body.title || !body.style || !body.prompt) { alert('Заполните название, стиль и текст вокала'); return; }
            }
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

            // Известная песня из каталога — Suno не принимает аудио,
            // предлагаем ремейк: перерабатываем текст и генерируем заново
            if (d.catalog_match && op === 'upload_cover') {
                const lyricsText = val('cover-prompt');
                if (!lyricsText) {
                    alert(d.error + '\n\nВставьте текст песни в поле «Текст кавера» и повторите.');
                } else if (confirm(d.error + '\n\nСделать ремейк сейчас? Слегка переработаем текст (чтобы прошёл проверку) и создадим новую песню в стиле «' + (val('cover-style') || 'как в оригинале') + '». Спишется 1 песня.')) {
                    btn.textContent = '⏳ Создаю ремейк...';
                    await remakeFromText(btn, orig);
                    return;
                }
                btn.disabled = false; btn.textContent = orig; return;
            }

            alert('❌ ' + (d.error || 'Ошибка')); btn.disabled = false; btn.textContent = orig;
        } catch(e) { alert('Ошибка: ' + e.message); btn.disabled = false; btn.textContent = orig; }
    }

    // Ремейк по тексту — переработка текста + обычная генерация (без исходного аудио)
    async function remakeFromText(btn, orig) {
        try {
            let lyricsText = val('cover-prompt');
            try {
                btn.textContent = '⏳ Перерабатываю текст...';
                lyricsText = await rephraseLyrics(lyricsText);
                document.getElementById('cover-prompt').value = lyricsText;
            } catch(e) { /* не критично — пробуем с исходным текстом */ }

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
            if (r.ok && d.success) { window.location.href = '/songs/' + d.song_id; }
            else { alert('❌ ' + (d.error || 'Ошибка')); btn.disabled = false; btn.textContent = orig; }
        } catch(e) { alert('Ошибка: ' + e.message); btn.disabled = false; btn.textContent = orig; }
    }

    // Прямой заход с параметрами: /studio?op=upload_cover&song=123
    (function initFromQuery() {
        const q = new URLSearchParams(window.location.search);
        const song = parseInt(q.get('song')) || null;
        const op = q.get('op');
        if (song) {
            selectSource('track');
            loadTracks().then(() => {
                const item = myTracks.find(s => s.id === song);
                if (item) {
                    pickedSongId = item.id;
                    trackCombo.input.value = item.title || 'Без названия';
                    onTrackPicked(item);
                }
            });
        }
        if (op && document.querySelector(`.op-tab[data-op="${op}"]`)) selectOp(op);
    })();
</script>
@endif
@endpush
