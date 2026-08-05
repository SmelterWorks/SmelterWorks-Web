@props([
    'href' => null,
    'variant' => 'solid',
])

@php
    $classes = 'button button--' . $variant;
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
        {{ $slot }}
    </a>
@else
    <button type="button" {{ $attributes->class($classes) }}>
        {{ $slot }}
    </button>
@endif
