@extends('products.layout')
@section('products-content')
    <header class="ui-list-heading"><h1>Insumos Costo-Venta <small>Listado</small></h1>
        @can('products.view')<a href="{{ route('products.index') }}">Volver al Panel de Productos ➜</a>@else<a href="{{ route('inicio') }}">Volver al Panel de Inicio ➜</a>@endcan
    </header>
    @if(session('cost-success'))
        <x-ui.feedback variant="dialog" position="corner" :title="session('cost-success')" :duration="1000" id="cost-success" />
        <noscript><p class="ui-feedback ui-feedback--notice" role="status">{{ session('cost-success') }}</p></noscript>
    @endif
    <p data-cost-status class="ui-list__copy-status" role="status" aria-live="polite"></p>
    <template data-cost-success-template>
        <x-ui.feedback variant="dialog" position="corner" title="" :auto-open="false" :duration="1000" id="cost-async-success" />
    </template>
    @fragment('listing')
    <x-ui.listing data-async-list label="Costo de Venta">
        @php($remittances = ($filters['mode'] ?? 'orders') === 'remittances')
        <form method="GET" action="{{ route('products.cost-of-sale.index') }}" class="cost-filters">
            @foreach(['q', 'sort', 'direction'] as $key)@if(isset($filters[$key]))<input type="hidden" name="{{ $key }}" value="{{ $filters[$key] }}">@endif @endforeach
            <fieldset class="products-options catalog-options"><legend>Opciones:</legend>
                <label><input type="radio" name="mode" value="orders" data-auto-filter @checked(!$remittances)> Costos Insumos Pedido</label>
                <label><input type="radio" name="mode" value="remittances" data-auto-filter @checked($remittances)> Costos Insumos Remesas</label>
            </fieldset>
            <div class="products-options catalog-options" @if(!$remittances) hidden @endif>
                <label for="cost-date">Búsqueda:</label><input class="products-input" id="cost-date" type="date" name="date" value="{{ $from }}" required>
                <label for="cost-end" class="ui-list__sr-only">Fin de semana</label><input class="products-input" id="cost-end" type="date" value="{{ $to }}" readonly>
                <label for="cost-plaza" class="ui-list__sr-only">Plaza</label><select class="products-input" id="cost-plaza" name="plaza"><option value="">Todas las plazas</option>
                    @foreach($plazas as $plaza)<option value="{{ $plaza->id }}" @selected((string)($filters['plaza'] ?? '') === (string)$plaza->id)>{{ $plaza->Nombre }}</option>@endforeach
                </select>
                <button class="products-button products-button--green" type="submit">⌕ Buscar</button>
            </div>
            <noscript><button class="products-button" type="submit">Aplicar opciones</button></noscript>
        </form>
        <form method="GET" action="{{ route('products.cost-of-sale.index') }}" class="ui-list__filters">
            @foreach(['mode', 'date', 'plaza', 'sort', 'direction'] as $key)@if(isset($filters[$key]))<input type="hidden" name="{{ $key }}" value="{{ $filters[$key] }}">@endif @endforeach
            <div class="ui-list__toolbar cost-search"><div class="ui-list__search"><label for="cost-search">Buscar:</label>
                <input id="cost-search" class="ui-list__input" type="search" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100" placeholder="Buscar registros">
                <button class="ui-list__sr-only" type="submit">Aplicar búsqueda</button>
            </div></div>
        </form>
        <x-ui.table label="Insumos Costo-Venta">
            <x-slot:head>
                @foreach($remittances ? ['id' => '#', 'Plaza' => 'Plaza', 'Nombre' => 'Nombre', 'Cost' => 'Costo Venta'] : ['id' => '#', 'Nombre' => 'Nombre', 'Cost' => 'Costo Venta'] as $field => $label)
                    @php($selected = ($filters['sort'] ?? 'id') === $field)
                    <x-ui.sort-heading :label="$label" :selected="$selected" :direction="$filters['direction'] ?? 'asc'" :url="route('products.cost-of-sale.index', [...$filters, 'sort' => $field, 'direction' => $selected && ($filters['direction'] ?? 'asc') === 'asc' ? 'desc' : 'asc'])" />
                @endforeach
                @if(!$remittances)<th scope="col">Editar</th>@endif
            </x-slot:head>
            @forelse($records as $record)
                <tr data-record-row><td>{{ $record->id }}</td>@if($remittances)<td>{{ $record->Plaza }}</td>@endif<td>{{ $record->Nombre }}</td>
                    <td>@if($record->Cost === null)No calculable (cantidad total cero)@else${{ number_format($record->Cost, $remittances ? 3 : 2) }}@endif</td>
                    @if(!$remittances)<td>@can('products.cost-of-sale.update')<x-ui.table-action color="green" data-edit-cost :href="route('products.cost-of-sale.edit', ['ingredient' => $record->id, ...$filters])" aria-label="Editar costo de {{ $record->Nombre }}" />@else<span>Sin permiso de edición</span>@endcan</td>@endif
                </tr>
            @empty<tr><td colspan="4">No se encontraron registros.</td></tr>@endforelse
        </x-ui.table>
        <p class="ui-list__copy-status" role="status">{{ $records->count() }} registros.</p>
    </x-ui.listing>
    @endfragment
    @if($ingredient)
        <noscript><style>.products-dialog { display: block; position: static; margin: 15px auto; } .products-dialog button[type="button"] { display: none; }</style></noscript>
        <dialog class="products-dialog" data-cost-dialog aria-labelledby="cost-edit-title">
            <header><h2 id="cost-edit-title">Modificación - Costo Venta</h2><button type="button" data-close-cost aria-label="Cerrar">×</button></header>
            <form method="POST" action="{{ route('products.cost-of-sale.update', $ingredient) }}" data-cost-form>
                @csrf @method('PUT')
                @foreach($filters as $key => $value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endforeach
                <div class="ui-list__errors" data-cost-errors role="alert" tabindex="-1" @if(!$errors->any()) hidden @endif>{{ implode(' ', $errors->all()) }}</div>
                <div class="products-field"><label for="cost-name">Nombre:</label><input class="products-input" id="cost-name" value="{{ $ingredient->Nombre }}" readonly></div>
                <div class="products-field"><label for="cost-value">Costo Venta:</label><input class="products-input" id="cost-value" type="number" name="cost" step="any" required value="{{ old('cost', $ingredient->Costo_Venta) }}"></div>
                <footer><a class="products-button" data-close-cost href="{{ route('products.cost-of-sale.index', $filters) }}">Cerrar</a><button class="products-button products-button--blue" type="submit">Actualizar datos</button></footer>
            </form>
        </dialog>
    @endif
@endsection
