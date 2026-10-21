@extends('products.layout')

@section('products-content')
    <p class="ui-list__errors" data-ingredient-load-status role="status" hidden></p>
    <template data-ingredient-success-template>
        <x-ui.feedback variant="dialog" position="corner" title="" :auto-open="false" :duration="1500" id="ingredient-async-success" />
    </template>
    <noscript><style>
        .products-dialog { display: block; position: static; margin: 15px auto; }
        .products-dialog button[type="button"] { display: none; }
    </style></noscript>
    <header class="ui-list-heading">
        <h1>Insumos <small>Listado</small></h1>
        @can('products.view')<a href="{{ route('products.index') }}">Volver al Panel de Productos <span aria-hidden="true">➜</span></a>
        @else<a href="{{ route('inicio') }}">Volver al Panel de Inicio <span aria-hidden="true">➜</span></a>@endcan
    </header>
    @if (session('ingredient-success') && !$errors->any())
        <x-ui.feedback variant="dialog" position="corner" :title="session('ingredient-success')" :duration="1500" id="ingredient-success" />
        <noscript><p class="ui-feedback ui-feedback--notice" role="status">{{ session('ingredient-success') }}</p></noscript>
    @endif
    @fragment('listing')
    <x-ui.listing data-async-list label="Insumos">
        @if ($errors->any() && !$ingredient)<div class="ui-list__errors" role="alert">No se guardaron los cambios.</div>@endif
        <div class="products-options ingredients-options">
            <button class="products-button products-button--blue" type="button" disabled aria-describedby="ingredient-create-pending"><span aria-hidden="true">✚</span> Agregar Insumo</button>
            <span id="ingredient-create-pending">Alta pendiente de definición.</span>
        </div>
        <form action="{{ route('products.ingredients.index') }}" method="GET" class="ui-list__filters">
            <input name="sort" type="hidden" value="{{ $filters['sort'] ?? 'id' }}"><input name="direction" type="hidden" value="{{ $filters['direction'] ?? 'asc' }}">
            <x-ui.toolbar export-route="products.ingredients.export" :filters="$filters" :records="$records" search-id="ingredients-search" />
        </form>
        <x-ui.table label="Insumos" :wide="true" data-copy-title="Datos Insumos">
            <x-slot:head>
                @foreach ($columns as $column)
                    @php
                        $selected = ($filters['sort'] ?? 'id') === $column['field'];
                    @endphp
                    <x-ui.sort-heading :label="$column['label']" :selected="$selected" :direction="$filters['direction'] ?? 'asc'"
                        :url="route('products.ingredients.index', [...$filters, 'page' => 1, 'sort' => $column['field'], 'direction' => $selected && ($filters['direction'] ?? 'asc') === 'asc' ? 'desc' : 'asc'])" />
                @endforeach
                <th scope="col" class="ui-list__action-column" data-copy-exclude>Operaciones</th>
            </x-slot:head>
            @forelse ($records as $record)
                <tr data-record-row>@foreach ($columns as $column)<td>{{ $record->{$column['field']} }}</td>@endforeach
                    <td data-copy-exclude><x-ui.table-action color="green" data-open-conversions :href="route('products.ingredients.conversions', ['ingredient' => $record->id, ...$filters])" aria-label="Configurar insumo {{ $record->Nombre }}" /></td>
                </tr>
            @empty<tr><td colspan="7">No se encontraron registros.</td></tr>@endforelse
        </x-ui.table>
        <x-ui.pagination :records="$records" />
    </x-ui.listing>
    @endfragment
    @if ($ingredient)
        @php
            $oldConversion = $conversions->first(fn ($row) => (string)$row->id === (is_scalar(old('_conversion_id')) ? (string)old('_conversion_id') : ''));
            $canEdit = $conversions->count() === 1 && auth('web')->user()->can('products.ingredients.conversions.update');
        @endphp
        <dialog class="products-dialog" data-ingredient-conversions data-return-url="{{ route('products.ingredients.index', $filters) }}" aria-labelledby="conversions-title">
            <header><h2 id="conversions-title">Configurando Insumo</h2><button type="button" data-close-conversions aria-label="Cerrar">×</button></header>
            <div class="ingredients-conversions-body">
                <p>{{ $ingredient->Nombre }}</p>
                @if ($errors->any())<div class="ui-list__errors" role="alert"><p>No se guardaron los cambios:</p><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
                @if ($conversions->count() > 1)<p role="status">Existen varias conversiones para este insumo. Su edición requiere revisión.</p>@endif
                <x-ui.table label="Operaciones del insumo">
                    <x-slot:head><th scope="col">Bodega</th><th scope="col">Pedido</th><th scope="col" data-copy-exclude>Editar</th></x-slot:head>
                    @forelse ($conversions as $conversion)
                        <tr><td>{{ $conversion->Bodega }}</td><td>{{ $conversion->Pedido }}</td><td>
                            @if ($canEdit)
                                <x-ui.table-action color="cyan" data-edit-conversion data-id="{{ $conversion->id }}" data-bodega="{{ $conversion->Bodega }}" data-pedido="{{ $conversion->Pedido }}"
                                    data-action="{{ route('products.ingredients.conversions.update', ['ingredient' => $ingredient->id, 'conversion' => $conversion->id]) }}" aria-label="Editar operaciones de {{ $ingredient->Nombre }}" />
                            @else<span>Edición no disponible</span>@endif
                        </td></tr>
                    @empty<tr><td colspan="3">Este insumo no tiene operaciones configuradas.</td></tr>@endforelse
                </x-ui.table>
            </div>
            <footer><a class="products-button" data-close-conversions href="{{ route('products.ingredients.index', $filters) }}">Cerrar</a></footer>
        </dialog>
        @if ($canEdit)
            <dialog class="products-dialog" data-conversion-edit @if ($errors->any() && $oldConversion) data-reopen @endif aria-labelledby="conversion-edit-title">
                <header><h2 id="conversion-edit-title">Modificación de operaciones</h2><button type="button" data-close-conversion-edit aria-label="Cerrar">×</button></header>
                <form action="{{ route('products.ingredients.conversions.update', ['ingredient' => $ingredient->id, 'conversion' => $conversions->first()->id]) }}" method="POST" data-conversion-form>
                    @csrf @method('PUT')
                    <input type="hidden" name="_conversion_id" value="{{ $oldConversion?->id ?? $conversions->first()->id }}">
                    <div class="ui-list__errors" data-conversion-errors role="alert" tabindex="-1" hidden></div>
                    @foreach ($filters as $key => $value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endforeach
                    @if ($errors->any())<div class="ui-list__errors" role="alert"><p>No se guardaron los cambios:</p><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
                    @foreach (['bodega' => ['Bodega', 200, 'Hasta tres operaciones sucesivas. Ejemplo: -,615,/,1000.'], 'pedido' => ['Pedido', 50, 'Una sola operación. Ejemplo: *,150.']] as $field => [$label, $length, $help])
                        <div class="products-field"><label for="conversion-{{ $field }}">{{ $label }}:</label>
                            <input class="products-input" id="conversion-{{ $field }}" name="{{ $field }}" type="text" required maxlength="{{ $length }}" value="{{ $oldConversion && is_string(old($field)) ? old($field) : $conversions->first()->{ucfirst($field)} }}" aria-describedby="conversion-{{ $field }}-help conversion-{{ $field }}-error" @error($field) aria-invalid="true" @enderror>
                            <p id="conversion-{{ $field }}-help" class="ingredients-field-help">{{ $help }} Operadores: *, +, -, /; sin espacios.</p>
                            <p id="conversion-{{ $field }}-error" class="products-field-error">@error($field){{ $message }}@enderror</p>
                        </div>
                    @endforeach
                    <footer><button class="products-button" type="button" data-close-conversion-edit>Cerrar</button><button class="products-button products-button--blue" type="submit">Guardar datos</button></footer>
                </form>
            </dialog>
        @endif
    @endif
@endsection
