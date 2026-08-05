<x-layouts.site :preload-image="$relic['preview_webp'] ?? null">
    <section class="hero">
        <div class="hero__atmosphere" aria-hidden="true">
            <div class="hero__grain"></div>
            <div class="hero__heat"></div>
            <svg class="hero__forge" viewBox="0 0 1200 800" preserveAspectRatio="xMidYMid slice" role="presentation">
                <defs>
                    <linearGradient id="hearth" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stop-color="#c45c26" stop-opacity="0.55" />
                        <stop offset="45%" stop-color="#8a3b1a" stop-opacity="0.28" />
                        <stop offset="100%" stop-color="#2a241f" stop-opacity="0" />
                    </linearGradient>
                </defs>
                <rect width="1200" height="800" fill="url(#hearth)" />
                <path
                    d="M0 560 C180 500 320 620 480 540 C640 460 760 600 920 520 C1040 460 1120 500 1200 470 L1200 800 L0 800 Z"
                    fill="#2f2923" opacity="0.55" />
                <path
                    d="M0 620 C220 570 360 690 540 610 C720 530 860 680 1040 600 C1120 565 1160 590 1200 575 L1200 800 L0 800 Z"
                    fill="#1f1b17" opacity="0.72" />
            </svg>
        </div>

        <div class="hero__content">
            <div class="hero__copy">
                <h1 class="hero__headline reveal" data-reveal>
                    Open-source tools, mods, and hosting for Vintage Story
                </h1>
                <p class="hero__lede reveal" data-reveal>
                    {{ config('smelterworks.mission') }}
                </p>
                <div class="hero__actions reveal" data-reveal>
                    <x-button href="{{ route('hosting') }}" class="button--badged">
                        Hosting
                        @if ($hostingComingSoon)
                            <span class="button__badge">Soon</span>
                        @endif
                    </x-button>
                    <x-button href="{{ route('relic') }}" variant="ghost">Relic Launcher</x-button>
                    <x-button href="{{ route('mods') }}" variant="ghost">Mods</x-button>
                </div>
            </div>

            @if (filled($relic['preview_url'] ?? null))
                <figure class="hero__preview relic-preview reveal" data-reveal>
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

    <section class="section section--near section--compact-bottom">
        <div class="section__inner">
            <div class="section__intro section__intro--row">
                <h2 class="section__title">Hosting</h2>
                @if ($hostingComingSoon)
                    <span class="pill">Coming soon</span>
                @endif
            </div>

            <x-feature-grid :items="$hostingHighlights" />

            <p class="section__note">
                VS =
                <a href="{{ config('smelterworks.links.vintage_story') }}" class="section__note-link"
                    rel="noopener noreferrer" target="_blank">Vintage Story</a>
            </p>

            @if (filled($hostingNote))
                <p class="section__note">{{ $hostingNote }}</p>
            @endif

            <div class="section__footer">
                <x-button href="{{ route('hosting') }}" variant="ghost">View plans</x-button>
            </div>
        </div>
    </section>

    <section class="section section--near">
        <div class="section__inner">
            <div class="section__intro">
                <h2 class="section__title">Projects</h2>
            </div>

            <div class="project-list">
                @forelse ($featuredProjects as $project)
                    <x-project-row :project="$project" />
                @empty
                    <x-empty-state title="No projects yet">
                        <p>Published software and mods will appear here.</p>
                    </x-empty-state>
                @endforelse
            </div>

            <div class="section__footer">
                <x-button href="{{ route('projects.index') }}" variant="ghost">All projects</x-button>
            </div>
        </div>
    </section>
</x-layouts.site>
