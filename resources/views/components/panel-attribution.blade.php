@props(['attribution'])

@if (filled($attribution['name'] ?? null))
    <p class="panel-attribution">
        <span>{{ $attribution['text'] ?? 'Panel built by' }}</span>
        @if (filled($attribution['url'] ?? null))
            <a href="{{ $attribution['url'] }}" class="panel-attribution__link" rel="noopener noreferrer" target="_blank">
                {{ $attribution['name'] }}
            </a>
        @elseif (filled($attribution['name'] ?? null))
            <span>{{ $attribution['name'] }}</span>
        @endif
        <img src="{{ asset($attribution['icon'] ?? 'images/partners/voltaic/favicon-32.png') }}" alt=""
            width="20" height="20" class="panel-attribution__icon" loading="lazy" decoding="async">
    </p>
@endif
