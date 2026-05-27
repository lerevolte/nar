@extends('layouts.public')

@section('title', $page->final_seo_title)

@section('jsonld')
    @php
        $breadcrumbs = [['name' => 'Главная', 'url' => url('/')]];
        if (($page->parent_id ?? null) && isset($rootPage)) {
            $breadcrumbs[] = [
                'name' => $rootPage->title,
                'url' => route('public.pages.show', $rootPage->slug),
            ];
        }
        $breadcrumbs[] = ['name' => $page->title];
    @endphp
    @include('partials.seo.json-ld', [
        'include' => ['breadcrumb'],
        'breadcrumbs' => $breadcrumbs,
    ])
@endsection

@section('meta')
    <meta name="description" content="{{ $page->final_seo_description }}">
    @if($page->seo_keywords)
        <meta name="keywords" content="{{ $page->seo_keywords }}">
    @endif
    @if($page->canonical_url)
        <link rel="canonical" href="{{ $page->canonical_url }}">
    @else
        <link rel="canonical" href="{{ url()->current() }}">
    @endif
    @if($page->noindex)
        <meta name="robots" content="noindex,nofollow">
    @endif

    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $page->final_seo_title }}">
    <meta property="og:description" content="{{ $page->final_seo_description }}">
    <meta property="og:url" content="{{ url()->current() }}">
    @if($page->og_image)
        <meta property="og:image" content="{{ url($page->og_image) }}">
    @endif
@endsection

@section('content')
<div class="page-wrap max-w-7xl mx-auto px-4 md:px-8 py-8">
    <div class="page-layout">
        <!-- Sidebar (siblings) -->
        <aside>
            @if($siblings->count() > 0)
                <div class="article-side page-sidebar" id="page-sidebar">
                    <span class="article-side-title">{{ $rootPage->title }}</span>
                    <ul class="article-side-list">
            

                        @php
                            $visibleSiblings = $siblings->take(6);
                            $hiddenSiblings = $siblings->slice(6);
                        @endphp

                        @foreach($visibleSiblings as $sib)
                            <li>
                                <a href="{{ route('public.pages.show.child', [$rootPage->slug, $sib->slug]) }}"
                                   class="{{ $page->id === $sib->id ? 'active' : '' }}">
                                    {{ $sib->title }}
                                </a>
                            </li>
                        @endforeach

                        @if($hiddenSiblings->count() > 0)
                            <li class="others-item">
                                <a href="javascript:;" id="others-trigger" class="open">
                                    Другие ({{ $hiddenSiblings->count() }}) <span class="others-arrow">▸</span>
                                </a>
                                <div class="others-popup" id="others-popup">
                                    <ul class="article-side-list">
                                        @foreach($hiddenSiblings as $sib)
                                            <li>
                                                <a href="{{ route('public.pages.show.child', [$rootPage->slug, $sib->slug]) }}"
                                                   class="{{ $page->id === $sib->id ? 'active' : '' }}">
                                                    {{ $sib->title }}
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </li>
                        @endif
                    </ul>
                </div>
            @endif
        </aside>

        <!-- Main content -->
        <div class="page-content">
            <!-- Хлебные крошки -->
            <nav class="breadcrumbs">
                <a href="/">Главная</a>
                @if($page->parent_id)
                    <span class="breadcrumbs-sep">•</span>
                    <a href="{{ route('public.pages.show', $rootPage->slug) }}">{{ $rootPage->title }}</a>
                    <span class="breadcrumbs-sep">•</span>
                    <span class="breadcrumbs-current">{{ $page->title }}</span>
                @else
                    <span class="breadcrumbs-sep">•</span>
                    <span class="breadcrumbs-current">{{ $page->title }}</span>
                @endif
            </nav>

            <h1 class="page-title">{{ $page->title }}</h1>

            <div class="article-content">
                @include('public.partials.blocks', ['blocks' => $page->blocks])
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {

        var trigger = document.getElementById('others-trigger');
        var popup = document.getElementById('others-popup');

        if (trigger && popup) {
            trigger.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                popup.classList.toggle('open');
                trigger.classList.toggle('open');
            });

            document.addEventListener('click', function(e) {
                if (popup.classList.contains('open')) {
                    if (!popup.contains(e.target) && !trigger.contains(e.target)) {
                        popup.classList.remove('open');
                        trigger.classList.remove('open');
                    }
                }
            });
        }

        // Sidebar collapse on mobile
        var sidebarTitle = document.querySelector('.page-sidebar .article-side-title');
        var sidebarList = document.querySelector('.page-sidebar .article-side-list');

        if (sidebarTitle && sidebarList) {
            sidebarTitle.addEventListener('click', function() {
                if (window.innerWidth > 960) return;
                sidebarList.classList.toggle('collapsed');
                sidebarTitle.classList.toggle('collapsed');
            });

            // По умолчанию свернуть на мобильном
            if (window.innerWidth <= 960) {
                sidebarList.classList.add('collapsed');
                sidebarTitle.classList.add('collapsed');
            }
        }
    });
</script>

@endpush