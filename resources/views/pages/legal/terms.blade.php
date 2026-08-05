<x-layouts.site title="Terms of use"
    description="SmelterWorks terms for the website, Relic Launcher downloads, and upcoming hosting.">
    <section class="page-hero">
        <div class="page-hero__inner">
            <h1 class="page-hero__title">Terms of use</h1>
            <p class="page-hero__lede">
                Effective {{ $legal['effective_date'] }}. Operated by {{ $legal['operator'] }}.
            </p>
        </div>
    </section>

    <section class="section section--tight">
        <div class="section__inner prose-block">
            <p>
                These terms cover this website, Relic Launcher downloads linked from it, and hosting once purchases
                open.
                Vintage Story is made by Anego Studios. SmelterWorks is not affiliated with them.
            </p>

            <h2>Website and software</h2>
            <ul>
                <li>Site content is provided as-is for the SmelterWorks projects.</li>
                <li>Relic Launcher source and binaries follow their repository license (0BSD) and release notes.</li>
                <li>You need a valid Vintage Story license to play. We do not sell the game.</li>
            </ul>

            <h2>Hosting (when available)</h2>
            <ul>
                <li>Plans, regions, and stock limits are shown on the Hosting page.</li>
                <li>Refunds follow the policy printed on that page.</li>
                <li>Do not use servers for illegal activity, attacks, or abuse of the game or our network.</li>
                <li>You can export worlds and mods, including Docker self-host packages when that feature ships.</li>
            </ul>

            <h2>Acceptable use</h2>
            <p>
                Do not attempt to break into the site, panel, or other customers' servers. Do not scrape in a way that
                harms the service. We may suspend accounts that burn resources or break these terms.
            </p>

            <h2>Disclaimer</h2>
            <p>
                The software and hosting are provided without warranty. We work to keep things online and safe.
                We are not liable for lost worlds beyond the backups we offer, or for third-party services such as
                GitHub, Fluxer, or payment processors.
            </p>

            <h2>Contact</h2>
            <p>
                Questions: <a href="{{ route('contact') }}">Contact</a>.
            </p>

            <div class="action-row">
                <x-button :href="route('privacy')" variant="ghost">Privacy</x-button>
            </div>
        </div>
    </section>
</x-layouts.site>
