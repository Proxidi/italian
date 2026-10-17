@extends('layouts.app')
@section('title', 'Usuarios — The Italian Coffee Company')
@push('styles')
    @vite('resources/css/users-read.css')
@endpush
@section('content')
    <header class="users-read-heading">
        <h1>Usuarios <small>Editar</small></h1>
        <a href="{{ route('usuarios.index') }}">Volver a la lista de Usuarios <span aria-hidden="true">➜</span></a>
    </header>
    <section class="users-read">
        @if($errors->any())
            <div class="users-read__errors" role="alert"><p>Error al actualizar. Revisa los datos del formulario.</p></div>
        @endif
        <form class="users-form" action="{{ route('usuarios.update', $subject) }}" method="POST">
            @csrf
            @method('PUT')
            @include('usuarios.partials.fields', ['prefix' => 'edit'])
            <button class="users-read__button users-read__button--green" type="submit">Guardar</button>
        </form>
    </section>
@endsection
