import { readFileSync, readdirSync, writeFileSync } from 'node:fs';
import { join } from 'node:path';

const assetsDir = join(process.cwd(), 'public/build/assets');
const fontCssPattern = /^fonts-.*\.css$/;

for (const file of readdirSync(assetsDir)) {
    if (!fontCssPattern.test(file)) {
        continue;
    }

    const path = join(assetsDir, file);
    const css = readFileSync(path, 'utf8');
    const stripped = css.replace(/@font-face\s*\{[^}]*format\("woff"\);[^}]*\}\s*/g, '');

    writeFileSync(path, stripped);
}
