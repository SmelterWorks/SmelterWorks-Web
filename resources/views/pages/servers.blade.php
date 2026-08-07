<x-layouts.site title="Servers" description="Browse public Vintage Story servers with live player counts, versions, and filters.">
    @push('head')
        @vite(['resources/js/servers.js'])
    @endpush

    <section class="page-hero page-hero--compact">
        <div class="page-hero__inner">
            <h1 class="page-hero__title">Servers</h1>
            <p class="page-hero__lede">
                Public Vintage Story servers from the master list, cached by SmelterWorks so Relic and this page do not hammer upstream.
            </p>
        </div>
    </section>

    <section class="section section--near servers-page" data-servers-page data-api-url="{{ $apiUrl }}">
        <div class="section__inner">
            <div class="servers-stats" data-servers-stats hidden>
                <div class="servers-stat">
                    <p class="servers-stat__value" data-stat-players>0</p>
                    <p class="servers-stat__label">Players online</p>
                </div>
                <div class="servers-stat">
                    <p class="servers-stat__value" data-stat-servers>0</p>
                    <p class="servers-stat__label">Public servers</p>
                </div>
                <div class="servers-stat">
                    <p class="servers-stat__value" data-stat-version>0%</p>
                    <p class="servers-stat__label" data-stat-version-label>On latest major version</p>
                </div>
            </div>

            <p class="servers-status" data-servers-status hidden></p>

            <div class="servers-toolbar">
                <div class="servers-toolbar__top">
                    <label class="servers-search">
                        <span class="sr-only">Search servers</span>
                        <input type="search" class="servers-search__input" placeholder="Search name, IP, or description"
                            data-servers-search autocomplete="off" spellcheck="false">
                    </label>

                    <div class="servers-view-toggle" role="group" aria-label="Layout">
                        <button type="button" class="servers-view-btn is-active" data-servers-view="card"
                            aria-pressed="true" aria-label="Card view">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="3" y="3" width="7" height="7" rx="1" />
                                <rect x="14" y="3" width="7" height="7" rx="1" />
                                <rect x="3" y="14" width="7" height="7" rx="1" />
                                <rect x="14" y="14" width="7" height="7" rx="1" />
                            </svg>
                        </button>
                        <button type="button" class="servers-view-btn" data-servers-view="list" aria-pressed="false"
                            aria-label="List view">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <line x1="8" y1="6" x2="21" y2="6" />
                                <line x1="8" y1="12" x2="21" y2="12" />
                                <line x1="8" y1="18" x2="21" y2="18" />
                                <line x1="3" y1="6" x2="3.01" y2="6" />
                                <line x1="3" y1="12" x2="3.01" y2="12" />
                                <line x1="3" y1="18" x2="3.01" y2="18" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="servers-controls">
                    <div class="servers-filters">
                        <label class="servers-filter">
                            <span class="servers-filter__label">Version</span>
                            <select class="servers-filter__control" data-servers-version>
                                <option value="">All versions</option>
                            </select>
                        </label>

                        <label class="servers-filter">
                            <span class="servers-filter__label">Playstyle</span>
                            <select class="servers-filter__control" data-servers-playstyle>
                                <option value="">All playstyles</option>
                            </select>
                        </label>

                        <label class="servers-filter">
                            <span class="servers-filter__label">Sort</span>
                            <select class="servers-filter__control" data-servers-sort>
                                <option value="players">Players</option>
                                <option value="name">Name</option>
                                <option value="version">Version</option>
                            </select>
                        </label>
                    </div>

                    <div class="servers-toggles" role="group" aria-label="Server filters">
                        <button type="button" class="servers-toggle-btn is-active" data-servers-hide-empty
                            aria-pressed="true">Hide empty</button>
                        <button type="button" class="servers-toggle-btn" data-servers-mods-only
                            aria-pressed="false">Modded only</button>
                        <button type="button" class="servers-toggle-btn" data-servers-no-password
                            aria-pressed="false">No password</button>
                        <button type="button" class="servers-toggle-btn" data-servers-not-whitelisted
                            aria-pressed="false">Open join</button>
                    </div>
                </div>
            </div>

            <p class="servers-results" data-servers-results hidden></p>

            <div class="servers-list servers-list--cards" data-servers-list>
                <p class="servers-loading">Loading server list...</p>
            </div>

            <nav class="servers-pagination" data-servers-pagination hidden aria-label="Server list pages">
                <button type="button" class="servers-page-btn" data-servers-page-prev disabled>Previous</button>
                <p class="servers-page-status" data-servers-page-status></p>
                <button type="button" class="servers-page-btn" data-servers-page-next>Next</button>
            </nav>

            <p class="servers-note">
                List data comes from the Vintage Story master server API, proxied and cached on SmelterWorks.
                Not affiliated with Anego Studios.
                <a href="{{ $officialListUrl }}" rel="noopener noreferrer" target="_blank">Official server browser</a>
            </p>
        </div>
    </section>
</x-layouts.site>
