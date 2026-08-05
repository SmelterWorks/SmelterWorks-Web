@props(['code', 'label' => null])

@php
    $loader = app(\App\Support\Icons\IconLoader::class);
    $path = $loader->path('flags', (string) $code);
@endphp

@if ($path !== null)
    <img class="region-flag" src="{{ asset('icons/flags/' . basename($path)) }}" alt="" width="20" height="14"
        loading="lazy" decoding="async">
@endif
