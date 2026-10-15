@props([
    'items',
    'nested' => false,
])

@if (! $nested)
    <aside id="app-sidebar"
           class="app-sidebar"
           aria-label="Navegación principal"
           data-app-sidebar>
        <nav>
            @endif

            <ul class="{{ $nested ? 'sidebar-submenu' : 'sidebar-list' }}">
                @foreach ($items as $item)
                    <li>
                        @if (isset($item['children']))
                            <details class="sidebar-group"
                                     @if ($item['active'] ?? false) open @endif>
                                <summary class="sidebar-entry">
                                    <x-layout.icon :name="$item['icon']" />
                                    <span>{{ $item['label'] }}</span>
                                    <x-layout.icon name="chevron"
                                                   class="sidebar-chevron" />
                                </summary>

                                <x-layout.sidebar :items="$item['children']"
                                                  :nested="true" />
                            </details>
                        @elseif (($item['type'] ?? null) === 'logout')
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button class="sidebar-entry sidebar-entry--button"
                                        type="submit">
                                    <x-layout.icon :name="$item['icon']" />
                                    <span>{{ $item['label'] }}</span>
                                </button>
                            </form>
                        @elseif ($item['url'])
                            <a class="sidebar-entry {{ $item['active'] ? 'is-active' : '' }}"
                               href="{{ $item['url'] }}"
                               @if ($item['active']) aria-current="page" @endif>
                                <x-layout.icon :name="$item['icon']" />
                                <span>{{ $item['label'] }}</span>
                            </a>
                        @else
                            <span class="sidebar-entry sidebar-entry--unavailable"
                                  role="link"
                                  aria-disabled="true"
                                  title="{{ $item['unavailable_message'] }}">
                    <x-layout.icon :name="$item['icon']" />
                    <span>
                        {{ $item['label'] }}
                        <span class="sr-only">
                            — {{ $item['unavailable_message'] }}
                        </span>
                    </span>
                </span>
                        @endif
                    </li>
                @endforeach
            </ul>

            @if (! $nested)
        </nav>
    </aside>
@endif
