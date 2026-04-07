<style>
    .pg-info {
    font-size: 13px;
    color: #6b7280;
}
.pg-info strong {
    color: #111827;
    font-weight: 500;
}
.pg-nav {
    display: flex;
    align-items: center;
    gap: 4px;
}
.pg-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 34px;
    height: 34px;
    padding: 0 6px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: #fff;
    color: #374151;
    font-size: 13px;
    text-decoration: none;
    transition: background 0.15s, border-color 0.15s;
}
.pg-btn:hover {
    background: #f9fafb;
    border-color: #d1d5db;
}
.pg-active {
    background: #111827 !important;
    color: #fff !important;
    border-color: #111827 !important;
    font-weight: 500;
}
.pg-ellipsis {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 28px;
    height: 34px;
    font-size: 13px;
    color: #9ca3af;
}
</style>

@if ($paginator->hasPages())
<div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; padding: 1rem 0;">

    {{-- Result count --}}
    <span class="pg-info">
        Showing <strong>{{ $paginator->firstItem() }}</strong>–<strong>{{ $paginator->lastItem() }}</strong>
        of <strong>{{ $paginator->total() }}</strong> results
    </span>

    {{-- Page buttons --}}
    <nav class="pg-nav" aria-label="Pagination">

        {{-- Prev --}}
        @if ($paginator->onFirstPage())
            <span class="pg-btn" aria-disabled="true" style="opacity:.35; cursor:not-allowed;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}&{{ $paginator->getPageName() }}={{ $paginator->currentPage() - 1 }}" class="pg-btn" aria-label="Previous page">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            </a>
        @endif

        {{-- Page numbers --}}
        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="pg-ellipsis">…</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="pg-btn pg-active" aria-current="page">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="pg-btn">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="pg-btn" aria-label="Next page">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
            </a>
        @else
            <span class="pg-btn" style="opacity:.35; cursor:not-allowed;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
            </span>
        @endif

    </nav>
</div>
@endif
