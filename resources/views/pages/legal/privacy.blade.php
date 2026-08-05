<x-layouts.site title="Privacy"
    description="SmelterWorks privacy policy. No ads, no tracking, no telemetry. Functional cookies only.">
    <section class="page-hero">
        <div class="page-hero__inner">
            <h1 class="page-hero__title">Privacy</h1>
            <p class="page-hero__lede">
                Effective {{ $legal['effective_date'] }}. Operated by {{ $legal['operator'] }}.
            </p>
        </div>
    </section>

    <section class="section section--tight">
        <div class="section__inner prose-block">
            <p>
                We do not run ads. We do not sell personal data. We do not use tracking pixels,
                analytics beacons, or product telemetry on this website.
            </p>

            <h2>What we collect</h2>
            <ul>
                <li>Server logs needed to run the site (IP address, user agent, requested URL, time). Kept only as long
                    as operations need them.</li>
                <li>Account and billing details when you buy hosting or use the panel. Used to provide the service,
                    refunds, and support.</li>
                <li>Messages you send us on Fluxer or email.</li>
            </ul>

            <h2>Cookies</h2>
            <p>
                Cookies on this site are functional only. Examples: session login for the panel, CSRF protection,
                and remembering a currency preference if you set one. No advertising cookies. No third-party trackers.
            </p>

            <h2>Relic Launcher</h2>
            <p>
                Relic Launcher is open source. The website download page does not phone home beyond normal HTTP requests
                to fetch this page and GitHub release links. Game account login and mod downloads use Vintage Story and
                ModDB services under their own terms.
            </p>

            <h2>Third parties</h2>
            <ul>
                <li>GitHub for source and release files.</li>
                <li>Frankfurter / European Central Bank reference rates for USD to EUR display on hosting.</li>
                <li>CARTO / OpenStreetMap tiles via OpenLayers on the hosting regions map. No account, no tracker
                    scripts.</li>
                <li>Fluxer for community chat if you join the invite.</li>
                <li>Payment processors when checkout is enabled (details will be listed here before charges go live).
                </li>
            </ul>

            <h2>Your choices</h2>
            <p>
                Ask for a copy, correction, or deletion of account data tied to hosting or panel use by
                <a href="{{ route('contact') }}">contacting us</a>.
                Export your world and mods from the panel before you close an account if you want to keep them.
            </p>

            <h2>Changes</h2>
            <p>
                If this policy changes, we will update the effective date on this page.
            </p>

            <div class="action-row">
                <x-button :href="route('terms')" variant="ghost">Terms of use</x-button>
            </div>
        </div>
    </section>
</x-layouts.site>
