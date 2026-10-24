@extends('products.layout')
@section('products-content')
    <header class="ui-list-heading">
        <h1>Productos <small>Listado</small></h1>
        @can('products.view')<a href="{{ route('products.index') }}">Volver al Panel de Productos ➜</a>@else<a href="{{ route('inicio') }}">Volver al Panel de Inicio ➜</a>@endcan
    </header>
    @include('products.partials.catalog-feedback')
    @fragment('listing')
    <x-ui.listing data-async-list label="Productos">
        <form action="{{ route('products.catalog.index') }}" method="GET" class="products-options catalog-options">
            @foreach (['q', 'sort', 'direction'] as $key)@if(isset($filters[$key]))<input type="hidden" name="{{ $key }}" value="{{ $filters[$key] }}">@endif @endforeach
            <label for="product-family">Familias:</label>
            <select class="products-input" id="product-family" name="family"><option value="">Todas las familias</option>
                @foreach ($families as $family)<option value="{{ $family->id }}" @selected((string)($filters['family'] ?? '') === (string)$family->id)>{{ $family->nombre }}</option>@endforeach
            </select>
            <button class="products-button products-button--blue" type="submit">Buscar</button>
            @can('products.catalog.create')<a class="products-button products-button--blue" data-open-product href="{{ route('products.catalog.create', $filters) }}">✚ Agregar Producto</a>@endcan
        </form>
        <form action="{{ route('products.catalog.index') }}" method="GET" class="ui-list__filters">
            @foreach (['family', 'sort', 'direction'] as $key)@if(isset($filters[$key]))<input type="hidden" name="{{ $key }}" value="{{ $filters[$key] }}">@endif @endforeach
            <x-ui.toolbar export-route="products.catalog.export" :filters="$filters" :records="$records" search-id="products-search" />
        </form>
        <x-ui.table label="Productos" data-copy-title="Datos">
            <x-slot:head>
                @foreach ($columns as $column)
                    @php($selected = ($filters['sort'] ?? 'id') === $column['field'])
                    <x-ui.sort-heading :label="$column['label']" :selected="$selected" :direction="$filters['direction'] ?? 'asc'" :url="route('products.catalog.index', [...$filters, 'page' => 1, 'sort' => $column['field'], 'direction' => $selected && ($filters['direction'] ?? 'asc') === 'asc' ? 'desc' : 'asc'])" />
                @endforeach
                <th scope="col" data-copy-exclude>Fórmula</th>
            </x-slot:head>
            @forelse ($records as $record)
                <tr data-record-row @class(['catalog-product-inactive' => (int)$record->Estado !== 1])>
                    @foreach ($columns as $column)<td>{{ $record->{$column['field']} }}</td>@endforeach
                    <td data-copy-exclude>
                        @can('products.formulas.view')<x-ui.table-action color="cyan" icon="add" data-open-formulas :href="route('products.formulas.index', ['product' => $record->id, ...$filters])" aria-label="Consultar fórmula de {{ $record->Nombre }}" />
                        @else<x-ui.table-action color="cyan" icon="add" disabled aria-label="Sin permiso para consultar fórmulas" />@endcan
                    </td>
                </tr>
            @empty<tr><td colspan="5">No se encontraron registros.</td></tr>@endforelse
        </x-ui.table>
        <x-ui.pagination :records="$records" />
    </x-ui.listing>
    @endfragment
    @can('products.catalog.create')
        <dialog class="products-dialog catalog-dialog" data-product-create @if(request()->routeIs('products.catalog.create') || $errors->any()) data-reopen @endif aria-labelledby="product-create-title">
            <header><h2 id="product-create-title">Agregar Producto</h2><button type="button" data-close-product aria-label="Cerrar">×</button></header>
            <form method="POST" action="{{ route('products.catalog.store') }}" data-catalog-form>
                @csrf
                @foreach ($filters as $key => $value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endforeach
                <div class="ui-list__errors" data-catalog-errors role="alert" tabindex="-1" @if(!$errors->any()) hidden @endif>{{ implode(' ', $errors->all()) }}</div>
                @foreach (['nombre' => ['Nombre', 100], 'clave' => ['Clave', 50]] as $name => [$label, $length])
                    <div class="products-field"><label for="product-{{ $name }}">{{ $label }}:</label><input class="products-input" name="{{ $name }}" id="product-{{ $name }}" required maxlength="{{ $length }}" value="{{ old($name) }}"></div>
                @endforeach
                <div class="products-field"><label for="product-family-create">Familia:</label><select class="products-input" name="familia_id" id="product-family-create" required>
                    @foreach($families as $family)<option value="{{ $family->id }}" @selected((string)old('familia_id') === (string)$family->id)>{{ $family->nombre }}</option>@endforeach
                </select></div>
                <footer><button class="products-button" type="button" data-close-product>Cerrar</button><button class="products-button products-button--blue" type="submit">Guardar datos</button></footer>
            </form>
        </dialog>
    @endcan
@endsection
