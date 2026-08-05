@props([
    'enabled' => null,
    'message' => null,
    'background' => null,
    'color' => null,
    'href' => null,
    'linkLabel' => null,
])

@php
    $config = config('smelterworks.banner', []);
    $show = $enabled ?? (bool) ($config['enabled'] ?? false);
    $text = $message ?? ($config['message'] ?? '');
    $bg = $background ?? ($config['background'] ?? '#b45309');
    $fg = $color ?? ($config['color'] ?? '#ffffff');
    $link = $href ?? ($config['href'] ?? null);
    $linkText = $linkLabel ?? ($config['link_label'] ?? null);
@endphp

@if ($show && filled($text))
    <div
        {{ $attributes->class('site-banner')->merge([
            'role' => 'status',
            'style' => '--site-banner-bg: ' . $bg . '; --site-banner-fg: ' . $fg . ';',
        ]) }}>
        <p class="site-banner__text">
            {{ $text }}
            @if (filled($link) && filled($linkText))
                <a href="{{ $link }}" class="site-banner__link">{{ $linkText }}</a>
            @endif
        </p>
    </div>
@endif
