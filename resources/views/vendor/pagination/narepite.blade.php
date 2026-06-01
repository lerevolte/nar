@if ($paginator->hasPages())
    <nav class="pager" role="navigation" aria-label="Pagination">
        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
            <span class="pager-btn pager-btn--disabled" aria-disabled="true">←</span>
        @else
            <a class="pager-btn" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Назад">←</a>
        @endif

        {{-- Pagination Elements --}}
        @foreach ($elements as $element)
            {{-- "Three Dots" Separator --}}
            @if (is_string($element))
                <span class="pager-dots" aria-disabled="true">{{ $element }}</span>
            @endif

            {{-- Array Of Links --}}
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="pager-btn pager-btn--active" aria-current="page">{{ $page }}</span>
                    @else
                        <a class="pager-btn" href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
            <a class="pager-btn" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Вперёд">→</a>
        @else
            <span class="pager-btn pager-btn--disabled" aria-disabled="true">→</span>
        @endif
    </nav>
@endif
