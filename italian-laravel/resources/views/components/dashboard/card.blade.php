@props(['card'])

<article class="dashboard-card dashboard-card--{{ $card['tone'] }}">
    @if ($card['url'])
        <a class="dashboard-card__body" href="{{ $card['url'] }}">
            <div class="dashboard-card__heading">
                <img src="{{ asset('images/authenticated/'.$card['image']) }}"
                     alt=""
                     width="72"
                     height="72">

                <div class="dashboard-card__text">
                    <h2>{{ $card['label'] }}</h2>
                    <p class="dashboard-card__subtitle">
                        {{ $card['subtitle'] }}
                    </p>
                </div>
            </div>

            <div class="dashboard-card__footer">
                <span>{{ $card['footer_label'] }}</span>
                <span class="dashboard-card__badge">
                    Más información
                    <x-layout.icon name="arrow" />
                </span>
            </div>
        </a>
    @else
        <div class="dashboard-card__body dashboard-card__body--unavailable">
            <div class="dashboard-card__heading">
                <img src="{{ asset('images/authenticated/'.$card['image']) }}"
                     alt=""
                     width="72"
                     height="72">

                <div class="dashboard-card__text">
                    <h2>{{ $card['label'] }}</h2>
                    <p class="dashboard-card__subtitle">
                        {{ $card['subtitle'] }}
                    </p>
                </div>
            </div>

            <div class="dashboard-card__footer">
                <span>{{ $card['footer_label'] }}</span>
                <span class="dashboard-card__badge dashboard-card__badge--unavailable">
                    Más información
                    <x-layout.icon name="lock" />
                </span>
            </div>

            <p class="dashboard-card__availability">
                {{ $card['unavailable_message'] }}
            </p>
        </div>
    @endif
</article>
