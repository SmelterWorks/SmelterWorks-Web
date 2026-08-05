import { execFileSync } from 'node:child_process';
import { existsSync, mkdirSync, statSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const brandDir = join(root, 'public', 'images', 'brand');

const MARK_SIZES = [64, 128, 256, 512];
const FLUXER_SIZES = [16, 32, 180];

mkdirSync(brandDir, { recursive: true });

function magickBinary() {
    for (const binary of ['magick', 'convert']) {
        try {
            execFileSync(binary, ['-version'], { stdio: 'ignore' });
            return binary;
        } catch {
            continue;
        }
    }

    throw new Error('ImageMagick is required. Install magick or convert and rerun brand:sync.');
}

function isStale(outputPath, sourcePath) {
    if (!existsSync(outputPath)) {
        return true;
    }

    return statSync(sourcePath).mtimeMs > statSync(outputPath).mtimeMs;
}

function writeRaster({ sourcePath, outputPath, size }) {
    if (!isStale(outputPath, sourcePath)) {
        return;
    }

    const binary = magickBinary();
    const args =
        binary === 'magick'
            ? [sourcePath, '-resize', `${size}x${size}`, outputPath]
            : [sourcePath, '-resize', `${size}x${size}`, outputPath];

    execFileSync(binary, args, { stdio: 'inherit' });
}

function syncSolidMarks() {
    const sourcePath = join(brandDir, 'SmelterWorks.png');

    if (!existsSync(sourcePath)) {
        throw new Error('Missing master mark: public/images/brand/SmelterWorks.png');
    }

    for (const size of MARK_SIZES) {
        writeRaster({
            sourcePath,
            outputPath: join(brandDir, `SmelterWorks-${size}.png`),
            size,
        });
        writeRaster({
            sourcePath,
            outputPath: join(brandDir, `SmelterWorks-${size}.webp`),
            size,
        });
    }
}

function syncTransparentMarks() {
    const sourcePath = join(brandDir, 'SmelterWorks-transparent.png');

    if (!existsSync(sourcePath)) {
        throw new Error('Missing transparent master: public/images/brand/SmelterWorks-transparent.png');
    }

    for (const size of MARK_SIZES) {
        writeRaster({
            sourcePath,
            outputPath: join(brandDir, `SmelterWorks-transparent-${size}.png`),
            size,
        });
        writeRaster({
            sourcePath,
            outputPath: join(brandDir, `SmelterWorks-transparent-${size}.webp`),
            size,
        });
    }
}

function syncFluxerIcons() {
    const sourcePath = join(brandDir, 'fluxer.png');

    if (!existsSync(sourcePath)) {
        console.warn('Skipping Fluxer icons: fluxer.png not found');
        return;
    }

    for (const size of FLUXER_SIZES) {
        const filename = size === 180 ? 'fluxer.png' : `fluxer-${size}.png`;

        writeRaster({
            sourcePath,
            outputPath: join(brandDir, filename),
            size,
        });
    }
}

syncSolidMarks();
syncTransparentMarks();
syncFluxerIcons();

console.log('Synced brand raster assets into public/images/brand');
