@extends('layouts.guest')

@section('content')
    <section class="login" aria-labelledby="login-title">
        <div class="login__content">
            <div class="login__brand">
                <img
                    src="{{ asset('images/auth/3.png') }}"
                    alt="The Italian Coffee Company"
                    width="249"
                    height="160"
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
                        Username
                    </label>

                    <div class="login__input-wrap">
                        <input
                            id="username"
                            class="login__input"
                            type="text"
                            name="username"
                            value="{{ old('username') }}"
                            placeholder="Username"
                            autocomplete="username"
                            autocapitalize="none"
                            spellcheck="false"
                            required
                            aria-invalid="{{ $errors->has('username') ? 'true' : 'false' }}"
                            aria-describedby="username-feedback{{ $errors->has('username') ? ' login-errors' : '' }}"
                            data-empty-message="Username is required"
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
                        Password
                    </label>

                    <div class="login__input-wrap">
                        <input
                            id="password"
                            class="login__input"
                            type="password"
                            name="password"
                            placeholder="Password"
                            autocomplete="current-password"
                            required
                            aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}"
                            aria-describedby="password-feedback{{ $errors->has('password') ? ' login-errors' : '' }}"
                            data-empty-message="Password is required"
                        >

                        <span class="login__focus" aria-hidden="true"></span>

                        <svg class="login__icon"
                             viewBox="0 0 24 24"
                             fill="currentColor"
                             aria-hidden="true"
                             focusable="false">
                            <path d="M17 9h-1V6a4 4 0 0 0-8 0v3H7a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-9a2 2 0 0 0-2-2ZM10 6a2 2 0 0 1 4 0v3h-4V6Zm3 11v2h-2v-2a2 2 0 1 1 2 0Z"/>
                        </svg>
                    </div>

                    <p id="password-feedback"
                       class="login__field-error"
                       aria-live="polite"
                       hidden></p>
                </div>

                <button class="login__submit" type="submit">
                    Login
                </button>
            </form>

            {{-- Textos del legacy; estas funcionalidades no están disponibles. --}}
            <div class="login__recovery">
                <span class="login__inactive-link"
                      role="link"
                      aria-disabled="true">
                    Forgot Username / Password?
                </span>
            </div>
        </div>

        <div class="login__registration">
            <span class="login__inactive-link"
                  role="link"
                  aria-disabled="true">
                Create new account
                <span aria-hidden="true">→</span>
            </span>
        </div>
    </section>
@endsection
