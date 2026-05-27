@extends('layouts.app')

@section('title', ($page->id ? 'Редактирование' : 'Новая страница') . ' — Админ')

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

    .form-actions { display: flex; gap: 12px; margin-top: 20px; flex-wrap: wrap; }
    .form-actions .btn { flex: 1; min-width: 140px; }

    .char-counter { font-size: 11px; color: var(--text-tertiary); text-align: right; margin-top: 4px; }

    /* === Block Builder === */
    .blocks-list { display: flex; flex-direction: column; gap: 16px; }
    .block-item {
        border: 1.5px solid var(--border);
        border-radius: var(--radius-md);
        background: var(--bg-input);
        padding: 16px;
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
    .block-actions { display: flex; gap: 6px; }
    .block-actions button {
        width: 28px; height: 28px;
        border-radius: var(--radius-sm);
        border: none;
        background: var(--bg-card);
        cursor: pointer;
        font-size: 13px;
        color: var(--text-secondary);
    }
    .block-actions button:hover { background: var(--surface-glass-hover); color: var(--text-primary); }
    .block-actions .btn-delete-block { color: var(--danger); }
    .block-actions .btn-delete-block:hover { background: var(--danger); color: white; }

    .block-editor { background: white; border-radius: var(--radius-sm); }
    .block-editor .ql-container.ql-snow { min-height: 120px; font-size: 14px; }

    .add-block-panel {
        margin-top: 14px;
        padding: 14px;
        border: 1.5px dashed var(--border-strong);
        border-radius: var(--radius-md);
        text-align: center;
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        justify-content: center;
    }
    .add-block-btn {
        padding: 10px 16px;
        background: var(--accent-soft);
        color: var(--accent);
        border: none;
        border-radius: var(--radius-md);
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
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
        <a href="{{ route('admin.pages.index') }}" style="color: var(--text-tertiary); font-size: 14px;">← Назад</a>
        <h2 style="font-size: 22px; font-weight: 800; flex: 1;">
            {{ $page->id ? 'Редактирование страницы' : 'Новая страница' }}
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

    <form method="POST" action="{{ $page->id ? route('admin.pages.update', $page->id) : route('admin.pages.store') }}" id="page-form">
        @csrf

        <!-- ОСНОВНОЕ -->
        <div class="form-section">
            <h3>Основное</h3>

            <div class="form-group">
                <label class="form-label">Заголовок *</label>
                <input type="text" name="title" class="form-input" id="title-input"
                       value="{{ old('title', $page->title) }}" required maxlength="500">
            </div>

            <div class="form-group">
                <label class="form-label">Слаг (URL)</label>
                <input type="text" name="slug" class="form-input" id="slug-input"
                       value="{{ old('slug', $page->slug) }}" maxlength="255">
                <div class="field-hint">Оставьте пустым — сгенерируется автоматически</div>
                @error('slug') <div class="field-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Родительская страница</label>
                <select name="parent_id" class="form-input">
                    <option value="">— Корневая страница —</option>
                    @foreach($rootPages as $rp)
                        <option value="{{ $rp->id }}" {{ old('parent_id', $page->parent_id) == $rp->id ? 'selected' : '' }}>
                            {{ $rp->title }}
                        </option>
                    @endforeach
                </select>
                @error('parent_id') <div class="field-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Краткое описание</label>
                <textarea name="excerpt" class="form-textarea" rows="2" maxlength="500">{{ old('excerpt', $page->excerpt) }}</textarea>
            </div>

            <div class="form-row-2">
                <div class="form-group">
                    <label class="form-label">Порядок сортировки</label>
                    <input type="number" name="sort_order" class="form-input" value="{{ old('sort_order', $page->sort_order ?? 0) }}">
                </div>
                <div class="form-group" style="display:flex; flex-direction:column; justify-content:flex-end;">
                    <div class="checkbox-row">
                        <input type="checkbox" name="is_published" id="is_published" value="1"
                            {{ old('is_published', $page->is_published) ? 'checked' : '' }}>
                        <label for="is_published">Опубликовано</label>
                    </div>
                    <div class="checkbox-row">
                        <input type="checkbox" name="show_in_menu" id="show_in_menu" value="1"
                            {{ old('show_in_menu', $page->show_in_menu) ? 'checked' : '' }}>
                        <label for="show_in_menu">В главном меню (для корневых)</label>
                    </div>
                </div>
            </div>
        </div>

        <!-- КОНТЕНТНЫЕ БЛОКИ -->
        <div class="form-section">
            <h3>Контент (блоки)</h3>

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

        <!-- SEO -->
        <div class="form-section">
            <h3 class="form-section-toggle" onclick="toggleSection(this)">SEO настройки</h3>
            <div class="form-section-body">
                <div class="form-group">
                    <label class="form-label">SEO Title</label>
                    <input type="text" name="seo_title" class="form-input" id="seo-title-input"
                           value="{{ old('seo_title', $page->seo_title) }}" maxlength="500">
                    <div class="char-counter"><span id="seo-title-count">0</span>/70</div>
                </div>
                <div class="form-group">
                    <label class="form-label">SEO Description</label>
                    <textarea name="seo_description" class="form-textarea" rows="3" id="seo-desc-input">{{ old('seo_description', $page->seo_description) }}</textarea>
                    <div class="char-counter"><span id="seo-desc-count">0</span>/160</div>
                </div>
                <div class="form-group">
                    <label class="form-label">SEO Keywords</label>
                    <input type="text" name="seo_keywords" class="form-input"
                           value="{{ old('seo_keywords', $page->seo_keywords) }}">
                </div>
               <!--  <div class="form-group">
                    <label class="form-label">OG Image</label>
                    <input type="text" name="og_image" class="form-input"
                           value="{{ old('og_image', $page->og_image) }}" placeholder="https://...">
                </div>
                <div class="form-group">
                    <label class="form-label">Canonical URL</label>
                    <input type="text" name="canonical_url" class="form-input"
                           value="{{ old('canonical_url', $page->canonical_url) }}" placeholder="https://...">
                </div> -->
                <div class="checkbox-row">
                    <input type="checkbox" name="noindex" id="noindex" value="1"
                        {{ old('noindex', $page->noindex) ? 'checked' : '' }}>
                    <label for="noindex">noindex, nofollow</label>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <a href="{{ route('admin.pages.index') }}" class="btn btn-secondary">Отмена</a>
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
    // Slug auto
    var titleInput = document.getElementById('title-input');
    var slugInput = document.getElementById('slug-input');
    var slugEdited = {{ $page->id ? 'true' : 'false' }};

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

    // Save blocks on submit
    document.getElementById('page-form').addEventListener('submit', function(e) {

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

    // Load existing blocks
    var existingBlocks = @json(old('blocks', $page->blocks ?? []));
    if (Array.isArray(existingBlocks) && existingBlocks.length) {
        existingBlocks.forEach(function(b) { window.addBlock(b.type, b.data); });
    }
});
</script>
@endpush