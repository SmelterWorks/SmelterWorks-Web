<x-layouts.site title="Contact"
    description="Contact SmelterWorks about mods, Relic Launcher, contributions, and support.">
    <section class="page-hero">
        <div class="page-hero__inner">
            <h1 class="page-hero__title">Contact</h1>
            <p class="page-hero__lede">
                {{ $contact['intro'] }}
            </p>
        </div>
    </section>

    <section class="section section--tight">
        <div class="section__inner prose-block">
            @if (filled($contact['email']))
                <h2>Email</h2>
                <p>
                    <a
                        href="mailto:{{ $contact['email'] }}">{{ \App\Support\ContactEmail::obfuscate($contact['email']) }}</a>
                </p>
            @endif

            @if (filled($links['fluxer']))
                <h2>Community chat</h2>
                <p>
                    For quick questions, join us on
                    <a href="{{ $links['fluxer'] }}" rel="noopener noreferrer" target="_blank">Fluxer</a>.
                </p>
            @endif

            @if (filled($links['github']))
                <h2>Code and bug reports</h2>
                <p>
                    Open an issue on
                    <a href="{{ $links['github'] }}" rel="noopener noreferrer" target="_blank">GitHub</a>
                    for bugs, docs, and pull requests.
                </p>
            @endif

            @unless (filled($contact['email']) || filled($links['fluxer']) || filled($links['github']))
                <p>Contact details are not configured for this deployment yet.</p>
            @endunless

            <div class="action-row">
                <x-button :href="route('about')" variant="ghost">About</x-button>
                <x-button :href="route('contribute')" variant="ghost">Contribute</x-button>
            </div>
        </div>
    </section>
</x-layouts.site>
