@props([
    'size' => 40,
    'showWordmark' => true,
])

@php
    $webp = asset('images/brand/SmelterWorks-transparent-64.webp');
    $png = asset('images/brand/SmelterWorks-transparent-64.png');
@endphp

<span {{ $attributes->class(['inline-flex items-center gap-3']) }}>
    <picture>
        <source srcset="{{ $webp }}" type="image/webp">
        <img
            src="{{ $png }}"
            alt="SmelterWorks"
            width="{{ $size }}"
            height="{{ $size }}"
            class="h-auto w-auto"
            style="width: {{ $size }}px; height: {{ $size }}px;"
            decoding="async"
        >
    </picture>

    @if ($showWordmark)
        <span class="text-lg font-semibold tracking-tight text-zinc-100">{{ config('panel.name') }}</span>
    @endif
</span>
