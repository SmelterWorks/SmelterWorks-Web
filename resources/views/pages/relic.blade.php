@php
    $relicJsonLd = [
        array_filter(
            [
                '@type' => 'SoftwareApplication',
                'name' => $relic['name'],
                'applicationCategory' => 'GameApplication',
                'operatingSystem' => collect($relic['platforms'])->pluck('label')->implode(', '),
                'offers' => [
                    '@type' => 'Offer',
                    'price' => '0',
                    'priceCurrency' => 'USD',
                ],
                'description' => $relic['summary'],
                'url' => route('relic'),
                'downloadUrl' => route('relic.download'),
                'image' => $relic['preview_url'] ?? null,
                'license' => 'https://spdx.org/licenses/0BSD.html',
                'codeRepository' => $relic['repo_url'] ?? null,
            ],
            fn(mixed $value): bool => $value !== null && $value !== '',
        ),
    ];
@endphp

<x-layouts.site :title="$relic['name']" :description="$relic['summary']" :image="$relic['preview_url'] ?? null" :image-alt="$relic['preview_alt'] ?? $relic['name']" :json-ld="$relicJsonLd"
    :preload-image="$relic['preview_webp'] ?? null">
    <section class="page-hero page-hero--split">
        <div class="page-hero__inner relic-hero">
            <div class="relic-hero__copy">
                <div class="page-hero__meta">
                    <span class="pill">Launcher</span>
                    <span class="pill pill--muted">{{ $relic['license'] }}</span>
                </div>
                <h1 class="page-hero__title">{{ $relic['name'] }}</h1>
                <p class="page-hero__lede">{{ $relic['tagline'] }}</p>
                <p class="page-hero__lede">{{ $relic['summary'] }}</p>
                <div class="platform-list">
                    @foreach ($relic['platforms'] as $platform)
                        <x-platform-chip :platform="$platform['icon']" :label="$platform['label']" :detail="$platform['detail']" />
                    @endforeach
                </div>
                <div class="action-row" style="margin-top: 1.25rem;">
                    <x-button :href="route('relic.download')" class="button--badged">
                        Download
                        @if (filled($relic['stable_tag'] ?? null))
                            <span class="button__badge button__badge--version">{{ $relic['stable_tag'] }}</span>
                        @endif
                    </x-button>
                    <x-button :href="$relic['repo_url']" variant="ghost" rel="noopener noreferrer" target="_blank">
                        Source
                    </x-button>
                </div>
            </div>

            @if (filled($relic['preview_url'] ?? null))
                <figure class="relic-preview">
                    <picture>
                        @if (filled($relic['preview_webp'] ?? null))
                            <source srcset="{{ $relic['preview_webp'] }}" type="image/webp">
                        @endif
                        <img class="relic-preview__image"
                            src="{{ $relic['preview_fallback'] ?? $relic['preview_url'] }}"
                            alt="{{ $relic['preview_alt'] ?? $relic['name'] }}" width="1280" height="720"
                            fetchpriority="high" loading="eager" decoding="async">
                    </picture>
                </figure>
            @endif
        </div>
    </section>

    <section class="section section--tight">
        <div class="section__inner">
            <div class="feature-section">
                <h2 class="feature-section__title">Features</h2>
                <x-feature-grid :items="$relic['features']" />

                <h2 class="feature-section__title">Upcoming</h2>
                <x-feature-grid :items="$relic['upcoming']" variant="muted" />
            </div>

            <p class="feature-section__note">
                Relic Launcher and SmelterWorks are not affiliated with Anego Studios.
            </p>
        </div>
    </section>
</x-layouts.site>
