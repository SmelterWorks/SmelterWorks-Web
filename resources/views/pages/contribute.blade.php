<x-layouts.site title="Contribute" description="How to contribute to SmelterWorks open-source projects.">
    <section class="page-hero">
        <div class="page-hero__inner">
            <h1 class="page-hero__title">Contribute</h1>
            <p class="page-hero__lede">
                Patches, tests, docs, and bug reports are welcome. Pick a repository and ship a small change.
            </p>
        </div>
    </section>

    <section class="section section--tight">
        <div class="section__inner prose-block">
            <h2>Start here</h2>
            <ol class="steps">
                <li>Browse Projects or open Relic Launcher on GitHub.</li>
                <li>Read <a href="https://github.com/SmelterWorks/SmelterWorks-Web/blob/main/CONTRIBUTING.md"
                        rel="noopener noreferrer" target="_blank">CONTRIBUTING.md</a> in this repository.</li>
                <li>Ship a focused pull request. Prefer clarity over cleverness.</li>
            </ol>

            <h2>Useful help</h2>
            <ul>
                <li>Reproduce bugs and write clear reports</li>
                <li>Improve docs, packaging, and setup scripts</li>
                <li>Test mods and Relic against current Vintage Story builds</li>
                <li>Talk through ideas in community chat before large redesigns</li>
            </ul>

            <div class="action-row">
                @if (filled(config('smelterworks.links.github')))
                    <x-button href="{{ config('smelterworks.links.github') }}" rel="noopener noreferrer"
                        target="_blank">
                        GitHub
                    </x-button>
                @endif
                @if (filled(config('smelterworks.links.fluxer')))
                    <x-button href="{{ config('smelterworks.links.fluxer') }}" variant="ghost" rel="noopener noreferrer"
                        target="_blank">
                        Fluxer
                    </x-button>
                @endif
                <x-button href="{{ route('projects.index') }}" variant="ghost">
                    View projects
                </x-button>
            </div>
        </div>
    </section>
</x-layouts.site>
