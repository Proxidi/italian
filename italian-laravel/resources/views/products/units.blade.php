@extends('products.layout')

@section('products-content')
    @php
        $oldName = is_string(old('nombre')) ? old('nombre') : '';
        $oldUnitId = is_scalar(old('_unit_id')) && ctype_digit((string)old('_unit_id')) ? old('_unit_id') : 0;
    @endphp
    <header class="products-heading">
        <h1>Unidades <small>Listado</small></h1>
        @can('products.view')<a href="{{ route('products.index') }}">Volver al Panel de Productos <span aria-hidden="true">➜</span></a>
        @else<a href="{{ route('inicio') }}">Volver al Panel de Inicio <span aria-hidden="true">➜</span></a>@endcan
    </header>
    @if (session('unit-success') && !$errors->any())
        <div class="products-success" role="status" data-unit-success><span aria-hidden="true">✓</span> {{ session('unit-success') }}</div>
    @endif
    <section class="products-box" aria-label="Unidades">
        @if ($errors->any())<div class="products-errors" role="alert"><p>No se guardaron los cambios:</p><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <div class="products-options"><span>Opciones:</span>@can('products.units.create')<button class="products-button products-button--green" type="button" data-new-unit><span aria-hidden="true">✚</span> Agregar Unidad</button>@endcan</div>
        <div class="products-toolbar">
            <div class="products-tools" aria-label="Exportar datos">
                <a class="products-button" href="{{ route('products.units.export', ['q' => $search, 'direction' => $direction, 'format' => 'xlsx']) }}">Excel</a>
                <a class="products-button" href="{{ route('products.units.export', ['q' => $search, 'direction' => $direction, 'format' => 'pdf']) }}">PDF</a>
                <button class="products-button" type="button" data-copy-units>Copy</button><span role="status" class="products-sr-only" data-copy-status></span>
            </div>
            <form class="products-search" action="{{ route('products.units.index') }}" method="GET">
                <input name="direction" type="hidden" value="{{ $direction }}"><label for="unit-search">Buscar:</label>
                <input id="unit-search" class="products-input" name="q" type="search" value="{{ $search }}" maxlength="100" placeholder="Buscar registros" aria-describedby="search-help">
                <span class="products-sr-only" id="search-help">Presiona Enter para buscar.</span><button class="products-sr-only" type="submit">Buscar</button>
            </form>
        </div>
        <div class="products-scroll" role="region" aria-label="Listado de unidades" tabindex="0">
            <table class="products-table">
                <thead><tr><th scope="col" aria-sort="{{ $direction === 'asc' ? 'ascending' : 'descending' }}"><a href="{{ route('products.units.index', ['q' => $search, 'direction' => $direction === 'asc' ? 'desc' : 'asc']) }}">Nombre <span aria-hidden="true">↕</span></a></th><th scope="col">Editar</th></tr></thead>
                <tbody>@forelse ($records as $unit)
                    <tr data-unit-row><td data-unit-name>{{ $unit->Nombre }}</td><td>@can('products.units.update')<button class="products-button products-button--green" type="button" data-edit-unit data-id="{{ $unit->id }}" data-name="{{ $unit->Nombre }}" data-action="{{ route('products.units.update', $unit->id) }}" aria-label="Editar unidad {{ $unit->Nombre }}"><span aria-hidden="true">✎</span></button>@else<span>Sin permiso de edición</span>@endcan</td></tr>
                @empty<tr><td colspan="2">No se encontraron registros.</td></tr>@endforelse</tbody>
            </table>
        </div>
        <footer class="products-pagination"><p role="status">@if ($records->total())Mostrando registros de {{ $records->firstItem() }} al {{ $records->lastItem() }} de un total de {{ $records->total() }} registros @else No existen registros. @endif</p><nav aria-label="Paginación">@if ($records->previousPageUrl())<a href="{{ $records->previousPageUrl() }}">Anterior</a>@else<span>Anterior</span>@endif <strong aria-current="page">{{ $records->currentPage() }}</strong> @if ($records->nextPageUrl())<a href="{{ $records->nextPageUrl() }}">Siguiente</a>@else<span>Siguiente</span>@endif</nav></footer>
    </section>
    @foreach (['create', 'update'] as $operation)
        @can('products.units.'.$operation)
            @php($isEdit = $operation === 'update')
            <dialog class="products-dialog" data-unit-dialog="{{ $operation }}" @if ($errors->has('nombre') && old('_operation') === $operation) data-reopen @endif aria-labelledby="unit-title-{{ $operation }}">
                <header><h2 id="unit-title-{{ $operation }}">{{ $isEdit ? 'Editar Unidad' : 'Nueva Unidad' }}</h2><button type="button" data-close-unit aria-label="Cerrar">×</button></header>
                <form action="{{ $isEdit ? route('products.units.update', $oldUnitId) : route('products.units.store') }}" method="POST" data-unit-form>
                    @csrf @if ($isEdit) @method('PUT') @endif
                    <input type="hidden" name="_operation" value="{{ $operation }}"><input type="hidden" name="_unit_id" value="{{ $oldUnitId }}">
                    <div class="products-field"><label for="unit-name-{{ $operation }}">Nombre:</label><input class="products-input" id="unit-name-{{ $operation }}" name="nombre" type="text" required maxlength="50" value="{{ old('_operation') === $operation ? $oldName : '' }}" aria-describedby="unit-error-{{ $operation }}">
                    <p id="unit-error-{{ $operation }}" class="products-field-error">@if (old('_operation') === $operation) @error('nombre'){{ $message }}@enderror @endif</p></div>
                    <footer><button class="products-button" type="button" data-close-unit>Cerrar</button><button class="products-button products-button--blue" type="submit">Guardar datos</button></footer>
                </form>
            </dialog>
        @endcan
    @endforeach
@endsection
