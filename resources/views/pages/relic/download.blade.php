@php
    $platformIcon = static function (string $id): string {
        return str_starts_with($id, 'macos') ? 'macos' : $id;
    };

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
                'description' => 'Download Relic Launcher for Windows, Linux, or macOS.',
                'url' => route('relic.download'),
                'downloadUrl' => route('relic.download'),
                'image' => $relic['preview_url'] ?? null,
                'license' => 'https://spdx.org/licenses/0BSD.html',
                'codeRepository' => $relic['repo_url'] ?? null,
            ],
            fn(mixed $value): bool => $value !== null && $value !== '',
        ),
    ];
@endphp

<x-layouts.site title="Download Relic Launcher" description="Download Relic Launcher for Windows, Linux, or macOS."
    :image="$relic['preview_url'] ?? null" :image-alt="$relic['preview_alt'] ?? $relic['name']" :json-ld="$relicJsonLd">
    <section class="page-hero">
        <div class="page-hero__inner">
            <h1 class="page-hero__title">Download Relic Launcher</h1>
            <p class="page-hero__lede">
                @if ($detected['family'] !== 'unknown')
                    Looks like you are on {{ $detected['label'] }}.
                @else
                    Pick a build for your system.
                @endif
                Stable and nightly installers download from this site. Release notes stay on the upstream tracker.
            </p>
            <div class="platform-list platform-list--compact">
                <x-platform-chip platform="windows" label="Windows" detail="10+ x64" />
                <x-platform-chip platform="linux" label="Linux" detail="x64" />
                <x-platform-chip platform="macos" label="macOS" detail="Intel and Apple Silicon" />
            </div>
        </div>
    </section>

    <section class="section section--tight">
        <div class="section__inner">
            @if ($suggested && ($suggested['available'] ?? false) && filled($suggested['url']))
                <div class="download-hero">
                    <p class="download-hero__label">Suggested for you</p>
                    <div class="download-hero__heading">
                        <span class="download-hero__icon" aria-hidden="true">
                            <x-platform-icon :platform="$platformIcon($suggested['id'])" :size="32" />
                        </span>
                        <h2 class="download-hero__title">{{ $suggested['label'] }}</h2>
                    </div>
                    <p class="download-hero__detail">{{ $suggested['detail'] }}</p>
                    <div class="action-row">
                        <x-button :href="$suggested['url']">
                            Download {{ $suggested['label'] }}
                        </x-button>
                        @if ($stable['available'] && filled($stable['html_url']))
                            <x-button :href="$stable['html_url']" variant="ghost" rel="noopener noreferrer" target="_blank">
                                Latest release
                            </x-button>
                        @endif
                    </div>
                </div>
            @endif

            <h2 class="download-channel__title">Latest release</h2>
            @if ($stable['available'])
                <p class="download-channel__lede">
                    @if (filled($stable['tag']))
                        Current stable:
                        <a href="{{ $stable['html_url'] }}" rel="noopener noreferrer"
                            target="_blank">{{ $stable['tag'] }}</a>.
                    @else
                        Pick the build for your OS below.
                    @endif
                    Pick the asset for your OS below.
                </p>
            @else
                <p class="download-channel__lede">
                    No stable build is mirrored on this site yet. The server pulls releases automatically.
                    Refresh in a few minutes.
                    @if (filled($relic['releases_url']))
                        Upstream release notes:
                        <a href="{{ $relic['releases_url'] }}" rel="noopener noreferrer"
                            target="_blank">open tracker</a>.
                    @endif
                </p>
            @endif

            <div class="download-grid">
                @foreach ($downloads as $download)
                    <article @class([
                        'download-card',
                        'download-card--suggested' =>
                            $suggested && $suggested['id'] === $download['id'],
                    ])>
                        <div class="download-card__head">
                            <span class="download-card__icon" aria-hidden="true">
                                <x-platform-icon :platform="$platformIcon($download['id'])" :size="28" />
                            </span>
                            <div>
                                <h3 class="download-card__title">{{ $download['label'] }}</h3>
                                <p class="download-card__detail">{{ $download['detail'] }}</p>
                            </div>
                        </div>
                        @if (($download['available'] ?? false) && filled($download['url']))
                            <x-button :href="$download['url']" variant="ghost">
                                Download
                            </x-button>
                        @else
                            <x-button disabled aria-disabled="true">Not available</x-button>
                        @endif
                    </article>
                @endforeach
            </div>

            @if ($nightly['enabled'])
                <div class="download-channel download-channel--nightly">
                    <div class="download-channel__heading">
                        <h2 class="download-channel__title">Nightly pre-release</h2>
                        <span class="pill pill--muted">Pre-release</span>
                    </div>
                    <p class="download-channel__lede">
                        Automated builds from main. They can break. Use the latest release unless you are testing.
                        @if ($nightly['available'] && filled($nightly['tag']))
                            Current nightly:
                            <a href="{{ $nightly['html_url'] }}" rel="noopener noreferrer"
                                target="_blank">{{ $nightly['tag'] }}</a>.
                        @else
                            No nightly pre-release is published right now.
                        @endif
                    </p>

                    <div class="download-grid">
                        @foreach ($nightly['downloads'] as $download)
                            <article class="download-card download-card--nightly">
                                <div class="download-card__head">
                                    <span class="download-card__icon" aria-hidden="true">
                                        <x-platform-icon :platform="$platformIcon($download['id'])" :size="28" />
                                    </span>
                                    <div>
                                        <h3 class="download-card__title">{{ $download['label'] }}</h3>
                                        <p class="download-card__detail">{{ $download['detail'] }}</p>
                                    </div>
                                </div>
                                @if (($download['available'] ?? false) && filled($download['url']))
                                    <x-button :href="$download['url']" variant="ghost">
                                        Download nightly
                                    </x-button>
                                @else
                                    <x-button disabled aria-disabled="true">No nightly yet</x-button>
                                @endif
                            </article>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="prose-block" style="margin-top: 2rem;">
                <p>
                    Source and issues:
                    <a href="{{ $relic['repo_url'] }}" rel="noopener noreferrer"
                        target="_blank">{{ $relic['repo_url'] }}</a>.
                    Installers are cached on this site.
                    @if (filled($relic['releases_url']))
                        Release notes:
                        <a href="{{ $relic['releases_url'] }}" rel="noopener noreferrer"
                            target="_blank">{{ $relic['releases_url'] }}</a>.
                    @endif
                </p>
                <div class="action-row">
                    <x-button :href="route('relic')" variant="ghost">Back to Relic</x-button>
                </div>
            </div>
        </div>
    </section>
</x-layouts.site>
