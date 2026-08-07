import { LAND_MASK_BASE64, MASK_WIDTH, MASK_HEIGHT } from './land-mask-data.js';
import { KOPPEN_MASK_BASE64, KOPPEN_WIDTH, KOPPEN_HEIGHT } from './koppen-mask-data.js';
import { terrainFromKoppenIndex } from './koppen-terrain.js';

export { KOPPEN_CLASSES } from './koppen-mask-data.js';
export { terrainFromKoppen, terrainFromKoppenIndex } from './koppen-terrain.js';

export const BLOCK_PX = 4;
export const TILE_PX = 256;

export const COLORS = {
    waterDeep: '#1c3644',
    water: '#2a5468',
    waterShallow: '#3a7088',
    ice: '#dce8ea',
    snow: '#c9d8d6',
    tundra: '#8a9a7a',
    tundraDark: '#707e64',
    grassDark: '#3a6830',
    grass: '#4d8a3c',
    grassLight: '#62a84e',
    savanna: '#a89050',
    savannaLight: '#bca35e',
    forest: '#2a5424',
    forestDark: '#1c3c18',
    rainforest: '#1f4a22',
    rainforestDark: '#153818',
    rock: '#8a7c6a',
    rockLight: '#a89680',
    rockDark: '#625848',
    rockSnow: '#b8b0a4',
    sand: '#b8a060',
    sandLight: '#c8b070',
    desert: '#c9a866',
    desertDark: '#b08f4e',
    desertRock: '#a4885e',
};

const landMaskBytes = (() => {
    const binary = atob(LAND_MASK_BASE64);
    const bytes = new Uint8Array(binary.length);

    for (let i = 0; i < binary.length; i++) {
        bytes[i] = binary.charCodeAt(i);
    }

    return bytes;
})();

const koppenMaskBytes = (() => {
    const binary = atob(KOPPEN_MASK_BASE64);
    const bytes = new Uint8Array(binary.length);

    for (let i = 0; i < binary.length; i++) {
        bytes[i] = binary.charCodeAt(i);
    }

    return bytes;
})();

export function mercatorToLngLat(x, y) {
    const lng = (x / 20037508.34) * 180;
    const lat = (180 / Math.PI) * (2 * Math.atan(Math.exp((y / 20037508.34) * Math.PI)) - Math.PI / 2);

    return { lng, lat };
}

export function hash2i(x, y) {
    let n = Math.imul(x ^ 0x9e3779b9, 0x85ebca6b) ^ Math.imul(y ^ 0xc2b2ae35, 0x7f4a7c15);
    n = Math.imul(n ^ (n >>> 16), 0x7feb352d);

    return (n ^ (n >>> 15)) >>> 0;
}

export function hash01(x, y) {
    return hash2i(x, y) / 0xffffffff;
}

export function smoothNoise(x, y) {
    const ix = Math.floor(x);
    const iy = Math.floor(y);
    const fx = x - ix;
    const fy = y - iy;
    const ux = fx * fx * (3 - 2 * fx);
    const uy = fy * fy * (3 - 2 * fy);
    const a = hash01(ix, iy);
    const b = hash01(ix + 1, iy);
    const c = hash01(ix, iy + 1);
    const d = hash01(ix + 1, iy + 1);

    return a + (b - a) * ux + (c - a) * uy + (a - b - c + d) * ux * uy;
}

export function fbm(x, y, octaves = 4) {
    let value = 0;
    let amplitude = 0.5;
    let frequency = 1;

    for (let i = 0; i < octaves; i++) {
        value += amplitude * smoothNoise(x * frequency, y * frequency);
        amplitude *= 0.5;
        frequency *= 2;
    }

    return value;
}

function maskIndex(lng, lat, width, height) {
    const col = Math.floor(((lng + 180) / 360) * width);
    const row = Math.floor(((90 - lat) / 180) * height);
    const clampedCol = Math.min(width - 1, Math.max(0, col));
    const clampedRow = Math.min(height - 1, Math.max(0, row));

    return clampedRow * width + clampedCol;
}

export function isLand(lng, lat) {
    const idx = maskIndex(lng, lat, MASK_WIDTH, MASK_HEIGHT);
    const byteIdx = idx >> 3;
    const bitIdx = 7 - (idx & 7);

    return ((landMaskBytes[byteIdx] >> bitIdx) & 1) === 1;
}

export function koppenIndexAt(lng, lat) {
    const idx = maskIndex(lng, lat, KOPPEN_WIDTH, KOPPEN_HEIGHT);

    return koppenMaskBytes[idx];
}

// Greenland's ice sheet covers roughly 80% of the island. Treat the whole
// landmass as ice/snow rather than diluting it into generic tundra from the
// 1-degree Köppen raster.
const GREENLAND_REGION = { minLng: -74, maxLng: -11, minLat: 59, maxLat: 84 };

// Coarse mountain ranges layered on Köppen coloring for visible relief.
const MOUNTAIN_REGIONS = [
    { minLng: -122, maxLng: -104, minLat: 32, maxLat: 60 }, // Rockies
    { minLng: -78, maxLng: -66, minLat: -55, maxLat: 10 }, // Andes
    { minLng: 5, maxLng: 16, minLat: 43, maxLat: 48 }, // Alps
    { minLng: 68, maxLng: 100, minLat: 26, maxLat: 40 }, // Himalaya / Tibetan plateau
    { minLng: 38, maxLng: 50, minLat: -3, maxLat: 15 }, // Ethiopian highlands
    { minLng: 128, maxLng: 145, minLat: 33, maxLat: 46 }, // Japanese Alps
];

function inRegion(lng, lat, region) {
    return lng >= region.minLng && lng <= region.maxLng && lat >= region.minLat && lat <= region.maxLat;
}

function isInAnyRegion(lng, lat, regions) {
    for (const region of regions) {
        if (inRegion(lng, lat, region)) {
            return true;
        }
    }

    return false;
}

function isMountainous(lng, lat) {
    return isInAnyRegion(lng, lat, MOUNTAIN_REGIONS);
}

function mountainTerrain(absLat, elevation, band) {
    if (absLat > 55 || elevation > 0.88) {
        return band ? 'rockSnow' : 'rock';
    }

    return band ? 'rockLight' : 'rockDark';
}

function latitudeFallbackTerrain(absLat, roughness, microNoise) {
    if (absLat > 66) {
        return roughness > 0.35 ? 'tundra' : 'tundraDark';
    }

    if (absLat > 50) {
        return roughness > 0.45 ? 'forestDark' : 'forest';
    }

    if (absLat < 23) {
        return microNoise > 0.6 ? 'savannaLight' : 'savanna';
    }

    if (microNoise > 0.72) {
        return 'grassLight';
    }

    if (microNoise > 0.4) {
        return 'grass';
    }

    return 'grassDark';
}

export function terrainAt(lng, lat) {
    if (!isLand(lng, lat)) {
        const depth = Math.abs(fbm(lng * 0.3, lat * 0.3, 2));

        if (depth > 0.28) {
            return 'waterShallow';
        }

        if (depth > 0.14) {
            return 'water';
        }

        return 'waterDeep';
    }

    const absLat = Math.abs(lat);
    const roughness = fbm(lng * 3, lat * 3, 3);
    const microNoise = hash01(Math.floor(lng * 60), Math.floor(lat * 60));

    if (lat < -60) {
        return roughness > 0.78 ? 'rockSnow' : roughness > 0.4 ? 'snow' : 'ice';
    }

    if (inRegion(lng, lat, GREENLAND_REGION)) {
        return roughness > 0.78 ? 'rockSnow' : roughness > 0.4 ? 'snow' : 'ice';
    }

    if (absLat > 78) {
        return roughness > 0.5 ? 'snow' : 'ice';
    }

    const koppenIndex = koppenIndexAt(lng, lat);
    let terrain = terrainFromKoppenIndex(koppenIndex, roughness, microNoise);

    if (!terrain) {
        terrain = latitudeFallbackTerrain(absLat, roughness, microNoise);
    }

    if (isMountainous(lng, lat)) {
        const elevation = 0.6 + fbm(lng * 1.5, lat * 1.5, 3) * 0.4;
        const band = Math.floor(elevation * 14) % 2;

        if (
            terrain.startsWith('forest') ||
            terrain.startsWith('grass') ||
            terrain.startsWith('savanna') ||
            terrain.startsWith('tundra')
        ) {
            return mountainTerrain(absLat, elevation, band);
        }
    }

    return terrain;
}

function adjustBrightness(hex, amount) {
    const r = Math.min(255, Math.max(0, parseInt(hex.slice(1, 3), 16) + amount));
    const g = Math.min(255, Math.max(0, parseInt(hex.slice(3, 5), 16) + amount));
    const b = Math.min(255, Math.max(0, parseInt(hex.slice(5, 7), 16) + amount));

    return `#${r.toString(16).padStart(2, '0')}${g.toString(16).padStart(2, '0')}${b.toString(16).padStart(2, '0')}`;
}

export function colorForTerrain(type) {
    return COLORS[type] ?? COLORS.grass;
}

const TEXTURED_PREFIXES = ['grass', 'forest', 'rainforest', 'savanna', 'tundra', 'desert'];
const ROCK_PREFIXES = ['rock'];

/**
 * Renders one square tile of the pixelated world map onto a canvas context.
 *
 * @param {CanvasRenderingContext2D} ctx
 * @param {[number, number, number, number]} extent Web Mercator [minX, minY, maxX, maxY] covered by this tile.
 * @param {{ tilePx?: number, blockPx?: number }} [options]
 */
export function drawWorldTile(ctx, extent, options = {}) {
    const tilePx = options.tilePx ?? TILE_PX;
    const blockPx = options.blockPx ?? BLOCK_PX;
    const [minX, minY, maxX, maxY] = extent;
    const spanX = maxX - minX;
    const spanY = maxY - minY;
    const blocks = tilePx / blockPx;

    ctx.setTransform(1, 0, 0, 1, 0, 0);
    ctx.clearRect(0, 0, tilePx, tilePx);

    for (let by = 0; by < blocks; by++) {
        for (let bx = 0; bx < blocks; bx++) {
            const mercX = minX + ((bx + 0.5) / blocks) * spanX;
            const mercY = maxY - ((by + 0.5) / blocks) * spanY;
            const { lng, lat } = mercatorToLngLat(mercX, mercY);
            const terrain = terrainAt(lng, lat);
            let color = colorForTerrain(terrain);
            const micro = hash01(Math.floor(lng * 200), Math.floor(lat * 200));

            if (TEXTURED_PREFIXES.some((prefix) => terrain.startsWith(prefix))) {
                if (micro > 0.82) {
                    color = adjustBrightness(color, 8);
                } else if (micro < 0.12) {
                    color = adjustBrightness(color, -8);
                }
            } else if (ROCK_PREFIXES.some((prefix) => terrain.startsWith(prefix))) {
                if (micro > 0.88) {
                    color = adjustBrightness(color, 6);
                } else if (micro < 0.1) {
                    color = adjustBrightness(color, -6);
                }
            }

            ctx.fillStyle = color;
            ctx.fillRect(bx * blockPx, by * blockPx, blockPx, blockPx);
        }
    }
}
