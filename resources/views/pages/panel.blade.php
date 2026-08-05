<x-layouts.site title="Panel"
    description="SmelterWorks control panel for Vintage Story hosting, mods, backups, and files.">
    <section class="page-hero">
        <div class="page-hero__inner">
            <h1 class="page-hero__title">Panel</h1>
            <p class="page-hero__lede">
                Manage servers, mods, backups, and files from one place.
            </p>
        </div>
    </section>

    <section class="section section--tight">
        <div class="section__inner prose-block">
            <p>
                The SmelterWorks panel ships in this web app. Account login, server provisioning,
                the mod browser, backups, and the file editor land here as the hosting stack is wired up.
            </p>
            <p>
                Until then, ask in community chat if configured, or use GitHub for the open-source code.
            </p>
            <div class="action-row">
                <x-button href="{{ route('hosting') }}">Hosting plans</x-button>
                @if (filled(config('smelterworks.links.fluxer')))
                    <x-button href="{{ config('smelterworks.links.fluxer') }}" variant="ghost" rel="noopener noreferrer"
                        target="_blank">
                        Fluxer
                    </x-button>
                @endif
                @if (filled(config('smelterworks.links.github')))
                    <x-button href="{{ config('smelterworks.links.github') }}" variant="ghost" rel="noopener noreferrer"
                        target="_blank">
                        GitHub
                    </x-button>
                @endif
            </div>
        </div>
    </section>
</x-layouts.site>
