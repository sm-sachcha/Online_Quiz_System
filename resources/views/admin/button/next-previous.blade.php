<style>
    .pg-info {
        font-size: 13px;
        color: #64748b;
    }
    .pg-info strong {
        color: #0f172a;
        font-weight: 700;
    }
    .pg-nav {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }
    .pg-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 38px;
        height: 38px;
        padding: 0 10px;
        border: 1px solid #dbe2ea;
        border-radius: 12px;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        color: #334155;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        box-shadow: 0 4px 14px rgba(15, 23, 42, 0.05);
        transition: transform 0.15s ease, background 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
    }
    .pg-btn:hover {
        transform: translateY(-1px);
        background: #f8fafc;
        border-color: #cbd5e1;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08);
        color: #0f172a;
    }
    .pg-active {
        background: linear-gradient(135deg, #0f172a 0%, #334155 100%) !important;
        color: #fff !important;
        border-color: #0f172a !important;
        font-weight: 700;
        box-shadow: 0 10px 20px rgba(15, 23, 42, 0.18);
    }
    .pg-ellipsis {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 28px;
        height: 38px;
        font-size: 13px;
        color: #94a3b8;
    }
    .pg-disabled {
        opacity: .4;
        cursor: not-allowed;
        pointer-events: none;
        box-shadow: none;
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
            <span class="pg-btn pg-disabled" aria-disabled="true">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="pg-btn" aria-label="Previous page">
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
            <span class="pg-btn pg-disabled" aria-disabled="true">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
            </span>
        @endif

    </nav>
</div>
@endif
