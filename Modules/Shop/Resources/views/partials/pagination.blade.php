@if ($paginator->hasPages())
    <nav class="pager" aria-label="الصفحات">
        @if ($paginator->previousPageUrl())
            <a rel="prev" href="{{ $paginator->previousPageUrl() }}">السابق</a>
        @endif
        <span>صفحة {{ $paginator->currentPage() }} من {{ $paginator->lastPage() }}</span>
        @if ($paginator->nextPageUrl())
            <a rel="next" href="{{ $paginator->nextPageUrl() }}">التالي</a>
        @endif
    </nav>
@endif
