@if ($paginator->hasPages())
    <nav class="pager">
        @if ($paginator->onFirstPage())
            <span class="pager-btn is-disabled">&lsaquo;</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="pager-btn" rel="prev">&lsaquo;</a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="pager-btn is-disabled">{{ $element }}</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="pager-btn is-current">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="pager-btn">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="pager-btn" rel="next">&rsaquo;</a>
        @else
            <span class="pager-btn is-disabled">&rsaquo;</span>
        @endif
    </nav>
@endif
