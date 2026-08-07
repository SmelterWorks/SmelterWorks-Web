@props([
    'url',
    'alt',
    'webp' => null,
    'fallback' => null,
    'compact' => false,
    'priority' => false,
])

<figure @class(['relic-preview', 'relic-preview--compact' => $compact])>
    <picture>
        @if (filled($webp))
            <source srcset="{{ $webp }}" type="image/webp">
        @endif
        <img class="relic-preview__image" src="{{ $fallback ?? $url }}" alt="{{ $alt }}"
            width="1280" height="720" @if ($priority) fetchpriority="high" loading="eager" @else loading="lazy" @endif
            decoding="async">
    </picture>
</figure>
