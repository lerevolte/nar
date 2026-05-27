@extends('layouts.app')

@section('title', 'Мои голоса — На Репите')

@push('styles')
<style>
    .voices-container { max-width: 700px; margin: 0 auto; }

    .voice-card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        padding: 20px;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 16px;
    }
    .voice-card-icon { font-size: 36px; }
    .voice-card-info { flex: 1; min-width: 0; }
    .voice-card-name { font-size: 16px; font-weight: 700; }
    .voice-card-meta { font-size: 12px; color: var(--text-tertiary); margin-top: 4px; }

    .voice-status {
        padding: 4px 10px; border-radius: var(--radius-full);
        font-size: 11px; font-weight: 600; white-space: nowrap;
    }
    .voice-status.ready { background: var(--success-soft); color: var(--success); }
    .voice-status.processing { background: var(--accent-soft); color: var(--accent); }
    .voice-status.failed { background: var(--danger-soft); color: var(--danger); }
    .voice-status.phrase_ready { background: #fef3c7; color: #92400e; }

    .voice-delete-btn {
        background: none; border: none; color: var(--text-tertiary);
        cursor: pointer; font-size: 16px; padding: 4px;
    }
    .voice-delete-btn:hover { color: var(--danger); }

    .wizard { display: none; }
    .wizard.active { display: block; }

    .wizard-step { display: none; }
    .wizard-step.active { display: block; }

    .wizard-card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        padding: 28px;
    }

    .upload-zone {
        border: 2px dashed var(--border-strong);
        border-radius: var(--radius-md);
        padding: 40px 20px;
        text-align: center;
        cursor: pointer;
        transition: border-color 0.2s, background 0.2s;
    }
    .upload-zone:hover, .upload-zone.dragover {
        border-color: var(--accent);
        background: var(--accent-soft);
    }
    .upload-zone-icon { font-size: 40px; margin-bottom: 8px; }
    .upload-zone-text { font-size: 14px; color: var(--text-secondary); }
    .upload-zone-hint { font-size: 12px; color: var(--text-tertiary); margin-top: 4px; }

    .uploaded-file {
        display: flex; align-items: center; gap: 12px;
        padding: 12px; background: var(--bg-input); border-radius: var(--radius-md);
        margin-top: 12px;
    }
    .uploaded-file audio { flex: 1; height: 36px; }
    .uploaded-file-remove {
        background: none; border: none; color: var(--text-tertiary);
        cursor: pointer; font-size: 16px;
    }

    .time-range {
        display: flex; align-items: center; gap: 12px; margin-top: 16px;
    }
    .time-range label { font-size: 13px; color: var(--text-secondary); min-width: 60px; }
    .time-range input {
        width: 80px; padding: 8px; border: 1px solid var(--border);
        border-radius: var(--radius-sm); font-size: 14px; text-align: center;
    }

    .verify-phrase-box {
        background: var(--bg-input);
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        padding: 20px;
        text-align: center;
        font-size: 18px;
        line-height: 1.6;
        font-weight: 600;
        color: var(--text-primary);
        margin: 16px 0;
    }

    .wizard-progress {
        display: flex; gap: 8px; margin-bottom: 20px;
    }
    .wizard-progress-step {
        flex: 1; height: 4px; border-radius: 2px; background: var(--border);
    }
    .wizard-progress-step.done { background: var(--accent); }
    .wizard-progress-step.current { background: var(--accent); opacity: 0.5; }

    .spinner-inline {
        display: inline-block; width: 16px; height: 16px;
        border: 2px solid var(--border); border-top-color: var(--accent);
        border-radius: 50%; animation: spin 0.8s linear infinite;
        vertical-align: middle; margin-right: 6px;
    }

    .record-controls {
        display: flex; gap: 12px; justify-content: center; margin: 16px 0;
    }
    .record-btn {
        width: 56px; height: 56px; border-radius: 50%;
        border: none; cursor: pointer; font-size: 24px;
        display: flex; align-items: center; justify-content: center;
        transition: transform 0.15s;
    }
    .record-btn:hover { transform: scale(1.1); }
    .record-btn.rec { background: #ef4444; color: white; }
    .record-btn.rec.recording { animation: pulse-rec 1s ease-in-out infinite; }
    .record-btn.stop { background: var(--bg-input); color: var(--text-primary); border: 2px solid var(--border); }

    @keyframes pulse-rec {
        0%,100% { box-shadow: 0 0 0 0 rgba(239,68,68,0.4); }
        50% { box-shadow: 0 0 0 12px rgba(239,68,68,0); }
    }

    .or-divider {
        display: flex; align-items: center; margin: 16px 0;
        color: var(--text-tertiary); font-size: 13px;
    }
    .or-divider::before, .or-divider::after {
        content: ''; flex: 1; height: 1px; background: var(--border);
    }
    .or-divider::before { margin-right: 12px; }
    .or-divider::after { margin-left: 12px; }
</style>
@endpush

@section('content')
<div class="voices-container">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
        <h2 style="font-size:22px;font-weight:800;">🎙 Мои голоса</h2>
        <button class="btn btn-primary" id="btn-new-voice" onclick="startWizard()">+ Создать голос</button>
    </div>
    <div style="padding:14px 16px;background:var(--accent-soft);border:1px solid var(--border);border-radius:var(--radius-md);font-size:13px;color:var(--text-secondary);line-height:1.6;margin-bottom:20px;">
        <strong>ℹ️ О функции «Свой голос»</strong><br>
        ИИ анализирует ваш тембр и старается воспроизвести его в песне. Степень сходства зависит от качества записи, выбранного жанра и стиля. Для лучшего результата записывайте голос в тишине, без музыки и эффектов. Спокойные жанры (поп, баллада, акустика) передают голос точнее, чем агрессивные (метал, хардкор).
    </div>

    <!-- Список голосов -->
    <div id="voices-list">
        @forelse($voices as $v)
        <div class="voice-card" id="voice-card-{{ $v->id }}">
            <div class="voice-card-icon">🎙</div>
            <div class="voice-card-info">
                <div class="voice-card-name">{{ $v->name }}</div>
                <div class="voice-card-meta">
                    {{ $v->style ?: 'Без стиля' }} · {{ $v->created_at->format('d.m.Y') }}
                    @if($v->voice_id) · <code style="font-size:11px;">{{ Str::limit($v->voice_id, 20) }}</code> @endif
                </div>
            </div>
            <span class="voice-status {{ in_array($v->status, ['ready']) ? 'ready' : (in_array($v->status, ['failed','expired']) ? 'failed' : ($v->status === 'phrase_ready' ? 'phrase_ready' : 'processing')) }}">
                @if($v->status === 'ready') ✅ Готов
                @elseif($v->status === 'phrase_ready') ⏳ Ждёт верификации
                @elseif($v->status === 'failed') ❌ Ошибка
                @elseif($v->status === 'expired') Истёк
                @else ⏳ Обработка
                @endif
            </span>
            @if($v->status === 'phrase_ready')
                <button class="btn btn-secondary btn-sm" onclick="resumeVoice({{ $v->id }}, '{{ $v->verify_phrase }}')">Продолжить</button>
            @elseif($v->status === 'expired')
                <button class="btn btn-secondary btn-sm" onclick="recreateVoice({{ $v->id }})">🔄 Пересоздать</button>
            @endif
            <button class="voice-delete-btn" onclick="deleteVoice({{ $v->id }})" title="Удалить">🗑</button>
        </div>
        @empty
        <div id="empty-voices" style="text-align:center;padding:40px 20px;color:var(--text-tertiary);">
            <div style="font-size:48px;margin-bottom:12px;">🎙</div>
            <p>У вас пока нет голосов</p>
            <p style="font-size:13px;margin-top:4px;">Создайте свой первый голос для генерации песен</p>
        </div>
        @endforelse
    </div>

    <!-- Мастер создания -->
    <div class="wizard" id="wizard">
        <div class="wizard-card">
            <button type="button" class="btn-back" onclick="closeWizard()">← Назад к списку</button>

            <div class="wizard-progress" id="wizard-progress">
                <div class="wizard-progress-step" id="wp-1"></div>
                <div class="wizard-progress-step" id="wp-2"></div>
                <div class="wizard-progress-step" id="wp-3"></div>
            </div>

            <!-- Шаг 1: Загрузить аудио -->
            <div class="wizard-step active" id="wstep-1">
                <h3 style="margin-bottom:4px;">1. Загрузите образец голоса</h3>
                <p style="font-size:13px;color:var(--text-secondary);margin-bottom:16px;">
                    Аудио 10–60 секунд с чистым вокалом. Без музыки, шумов и эффектов.
                </p>

                <div class="form-group">
                    <label class="form-label">Название голоса *</label>
                    <input type="text" class="form-input" id="w-name" placeholder="Например: Мой голос">
                </div>

                <div class="record-controls">
                    <button class="record-btn rec" id="btn-record-source" onclick="toggleSourceRecording()">🎙</button>
                    <button class="record-btn stop" id="btn-stop-source" onclick="stopSourceRecording()" style="display:none;">⏹</button>
                </div>
                <div style="text-align:center;font-size:13px;color:var(--text-tertiary);margin-bottom:12px;" id="source-rec-status">
                    Запишите голос или загрузите файл
                </div>

                <div id="source-recorded-audio" style="display:none;"></div>

                <div class="or-divider">или загрузите файл</div>

                <div class="upload-zone" id="upload-zone">
                    <div class="upload-zone-icon">📁</div>
                    <div class="upload-zone-text">Перетащите файл или нажмите</div>
                    <div class="upload-zone-hint">MP3, WAV, M4A — до 20 МБ</div>
                    <input type="file" id="audio-file-input" accept="audio/*" style="display:none;">
                </div>
                <div id="uploaded-audio" style="display:none;"></div>

                <div class="time-range">
                    <label>Начало (с):</label>
                    <input type="number" id="w-start" value="0" min="0">
                    <label>Конец (с):</label>
                    <input type="number" id="w-end" value="30" min="1">
                </div>

                <button class="btn btn-primary btn-block" style="margin-top:20px;" id="btn-step1" onclick="wizardStep1()">
                    Далее →
                </button>
            </div>

            <!-- Шаг 2: Прочитать фразу -->
            <div class="wizard-step" id="wstep-2">
                <h3 style="margin-bottom:4px;">2. Прочитайте фразу</h3>
                <p style="font-size:13px;color:var(--text-secondary);margin-bottom:16px;">
                    Для верификации запишите как вы читаете эту фразу:
                </p>

                <div class="verify-phrase-box" id="verify-phrase-text">
                    <span class="spinner-inline"></span> Анализируем голос...
                </div>

                <div id="verify-controls" style="display:none;">
                <div class="record-controls">
                    <button class="record-btn rec" id="btn-record" onclick="toggleRecording()">🎙</button>
                    <button class="record-btn stop" id="btn-stop-rec" onclick="stopRecording()" style="display:none;">⏹</button>
                </div>
                <div style="text-align:center;font-size:13px;color:var(--text-tertiary);" id="rec-status">
                    Нажмите 🎙 для записи
                </div>

                <div id="recorded-audio" style="display:none;margin-top:12px;"></div>

                <div class="or-divider">или загрузите файл</div>

                <div class="upload-zone" id="verify-upload-zone" style="padding:20px;">
                    <div class="upload-zone-text">Загрузить аудио с прочтением</div>
                    <input type="file" id="verify-file-input" accept="audio/*" style="display:none;">
                </div>

                </div><!-- close verify-controls -->
                <button class="btn btn-primary btn-block" style="margin-top:20px;" id="btn-step2" onclick="wizardStep2()" disabled>
                    Создать голос →
                </button>
            </div>

            <!-- Шаг 3: Генерация -->
            <div class="wizard-step" id="wstep-3">
                <div style="text-align:center;padding:32px 0;">
                    <div class="spinner-inline" style="width:48px;height:48px;border-width:3px;margin:0 auto 16px;display:block;"></div>
                    <h3 id="wstep3-title">Создаём ваш голос...</h3>
                    <p style="color:var(--text-secondary);font-size:14px;" id="wstep3-sub">Это может занять 1–3 минуты</p>
                </div>
            </div>

            <!-- Шаг 4: Готово -->
            <div class="wizard-step" id="wstep-4">
                <div style="text-align:center;padding:32px 0;">
                    <div style="font-size:64px;margin-bottom:16px;">✅</div>
                    <h3>Голос готов!</h3>
                    <p style="color:var(--text-secondary);font-size:14px;margin-bottom:20px;">
                        Теперь можно использовать при генерации песен
                    </p>
                    <button class="btn btn-primary" onclick="closeWizard(); location.reload();">К списку голосов</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
var wizardVoiceId = null;
var sourceRecorder = null;
var sourceChunks = [];
var uploadedAudioUrl = null;
var verifyAudioUrl = null;
var mediaRecorder = null;
var recordedChunks = [];

// ===== WIZARD =====
function startWizard() {
    document.getElementById('voices-list').style.display = 'none';
    document.getElementById('btn-new-voice').style.display = 'none';
    document.getElementById('wizard').classList.add('active');
    showWizardStep(1);
}

function closeWizard() {
    document.getElementById('wizard').classList.remove('active');
    document.getElementById('voices-list').style.display = 'block';
    document.getElementById('btn-new-voice').style.display = 'inline-flex';
    wizardVoiceId = null;
    uploadedAudioUrl = null;
    verifyAudioUrl = null;
}

function showWizardStep(n) {
    document.querySelectorAll('.wizard-step').forEach(function(s) { s.classList.remove('active'); });
    document.getElementById('wstep-' + n).classList.add('active');
    for (var i = 1; i <= 3; i++) {
        var el = document.getElementById('wp-' + i);
        el.className = 'wizard-progress-step' + (i < n ? ' done' : (i === n ? ' current' : ''));
    }
}

// ===== UPLOAD ZONE =====
(function() {
    var zone = document.getElementById('upload-zone');
    var input = document.getElementById('audio-file-input');

    zone.addEventListener('click', function() { input.click(); });
    zone.addEventListener('dragover', function(e) { e.preventDefault(); zone.classList.add('dragover'); });
    zone.addEventListener('dragleave', function() { zone.classList.remove('dragover'); });
    zone.addEventListener('drop', function(e) {
        e.preventDefault(); zone.classList.remove('dragover');
        if (e.dataTransfer.files.length) handleAudioUpload(e.dataTransfer.files[0]);
    });
    input.addEventListener('change', function() { if (input.files.length) handleAudioUpload(input.files[0]); });

    // Verify upload zone
    var vzone = document.getElementById('verify-upload-zone');
    var vinput = document.getElementById('verify-file-input');
    vzone.addEventListener('click', function() { vinput.click(); });
    vinput.addEventListener('change', function() { if (vinput.files.length) handleVerifyUpload(vinput.files[0]); });
})();

function handleAudioUpload(file) {
    if (file.size > 20 * 1024 * 1024) { alert('Файл слишком большой (макс. 20 МБ)'); return; }

    var zone = document.getElementById('upload-zone');
    zone.innerHTML = '<div class="spinner-inline"></div> Загружаю...';

    var fd = new FormData();
    fd.append('audio', file);
    fd.append('_token', '{{ csrf_token() }}');

    fetch('/api/voice/upload-audio', { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.success) {
                uploadedAudioUrl = d.url;
                zone.style.display = 'none';
                document.getElementById('source-recorded-audio').style.display = 'none';
                var preview = document.getElementById('uploaded-audio');
                preview.style.display = 'block';
                preview.innerHTML = '<div class="uploaded-file">' +
                    '<span>🎵</span>' +
                    '<audio controls src="' + d.url + '"></audio>' +
                    '<button class="uploaded-file-remove" onclick="resetAudioUpload()">✕</button>' +
                    '</div>';
            } else {
                zone.innerHTML = '<div class="upload-zone-icon">🎤</div><div class="upload-zone-text">Ошибка. Попробуйте снова</div>';
            }
        }).catch(function() {
            zone.innerHTML = '<div class="upload-zone-icon">🎤</div><div class="upload-zone-text">Ошибка загрузки</div>';
        });
}

function resetAudioUpload() {
    uploadedAudioUrl = null;
    document.getElementById('uploaded-audio').style.display = 'none';
    var zone = document.getElementById('upload-zone');
    zone.style.display = 'block';
    zone.innerHTML = '<div class="upload-zone-icon">🎤</div><div class="upload-zone-text">Перетащите файл или нажмите</div><div class="upload-zone-hint">MP3, WAV, M4A — до 20 МБ</div><input type="file" id="audio-file-input" accept="audio/*" style="display:none;">';
    document.getElementById('audio-file-input').addEventListener('change', function() {
        if (this.files.length) handleAudioUpload(this.files[0]);
    });
    document.getElementById('source-recorded-audio').style.display = 'none';
    document.getElementById('source-rec-status').textContent = 'Запишите голос или загрузите файл';
}

// ===== STEP 1: Submit audio =====
function wizardStep1() {
    var name = document.getElementById('w-name').value.trim();
    if (!name) { alert('Введите название'); return; }
    if (!uploadedAudioUrl) { alert('Загрузите аудио'); return; }

    var btn = document.getElementById('btn-step1');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-inline"></span> Отправляю...';

    fetch('/api/voice/create', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        credentials: 'same-origin',
        body: JSON.stringify({
            name: name,
            source_audio_url: uploadedAudioUrl,
            vocal_start: parseInt(document.getElementById('w-start').value) || 0,
            vocal_end: parseInt(document.getElementById('w-end').value) || 30,
            style: '',
            language: 'ru'
        })
    }).then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.success) {
            wizardVoiceId = d.voice_id;
            showWizardStep(2);
            pollPhrase();
        } else {
            alert(d.error || 'Ошибка');
            btn.disabled = false;
            btn.textContent = 'Далее →';
        }
    }).catch(function(e) { alert('Ошибка: ' + e.message); btn.disabled = false; btn.textContent = 'Далее →'; });
}

// ===== POLL PHRASE =====
function pollPhrase() {
    var el = document.getElementById('verify-phrase-text');
    el.textContent = 'Анализируем голос...';

    var interval = setInterval(function() {
        fetch('/api/voice/check-phrase', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            credentials: 'same-origin',
            body: JSON.stringify({ voice_id: wizardVoiceId })
        }).then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.status === 'ready') {
                clearInterval(interval);
                el.textContent = d.verify_phrase;
                document.getElementById('verify-controls').style.display = 'block';
                document.getElementById('rec-status').textContent = 'Нажмите 🎙 для записи';
            } else if (d.status === 'failed') {
                clearInterval(interval);
                el.textContent = 'Ошибка: ' + (d.error || 'не удалось');
            }
        });
    }, 3000);
}

// ===== RECORDING =====
function toggleRecording() {
    if (mediaRecorder && mediaRecorder.state === 'recording') {
        stopRecording();
        return;
    }
    recordedChunks = [];
    navigator.mediaDevices.getUserMedia({ audio: true }).then(function(stream) {
        mediaRecorder = new MediaRecorder(stream);
        mediaRecorder.ondataavailable = function(e) { if (e.data.size > 0) recordedChunks.push(e.data); };
        mediaRecorder.onstop = function() {
            stream.getTracks().forEach(function(t) { t.stop(); });
            var blob = new Blob(recordedChunks, { type: 'audio/webm' });
            uploadVerifyBlob(blob);
        };
        mediaRecorder.start();
        document.getElementById('btn-record').classList.add('recording');
        document.getElementById('btn-stop-rec').style.display = 'flex';
        document.getElementById('rec-status').textContent = '🔴 Записываю...';
    }).catch(function(e) {
        alert('Нет доступа к микрофону: ' + e.message);
    });
}

function stopRecording() {
    if (mediaRecorder && mediaRecorder.state === 'recording') {
        mediaRecorder.stop();
    }
    document.getElementById('btn-record').classList.remove('recording');
    document.getElementById('btn-stop-rec').style.display = 'none';
    document.getElementById('rec-status').textContent = 'Загружаю запись...';
}

function uploadVerifyBlob(blob) {
    var fd = new FormData();
    fd.append('audio', blob, 'verify_recording.webm');
    fd.append('_token', '{{ csrf_token() }}');

    fetch('/api/voice/upload-audio', { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.success) {
                verifyAudioUrl = d.url;
                showVerifyPreview(d.url);
                document.getElementById('btn-step2').disabled = false;
                document.getElementById('rec-status').textContent = '✅ Запись готова';
            } else {
                document.getElementById('rec-status').textContent = '❌ Ошибка загрузки';
            }
        });
}

function handleVerifyUpload(file) {
    var fd = new FormData();
    fd.append('audio', file);
    fd.append('_token', '{{ csrf_token() }}');

    document.getElementById('rec-status').textContent = 'Загружаю файл...';

    fetch('/api/voice/upload-audio', { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.success) {
                verifyAudioUrl = d.url;
                showVerifyPreview(d.url);
                document.getElementById('btn-step2').disabled = false;
                document.getElementById('rec-status').textContent = '✅ Файл загружен';
            }
        });
}

function showVerifyPreview(url) {
    var el = document.getElementById('recorded-audio');
    el.style.display = 'block';
    el.innerHTML = '<div class="uploaded-file"><span>🎙</span><audio controls src="' + url + '"></audio></div>';
}

// ===== STEP 2: Submit verify =====
function wizardStep2() {
    if (!verifyAudioUrl) { alert('Запишите или загрузите аудио'); return; }

    var btn = document.getElementById('btn-step2');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-inline"></span> Создаю...';

    fetch('/api/voice/submit-verify', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        credentials: 'same-origin',
        body: JSON.stringify({ voice_id: wizardVoiceId, verify_audio_url: verifyAudioUrl })
    }).then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.success) {
            showWizardStep(3);
            pollVoiceReady();
        } else {
            alert(d.error || 'Ошибка');
            btn.disabled = false;
            btn.textContent = 'Создать голос →';
        }
    });
}

// ===== POLL VOICE READY =====
function pollVoiceReady() {
    var interval = setInterval(function() {
        fetch('/api/voice/check-status', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            credentials: 'same-origin',
            body: JSON.stringify({ voice_id: wizardVoiceId })
        }).then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.status === 'ready') {
                clearInterval(interval);
                showWizardStep(4);
            } else if (d.status === 'failed') {
                clearInterval(interval);
                document.getElementById('wstep3-title').textContent = '❌ Ошибка';
                document.getElementById('wstep3-sub').textContent = d.error || 'Не удалось создать голос';
            }
        });
    }, 5000);
}

// ===== RESUME (for phrase_ready voices) =====
function resumeVoice(id, phrase) {
    wizardVoiceId = id;
    startWizard();
    showWizardStep(2);
    document.getElementById('verify-phrase-text').textContent = phrase;
    document.getElementById('rec-status').textContent = 'Нажмите 🎙 для записи';
}

// ===== DELETE =====
function deleteVoice(id) {
    if (!confirm('Удалить голос?')) return;
    fetch('/api/voice/delete/' + id, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        credentials: 'same-origin'
    }).then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.success) {
            var card = document.getElementById('voice-card-' + id);
            if (card) card.remove();
        }
    });
}
// ===== SOURCE RECORDING (step 1) =====
function toggleSourceRecording() {
    if (sourceRecorder && sourceRecorder.state === 'recording') {
        stopSourceRecording();
        return;
    }
    sourceChunks = [];
    navigator.mediaDevices.getUserMedia({ audio: true }).then(function(stream) {
        sourceRecorder = new MediaRecorder(stream);
        sourceRecorder.ondataavailable = function(e) { if (e.data.size > 0) sourceChunks.push(e.data); };
        sourceRecorder.onstop = function() {
            stream.getTracks().forEach(function(t) { t.stop(); });
            var blob = new Blob(sourceChunks, { type: 'audio/webm' });
            uploadSourceBlob(blob);
        };
        sourceRecorder.start();
        document.getElementById('btn-record-source').classList.add('recording');
        document.getElementById('btn-stop-source').style.display = 'flex';
        document.getElementById('source-rec-status').textContent = '🔴 Записываю...';
        // Скрываем зону загрузки
        document.getElementById('upload-zone').style.display = 'none';
    }).catch(function(e) {
        alert('Нет доступа к микрофону: ' + e.message);
    });
}

function stopSourceRecording() {
    if (sourceRecorder && sourceRecorder.state === 'recording') {
        sourceRecorder.stop();
    }
    document.getElementById('btn-record-source').classList.remove('recording');
    document.getElementById('btn-stop-source').style.display = 'none';
    document.getElementById('source-rec-status').textContent = 'Загружаю запись...';
}

function uploadSourceBlob(blob) {
    var fd = new FormData();
    fd.append('audio', blob, 'source_recording.webm');
    fd.append('_token', '{{ csrf_token() }}');

    fetch('/api/voice/upload-audio', { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.success) {
                uploadedAudioUrl = d.url;
                document.getElementById('source-rec-status').textContent = '✅ Запись готова';
                var preview = document.getElementById('source-recorded-audio');
                preview.style.display = 'block';
                preview.innerHTML = '<div class="uploaded-file">' +
                    '<span>🎙</span>' +
                    '<audio controls src="' + d.url + '"></audio>' +
                    '<button class="uploaded-file-remove" onclick="resetSourceRecording()">✕</button>' +
                    '</div>';
                // Скрываем зону загрузки файлов
                document.getElementById('upload-zone').style.display = 'none';
                document.getElementById('uploaded-audio').style.display = 'none';
            } else {
                document.getElementById('source-rec-status').textContent = '❌ Ошибка загрузки';
                document.getElementById('upload-zone').style.display = 'block';
            }
        });
}

function resetSourceRecording() {
    uploadedAudioUrl = null;
    document.getElementById('source-recorded-audio').style.display = 'none';
    document.getElementById('source-rec-status').textContent = 'Запишите голос или загрузите файл';
    document.getElementById('upload-zone').style.display = 'block';
}
// ===== RECREATE expired voice =====
async function recreateVoice(id) {
    if (!confirm('Пересоздать голос? Нужно будет заново записать верификацию.')) return;

    try {
        var r = await fetch('/api/voice/recreate', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            credentials: 'same-origin',
            body: JSON.stringify({ voice_id: id })
        });
        var d = await r.json();
        if (r.ok && d.success) {
            wizardVoiceId = d.voice_id;
            startWizard();
            showWizardStep(2);
            pollPhrase();
        } else {
            alert('❌ ' + (d.error || 'Ошибка'));
        }
    } catch(e) { alert('Ошибка: ' + e.message); }
}
</script>
@endpush