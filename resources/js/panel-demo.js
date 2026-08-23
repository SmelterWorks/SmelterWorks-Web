const demos = document.querySelectorAll('[data-panel-demo]');

demos.forEach((root) => {
    const config = readConfig(root);
    const modsApi = root.dataset.modsApi ?? '/panel/demo/mods';
    const serverState = new Map(
        (config.servers ?? []).map((server) => [
            server.id,
            {
                ...server,
                status: server.status ?? 'stopped',
            },
        ]),
    );

    const state = {
        view: 'dashboard',
        serverId: config.servers?.[0]?.id ?? null,
        installed: new Map(
            (config.installed_mods ?? []).map((mod) => [
                mod.modid,
                {
                    modid: mod.modid,
                    name: mod.name,
                    author: mod.author,
                    version: mod.version,
                    enabled: mod.enabled !== false,
                    update: mod.update ?? null,
                    logo: mod.logo ?? null,
                    downloads: mod.downloads ?? 0,
                    tags: mod.tags ?? [],
                    summary: mod.summary ?? '',
                },
            ]),
        ),
        settings: new Map((config.settings ?? []).map((item) => [item.id, item.enabled !== false])),
        modTab: 'browse',
        searchResults: [],
        searching: false,
        lastQuery: '',
        orderBy: 'downloads',
    };

    const overlay = root.querySelector('[data-panel-overlay]');
    const sidebar = root.querySelector('[data-panel-sidebar]');
    const toast = root.querySelector('[data-panel-toast]');
    const tabs = root.querySelectorAll('[data-panel-tab]');
    const views = root.querySelectorAll('[data-panel-view]');
    const serverLinks = root.querySelectorAll('[data-panel-server]');
    const modBrowse = root.querySelector('[data-panel-mod-browse]');
    const modInstalled = root.querySelector('[data-panel-mod-installed]');
    const modStatus = root.querySelector('[data-panel-mod-status]');
    const sortSelect = root.querySelector('[data-panel-mod-sort]');
    let toastTimer = null;

    const setSidebarOpen = (open) => {
        sidebar?.classList.toggle('is-open', open);
        overlay?.toggleAttribute('hidden', !open);
        document.body.classList.toggle('panel-app--menu-open', open);
    };

    const showToast = (message) => {
        if (!toast) {
            return;
        }

        toast.textContent = message;
        toast.hidden = false;
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => {
            toast.hidden = true;
        }, 2400);
    };

    root.querySelector('[data-panel-menu-open]')?.addEventListener('click', () => setSidebarOpen(true));
    overlay?.addEventListener('click', () => setSidebarOpen(false));

    const activateTab = (viewId) => {
        tabs.forEach((tab) => {
            const active = tab.dataset.panelTab === viewId;
            tab.classList.toggle('is-active', active);

            if (active) {
                tab.setAttribute('aria-current', 'page');
            } else {
                tab.removeAttribute('aria-current');
            }
        });
    };

    const showMainView = (viewId) => {
        state.view = viewId;
        views.forEach((view) => {
            const id = view.dataset.panelView;
            const isServerPanel = view.hasAttribute('data-panel-server-panel');

            if (isServerPanel) {
                view.hidden = viewId !== 'server' || view.dataset.panelServerPanel !== state.serverId;
                view.classList.toggle('is-active', !view.hidden);

                return;
            }

            view.hidden = id !== viewId;
            view.classList.toggle('is-active', id === viewId);
        });

        const tabId = viewId === 'server' ? 'server' : viewId;
        activateTab(tabId);
        setSidebarOpen(false);

        if (viewId === 'mods') {
            renderInstalledMods();

            if (state.searchResults.length === 0 && !state.searching) {
                loadMods('');
            }
        }
    };

    const showServer = (serverId) => {
        state.serverId = serverId;
        serverLinks.forEach((link) => {
            link.classList.toggle('is-active', link.dataset.panelServer === serverId);
        });
        showMainView('server');
    };

    const renameServer = (serverId, nextName) => {
        const server = serverState.get(serverId);

        if (!server || nextName === '') {
            return;
        }

        server.name = nextName;
        serverState.set(serverId, server);

        root.querySelectorAll(`[data-panel-server-name="${CSS.escape(serverId)}"]`).forEach((node) => {
            node.textContent = nextName;
        });

        const panel = root.querySelector(`[data-panel-server-panel="${CSS.escape(serverId)}"]`);
        const title = panel?.querySelector('[data-panel-server-title]');

        if (title) {
            title.textContent = nextName;
        }

        showToast(`Renamed to ${nextName}`);
    };

    const setServerStatus = (serverId, status, message) => {
        const server = serverState.get(serverId);

        if (!server) {
            return;
        }

        server.status = status;
        serverState.set(serverId, server);

        root.querySelectorAll(`[data-panel-status-badge="${CSS.escape(serverId)}"]`).forEach((badge) => {
            badge.textContent = status === 'running' ? 'Running' : 'Stopped';
            badge.classList.toggle('panel-demo__status--running', status === 'running');
            badge.classList.toggle('panel-demo__status--stopped', status === 'stopped');
        });

        const link = root.querySelector(`[data-panel-server="${CSS.escape(serverId)}"]`);

        if (link) {
            link.dataset.panelServerStatus = status;
            const dot = link.querySelector('.panel-demo__server-dot');
            dot?.classList.toggle('panel-demo__server-dot--running', status === 'running');
            dot?.classList.toggle('panel-demo__server-dot--stopped', status === 'stopped');
        }

        const panel = root.querySelector(`[data-panel-server-panel="${CSS.escape(serverId)}"]`);

        if (panel) {
            const startBtn = panel.querySelector('[data-panel-power="start"]');
            const stopBtn = panel.querySelector('[data-panel-power="stop"]');

            if (startBtn) {
                startBtn.disabled = status === 'running';
            }

            if (stopBtn) {
                stopBtn.disabled = status === 'stopped';
            }

            const players = panel.querySelector('[data-panel-metric-players]');

            if (players && typeof server.players === 'string') {
                const max = server.players.split('/')[1]?.trim() ?? '0';
                players.textContent = status === 'running' ? server.players : `0 / ${max}`;
            }

            const consoleEl = panel.querySelector('[data-panel-console]');

            if (consoleEl && message) {
                appendConsole(consoleEl, 'warn', message);
            }
        }
    };

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            const viewId = tab.dataset.panelTab ?? 'dashboard';

            if (viewId === 'server' && state.serverId) {
                showServer(state.serverId);

                return;
            }

            showMainView(viewId);
        });
    });

    root.querySelectorAll('[data-panel-tab-trigger]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            showMainView(trigger.dataset.panelTabTrigger ?? 'dashboard');
        });
    });

    root.querySelectorAll('[data-panel-server-open]').forEach((button) => {
        button.addEventListener('click', () => {
            showServer(button.dataset.panelServerOpen ?? state.serverId);
        });
    });

    serverLinks.forEach((link) => {
        link.addEventListener('click', () => {
            showServer(link.dataset.panelServer ?? state.serverId);
        });
    });

    root.querySelectorAll('[data-panel-server-panel]').forEach((panel) => {
        const serverId = panel.dataset.panelServerPanel;

        panel.querySelectorAll('[data-panel-subtab]').forEach((button) => {
            button.addEventListener('click', () => {
                const name = button.dataset.panelSubtab;
                panel.querySelectorAll('[data-panel-subtab]').forEach((entry) => {
                    entry.classList.toggle('is-active', entry === button);
                });
                panel.querySelectorAll('[data-panel-subview]').forEach((subview) => {
                    const active = subview.dataset.panelSubview === name;
                    subview.hidden = !active;
                    subview.classList.toggle('is-active', active);
                });
            });
        });

        panel.querySelector('[data-panel-rename]')?.addEventListener('click', () => {
            const current = serverState.get(serverId)?.name ?? '';
            const next = window.prompt('Rename server', current);

            if (next === null) {
                return;
            }

            renameServer(serverId, next.trim());
        });

        panel.querySelector('[data-panel-console-form]')?.addEventListener('submit', (event) => {
            event.preventDefault();
            const input = panel.querySelector('[data-panel-console-form] input');
            const consoleEl = panel.querySelector('[data-panel-console]');
            const value = input?.value.trim();

            if (!value || !consoleEl) {
                return;
            }

            appendConsole(consoleEl, 'cmd', `> ${value}`);

            if (value === '/list' || value === 'list') {
                appendConsole(consoleEl, 'info', '[Server] Players online: simulated roster');
            } else if (value === '/stop' || value === 'stop') {
                setServerStatus(serverId, 'stopped', '[Panel] stop via console (simulated)');
            } else if (value === '/start' || value === 'start') {
                setServerStatus(serverId, 'running', '[Panel] start via console (simulated)');
            } else {
                appendConsole(consoleEl, 'info', `[Demo] Command not sent in preview: ${value}`);
            }

            input.value = '';
        });

        panel.querySelectorAll('[data-panel-power]').forEach((button) => {
            button.addEventListener('click', () => {
                const action = button.dataset.panelPower;
                const confirmPower = state.settings.get('confirm_power');

                if (confirmPower && (action === 'stop' || action === 'restart')) {
                    const ok = window.confirm(`${action === 'stop' ? 'Stop' : 'Restart'} this server?`);

                    if (!ok) {
                        return;
                    }
                }

                if (action === 'start') {
                    setServerStatus(serverId, 'running', '[Panel] start requested (simulated)');
                    showToast('Server started');
                } else if (action === 'stop') {
                    setServerStatus(serverId, 'stopped', '[Panel] stop requested (simulated)');
                    showToast('Server stopped');
                } else if (action === 'restart') {
                    setServerStatus(serverId, 'stopped', '[Panel] restart requested (simulated)');
                    window.setTimeout(() => {
                        setServerStatus(serverId, 'running', '[Panel] server back online (simulated)');
                        showToast('Server restarted');
                    }, 700);
                }
            });
        });

        panel.querySelector('[data-panel-backup-now]')?.addEventListener('click', () => {
            const list = panel.querySelector('[data-panel-backup-list]');

            if (!list) {
                return;
            }

            const row = document.createElement('li');
            row.className = 'panel-demo__backup-row';
            row.innerHTML =
                '<div><p class="panel-demo__backup-label">Manual snapshot</p><p class="panel-demo__muted">Just now · starting</p></div><div class="panel-demo__row-actions"><span class="panel-demo__pill panel-demo__pill--running">Running</span></div>';
            list.prepend(row);
            showToast('Backup started');

            window.setTimeout(() => {
                row.innerHTML =
                    '<div><p class="panel-demo__backup-label">Manual snapshot</p><p class="panel-demo__muted">Just now · 1.8 GB</p></div><div class="panel-demo__row-actions"><button type="button" class="panel-demo__ghost-btn" data-panel-backup-restore="Manual snapshot">Restore</button><span class="panel-demo__pill">Complete</span></div>';
                showToast('Backup complete');
            }, 1200);
        });

        panel.querySelector('[data-panel-backup-list]')?.addEventListener('click', (event) => {
            const button = event.target.closest('[data-panel-backup-restore]');

            if (!button) {
                return;
            }

            const label = button.dataset.panelBackupRestore ?? 'backup';
            const consoleEl = panel.querySelector('[data-panel-console]');

            if (consoleEl) {
                appendConsole(consoleEl, 'warn', `[Backup] Restore queued for ${label} (simulated)`);
            }

            showToast(`Restore queued: ${label}`);
        });

        const editor = panel.querySelector('[data-panel-file-editor]');
        const textarea = panel.querySelector('[data-panel-file-textarea]');
        const fileTitle = panel.querySelector('[data-panel-file-title]');

        panel.querySelectorAll('[data-panel-file-open]').forEach((button) => {
            button.addEventListener('click', () => {
                const path = button.dataset.panelFileOpen ?? '';
                const kind = button.dataset.panelFileKind ?? 'file';
                const preview = button.dataset.panelFilePreview ?? '';

                if (!editor || !textarea || !fileTitle) {
                    return;
                }

                if (kind === 'folder') {
                    showToast(`Opened folder ${path}`);

                    return;
                }

                fileTitle.textContent = path;
                textarea.value =
                    preview ||
                    `// Demo preview for ${path}\n// Edits stay in this browser session only.\n`;
                editor.hidden = false;
            });
        });

        panel.querySelector('[data-panel-file-close]')?.addEventListener('click', () => {
            if (editor) {
                editor.hidden = true;
            }
        });

        panel.querySelector('[data-panel-file-save]')?.addEventListener('click', () => {
            showToast('File saved (simulated)');
        });
    });

    const loadMods = async (query) => {
        state.searching = true;
        state.lastQuery = query;
        setModStatus(query ? `Searching ModDB for “${query}”…` : 'Loading popular mods from VS ModDB…');
        renderBrowseMods([]);

        try {
            const params = new URLSearchParams({
                orderby: state.orderBy,
            });

            if (query) {
                params.set('q', query);
            }

            const response = await fetch(`${modsApi}?${params.toString()}`, {
                headers: { Accept: 'application/json' },
            });
            const payload = await response.json();
            state.searchResults = Array.isArray(payload.mods) ? payload.mods : [];
            setModStatus(
                state.searchResults.length > 0
                    ? `${state.searchResults.length} result(s) from VS ModDB`
                    : 'No mods matched that search.',
            );
            renderBrowseMods(state.searchResults);
        } catch {
            setModStatus('Could not reach ModDB. Try again in a moment.');
        } finally {
            state.searching = false;
        }
    };

    const modSearchForm = root.querySelector('[data-panel-mod-search]');

    modSearchForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const query = new FormData(modSearchForm).get('q')?.toString().trim() ?? '';
        await loadMods(query);
    });

    sortSelect?.addEventListener('change', async () => {
        state.orderBy = sortSelect.value || 'downloads';
        await loadMods(state.lastQuery);
    });

    root.querySelectorAll('[data-panel-mod-tab]').forEach((button) => {
        button.addEventListener('click', () => {
            state.modTab = button.dataset.panelModTab ?? 'browse';
            root.querySelectorAll('[data-panel-mod-tab]').forEach((entry) => {
                entry.classList.toggle('is-active', entry === button);
            });
            modBrowse?.toggleAttribute('hidden', state.modTab !== 'browse');
            modInstalled?.toggleAttribute('hidden', state.modTab !== 'installed');

            if (state.modTab === 'installed') {
                renderInstalledMods();
            }
        });
    });

    root.querySelector('[data-panel-create-token]')?.addEventListener('click', () => {
        const nameInput = root.querySelector('[data-panel-daemon-name]');
        const card = root.querySelector('[data-panel-token-card]');
        const output = root.querySelector('[data-panel-token-output]');
        const label = nameInput?.value.trim() || 'my-host';
        const token = `sw_demo_${randomToken()}`;

        if (card && output) {
            output.textContent = token;
            card.hidden = false;
        }

        showToast(`Token created for ${label}`);
    });

    root.querySelector('[data-panel-copy-token]')?.addEventListener('click', async () => {
        const token = root.querySelector('[data-panel-token-output]')?.textContent ?? '';

        if (token === '') {
            return;
        }

        try {
            await navigator.clipboard.writeText(token);
            showToast('Token copied to clipboard');
        } catch {
            showToast('Copy failed. Select the token manually.');
        }
    });

    root.querySelector('[data-panel-profile-form]')?.addEventListener('submit', (event) => {
        event.preventDefault();
        const form = event.currentTarget;
        const data = new FormData(form);
        const name = data.get('name')?.toString().trim() || 'Demo user';
        const organization = data.get('organization')?.toString().trim() || 'Demo org';

        root.querySelectorAll('[data-panel-user-label]').forEach((node) => {
            node.textContent = name;
        });
        root.querySelectorAll('[data-panel-org-label]').forEach((node) => {
            node.textContent = organization;
        });

        showToast('Profile saved');
    });

    root.querySelectorAll('[data-panel-setting]').forEach((button) => {
        button.addEventListener('click', () => {
            const id = button.dataset.panelSetting;
            const next = !state.settings.get(id);
            state.settings.set(id, next);
            button.classList.toggle('is-on', next);
            button.setAttribute('aria-pressed', next ? 'true' : 'false');
            showToast(`${button.getAttribute('aria-label') ?? 'Setting'} ${next ? 'on' : 'off'}`);
        });
    });

    modBrowse?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-mod-action]');

        if (!button) {
            return;
        }

        handleModAction(
            button.dataset.modAction,
            button.dataset.modid,
            button.dataset.modName,
            button.dataset.modAuthor,
            button.dataset.modVersion,
            button.dataset.modLogo,
        );
    });

    modInstalled?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-mod-action]');

        if (!button) {
            return;
        }

        handleModAction(button.dataset.modAction, button.dataset.modid);
    });

    function handleModAction(action, modid, name = '', author = '', version = '', logo = '') {
        const existing = state.installed.get(modid);
        const browseHit = state.searchResults.find((mod) => mod.modid === modid);

        if (action === 'install' || action === 'update') {
            state.installed.set(modid, {
                modid,
                name: name || existing?.name || browseHit?.name || modid,
                author: author || existing?.author || browseHit?.author || 'Unknown',
                version: version || existing?.version || browseHit?.version || '1.0.0',
                enabled: true,
                update: null,
                logo: logo || existing?.logo || browseHit?.logo || null,
                downloads: browseHit?.downloads ?? existing?.downloads ?? 0,
                tags: browseHit?.tags ?? existing?.tags ?? [],
                summary: browseHit?.summary ?? existing?.summary ?? '',
            });
            showToast(`${name || modid} ${action === 'update' ? 'updated' : 'installed'}`);
        }

        if (action === 'uninstall' && existing) {
            state.installed.delete(modid);
            showToast(`${existing.name} removed`);
        }

        if (action === 'toggle' && existing) {
            existing.enabled = !existing.enabled;
            state.installed.set(modid, existing);
            showToast(`${existing.name} ${existing.enabled ? 'enabled' : 'disabled'}`);
        }

        renderBrowseMods(state.searchResults);
        renderInstalledMods();
    }

    function renderBrowseMods(mods) {
        if (!modBrowse) {
            return;
        }

        if (mods.length === 0) {
            modBrowse.innerHTML = state.searching
                ? '<p class="panel-demo__muted">Loading…</p>'
                : '<p class="panel-demo__muted">No mods to show yet.</p>';

            return;
        }

        modBrowse.innerHTML = mods.map((mod) => renderModCard(mod, false)).join('');
    }

    function renderInstalledMods() {
        if (!modInstalled) {
            return;
        }

        const mods = [...state.installed.values()];

        if (mods.length === 0) {
            modInstalled.innerHTML = '<p class="panel-demo__muted">No mods installed in this demo yet.</p>';

            return;
        }

        modInstalled.innerHTML = mods.map((mod) => renderModCard(mod, true)).join('');
    }

    function renderModCard(mod, installedView) {
        const installed = state.installed.get(mod.modid);
        const tags = Array.isArray(mod.tags) ? mod.tags.slice(0, 4) : [];
        const downloads = formatCount(mod.downloads ?? installed?.downloads ?? 0);
        const logo = mod.logo || installed?.logo;
        const summary = mod.summary || '';
        const versionLabel = mod.version ? ` · v${escapeHtml(mod.version)}` : '';
        const statusLabel = installedView ? ` · ${installed?.enabled ? 'Enabled' : 'Disabled'}` : '';

        let actions = '';

        if (installed) {
            actions = `
                <button type="button" class="panel-demo__ghost-btn" data-mod-action="toggle" data-modid="${escapeAttr(mod.modid)}">${installed.enabled ? 'Disable' : 'Enable'}</button>
                ${
                    installed.update
                        ? `<button type="button" class="panel-demo__action-btn" data-mod-action="update" data-modid="${escapeAttr(mod.modid)}" data-mod-name="${escapeAttr(mod.name)}" data-mod-author="${escapeAttr(mod.author)}" data-mod-version="${escapeAttr(installed.update)}" data-mod-logo="${escapeAttr(logo || '')}">Update to v${escapeHtml(installed.update)}</button>`
                        : ''
                }
                <button type="button" class="panel-demo__ghost-btn" data-mod-action="uninstall" data-modid="${escapeAttr(mod.modid)}">Remove</button>
            `;
        } else {
            actions = `<button type="button" class="panel-demo__action-btn" data-mod-action="install" data-modid="${escapeAttr(mod.modid)}" data-mod-name="${escapeAttr(mod.name)}" data-mod-author="${escapeAttr(mod.author)}" data-mod-version="${escapeAttr(mod.version || '1.0.0')}" data-mod-logo="${escapeAttr(logo || '')}">Install</button>`;
        }

        return `<article class="panel-demo__mod-card">
            <div class="panel-demo__mod-card-body">
                ${logo ? `<img src="${escapeAttr(logo)}" alt="" class="panel-demo__mod-logo" loading="lazy" decoding="async" referrerpolicy="no-referrer">` : '<div class="panel-demo__mod-logo panel-demo__mod-logo--empty" aria-hidden="true"></div>'}
                <div>
                    <h3 class="panel-demo__mod-name">${escapeHtml(mod.name)}</h3>
                    <p class="panel-demo__mod-meta">${escapeHtml(mod.author || 'Unknown')}${versionLabel}${statusLabel}</p>
                    ${downloads !== '0' ? `<p class="panel-demo__mod-meta">${downloads} downloads</p>` : ''}
                    ${summary ? `<p class="panel-demo__mod-summary">${escapeHtml(summary)}</p>` : ''}
                    ${
                        tags.length > 0
                            ? `<ul class="panel-demo__mod-tags">${tags.map((tag) => `<li>${escapeHtml(tag)}</li>`).join('')}</ul>`
                            : ''
                    }
                </div>
            </div>
            <div class="panel-demo__mod-actions">${actions}</div>
        </article>`;
    }

    function setModStatus(message) {
        if (modStatus) {
            modStatus.textContent = message;
        }
    }

    showMainView('dashboard');
    renderInstalledMods();
});

function readConfig(root) {
    const node = root.querySelector('[data-panel-config]');

    if (!node) {
        return {};
    }

    try {
        return JSON.parse(node.textContent ?? '{}');
    } catch {
        return {};
    }
}

function appendConsole(container, level, text) {
    const line = document.createElement('p');
    line.className = `panel-demo__console-line panel-demo__console-line--${level}`;
    line.textContent = text;
    container.append(line);
    container.scrollTop = container.scrollHeight;
}

function randomToken() {
    return Array.from({ length: 32 }, () => Math.floor(Math.random() * 16).toString(16)).join('');
}

function formatCount(value) {
    const count = Number(value) || 0;

    if (count >= 1_000_000) {
        return `${(count / 1_000_000).toFixed(1)}M`;
    }

    if (count >= 1_000) {
        return `${(count / 1_000).toFixed(1)}k`;
    }

    return String(count);
}

function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');
}

function escapeAttr(value) {
    return escapeHtml(value).replaceAll("'", '&#39;');
}
