@extends('configuration.layout')

@section('configuration-content')
    <header class="configuration-heading">
        <h1>Panel de Administración <small>Panel de Control</small></h1>
        <a href="{{ route('inicio') }}">Volver al Panel de Inicio <span aria-hidden="true">➜</span></a>
    </header>
    <section class="dashboard-grid" aria-label="Configurar">
        @forelse ($cards as $card)
            <x-dashboard.card :card="[
                ...$card, 'url' => route($card['route']), 'footer_label' => $card['label'],
                'tone' => $card['route'] === 'configuration.dates' ? 'blue' : 'orange',
            ]" :style="'--card-delay: '.($loop->index * 35).'ms'" />
        @empty
            <p>No tienes permisos para las subpantallas de Configurar.</p>
        @endforelse
    </section>
@endsection
