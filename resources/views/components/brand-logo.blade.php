@props([
    'variant' => 'transparent',
    'size' => 40,
    'showWordmark' => true,
])

@php
    $src = match ($variant) {
        'icon' => asset('images/brand/SmelterWorks-256.webp'),
        'full' => asset('images/brand/SmelterWorks-512.webp'),
        default => asset('images/brand/SmelterWorks-transparent.png'),
    };

    $fallback = match ($variant) {
        'icon' => asset('images/brand/SmelterWorks-256.png'),
        'full' => asset('images/brand/SmelterWorks-512.png'),
        default => asset('images/brand/SmelterWorks-transparent.png'),
    };
@endphp

<span {{ $attributes->class(['brand-logo']) }} style="--brand-logo-size: {{ $size }}px">
    <picture>
        @if ($variant !== 'transparent')
            <source srcset="{{ $src }}" type="image/webp">
        @endif
        <img class="brand-logo__mark" src="{{ $fallback }}" alt="" width="{{ $size }}"
            height="{{ $size }}" decoding="async"
            @if ($variant === 'transparent') loading="eager" @else loading="lazy" @endif>
    </picture>

    @if ($showWordmark)
        <span class="brand-logo__name">{{ config('app.name') }}</span>
    @endif
</span>
