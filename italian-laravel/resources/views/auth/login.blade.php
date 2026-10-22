@extends('layouts.guest')

@section('content')
    <section class="login" aria-labelledby="login-title">
        <div class="login__content">
            <div class="login__brand">
                <img
                    src="{{ asset('assets/images/auth/logo-blanco.png') }}"
                    alt="The Italian Coffee Company"
                    width="264"
                    height="164"
                    class="login__logo"
                >
            </div>

            <h1 id="login-title" class="login__title">
                Any Time is Coffee Time
            </h1>

            @if ($errors->any())
                <div id="login-errors"
                     class="login__alert"
                     role="alert">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form
                class="login__form"
                action="{{ route('login.store') }}"
                method="POST"
                data-login-form
            >
                @csrf

                <div class="login__field">
                    <label for="username" class="visually-hidden">
                        Usuario
                    </label>

                    <div class="login__input-wrap">
                        <input
                            id="username"
                            class="login__input"
                            type="text"
                            name="username"
                            value="{{ old('username') }}"
                            placeholder="Usuario"
                            autocomplete="username"
                            autocapitalize="none"
                            spellcheck="false"
                            required
                            aria-invalid="{{ $errors->has('username') ? 'true' : 'false' }}"
                            aria-describedby="username-feedback{{ $errors->has('username') ? ' login-errors' : '' }}"
                            data-empty-message="Escribe tu usuario."
                        >

                        <span class="login__focus" aria-hidden="true"></span>

                        <svg class="login__icon"
                             viewBox="0 0 24 24"
                             fill="currentColor"
                             aria-hidden="true"
                             focusable="false">
                            <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm0 2c-4.42 0-8 2.24-8 5v1h16v-1c0-2.76-3.58-5-8-5Z"/>
                        </svg>
                    </div>

                    <p id="username-feedback"
                       class="login__field-error"
                       aria-live="polite"
                       hidden></p>
                </div>

                <div class="login__field">
                    <label for="password" class="visually-hidden">
                        Contraseña
                    </label>

                    <div class="login__input-wrap">
                        <input
                            id="password"
                            class="login__input"
                            type="password"
                            name="password"
                            placeholder="Contraseña"
                            autocomplete="current-password"
                            required
                            aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}"
                            aria-describedby="password-feedback{{ $errors->has('password') ? ' login-errors' : '' }}"
                            data-empty-message="Escribe tu contraseña."
                        >

                        <span class="login__focus" aria-hidden="true"></span>

                        <svg class="login__icon"
                             viewBox="0 0 24 24"
                             fill="currentColor"
                             aria-hidden="true"
                             focusable="false">
                            <path d="M17 9h-1V6a4 4 0 0 0-8 0v3H7a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-9a2 2 0 0 0-2-2ZM10 6a2 2 0 0 1 4 0v3h-4V6Zm3 11v2h-2v-2a2 2 0 1 1 2 0Z"/>
                        </svg>
                        <button class="login__visibility" type="button" data-password-toggle aria-label="Mostrar contraseña" aria-controls="password" aria-pressed="false" hidden>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>
                                <path class="login__eye-slash" d="m4 4 16 16"/>
                            </svg>
                        </button>
                    </div>

                    <p id="password-feedback"
                       class="login__field-error"
                       aria-live="polite"
                       hidden></p>
                </div>

                <button class="login__submit" type="submit">
                    <span data-submit-label>Iniciar sesión</span>
                    <span class="login__spinner" aria-hidden="true" hidden></span>
                </button>
                <p class="visually-hidden" role="status" aria-live="polite" data-login-status></p>
            </form>

            <div class="login__recovery">
                <p>¿Olvidaste tu usuario o contraseña?</p>
            </div>
        </div>

    </section>
@endsection
