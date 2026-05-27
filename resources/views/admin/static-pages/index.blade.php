@extends('layouts.app')

@section('title', 'Статические страницы — Админ')

@push('styles')
<style>
    .pages-admin-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 12px;
    }
    .pages-tree { display: flex; flex-direction: column; gap: 8px; }
    .page-row {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        box-shadow: var(--shadow-xs);
    }
    .page-row.child { margin-left: 32px; background: var(--bg-input); }
    .page-row-info { flex: 1; min-width: 0; }
    .page-row-title { font-weight: 600; font-size: 14px; color: var(--text-primary); }
    .page-row-slug { font-size: 11px; color: var(--text-tertiary); font-family: monospace; margin-top: 2px; }
    .page-row-status {
        display: inline-block;
        padding: 3px 10px;
        border-radius: var(--radius-full);
        font-size: 11px;
        font-weight: 700;
    }
    .page-row-status.published { background: var(--success-soft); color: var(--success); }
    .page-row-status.draft { background: var(--warning-soft); color: var(--warning); }
    .page-row-actions { display: flex; gap: 6px; }
    .page-row-actions a,
    .page-row-actions button {
        padding: 6px 12px;
        border-radius: var(--radius-sm);
        font-size: 11px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        text-decoration: none;
        transition: opacity 0.15s;
    }
    .btn-add-child { background: var(--accent-soft); color: var(--accent); }
    .btn-edit-page { background: var(--surface-glass); color: var(--text-secondary); }
    .btn-delete-page { background: var(--danger-soft); color: var(--danger); }
    .btn-view-page { background: var(--success-soft); color: var(--success); }
</style>
@endpush

@section('content')
<div class="pages-admin-header">
    <h2 style="font-size: 22px; font-weight: 800;">📄 Страницы</h2>
    <a href="{{ route('admin.static-pages.create') }}" class="btn btn-primary">+ Создать страницу</a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if($pages->isEmpty())
    <div class="empty-state">
        <div class="empty-state-icon"></div>
        <p>Страниц пока нет</p>
    </div>
@else
    <div class="pages-tree">
        @foreach($pages as $root)
            <div class="page-row">
                <div class="page-row-info">
                    <div class="page-row-title">{{ $root->title }}</div>
                    <div class="page-row-slug">/{{ $root->slug }}</div>
                </div>
                <span class="page-row-status {{ $root->is_published ? 'published' : 'draft' }}">
                    {{ $root->is_published ? '● Опубликовано' : '● Черновик' }}
                </span>
                <div class="page-row-actions">
                    @if($root->is_published)
                        <a href="{{ route('public.static-pages.show', $root->slug) }}" target="_blank" class="btn-view-page">👁</a>
                    @endif
                    <a href="{{ route('admin.static-pages.edit', $root->id) }}" class="btn-edit-page">✏️</a>
                    <form action="{{ route('admin.static-pages.destroy', $root->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Удалить страницу со всеми дочерними?')">
                        @csrf
                        <button type="submit" class="btn-delete-page">🗑</button>
                    </form>
                </div>
            </div>

        @endforeach
    </div>
@endif
@endsection