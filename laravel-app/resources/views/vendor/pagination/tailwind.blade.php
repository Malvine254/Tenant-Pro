@if ($paginator->hasPages())
    <nav aria-label="Pagination">
        @if ($paginator->onFirstPage())
            <span aria-disabled="true">&lsaquo; Previous</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev">&lsaquo; Previous</a>
        @endif

        @foreach ($paginator->getUrlRange(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $page => $url)
            @if ($page == $paginator->currentPage())
                <span class="active"><span>{{ $page }}</span></span>
            @else
                <a href="{{ $url }}">{{ $page }}</a>
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next">Next &rsaquo;</a>
        @else
            <span aria-disabled="true">Next &rsaquo;</span>
        @endif
    </nav>
@endif
