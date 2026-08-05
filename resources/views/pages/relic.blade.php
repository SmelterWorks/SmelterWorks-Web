<x-layouts.site :title="$relic['name']" :description="$relic['summary']">
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
                    <x-button :href="route('relic.download')">
                        Download
                    </x-button>
                    <x-button :href="$relic['repo_url']" variant="ghost" rel="noopener noreferrer" target="_blank">
                        Source
                    </x-button>
                </div>
            </div>

            @if (filled($relic['preview_url'] ?? null))
                <figure class="relic-preview">
                    <img class="relic-preview__image" src="{{ $relic['preview_url'] }}"
                        alt="{{ $relic['preview_alt'] ?? $relic['name'] }}" width="1280" height="720"
                        loading="eager" decoding="async">
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
