@extends('products.layout')
@section('products-content')
    <header class="ui-list-heading"><h1>Productos <small>Fórmulas</small></h1><a href="{{ route('products.catalog.index', $filters) }}">Volver al listado de Productos ➜</a></header>
    @include('products.partials.catalog-feedback')
    <div data-formula-panel>
        <dialog class="products-dialog catalog-dialog formula-dialog" data-formula-dialog data-return-url="{{ route('products.catalog.index', $filters) }}" aria-labelledby="formula-title">
            <header><h2 id="formula-title">Fórmula del Producto</h2><button type="button" data-close-formulas aria-label="Cerrar">×</button></header>
            <div class="ingredients-conversions-body">
                <p>{{ $product->nombre }}</p>
                @can('products.formulas.create')<button class="products-button products-button--blue" type="button" data-add-formula>✚ Agregar Fórmula</button>@endcan
                <x-ui.listing label="Composición de la fórmula" class="formula-list">
                <x-ui.table label="Fórmula del Producto">
                    <x-slot:head><th scope="col">Nombre</th><th scope="col">Insumo</th><th scope="col">Cantidad</th><th scope="col">Unidad</th><th scope="col">Editar</th><th scope="col">Eliminar</th></x-slot:head>
                    @forelse($lines as $line)
                        <tr><td>{{ $product->nombre }}</td><td>{{ $line->ingredient?->nombre ?? 'Insumo inexistente (ID '.$line->insumo_id.')' }}</td><td>{{ \App\Rules\ExactProductDecimal::display($line->cantidad) }}</td><td>{{ $line->ingredient?->initialUnit?->nombre ?? 'Unidad inexistente' }}</td>
                            <td>@if($line->editable && auth('web')->user()->can('products.formulas.update'))
                                <x-ui.table-action color="cyan" data-edit-formula :href="route('products.formulas.edit', ['product' => $product->id, 'formula' => $line->id, ...$filters])" data-action="{{ route('products.formulas.update', ['product' => $product->id, 'formula' => $line->id]) }}" data-quantity="{{ \App\Rules\ExactProductDecimal::display($line->cantidad) }}" data-ingredient="{{ $line->ingredient->nombre }}" data-unit="{{ $line->ingredient->unidad_id }}" aria-label="Editar cantidad de {{ $line->ingredient->nombre }}" />
                            @else<span>{{ $line->editable ? 'Sin permiso' : 'Relación inconsistente: requiere revisión' }}</span>@endif</td>
                            <td><x-ui.table-action color="red" icon="delete" disabled aria-label="Eliminar fórmula pendiente de decisión" /></td>
                        </tr>
                    @empty<tr><td colspan="6">Este producto no tiene fórmula.</td></tr>@endforelse
                </x-ui.table>
                </x-ui.listing>
                <p class="ingredients-field-help">Eliminar permanece deshabilitado hasta definir el tratamiento de las fórmulas y su historial.</p>
            </div>
            <footer><a class="products-button" data-close-formulas href="{{ route('products.catalog.index', $filters) }}">Cerrar</a></footer>
        </dialog>
        @foreach (['create', 'update'] as $operation)
            @can('products.formulas.'.$operation)
                @php($isEdit = $operation === 'update')
                <dialog class="products-dialog catalog-dialog" data-formula-editor="{{ $operation }}" @if(($isEdit && $editing) || (!$isEdit && $errors->any() && !$editing)) data-reopen @endif aria-labelledby="formula-{{ $operation }}-title">
                    <header><h2 id="formula-{{ $operation }}-title">{{ $isEdit ? 'Editar cantidad de Fórmula' : 'Nueva Fórmula' }}</h2><button type="button" data-close-formula-editor aria-label="Cerrar">×</button></header>
                    <form method="POST" action="{{ $isEdit ? ($editing ? route('products.formulas.update', [$product, $editing]) : '') : route('products.formulas.store', $product) }}" data-catalog-form data-formula-operation="{{ $operation }}">
                        @csrf @if($isEdit) @method('PUT') @endif
                        @foreach($filters as $key => $value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endforeach
                        <div class="ui-list__errors" data-catalog-errors role="alert" tabindex="-1" @if(!$errors->any()) hidden @endif>{{ implode(' ', $errors->all()) }}</div>
                        <div class="products-field"><label>Producto:</label><input class="products-input" value="{{ $product->nombre }}" readonly aria-label="Producto de la fórmula"></div>
                        <div class="products-field"><label for="formula-{{ $operation }}-ingredient">Insumo:</label>
                            @if($isEdit)<input class="products-input" data-formula-ingredient id="formula-update-ingredient" readonly value="{{ $editing?->ingredient?->nombre }}">
                            @else<select class="products-input" name="insumo_id" id="formula-create-ingredient" data-formula-picker required>
                                <option value="">Selecciona un insumo</option>
                                @foreach($ingredients as $ingredient)<option value="{{ $ingredient->id }}" data-unit="{{ $ingredient->unidad_id }}" data-unit-name="{{ $ingredient->initialUnit->nombre }}" @selected((string)old('insumo_id') === (string)$ingredient->id)>{{ $ingredient->nombre }}</option>@endforeach
                            </select>@endif
                        </div>
                        @if(!$isEdit && $ingredients->isEmpty())<p class="ui-list__errors" role="status">No hay insumos de producción con los catálogos necesarios para agregar una fórmula.</p>@endif
                        <div class="products-field"><label for="formula-{{ $operation }}-quantity">Cantidad:</label><input class="products-input" type="number" name="cantidad" id="formula-{{ $operation }}-quantity" step="any" min="0" required value="{{ old('cantidad', $isEdit && $editing ? \App\Rules\ExactProductDecimal::display($editing->cantidad) : '') }}"></div>
                        @if($isEdit)
                            <input type="hidden" name="unit_id" value="{{ $editing?->ingredient?->unidad_id }}">
                        @else
                            <div class="products-field"><label for="formula-create-unit">Unidad:</label>
                                <select class="products-input" name="unit_id" id="formula-create-unit" data-formula-unit required aria-describedby="formula-unit-help">
                                    <option value="">Unidad del insumo</option>
                                    @foreach($ingredients->unique('unidad_id') as $ingredient)<option value="{{ $ingredient->unidad_id }}" @selected((string)old('unit_id') === (string)$ingredient->unidad_id)>{{ $ingredient->initialUnit->nombre }}</option>@endforeach
                                </select>
                                <small id="formula-unit-help" class="ingredients-field-help">Debe coincidir con la unidad del insumo; no convierte cantidades.</small>
                            </div>
                        @endif
                        <footer><button class="products-button" type="button" data-close-formula-editor>Cerrar</button><button class="products-button products-button--blue" type="submit" @disabled($isEdit && !$editing)>Guardar datos</button></footer>
                    </form>
                </dialog>
            @endcan
        @endforeach
    </div>
@endsection
