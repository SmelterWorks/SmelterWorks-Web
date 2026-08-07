@props(['download', 'stable' => []])

@php
    $platformIcon = static function (string $id): string {
        return str_starts_with($id, 'macos') ? 'macos' : $id;
    };

    $formats = $download['formats'] ?? [];
    $hasFormats = count($formats) > 1;
    $defaultFormat =
        collect($formats)->firstWhere('id', $download['default_format'] ?? '') ??
        (collect($formats)->firstWhere('available', true) ?? ($formats[0] ?? null));
    $downloadUrl = $defaultFormat['url'] ?? ($download['url'] ?? '');
    $isAvailable = ($download['available'] ?? false) && filled($downloadUrl);
@endphp

@if ($isAvailable)
    <div class="download-hero" @if ($hasFormats) data-download-card @endif>
        <p class="download-hero__label">Suggested for you</p>
        <div class="download-hero__heading">
            <span class="download-hero__icon" aria-hidden="true">
                <x-platform-icon :platform="$platformIcon($download['id'])" :size="32" />
            </span>
            <h2 class="download-hero__title">{{ $download['label'] }}</h2>
        </div>
        <p class="download-hero__detail">{{ $download['detail'] }}</p>

        @if ($hasFormats)
            <div class="download-formats download-formats--suggested" role="group"
                aria-label="Download format for {{ $download['label'] }}">
                @foreach ($formats as $format)
                    <button type="button" @class([
                        'download-formats__option',
                        'is-active' => $format['id'] === ($defaultFormat['id'] ?? ''),
                        'is-unavailable' => !($format['available'] ?? false),
                    ]) data-download-format="{{ $format['id'] }}"
                        data-url="{{ $format['url'] ?? '' }}" data-label="{{ $format['label'] }}"
                        data-available="{{ $format['available'] ?? false ? 'true' : 'false' }}"
                        aria-pressed="{{ $format['id'] === ($defaultFormat['id'] ?? '') ? 'true' : 'false' }}"
                        @disabled(!($format['available'] ?? false))>
                        {{ $format['label'] }}
                    </button>
                @endforeach
            </div>
        @endif

        <div class="action-row">
            <x-button :href="$downloadUrl" data-download-link>
                Download {{ $download['label'] }}
            </x-button>
            @if (($stable['available'] ?? false) && filled($stable['html_url'] ?? null))
                <x-button :href="$stable['html_url']" variant="ghost" rel="noopener noreferrer" target="_blank">
                    Latest release
                </x-button>
            @endif
        </div>
    </div>
@endif
