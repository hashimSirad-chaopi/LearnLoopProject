@if ($paginator->hasPages())
    <nav class="pager" role="navigation" aria-label="Pagination">

        @if ($paginator->onFirstPage())
            <span class="pager-link is-disabled">&larr; Previous</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="pager-link" rel="prev">&larr; Previous</a>
        @endif

        <span class="pager-status">
            Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}
            <span class="cell-muted">({{ $paginator->total() }} total)</span>
        </span>

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="pager-link" rel="next">Next &rarr;</a>
        @else
            <span class="pager-link is-disabled">Next &rarr;</span>
        @endif

    </nav>
@endif
