const PAGE_SIZE = 40;
const SEARCH_DEBOUNCE_MS = 200;

const page = document.querySelector('[data-servers-page]');

if (page) {
    const apiUrl = page.dataset.apiUrl;
    const stats = page.querySelector('[data-servers-stats]');
    const status = page.querySelector('[data-servers-status]');
    const results = page.querySelector('[data-servers-results]');
    const list = page.querySelector('[data-servers-list]');
    const pagination = page.querySelector('[data-servers-pagination]');
    const pageStatus = page.querySelector('[data-servers-page-status]');
    const pagePrev = page.querySelector('[data-servers-page-prev]');
    const pageNext = page.querySelector('[data-servers-page-next]');
    const searchInput = page.querySelector('[data-servers-search]');
    const versionSelect = page.querySelector('[data-servers-version]');
    const playstyleSelect = page.querySelector('[data-servers-playstyle]');
    const sortSelect = page.querySelector('[data-servers-sort]');
    const hideEmptyToggle = page.querySelector('[data-servers-hide-empty]');
    const modsOnlyToggle = page.querySelector('[data-servers-mods-only]');
    const noPasswordToggle = page.querySelector('[data-servers-no-password]');
    const notWhitelistedToggle = page.querySelector('[data-servers-not-whitelisted]');
    const viewButtons = page.querySelectorAll('[data-servers-view]');

    let servers = [];
    let filteredServers = [];
    let currentPage = 1;
    let viewMode = 'card';
    let searchTimer = null;
    let listSummary = null;

    const players = (server) => {
        const value = Number(server.players ?? 0);

        return Number.isFinite(value) ? value : 0;
    };

    const maxPlayers = (server) => {
        const value = Number(server.maxPlayers ?? 0);

        return Number.isFinite(value) ? value : 0;
    };

    const hasMods = (server) => Array.isArray(server.mods) && server.mods.length > 0;

    const playstyleId = (server) => {
        const playstyle = server.playstyle;

        return playstyle && typeof playstyle === 'object' ? String(playstyle.id ?? '') : '';
    };

    const compareVersions = (left, right) => {
        const leftParts = String(left).replace(/[^0-9.]/g, '').split('.').map(Number);
        const rightParts = String(right).replace(/[^0-9.]/g, '').split('.').map(Number);
        const length = Math.max(leftParts.length, rightParts.length);

        for (let index = 0; index < length; index += 1) {
            const leftValue = leftParts[index] ?? 0;
            const rightValue = rightParts[index] ?? 0;

            if (leftValue !== rightValue) {
                return leftValue - rightValue;
            }
        }

        return 0;
    };

    const majorVersion = (version) => {
        const match = String(version).match(/^(\d+\.\d+)/);

        return match ? match[1] : '';
    };

    const latestMajorVersion = (entries) => {
        const versions = [...new Set(entries.map((server) => String(server.gameVersion ?? '')).filter(Boolean))];

        if (versions.length === 0) {
            return '';
        }

        versions.sort((left, right) => compareVersions(right, left));

        return majorVersion(versions[0]);
    };

    const latestMajorShare = (entries) => {
        if (entries.length === 0) {
            return 0;
        }

        const latestMajor = latestMajorVersion(entries);

        if (!latestMajor) {
            return 0;
        }

        const matching = entries.filter((server) => majorVersion(server.gameVersion ?? '') === latestMajor).length;

        return Math.round((matching / entries.length) * 100);
    };

    const stripHtml = (value) => String(value).replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();

    const isToggleActive = (button) => button?.getAttribute('aria-pressed') === 'true';

    const filters = () => ({
        search: searchInput?.value.trim() ?? '',
        version: versionSelect?.value ?? '',
        playstyle: playstyleSelect?.value ?? '',
        hideEmpty: isToggleActive(hideEmptyToggle),
        hasMods: isToggleActive(modsOnlyToggle) ? true : null,
        hasPassword: isToggleActive(noPasswordToggle) ? false : null,
        whitelisted: isToggleActive(notWhitelistedToggle) ? false : null,
        sort: sortSelect?.value ?? 'players',
    });

    const matches = (server, currentFilters) => {
        if (currentFilters.hideEmpty && players(server) < 1) {
            return false;
        }

        if (currentFilters.version && String(server.gameVersion ?? '') !== currentFilters.version) {
            return false;
        }

        if (currentFilters.playstyle && playstyleId(server) !== currentFilters.playstyle) {
            return false;
        }

        if (currentFilters.hasMods === true && !hasMods(server)) {
            return false;
        }

        if (currentFilters.hasPassword === false && Boolean(server.hasPassword)) {
            return false;
        }

        if (currentFilters.whitelisted === false && Boolean(server.whitelisted)) {
            return false;
        }

        if (currentFilters.search) {
            const haystack = [
                server.serverName,
                server.serverIP,
                stripHtml(server.gameDescription ?? ''),
            ].join(' ').toLowerCase();

            if (!haystack.includes(currentFilters.search.toLowerCase())) {
                return false;
            }
        }

        return true;
    };

    const sortServers = (entries, sort) => {
        const sorted = [...entries];

        sorted.sort((left, right) => {
            if (sort === 'name') {
                return String(left.serverName ?? '').localeCompare(String(right.serverName ?? ''), undefined, {
                    sensitivity: 'base',
                });
            }

            if (sort === 'version') {
                return compareVersions(String(right.gameVersion ?? ''), String(left.gameVersion ?? ''));
            }

            return players(right) - players(left);
        });

        return sorted;
    };

    const escapeHtml = (value) => String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');

    const isSafeHref = (href) => /^https?:\/\//i.test(href);

    const sanitizeDescription = (input) => {
        const raw = String(input ?? '').trim();

        if (raw === '') {
            return '';
        }

        if (!/[<>]/.test(raw)) {
            return escapeHtml(raw);
        }

        const allowedTags = new Set(['A', 'BR', 'STRONG', 'B', 'EM', 'I', 'SPAN', 'FONT']);
        const parser = new DOMParser();
        const doc = parser.parseFromString(`<div>${raw}</div>`, 'text/html');
        const root = doc.body.firstElementChild;

        if (!root) {
            return escapeHtml(raw);
        }

        const cleanNode = (node) => {
            [...node.childNodes].forEach((child) => {
                if (child.nodeType === Node.TEXT_NODE) {
                    return;
                }

                if (child.nodeType !== Node.ELEMENT_NODE) {
                    child.remove();

                    return;
                }

                const element = child;
                const tag = element.tagName;

                if (!allowedTags.has(tag)) {
                    while (element.firstChild) {
                        node.insertBefore(element.firstChild, element);
                    }

                    element.remove();

                    return;
                }

                [...element.attributes].forEach((attribute) => {
                    if (tag === 'A' && attribute.name === 'href' && isSafeHref(attribute.value)) {
                        return;
                    }

                    if (tag === 'FONT' && attribute.name === 'color') {
                        return;
                    }

                    element.removeAttribute(attribute.name);
                });

                if (tag === 'A') {
                    if (!isSafeHref(element.getAttribute('href') ?? '')) {
                        const text = doc.createTextNode(element.textContent ?? '');
                        element.replaceWith(text);

                        return;
                    }

                    element.setAttribute('rel', 'noopener noreferrer');
                    element.setAttribute('target', '_blank');
                }

                cleanNode(element);
            });
        };

        cleanNode(root);

        return root.innerHTML;
    };

    const renderBadges = (server) => {
        const badges = [];

        if (server.hasPassword) {
            badges.push('<span class="servers-badge">Password</span>');
        }

        if (server.whitelisted) {
            badges.push('<span class="servers-badge">Whitelist</span>');
        }

        if (hasMods(server)) {
            badges.push('<span class="servers-badge">Modded</span>');
        }

        return badges.length > 0 ? `<div class="servers-row__badges">${badges.join('')}</div>` : '';
    };

    const renderDescription = (server) => {
        const description = sanitizeDescription(server.gameDescription ?? '');

        if (!description) {
            return '';
        }

        return `<div class="servers-row__description">${description}</div>`;
    };

    const renderCard = (server) => {
        const playstyle = playstyleId(server);

        return `
            <article class="servers-row servers-row--card">
                <div class="servers-row__head">
                    <h2 class="servers-row__title">${escapeHtml(server.serverName ?? 'Unnamed server')}</h2>
                    <p class="servers-row__players">
                        <strong>${players(server)}</strong>
                        <span>/ ${maxPlayers(server) || '?'}</span>
                    </p>
                </div>
                <div class="servers-row__meta">
                    <span>${escapeHtml(server.serverIP ?? '')}</span>
                    <span>${escapeHtml(server.gameVersion ?? 'Unknown version')}</span>
                    ${playstyle ? `<span>${escapeHtml(playstyle)}</span>` : ''}
                </div>
                ${renderBadges(server)}
                ${renderDescription(server)}
            </article>
        `;
    };

    const renderListRow = (server) => {
        const playstyle = playstyleId(server);

        return `
            <article class="servers-row servers-row--list">
                <div class="servers-row__list-main">
                    <h2 class="servers-row__title">${escapeHtml(server.serverName ?? 'Unnamed server')}</h2>
                    <p class="servers-row__players">
                        <strong>${players(server)}</strong>
                        <span>/ ${maxPlayers(server) || '?'}</span>
                    </p>
                </div>
                <div class="servers-row__list-meta">
                    <span>${escapeHtml(server.serverIP ?? '')}</span>
                    <span>${escapeHtml(server.gameVersion ?? 'Unknown version')}</span>
                    ${playstyle ? `<span>${escapeHtml(playstyle)}</span>` : ''}
                </div>
                ${renderBadges(server)}
                ${renderDescription(server)}
            </article>
        `;
    };

    const populateFilterOptions = () => {
        if (versionSelect) {
            const selected = versionSelect.value;
            versionSelect.innerHTML = '<option value="">All versions</option>';
            [...new Set(servers.map((server) => String(server.gameVersion ?? '')).filter(Boolean))]
                .sort((left, right) => compareVersions(right, left))
                .forEach((version) => {
                    const option = document.createElement('option');
                    option.value = version;
                    option.textContent = version;
                    versionSelect.append(option);
                });
            versionSelect.value = selected;
        }

        if (playstyleSelect) {
            const selected = playstyleSelect.value;
            playstyleSelect.innerHTML = '<option value="">All playstyles</option>';
            [...new Set(servers.map(playstyleId).filter(Boolean))]
                .sort((left, right) => left.localeCompare(right, undefined, { sensitivity: 'base' }))
                .forEach((value) => {
                    const option = document.createElement('option');
                    option.value = value;
                    option.textContent = value;
                    playstyleSelect.append(option);
                });
            playstyleSelect.value = selected;
        }
    };

    const updateStats = (summaryMeta = null) => {
        if (!stats) {
            return;
        }

        stats.hidden = false;

        const totalPlayers = summaryMeta?.total_players
            ?? servers.reduce((total, server) => total + players(server), 0);
        const serverCount = summaryMeta?.server_count ?? servers.length;
        const share = summaryMeta?.latest_major_share ?? latestMajorShare(servers);
        const latestMajor = summaryMeta?.latest_major_version ?? latestMajorVersion(servers);

        stats.querySelector('[data-stat-players]').textContent = Number(totalPlayers).toLocaleString();
        stats.querySelector('[data-stat-servers]').textContent = Number(serverCount).toLocaleString();
        stats.querySelector('[data-stat-version]').textContent = `${share ?? 0}%`;

        const versionLabel = stats.querySelector('[data-stat-version-label]');

        if (versionLabel) {
            versionLabel.textContent = latestMajor
                ? `On v${latestMajor}`
                : 'On latest major version';
        }
    };

    const updatePagination = (totalItems) => {
        const totalPages = Math.max(1, Math.ceil(totalItems / PAGE_SIZE));

        if (currentPage > totalPages) {
            currentPage = totalPages;
        }

        if (pagination) {
            pagination.hidden = totalPages <= 1;

            if (pageStatus) {
                pageStatus.textContent = `Page ${currentPage} of ${totalPages}`;
            }

            if (pagePrev) {
                pagePrev.disabled = currentPage <= 1;
            }

            if (pageNext) {
                pageNext.disabled = currentPage >= totalPages;
            }
        }
    };

    const render = () => {
        const currentFilters = filters();
        filteredServers = sortServers(
            servers.filter((server) => matches(server, currentFilters)),
            currentFilters.sort,
        );

        updateStats(listSummary);
        updatePagination(filteredServers.length);

        if (results) {
            results.hidden = false;
            const start = filteredServers.length === 0 ? 0 : ((currentPage - 1) * PAGE_SIZE) + 1;
            const end = Math.min(currentPage * PAGE_SIZE, filteredServers.length);
            results.textContent = filteredServers.length === 0
                ? 'No servers match the current filters.'
                : `Showing ${start.toLocaleString()}-${end.toLocaleString()} of ${filteredServers.length.toLocaleString()} servers`;
        }

        if (filteredServers.length === 0) {
            list.innerHTML = '<p class="servers-empty">No servers match the current filters.</p>';
            list.className = `servers-list servers-list--${viewMode}`;

            return;
        }

        const pageItems = filteredServers.slice((currentPage - 1) * PAGE_SIZE, currentPage * PAGE_SIZE);
        const renderItem = viewMode === 'list' ? renderListRow : renderCard;

        list.className = `servers-list servers-list--${viewMode}`;
        list.innerHTML = pageItems.map(renderItem).join('');
    };

    const scheduleRender = () => {
        currentPage = 1;
        render();
    };

    const scheduleSearchRender = () => {
        window.clearTimeout(searchTimer);
        searchTimer = window.setTimeout(scheduleRender, SEARCH_DEBOUNCE_MS);
    };

    const bindControls = () => {
        [versionSelect, playstyleSelect, sortSelect].forEach((control) => {
            control?.addEventListener('change', scheduleRender);
        });

        searchInput?.addEventListener('input', scheduleSearchRender);

        [hideEmptyToggle, modsOnlyToggle, noPasswordToggle, notWhitelistedToggle].forEach((button) => {
            button?.addEventListener('click', () => {
                const active = button.getAttribute('aria-pressed') !== 'true';
                button.setAttribute('aria-pressed', active ? 'true' : 'false');
                button.classList.toggle('is-active', active);
                scheduleRender();
            });
        });

        viewButtons.forEach((button) => {
            button.addEventListener('click', () => {
                viewMode = button.dataset.serversView === 'list' ? 'list' : 'card';

                viewButtons.forEach((entry) => {
                    const active = entry === button;
                    entry.classList.toggle('is-active', active);
                    entry.setAttribute('aria-pressed', active ? 'true' : 'false');
                });

                render();
            });
        });

        pagePrev?.addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage -= 1;
                render();
                list.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });

        pageNext?.addEventListener('click', () => {
            const totalPages = Math.ceil(filteredServers.length / PAGE_SIZE);

            if (currentPage < totalPages) {
                currentPage += 1;
                render();
                list.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    };

    const load = async () => {
        try {
            const response = await fetch(apiUrl, {
                headers: {
                    Accept: 'application/json',
                },
            });

            if (!response.ok) {
                throw new Error(`Server list request failed with HTTP ${response.status}`);
            }

            const payload = await response.json();

            if (!Array.isArray(payload.data)) {
                throw new Error('Server list response did not include a data array.');
            }

            servers = payload.data;
            listSummary = payload.meta ?? null;
            populateFilterOptions();
            bindControls();
            render();

            const cacheHeader = response.headers.get('X-Cache');

            if (status && cacheHeader && cacheHeader !== 'HIT') {
                status.hidden = false;
                status.textContent = cacheHeader === 'STALE' || cacheHeader === 'DISK'
                    ? 'Showing cached server list while upstream refresh is unavailable.'
                    : 'Server list loaded from SmelterWorks cache.';
            }
        } catch (error) {
            list.innerHTML = '<p class="servers-empty">Could not load the server list right now.</p>';

            if (status) {
                status.hidden = false;
                status.textContent = 'The cached server list is unavailable. Try again in a minute.';
            }
        }
    };

    load();
}
