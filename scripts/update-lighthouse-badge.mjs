import { readFileSync, readdirSync, writeFileSync, mkdirSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const lhciDir = join(root, '.lighthouseci');
const outputDir = join(root, '.github/badges');
const outputPath = join(outputDir, 'lighthouse.json');
const theme = JSON.parse(readFileSync(join(root, 'docs/badges.json'), 'utf8')).theme;

const reports = readdirSync(lhciDir).filter((file) => file.endsWith('.report.json'));

if (reports.length === 0) {
    throw new Error('No Lighthouse report JSON files found in .lighthouseci');
}

const minimums = {
    performance: 1,
    accessibility: 1,
    'best-practices': 1,
    seo: 1,
};

for (const file of reports) {
    const report = JSON.parse(readFileSync(join(lhciDir, file), 'utf8'));

    for (const [category, data] of Object.entries(report.categories)) {
        minimums[category] = Math.min(minimums[category], data.score ?? 0);
    }
}

const score = Math.round(Math.min(...Object.values(minimums)) * 100);
const color = score >= 100 ? theme.accent : score >= 90 ? theme.success : 'c45c26';

mkdirSync(outputDir, { recursive: true });
writeFileSync(
    outputPath,
    `${JSON.stringify(
        {
            schemaVersion: 1,
            label: 'Lighthouse',
            message: String(score),
            color,
        },
        null,
        4,
    )}\n`,
);

console.log(`Wrote Lighthouse badge score ${score} to ${outputPath}`);
