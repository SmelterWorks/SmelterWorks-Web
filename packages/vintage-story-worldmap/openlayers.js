import XYZ from 'ol/source/XYZ.js';
import TileState from 'ol/TileState.js';
import { createXYZ } from 'ol/tilegrid.js';
import { drawWorldTile, TILE_PX } from './src/terrain.js';

/**
 * Builds an OpenLayers XYZ tile source that procedurally renders the pixelated
 * world map instead of fetching image tiles over the network. Each tile is
 * drawn once onto an offscreen canvas and cached by tile coordinate.
 *
 * @param {{ tileGrid?: import('ol/tilegrid/TileGrid.js').default, maxZoom?: number, tilePx?: number }} [options]
 */
export function createWorldTileSource(options = {}) {
    const tileGrid = options.tileGrid ?? createXYZ();
    const maxZoom = options.maxZoom ?? 12;
    const tilePx = options.tilePx ?? TILE_PX;
    const tileCanvasCache = new Map();

    return new XYZ({
        tileGrid,
        maxZoom,
        tileUrlFunction: (tileCoord) => tileCoord.join('/'),
        tileLoadFunction: (tile) => {
            const image = tile.getImage();

            if (!(image instanceof HTMLImageElement)) {
                tile.setState(TileState.ERROR);

                return;
            }

            const tileCoord = tile.getTileCoord();
            const cacheKey = tileCoord.join('/');
            const cached = tileCanvasCache.get(cacheKey);

            const markLoaded = () => tile.setState(TileState.LOADED);
            const markError = () => tile.setState(TileState.ERROR);

            if (cached) {
                image.onload = markLoaded;
                image.onerror = markError;
                image.src = cached;

                return;
            }

            const canvas = document.createElement('canvas');
            canvas.width = tilePx;
            canvas.height = tilePx;
            const ctx = canvas.getContext('2d');

            if (!ctx) {
                markError();

                return;
            }

            try {
                drawWorldTile(ctx, tileGrid.getTileCoordExtent(tileCoord), { tilePx });
            } catch {
                markError();

                return;
            }

            const dataUrl = canvas.toDataURL('image/png');
            tileCanvasCache.set(cacheKey, dataUrl);

            image.onload = markLoaded;
            image.onerror = markError;
            image.src = dataUrl;
        },
    });
}
