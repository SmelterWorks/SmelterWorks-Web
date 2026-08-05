import Map from 'ol/Map.js';
import View from 'ol/View.js';
import TileLayer from 'ol/layer/Tile.js';
import VectorLayer from 'ol/layer/Vector.js';
import VectorSource from 'ol/source/Vector.js';
import XYZ from 'ol/source/XYZ.js';
import Feature from 'ol/Feature.js';
import Point from 'ol/geom/Point.js';
import Overlay from 'ol/Overlay.js';
import { fromLonLat } from 'ol/proj.js';
import { defaults as defaultControls } from 'ol/control.js';
import { defaults as defaultInteractions } from 'ol/interaction.js';
import { Circle as CircleStyle, Fill, Stroke, Style } from 'ol/style.js';
import 'ol/ol.css';

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

    const pinStyle = new Style({
        image: new CircleStyle({
            radius: 7,
            fill: new Fill({ color: '#b45309' }),
            stroke: new Stroke({ color: '#f4ebe1', width: 2 }),
        }),
    });

    const pixelRatio = Math.min(window.devicePixelRatio || 1, 2);
    const useRetinaTiles = pixelRatio > 1;
    const vectorSource = new VectorSource({ features });

    const map = new Map({
        target: canvas,
        pixelRatio,
        layers: [
            new TileLayer({
                source: new XYZ({
                    url: useRetinaTiles
                        ? 'https://{a-d}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}@2x.png'
                        : 'https://{a-d}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}.png',
                    attributions:
                        '&copy; <a href="https://www.openstreetmap.org/copyright" rel="noopener noreferrer" target="_blank">OpenStreetMap</a> &copy; <a href="https://carto.com/attributions" rel="noopener noreferrer" target="_blank">CARTO</a>',
                    maxZoom: 12,
                    tilePixelRatio: useRetinaTiles ? 2 : 1,
                }),
            }),
            new VectorLayer({
                source: vectorSource,
                style: pinStyle,
            }),
        ],
        controls: defaultControls({
            zoom: false,
            rotate: false,
            attribution: true,
        }),
        interactions: defaultInteractions({
            mouseWheelZoom: false,
            doubleClickZoom: false,
            pinchZoom: false,
            shiftDragZoom: false,
            dragPan: true,
            keyboard: false,
        }),
        view: new View({
            center: fromLonLat([0, 20]),
            zoom: 1,
            maxZoom: 6,
            minZoom: 0,
            multiWorld: false,
            constrainResolution: true,
        }),
    });

    requestAnimationFrame(() => {
        map.updateSize();
        map.getView().fit([-20037508.34, -16000000, 20037508.34, 18000000], {
            padding: [8, 8, 8, 8],
            duration: 0,
            constrainResolution: true,
        });
    });

    const tip = document.createElement('div');
    tip.className = 'region-map__tip';
    tip.hidden = true;
    canvas.appendChild(tip);

    const tipOverlay = new Overlay({
        element: tip,
        offset: [0, -14],
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
