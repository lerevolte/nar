@extends('layouts.public')

@section('title', $article->final_seo_title)

@section('meta')
    <meta name="description" content="{{ $article->final_seo_description }}">
    @if($article->seo_keywords)
        <meta name="keywords" content="{{ $article->seo_keywords }}">
    @endif
    @if($article->canonical_url)
        <link rel="canonical" href="{{ $article->canonical_url }}">
    @else
        <link rel="canonical" href="{{ url()->current() }}">
    @endif
    @if($article->noindex)
        <meta name="robots" content="noindex,nofollow">
    @endif

    <meta property="og:type" content="article">
    <meta property="og:title" content="{{ $article->final_seo_title }}">
    <meta property="og:description" content="{{ $article->final_seo_description }}">
    <meta property="og:url" content="{{ url()->current() }}">
    @if($article->og_image ?: $article->banner_url ?: $article->cover_url)
        <meta property="og:image" content="{{ url($article->og_image ?: $article->banner_url ?: $article->cover_url) }}">
    @endif
    <meta property="og:site_name" content="НА РЕПИТЕ">
    @if($article->published_at)
        <meta property="article:published_time" content="{{ $article->published_at->toIso8601String() }}">
    @endif

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $article->final_seo_title }}">
    <meta name="twitter:description" content="{{ $article->final_seo_description }}">
    @if($article->og_image ?: $article->banner_url ?: $article->cover_url)
        <meta name="twitter:image" content="{{ url($article->og_image ?: $article->banner_url ?: $article->cover_url) }}">
    @endif

@endsection

@section('jsonld')
    @include('partials.seo.json-ld', [
        'include' => ['organization', 'breadcrumb', 'blog-posting'],
        'breadcrumbs' => [
            ['name' => 'Главная', 'url' => url('/')],
            ['name' => 'Статьи', 'url' => route('public.articles.index')],
            ['name' => $article->title],
        ],
    ])
@endsection

@section('content')
<div class="max-w-7xl mx-auto px-4 md:px-8">
    <div class="article-page-wrap">
        <nav class="breadcrumbs" style="max-width: 1280px; margin: 0 auto; padding: 0 0 20px;">
            <a href="/">Главная</a>
            <span class="breadcrumbs-sep">•</span>
            <a href="{{ route('public.articles.index') }}">Статьи</a>
            <span class="breadcrumbs-sep">•</span>
            <span class="breadcrumbs-current">{{ $article->title }}</span>
        </nav>
        <div class="article-top">
            <div class="article-top-wrap"
                @if($article->banner_url)
                    style="background: url('{{ $article->banner_url }}') center/cover no-repeat !important;"
                @endif
            >
                <h1 class="article-top-title">{{ $article->title }}</h1>
            </div>
            <div class="article-top-panel">
                <div class="article-top-panel-wrap">
                    <div class="article-top-panel__user"></div>
                    <div class="article-top-panel__right">
                        <div class="article-top-panel__date">
                            {{ ($article->published_at ?? $article->created_at)->locale('ru')->isoFormat('D MMMM YYYY') }}
                        </div>
                        <div class="article-top-panel__views">
                            <span>👁 {{ $article->views_count }}</span>
                        </div>
                        <div class="article-top-panel__reading">
                            Время чтения: <span>{{ $readingTime }} мин</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="article-layout">
            <div class="article-content" id="article-content">
                @include('public.partials.blocks', ['blocks' => $article->blocks])
            </div>

            <aside>
                <div class="article-side collapsed" id="article-toc" style="display:none;">
                    <span class="article-side-title" onclick="toggleTocMobile()">Содержание</span>
                    <ul class="article-side-list" id="toc-list"></ul>
                </div>
            </aside>
        </div>

        @if($relatedArticles->isNotEmpty())
            <div class="related-articles-section">
                <h2 class="related-articles-title">Читайте также</h2>
                <div class="related-slider-wrap">
                    <div class="related-slider" id="related-slider">
                        @foreach($relatedArticles as $rel)
                            <a href="{{ route('public.articles.show', $rel->slug) }}" class="article-card">
                                <div class="article-card-cover">
                                    @if($rel->cover_url)
                                        <img src="{{ $rel->cover_thumb_url ?? $rel->cover_url }}" alt="{{ $rel->title }}" draggable="false">
                                    @else
                                        <div class="article-card-cover-placeholder">📝</div>
                                    @endif
                                </div>
                                <div class="article-card-info">
                                    <div class="article-card-title">{{ $rel->title }}</div>
                                    <div class="article-card-meta">
                                        <span class="article-card-meta-item">{{ ($rel->published_at ?? $rel->created_at)->format('d.m.Y') }}</span>
                                        <span class="article-card-meta-item">👁 {{ $rel->views_count }}</span>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    // === TOC generation + scroll-spy + click highlight ===
(function() {
    var content = document.getElementById('article-content');
    var tocBox = document.getElementById('article-toc');
    var tocList = document.getElementById('toc-list');
    if (!content || !tocList) return;

    var headings = content.querySelectorAll('h1, h2, h3, h4');
    if (headings.length < 2) return;

    tocBox.style.display = '';
    var items = [];
    headings.forEach(function(h, i) {
        if (!h.id) h.id = 'toc-h-' + i;
        var li = document.createElement('li');
        li.className = 'toc-item toc-level-' + h.tagName.toLowerCase();
        var a = document.createElement('a');
        a.href = '#' + h.id;
        a.textContent = h.textContent;
        a.addEventListener('click', function(e) {
            e.preventDefault();
            window.scrollTo({ top: h.offsetTop - 80, behavior: 'smooth' });
            highlightSection(h);
        });
        li.appendChild(a);
        tocList.appendChild(li);
        items.push({ el: h, link: li });
    });

    function updateActive() {
        var scrollY = window.scrollY + 120;
        var activeIdx = -1;

        // Низ контентной зоны (последний item.el + его ориентировочная высота)
        var lastItem = items[items.length - 1];
        var lastBottom = lastItem.el.getBoundingClientRect().bottom + window.scrollY;

        // Если проскроллили ниже последнего заголовка — он активный
        if (scrollY >= lastBottom - 100) {
            activeIdx = items.length - 1;
        } else {
            for (var i = 0; i < items.length; i++) {
                if (items[i].el.getBoundingClientRect().top + window.scrollY <= scrollY) {
                    activeIdx = i;
                }
            }
        }

        items.forEach(function(it, i) {
            it.link.classList.remove('active');
            it.link.classList.toggle('visited', i < activeIdx);
            if (i === activeIdx) it.link.classList.add('active');
        });
    }
    window.addEventListener('scroll', updateActive);
    window.addEventListener('resize', updateActive);
    updateActive();

    // === Подсветка секции при клике ===
    // Классы-кандидаты обёрток, которые подсвечиваем целиком
    var WRAPPER_CLASSES = [
        'article-block-gradient',
        'article-block-image',
        'article-block-songs',
        'article-block-articles'
    ];

    function findWrapper(el) {
        var node = el;
        while (node && node !== content) {
            if (node.classList) {
                for (var i = 0; i < WRAPPER_CLASSES.length; i++) {
                    if (node.classList.contains(WRAPPER_CLASSES[i])) return node;
                }
            }
            node = node.parentElement;
        }
        return null;
    }

    function highlightSection(heading) {
        // Снимаем предыдущую подсветку
        document.querySelectorAll('.toc-highlighted').forEach(function(el) {
            el.classList.remove('toc-highlighted');
        });

        var targets = [];

        // 1) Если сам заголовок внутри обёртки — подсвечиваем её
        var wrapper = findWrapper(heading);
        if (wrapper) {
            targets.push(wrapper);
        } else {
            // 2) Иначе подсвечиваем сам заголовок + всё до следующего заголовка того же/высшего уровня
            targets.push(heading);
            var level = parseInt(heading.tagName.substring(1), 10);
            var node = heading.nextElementSibling;

            while (node) {
                if (/^H[1-6]$/.test(node.tagName)) {
                    var nodeLevel = parseInt(node.tagName.substring(1), 10);
                    if (nodeLevel <= level) break;
                }
                // Если встретили обёрточный блок — он весь идёт целиком, пропускаем внутренние заголовки
                var innerWrapper = null;
                for (var i = 0; i < WRAPPER_CLASSES.length; i++) {
                    if (node.classList && node.classList.contains(WRAPPER_CLASSES[i])) {
                        innerWrapper = node;
                        break;
                    }
                }
                if (innerWrapper) {
                    targets.push(innerWrapper);
                } else {
                    targets.push(node);
                }
                node = node.nextElementSibling;
            }
        }

        targets.forEach(function(n) { n.classList.add('toc-highlighted'); });

        setTimeout(function() {
            targets.forEach(function(n) { n.classList.remove('toc-highlighted'); });
        }, 3000);
    }
})();

    window.toggleTocMobile = function() {
        var toc = document.getElementById('article-toc');
        if (toc) toc.classList.toggle('collapsed');
    };

    // === Related slider drag ===
    (function() {
        var slider = document.getElementById('related-slider');
        if (!slider) return;
        var isDown = false, startX, scrollLeft, hasMoved = false;

        slider.addEventListener('mousedown', function(e) {
            isDown = true; hasMoved = false;
            slider.style.cursor = 'grabbing';
            startX = e.pageX - slider.offsetLeft;
            scrollLeft = slider.scrollLeft;
        });
        slider.addEventListener('mouseleave', function() { isDown = false; slider.style.cursor = 'grab'; });
        slider.addEventListener('mouseup', function() { isDown = false; slider.style.cursor = 'grab'; });
        slider.addEventListener('mousemove', function(e) {
            if (!isDown) return;
            e.preventDefault();
            var x = e.pageX - slider.offsetLeft;
            var walk = (x - startX) * 1.5;
            if (Math.abs(walk) > 5) hasMoved = true;
            slider.scrollLeft = scrollLeft - walk;
        });
        slider.addEventListener('click', function(e) {
            if (hasMoved) { e.stopPropagation(); e.preventDefault(); }
        }, true);
    })();
</script>
@endpush