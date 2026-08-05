@props(['plan', 'comingSoon'])

@php
    $remaining = $plan['stock']['remaining'] ?? 0;
    $soldOut = !$comingSoon && $remaining < 1;
@endphp

<article @class([
    'plan-card',
    'plan-card--featured' => $plan['recommended'] && !$comingSoon,
    'plan-card--sold-out' => $soldOut,
])>
    @if ($comingSoon)
        <p class="plan-card__badge">Coming soon</p>
    @elseif ($plan['recommended'] && !$soldOut)
        <p class="plan-card__badge">Most players pick this</p>
    @endif
    @if ($soldOut)
        <p class="plan-card__badge plan-card__badge--sold">Sold out</p>
    @endif
    <h2 class="plan-card__name">{{ $plan['name'] }}</h2>
    <p class="plan-card__flavor">{{ $plan['flavor'] }}</p>
    <p class="plan-card__price">
        <span class="plan-card__amount" data-price data-usd="{{ $plan['price_monthly'] }}"
            data-eur="{{ $plan['price_monthly_eur'] }}">${{ $plan['price_monthly'] }}</span>
        <span class="plan-card__period">/ month</span>
    </p>
    <p class="plan-card__yearly">
        <span data-price data-usd="{{ $plan['price_yearly'] }}"
            data-eur="{{ $plan['price_yearly_eur'] }}">${{ $plan['price_yearly'] }}</span>
        / year · save
        <span data-price data-usd="{{ $plan['yearly_savings'] }}"
            data-eur="{{ $plan['yearly_savings_eur'] }}">${{ $plan['yearly_savings'] }}</span>
    </p>
    <p class="plan-card__blurb">{{ $plan['blurb'] }}</p>
    <ul class="plan-card__specs">
        <li>{{ $plan['ram_gb'] }} GB RAM</li>
        <li>{{ $plan['storage_gb'] }} GB NVMe</li>
        <li>{{ $plan['comfort'] }}</li>
        <li>US or Germany</li>
        <li>Docker export for self-hosting</li>
    </ul>
    @unless ($comingSoon)
        <p class="plan-card__stock">
            @if ($soldOut)
                No slots left on either host.
            @else
                {{ $remaining }} left
                @foreach ($plan['stock']['by_region'] as $region)
                    · {{ $region['label'] }}: {{ $region['remaining'] }}
                @endforeach
            @endif
        </p>
    @endunless
    @if ($comingSoon)
        <span class="button button--ghost" aria-disabled="true">Coming soon</span>
    @elseif ($soldOut)
        <span class="button button--ghost" aria-disabled="true">Sold out</span>
    @else
        <x-button :href="route('hosting.purchase', $plan['slug'])" :variant="$plan['recommended'] ? 'solid' : 'ghost'">
            Purchase
        </x-button>
    @endif
</article>
