@props(['name', 'pack' => 'lucide', 'size' => 20, 'class' => ''])

@php
    $loader = app(\App\Support\Icons\IconLoader::class);
    $svg = $loader->contents((string) $pack, (string) $name);
    $size = max(1, min(128, (int) $size));

    if ($svg !== '') {
        $svg = preg_replace('/\s(width|height)="[^"]*"/', '', $svg) ?? $svg;
        $svg =
            preg_replace(
                '/<svg\b/',
                '<svg width="' .
                    $size .
                    '" height="' .
                    $size .
                    '" class="icon ' .
                    e($class) .
                    '" aria-hidden="true" focusable="false"',
                $svg,
                1,
            ) ?? $svg;
    }
@endphp

@if ($svg !== '')
    {!! $svg !!}
@endif
