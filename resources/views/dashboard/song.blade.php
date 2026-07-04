@extends('layouts.app')
@section('title', $song->title . ' — На Репите')

@push('styles')
<style>
    .song-detail-header { text-align: center; margin-bottom: 24px; }
    .big-cover {
        width: 180px; height: 180px; margin: 0 auto 16px;
        background: linear-gradient(135deg, var(--accent), #a29bfe);
        border-radius: var(--radius-xl);
        display: flex; align-items: center; justify-content: center;
        font-size: 72px;
        box-shadow: 0 12px 40px var(--accent-glow);
    }
    .detail-title { font-size: 22px; font-weight: 800; margin-bottom: 8px; letter-spacing: -0.02em; }
    .detail-title .edit-icon { font-size: 14px; opacity: 0; transition: opacity var(--duration); vertical-align: middle; }
    .detail-title:hover .edit-icon { opacity: 0.4; }
    .detail-title:hover { cursor: pointer; }
    .detail-tags { display: flex; gap: 6px; justify-content: center; flex-wrap: wrap; }
    .tag { background: var(--surface-glass); padding: 4px 12px; border-radius: var(--radius-full); font-size: 12px; color: var(--text-secondary); border: 1px solid var(--border); }

    .track-row {
        background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-lg);
        padding: 14px; margin-bottom: 10px; display: flex; align-items: center; gap: 12px;
        box-shadow: var(--shadow-xs);
    }
    .track-play-btn {
        width: 48px; height: 48px; border-radius: 50%;
        background: var(--bg-input); border: none; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        transition: all var(--duration) var(--ease); color: var(--text-primary); flex-shrink: 0;
    }
    .track-play-btn.active { background: var(--accent); color: white; }
    .track-play-btn svg { width: 20px; height: 20px; fill: currentColor; }
    .track-play-btn .icon-pause, .track-play-btn .icon-loading { display: none; }

    .track-info { flex: 1; }
    .track-name { font-weight: 600; font-size: 15px; }
    .track-dl { padding: 3px; color: var(--text-tertiary); font-size: 13px; display: flex; align-items: center; gap: 4px; margin-top: 4px; background: none; cursor: pointer; }
    .track-dl:hover { color: var(--accent); }

    .favorite-btn {
        width: 40px; height: 40px; border-radius: 50%;
        border: 2px solid var(--border); background: var(--bg-card);
        cursor: pointer; display: flex; align-items: center; justify-content: center;
        font-size: 18px; transition: all var(--duration) var(--ease);
    }
    .favorite-btn:hover { border-color: var(--heart); background: var(--danger-soft); }
    .favorite-btn.active { border-color: var(--heart); background: var(--heart); color: white; }

    .lyrics-box {
        background: var(--bg-card); border: 1px solid var(--border);
        padding: 20px; border-radius: var(--radius-lg);
        margin-top: 24px; white-space: pre-wrap;
        font-size: 14px; line-height: 1.7; color: var(--text-primary);
        box-shadow: var(--shadow-xs);
    }

    /* Chart buttons */
    .chart-buttons-row { display: flex; flex-direction: column; gap: 8px; margin-bottom: 16px; }
    .chart-add-btn {
        display: flex; align-items: center; justify-content: center; gap: 8px;
        padding: 12px 20px; border: 1.5px solid var(--border); border-radius: var(--radius-md);
        font-size: 14px; font-weight: 600; cursor: pointer; transition: all var(--duration) var(--ease);
        background: var(--bg-card); box-shadow: var(--shadow-xs);
    }
    .chart-add-btn:hover { transform: translateY(-1px); box-shadow: var(--shadow-md); }
    .chart-add-btn.weekly { border-color: var(--gold-border); color: var(--gold); }
    .chart-add-btn.weekly:hover { background: var(--gold-soft); }
    .chart-add-btn.valentine { border-color: rgba(225,29,72,0.2); color: var(--danger); }
    .chart-add-btn.valentine:hover { background: var(--danger-soft); }

    .chart-status-badge {
        display: flex; align-items: center; justify-content: space-between;
        padding: 10px 14px; border-radius: var(--radius-md); font-size: 14px; font-weight: 500;
    }
    .chart-status-badge.weekly { background: var(--success-soft); color: var(--success); }
    .chart-status-badge.valentine { background: var(--danger-soft); color: var(--danger); }
    .chart-status-badge button {
        background: rgba(0,0,0,0.06); border: none; width: 24px; height: 24px;
        border-radius: 50%; cursor: pointer; font-size: 12px;
        display: flex; align-items: center; justify-content: center;
    }
    .chart-status-badge button:hover { background: rgba(0,0,0,0.12); }

    .chart-info-hint {
        font-size: 13px; color: var(--text-tertiary); text-align: center;
        padding: 8px; background: var(--bg-input); border-radius: var(--radius-sm); margin-bottom: 8px;
    }
    .chart-info-hint a { color: var(--accent); }

    /* Modal */
    .chart-modal-overlay {
        position: fixed; top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0,0,0,0.4); display: none;
        align-items: center; justify-content: center;
        z-index: 1000; padding: 20px;
    }
    .chart-modal-overlay.active { display: flex; }
    .chart-modal {
        background: var(--bg-card); border-radius: var(--radius-xl);
        max-width: 400px; width: 100%; max-height: 90vh; overflow-y: auto;
        box-shadow: var(--shadow-lg); animation: modalUp 0.3s var(--ease);
    }
    @keyframes modalUp { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }
    .chart-modal-header { display:flex; justify-content:space-between; align-items:center; padding:20px; border-bottom:1px solid var(--border); }
    .chart-modal-header h3 { font-size:18px; font-weight:700; margin:0; }
    .chart-modal-close { width:32px; height:32px; border:none; background:var(--bg-input); border-radius:50%; cursor:pointer; font-size:16px; }
    .chart-modal-body { padding:20px; }
    .chart-modal-desc { font-size:14px; color:var(--text-secondary); margin-bottom:20px; line-height:1.5; }
    .chart-modal-section { margin-bottom:20px; }
    .chart-modal-section label { display:block; font-size:13px; font-weight:600; color:var(--text-primary); margin-bottom:10px; }
    .variant-buttons { display:flex; gap:8px; }
    .variant-btn {
        flex:1; padding:12px; border:2px solid var(--border); background:var(--bg-card);
        border-radius:var(--radius-md); font-size:14px; cursor:pointer; transition:all var(--duration) var(--ease); text-align:center;
    }
    .variant-btn:hover { border-color:var(--accent); }
    .variant-btn.active { border-color:var(--accent); background:var(--accent); color:white; }
    .chart-modal-section textarea { width:100%; padding:12px; border:1.5px solid var(--border); border-radius:var(--radius-md); font-size:14px; font-family:inherit; resize:vertical; min-height:80px; background:var(--bg-input); color:var(--text-primary); }
    .chart-modal-section textarea:focus { outline:none; border-color:var(--accent); }
    .comment-counter { text-align:right; font-size:12px; color:var(--text-tertiary); margin-top:6px; }
    .chart-modal-footer { display:flex; gap:10px; padding:20px; border-top:1px solid var(--border); }
    .chart-modal-cancel { flex:1; padding:12px; border:1.5px solid var(--border); background:var(--bg-card); border-radius:var(--radius-md); font-size:14px; font-weight:600; cursor:pointer; }
    .chart-modal-submit { flex:2; padding:12px; border:none; background:var(--accent); color:white; border-radius:var(--radius-md); font-size:14px; font-weight:600; cursor:pointer; transition:background var(--duration) var(--ease); }
    .chart-modal-submit:hover { background:var(--accent-hover); }
    .chart-modal-submit:disabled { background:var(--text-tertiary); cursor:not-allowed; }
    .chart-modal.valentine .chart-modal-header { background:var(--danger-soft); }
    .chart-modal.valentine .chart-modal-header h3 { color:var(--danger); }
    .chart-modal.valentine .chart-modal-submit { background:var(--danger); }
    .chart-modal.valentine .variant-btn.active { background:var(--danger); border-color:var(--danger); }

    /* Edit title */
    .editable-title-wrap { position:relative; }
    .edit-title-form { display:flex; flex-direction:column; align-items:center; gap:8px; }
    .title-input { width:100%; max-width:400px; padding:10px 14px; border:2px solid var(--accent); border-radius:var(--radius-md); font-size:20px; font-weight:700; text-align:center; font-family:inherit; outline:none; }
    .title-input:focus { box-shadow:0 0 0 3px var(--accent-soft); }
    .edit-title-buttons { display:flex; gap:8px; }
    .edit-btn { width:36px; height:36px; border-radius:50%; border:none; cursor:pointer; font-size:16px; display:flex; align-items:center; justify-content:center; transition:all var(--duration) var(--ease); }
    .edit-btn.save { background:var(--success-soft); color:var(--success); }
    .edit-btn.save:hover { background:var(--success); color:white; }
    .edit-btn.cancel { background:var(--danger-soft); color:var(--danger); }
    .edit-btn.cancel:hover { background:var(--danger); color:white; }

    /* Generation status */
    .generation-status { background:var(--accent-soft); border:1.5px solid var(--border-accent); border-radius:var(--radius-lg); padding:24px; margin-bottom:20px; text-align:center; }
    .generation-status h3 { font-size:16px; margin-bottom:8px; color:var(--accent); }
    .generation-status p { font-size:14px; color:var(--text-secondary); margin-bottom:12px; }
    .generation-spinner { width:40px; height:40px; border:3px solid var(--border); border-top:3px solid var(--accent); border-radius:50%; animation:spin 1s linear infinite; margin:0 auto 12px; }
    .generation-error { background:var(--danger-soft); border:1.5px solid rgba(225,29,72,0.2); border-radius:var(--radius-lg); padding:24px; margin-bottom:20px; text-align:center; }
    .generation-error h3 { color:var(--danger); }
    .retry-btn { background:var(--accent); color:white; border:none; padding:10px 20px; border-radius:var(--radius-sm); font-size:14px; cursor:pointer; font-weight:600; }
    .retry-btn:hover { background:var(--accent-hover); }

    /* Stems */
    .stem-section { margin-top:12px; padding:16px; background:var(--bg-input); border-radius:var(--radius-md); border:1px solid var(--border); }
    .stem-section h4 { font-size:14px; margin-bottom:12px; }
    .stem-btn {
        display:inline-flex; align-items:center; gap:8px; padding:10px 16px;
        background:var(--accent); color:white; border:none; border-radius:var(--radius-md);
        font-size:14px; font-weight:600; cursor:pointer; transition:all var(--duration) var(--ease); width:100%; justify-content:center;
    }
    .stem-btn:hover { background:var(--accent-hover); box-shadow:var(--shadow-glow); }
    .stem-btn:disabled { background:var(--text-tertiary); cursor:wait; box-shadow:none; }
    .stem-results { display:none; margin-top:12px; }
    .stem-results.show { display:block; }
    .stem-file { display:flex; align-items:center; justify-content:space-between; padding:10px 14px; background:var(--bg-card); border-radius:var(--radius-sm); margin-bottom:8px; border:1px solid var(--border); }
    .stem-file-info { display:flex; align-items:center; gap:8px; }
    .stem-file-icon { font-size:20px; }
    .stem-file-name { font-size:13px; font-weight:600; }
    .stem-file-actions { display:flex; gap:6px; }
    .stem-play-btn { padding:6px 12px; border-radius:var(--radius-sm); border:none; font-size:14px; cursor:pointer; transition:all var(--duration) var(--ease); background:var(--accent); color:white; min-width:36px; text-align:center; }
    .stem-play-btn:hover { opacity:0.85; }
    .stem-play-btn.active { background:var(--danger); }
    .stem-download-btn { padding:6px 12px; border-radius:var(--radius-sm); background:var(--bg-input); color:var(--text-primary); text-decoration:none; font-size:14px; border:1px solid var(--border); display:inline-flex; align-items:center; }
    .stem-download-btn:hover { background:var(--border); }
    .stem-error { color:var(--danger); font-size:13px; margin-top:8px; }
    .stem-loading-text { display:none; text-align:center; padding:20px; color:var(--text-secondary); font-size:14px; }
    .stem-loading-text.show { display:block; }
    .stem-loading-spinner { width:32px; height:32px; border:3px solid var(--border); border-top-color:var(--accent); border-radius:50%; animation:spin 1s linear infinite; margin:0 auto 12px; }

    /* Track operations */
    .track-ops-grid { display:flex; flex-direction:column; gap:8px; }
    .track-ops-grid a.chart-add-btn { text-decoration:none; color:var(--text-primary); }
    .op-input { width:100%; padding:12px; border:1.5px solid var(--border); border-radius:var(--radius-md); font-size:14px; font-family:inherit; background:var(--bg-input); color:var(--text-primary); }
    .op-input:focus { outline:none; border-color:var(--accent); }
</style>
@endpush

@section('content')
@php
    $trackOpsAllowedIds = config('services.track_ops.allowed_user_ids', []);
    $trackOpsAllowed = empty($trackOpsAllowedIds)
        || in_array('*', $trackOpsAllowedIds, true)
        || in_array((string) $authUser->user_id, $trackOpsAllowedIds, true);
@endphp
<a href="{{ route('songs.index') }}" style="display:inline-flex; align-items:center; gap:6px; color:var(--text-tertiary); margin-bottom:16px; font-size:14px;">← Назад к трекам</a>

<div class="song-detail-header">
    <div class="big-cover" id="song-cover">
        @if($song->cover_url)
            <img src="{{ $song->cover_url }}" alt="Обложка" style="width:100%;height:100%;object-fit:cover;border-radius:var(--radius-xl);">
        @else
            🎶
        @endif
    </div>
    <button onclick="fetchCover({{ $song->id }})" id="cover-btn" style="background:none;border:1px solid var(--border);border-radius:var(--radius-md);padding:8px 16px;font-size:13px;cursor:pointer;color:var(--text-secondary);margin-bottom:12px;">
        🖼 Получить обложку
    </button>
    @if($song->user_id === $authUser->user_id)
        <div class="editable-title-wrap">
            <h1 class="detail-title" id="song-title" onclick="startEditTitle()">{{ $song->title ?: 'Без названия' }} <span class="edit-icon">✏️</span></h1>
            <div class="edit-title-form" id="edit-title-form" style="display:none;">
                <input type="text" id="title-input" class="title-input" value="{{ $song->title }}" maxlength="255">
                <div class="edit-title-buttons">
                    <button class="edit-btn save" onclick="saveTitle()">✓</button>
                    <button class="edit-btn cancel" onclick="cancelEditTitle()">✕</button>
                </div>
            </div>
        </div>
    @else
        <h1 class="detail-title">{{ $song->title ?: 'Без названия' }}</h1>
    @endif
    <div class="detail-tags">
        @if($song->genre) <span class="tag">🎸 {{ $song->genre }}</span> @endif
        @if($song->occasion) <span class="tag">📌 {{ $song->occasion }}</span> @endif
        <span class="tag">📅 {{ $song->created_at->format('d.m.Y') }}</span>
    </div>
</div>

{{-- Generation status --}}
@if(!$song->file_path && $song->suno_task_id)
    <div class="generation-status" id="generation-status">
        <div class="generation-spinner" id="gen-spinner"></div>
        <h3>⏳ Песня генерируется...</h3>
        <p>Обычно 1-3 минуты. Страница обновится автоматически.</p>
    </div>
@elseif(!$song->file_path && !$song->suno_task_id)
    <div class="generation-error">
        <h3>❌ Ошибка генерации</h3>
        <p style="color:var(--text-secondary); margin:8px 0 12px;">Попробуйте создать песню заново.</p>
        <a href="{{ route('generate.create') }}" class="retry-btn" style="display:inline-block; text-decoration:none;">Создать новую</a>
    </div>
@endif

{{-- Chart buttons --}}
@if($song->file_path && $song->user_id === $authUser->user_id)
    <div class="chart-buttons-row">
        @if(isset($valentineChart) && $valentineChart && $valentineChart->is_active)
            @if($songInValentineChart)
                <div class="chart-status-badge valentine"><span>💕 В чарте «Песни о любви»</span><button onclick="removeFromThemeChart({{ $song->id }}, 'valentine')">✕</button></div>
            @elseif(!$userHasOtherSongInValentineChart)
                <button class="chart-add-btn valentine" onclick="openChartModal('valentine')">💕 Добавить в чарт ко Дню влюблённых</button>
            @endif
        @endif
        @if(isset($songInChart) && $songInChart)
            <div class="chart-status-badge weekly"><span>🏆 В недельном чарте</span><button onclick="removeFromChart({{ $song->id }})">✕</button></div>
        @elseif(!$userHasOtherSongInChart)
            <button class="chart-add-btn weekly" onclick="openChartModal('weekly')">🏆 Добавить в недельный чарт</button>
        @endif
    </div>
    @if(isset($userHasOtherSongInValentineChart) && $userHasOtherSongInValentineChart && !$songInValentineChart)
        <div class="chart-info-hint">💕 У тебя уже есть песня в <a href="{{ route('charts.valentine') }}">«Песни о любви»</a></div>
    @endif
    @if(isset($userHasOtherSongInChart) && $userHasOtherSongInChart && !$songInChart)
        <div class="chart-info-hint">🏆 У тебя уже есть песня в <a href="{{ route('charts.index') }}">недельном чарте</a></div>
    @endif
@endif

{{-- Modal --}}
<div class="chart-modal-overlay" id="chartModal">
    <div class="chart-modal">
        <div class="chart-modal-header"><h3 id="chartModalTitle">Добавить в чарт</h3><button class="chart-modal-close" onclick="closeChartModal()">✕</button></div>
        <div class="chart-modal-body">
            <p id="chartModalDesc" class="chart-modal-desc"></p>
            <div class="chart-modal-section">
                <label>Выберите вариант:</label>
                <div class="variant-buttons" id="modal-variants">
                    @if($song->file_path) <button type="button" class="variant-btn active" data-variant="1" onclick="selectModalVariant(1)">▶ Вариант 1</button> @endif
                    @if($song->file_path_2) <button type="button" class="variant-btn" data-variant="2" onclick="selectModalVariant(2)">▶ Вариант 2</button> @endif
                </div>
            </div>
            <div class="chart-modal-section">
                <label id="commentLabel">Комментарий:</label>
                <textarea id="modal-comment" placeholder="Расскажите историю..." maxlength="500"></textarea>
                <div class="comment-counter"><span id="modal-comment-length">0</span>/500</div>
            </div>
        </div>
        <div class="chart-modal-footer">
            <button class="chart-modal-cancel" onclick="closeChartModal()">Отмена</button>
            <button class="chart-modal-submit" id="chartModalSubmit" onclick="submitToChart()">Добавить</button>
        </div>
    </div>
</div>

{{-- Tracks --}}
<div class="tracks-list">
    @if($song->file_path)
    <div class="track-row">
        <button class="track-play-btn" data-play-track data-url="{{ $song->file_path }}" data-title="{{ $song->title }} (Вариант 1)" data-author="Вариант 1" data-cover="{{ $song->cover_url ?? '🎶' }}">
            <svg class="icon-play" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
            <svg class="icon-pause" viewBox="0 0 24 24"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
            <div class="spinner icon-loading" style="border-color:rgba(0,0,0,0.15);border-top-color:currentColor;"></div>
        </button>
        <div class="track-info">
            <div class="track-name">Вариант 1</div>
            <button id="dl-btn-1" onclick="downloadSong({{ $song->id }}, 1)" class="track-dl">⬇ Скачать</button>
        </div>
        <button class="favorite-btn {{ in_array($song->id . '_1', $favoriteVariants ?? []) ? 'active' : '' }}" onclick="toggleFavorite({{ $song->id }}, 1)" data-variant="1" title="Избранное">❤️</button>
    </div>

    <div class="stem-section" id="stem-section-1">
        <h4>✂️ Разделить минус и голос (Вариант 1)</h4>
        @if($song->instrumental_url_1)
            <div class="stem-results show" id="stem-results-1">
                <div class="stem-file"><div class="stem-file-info"><span class="stem-file-icon">🎹</span><span class="stem-file-name">Минусовка</span></div><div class="stem-file-actions"><button class="stem-play-btn" data-url="{{ $song->instrumental_url_1 }}" data-title="{{ $song->title }} (Instrumental)" data-author="На Репите">▶</button><a href="{{ $song->instrumental_url_1 }}" download class="stem-download-btn">⬇</a></div></div>
                @if($song->vocal_url_1)<div class="stem-file"><div class="stem-file-info"><span class="stem-file-icon">🎤</span><span class="stem-file-name">Чистый голос</span></div><div class="stem-file-actions"><button class="stem-play-btn" data-url="{{ $song->vocal_url_1 }}" data-title="{{ $song->title }} (Vocals)" data-author="На Репите">▶</button><a href="{{ $song->vocal_url_1 }}" download class="stem-download-btn">⬇</a></div></div>@endif
            </div>
        @elseif($song->stem_task_id_1)
            <div class="stem-loading-text show" id="stem-loading-1"><div class="stem-loading-spinner"></div><div>Разделение запущено...</div><div style="font-size:12px;margin-top:4px;">Обновите через 1-2 минуты</div></div>
            <button class="stem-btn" id="stem-btn-1" onclick="startStemSeparation({{ $song->id }}, 1)" style="margin-top:12px;background:var(--text-tertiary);font-size:13px;">🔄 Проверить</button>
            <div class="stem-results" id="stem-results-1"></div><div class="stem-error" id="stem-error-1"></div>
        @else
            <button class="stem-btn" id="stem-btn-1" onclick="startStemSeparation({{ $song->id }}, 1)">✂️ Разделить на минус и голос</button>
            <div class="stem-loading-text" id="stem-loading-1"><div class="stem-loading-spinner"></div><div>Разделяю трек...</div><div style="font-size:12px;margin-top:4px;">1-3 минуты, не закрывайте страницу</div></div>
            <div class="stem-results" id="stem-results-1"></div><div class="stem-error" id="stem-error-1"></div>
        @endif
    </div>
    @endif

    @if($song->file_path_2)
    <div class="track-row">
        <button class="track-play-btn" data-play-track data-url="{{ $song->file_path_2 }}" data-title="{{ $song->title }} (Вариант 2)" data-author="Вариант 2" data-cover="{{ $song->cover_url ?? '🎶' }}">
            <svg class="icon-play" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
            <svg class="icon-pause" viewBox="0 0 24 24"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
            <div class="spinner icon-loading" style="border-color:rgba(0,0,0,0.15);border-top-color:currentColor;"></div>
        </button>
        <div class="track-info">
            <div class="track-name">Вариант 2</div>
            <button id="dl-btn-2" onclick="downloadSong({{ $song->id }}, 2)" class="track-dl">⬇ Скачать</button>
        </div>
        <button class="favorite-btn {{ in_array($song->id . '_2', $favoriteVariants ?? []) ? 'active' : '' }}" onclick="toggleFavorite({{ $song->id }}, 2)" data-variant="2" title="Избранное">❤️</button>
    </div>

    <div class="stem-section" id="stem-section-2">
        <h4>✂️ Разделить минус и голос (Вариант 2)</h4>
        @if($song->instrumental_url_2)
            <div class="stem-results show" id="stem-results-2">
                <div class="stem-file"><div class="stem-file-info"><span class="stem-file-icon">🎹</span><span class="stem-file-name">Минусовка</span></div><div class="stem-file-actions"><button class="stem-play-btn" data-url="{{ $song->instrumental_url_2 }}" data-title="{{ $song->title }} (Instrumental v2)" data-author="На Репите">▶</button><a href="{{ $song->instrumental_url_2 }}" download class="stem-download-btn">⬇</a></div></div>
                @if($song->vocal_url_2)<div class="stem-file"><div class="stem-file-info"><span class="stem-file-icon">🎤</span><span class="stem-file-name">Чистый голос</span></div><div class="stem-file-actions"><button class="stem-play-btn" data-url="{{ $song->vocal_url_2 }}" data-title="{{ $song->title }} (Vocals v2)" data-author="На Репите">▶</button><a href="{{ $song->vocal_url_2 }}" download class="stem-download-btn">⬇</a></div></div>@endif
            </div>
        @elseif($song->stem_task_id_2)
            <div class="stem-loading-text show" id="stem-loading-2"><div class="stem-loading-spinner"></div><div>Разделение запущено...</div></div>
            <button class="stem-btn" id="stem-btn-2" onclick="startStemSeparation({{ $song->id }}, 2)" style="margin-top:12px;background:var(--text-tertiary);font-size:13px;">🔄 Проверить</button>
            <div class="stem-results" id="stem-results-2"></div><div class="stem-error" id="stem-error-2"></div>
        @else
            <button class="stem-btn" id="stem-btn-2" onclick="startStemSeparation({{ $song->id }}, 2)">✂️ Разделить на минус и голос</button>
            <div class="stem-loading-text" id="stem-loading-2"><div class="stem-loading-spinner"></div><div>Разделяю трек...</div></div>
            <div class="stem-results" id="stem-results-2"></div><div class="stem-error" id="stem-error-2"></div>
        @endif
    </div>
    @endif
</div>

@if($song->file_path && !$song->is_deleted && !$isProtected)
    <div style="background:var(--bg-input);border:1px solid var(--border);border-radius:var(--radius-md);padding:12px 16px;margin-top:12px;display:flex;align-items:flex-start;gap:10px;font-size:13px;color:var(--text-secondary);line-height:1.5;">
        <span style="font-size:18px;flex-shrink:0;">⏳</span>
        <div>Аудиофайлы хранятся <strong>7 дней</strong> — рекомендуем скачать! Добавьте в избранное ❤️ или в чарт 🏆 чтобы защитить от удаления.</div>
    </div>
@endif
@if($song->file_path && $song->suno_task_id)
<div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);padding:20px;margin-top:16px;">
    <h3 style="font-size:15px;font-weight:700;margin-bottom:12px;">Создать персону</h3>
    <p style="font-size:13px;color:var(--text-secondary);margin-bottom:16px;">Сохраните голос и стиль этой песни для будущих генераций</p>

    <div style="display:flex;gap:8px;flex-wrap:wrap;" id="persona-buttons">
        @if($song->audio_id_1)
        <button class="btn btn-secondary btn-sm" onclick="openPersonaModal('{{ $song->audio_id_1 }}', 1)">Из варианта 1</button>
        @endif
        @if($song->audio_id_2)
        <button class="btn btn-secondary btn-sm" onclick="openPersonaModal('{{ $song->audio_id_2 }}', 2)">Из варианта 2</button>
        @endif
    </div>
</div>

<!-- Persona Modal -->
<div class="chart-modal-overlay" id="personaModal">
    <div class="chart-modal">
        <div class="chart-modal-header">
            <h3>Создать персону</h3>
            <button class="chart-modal-close" onclick="closePersonaModal()">✕</button>
        </div>
        <div class="chart-modal-body">
            <input type="hidden" id="persona-audio-id">
            <div class="form-group">
                <label class="form-label">Название *</label>
                <input type="text" class="form-input" id="persona-name" placeholder="Например: Рок-вокалист" maxlength="100">
            </div>
            <div class="form-group">
                <label class="form-label">Описание стиля *</label>
                <textarea class="form-textarea" id="persona-desc" rows="3" placeholder="Мужской вокал, энергичный рок, хриплый тембр..." maxlength="500"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Жанр (необязательно)</label>
                <input type="text" class="form-input" id="persona-style" placeholder="Rock, Pop..." maxlength="100">
            </div>
        </div>
        <div class="chart-modal-footer">
            <button class="chart-modal-cancel" onclick="closePersonaModal()">Отмена</button>
            <button class="chart-modal-submit" id="persona-submit-btn" onclick="submitPersona()">Создать</button>
        </div>
    </div>
</div>
@endif
{{-- Track operations (extend / replace section / mashup) --}}
@if($song->file_path && $song->suno_task_id && $song->user_id === $authUser->user_id && $trackOpsAllowed)
<div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);padding:20px;margin-top:16px;">
    <h3 style="font-size:15px;font-weight:700;margin-bottom:4px;">Изменить трек</h3>
    <p style="font-size:13px;color:var(--text-secondary);margin-bottom:16px;">Понравилась песня? Сделайте её длиннее, перепойте в другом стиле или получите минусовку</p>
    <div class="track-ops-grid">
        <button class="chart-add-btn" onclick="openExtendModal()">➕ Продлить трек</button>
        <a class="chart-add-btn" href="{{ route('studio') }}?op=upload_cover&song={{ $song->id }}">🎤 Кавер в новом стиле</a>
        <a class="chart-add-btn" href="{{ route('studio') }}?op=add_instrumental&song={{ $song->id }}">🎹 Минусовка в новом стиле</a>
        <a class="chart-add-btn" href="{{ route('studio') }}?op=add_vocals&song={{ $song->id }}">🎶 Новый вокал на минус</a>
        <button class="chart-add-btn" onclick="openMashupModal()">🔀 Сделать мэшап</button>
        <button class="chart-add-btn" onclick="openReplaceModal()">✏️ Заменить фрагмент</button>
    </div>
    <p style="font-size:12px;color:var(--text-tertiary);margin-top:10px;">Каждая операция — 1 песня с баланса. Замена фрагмента — отдельная функция.</p>
</div>

{{-- Extend modal --}}
<div class="chart-modal-overlay" id="extendModal">
    <div class="chart-modal">
        <div class="chart-modal-header"><h3>➕ Продлить трек</h3><button class="chart-modal-close" onclick="closeOpModal('extendModal')">✕</button></div>
        <div class="chart-modal-body">
            <p class="chart-modal-desc">Допишем продолжение к выбранному варианту. Спишется 1 песня.</p>
            <div class="chart-modal-section">
                <label>Вариант:</label>
                <div class="variant-buttons" id="extend-variants">
                    @if($song->audio_id_1)<button type="button" class="variant-btn active" data-variant="1" onclick="selectOpVariant('extend',1)">Вариант 1</button>@endif
                    @if($song->audio_id_2)<button type="button" class="variant-btn" data-variant="2" onclick="selectOpVariant('extend',2)">Вариант 2</button>@endif
                </div>
            </div>
            <div class="chart-modal-section">
                <label>С какой секунды продолжить (необязательно):</label>
                <input type="number" id="extend-continue" class="op-input" min="0" step="1" placeholder="например, 120 — с конца, если пусто">
            </div>
            <div class="chart-modal-section">
                <label>Что добавить в продолжении (необязательно):</label>
                <textarea id="extend-prompt" placeholder="Текст / описание продолжения" maxlength="5000"></textarea>
            </div>
        </div>
        <div class="chart-modal-footer">
            <button class="chart-modal-cancel" onclick="closeOpModal('extendModal')">Отмена</button>
            <button class="chart-modal-submit" id="extend-submit" onclick="submitExtend()">Продлить</button>
        </div>
    </div>
</div>

{{-- Mashup modal --}}
<div class="chart-modal-overlay" id="mashupModal">
    <div class="chart-modal">
        <div class="chart-modal-header"><h3>🔀 Мэшап</h3><button class="chart-modal-close" onclick="closeOpModal('mashupModal')">✕</button></div>
        <div class="chart-modal-body">
            <p class="chart-modal-desc">Смешаем этот трек с другим вашим треком. Спишется 1 песня.</p>
            <div class="chart-modal-section">
                <label>Второй трек:</label>
                <select id="mashup-song" class="op-input"><option value="">Загрузка...</option></select>
            </div>
            <div class="chart-modal-section">
                <label>Стиль (необязательно):</label>
                <input type="text" id="mashup-style" class="op-input" placeholder="например, lo-fi, dance" maxlength="200">
            </div>
        </div>
        <div class="chart-modal-footer">
            <button class="chart-modal-cancel" onclick="closeOpModal('mashupModal')">Отмена</button>
            <button class="chart-modal-submit" id="mashup-submit" onclick="submitMashup()">Смешать</button>
        </div>
    </div>
</div>

{{-- Replace section modal --}}
<div class="chart-modal-overlay" id="replaceModal">
    <div class="chart-modal">
        <div class="chart-modal-header"><h3>✏️ Заменить фрагмент</h3><button class="chart-modal-close" onclick="closeOpModal('replaceModal')">✕</button></div>
        <div class="chart-modal-body">
            <p class="chart-modal-desc">Перезапишем участок песни (от 6 до 60 сек). Отдельная функция.</p>
            <div class="chart-modal-section">
                <label>Вариант:</label>
                <div class="variant-buttons" id="replace-variants">
                    @if($song->audio_id_1)<button type="button" class="variant-btn active" data-variant="1" onclick="selectOpVariant('replace',1)">Вариант 1</button>@endif
                    @if($song->audio_id_2)<button type="button" class="variant-btn" data-variant="2" onclick="selectOpVariant('replace',2)">Вариант 2</button>@endif
                </div>
            </div>
            <div class="chart-modal-section" style="display:flex;gap:10px;">
                <div style="flex:1;"><label>Начало (сек):</label><input type="number" id="replace-start" class="op-input" min="0" step="0.01" placeholder="10.0"></div>
                <div style="flex:1;"><label>Конец (сек):</label><input type="number" id="replace-end" class="op-input" min="0" step="0.01" placeholder="25.0"></div>
            </div>
            <div class="chart-modal-section">
                <label>Стиль (теги):</label>
                <input type="text" id="replace-tags" class="op-input" placeholder="например, pop, energetic" maxlength="200">
            </div>
            <div class="chart-modal-section">
                <label>Что спеть в этом фрагменте:</label>
                <textarea id="replace-prompt" placeholder="Описание нового фрагмента" maxlength="5000"></textarea>
            </div>
            <div class="chart-modal-section">
                <label>Полный текст песни (с правкой):</label>
                <textarea id="replace-lyrics" maxlength="5000">{{ $song->lyrics }}</textarea>
            </div>
        </div>
        <div class="chart-modal-footer">
            <button class="chart-modal-cancel" onclick="closeOpModal('replaceModal')">Отмена</button>
            <button class="chart-modal-submit" id="replace-submit" onclick="submitReplace()">Заменить</button>
        </div>
    </div>
</div>
@endif

@if($song->lyrics)
<div class="lyrics-box">
    <h3 style="font-size:16px; margin-bottom:10px; font-weight:700;">📝 Текст песни</h3>
    {{ $song->lyrics }}
</div>
@endif

@if($song->user_id === $authUser->user_id)
<div style="text-align:center; margin-top:24px; padding-top:24px; border-top:1px solid var(--border);">
    <button onclick="deleteSong({{ $song->id }})" class="btn btn-danger">🗑 Удалить трек</button>
</div>
@endif
@endsection

@push('scripts')
<script>
    let currentChartType = null;
    let selectedVariant = 1;
    const songId = {{ $song->id }};

    function openChartModal(chartType) {
        currentChartType = chartType; selectedVariant = 1;
        const modal = document.getElementById('chartModal');
        const modalEl = modal.querySelector('.chart-modal');
        const title = document.getElementById('chartModalTitle');
        const desc = document.getElementById('chartModalDesc');
        const submitBtn = document.getElementById('chartModalSubmit');
        const comment = document.getElementById('modal-comment');
        comment.value = ''; document.getElementById('modal-comment-length').textContent = '0';
        document.querySelectorAll('#modal-variants .variant-btn').forEach((btn,i) => btn.classList.toggle('active', i===0));
        modalEl.classList.remove('valentine');
        if (chartType === 'valentine') {
            modalEl.classList.add('valentine');
            title.textContent = '💕 Песни о любви'; desc.textContent = 'Поделись историей любви — победители получат призы!';
            submitBtn.textContent = '💕 Добавить'; comment.placeholder = 'Расскажите историю этой песни...';
        } else {
            title.textContent = '🏆 Недельный чарт'; desc.textContent = 'Участвуйте в еженедельном соревновании. Топ-3 получают призы!';
            submitBtn.textContent = '🏆 Добавить'; comment.placeholder = 'Расскажите о песне...';
        }
        modal.classList.add('active'); document.body.style.overflow = 'hidden';
    }
    function closeChartModal() { document.getElementById('chartModal').classList.remove('active'); document.body.style.overflow = ''; currentChartType = null; }
    document.getElementById('chartModal').addEventListener('click', function(e) { if (e.target === this) closeChartModal(); });
    document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeChartModal(); });
    function selectModalVariant(v) { selectedVariant = v; document.querySelectorAll('#modal-variants .variant-btn').forEach(btn => btn.classList.toggle('active', parseInt(btn.dataset.variant)===v)); }
    document.getElementById('modal-comment').addEventListener('input', function() { document.getElementById('modal-comment-length').textContent = this.value.length; });

    async function submitToChart() {
        const comment = document.getElementById('modal-comment').value;
        const btn = document.getElementById('chartModalSubmit'); const orig = btn.textContent;
        btn.disabled = true; btn.textContent = 'Добавляю...';
        try {
            let url, body;
            if (currentChartType === 'valentine') { url = '/api/charts/theme/add-song'; body = {song_id:songId,theme:'valentine',variant:selectedVariant,comment}; }
            else { url = '/api/charts/add-song'; body = {song_id:songId,variant:selectedVariant,comment}; }
            const r = await fetch(url, {method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},body:JSON.stringify(body)});
            const d = await r.json();
            if (r.ok) { closeChartModal(); alert((currentChartType==='valentine'?'💕 ':'🎉 ')+d.message); window.location.reload(); }
            else { alert('❌ '+(d.error||'Ошибка')); btn.disabled=false; btn.textContent=orig; }
        } catch(e) { alert('Ошибка: '+e.message); btn.disabled=false; btn.textContent=orig; }
    }

    async function removeFromThemeChart(id,theme) {
        if(!confirm('Убрать из чарта?')) return;
        try { const r=await fetch('/api/charts/theme/remove-song',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},body:JSON.stringify({song_id:id,theme})}); if((await r.json()).success||r.ok) window.location.reload(); } catch(e){alert(e.message);}
    }
    async function removeFromChart(id) {
        if(!confirm('Убрать из чарта?')) return;
        try { const r=await fetch('/api/charts/remove-song',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},body:JSON.stringify({song_id:id})}); if((await r.json()).success||r.ok) window.location.reload(); } catch(e){alert(e.message);}
    }

    async function downloadSong(id,v) {
        const btn=document.getElementById(`dl-btn-${v}`); const orig=btn.textContent; btn.textContent='⏳'; btn.disabled=true;
        try { const r=await fetch(`/songs/${id}/download/${v}`,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'}}); const d=await r.json();
            if(d.success) { const tg=window.Telegram?.WebApp; tg?tg.openLink(d.download_url):(window.location.href=d.download_url); } else alert(d.error||'Ошибка');
        } catch(e){alert(e.message);} finally{btn.textContent=orig;btn.disabled=false;}
    }

    async function toggleFavorite(id,v) {
        const btn=document.querySelector(`.favorite-btn[data-variant="${v}"]`);
        try { const r=await fetch('/api/favorites/toggle',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},body:JSON.stringify({song_id:id,variant:v})}); if((await r.json()).success) btn.classList.toggle('active'); } catch(e){alert(e.message);}
    }

    async function deleteSong(id) {
        if(!confirm('Удалить трек?')) return;
        try { const r=await fetch(`/api/songs/${id}/delete`,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'}}); const d=await r.json();
            if(r.ok&&d.success) { alert('Трек удалён'); window.location.href='/songs'; } else alert(d.error||'Ошибка');
        } catch(e){alert(e.message);}
    }

    function startEditTitle() { document.getElementById('song-title').style.display='none'; document.getElementById('edit-title-form').style.display='flex'; const i=document.getElementById('title-input'); i.focus(); i.select(); }
    function cancelEditTitle() { document.getElementById('edit-title-form').style.display='none'; document.getElementById('song-title').style.display=''; }
    document.getElementById('title-input')?.addEventListener('keydown', function(e) { if(e.key==='Enter'){e.preventDefault();saveTitle();} if(e.key==='Escape') cancelEditTitle(); });
    async function saveTitle() {
        const t=document.getElementById('title-input').value.trim(); if(!t){alert('Название пустое');return;}
        try { const r=await fetch(`/api/songs/${songId}/update-title`,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},body:JSON.stringify({title:t})}); const d=await r.json();
            if(r.ok&&d.success) { document.getElementById('song-title').childNodes[0].textContent=d.title+' '; cancelEditTitle(); } else alert(d.error||'Ошибка');
        } catch(e){alert(e.message);}
    }

    // === STEM AUDIO ===
    let stemAudio = null; let stemActiveBtn = null;
    window.stopStemAudio = function() { if(stemAudio&&!stemAudio.paused){stemAudio.pause();resetStemBtnState(stemActiveBtn);stemActiveBtn=null;} };
    function handleStemPlay(btn) {
        const url=btn.dataset.url;
        if(window.player&&typeof window.player.stopForExternal==='function') window.player.stopForExternal();
        if(!stemAudio){stemAudio=new Audio();stemAudio.addEventListener('ended',()=>{resetStemBtnState(stemActiveBtn);stemActiveBtn=null;});stemAudio.addEventListener('error',()=>{resetStemBtnState(stemActiveBtn);stemActiveBtn=null;});}
        if(stemActiveBtn===btn&&!stemAudio.paused){stemAudio.pause();resetStemBtnState(btn);stemActiveBtn=null;return;}
        if(stemActiveBtn&&stemActiveBtn!==btn) resetStemBtnState(stemActiveBtn);
        stemActiveBtn=btn;
        if(stemAudio.src===url&&stemAudio.currentTime>0){stemAudio.play();setStemBtnPlaying(btn);return;}
        setStemBtnLoading(btn);stemAudio.src=url;stemAudio.load();
        stemAudio.oncanplay=function(){stemAudio.oncanplay=null;stemAudio.play();setStemBtnPlaying(btn);};
    }
    function setStemBtnPlaying(b){b.textContent='⏸';b.classList.add('active');}
    function setStemBtnLoading(b){b.textContent='⏳';b.classList.add('active');}
    function resetStemBtnState(b){if(!b)return;b.textContent='▶';b.classList.remove('active');}
    function reinitStemPlayButtons(c){if(!c)return;c.querySelectorAll('.stem-play-btn').forEach(btn=>{btn.removeEventListener('click',btn._h);btn._h=function(e){e.preventDefault();e.stopPropagation();handleStemPlay(this);};btn.addEventListener('click',btn._h);});}
    document.addEventListener('DOMContentLoaded',function(){document.querySelectorAll('.stem-section').forEach(s=>reinitStemPlayButtons(s));});

    const stemRunning = {};
    async function startStemSeparation(songId, variant) {
        if(stemRunning[variant]) return; stemRunning[variant]=true;
        const btn=document.getElementById(`stem-btn-${variant}`); const loading=document.getElementById(`stem-loading-${variant}`);
        const results=document.getElementById(`stem-results-${variant}`); const errorEl=document.getElementById(`stem-error-${variant}`);
        btn.style.display='none'; loading.classList.add('show'); if(errorEl) errorEl.textContent='';
        try {
            const r=await fetch('/api/stems/separate',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},credentials:'same-origin',body:JSON.stringify({song_id:songId,variant})});
            const d=await r.json(); loading.classList.remove('show');
            if(!r.ok||!d.success) throw new Error(d.error||'Ошибка');
            const title='Трек'; let html='';
            if(d.instrumental_url) html+=`<div class="stem-file"><div class="stem-file-info"><span class="stem-file-icon">🎹</span><span class="stem-file-name">Минусовка</span></div><div class="stem-file-actions"><button class="stem-play-btn" data-url="${d.instrumental_url}" data-title="${title} (Inst)" data-author="На Репите">▶</button><a href="${d.instrumental_url}" download class="stem-download-btn">⬇</a></div></div>`;
            if(d.vocal_url) html+=`<div class="stem-file"><div class="stem-file-info"><span class="stem-file-icon">🎤</span><span class="stem-file-name">Голос</span></div><div class="stem-file-actions"><button class="stem-play-btn" data-url="${d.vocal_url}" data-title="${title} (Vocals)" data-author="На Репите">▶</button><a href="${d.vocal_url}" download class="stem-download-btn">⬇</a></div></div>`;
            results.innerHTML=html; results.classList.add('show'); reinitStemPlayButtons(results);
        } catch(e){loading.classList.remove('show');btn.style.display='';if(errorEl)errorEl.textContent='❌ '+e.message;}
        finally{stemRunning[variant]=false;}
    }

    @if(!$song->file_path && $song->suno_task_id)
    let checkCount=0;
    async function checkGenerationStatus(){
        try{const r=await fetch('/api/generate/status?task_id={{ $song->suno_task_id }}&song_id={{ $song->id }}',{headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'}});const d=await r.json();
            if(d.status==='completed') window.location.reload(); else if(d.status!=='failed'&&++checkCount<60) setTimeout(checkGenerationStatus,5000);
        }catch(e){if(++checkCount<60) setTimeout(checkGenerationStatus,5000);}
    }
    setTimeout(checkGenerationStatus,3000);
    @endif

    async function fetchCover(id) {
        const btn = document.getElementById('cover-btn');
        btn.disabled = true; btn.textContent = '⏳ Загружаю...';
        try {
            const r = await fetch('/api/song/cover', {
                method: 'POST',
                headers: {'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
                body: JSON.stringify({song_id: id})
            });
            const d = await r.json();
            if (r.ok && d.cover_url) {
                document.getElementById('song-cover').innerHTML = `<img src="${d.cover_url}" alt="Обложка" style="width:100%;height:100%;object-fit:cover;border-radius:var(--radius-xl);">`;
                btn.style.display = 'none';
            } else {
                btn.textContent = '❌ ' + (d.error || 'Ошибка');
                setTimeout(() => { btn.textContent = '🖼 Получить обложку'; btn.disabled = false; }, 3000);
            }
        } catch(e) { btn.textContent = '❌ ' + e.message; btn.disabled = false; }
    }

    // ===== PERSONA =====
    function openPersonaModal(audioId, variant) {
        document.getElementById('persona-audio-id').value = audioId;
        document.getElementById('persona-name').value = '';
        document.getElementById('persona-desc').value = '';
        document.getElementById('persona-style').value = '';
        document.getElementById('personaModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closePersonaModal() {
        document.getElementById('personaModal').classList.remove('active');
        document.body.style.overflow = '';
    }

    document.getElementById('personaModal')?.addEventListener('click', function(e) { if (e.target === this) closePersonaModal(); });

    async function submitPersona() {
        var name = document.getElementById('persona-name').value.trim();
        var desc = document.getElementById('persona-desc').value.trim();
        if (!name || !desc) { alert('Заполните название и описание'); return; }

        var btn = document.getElementById('persona-submit-btn');
        btn.disabled = true; btn.textContent = '⏳ Создаю...';

        try {
            var r = await fetch('/api/persona/create', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                credentials: 'same-origin',
                body: JSON.stringify({
                    song_id: songId,
                    audio_id: document.getElementById('persona-audio-id').value,
                    name: name,
                    description: desc,
                    style: document.getElementById('persona-style').value.trim()
                })
            });
            var d = await r.json();
            if (r.ok && d.success) {
                closePersonaModal();
                alert('✅ Персона «' + d.persona.name + '» создана!');
            } else {
                alert('❌ ' + (d.error || 'Ошибка'));
                btn.disabled = false; btn.textContent = 'Создать';
            }
        } catch(e) { alert('Ошибка: ' + e.message); btn.disabled = false; btn.textContent = 'Создать'; }
    }

    // ===== TRACK OPERATIONS (extend / mashup / replace) =====
    const opVariant = { extend: 1, replace: 1 };
    function selectOpVariant(kind, v) {
        opVariant[kind] = v;
        document.querySelectorAll(`#${kind}-variants .variant-btn`).forEach(b => b.classList.toggle('active', parseInt(b.dataset.variant) === v));
    }
    function openOpModal(id) { document.getElementById(id).classList.add('active'); document.body.style.overflow = 'hidden'; }
    function closeOpModal(id) { document.getElementById(id).classList.remove('active'); document.body.style.overflow = ''; }
    ['extendModal','mashupModal','replaceModal'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('click', e => { if (e.target === el) closeOpModal(id); });
    });

    async function runTrackOp(url, body, btn) {
        const orig = btn.textContent; btn.disabled = true; btn.textContent = '⏳ Запускаю...';
        try {
            const r = await fetch(url, {
                method:'POST',
                headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
                credentials:'same-origin',
                body: JSON.stringify(body)
            });
            const d = await r.json();
            if (r.ok && d.success) {
                window.location.href = '/songs/' + d.song_id;
            } else {
                alert('❌ ' + (d.error || 'Ошибка'));
                btn.disabled = false; btn.textContent = orig;
            }
        } catch(e) { alert('Ошибка: ' + e.message); btn.disabled = false; btn.textContent = orig; }
    }

    function openExtendModal() { openOpModal('extendModal'); }
    function submitExtend() {
        const continueAt = document.getElementById('extend-continue').value;
        const body = { song_id: songId, variant: opVariant.extend };
        if (continueAt !== '') body.continue_at = parseFloat(continueAt);
        const prompt = document.getElementById('extend-prompt').value.trim();
        if (prompt) body.prompt = prompt;
        runTrackOp('/api/track-ops/extend', body, document.getElementById('extend-submit'));
    }

    function openReplaceModal() { openOpModal('replaceModal'); }
    function submitReplace() {
        const start = parseFloat(document.getElementById('replace-start').value);
        const end = parseFloat(document.getElementById('replace-end').value);
        const tags = document.getElementById('replace-tags').value.trim();
        const prompt = document.getElementById('replace-prompt').value.trim();
        const lyrics = document.getElementById('replace-lyrics').value.trim();
        if (isNaN(start) || isNaN(end)) { alert('Укажите начало и конец фрагмента'); return; }
        if (end - start < 6 || end - start > 60) { alert('Фрагмент должен быть от 6 до 60 секунд'); return; }
        if (!tags || !prompt || !lyrics) { alert('Заполните стиль, описание фрагмента и текст'); return; }
        runTrackOp('/api/track-ops/replace-section', {
            song_id: songId, variant: opVariant.replace,
            infill_start_s: start, infill_end_s: end,
            tags, prompt, full_lyrics: lyrics
        }, document.getElementById('replace-submit'));
    }

    let mashupLoaded = false;
    async function openMashupModal() {
        openOpModal('mashupModal');
        if (mashupLoaded) return;
        const sel = document.getElementById('mashup-song');
        try {
            const r = await fetch('/api/songs', { headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'}, credentials:'same-origin' });
            const d = await r.json();
            const others = (d.songs || []).filter(s => s.id !== songId && s.audio_url_1);
            if (!others.length) { sel.innerHTML = '<option value="">Нет других треков</option>'; return; }
            sel.innerHTML = others.map(s => `<option value="${s.id}">${(s.title||'Без названия').replace(/</g,'&lt;')}</option>`).join('');
            mashupLoaded = true;
        } catch(e) { sel.innerHTML = '<option value="">Ошибка загрузки</option>'; }
    }
    function submitMashup() {
        const other = document.getElementById('mashup-song').value;
        if (!other) { alert('Выберите второй трек'); return; }
        const body = { song_ids: [songId, parseInt(other)] };
        const style = document.getElementById('mashup-style').value.trim();
        if (style) body.style = style;
        runTrackOp('/api/track-ops/mashup', body, document.getElementById('mashup-submit'));
    }
</script>
@endpush