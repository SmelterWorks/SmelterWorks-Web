@php
    $platformIcon = static function (string $id): string {
        return str_starts_with($id, 'macos') ? 'macos' : $id;
    };

    $relicJsonLd = [
        array_filter([
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
        ], fn (mixed $value): bool => $value !== null && $value !== ''),
    ];
@endphp

<x-layouts.site title="Download Relic Launcher" description="Download Relic Launcher for Windows, Linux, or macOS."
    :image="$relic['preview_url'] ?? null" :image-alt="$relic['preview_alt'] ?? $relic['name']"
    :json-ld="$relicJsonLd">
    <section class="page-hero">
        <div class="page-hero__inner">
            <h1 class="page-hero__title">Download Relic Launcher</h1>
            <p class="page-hero__lede">
                @if ($detected['family'] !== 'unknown')
                    Looks like you are on {{ $detected['label'] }}.
                @else
                    Pick a build for your system.
                @endif
                Release packages run on their own. No extra runtime install.
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
            @if ($suggested)
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
                        <x-button :href="$suggested['url']" rel="noopener noreferrer" target="_blank">
                            Download {{ $suggested['label'] }}
                        </x-button>
                        <x-button :href="$relic['releases_url']" variant="ghost" rel="noopener noreferrer" target="_blank">
                            All releases
                        </x-button>
                    </div>
                </div>
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
                        <x-button :href="$download['url']" variant="ghost" rel="noopener noreferrer" target="_blank">
                            Download
                        </x-button>
                    </article>
                @endforeach
            </div>

            <div class="prose-block" style="margin-top: 2rem;">
                <p>
                    Releases ship from GitHub. If a direct asset is not published yet, the button opens the latest
                    release page.
                    Source and issue tracker:
                    <a href="{{ $relic['repo_url'] }}" rel="noopener noreferrer"
                        target="_blank">{{ $relic['repo_url'] }}</a>.
                </p>
                <div class="action-row">
                    <x-button :href="route('relic')" variant="ghost">Back to Relic</x-button>
                </div>
            </div>
        </div>
    </section>
</x-layouts.site>
