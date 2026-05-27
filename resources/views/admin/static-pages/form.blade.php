@extends('layouts.app')

@section('title', ($page->id ? 'Редактирование' : 'Новая страница') . ' — Статическая страница')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
<style>
    .form-section { background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 20px; margin-bottom: 16px; }
    .form-section h3 { font-size: 15px; font-weight: 700; margin-bottom: 14px; }
    .form-row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .checkbox-row { display:flex; align-items:center; gap:10px; margin:12px 0; }
    #editor { min-height: 400px; background: white; }
    .form-actions { display:flex; gap:12px; margin-top:20px; }
    .form-actions .btn { flex:1; }
    .field-hint { font-size:12px; color: var(--text-tertiary); margin-top: 4px; }
    .cover-upload-box {
        position: relative;
        border: 2px dashed var(--border-strong);
        border-radius: var(--radius-md);
        background: var(--bg-input);
        cursor: pointer;
        overflow: hidden;
        min-height: 150px;
    }
    .cover-upload-box:hover { border-color: var(--accent); }
    .cover-upload-empty {
        display: flex; align-items: center; justify-content: center;
        padding: 40px 20px; text-align: center;
    }
    .cover-upload-text { font-size: 14px; font-weight: 600; color: var(--text-secondary); }
    .cover-preview { position: relative; width: 100%; }
    .cover-preview img { width: 100%; height: auto; max-height: 260px; object-fit: cover; display: block; }
    .cover-remove-btn {
        position: absolute; top: 10px; right: 10px;
        width: 32px; height: 32px; border-radius: 50%;
        background: rgba(0,0,0,0.7); color: white; border: none;
        cursor: pointer; display: flex; align-items: center; justify-content: center;
    }
    .cover-upload-progress, .cover-upload-error {
        margin-top: 8px; padding: 10px; border-radius: var(--radius-sm); font-size: 13px; text-align: center;
    }
    .cover-upload-progress { background: var(--accent-soft); color: var(--accent); }
    .cover-upload-error { background: var(--danger-soft); color: var(--danger); }
    .btn-mode {
        padding: 6px 14px;
        font-size: 13px;
        font-weight: 600;
        background: var(--bg-input);
        color: var(--text-secondary);
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        cursor: pointer;
    }
    .btn-mode.active {
        background: var(--accent);
        color: white;
        border-color: var(--accent);
    }
</style>
@endpush

@section('content')
<div style="max-width: 900px; margin: 0 auto;">
    <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
        <a href="{{ route('admin.static-pages.index') }}" style="color:var(--text-tertiary);font-size:14px;">← Назад</a>
        <h2 style="font-size:22px;font-weight:800;flex:1;">{{ $page->id ? 'Редактирование страницы' : 'Новая страница' }}</h2>
    </div>

    @if($errors->any())
        <div class="alert alert-error">@foreach($errors->all() as $e){{ $e }}<br>@endforeach</div>
    @endif

    <form method="POST" action="{{ $page->id ? route('admin.static-pages.update', $page->id) : route('admin.static-pages.store') }}" id="static-form">
        @csrf

        <div class="form-section">
            <h3>Основное</h3>

            <div class="form-group">
                <label class="form-label">Заголовок *</label>
                <input type="text" name="title" class="form-input" id="title-input" value="{{ old('title', $page->title) }}" required>
            </div>

            <div class="form-group">
                <label class="form-label">Слаг (URL)</label>
                <input type="text" name="slug" class="form-input" id="slug-input" value="{{ old('slug', $page->slug) }}">
                <div class="field-hint">Оставьте пустым — сгенерируется из заголовка. URL: /p/{slug}</div>
            </div>

            <div class="form-row-2">
                <div class="form-group">
                    <label class="form-label">Текст баннера</label>
                    <input type="text" name="banner_text" class="form-input" value="{{ old('banner_text', $page->banner_text) }}" placeholder="По умолчанию — заголовок">
                </div>
                <div class="form-group">
                    <label class="form-label">Цвет фона баннера</label>
                    <input type="text" name="banner_bg_color" class="form-input" value="{{ old('banner_bg_color', $page->banner_bg_color ?? '#1253a2') }}" placeholder="#1253a2 или linear-gradient(...)">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Фоновое изображение баннера</label>
                <input type="hidden" name="banner_image" id="banner-url-input" value="{{ old('banner_image', $page->banner_image) }}">

                <div class="cover-upload-box" id="banner-upload-box">
                    <div class="cover-preview" id="banner-preview" style="{{ old('banner_image', $page->banner_image) ? '' : 'display:none;' }}">
                        <img src="{{ old('banner_image', $page->banner_image) }}" alt="" id="banner-preview-img">
                        <button type="button" class="cover-remove-btn" onclick="removeBanner()">✕</button>
                    </div>
                    <div class="cover-upload-empty" id="banner-upload-empty" style="{{ old('banner_image', $page->banner_image) ? 'display:none;' : '' }}">
                        <div class="cover-upload-text">Выбрать файл</div>
                    </div>
                    <input type="file" id="banner-file-input" accept="image/jpeg,image/png,image/webp" style="display:none;">
                </div>
                <div class="cover-upload-progress" id="banner-upload-progress" style="display:none;">Загрузка...</div>
                <div class="cover-upload-error" id="banner-upload-error" style="display:none;"></div>
            </div>

            <div class="form-row-2">
                <div class="form-group">
                    <label class="form-label">Порядок сортировки</label>
                    <input type="number" name="sort_order" class="form-input" value="{{ old('sort_order', $page->sort_order) }}">
                </div>
                <div class="form-group" style="padding-top:28px;">
                    <div class="checkbox-row">
                        <input type="checkbox" name="is_published" id="is_published" value="1" {{ old('is_published', $page->is_published ?? true) ? 'checked' : '' }}>
                        <label for="is_published">Опубликована</label>
                    </div>
                    <div class="checkbox-row">
                        <input type="checkbox" name="show_in_menu" id="show_in_menu" value="1" {{ old('show_in_menu', $page->show_in_menu) ? 'checked' : '' }}>
                        <label for="show_in_menu">Показывать в главном меню</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-section">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
                <h3 style="margin:0;">Контент</h3>
                <div style="display:flex;gap:6px;">
                    <button type="button" id="mode-visual" class="btn-mode active" onclick="switchMode('visual')">Визуальный</button>
                    <button type="button" id="mode-html" class="btn-mode" onclick="switchMode('html')">HTML</button>
                </div>
            </div>
            <div id="editor-visual">
                <div id="editor">{!! old('content_html', $page->content_html) !!}</div>
            </div>
            <textarea id="editor-html" style="display:none;width:100%;min-height:400px;font-family:monospace;font-size:13px;padding:12px;border:1px solid var(--border);border-radius:var(--radius-sm);background:#fafbfc;">{{ old('content_html', $page->content_html) }}</textarea>
            <input type="hidden" name="content_html" id="content-html-input">
        </div>

        <div class="form-section">
            <h3>SEO</h3>
            <div class="form-group">
                <label class="form-label">SEO Title</label>
                <input type="text" name="seo_title" class="form-input" value="{{ old('seo_title', $page->seo_title) }}">
            </div>
            <div class="form-group">
                <label class="form-label">SEO Description</label>
                <textarea name="seo_description" class="form-textarea" rows="3">{{ old('seo_description', $page->seo_description) }}</textarea>
            </div>
            <div class="form-group">
                <label class="form-label">SEO Keywords</label>
                <input type="text" name="seo_keywords" class="form-input" value="{{ old('seo_keywords', $page->seo_keywords) }}">
            </div>
            <div class="checkbox-row">
                <input type="checkbox" name="noindex" id="noindex" value="1" {{ old('noindex', $page->noindex) ? 'checked' : '' }}>
                <label for="noindex">noindex, nofollow</label>
            </div>
        </div>

        <div class="form-actions">
            <a href="{{ route('admin.static-pages.index') }}" class="btn btn-secondary">Отмена</a>
            <button type="submit" class="btn btn-primary">💾 Сохранить</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var quill = new Quill('#editor', {
        theme: 'snow',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                [{ 'align': [] }],
                ['blockquote', 'link', 'image'],
                [{ 'color': [] }, { 'background': [] }],
                ['clean']
            ]
        }
    });

    var currentMode = 'visual';
    var htmlArea = document.getElementById('editor-html');
    var visualWrap = document.getElementById('editor-visual');

    // Автопереключение в HTML если контент содержит inline-стили
    var initialHtml = htmlArea.value || '';
    if (/style\s*=/i.test(initialHtml)) {
        currentMode = 'html';
        visualWrap.style.display = 'none';
        htmlArea.style.display = 'block';
        document.getElementById('mode-visual').classList.remove('active');
        document.getElementById('mode-html').classList.add('active');
    }
    var htmlArea = document.getElementById('editor-html');
    var visualWrap = document.getElementById('editor-visual');

    window.switchMode = function(mode) {
        if (mode === currentMode) return;
        if (mode === 'visual' && /style\s*=/i.test(htmlArea.value)) {
            if (!confirm('В HTML есть inline-стили, визуальный редактор их удалит. Продолжить?')) return;
        }
        if (mode === 'html') {
            htmlArea.value = quill.root.innerHTML;
            visualWrap.style.display = 'none';
            htmlArea.style.display = 'block';
        } else {
            quill.root.innerHTML = htmlArea.value;
            htmlArea.style.display = 'none';
            visualWrap.style.display = 'block';
        }
        currentMode = mode;
        document.getElementById('mode-visual').classList.toggle('active', mode === 'visual');
        document.getElementById('mode-html').classList.toggle('active', mode === 'html');
    };

    document.getElementById('static-form').addEventListener('submit', function() {
        var html = currentMode === 'html' ? htmlArea.value : quill.root.innerHTML;
        document.getElementById('content-html-input').value = html;
    });

    // Slug auto-generation
    var title = document.getElementById('title-input');
    var slug = document.getElementById('slug-input');
    var slugEdited = {{ $page->id ? 'true' : 'false' }};
    slug.addEventListener('input', function(){ slugEdited = true; });
    title.addEventListener('input', function() {
        if (slugEdited) return;
        var map = {'а':'a','б':'b','в':'v','г':'g','д':'d','е':'e','ё':'yo','ж':'zh','з':'z','и':'i','й':'y','к':'k','л':'l','м':'m','н':'n','о':'o','п':'p','р':'r','с':'s','т':'t','у':'u','ф':'f','х':'h','ц':'ts','ч':'ch','ш':'sh','щ':'sch','ъ':'','ы':'y','ь':'','э':'e','ю':'yu','я':'ya'};
        slug.value = title.value.toLowerCase().split('').map(function(c){ return map[c] !== undefined ? map[c] : c; }).join('').replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'').substring(0,100);
    });
    // Banner upload
    (function() {
        var box = document.getElementById('banner-upload-box');
        var input = document.getElementById('banner-file-input');
        var urlInput = document.getElementById('banner-url-input');
        var preview = document.getElementById('banner-preview');
        var previewImg = document.getElementById('banner-preview-img');
        var empty = document.getElementById('banner-upload-empty');
        var progress = document.getElementById('banner-upload-progress');
        var error = document.getElementById('banner-upload-error');

        box.addEventListener('click', function() { input.click(); });
        box.addEventListener('dragover', function(e) { e.preventDefault(); box.classList.add('dragover'); });
        box.addEventListener('drop', function(e) {
            e.preventDefault(); box.classList.remove('dragover');
            if (e.dataTransfer.files.length) { input.files = e.dataTransfer.files; uploadFile(e.dataTransfer.files[0]); }
        });
        input.addEventListener('change', function(e) { if (e.target.files.length) uploadFile(e.target.files[0]); });

        function uploadFile(file) {
            error.style.display = 'none';
            progress.style.display = 'block';
            var fd = new FormData();
            fd.append('image', file);
            fd.append('_token', '{{ csrf_token() }}');

            fetch('{{ route('admin.static-pages.upload-image') }}', { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function(r){ return r.json().then(function(d){ return {ok:r.ok, d:d}; }); })
                .then(function(res) {
                    if (!res.ok || !res.d.success) throw new Error(res.d.error || 'Ошибка');
                    urlInput.value = res.d.url;
                    previewImg.src = res.d.url;
                    preview.style.display = 'block';
                    empty.style.display = 'none';
                    progress.style.display = 'none';
                }).catch(function(e) {
                    progress.style.display = 'none';
                    error.textContent = '❌ ' + e.message;
                    error.style.display = 'block';
                });
        }
    })();

    window.removeBanner = function() {
        document.getElementById('banner-url-input').value = '';
        document.getElementById('banner-preview-img').src = '';
        document.getElementById('banner-preview').style.display = 'none';
        document.getElementById('banner-upload-empty').style.display = 'flex';
        document.getElementById('banner-file-input').value = '';
    };
});


</script>
@endpush