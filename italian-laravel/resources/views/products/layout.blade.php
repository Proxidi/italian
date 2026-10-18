@extends('layouts.app')

@section('title', 'Productos — The Italian Coffee Company')
@push('styles')
    @vite('resources/css/products.css')
@endpush
@push('scripts')
    @vite('resources/js/products.js')
@endpush

@section('content')
    @yield('products-content')
@endsection
