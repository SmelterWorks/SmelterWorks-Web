<footer class="site-footer">
    <div class="site-footer__inner">
        <div class="site-footer__top">
            <div class="site-footer__brand">
                <div class="site-footer__identity">
                    <x-brand-logo variant="transparent" :size="28" />
                </div>
                <p class="site-footer__tagline">{{ config('smelterworks.tagline') }}</p>
            </div>

            <div class="site-footer__columns">
                <div class="site-footer__group">
                    <p class="site-footer__heading">Product</p>
                    <ul class="site-footer__list">
                        <li><a href="{{ route('hosting') }}">Hosting</a></li>
                        <li><a href="{{ route('mods') }}">Mods</a></li>
                        <li><a href="{{ route('relic') }}">Relic</a></li>
                        <li><a href="{{ route('projects.index') }}">Projects</a></li>
                    </ul>
                </div>

                <div class="site-footer__group">
                    <p class="site-footer__heading">About</p>
                    <ul class="site-footer__list">
                        <li><a href="{{ route('about') }}">About</a></li>
                        <li><a href="{{ route('donate') }}">Donate</a></li>
                        <li><a href="{{ route('contribute') }}">Contribute</a></li>
                        <li><a href="{{ route('contact') }}">Contact</a></li>
                    </ul>
                </div>

                <div class="site-footer__group">
                    <p class="site-footer__heading">Legal</p>
                    <ul class="site-footer__list">
                        <li><a href="{{ route('privacy') }}">Privacy</a></li>
                        <li><a href="{{ route('terms') }}">Terms</a></li>
                    </ul>
                </div>

                <div class="site-footer__group">
                    <p class="site-footer__heading">Links</p>
                    <ul class="site-footer__list">
                        @if (filled(config('smelterworks.links.fluxer')))
                            <li>
                                <a href="{{ config('smelterworks.links.fluxer') }}" rel="noopener noreferrer"
                                    target="_blank">Fluxer</a>
                            </li>
                        @endif
                        @if (filled(config('smelterworks.links.github')))
                            <li>
                                <a href="{{ config('smelterworks.links.github') }}" rel="noopener noreferrer"
                                    target="_blank">GitHub</a>
                            </li>
                        @endif
                        <li>
                            <a href="{{ config('smelterworks.links.vintage_story') }}" rel="noopener noreferrer"
                                target="_blank">Vintage Story</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <p class="site-footer__note">
            Open-source project. Not affiliated with Anego Studios. No ads, no tracking, functional cookies only.
        </p>
    </div>
</footer>
