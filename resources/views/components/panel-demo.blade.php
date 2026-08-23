@props(['demo'])

@php
    $defaultServer = collect($demo['servers'])->firstWhere('status', 'running') ?? ($demo['servers'][0] ?? null);
@endphp

<div class="panel-demo" data-panel-demo data-mods-api="{{ url($demo['mods_api'] ?? '/panel/demo/mods') }}">
    <script type="application/json" data-panel-config>@json($demo)</script>

    <div class="panel-demo__overlay" data-panel-overlay hidden></div>
    <div class="panel-demo__toast" data-panel-toast hidden role="status" aria-live="polite"></div>

    <header class="panel-demo__bar">
        <button type="button" class="panel-demo__menu-btn" data-panel-menu-open aria-label="Open menu">
            <x-icon name="menu" pack="lucide" :size="18" />
        </button>
        <p class="panel-demo__badge">{{ $demo['preview_label'] }}</p>
        <p class="panel-demo__disclaimer">{{ $demo['disclaimer'] }}</p>
        <a href="{{ url($demo['exit_url'] ?? '/') }}" class="panel-demo__exit">Back to site</a>
    </header>

    <div class="panel-demo__shell">
        <aside class="panel-demo__sidebar" data-panel-sidebar aria-label="Panel navigation">
            <div class="panel-demo__brand">
                <p class="panel-demo__brand-name">SmelterWorks Panel</p>
                <p class="panel-demo__brand-user" data-panel-org-label>{{ $demo['user']['organization'] ?? 'Demo org' }}
                </p>
            </div>

            <nav class="panel-demo__nav">
                @foreach ($demo['nav'] as $item)
                    <button type="button"
                        class="panel-demo__nav-btn @if ($loop->first) is-active @endif"
                        data-panel-tab="{{ $item['id'] }}" aria-controls="panel-view-{{ $item['id'] }}"
                        @if ($loop->first) aria-current="page" @endif>
                        <x-icon :name="$item['icon']" :size="16" />
                        <span>{{ $item['label'] }}</span>
                    </button>
                @endforeach
            </nav>

            <div class="panel-demo__sidebar-servers">
                <p class="panel-demo__sidebar-label">Servers</p>
                @foreach ($demo['servers'] as $server)
                    <button type="button"
                        class="panel-demo__server-link @if ($defaultServer && $server['id'] === $defaultServer['id']) is-active @endif"
                        data-panel-server="{{ $server['id'] }}" data-panel-server-status="{{ $server['status'] }}">
                        <span class="panel-demo__server-dot panel-demo__server-dot--{{ $server['status'] }}"
                            aria-hidden="true"></span>
                        <span class="panel-demo__server-link-name"
                            data-panel-server-name="{{ $server['id'] }}">{{ $server['name'] }}</span>
                        <span class="panel-demo__server-link-type">{{ $server['type'] }}</span>
                    </button>
                @endforeach
            </div>

            <div class="panel-demo__sidebar-foot">
                <button type="button" class="panel-demo__sidebar-user" data-panel-tab-trigger="profile">
                    <span data-panel-user-label>{{ $demo['user']['name'] ?? 'Demo user' }}</span>
                </button>
                @if (filled($demo['attribution']['name'] ?? null))
                    <x-panel-attribution :attribution="$demo['attribution']" />
                @endif
            </div>
        </aside>

        <div class="panel-demo__main">
            <section class="panel-demo__view is-active" id="panel-view-dashboard" data-panel-view="dashboard">
                <header class="panel-demo__view-head">
                    <div>
                        <h1 class="panel-demo__view-title">Dashboard</h1>
                        <p class="panel-demo__view-lede">Cloud servers, paired hardware, and account features.</p>
                    </div>
                </header>

                <div class="panel-demo__feature-grid">
                    @foreach ($demo['features'] as $feature)
                        <div class="panel-demo__feature">
                            <x-icon :name="$feature['icon']" :size="18" />
                            <span>{{ $feature['label'] }}</span>
                        </div>
                    @endforeach
                </div>

                <div class="panel-demo__stack">
                    <article class="panel-demo__card">
                        <div class="panel-demo__card-head">
                            <h2 class="panel-demo__card-title">Cloud servers</h2>
                            <span class="panel-demo__muted">US and Germany</span>
                        </div>
                        <ul class="panel-demo__server-list">
                            @foreach (collect($demo['servers'])->where('type', 'Cloud') as $server)
                                <li class="panel-demo__server-row">
                                    <button type="button" class="panel-demo__server-open"
                                        data-panel-server-open="{{ $server['id'] }}">
                                        <span class="panel-demo__server-name"
                                            data-panel-server-name="{{ $server['id'] }}">{{ $server['name'] }}</span>
                                        <span class="panel-demo__server-meta">{{ $server['region'] }} ·
                                            {{ $server['plan'] }} · {{ $server['players'] }}</span>
                                    </button>
                                    <span @class([
                                        'panel-demo__status',
                                        'panel-demo__status--running' => $server['status'] === 'running',
                                        'panel-demo__status--stopped' => $server['status'] === 'stopped',
                                    ])
                                        data-panel-status-badge="{{ $server['id'] }}">{{ ucfirst($server['status']) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </article>

                    <article class="panel-demo__card">
                        <div class="panel-demo__card-head">
                            <h2 class="panel-demo__card-title">Paired servers</h2>
                            <button type="button" class="panel-demo__link-btn" data-panel-tab-trigger="pairing">Pair
                                hardware</button>
                        </div>
                        <ul class="panel-demo__server-list">
                            @foreach (collect($demo['servers'])->where('type', 'Paired') as $server)
                                <li class="panel-demo__server-row">
                                    <button type="button" class="panel-demo__server-open"
                                        data-panel-server-open="{{ $server['id'] }}">
                                        <span class="panel-demo__server-name"
                                            data-panel-server-name="{{ $server['id'] }}">{{ $server['name'] }}</span>
                                        <span class="panel-demo__server-meta">{{ $server['daemon'] ?? 'Paired' }} ·
                                            {{ $server['players'] }}</span>
                                    </button>
                                    <span @class([
                                        'panel-demo__status',
                                        'panel-demo__status--running' => $server['status'] === 'running',
                                        'panel-demo__status--stopped' => $server['status'] === 'stopped',
                                    ])
                                        data-panel-status-badge="{{ $server['id'] }}">{{ ucfirst($server['status']) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </article>

                    <article class="panel-demo__card">
                        <h2 class="panel-demo__card-title">Paired daemons</h2>
                        <ul class="panel-demo__daemon-list">
                            @foreach ($demo['paired'] as $daemon)
                                <li class="panel-demo__daemon-row">
                                    <div>
                                        <p class="panel-demo__server-name">{{ $daemon['name'] }}</p>
                                        <p class="panel-demo__muted">{{ $daemon['status'] }} ·
                                            {{ $daemon['last_seen'] }} · {{ $daemon['servers'] }} server(s)</p>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </article>
                </div>
            </section>

            @foreach ($demo['servers'] as $server)
                <section class="panel-demo__view" id="panel-view-server-{{ $server['id'] }}" data-panel-view="server"
                    data-panel-server-panel="{{ $server['id'] }}" hidden>
                    <header class="panel-demo__view-head">
                        <div>
                            <div class="panel-demo__title-row">
                                <h1 class="panel-demo__view-title" data-panel-server-title>{{ $server['name'] }}</h1>
                                <button type="button" class="panel-demo__rename-btn" data-panel-rename
                                    aria-label="Rename server">
                                    <x-icon name="pencil" :size="16" />
                                </button>
                            </div>
                            <p class="panel-demo__view-lede">{{ $server['type'] }} · {{ $server['region'] }} ·
                                {{ $server['plan'] }}</p>
                        </div>
                        <span @class([
                            'panel-demo__status',
                            'panel-demo__status--running' => $server['status'] === 'running',
                            'panel-demo__status--stopped' => $server['status'] === 'stopped',
                        ])
                            data-panel-status-badge="{{ $server['id'] }}">{{ ucfirst($server['status']) }}</span>
                    </header>

                    <div class="panel-demo__metrics">
                        <div class="panel-demo__metric"><span class="panel-demo__metric-label">Players</span><span
                                class="panel-demo__metric-value"
                                data-panel-metric-players>{{ $server['players'] }}</span></div>
                        <div class="panel-demo__metric"><span class="panel-demo__metric-label">RAM</span><span
                                class="panel-demo__metric-value">{{ $server['ram'] }}</span></div>
                        <div class="panel-demo__metric"><span class="panel-demo__metric-label">Storage</span><span
                                class="panel-demo__metric-value">{{ $server['storage'] }}</span></div>
                        <div class="panel-demo__metric"><span class="panel-demo__metric-label">Address</span><span
                                class="panel-demo__metric-value panel-demo__metric-value--mono">{{ $server['address'] }}</span>
                        </div>
                    </div>

                    <ul class="panel-demo__pill-list">
                        @foreach ($server['highlights'] as $highlight)
                            <li>{{ $highlight }}</li>
                        @endforeach
                    </ul>

                    <div class="panel-demo__subnav" role="tablist" aria-label="Server sections">
                        <button type="button" class="panel-demo__subnav-btn is-active" data-panel-subtab="console"
                            role="tab">Console</button>
                        <button type="button" class="panel-demo__subnav-btn" data-panel-subtab="backups"
                            role="tab">Backups</button>
                        <button type="button" class="panel-demo__subnav-btn" data-panel-subtab="files"
                            role="tab">Files</button>
                        <button type="button" class="panel-demo__subnav-btn" data-panel-subtab="mods"
                            role="tab">Mods</button>
                    </div>

                    <div class="panel-demo__subview is-active" data-panel-subview="console">
                        <div class="panel-demo__split">
                            <article class="panel-demo__card panel-demo__card--stretch">
                                <div class="panel-demo__card-head">
                                    <h2 class="panel-demo__card-title">Live console</h2>
                                    <div class="panel-demo__toolbar">
                                        <button type="button" class="panel-demo__icon-btn" data-panel-power="start"
                                            aria-label="Start server" title="Start" @disabled($server['status'] === 'running')>
                                            <x-icon name="play" :size="16" />
                                        </button>
                                        <button type="button" class="panel-demo__icon-btn" data-panel-power="stop"
                                            aria-label="Stop server" title="Stop" @disabled($server['status'] === 'stopped')>
                                            <x-icon name="square" :size="16" />
                                        </button>
                                        <button type="button" class="panel-demo__icon-btn"
                                            data-panel-power="restart" aria-label="Restart server" title="Restart">
                                            <x-icon name="refresh-cw" :size="16" />
                                        </button>
                                    </div>
                                </div>
                                <div class="panel-demo__console" data-panel-console aria-live="polite">
                                    @foreach ($demo['console_lines'] as $line)
                                        <p @class([
                                            'panel-demo__console-line',
                                            'panel-demo__console-line--' . ($line['level'] ?? 'info'),
                                        ])>{{ $line['text'] }}</p>
                                    @endforeach
                                </div>
                                <form class="panel-demo__console-form" data-panel-console-form>
                                    <label class="panel-demo__console-input">
                                        <span class="sr-only">Console command</span>
                                        <input type="text" placeholder="Type a command and press Enter"
                                            autocomplete="off">
                                    </label>
                                </form>
                            </article>
                        </div>
                    </div>

                    <div class="panel-demo__subview" data-panel-subview="backups" hidden>
                        <article class="panel-demo__card">
                            <div class="panel-demo__card-head">
                                <h2 class="panel-demo__card-title">Backups</h2>
                                <button type="button" class="panel-demo__action-btn" data-panel-backup-now>Backup
                                    now</button>
                            </div>
                            <ul class="panel-demo__backup-list" data-panel-backup-list>
                                @foreach ($demo['backups'] as $backup)
                                    <li class="panel-demo__backup-row">
                                        <div>
                                            <p class="panel-demo__backup-label">{{ $backup['label'] }}</p>
                                            <p class="panel-demo__muted">{{ $backup['when'] }} ·
                                                {{ $backup['size'] }}</p>
                                        </div>
                                        <div class="panel-demo__row-actions">
                                            @if ($backup['status'] === 'complete')
                                                <button type="button" class="panel-demo__ghost-btn"
                                                    data-panel-backup-restore="{{ $backup['label'] }}">Restore</button>
                                            @endif
                                            <span @class([
                                                'panel-demo__pill',
                                                'panel-demo__pill--running' => $backup['status'] === 'running',
                                            ])>{{ ucfirst($backup['status']) }}</span>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </article>
                    </div>

                    <div class="panel-demo__subview" data-panel-subview="files" hidden>
                        <article class="panel-demo__card">
                            <h2 class="panel-demo__card-title">File browser</h2>
                            <ul class="panel-demo__file-list">
                                @foreach ($demo['files'] as $file)
                                    <li class="panel-demo__file-row">
                                        <button type="button" class="panel-demo__file-open"
                                            data-panel-file-open="{{ $file['path'] }}"
                                            data-panel-file-kind="{{ $file['kind'] }}"
                                            data-panel-file-preview="{{ $file['preview'] ?? '' }}">
                                            <span
                                                class="panel-demo__file-kind">{{ $file['kind'] === 'folder' ? 'dir' : 'file' }}</span>
                                            <span>{{ $file['path'] }}</span>
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                            <div class="panel-demo__file-editor" data-panel-file-editor hidden>
                                <div class="panel-demo__card-head">
                                    <h3 class="panel-demo__card-title" data-panel-file-title></h3>
                                    <button type="button" class="panel-demo__ghost-btn"
                                        data-panel-file-close>Close</button>
                                </div>
                                <textarea class="panel-demo__file-textarea" data-panel-file-textarea rows="10" spellcheck="false"></textarea>
                                <button type="button" class="panel-demo__action-btn" data-panel-file-save>Save
                                    (simulated)</button>
                            </div>
                            <p class="panel-demo__muted">Uploads and SFTP land in the same tree on the real panel.</p>
                        </article>
                    </div>

                    <div class="panel-demo__subview" data-panel-subview="mods" hidden>
                        <p class="panel-demo__muted">Browse VS ModDB for this server. Install actions stay simulated.
                        </p>
                        <button type="button" class="panel-demo__action-btn" data-panel-tab-trigger="mods">Open mod
                            browser</button>
                    </div>
                </section>
            @endforeach

            <section class="panel-demo__view" id="panel-view-mods" data-panel-view="mods" hidden>
                <header class="panel-demo__view-head">
                    <div>
                        <h1 class="panel-demo__view-title">Mod browser</h1>
                        <p class="panel-demo__view-lede">Live VS ModDB browse and search with logos. Install, enable,
                            disable, and remove are simulated.</p>
                    </div>
                </header>

                <div class="panel-demo__mod-toolbar">
                    <form class="panel-demo__search" data-panel-mod-search>
                        <input type="search" name="q" placeholder="Search VS ModDB…"
                            aria-label="Search VS ModDB" autocomplete="off">
                        <button type="submit" class="panel-demo__action-btn">Search</button>
                    </form>
                    <label class="panel-demo__sort">
                        <span class="sr-only">Sort mods</span>
                        <select data-panel-mod-sort aria-label="Sort mods">
                            <option value="downloads">Most downloads</option>
                            <option value="trendingpoints">Trending</option>
                            <option value="lastreleased">Recently updated</option>
                            <option value="follows">Most followed</option>
                        </select>
                    </label>
                    <div class="panel-demo__mod-tabs" role="tablist">
                        <button type="button" class="panel-demo__subnav-btn is-active" data-panel-mod-tab="browse"
                            role="tab">Browse</button>
                        <button type="button" class="panel-demo__subnav-btn" data-panel-mod-tab="installed"
                            role="tab">Installed</button>
                    </div>
                </div>

                <p class="panel-demo__muted" data-panel-mod-status>Loading popular mods from VS ModDB…</p>
                <div class="panel-demo__mod-grid" data-panel-mod-browse></div>
                <div class="panel-demo__mod-grid" data-panel-mod-installed hidden></div>
            </section>

            <section class="panel-demo__view" id="panel-view-pairing" data-panel-view="pairing" hidden>
                <header class="panel-demo__view-head">
                    <div>
                        <h1 class="panel-demo__view-title">Pairing</h1>
                        <p class="panel-demo__view-lede">Install <code>smelterd</code> on your host, create a token,
                            and pair managed access to your hardware.</p>
                    </div>
                </header>

                <div class="panel-demo__stack">
                    <article class="panel-demo__card">
                        <h2 class="panel-demo__card-title">Create registration token</h2>
                        <label class="panel-demo__field">
                            <span>Daemon name</span>
                            <input type="text" data-panel-daemon-name
                                value="{{ $demo['pairing']['daemon_name'] ?? 'my-host' }}">
                        </label>
                        <button type="button" class="panel-demo__action-btn" data-panel-create-token>Create
                            token</button>
                    </article>

                    <article class="panel-demo__card" data-panel-token-card hidden>
                        <h2 class="panel-demo__card-title">Copy now</h2>
                        <p class="panel-demo__muted">Paste into <code>SMELTER_TOKEN</code> on the host. Tokens are
                            shown once in production.</p>
                        <code class="panel-demo__token" data-panel-token-output></code>
                        <button type="button" class="panel-demo__ghost-btn" data-panel-copy-token>Copy token</button>
                    </article>

                    <article class="panel-demo__card">
                        <h2 class="panel-demo__card-title">Paired daemons</h2>
                        <ul class="panel-demo__daemon-list">
                            @foreach ($demo['paired'] as $daemon)
                                <li class="panel-demo__daemon-row">
                                    <div>
                                        <p class="panel-demo__server-name">{{ $daemon['name'] }}</p>
                                        <p class="panel-demo__muted">{{ $daemon['status'] }} ·
                                            {{ $daemon['last_seen'] }}</p>
                                    </div>
                                    <span @class([
                                        'panel-demo__pill',
                                        'panel-demo__pill--running' => $daemon['status'] === 'online',
                                    ])>{{ ucfirst($daemon['status']) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </article>
                </div>
            </section>

            <section class="panel-demo__view" id="panel-view-profile" data-panel-view="profile" hidden>
                <header class="panel-demo__view-head">
                    <div>
                        <h1 class="panel-demo__view-title">Profile</h1>
                        <p class="panel-demo__view-lede">Account details for this demo organization.</p>
                    </div>
                </header>

                <article class="panel-demo__card panel-demo__card--narrow">
                    <form class="panel-demo__form" data-panel-profile-form>
                        <label class="panel-demo__field">
                            <span>Display name</span>
                            <input type="text" name="name" value="{{ $demo['user']['name'] ?? '' }}" required>
                        </label>
                        <label class="panel-demo__field">
                            <span>Email</span>
                            <input type="email" name="email" value="{{ $demo['user']['email'] ?? '' }}"
                                required>
                        </label>
                        <label class="panel-demo__field">
                            <span>Organization</span>
                            <input type="text" name="organization"
                                value="{{ $demo['user']['organization'] ?? '' }}" required>
                        </label>
                        <label class="panel-demo__field">
                            <span>Timezone</span>
                            <input type="text" name="timezone" value="{{ $demo['user']['timezone'] ?? '' }}">
                        </label>
                        <p class="panel-demo__muted">Role: {{ $demo['user']['role'] ?? 'Owner' }}</p>
                        <button type="submit" class="panel-demo__action-btn">Save profile</button>
                    </form>
                </article>
            </section>

            <section class="panel-demo__view" id="panel-view-settings" data-panel-view="settings" hidden>
                <header class="panel-demo__view-head">
                    <div>
                        <h1 class="panel-demo__view-title">Settings</h1>
                        <p class="panel-demo__view-lede">Panel preferences for this demo session.</p>
                    </div>
                </header>

                <article class="panel-demo__card panel-demo__card--narrow">
                    <ul class="panel-demo__settings-list">
                        @foreach ($demo['settings'] as $setting)
                            <li class="panel-demo__setting-row">
                                <span>{{ $setting['label'] }}</span>
                                <button type="button"
                                    class="panel-demo__toggle @if ($setting['enabled']) is-on @endif"
                                    data-panel-setting="{{ $setting['id'] }}"
                                    aria-pressed="{{ $setting['enabled'] ? 'true' : 'false' }}"
                                    aria-label="{{ $setting['label'] }}">
                                    <span class="panel-demo__toggle-knob"></span>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </article>
            </section>
        </div>
    </div>
</div>
