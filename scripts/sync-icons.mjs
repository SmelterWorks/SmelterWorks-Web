import { cpSync, mkdirSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const publicIcons = join(root, 'public', 'icons');

const simpleSlugs = ['forgejo', 'kofi', 'rss'];
const brandIcons = ['windows', 'apple', 'linux'];
const flagCodes = ['US', 'DE'];
const lucideNames = [
    'archive',
    'cog',
    'file-code',
    'package',
    'users',
    'globe',
    'container',
    'badge-check',
    'log-in',
    'layers',
    'store',
    'folder-open',
    'shield-alert',
    'circle-play',
    'newspaper',
    'book-open',
    'rocket',
    'shield',
    'box',
    'type',
    'key-round',
    'mail',
    'message-circle',
    'menu',
    'x',
    'leaf',
    'upload',
];

function resetDir(path) {
    rmSync(path, { recursive: true, force: true });
    mkdirSync(path, { recursive: true });
}

function monochromeSvg(sourcePath, targetPath) {
    let svg = readFileSync(sourcePath, 'utf8');
    svg = svg.replace(/fill="#[^"]+"/g, 'fill="currentColor"');
    if (!/\bfill=/.test(svg)) {
        svg = svg.replace(/<svg\b/, '<svg fill="currentColor"');
    } else {
        svg = svg.replace(/<svg\b/, '<svg fill="currentColor"');
    }
    writeFileSync(targetPath, svg);
}

resetDir(join(publicIcons, 'simple'));
for (const slug of simpleSlugs) {
    monochromeSvg(
        join(root, 'node_modules', 'simple-icons', 'icons', `${slug}.svg`),
        join(publicIcons, 'simple', `${slug}.svg`),
    );
}

resetDir(join(publicIcons, 'brands'));
for (const name of brandIcons) {
    monochromeSvg(
        join(root, 'node_modules', '@fortawesome', 'fontawesome-free', 'svgs', 'brands', `${name}.svg`),
        join(publicIcons, 'brands', `${name}.svg`),
    );
}

resetDir(join(publicIcons, 'flags'));
for (const code of flagCodes) {
    cpSync(
        join(root, 'node_modules', 'country-flag-icons', '3x2', `${code}.svg`),
        join(publicIcons, 'flags', `${code}.svg`),
    );
}

resetDir(join(publicIcons, 'lucide'));
for (const name of lucideNames) {
    cpSync(
        join(root, 'node_modules', 'lucide-static', 'icons', `${name}.svg`),
        join(publicIcons, 'lucide', `${name}.svg`),
    );
}

console.log(
    `Synced ${simpleSlugs.length} Simple Icons, ${brandIcons.length} Font Awesome brands, ${flagCodes.length} flags, and ${lucideNames.length} Lucide icons.`,
);
