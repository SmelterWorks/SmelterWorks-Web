@props(['mark'])

@php
    $displaySize = min(128, max($mark['width'], $mark['height']));
    $defaultFormat = $mark['default_format'];
    $defaultVariant = $mark['variants'][$defaultFormat];
    $previewClass = match ($mark['preview']) {
        'checker' => 'branding-mark__preview--checker',
        default => 'branding-mark__preview--solid',
    };
@endphp

<article class="branding-mark" data-branding-mark>
    <div @class(['branding-mark__preview', $previewClass])>
        <img class="branding-mark__image" data-branding-preview src="{{ $defaultVariant['url'] }}" alt=""
            width="{{ $displaySize }}" height="{{ $displaySize }}" loading="lazy" decoding="async">
    </div>

    <div class="branding-mark__meta">
        <p class="branding-mark__size">{{ $mark['label'] }}</p>

        <div class="branding-mark__formats" role="group" aria-label="Download format for {{ $mark['label'] }}">
            @foreach ($mark['variants'] as $format => $variant)
                <button type="button" @class([
                    'branding-mark__format',
                    'is-active' => $format === $defaultFormat,
                ]) data-branding-format="{{ $format }}"
                    data-url="{{ $variant['url'] }}" data-filename="{{ $variant['filename'] }}"
                    aria-pressed="{{ $format === $defaultFormat ? 'true' : 'false' }}">
                    {{ strtoupper($format) }}
                </button>
            @endforeach
        </div>

        <a class="branding-mark__download" data-branding-download href="{{ $defaultVariant['url'] }}"
            download="{{ $defaultVariant['filename'] }}">
            Download {{ strtoupper($defaultFormat) }}
        </a>
    </div>
</article>
