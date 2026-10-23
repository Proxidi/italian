@extends('products.layout')

@section('products-content')
    <header class="products-heading">
        <h1>Panel de Productos <small>Productos</small></h1>
        <a href="{{ route('inicio') }}">Volver al Panel de Inicio <span aria-hidden="true">➜</span></a>
    </header>
    <section class="dashboard-grid" aria-label="Productos">
        @foreach ([['Productos', 'Productos', 'coffe2.png', 'yellow'], ['Insumos', 'Insumos', 'unit.png', 'green'], ['Unidades', 'Gr,Kg,Lts', 'rule.png', 'blue'], ['Costo de Venta', 'Costo de Venta', 'rule.png', 'purple']] as [$label, $subtitle, $image, $tone])
            @php
                $destination = match ($label) {
                    'Productos' => ['products.catalog.index', 'products.catalog.view'],
                    'Unidades' => ['products.units.index', 'products.units.view'],
                    'Insumos' => ['products.ingredients.index', 'products.ingredients.view'],
                    'Costo de Venta' => ['products.cost-of-sale.index', 'products.cost-of-sale.view'],
                    default => null,
                };
                $available = $destination && auth('web')->user()->can($destination[1]);
            @endphp
            @if ($available || $destination === null)
                <x-dashboard.card :card="[
                    'label' => $label, 'subtitle' => $subtitle, 'image' => $image, 'tone' => $tone,
                    'url' => $available ? route($destination[0]) : null,
                    'footer_label' => $label, 'unavailable_message' => 'Módulo pendiente de migración',
                ]" :style="'--card-delay: '.($loop->index * 35).'ms'" />
            @endif
        @endforeach
    </section>
@endsection
