@props([
    'download',
    'suggested' => false,
    'channel' => 'stable',
])

@php
    $platformIcon = static function (string $id): string {
        return str_starts_with($id, 'macos') ? 'macos' : $id;
    };

    $formats = $download['formats'] ?? [];
    $hasFormats = count($formats) > 1;
    $defaultFormat = collect($formats)->firstWhere('id', $download['default_format'] ?? '')
        ?? collect($formats)->firstWhere('available', true)
        ?? ($formats[0] ?? null);
    $downloadUrl = $defaultFormat['url'] ?? ($download['url'] ?? '');
    $isAvailable = ($download['available'] ?? false) && filled($downloadUrl);
    $downloadLabel = $channel === 'nightly' ? 'Download nightly' : 'Download';
@endphp

<article @class([
    'download-card',
    'download-card--suggested' => $suggested,
    'download-card--nightly' => $channel === 'nightly',
]) @if ($hasFormats) data-download-card @endif>
    <div class="download-card__head">
        <span class="download-card__icon" aria-hidden="true">
            <x-platform-icon :platform="$platformIcon($download['id'])" :size="28" />
        </span>
        <div>
            <h3 class="download-card__title">{{ $download['label'] }}</h3>
            <p class="download-card__detail">{{ $download['detail'] }}</p>
        </div>
    </div>

    @if ($hasFormats)
        <div class="download-formats" role="group"
            aria-label="Download format for {{ $download['label'] }}">
            @foreach ($formats as $format)
                <button type="button" @class([
                    'download-formats__option',
                    'is-active' => $format['id'] === ($defaultFormat['id'] ?? ''),
                    'is-unavailable' => !($format['available'] ?? false),
                ]) data-download-format="{{ $format['id'] }}"
                    data-url="{{ $format['url'] ?? '' }}"
                    data-label="{{ $format['label'] }}"
                    data-available="{{ ($format['available'] ?? false) ? 'true' : 'false' }}"
                    aria-pressed="{{ $format['id'] === ($defaultFormat['id'] ?? '') ? 'true' : 'false' }}"
                    @disabled(!($format['available'] ?? false))>
                    {{ $format['label'] }}
                </button>
            @endforeach
        </div>
    @endif

    @if ($isAvailable)
        <x-button :href="$downloadUrl" variant="ghost" data-download-link>
            {{ $downloadLabel }}
        </x-button>
    @else
        <x-button disabled aria-disabled="true" data-download-link>
            {{ $channel === 'nightly' ? 'No nightly yet' : 'Not available' }}
        </x-button>
    @endif
</article>
