@extends('layouts.app')

@section('title', 'Configurar — The Italian Coffee Company')

@push('styles')
    @vite('resources/css/configuration.css')
@endpush
@push('scripts')
    @vite('resources/js/configuration.js')
@endpush

@section('content')
    @yield('configuration-content')
@endsection
