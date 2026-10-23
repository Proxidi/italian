@extends('layouts.app')

@section('title', $layout['title'].' — The Italian Coffee Company')

@section('content')
    <div class="dashboard-home" data-dashboard-home data-welcome-key="{{ csrf_token() }}">
    <header class="page-heading dashboard-heading">
        <div>
        <p class="dashboard-eyebrow">THE ITALIAN COFFEE COMPANY · {{ $layout['profile']['plaza'] ?: 'Mi espacio' }}</p>
        <h1>
            {{ $layout['title'] }}
        </h1>
        <p>Hola, {{ $layout['profile']['short_name'] }}. Todo listo para comenzar.</p>
        </div>
        <span class="dashboard-heading__tag">{{ $layout['subtitle'] }}</span>
    </header>

    <section class="dashboard-grid" aria-label="Opciones del panel">
        @foreach ($layout['cards'] as $card)
            <x-dashboard.card :card="$card" :style="'--card-delay: '.min($loop->index * 35, 210).'ms'" />
        @endforeach
    </section>
    </div>
    @include('inicio.partials.arrival')
@endsection
