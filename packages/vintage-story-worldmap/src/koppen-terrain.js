import { KOPPEN_CLASSES } from './koppen-mask-data.js';

/**
 * Maps a Köppen-Geiger class code to a base Vintage Story terrain type.
 * Procedural noise in terrain.js still adds micro-variation on top.
 *
 * @param {string} code
 * @param {number} roughness fbm noise 0..1
 * @param {number} microNoise hash01 0..1
 */
export function terrainFromKoppen(code, roughness, microNoise) {
    switch (code) {
        case 'Af':
        case 'Am':
            return roughness > 0.35 ? 'rainforest' : 'rainforestDark';
        case 'Aw':
        case 'As':
            return microNoise > 0.55 ? 'savannaLight' : 'savanna';
        case 'BWh':
        case 'BWk':
            if (roughness > 0.45) {
                return 'desertRock';
            }

            return microNoise > 0.5 ? 'desert' : 'desertDark';
        case 'BSh':
            return microNoise > 0.6 ? 'sandLight' : 'sand';
        case 'BSk':
            return microNoise > 0.55 ? 'savanna' : 'grass';
        case 'Cfa':
        case 'Cwa':
            if (roughness > 0.5) {
                return 'forest';
            }

            return microNoise > 0.5 ? 'grass' : 'grassDark';
        case 'Cfb':
        case 'Cfc':
        case 'Dfb':
        case 'Dfc':
        case 'Dwb':
        case 'Dwc':
            return roughness > 0.42 ? 'forestDark' : 'forest';
        case 'Csa':
        case 'Csb':
            return microNoise > 0.55 ? 'savanna' : 'grass';
        case 'Csc':
        case 'Dsc':
            return roughness > 0.4 ? 'tundra' : 'tundraDark';
        case 'Dfa':
        case 'Dwa':
            return roughness > 0.48 ? 'forest' : 'grass';
        case 'Dfd':
        case 'Dwd':
            return roughness > 0.45 ? 'snow' : 'tundra';
        case 'Dsa':
        case 'Dsb':
            return roughness > 0.5 ? 'rock' : 'savanna';
        case 'EF':
            return roughness > 0.78 ? 'rockSnow' : roughness > 0.4 ? 'snow' : 'ice';
        case 'ET':
            return roughness > 0.35 ? 'tundra' : 'tundraDark';
        default:
            return microNoise > 0.5 ? 'grass' : 'grassDark';
    }
}

/**
 * @param {number} index 1-based index from the Köppen raster. 0 means no data.
 */
export function terrainFromKoppenIndex(index, roughness, microNoise) {
    if (index <= 0 || index > KOPPEN_CLASSES.length) {
        return null;
    }

    return terrainFromKoppen(KOPPEN_CLASSES[index - 1], roughness, microNoise);
}
