@extends('layouts.app')

@section('title', ($article->id ? 'Редактирование' : 'Новая статья') . ' — Админ')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/trumbowyg@2.27.3/dist/ui/trumbowyg.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/trumbowyg@2.27.3/dist/plugins/upload/ui/trumbowyg.upload.min.css">
<style>
    .form-section {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        padding: 20px;
        margin-bottom: 16px;
        box-shadow: var(--shadow-xs);
    }
    .form-section h3 {
        font-size: 15px;
        font-weight: 700;
        margin-bottom: 14px;
        color: var(--text-primary);
    }
    .form-section-toggle {
        cursor: pointer;
        user-select: none;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .form-section-toggle::after {
        content: '▼';
        font-size: 12px;
        color: var(--text-tertiary);
        transition: transform 0.2s;
    }
    .form-section-toggle.collapsed::after { transform: rotate(-90deg); }
    .form-section-body.collapsed { display: none; }

    .form-row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    @media (max-width: 600px) { .form-row-2 { grid-template-columns: 1fr; } }

    .checkbox-row { display: flex; align-items: center; gap: 10px; margin: 12px 0; }
    .checkbox-row input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; }
    .checkbox-row label { cursor: pointer; font-size: 14px; }

    .field-hint { font-size: 12px; color: var(--text-tertiary); margin-top: 4px; }
    .field-error { font-size: 12px; color: var(--danger); margin-top: 4px; }

    /* Quill editor */
    #editor-wrap { background: white; border-radius: var(--radius-md); }
    #editor { min-height: 400px; }
    .ql-toolbar.ql-snow { border-top-left-radius: var(--radius-md); border-top-right-radius: var(--radius-md); border-color: var(--border); }
    .ql-container.ql-snow { border-bottom-left-radius: var(--radius-md); border-bottom-right-radius: var(--radius-md); border-color: var(--border); font-size: 15px; }

    .form-actions {
        display: flex;
        gap: 12px;
        margin-top: 20px;
        flex-wrap: wrap;
    }
    .form-actions .btn { flex: 1; min-width: 140px; }

    .char-counter { font-size: 11px; color: var(--text-tertiary); text-align: right; margin-top: 4px; }
    /* Cover upload */
    .cover-upload-box {
        position: relative;
        border: 2px dashed var(--border-strong);
        border-radius: var(--radius-md);
        background: var(--bg-input);
        cursor: pointer;
        transition: all 0.2s ease;
        overflow: hidden;
        min-height: 180px;
    }
    .cover-upload-box:hover { border-color: var(--accent); background: var(--accent-soft); }
    .cover-upload-box.dragover { border-color: var(--accent); background: var(--accent-soft); }

    .cover-upload-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 40px 20px;
        text-align: center;
        pointer-events: none;
    }
    .cover-upload-icon { font-size: 42px; margin-bottom: 8px; opacity: 0.6; }
    .cover-upload-text { font-size: 14px; font-weight: 600; color: var(--text-secondary); margin-bottom: 4px; }
    .cover-upload-hint { font-size: 12px; color: var(--text-tertiary); }

    .cover-preview {
        position: relative;
        width: 100%;
        max-height: 300px;
        overflow: hidden;
    }
    .cover-preview img {
        width: 100%;
        height: auto;
        max-height: 300px;
        object-fit: cover;
        display: block;
    }
    .cover-remove-btn {
        position: absolute;
        top: 10px; right: 10px;
        width: 32px; height: 32px;
        border-radius: 50%;
        background: rgba(0, 0, 0, 0.7);
        color: white;
        border: none;
        cursor: pointer;
        font-size: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .cover-remove-btn:hover { background: var(--danger); }

    .cover-upload-progress {
        margin-top: 8px;
        padding: 10px;
        background: var(--accent-soft);
        color: var(--accent);
        border-radius: var(--radius-sm);
        font-size: 13px;
        text-align: center;
    }
    .cover-upload-error {
        margin-top: 8px;
        padding: 10px;
        background: var(--danger-soft);
        color: var(--danger);
        border-radius: var(--radius-sm);
        font-size: 13px;
    }
    .related-select {
        max-height: 260px;
        overflow-y: auto;
        border: 1.5px solid var(--border);
        border-radius: var(--radius-md);
        background: var(--bg-input);
        padding: 8px;
    }
    .related-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 10px;
        border-radius: var(--radius-sm);
        cursor: pointer;
        transition: background 0.15s;
    }
    .related-item:hover { background: var(--surface-glass); }
    .related-item input[type="checkbox"] { width: 16px; height: 16px; cursor: pointer; flex-shrink: 0; }
    .related-item label { cursor: pointer; font-size: 13px; flex: 1; }
    .related-search {
        width: 100%;
        padding: 10px 12px;
        border: 1.5px solid var(--border);
        border-radius: var(--radius-md);
        background: var(--bg-input);
        margin-bottom: 8px;
        font-size: 13px;
    }
    /* === Block Builder === */
    .blocks-list {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }
    .block-item {
        border: 1.5px solid var(--border);
        border-radius: var(--radius-md);
        background: var(--bg-input);
        padding: 16px;
        position: relative;
    }
    .block-item-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 12px;
        padding-bottom: 10px;
        border-bottom: 1px solid var(--border);
    }
    .block-type-label {
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--accent);
    }
    .block-actions {
        display: flex;
        gap: 6px;
    }
    .block-actions button {
        width: 28px;
        height: 28px;
        border-radius: var(--radius-sm);
        border: none;
        background: var(--bg-card);
        cursor: pointer;
        font-size: 13px;
        transition: all 0.15s;
        color: var(--text-secondary);
    }
    .block-actions button:hover { background: var(--surface-glass-hover); color: var(--text-primary); }
    .block-actions .btn-delete-block { color: var(--danger); }
    .block-actions .btn-delete-block:hover { background: var(--danger); color: white; }

    .block-editor {
        background: white;
        border-radius: var(--radius-sm);
        min-height: 150px;
    }
    .block-editor .ql-toolbar.ql-snow {
        border-top-left-radius: var(--radius-sm);
        border-top-right-radius: var(--radius-sm);
        border-color: var(--border);
    }
    .block-editor .ql-container.ql-snow {
        border-bottom-left-radius: var(--radius-sm);
        border-bottom-right-radius: var(--radius-sm);
        border-color: var(--border);
        font-size: 14px;
        min-height: 120px;
    }

    .add-block-panel {
        margin-top: 14px;
        padding: 14px;
        border: 1.5px dashed var(--border-strong);
        border-radius: var(--radius-md);
        text-align: center;
    }
    .add-block-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 18px;
        background: var(--accent-soft);
        color: var(--accent);
        border: none;
        border-radius: var(--radius-md);
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s;
    }
    .add-block-btn:hover { background: var(--accent); color: white; }

    .blocks-empty {
        text-align: center;
        padding: 30px 20px;
        color: var(--text-tertiary);
        font-size: 13px;
    }
    /* === Block Builder extra === */
.block-item-inner {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.block-item .form-input,
.block-item input[type="text"]:not(.selector-search),
.block-item .selector-search {
    width: 100%;
    padding: 10px 12px;
    border: 1.5px solid var(--border);
    border-radius: var(--radius-sm);
    background: var(--bg-card);
    font-size: 14px;
    font-family: inherit;
    color: var(--text-primary);
    box-sizing: border-box;
}
.block-item .form-input:focus,
.block-item input[type="text"]:focus {
    outline: none;
    border-color: var(--accent);
}

.block-field-label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: var(--text-secondary);
    margin-bottom: 4px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.block-image-preview {
    margin-top: 8px;
}
.block-image-preview img {
    max-width: 100%;
    max-height: 260px;
    border-radius: var(--radius-sm);
    border: 1px solid var(--border);
    display: block;
}
.block-image-file {
    display: block;
    font-size: 13px;
    color: var(--text-secondary);
}

.selector-list {
    max-height: 240px;
    overflow-y: auto;
    border: 1.5px solid var(--border);
    border-radius: var(--radius-sm);
    background: var(--bg-card);
    padding: 6px;
}
.selector-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 10px;
    border-radius: var(--radius-sm);
    cursor: pointer;
    font-size: 13px;
    color: var(--text-primary);
    transition: background 0.15s;
}
.selector-item:hover { background: var(--surface-glass); }
.selector-item input[type="checkbox"] {
    width: 16px;
    height: 16px;
    cursor: pointer;
    flex-shrink: 0;
}

.blocks-toolbar {
    margin-top: 14px;
    padding: 14px;
    border: 1.5px dashed var(--border-strong);
    border-radius: var(--radius-md);
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    justify-content: center;
}
.blocks-toolbar .btn-secondary {
    padding: 8px 14px;
    background: var(--accent-soft);
    color: var(--accent);
    border: none;
    border-radius: var(--radius-sm);
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s;
}
.blocks-toolbar .btn-secondary:hover {
    background: var(--accent);
    color: white;
}

.block-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
    padding-bottom: 10px;
    border-bottom: 1px solid var(--border);
}
.btn-danger-sm {
    padding: 4px 10px !important;
    width: auto !important;
    color: var(--danger) !important;
}
.btn-danger-sm:hover {
    background: var(--danger) !important;
    color: white !important;
}

.mini-editor-wrap {
    background: white;
    border-radius: var(--radius-sm);
}
.mini-editor {
    min-height: 120px;
}
.block-mini-editor {
    width: 100%;
    min-height: 200px;
    font-family: monospace;
    font-size: 13px;
}
.mini-editor-placeholder {
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    background: white;
    padding: 16px;
    cursor: text;
    min-height: 100px;
    position: relative;
    transition: border-color 0.15s;
}
.mini-editor-placeholder:hover {
    border-color: var(--accent);
}
.mini-editor-preview {
    font-size: 14px;
    color: #333;
    line-height: 1.5;
    margin-bottom: 12px;
}
.mini-editor-edit-btn {
    padding: 6px 12px;
    font-size: 12px;
    background: var(--accent-soft);
    color: var(--accent);
    border: none;
    border-radius: var(--radius-sm);
    cursor: pointer;
}
.block-mini-editor-wrap { background: white; border-radius: var(--radius-sm); }
    .trumbowyg-box { margin: 0; min-height: 200px; }
    .trumbowyg-editor { min-height: 160px; }
</style>
@endpush

@section('content')
<div style="max-width: 900px; margin: 0 auto;">
    <div style="display:flex; align-items:center; gap: 12px; margin-bottom: 20px;">
        <a href="{{ route('admin.articles.index') }}" style="color: var(--text-tertiary); font-size: 14px;">← Назад</a>
        <h2 style="font-size: 22px; font-weight: 800; flex: 1;">
            {{ $article->id ? 'Редактирование статьи' : 'Новая статья' }}
        </h2>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-error">
            @foreach($errors->all() as $err) {{ $err }}<br> @endforeach
        </div>
    @endif

    <form method="POST" action="{{ $article->id ? route('admin.articles.update', $article->id) : route('admin.articles.store') }}" id="article-form">
        @csrf

        <!-- ОСНОВНОЕ -->
        <!-- ОСНОВНОЕ -->
        <div class="form-section">
            <h3>Основное</h3>

            <div class="form-group">
                <label class="form-label">Заголовок *</label>
                <input type="text" name="title" class="form-input" id="title-input"
                       value="{{ old('title', $article->title) }}" required maxlength="500">
            </div>

            <div class="form-group">
                <label class="form-label">Слаг (URL)</label>
                <input type="text" name="slug" class="form-input" id="slug-input"
                       value="{{ old('slug', $article->slug) }}" maxlength="255">
                <div class="field-hint">Оставьте пустым — сгенерируется автоматически из заголовка</div>
                @error('slug') <div class="field-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Краткое описание (excerpt)</label>
                <textarea name="excerpt" class="form-textarea" rows="3" maxlength="500">{{ old('excerpt', $article->excerpt) }}</textarea>
                <div class="field-hint">Отображается в списке статей и используется для SEO если не задан seo_description</div>
            </div>

            <div class="form-row-2">
                <input type="hidden" name="cover_thumb_url" value="{{ old('cover_thumb_url', $article->cover_thumb_url) }}">
                <!-- Обложка (для списка) -->
                <div class="form-group">
                    <label class="form-label">Обложка (для списка)</label>
                    <input type="hidden" name="cover_url" id="cover-url-input" value="{{ old('cover_url', $article->cover_url) }}">

                    <div class="cover-upload-box" id="cover-upload-box">
                        <div class="cover-preview" id="cover-preview" style="{{ old('cover_url', $article->cover_url) ? '' : 'display:none;' }}">
                            <img src="{{ old('cover_url', $article->cover_url) }}" alt="" id="cover-preview-img">
                            <button type="button" class="cover-remove-btn" onclick="removeImage('cover')">✕</button>
                        </div>
                        <div class="cover-upload-empty" id="cover-upload-empty" style="{{ old('cover_url', $article->cover_url) ? 'display:none;' : '' }}">
                            <!-- <div class="cover-upload-icon">🖼</div> -->
                            <div class="cover-upload-text">Выбрать файл</div>
                            <!-- <div class="cover-upload-hint">До 5 MB</div> -->
                        </div>
                        <input type="file" id="cover-file-input" accept="image/jpeg,image/png,image/webp" style="display:none;">
                    </div>
                    <div class="cover-upload-progress" id="cover-upload-progress" style="display:none;">Загрузка...</div>
                    <div class="cover-upload-error" id="cover-upload-error" style="display:none;"></div>
                </div>

                <!-- Баннер (для страницы статьи) -->
                <div class="form-group">
                    <label class="form-label">Баннер (для страницы статьи)</label>
                    <input type="hidden" name="banner_url" id="banner-url-input" value="{{ old('banner_url', $article->banner_url) }}">

                    <div class="cover-upload-box" id="banner-upload-box">
                        <div class="cover-preview" id="banner-preview" style="{{ old('banner_url', $article->banner_url) ? '' : 'display:none;' }}">
                            <img src="{{ old('banner_url', $article->banner_url) }}" alt="" id="banner-preview-img">
                            <button type="button" class="cover-remove-btn" onclick="removeImage('banner')">✕</button>
                        </div>
                        <div class="cover-upload-empty" id="banner-upload-empty" style="{{ old('banner_url', $article->banner_url) ? 'display:none;' : '' }}">
                            <!-- <div class="cover-upload-icon">🎨</div> -->
                            <div class="cover-upload-text">Выбрать файл</div>
                            <!-- <div class="cover-upload-hint">Широкое изображение</div> -->
                        </div>
                        <input type="file" id="banner-file-input" accept="image/jpeg,image/png,image/webp" style="display:none;">
                    </div>
                    <div class="cover-upload-progress" id="banner-upload-progress" style="display:none;">Загрузка...</div>
                    <div class="cover-upload-error" id="banner-upload-error" style="display:none;"></div>
                </div>
            </div>

            <div class="form-group" style="display:none;">
                <label class="form-label">Время чтения (минут)</label>
                <input type="number" name="reading_time" class="form-input" min="1" max="999"
                       value="{{ old('reading_time', $article->reading_time) }}" placeholder="Например: 6">
                <div class="field-hint">Оставьте пустым — рассчитается автоматически из контента</div>
            </div>

            <div class="checkbox-row">
                <input type="checkbox" name="is_published" id="is_published" value="1"
                    {{ old('is_published', $article->is_published) ? 'checked' : '' }}>
                <label for="is_published">Опубликовано</label>
            </div>
        </div>

        <!-- КОНТЕНТ -->
        <!-- <div class="form-section">
            <h3>Контент</h3>
            <div id="editor-wrap">
                <div id="editor">{!! old('content_html', $article->content_html) !!}</div>
            </div>
            <input type="hidden" name="content_html" id="content-html-input">
        </div> -->
        <!-- ДОПОЛНИТЕЛЬНЫЕ БЛОКИ -->
        <div class="form-section">
            <h3 class="form-section-toggle" onclick="toggleSection(this)">Дополнительные блоки</h3>
            <div class="form-section-body">
                <div class="field-hint" style="margin-bottom: 14px;">
                    Блоки отображаются после основного контента на странице статьи
                </div>

                <div class="blocks-list" id="blocks-list">
                    <div class="blocks-empty" id="blocks-empty">Блоков пока нет</div>
                </div>

                <div class="blocks-toolbar" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;">
                    <button type="button" onclick="window.addBlock('gradient_text')" class="btn-secondary">+ Текст с подложкой</button>
                    <button type="button" onclick="window.addBlock('image_full')" class="btn-secondary">+ Изображение</button>
                    <button type="button" onclick="window.addBlock('songs_list')" class="btn-secondary">+ Блок с песнями</button>
                    <button type="button" onclick="window.addBlock('articles_list')" class="btn-secondary">+ Блок со статьями</button>
                </div>
            </div>
        </div>

        <!-- СВЯЗАННЫЕ СТАТЬИ -->
        <div class="form-section">
            <h3 class="form-section-toggle" onclick="toggleSection(this)">Связанные статьи</h3>
            <div class="form-section-body">
                @php
                    $selectedRelated = old('related_ids', $article->related_ids ?? []);
                    if (!is_array($selectedRelated)) $selectedRelated = [];
                @endphp

                @if($allArticles->isEmpty())
                    <div style="color: var(--text-tertiary); font-size: 13px;">Других статей пока нет</div>
                @else
                    <input type="text" class="related-search" id="related-search" placeholder="Поиск по названию...">
                    <div class="related-select" id="related-select">
                        @foreach($allArticles as $item)
                            <div class="related-item" data-title="{{ mb_strtolower($item->title) }}">
                                <input type="checkbox" name="related_ids[]" value="{{ $item->id }}"
                                    id="rel-{{ $item->id }}"
                                    {{ in_array($item->id, $selectedRelated) ? 'checked' : '' }}>
                                <label for="rel-{{ $item->id }}">{{ $item->title }}</label>
                            </div>
                        @endforeach
                    </div>
                    <div class="field-hint">Отмеченные статьи будут показаны в слайдере «Читайте также»</div>
                @endif
            </div>
        </div>

        <!-- SEO -->
        <div class="form-section">
            <h3 class="form-section-toggle" onclick="toggleSection(this)">SEO настройки</h3>
            <div class="form-section-body">
                <div class="form-group">
                    <label class="form-label">SEO Title</label>
                    <input type="text" name="seo_title" class="form-input" id="seo-title-input"
                           value="{{ old('seo_title', $article->seo_title) }}" maxlength="500">
                    <div class="char-counter"><span id="seo-title-count">0</span>/70 рекомендуется</div>
                    <div class="field-hint">Если пусто — используется заголовок статьи</div>
                </div>

                <div class="form-group">
                    <label class="form-label">SEO Description</label>
                    <textarea name="seo_description" class="form-textarea" rows="3" id="seo-desc-input">{{ old('seo_description', $article->seo_description) }}</textarea>
                    <div class="char-counter"><span id="seo-desc-count">0</span>/160 рекомендуется</div>
                </div>

                <div class="form-group">
                    <label class="form-label">SEO Keywords</label>
                    <input type="text" name="seo_keywords" class="form-input"
                           value="{{ old('seo_keywords', $article->seo_keywords) }}"
                           placeholder="ключевые, слова, через, запятую">
                </div>

                <!-- <div class="form-group">
                    <label class="form-label">OG Image (для соцсетей)</label>
                    <input type="text" name="og_image" class="form-input"
                           value="{{ old('og_image', $article->og_image) }}" placeholder="https://...">
                    <div class="field-hint">Рекомендуемый размер: 1200×630</div>
                </div>

                <div class="form-group">
                    <label class="form-label">Canonical URL</label>
                    <input type="text" name="canonical_url" class="form-input"
                           value="{{ old('canonical_url', $article->canonical_url) }}" placeholder="https://...">
                </div> -->

                <div class="checkbox-row">
                    <input type="checkbox" name="noindex" id="noindex" value="1"
                        {{ old('noindex', $article->noindex) ? 'checked' : '' }}>
                    <label for="noindex">noindex, nofollow (скрыть от поисковиков)</label>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <a href="{{ route('admin.articles.index') }}" class="btn btn-secondary">Отмена</a>
            <button type="submit" class="btn btn-primary">💾 Сохранить</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/trumbowyg@2.27.3/dist/trumbowyg.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/trumbowyg@2.27.3/dist/langs/ru.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/trumbowyg@2.27.3/dist/plugins/upload/trumbowyg.upload.min.js"></script>
<script>

document.addEventListener('DOMContentLoaded', function() {

    document.getElementById('article-form').addEventListener('submit', function(e) {


        // Process all blocks before submit
        document.querySelectorAll('#blocks-list .block-item').forEach(function(wrap) {
            if (wrap._trumbowyg) {
                var htmlInput = wrap.querySelector('.block-html-input');
                if (htmlInput) htmlInput.value = wrap._trumbowyg.trumbowyg('html');
            }
            // song_ids / article_ids: CSV → массив hidden-полей
            var idsInput = wrap.querySelector('.block-ids-input');
            if (idsInput) {
                var csv = idsInput.value;
                var ids = csv ? csv.split(',').filter(function(x){ return x; }) : [];
                var name = idsInput.name; // blocks[X][data][song_ids]
                var parent = idsInput.parentNode;
                idsInput.remove();
                ids.forEach(function(id) {
                    var h = document.createElement('input');
                    h.type = 'hidden';
                    h.name = name + '[]';
                    h.value = id;
                    parent.appendChild(h);
                });
            }
        });
    });

    // Slug auto-generation
    var titleInput = document.getElementById('title-input');
    var slugInput = document.getElementById('slug-input');
    var slugEdited = {{ $article->id ? 'true' : 'false' }};

    slugInput.addEventListener('input', function() { slugEdited = true; });
    titleInput.addEventListener('input', function() {
        if (!slugEdited) slugInput.value = transliterate(this.value);
    });

    function transliterate(text) {
        var map = {'а':'a','б':'b','в':'v','г':'g','д':'d','е':'e','ё':'yo','ж':'zh','з':'z','и':'i','й':'y','к':'k','л':'l','м':'m','н':'n','о':'o','п':'p','р':'r','с':'s','т':'t','у':'u','ф':'f','х':'h','ц':'ts','ч':'ch','ш':'sh','щ':'sch','ъ':'','ы':'y','ь':'','э':'e','ю':'yu','я':'ya'};
        return text.toLowerCase().split('').map(function(c){ return map[c] !== undefined ? map[c] : c; }).join('').replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').substring(0, 100);
    }

    // SEO counters
    function bindCounter(inputId, counterId) {
        var input = document.getElementById(inputId);
        var counter = document.getElementById(counterId);
        var update = function() { counter.textContent = input.value.length; };
        input.addEventListener('input', update);
        update();
    }
    bindCounter('seo-title-input', 'seo-title-count');
    bindCounter('seo-desc-input', 'seo-desc-count');

    window.toggleSection = function(header) {
        header.classList.toggle('collapsed');
        header.nextElementSibling.classList.toggle('collapsed');
    };

    // === Image upload ===
    function initImageUpload(type) {
        var box = document.getElementById(type + '-upload-box');
        var input = document.getElementById(type + '-file-input');
        var urlInput = document.getElementById(type + '-url-input');
        var preview = document.getElementById(type + '-preview');
        var previewImg = document.getElementById(type + '-preview-img');
        var empty = document.getElementById(type + '-upload-empty');
        var progress = document.getElementById(type + '-upload-progress');
        var error = document.getElementById(type + '-upload-error');

        box.addEventListener('click', function() { input.click(); });
        box.addEventListener('dragover', function(e) { e.preventDefault(); box.classList.add('dragover'); });
        box.addEventListener('dragleave', function() { box.classList.remove('dragover'); });
        box.addEventListener('drop', function(e) {
            e.preventDefault();
            box.classList.remove('dragover');
            if (e.dataTransfer.files.length) {
                input.files = e.dataTransfer.files;
                uploadFile(e.dataTransfer.files[0]);
            }
        });
        input.addEventListener('change', function(e) {
            if (e.target.files.length) uploadFile(e.target.files[0]);
        });

        function uploadFile(file) {
            error.style.display = 'none';
            progress.style.display = 'block';
            progress.textContent = 'Загрузка...';

            var formData = new FormData();
            formData.append('image', file);
            formData.append('type', type);
            formData.append('_token', '{{ csrf_token() }}');

            fetch('/admin/articles/upload-image', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
            }).then(function(r) { return r.json().then(function(d){ return {ok: r.ok, d: d}; }); })

            .then(function(res) {
                if (!res.ok || !res.d.success) throw new Error(res.d.error || 'Ошибка загрузки');
                urlInput.value = res.d.url;
                if (res.d.thumb_url) {
                    var thumbInput = document.querySelector('[name="cover_thumb_url"]');
                    if (thumbInput) thumbInput.value = res.d.thumb_url;
                }
                previewImg.src = res.d.thumb_url || res.d.url;
                preview.style.display = 'block';
                empty.style.display = 'none';
                progress.style.display = 'none';
            }).catch(function(e) {
                progress.style.display = 'none';
                error.textContent = '❌ ' + e.message;
                error.style.display = 'block';
            });
        }
    }
    initImageUpload('cover');
    initImageUpload('banner');

    window.removeImage = function(type) {
        document.getElementById(type + '-url-input').value = '';
        document.getElementById(type + '-preview-img').src = '';
        document.getElementById(type + '-preview').style.display = 'none';
        document.getElementById(type + '-upload-empty').style.display = 'flex';
        document.getElementById(type + '-file-input').value = '';
    };

    // Related search
    var relatedSearch = document.getElementById('related-search');
    if (relatedSearch) {
        relatedSearch.addEventListener('input', function() {
            var query = this.value.toLowerCase().trim();
            document.querySelectorAll('#related-select .related-item').forEach(function(item) {
                item.style.display = item.dataset.title.indexOf(query) !== -1 ? 'flex' : 'none';
            });
        });
    }

    // === BLOCKS BUILDER ===
    var blocksContainer = document.getElementById('blocks-list');
    var blocksEmpty = document.getElementById('blocks-empty');
    var blockCounter = 0;
    var blockEditors = {};

    window.allSongsData = @json($allSongs ?? []);
    window.allArticlesData = @json($allArticles ?? []);
    function toArr(v) {
        if (!v) return [];
        if (Array.isArray(v)) return v;
        if (typeof v === 'object') return Object.values(v);
        if (typeof v === 'string') return v.split(',').filter(function(x){ return x; });
        return [];
    }

    window.addBlock = function(type, data) {
        data = data || {};
        var idx = document.querySelectorAll('.block-item').length;
        var container = document.getElementById('blocks-list');
        if (blocksEmpty) blocksEmpty.style.display = 'none';
        var wrap = document.createElement('div');
        wrap.className = 'block-item';
        wrap.dataset.type = type;
        wrap.dataset.idx = idx;

        var header = '<div class="block-header">' +
            '<span class="block-type-label">' + getBlockLabel(type) + '</span>' +
            '<div class="block-actions">' +
                '<button type="button" onclick="window.moveBlock(this, -1)">↑</button>' +
                '<button type="button" onclick="window.moveBlock(this, 1)">↓</button>' +
                '<button type="button" onclick="window.deleteBlock(this)" class="btn-danger-sm">Удалить</button>' +
            '</div>' +
        '</div>';

        var body = '';
        var hiddenType = '<input type="hidden" name="blocks[' + idx + '][type]" value="' + type + '">';

        if (type === 'gradient_text') {
            var editorId = 'block-editor-' + idx + '-' + Date.now();
            body =
                '<div class="block-item-inner">' +
                    '<div class="block-mini-editor-wrap">' +
                        '<div id="' + editorId + '" class="block-mini-editor"></div>' +
                    '</div>' +
                    '<input type="hidden" name="blocks[' + idx + '][data][html]" class="block-html-input" value="">' +
                '</div>';
            wrap.innerHTML = header + hiddenType + body;
            container.appendChild(wrap);

            var initialHtml = data.html || '';
            var $ed = jQuery('#' + editorId);
            $ed.html(initialHtml);
            $ed.trumbowyg({
                lang: 'ru',
                btns: [
                    ['viewHTML'],
                    ['undo', 'redo'],
                    ['formatting'],
                    ['strong', 'em', 'underline', 'del'],
                    ['foreColor', 'backColor'],
                    ['link'],
                    ['customUpload'],
                    ['unorderedList', 'orderedList'],
                    ['horizontalRule'],
                    ['removeformat'],
                    ['fullscreen']
                ],
                btnsDef: {
                    customUpload: {
                        ico: 'insertImage',
                        title: 'Загрузить изображение',
                        fn: function() {
                            var ed = $ed.data('trumbowyg');
                            var input = document.createElement('input');
                            input.type = 'file';
                            input.accept = 'image/*';
                            input.onchange = function() {
                                var file = input.files[0];
                                if (!file) return;

                                console.log('Original:', Math.round(file.size/1024) + 'KB');

                                compressImage(file, 1600, 0.82).then(function(compressed) {
                                    console.log('Compressed:', Math.round(compressed.size/1024) + 'KB');
                                    var fd = new FormData();
                                    fd.append('image', compressed);
                                    fd.append('_token', document.querySelector('meta[name="csrf-token"]').content);

                                    fetch('{{ route('admin.articles.upload-image') }}', {
                                        method: 'POST',
                                        body: fd,
                                        credentials: 'same-origin'
                                    }).then(function(r){ return r.json(); }).then(function(d) {
                                        if (d.success && d.url) {
                                            ed.execCmd('insertImage', d.url, false, true);
                                            // alt-атрибут можно добавить через DOM
                                            setTimeout(function() {
                                                var imgs = ed.$ed[0].querySelectorAll('img[src="' + d.url + '"]');
                                                imgs.forEach(function(img) { if (!img.alt) img.alt = file.name; });
                                            }, 100);
                                        } else {
                                            alert('Ошибка загрузки: ' + (d.error || ''));
                                        }
                                    }).catch(function(e){ alert('Ошибка: ' + e.message); });
                                });
                            };
                            input.click();
                        }
                    }
                },

                autogrow: true,
                removeformatPasted: false,
                // КЛЮЧЕВОЕ — не трогать HTML
                semantic: false,
                urlProtocol: true,
            });
         

            wrap._trumbowyg = $ed;
        }
        else if (type === 'image_full') {
            var inputId = 'img-input-' + idx + '-' + Date.now();
            body =
                '<div class="block-item-inner">' +
                    '<label class="block-field-label">URL изображения</label>' +
                    '<input type="text" name="blocks[' + idx + '][data][url]" class="block-image-url" placeholder="https://..." value="' + (data.url || '') + '">' +
                    '<label class="block-field-label">Alt-текст</label>' +
                    '<input type="text" name="blocks[' + idx + '][data][alt]" placeholder="Описание изображения" value="' + (data.alt || '') + '">' +
                    '<label class="block-field-label">Или загрузите файл</label>' +
                    '<input type="file" id="' + inputId + '" class="block-image-file" accept="image/*">' +
                    '<div class="block-image-preview">' + (data.url ? '<img src="' + data.url + '">' : '') + '</div>' +
                '</div>';
            wrap.innerHTML = header + hiddenType + body;
            container.appendChild(wrap);
            document.getElementById(inputId).addEventListener('change', function(e) {
                uploadBlockImage(e.target.files[0], wrap);
            });
        }
        else if (type === 'songs_list') {
            body =
                '<div class="block-item-inner">' +
                    '<label class="block-field-label">Заголовок блока</label>' +
                    '<input type="text" name="blocks[' + idx + '][data][title]" placeholder="Например: Популярные песни" value="' + (data.title || '') + '">' +
                    '<label class="block-field-label">Поиск и выбор песен</label>' +
                    '<input type="text" class="selector-search" placeholder="Начните вводить название...">' +
                    '<div class="selector-list" data-kind="songs"></div>' +
                    '<input type="hidden" name="blocks[' + idx + '][data][song_ids]" class="block-ids-input" value="' + toArr(data.song_ids).join(',') + '">' +
                '</div>';
            wrap.innerHTML = header + hiddenType + body;
            container.appendChild(wrap);
            initSelector(wrap, 'songs', toArr(data.song_ids));
        }
        else if (type === 'articles_list') {
            body =
                '<div class="block-item-inner">' +
                    '<label class="block-field-label">Заголовок блока</label>' +
                    '<input type="text" name="blocks[' + idx + '][data][title]" placeholder="Например: Читайте также" value="' + (data.title || '') + '">' +
                    '<label class="block-field-label">Поиск и выбор статей</label>' +
                    '<input type="text" class="selector-search" placeholder="Начните вводить название...">' +
                    '<div class="selector-list" data-kind="articles"></div>' +
                    '<input type="hidden" name="blocks[' + idx + '][data][article_ids]" class="block-ids-input" value="' + toArr(data.article_ids).join(',') + '">' +
                '</div>';
            wrap.innerHTML = header + hiddenType + body;
            container.appendChild(wrap);
            initSelector(wrap, 'articles', toArr(data.article_ids));
        }

        reindexBlocks();
    };

    function getBlockLabel(type) {
        var labels = {
            gradient_text: 'Текст с подложкой',
            image_full: 'Изображение',
            songs_list: 'Блок с песнями',
            articles_list: 'Блок со статьями'
        };
        return labels[type] || type;
    }

    function uploadBlockImage(file, wrap) {
        if (!file) return;
        var fd = new FormData();
        fd.append('image', file);
        fd.append('_token', document.querySelector('meta[name="csrf-token"]').content);

        fetch('{{ route('admin.articles.upload-image') }}', {
            method: 'POST',
            body: fd
        }).then(function(r){ return r.json(); }).then(function(d) {
            if (d.success) {
                wrap.querySelector('.block-image-url').value = d.url;
                wrap.querySelector('.block-image-preview').innerHTML = '<img src="' + d.url + '" style="max-width:300px;border-radius:6px;">';
            } else {
                alert('Ошибка загрузки: ' + (d.error || ''));
            }
        }).catch(function(e){ alert('Ошибка: ' + e.message); });
    }

    function initSelector(wrap, kind, selectedIds) {
        var list = wrap.querySelector('.selector-list');
        var search = wrap.querySelector('.selector-search');
        var hidden = wrap.querySelector('.block-ids-input');
        var items = kind === 'songs' ? window.allSongsData : window.allArticlesData;
        var idKey = 'id';
        var selected = selectedIds.map(function(x){ return parseInt(x); }).filter(function(x){ return x; });

        function render(filter) {
            filter = (filter || '').toLowerCase();
            var html = '';
            items.forEach(function(it) {
                if (filter && it.title.toLowerCase().indexOf(filter) === -1) return;
                var id = it[idKey];
                var checked = selected.indexOf(id) !== -1 ? 'checked' : '';
                html += '<label class="selector-item"><input type="checkbox" value="' + id + '" ' + checked + '> ' + escapeHtml(it.title) + '</label>';
            });
            list.innerHTML = html || '<div style="color:#999;padding:8px;">Ничего не найдено</div>';

            list.querySelectorAll('input[type=checkbox]').forEach(function(cb) {
                cb.addEventListener('change', function() {
                    var id = parseInt(cb.value);
                    if (cb.checked) {
                        if (selected.indexOf(id) === -1) selected.push(id);
                    } else {
                        selected = selected.filter(function(x){ return x !== id; });
                    }
                    hidden.value = selected.join(',');
                });
            });
        }

        render('');
        search.addEventListener('input', function() { render(search.value); });
    }

    function escapeHtml(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
    // Сжатие картинки через Canvas
    function compressImage(file, maxWidth, quality) {
        maxWidth = maxWidth || 1600;
        quality = quality || 0.8;
        return new Promise(function(resolve) {
            if (!file.type.match(/^image\//)) return resolve(file);

            var reader = new FileReader();
            reader.onload = function(e) {
                var img = new Image();
                img.onload = function() {
                    var w = img.width, h = img.height;
                    if (w > maxWidth) { h = Math.round(h * (maxWidth / w)); w = maxWidth; }

                    var canvas = document.createElement('canvas');
                    canvas.width = w;
                    canvas.height = h;
                    var ctx = canvas.getContext('2d');

                    // Всегда JPEG — белый фон под прозрачность
                    ctx.fillStyle = '#ffffff';
                    ctx.fillRect(0, 0, w, h);
                    ctx.drawImage(img, 0, 0, w, h);

                    canvas.toBlob(function(blob) {
                        if (!blob) return resolve(file);
                        var name = file.name.replace(/\.[^.]+$/, '') + '.jpg';
                        var newFile = new File([blob], name, { type: 'image/jpeg' });
                        console.log('Compress:',
                            Math.round(file.size/1024) + 'KB →',
                            Math.round(newFile.size/1024) + 'KB',
                            '(' + img.width + 'x' + img.height + ' → ' + w + 'x' + h + ')');
                        resolve(newFile);
                    }, 'image/jpeg', quality);
                };
                img.onerror = function() { console.log('img load error'); resolve(file); };
                img.src = e.target.result;
            };
            reader.onerror = function() { console.log('reader error'); resolve(file); };
            reader.readAsDataURL(file);
        });
    }
    function escapeAttr(s) { return String(s).replace(/&/g, '&amp;').replace(/"/g, '&quot;'); }

    window.deleteBlock = function(btn) {
        if (!confirm('Удалить блок?')) return;
        var block = btn.closest('.block-item');
        if (block._trumbowyg) {
            block._trumbowyg.trumbowyg('destroy');
        }
        reindexBlocks();
        if (!blocksContainer.querySelector('.block-item')) {
            blocksEmpty.style.display = 'block';
        }
    };

    window.moveBlock = function(btn, dir) {
        var block = btn.closest('.block-item');
        if (!block) return;
        if (dir === -1 && block.previousElementSibling && block.previousElementSibling.classList.contains('block-item')) {
            blocksContainer.insertBefore(block, block.previousElementSibling);
        } else if (dir === 1 && block.nextElementSibling) {
            blocksContainer.insertBefore(block.nextElementSibling, block);
        }
        reindexBlocks();
    };

    function reindexBlocks() {
        var items = blocksContainer.querySelectorAll('.block-item');
        for (var i = 0; i < items.length; i++) {
            items[i].dataset.idx = i;
            var fields = items[i].querySelectorAll('[name^="blocks["]');
            for (var j = 0; j < fields.length; j++) {
                fields[j].name = fields[j].name.replace(/^blocks\[\d+\]/, 'blocks[' + i + ']');
            }
        }
    }

    // Load existing blocks
    var existingBlocks = @json(old('blocks', $article->blocks ?? []));
    if (Array.isArray(existingBlocks) && existingBlocks.length) {
        existingBlocks.forEach(function(b) { window.addBlock(b.type, b.data); });
    }
});
</script>
@endpush