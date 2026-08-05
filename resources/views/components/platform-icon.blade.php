@props(['platform', 'size' => 24])

@php
    $name = match ($platform) {
        'linux' => 'linux',
        'macos' => 'apple',
        default => 'windows',
    };
@endphp

<x-icon :name="$name" pack="brands" :size="$size" {{ $attributes }} />
