@if ($paginator->hasPages())
    <div class="pagination">
        <span class="pagination-info">
            Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }} products
        </span>
        <div class="pagination-controls">

            {{-- Prev --}}
            @if ($paginator->onFirstPage())
                <button class="page-btn" disabled>‹</button>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="page-btn">‹</a>
            @endif

            {{-- Page numbers --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <button class="page-btn" disabled>{{ $element }}</button>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <button class="page-btn active">{{ $page }}</button>
                        @else
                            <a href="{{ $url }}" class="page-btn">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="page-btn">›</a>
            @else
                <button class="page-btn" disabled>›</button>
            @endif

        </div>
    </div>
@endif