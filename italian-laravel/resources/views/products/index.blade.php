@extends('products.layout')

@section('products-content')
    <header class="products-heading">
        <h1>Panel de Productos <small>Productos</small></h1>
        <a href="{{ route('inicio') }}">Volver al Panel de Inicio <span aria-hidden="true">➜</span></a>
    </header>
    <section class="products-cards" aria-label="Productos">
        @foreach ([['Productos', 'Productos', 'coffe2.png', 'yellow'], ['Insumos', 'Insumos', 'unit.png', 'green'], ['Unidades', 'Gr,Kg,Lts', 'rule.png', 'white'], ['Costo de Venta', 'Costo de Venta', 'rule.png', 'white']] as [$label, $subtitle, $image, $tone])
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
            @if ($available)
                <a class="products-card products-card--{{ $tone }}" href="{{ route($destination[0]) }}">
                    <div class="products-card__heading"><img src="{{ asset('images/authenticated/'.$image) }}" width="65" height="65" alt=""><div><h2>{{ $label }}</h2><p>{{ $subtitle }}</p></div></div>
                    <div class="products-card__footer"><span>{{ $label }}</span><span class="products-badge">Click here</span></div>
                </a>
            @elseif ($destination === null)
                <article class="products-card products-card--{{ $tone }}" aria-disabled="true">
                    <div class="products-card__heading"><img src="{{ asset('images/authenticated/'.$image) }}" width="65" height="65" alt=""><div><h2>{{ $label }}</h2><p>{{ $subtitle }}</p></div></div>
                    <div class="products-card__footer"><span>{{ $label }}</span><span class="products-badge products-badge--pending">Pendiente de migración</span></div>
                </article>
            @endif
        @endforeach
    </section>
@endsection
