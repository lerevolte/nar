@extends('layouts.public')

@section('title', $page->final_seo_title)

@section('jsonld')
    @php
        $slug = $page->slug ?? null;
        $isTariff = $slug === config('site.tariff.page_slug');
        $isHelp = $slug === config('site.help.page_slug');

        $jsonldInclude = ['breadcrumb'];
        if ($isTariff) {
            $jsonldInclude = array_merge($jsonldInclude, ['organization', 'tariff']);
        }
        if ($isHelp) {
            $jsonldInclude[] = 'help';
        }
    @endphp
    @include('partials.seo.json-ld', [
        'include' => $jsonldInclude,
        'breadcrumbs' => [
            ['name' => 'Главная', 'url' => url('/')],
            ['name' => $page->title],
        ],
    ])
@endsection

@section('meta')
    <meta name="description" content="{{ $page->final_seo_description }}">
    @if($page->seo_keywords)<meta name="keywords" content="{{ $page->seo_keywords }}">@endif
    @if($page->canonical_url)<link rel="canonical" href="{{ $page->canonical_url }}">@else<link rel="canonical" href="{{ url()->current() }}">@endif
    @if($page->noindex)<meta name="robots" content="noindex,nofollow">@endif
    <meta property="og:title" content="{{ $page->final_seo_title }}">
    <meta property="og:description" content="{{ $page->final_seo_description }}">
    <meta property="og:url" content="{{ url()->current() }}">
@endsection

@section('content')
<div class="static-page-wrap max-w-7xl mx-auto px-4 md:px-8 py-8">
    <nav class="breadcrumbs">
        <a href="/">Главная</a>
        <span class="breadcrumbs-sep">•</span>
        <span class="breadcrumbs-current">{{ $page->title }}</span>
    </nav>

    <h1 class="static-page-title">{{ $page->title }}</h1>

    @if($page->banner_text)
    <div class="static-page-banner"
        @if($page->banner_image)
            style="background-image: url('{{ $page->banner_image }}'); background-size: cover; background-position: center;"
        @elseif($page->banner_bg_color)
            style="background: {{ $page->banner_bg_color }};"
        @endif
    >
        <div class="static-page-banner-grid">
            <div class="static-page-banner-col-1"></div>
            <div class="static-page-banner-col-2">
                <div class="static-page-banner-text">
                    {{ $page->banner_text ?: $page->title }}
                </div>
            </div>
        </div>
    </div>
    @endif
    <div class="static-page-content">
        {!! $page->content_html !!}
    </div>
</div>
@endsection