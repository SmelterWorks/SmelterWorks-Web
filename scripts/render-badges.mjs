import { readFileSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const configPath = join(root, 'docs/badges.json');
const readmePath = join(root, 'README.md');
const beginMarker = '<!-- BADGES:BEGIN -->';
const endMarker = '<!-- BADGES:END -->';

const config = JSON.parse(readFileSync(configPath, 'utf8'));
const theme = config.theme;

function themeColor(name) {
    return theme[name] ?? name;
}

function workflowUrl(workflow) {
    return `https://github.com/${config.repository}/actions/workflows/${workflow}`;
}

function workflowBadge(badge) {
    const params = new URLSearchParams({
        branch: config.branch,
        label: badge.label,
        labelColor: theme.labelColor,
        color: themeColor(badge.color ?? 'accent'),
    });

    if (badge.logo) {
        params.set('logo', badge.logo);
    }

    return `https://img.shields.io/github/actions/workflow/status/${config.repository}/${badge.workflow}?${params.toString()}`;
}

function staticBadge(badge) {
    const params = new URLSearchParams({
        style: 'flat-square',
        labelColor: theme.labelColor,
        label: badge.label,
        message: badge.message,
        color: themeColor(badge.color ?? 'accent'),
    });

    if (badge.logo) {
        params.set('logo', badge.logo);
        params.set('logoColor', 'white');
    }

    return `https://img.shields.io/badge/?${params.toString()}`;
}

function endpointBadge(badge) {
    const endpoint = `https://raw.githubusercontent.com/${config.repository}/${config.branch}/${badge.path}`;

    return `https://img.shields.io/endpoint?url=${encodeURIComponent(endpoint)}`;
}

function badgeImageUrl(badge) {
    switch (badge.type) {
        case 'workflow':
            return workflowBadge(badge);
        case 'static':
            return staticBadge(badge);
        case 'endpoint':
            return endpointBadge(badge);
        default:
            throw new Error(`Unknown badge type: ${badge.type}`);
    }
}

function badgeLink(badge) {
    if (badge.link) {
        return badge.link.startsWith('http') ? badge.link : `https://github.com/${config.repository}/blob/${config.branch}/${badge.link}`;
    }

    if (badge.workflow) {
        return workflowUrl(badge.workflow);
    }

    return `https://github.com/${config.repository}`;
}

export function renderBadgesMarkdown(badgeConfig = config) {
    return badgeConfig.badges
        .map((badge) => {
            const alt = badge.label;
            const image = badgeImageUrl(badge);
            const link = badgeLink(badge);

            return `[![${alt}](${image})](${link})`;
        })
        .join(' ');
}

function updateReadme(markdown) {
    const readme = readFileSync(readmePath, 'utf8');
    const pattern = new RegExp(`${beginMarker}[\\s\\S]*?${endMarker}`);

    if (!pattern.test(readme)) {
        throw new Error(`README.md is missing ${beginMarker} / ${endMarker} markers`);
    }

    const block = `${beginMarker}\n${markdown}\n${endMarker}`;
    writeFileSync(readmePath, readme.replace(pattern, block));
}

function checkReadme(markdown) {
    const readme = readFileSync(readmePath, 'utf8');
    const expected = `${beginMarker}\n${markdown}\n${endMarker}`;

    if (!readme.includes(expected)) {
        throw new Error('README badges are out of date. Run: pnpm run badges');
    }
}

const markdown = renderBadgesMarkdown();
const mode = process.argv[2] ?? 'print';

if (mode === '--write') {
    updateReadme(markdown);
    console.log('Updated README badges.');
} else if (mode === '--check') {
    checkReadme(markdown);
    console.log('README badges are up to date.');
} else {
    console.log(markdown);
}
