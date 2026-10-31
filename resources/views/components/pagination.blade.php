@if($paginator->hasPages())
<nav class="pagination" aria-label="التنقل بين الصفحات">
    @if($paginator->onFirstPage())<span class="button button-small button-muted" aria-disabled="true">السابق</span>@else<a class="button button-small button-secondary" href="{{ $paginator->previousPageUrl() }}" rel="prev">السابق</a>@endif
    <span>الصفحة {{ $paginator->currentPage() }} @if(method_exists($paginator, 'lastPage')) من {{ $paginator->lastPage() }} @endif</span>
    @if($paginator->hasMorePages())<a class="button button-small button-secondary" href="{{ $paginator->nextPageUrl() }}" rel="next">التالي</a>@else<span class="button button-small button-muted" aria-disabled="true">التالي</span>@endif
</nav>
@endif
