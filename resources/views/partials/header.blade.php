<header class="site-header" data-site-header>
    <div class="site-header__inner">
        <a href="{{ route('home') }}" class="brand-mark" aria-label="{{ config('app.name') }} home">
            <x-brand-logo variant="transparent" :size="32" />
        </a>

        <nav class="site-nav" aria-label="Primary" data-site-nav>
            @foreach (config('smelterworks.nav') as $item)
                @php
                    $active = str_starts_with($item['route'], 'projects.')
                        ? request()->routeIs('projects.*')
                        : request()->routeIs($item['route']);
                @endphp
                <x-nav-link :href="route($item['route'])" :active="$active">
                    {{ $item['label'] }}
                </x-nav-link>
            @endforeach
        </nav>

        <div class="site-header__actions" data-site-actions>
            @if (filled(config('smelterworks.links.fluxer')))
                <x-icon-link :href="config('smelterworks.links.fluxer')" label="Fluxer">
                    <x-icons.fluxer />
                </x-icon-link>
            @endif

            @if (filled(config('smelterworks.links.forgejo')))
                <x-icon-link :href="config('smelterworks.links.forgejo')" label="Forgejo">
                    <x-icons.forgejo />
                </x-icon-link>
            @endif
        </div>

        <button type="button" class="menu-toggle" data-menu-toggle aria-controls="mobile-nav" aria-expanded="false"
            aria-label="Open menu">
            <span class="menu-toggle__open" aria-hidden="true">
                <x-icon name="menu" pack="lucide" :size="22" />
            </span>
            <span class="menu-toggle__close" aria-hidden="true">
                <x-icon name="x" pack="lucide" :size="22" />
            </span>
        </button>
    </div>

    <div class="mobile-nav" id="mobile-nav" data-mobile-nav hidden>
        <nav class="mobile-nav__links" aria-label="Mobile">
            @foreach (config('smelterworks.nav') as $item)
                @php
                    $active = str_starts_with($item['route'], 'projects.')
                        ? request()->routeIs('projects.*')
                        : request()->routeIs($item['route']);
                @endphp
                <x-nav-link :href="route($item['route'])" :active="$active">
                    {{ $item['label'] }}
                </x-nav-link>
            @endforeach
        </nav>

        <div class="mobile-nav__actions">
            @if (filled(config('smelterworks.links.fluxer')))
                <x-icon-link :href="config('smelterworks.links.fluxer')" label="Fluxer">
                    <x-icons.fluxer />
                </x-icon-link>
            @endif

            @if (filled(config('smelterworks.links.forgejo')))
                <x-icon-link :href="config('smelterworks.links.forgejo')" label="Forgejo">
                    <x-icons.forgejo />
                </x-icon-link>
            @endif
        </div>
    </div>
</header>
