@props(['exchange'])

<div class="currency-bar" data-currency-switcher data-rate="{{ $exchange['available'] ? $exchange['rate'] : '' }}">
    <p class="currency-bar__label">Show prices in</p>
    <div class="currency-bar__controls" role="group" aria-label="Currency">
        <button type="button" class="currency-toggle is-active" data-currency="USD" aria-pressed="true">USD</button>
        <button type="button" class="currency-toggle" data-currency="EUR" aria-pressed="false"
            @disabled(!$exchange['available'])>
            EUR
        </button>
    </div>
    <a class="rss-link" href="{{ route('hosting.feed') }}" aria-label="Hosting RSS feed">
        <x-icon name="rss" pack="simple" :size="16" />
        <span>RSS</span>
    </a>
    @if ($exchange['available'])
        <p class="currency-bar__rate">
            1 USD = {{ number_format($exchange['rate'], 4) }} EUR
            · {{ $exchange['source'] }}
            · {{ $exchange['as_of'] }}
        </p>
    @else
        <p class="currency-bar__rate">EUR conversion is temporarily unavailable. Showing USD.</p>
    @endif
</div>
