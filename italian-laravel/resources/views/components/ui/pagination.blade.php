@props(['records'])
<footer class="ui-list__pagination">
    <p role="status">@if ($records->total() > 0)Mostrando registros de {{ $records->firstItem() ?? 0 }} al {{ $records->lastItem() ?? 0 }} de un total de {{ $records->total() }} registros @else No existen registros. @endif</p>
    <nav aria-label="Paginación del listado">
        @if ($records->onFirstPage())<span class="ui-list__page is-disabled" aria-disabled="true">Anterior</span>
        @else<a class="ui-list__page" href="{{ $records->previousPageUrl() }}">Anterior</a>@endif
        <span class="ui-list__page is-current" aria-current="page">{{ $records->currentPage() }}</span>
        @if ($records->hasMorePages())<a class="ui-list__page" href="{{ $records->nextPageUrl() }}">Siguiente</a>
        @else<span class="ui-list__page is-disabled" aria-disabled="true">Siguiente</span>@endif
    </nav>
</footer>
