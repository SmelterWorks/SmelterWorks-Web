@props([
    'title',
    'detected' => null,
    'platforms' => [],
    'preview' => null,
])

<section class="page-hero page-hero--split">
    <div class="page-hero__inner download-hero-layout">
        <div class="download-hero-layout__copy">
            <h1 class="page-hero__title">{{ $title }}</h1>
            <p class="page-hero__lede">
                @if (filled($detected) && ($detected['family'] ?? 'unknown') !== 'unknown')
                    Looks like you are on {{ $detected['label'] }}.
                @else
                    Pick a build for your system.
                @endif
                Stable and nightly installers download from this site. Release notes stay on the upstream tracker.
            </p>
            @if ($platforms !== [])
                <div class="platform-list platform-list--compact">
                    @foreach ($platforms as $platform)
                        <x-platform-chip :platform="$platform['icon']" :label="$platform['label']"
                            :detail="$platform['detail']" />
                    @endforeach
                </div>
            @endif
        </div>

        @if (filled($preview['url'] ?? null))
            <div class="download-hero-layout__preview">
                <x-app-preview :url="$preview['url']" :alt="$preview['alt']" :webp="$preview['webp'] ?? null"
                    :fallback="$preview['fallback'] ?? null" compact :priority="true" />
            </div>
        @endif
    </div>
</section>
