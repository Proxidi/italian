@extends('products.layout')

@section('products-content')
    @php
        $oldName = is_string(old('nombre')) ? old('nombre') : '';
        $oldUnitId = is_scalar(old('_unit_id')) && ctype_digit((string)old('_unit_id')) ? old('_unit_id') : 0;
    @endphp
    <header class="ui-list-heading">
        <h1>Unidades <small>Listado</small></h1>
        @can('products.view')<a href="{{ route('products.index') }}">Volver al Panel de Productos <span aria-hidden="true">➜</span></a>
        @else<a href="{{ route('inicio') }}">Volver al Panel de Inicio <span aria-hidden="true">➜</span></a>@endcan
    </header>
    @if (session('unit-success') && !$errors->any())
        @if (session('unit-updated'))
            <x-ui.feedback variant="dialog" position="corner" :title="session('unit-success')" :duration="1000" id="unit-success" data-unit-success />
        @else
            <x-ui.feedback variant="notice" :title="session('unit-success')" data-unit-success />
        @endif
    @endif
    @fragment('listing')
    <x-ui.listing data-async-list label="Unidades">
        @if ($errors->any())<div class="ui-list__errors" role="alert"><p>No se guardaron los cambios:</p><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <div class="products-options"><span>Opciones:</span>@can('products.units.create')<button class="products-button products-button--green" type="button" data-new-unit><span aria-hidden="true">✚</span> Agregar Unidad</button>@endcan</div>
        <form action="{{ route('products.units.index') }}" method="GET" class="ui-list__filters">
            <input name="direction" type="hidden" value="{{ $direction }}">
            <x-ui.toolbar export-route="products.units.export" :filters="['q' => $search, 'direction' => $direction]" :records="$records" search-id="unit-search" />
        </form>
        <x-ui.table label="Unidades" data-copy-title="Unidades Insumos">
            <x-slot:head>
                <x-ui.sort-heading label="Nombre" :selected="true" :direction="$direction"
                    :url="route('products.units.index', ['q' => $search, 'direction' => $direction === 'asc' ? 'desc' : 'asc'])" />
                <th scope="col" class="ui-list__action-column" data-copy-exclude>Editar</th>
            </x-slot:head>
            @forelse ($records as $unit)
                <tr data-record-row><td>{{ $unit->Nombre }}</td><td data-copy-exclude>
                    @can('products.units.update')
                        <x-ui.table-action color="green" data-edit-unit data-id="{{ $unit->id }}" data-name="{{ $unit->Nombre }}" data-action="{{ route('products.units.update', $unit->id) }}" aria-label="Editar unidad {{ $unit->Nombre }}" />
                    @else<span>Sin permiso de edición</span>@endcan
                </td></tr>
            @empty<tr><td colspan="2">No se encontraron registros.</td></tr>@endforelse
        </x-ui.table>
        <x-ui.pagination :records="$records" />
    </x-ui.listing>
    @endfragment
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
