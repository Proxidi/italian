@extends('layouts.app')
@section('title', 'Usuarios — The Italian Coffee Company')
@push('styles')
    @vite('resources/css/ui-list.css')
@endpush
@section('content')
    <header class="ui-list-heading">
        <h1>Usuarios <small>Editar</small></h1>
        <a href="{{ route('usuarios.index') }}">Volver a la lista de Usuarios <span aria-hidden="true">➜</span></a>
    </header>
    <section class="ui-list">
        @if($errors->any())
            <div class="ui-list__errors" role="alert"><p>Error al actualizar. Revisa los datos del formulario.</p></div>
        @endif
        <form class="users-form" action="{{ route('usuarios.update', $subject) }}" method="POST">
            @csrf
            @method('PUT')
            @include('usuarios.partials.fields', ['prefix' => 'edit'])
            <button class="ui-list__button ui-list__button--green" type="submit">Guardar</button>
        </form>
    </section>
@endsection
