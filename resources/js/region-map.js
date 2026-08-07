import Map from 'ol/Map.js';
import View from 'ol/View.js';
import TileLayer from 'ol/layer/Tile.js';
import VectorLayer from 'ol/layer/Vector.js';
import VectorSource from 'ol/source/Vector.js';
import Feature from 'ol/Feature.js';
import Point from 'ol/geom/Point.js';
import Overlay from 'ol/Overlay.js';
import { fromLonLat } from 'ol/proj.js';
import { defaults as defaultControls } from 'ol/control.js';
import { defaults as defaultInteractions } from 'ol/interaction.js';
import { Circle as CircleStyle, Fill, Stroke, Style } from 'ol/style.js';
import { createWorldTileSource } from 'vintage-story-worldmap/openlayers';
import 'ol/ol.css';

// Mercator can't reach the true poles. Bounds below cover the full width of the
// world plus Greenland/Svalbard in the north and the Antarctic Peninsula in the
// south. The map frame background matches the ocean color, so any letterboxing
// from the container's aspect ratio reads as open water rather than empty space.
const WORLD_VIEW_EXTENT = (() => {
    const [minX, minY] = fromLonLat([-180, -75]);
    const [maxX, maxY] = fromLonLat([180, 80]);

    return [minX, minY, maxX, maxY];
})();

function markerStyles() {
    return [
        new Style({
            image: new CircleStyle({
                radius: 8,
                fill: new Fill({ color: '#f4ebe1' }),
                stroke: new Stroke({ color: '#1a1816', width: 2.5 }),
            }),
        }),
        new Style({
            image: new CircleStyle({
                radius: 3.5,
                fill: new Fill({ color: '#b45309' }),
                stroke: new Stroke({ color: '#7a3506', width: 1 }),
            }),
        }),
    ];
}

function initRegionMap() {
    const mapRoot = document.querySelector('[data-region-map]');

    if (!(mapRoot instanceof HTMLElement)) {
        return;
    }

    const canvas = mapRoot.querySelector('[data-region-map-canvas]');
    const markersNode = mapRoot.querySelector('[data-region-map-markers]');

    if (!(canvas instanceof HTMLElement) || !markersNode) {
        return;
    }

    let markers = [];

    try {
        markers = JSON.parse(markersNode.textContent || '[]');
    } catch {
        markers = [];
    }

    if (!Array.isArray(markers) || markers.length === 0) {
        return;
    }

    const features = [];

    markers.forEach((marker) => {
        const lat = Number(marker.lat);
        const lng = Number(marker.lng);
        const label = String(marker.label || '');

        if (!Number.isFinite(lat) || !Number.isFinite(lng) || label === '') {
            return;
        }

        features.push(
            new Feature({
                geometry: new Point(fromLonLat([lng, lat])),
                label,
            }),
        );
    });

    if (features.length === 0) {
        return;
    }

    const pixelRatio = Math.min(window.devicePixelRatio || 1, 2);
    const vectorSource = new VectorSource({ features });

    const map = new Map({
        target: canvas,
        pixelRatio,
        layers: [
            new TileLayer({
                source: createWorldTileSource(),
            }),
            new VectorLayer({
                source: vectorSource,
                style: markerStyles(),
            }),
        ],
        controls: defaultControls({
            zoom: false,
            rotate: false,
            attribution: false,
        }),
        // This map only ever shows the whole world at a fixed zoom, so there is
        // nowhere useful to pan or zoom to. Dragging used to be enabled, but the
        // view was fit to exactly the world extent with no bounds beyond it, so a
        // drag of even a few pixels exposed undefined tiles (no coastline data,
        // and Mercator y values with no sane latitude) alongside the markers,
        // and moved the markers out from under the fixed-position hit testing.
        // Disabling every interaction is the correct fix, not a tighter bound:
        // there is no valid interaction for a map that always shows everything.
        interactions: defaultInteractions({
            mouseWheelZoom: false,
            doubleClickZoom: false,
            pinchZoom: false,
            shiftDragZoom: false,
            dragPan: false,
            keyboard: false,
        }),
        view: new View({
            center: fromLonLat([0, 15]),
            zoom: 1,
            multiWorld: false,
            constrainResolution: false,
            enableRotation: false,
            extent: WORLD_VIEW_EXTENT,
        }),
    });

    requestAnimationFrame(() => {
        map.updateSize();
        map.getView().fit(WORLD_VIEW_EXTENT, {
            padding: [0, 0, 0, 0],
            duration: 0,
            constrainResolution: false,
        });
    });

    const tip = document.createElement('div');
    tip.className = 'region-map__tip';
    tip.hidden = true;
    canvas.appendChild(tip);

    const tipOverlay = new Overlay({
        element: tip,
        offset: [0, -16],
        positioning: 'bottom-center',
        stopEvent: false,
    });
    map.addOverlay(tipOverlay);

    const hitRadiusPx = 14;

    const featureNearPixel = (pixel) => {
        let nearest = null;
        let nearestDistance = hitRadiusPx;

        vectorSource.forEachFeature((feature) => {
            const geometry = feature.getGeometry();

            if (!(geometry instanceof Point)) {
                return;
            }

            const featurePixel = map.getPixelFromCoordinate(geometry.getCoordinates());

            if (!featurePixel) {
                return;
            }

            const dx = featurePixel[0] - pixel[0];
            const dy = featurePixel[1] - pixel[1];
            const distance = Math.hypot(dx, dy);

            if (distance <= nearestDistance) {
                nearest = feature;
                nearestDistance = distance;
            }
        });

        return nearest;
    };

    map.on('pointermove', (event) => {
        if (event.dragging) {
            tip.hidden = true;
            canvas.style.cursor = '';

            return;
        }

        const feature = featureNearPixel(event.pixel);
        const label = feature ? String(feature.get('label') || '') : '';

        if (label === '') {
            tip.hidden = true;
            canvas.style.cursor = '';

            return;
        }

        tip.textContent = label;
        tip.hidden = false;
        tipOverlay.setPosition(feature.getGeometry().getCoordinates());
        canvas.style.cursor = 'pointer';
    });
}

initRegionMap();
