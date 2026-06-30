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
<h2 style="font-size:20px;font-weight:700;margin-bottom:6px;">🎚 Обработать своё аудио</h2>
<p class="studio-hint">Загрузите аудиофайл и сделайте из него кавер, продление, минусовку или добавьте вокал. Любая операция спишет 1 песню с баланса.</p>

{{-- Step 1: upload --}}
<div class="studio-card">
    <h3>1. Загрузите файл</h3>
    <p class="studio-hint">MP3, WAV, M4A, OGG, FLAC. До {{ $maxMb }} МБ и {{ $maxMin }} мин.</p>
    <div class="upload-drop" id="dropZone">
        <div class="big">⬆️</div>
        <div id="dropText">Нажмите или перетащите файл сюда</div>
    </div>
    <input type="file" id="audioFile" accept="audio/*" style="display:none;">
    <div class="upload-status" id="uploadStatus"></div>
</div>

{{-- Step 2: operations --}}
<div class="studio-card" id="opsCard" style="display:none;">
    <h3>2. Выберите операцию</h3>
    <div class="op-tabs">
        <button class="op-tab active" data-op="upload_cover" onclick="selectOp('upload_cover')">🎤 Кавер</button>
        <button class="op-tab" data-op="upload_extend" onclick="selectOp('upload_extend')">➕ Продлить</button>
        <button class="op-tab" data-op="add_instrumental" onclick="selectOp('add_instrumental')">🎹 Сделать минус</button>
        <button class="op-tab" data-op="add_vocals" onclick="selectOp('add_vocals')">🎶 Добавить вокал</button>
    </div>

    {{-- Cover --}}
    <div class="op-form active" id="form-upload_cover">
        <p class="studio-hint">Перепоём ваш трек в новом стиле.</p>
        <div class="studio-field"><label>Название</label><input type="text" class="studio-input" id="cover-title" maxlength="100" placeholder="Название трека"></div>
        <div class="studio-field"><label>Стиль</label><input type="text" class="studio-input" id="cover-style" maxlength="200" placeholder="например, pop, rock"></div>
        <div class="studio-field"><label>Текст / описание (необязательно)</label><textarea class="studio-textarea" id="cover-prompt" maxlength="5000"></textarea></div>
        <label class="checkbox-row"><input type="checkbox" id="cover-instr"> Без вокала (инструментал)</label>
        <button class="studio-submit" onclick="submitOp('upload_cover')">Создать кавер · 1 песня</button>
    </div>

    {{-- Extend --}}
    <div class="op-form" id="form-upload_extend">
        <p class="studio-hint">Допишем продолжение к вашему файлу.</p>
        <div class="studio-field"><label>Название</label><input type="text" class="studio-input" id="ext-title" maxlength="100"></div>
        <div class="studio-field"><label>Стиль</label><input type="text" class="studio-input" id="ext-style" maxlength="200" placeholder="например, pop"></div>
        <div class="studio-field"><label>С какой секунды продолжить (необязательно)</label><input type="number" class="studio-input" id="ext-continue" min="0" step="1" placeholder="с конца, если пусто"></div>
        <div class="studio-field"><label>Что добавить (необязательно)</label><textarea class="studio-textarea" id="ext-prompt" maxlength="5000"></textarea></div>
        <button class="studio-submit" onclick="submitOp('upload_extend')">Продлить · 1 песня</button>
    </div>

    {{-- Add instrumental --}}
    <div class="op-form" id="form-add_instrumental">
        <p class="studio-hint">Уберём вокал и сделаем минусовку из вашего трека.</p>
        <div class="studio-field"><label>Название</label><input type="text" class="studio-input" id="instr-title" maxlength="100"></div>
        <div class="studio-field"><label>Стиль / инструменты</label><input type="text" class="studio-input" id="instr-tags" maxlength="200" placeholder="например, piano, ambient"></div>
        <div class="studio-field"><label>Исключить (необязательно)</label><input type="text" class="studio-input" id="instr-neg" maxlength="200" placeholder="например, heavy metal"></div>
        <button class="studio-submit" onclick="submitOp('add_instrumental')">Сделать минус · 1 песня</button>
    </div>

    {{-- Add vocals --}}
    <div class="op-form" id="form-add_vocals">
        <p class="studio-hint">Добавим вокал к вашему инструменталу.</p>
        <div class="studio-field"><label>Название</label><input type="text" class="studio-input" id="voc-title" maxlength="100"></div>
        <div class="studio-field"><label>Стиль</label><input type="text" class="studio-input" id="voc-style" maxlength="200" placeholder="например, pop, soul"></div>
        <div class="studio-field"><label>Текст / описание вокала</label><textarea class="studio-textarea" id="voc-prompt" maxlength="5000"></textarea></div>
        <div class="studio-field"><label>Исключить (необязательно)</label><input type="text" class="studio-input" id="voc-neg" maxlength="200"></div>
        <button class="studio-submit" onclick="submitOp('add_vocals')">Добавить вокал · 1 песня</button>
    </div>
</div>
@endif
@endsection

@push('scripts')
@if($trackOpsAllowed)
<script>
    const CSRF = '{{ csrf_token() }}';
    let uploadedUrl = null;
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
                document.getElementById('opsCard').style.display = '';
            } else {
                statusEl.style.color = 'var(--danger)';
                statusEl.textContent = '❌ ' + (d.error || 'Ошибка загрузки');
            }
        } catch(e) {
            statusEl.style.color = 'var(--danger)';
            statusEl.textContent = '❌ ' + e.message;
        }
    }

    function selectOp(op) {
        currentOp = op;
        document.querySelectorAll('.op-tab').forEach(t => t.classList.toggle('active', t.dataset.op === op));
        document.querySelectorAll('.op-form').forEach(f => f.classList.toggle('active', f.id === 'form-' + op));
    }

    function val(id) { const el = document.getElementById(id); return el ? el.value.trim() : ''; }

    async function submitOp(op) {
        if (!uploadedUrl) { alert('Сначала загрузите файл'); return; }
        let url, body = { upload_url: uploadedUrl };

        if (op === 'upload_cover') {
            url = '/api/track-ops/upload-cover';
            body.title = val('cover-title'); body.style = val('cover-style');
            body.prompt = val('cover-prompt'); body.instrumental = document.getElementById('cover-instr').checked;
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
            if (!body.title || !body.style || !body.prompt) { alert('Заполните название, стиль и текст вокала'); return; }
        }

        const btn = document.querySelector('#form-' + op + ' .studio-submit');
        const orig = btn.textContent; btn.disabled = true; btn.textContent = '⏳ Запускаю...';
        try {
            const r = await fetch(url, {
                method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN': CSRF},
                credentials:'same-origin', body: JSON.stringify(body)
            });
            const d = await r.json();
            if (r.ok && d.success) { window.location.href = '/songs/' + d.song_id; }
            else { alert('❌ ' + (d.error || 'Ошибка')); btn.disabled = false; btn.textContent = orig; }
        } catch(e) { alert('Ошибка: ' + e.message); btn.disabled = false; btn.textContent = orig; }
    }
</script>
@endif
@endpush
