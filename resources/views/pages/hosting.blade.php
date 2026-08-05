<x-layouts.site title="Hosting" :description="trim($hosting['summary'] . ' ' . $hosting['tagline'])" :rss-url="route('hosting.feed')" rss-title="SmelterWorks Hosting">
    <section class="page-hero">
        <div class="page-hero__inner">
            <h1 class="page-hero__title">Hosting</h1>
            <p class="page-hero__lede">
                {{ $hosting['summary'] }}
            </p>
            <p class="page-hero__lede">
                {{ $hosting['tagline'] }}
            </p>
        </div>
    </section>

    <section class="section section--tight">
        <div class="section__inner">
            @if (session('status'))
                <p class="flash-message">{{ session('status') }}</p>
            @endif

            @include('pages.hosting.partials.currency-bar', ['exchange' => $exchange])

            <div class="plan-grid">
                @foreach ($hosting['plans'] as $plan)
                    @include('pages.hosting.partials.plan-card', [
                        'plan' => $plan,
                        'comingSoon' => $comingSoon,
                    ])
                @endforeach
            </div>

            @include('pages.hosting.partials.details', ['hosting' => $hosting])
        </div>
    </section>
</x-layouts.site>
