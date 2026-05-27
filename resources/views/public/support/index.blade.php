@extends('layouts.public')

@section('title', 'Поддержка — НА РЕПИТЕ')

@section('jsonld')
    @include('partials.seo.json-ld', [
        'include' => ['breadcrumb'],
        'breadcrumbs' => [
            ['name' => 'Главная', 'url' => url('/')],
            ['name' => 'Поддержка'],
        ],
    ])
@endsection

@section('content')
<div class="max-w-7xl mx-auto px-4 md:px-8 py-8">
    <nav class="breadcrumbs">
        <a href="/">Главная</a>
        <span class="breadcrumbs-sep">•</span>
        <span class="breadcrumbs-current">Поддержка</span>
    </nav>

    <div class="support-page">
        <h1 class="support-title">Служба поддержки</h1>

        @if(session('success'))
            <div class="support-success">
                <div style="font-size:48px;margin-bottom:12px;">✅</div>
                <h2 style="font-size:22px;font-weight:bold;margin-bottom:8px;">Сообщение отправлено!</h2>
                <p style="color:#8f8f8f;">Мы ответим вам на Email в ближайшее время.</p>
                <a href="/" style="color:#1253a2;text-decoration:none;margin-top:16px;display:inline-block;">← На главную</a>
            </div>
        @else
            <div class="support-form-wrap">
                @if($errors->any())
                    <div class="support-error">
                        @foreach($errors->all() as $e){{ $e }}<br>@endforeach
                    </div>
                @endif

                <form action="{{ route('support.send') }}" method="POST" id="support-form">
                    @csrf
                    <input type="hidden" name="user_id" value="{{ request('user_id') }}">
                    <input type="hidden" name="attached_files" id="attached_files">

                    <div class="support-field">
                        <label>Ваш Email</label>
                        <input type="email" name="email" required placeholder="name@example.com"
                               value="{{ old('email') }}">
                    </div>

                    <div class="support-field">
                        <label>Сообщение</label>
                        <textarea name="message" required placeholder="Опишите вашу проблему..."
                                  rows="5">{{ old('message') }}</textarea>
                    </div>

                    <div class="support-field">
                        <label>Прикрепить файлы</label>
                        <div class="support-dropzone" id="support-dropzone">
                            <div class="support-dropzone-text">
                                <span style="font-size:28px;">📎</span>
                                <span>Перетащите файлы или нажмите для выбора</span>
                                <span style="font-size:12px;color:#999;">JPG, PNG, PDF, DOC — до 5 MB, макс. 5 файлов</span>
                            </div>
                            <input type="file" id="support-file-input" multiple
                                   accept="image/*,application/pdf,.doc,.docx" style="display:none;">
                        </div>
                        <div id="support-file-list" class="support-file-list"></div>
                    </div>

                    <button type="submit" class="support-submit">Отправить</button>
                </form>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
    var dropzone = document.getElementById('support-dropzone');
    var fileInput = document.getElementById('support-file-input');
    var fileList = document.getElementById('support-file-list');
    var hiddenInput = document.getElementById('attached_files');
    var uploadedFiles = [];

    dropzone.addEventListener('click', function() { fileInput.click(); });
    dropzone.addEventListener('dragover', function(e) { e.preventDefault(); dropzone.classList.add('dragover'); });
    dropzone.addEventListener('dragleave', function() { dropzone.classList.remove('dragover'); });
    dropzone.addEventListener('drop', function(e) {
        e.preventDefault();
        dropzone.classList.remove('dragover');
        handleFiles(e.dataTransfer.files);
    });
    fileInput.addEventListener('change', function() { handleFiles(fileInput.files); fileInput.value = ''; });

    function handleFiles(files) {
        for (var i = 0; i < files.length; i++) {
            if (uploadedFiles.length >= 5) break;
            uploadFile(files[i]);
        }
    }

    function uploadFile(file) {
        if (file.size > 5 * 1024 * 1024) { alert('Файл слишком большой (макс. 5 MB)'); return; }

        var item = document.createElement('div');
        item.className = 'support-file-item';
        item.innerHTML = '<span class="support-file-name">⏳ ' + file.name + '</span>';
        fileList.appendChild(item);

        var fd = new FormData();
        fd.append('file', file);
        fd.append('_token', document.querySelector('meta[name="csrf-token"]').content);

        fetch('{{ route('support.upload') }}', { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                if (d.success) {
                    uploadedFiles.push(d.filename);
                    hiddenInput.value = uploadedFiles.join(',');
                    item.innerHTML = '<span class="support-file-name">✅ ' + file.name + '</span>' +
                        '<button type="button" class="support-file-remove" onclick="removeFile(this, \'' + d.filename + '\')">✕</button>';
                } else {
                    item.innerHTML = '<span class="support-file-name" style="color:red;">❌ ' + file.name + '</span>';
                }
            }).catch(function() {
                item.innerHTML = '<span class="support-file-name" style="color:red;">❌ Ошибка загрузки</span>';
            });
    }

    window.removeFile = function(btn, filename) {
        uploadedFiles = uploadedFiles.filter(function(f) { return f !== filename; });
        hiddenInput.value = uploadedFiles.join(',');
        btn.parentElement.remove();
    };
})();
</script>
@endpush