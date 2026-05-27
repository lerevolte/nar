@extends('layouts.public')

@section('title', 'Статьи о создании музыки нейросетью')
@section('meta')
    <meta name="description" content="Полезные статьи о генерации песен с помощью ИИ: промпты для создания музыки, инструкции по работе с нейросетью, обзоры аналогов Suno. Учитесь создавать хиты!">
@endsection
@section('jsonld')
    @include('partials.seo.json-ld', [
        'include' => ['breadcrumb', 'articles-list'],
        'articles' => $articles,
        'breadcrumbs' => [
            ['name' => 'Главная', 'url' => url('/')],
            ['name' => 'Статьи'],
        ],
    ])
@endsection
@section('content')
<div class="max-w-7xl mx-auto px-4 md:px-8">
    <div class="articles-page">
        <nav class="breadcrumbs" style="max-width: 1280px; margin: 0 auto; padding: 0 0 20px;">
            <a href="/">Главная</a>
            <span class="breadcrumbs-sep">•</span>
            <span class="breadcrumbs-current">Статьи</span>
        </nav>
        <h1 class="articles-page-title">Статьи</h1>

        @if($articles->isEmpty())
            <div style="text-align:center;padding:60px 20px;color:#8f8f8f;">
                <div style="font-size:48px;margin-bottom:12px;">📝</div>
                <p>Пока нет статей</p>
            </div>
        @else
            <div class="articles-grid">
                @foreach($articles as $article)
                    <a href="{{ route('public.articles.show', $article->slug) }}" class="article-card">
                        <div class="article-card-cover">
                            @if($article->cover_url)
                                <img src="{{ $article->cover_thumb_url ?? $article->cover_url }}" alt="{{ $article->title }}">
                            @else
                                <div class="article-card-cover-placeholder">📝</div>
                            @endif
                        </div>
                        <div class="article-card-info">
                            <div class="article-card-title">{{ $article->title }}</div>
                            <div class="article-card-meta">
                                <span class="article-card-meta-item">{{ ($article->published_at ?? $article->created_at)->format('d.m.Y') }}</span>
                                <span class="article-card-meta-item">👁 {{ $article->views_count }}</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            @if($articles->hasPages())
                <div class="pagination-public">
                    @if($articles->onFirstPage())
                        <span class="page-nav disabled">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                            Назад
                        </span>
                    @else
                        <a href="{{ $articles->previousPageUrl() }}" class="page-nav">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                            Назад
                        </a>
                    @endif

                    @php
                        $current = $articles->currentPage();
                        $last = $articles->lastPage();
                        $from = max(1, $current - 2);
                        $to = min($last, $current + 2);
                    @endphp

                    <div class="pagination-pages">
                        @if($from > 1)
                            <a href="{{ $articles->url(1) }}">1</a>
                            @if($from > 2) <span class="page-dots">…</span> @endif
                        @endif
                        @for($i = $from; $i <= $to; $i++)
                            @if($i == $current)
                                <span class="page-num active">{{ $i }}</span>
                            @else
                                <a href="{{ $articles->url($i) }}">{{ $i }}</a>
                            @endif
                        @endfor
                        @if($to < $last)
                            @if($to < $last - 1) <span class="page-dots">…</span> @endif
                            <a href="{{ $articles->url($last) }}">{{ $last }}</a>
                        @endif
                    </div>

                    @if($articles->hasMorePages())
                        <a href="{{ $articles->nextPageUrl() }}" class="page-nav">
                            Вперёд
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                        </a>
                    @else
                        <span class="page-nav disabled">
                            Вперёд
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                        </span>
                    @endif
                </div>
            @endif
        @endif
    </div>
</div>
@endsection