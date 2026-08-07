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
    <x-download-hero title="Download Relic Launcher" :detected="$detected" :platforms="$relic['platforms']" :preview="[
        'url' => $relic['preview_url'] ?? null,
        'alt' => $relic['preview_alt'] ?? $relic['name'],
        'webp' => $relic['preview_webp'] ?? null,
        'fallback' => $relic['preview_fallback'] ?? null,
    ]" />

    <section class="section section--tight">
        <div class="section__inner">
            @if ($suggested && ($suggested['available'] ?? false))
                <x-download-suggested :download="$suggested" :stable="$stable" />
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
                        <a href="{{ $relic['releases_url'] }}" rel="noopener noreferrer" target="_blank">open
                            tracker</a>.
                    @endif
                </p>
            @endif

            <div class="download-grid">
                @foreach ($downloads as $download)
                    <x-download-card :download="$download" :suggested="$suggested && $suggested['id'] === $download['id']" />
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
                            <x-download-card :download="$download" channel="nightly" />
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
