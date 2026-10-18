@props(['exportRoute', 'filters' => [], 'records', 'searchId' => 'list-search'])
<div class="ui-list__toolbar">
    <div class="ui-list__tools" aria-label="Exportar datos">
        <a class="ui-list__export" href="{{ route($exportRoute, [...$filters, 'format' => 'xlsx']) }}" aria-label="Descargar listado en Excel">Excel</a>
        <a class="ui-list__export" href="{{ route($exportRoute, [...$filters, 'format' => 'pdf']) }}" aria-label="Descargar listado en PDF">PDF</a>
        <button class="ui-list__export" type="button" data-copy-page @disabled($records->isEmpty()) aria-label="Copiar los registros de esta página">Copy</button>
        <span class="ui-list__copy-status" role="status" aria-live="polite" data-copy-status></span>
    </div>
    <div class="ui-list__search">
        <label for="{{ $searchId }}">Buscar:</label>
        <input class="ui-list__input" id="{{ $searchId }}" name="q" type="search" value="{{ $filters['q'] ?? '' }}" maxlength="100" placeholder="Buscar registros" aria-describedby="{{ $searchId }}-help">
        <span id="{{ $searchId }}-help" class="ui-list__sr-only">Presiona Enter para aplicar la búsqueda.</span>
        <button class="ui-list__sr-only" type="submit">Aplicar búsqueda</button>
    </div>
</div>
