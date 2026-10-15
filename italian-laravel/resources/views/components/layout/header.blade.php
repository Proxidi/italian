@props(['profile'])

<header class="app-header">
    <a href="{{ route('inicio') }}"
       class="app-brand"
       aria-label="The Italian Coffee Company — Inicio">
        <img src="{{ asset('images/authenticated/coffe.png') }}"
             alt=""
             width="38"
             height="38">

        <span class="app-brand__text">
            {{ $profile['plaza'] ?: 'The Italian Coffee Company' }}
        </span>
    </a>

    <div class="app-header__tools">
        <button class="header-button"
                type="button"
                aria-label="Alternar navegación"
                aria-controls="app-sidebar"
                aria-expanded="true"
                data-sidebar-toggle
                hidden>
            <x-layout.icon name="menu" />
        </button>

        <details class="user-menu">
            <summary>
                <img src="{{ asset('images/authenticated/'.$profile['avatar']) }}"
                     alt=""
                     width="25"
                     height="25">

                <span class="user-menu__name">
                    {{ $profile['short_name'] }}
                </span>

                <span class="sr-only">Menú del usuario</span>
            </summary>

            <div class="user-menu__content">
                <div class="user-menu__hero">
                    <img class="user-menu__avatar"
                         src="{{ asset('images/authenticated/'.$profile['avatar']) }}"
                         alt=""
                         width="90"
                         height="90">

                    <p class="user-menu__fullname">
                        {{ $profile['name'] }}
                    </p>

                    <small>Sesión: Activa</small>
                </div>

                @if ($profile['plaza'])
                    <div class="user-menu__plaza">
                        {{ $profile['plaza'] }}
                    </div>
                @endif

                <div class="user-menu__actions">
                    <button type="button"
                            class="profile-button"
                            disabled
                            title="Perfil pendiente de migración">
                        Perfil
                        <span class="sr-only">
                            — pendiente de migración
                        </span>
                    </button>

                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="logout-button">
                            Cerrar sesión
                        </button>
                    </form>
                </div>
            </div>
        </details>
    </div>
</header>
