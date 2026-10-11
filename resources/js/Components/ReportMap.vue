<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps({
    // [{ code, lat, lng, level: 'red' | 'orange' | 'yellow' }]
    markers: { type: Array, default: () => [] },
    selected: { type: String, default: null },
});
const emit = defineEmits(['select']);

const el = ref(null);
let map = null;
let layer = null;

const COTONOU = [6.3703, 2.3912];

function makeIcon(level, active) {
    return L.divIcon({
        className: 'lf-wrap',
        html: `<span class="lf-dot ${level}${active ? ' active' : ''}"></span>`,
        iconSize: [28, 28],
        iconAnchor: [14, 14],
    });
}

function draw() {
    if (!map) return;
    layer.clearLayers();
    props.markers.forEach((m) => {
        L.marker([m.lat, m.lng], {
            icon: makeIcon(m.level, m.code === props.selected),
            title: m.code,
        })
            .on('click', () => emit('select', m.code))
            .addTo(layer);
    });
}

function fitAll() {
    if (!map || !props.markers.length) return;
    const bounds = L.latLngBounds(props.markers.map((m) => [m.lat, m.lng]));
    map.fitBounds(bounds, { padding: [40, 40], maxZoom: 15 });
}

onMounted(() => {
    map = L.map(el.value, { scrollWheelZoom: false }).setView(COTONOU, 12);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; contributeurs <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    }).addTo(map);
    layer = L.layerGroup().addTo(map);
    draw();
    fitAll();
});

onBeforeUnmount(() => {
    map?.remove();
    map = null;
});

watch(() => props.markers, draw, { deep: true });

watch(
    () => props.selected,
    (code) => {
        draw();
        const m = props.markers.find((x) => x.code === code);
        if (map && m) map.panTo([m.lat, m.lng]);
    },
);
</script>

<template>
    <div ref="el" class="leaflet-map" role="application" aria-label="Carte des signalements"></div>
</template>
