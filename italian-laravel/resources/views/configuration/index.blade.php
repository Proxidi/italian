@extends('configuration.layout')

@section('configuration-content')
    <header class="configuration-heading">
        <h1>Panel de Administración <small>Panel de Control</small></h1>
        <a href="{{ route('inicio') }}">Volver al Panel de Inicio <span aria-hidden="true">➜</span></a>
    </header>
    <section class="configuration-cards" aria-label="Configurar">
        @forelse ($cards as $card)
            <a class="configuration-card" href="{{ route($card['route']) }}">
                <div class="configuration-card__heading">
                    <img src="{{ asset('images/authenticated/'.$card['image']) }}" width="65" height="65" alt="">
                    <div><h2>{{ $card['label'] }}</h2><p>{{ $card['subtitle'] }}</p></div>
                </div>
                <div class="configuration-card__footer"><span>{{ $card['label'] }}</span><span class="configuration-card__badge">Click here</span></div>
            </a>
        @empty
            <p>No tienes permisos para las subpantallas de Configurar.</p>
        @endforelse
    </section>
@endsection
