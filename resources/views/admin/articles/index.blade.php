@extends('layouts.app')

@section('title', 'Статьи — Админ')

@push('styles')
<style>
    .articles-admin-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 12px;
    }
    .articles-admin-table {
        width: 100%;
        border-collapse: collapse;
        background: var(--bg-card);
        border-radius: var(--radius-md);
        overflow: hidden;
        box-shadow: var(--shadow-xs);
    }
    .articles-admin-table th,
    .articles-admin-table td {
        padding: 12px 14px;
        text-align: left;
        border-bottom: 1px solid var(--border);
        font-size: 13px;
    }
    .articles-admin-table th {
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-size: 11px;
        color: var(--text-tertiary);
        background: var(--bg-input);
    }
    .articles-admin-table tr:hover td { background: var(--surface-glass); }
    .article-row-title { font-weight: 600; color: var(--text-primary); }
    .article-row-slug { font-size: 11px; color: var(--text-tertiary); font-family: monospace; margin-top: 2px; }
    .article-status {
        display: inline-block;
        padding: 3px 10px;
        border-radius: var(--radius-full);
        font-size: 11px;
        font-weight: 700;
    }
    .article-status.published { background: var(--success-soft); color: var(--success); }
    .article-status.draft { background: var(--warning-soft); color: var(--warning); }

    .article-actions { display: flex; gap: 6px; }
    .article-actions a,
    .article-actions button {
        padding: 6px 10px;
        border-radius: var(--radius-sm);
        font-size: 11px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        text-decoration: none;
        transition: opacity 0.15s;
    }
    .article-actions .btn-edit { background: var(--accent-soft); color: var(--accent); }
    .article-actions .btn-view { background: var(--surface-glass); color: var(--text-secondary); }
    .article-actions .btn-delete { background: var(--danger-soft); color: var(--danger); }
    .article-actions a:hover, .article-actions button:hover { opacity: 0.85; }
</style>
@endpush

@section('content')
<div class="articles-admin-header">
    <h2 style="font-size: 22px; font-weight: 800;">📝 Статьи</h2>
    <a href="{{ route('admin.articles.create') }}" class="btn btn-primary">+ Создать статью</a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if($articles->isEmpty())
    <div class="empty-state">
        <div class="empty-state-icon"></div>
        <p>Статей пока нет</p>
    </div>
@else
    <div style="overflow-x: auto;">
        <table class="articles-admin-table">
            <thead>
                <tr>
                    <th>Название</th>
                    <th>Статус</th>
                    <th>👁</th>
                    <th>Создана</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($articles as $article)
                    <tr>
                        <td>
                            <div class="article-row-title">{{ $article->title }}</div>
                            <div class="article-row-slug">/{{ $article->slug }}</div>
                        </td>
                        <td>
                            @if($article->is_published)
                                <span class="article-status published">● Опубликовано</span>
                            @else
                                <span class="article-status draft">● Черновик</span>
                            @endif
                        </td>
                        <td>{{ $article->views_count }}</td>
                        <td>{{ $article->created_at->format('d.m.Y') }}</td>
                        <td>
                            <div class="article-actions">
                                @if($article->is_published)
                                    <a href="{{ route('public.articles.show', $article->slug) }}" target="_blank" class="btn-view">👁</a>
                                @endif
                                <a href="{{ route('admin.articles.edit', $article->id) }}" class="btn-edit">✏️</a>
                                <form action="{{ route('admin.articles.delete', $article->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Удалить статью?')">
                                    @csrf
                                    <button type="submit" class="btn-delete">🗑</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($articles->hasPages())
        <div class="pagination" style="margin-top: 20px;">
            {{ $articles->links('pagination::bootstrap-4') }}
        </div>
    @endif
@endif
@endsection