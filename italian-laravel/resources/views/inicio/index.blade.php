@extends('layouts.app')

@section('title', $layout['title'].' — The Italian Coffee Company')

@section('content')
    <header class="page-heading">
        <h1>
            {{ $layout['title'] }}
            <small>{{ $layout['subtitle'] }}</small>
        </h1>
    </header>

    <section class="dashboard-grid" aria-label="Opciones del panel">
        @foreach ($layout['cards'] as $card)
            <x-dashboard.card :card="$card" />
        @endforeach
    </section>
@endsection
