<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import OperatorLayout from '../../Layouts/OperatorLayout.vue';
import Icon from '../../Components/Icon.vue';
import ReportMap from '../../Components/ReportMap.vue';

const props = defineProps({
    filter: { type: String, default: 'all' },
    today: { type: String, default: '' },
    counts: { type: Object, required: true },
    metrics: { type: Object, required: true },
    reports: { type: Array, default: () => [] },
    markers: { type: Array, default: () => [] },
    selected: { type: Object, default: null },
});

const page = usePage();
const firstName = computed(() => (page.props.auth?.user?.name ?? '').split(' ')[0]);

const filters = computed(() => [
    { key: 'all', label: 'Ouverts', count: props.counts.all },
    { key: 'urgent', label: 'Urgents', count: props.counts.urgent },
    { key: 'to_assign', label: 'À affecter', count: props.counts.to_assign },
    { key: 'resolved', label: 'Terminés (30 j)', count: props.counts.resolved },
]);

/* --- Navigation dans la liste ------------------------------------------ */
function setFilter(key) {
    router.get('/operateur', { filter: key }, { preserveScroll: true, preserveState: true, replace: true });
}

function select(code) {
    router.get(
        '/operateur',
        { filter: props.filter, selected: code },
        { preserveScroll: true, preserveState: true, replace: true, only: ['selected', 'filter'] },
    );
}

/* --- Actions sur le signalement sélectionné ----------------------------- */
const teamChoice = ref(null);
watch(
    () => props.selected?.code,
    () => {
        teamChoice.value = props.selected?.suggested_team_id ?? null;
    },
    { immediate: true },
);

const busy = ref(false);

function send(payload, confirmMessage = null) {
    if (!props.selected) return;
    router.patch(`/operateur/signalements/${props.selected.id}`, payload, {
        preserveScroll: true,
        onBefore: () => (confirmMessage ? window.confirm(confirmMessage) : true),
        onStart: () => (busy.value = true),
        onFinish: () => (busy.value = false),
    });
}

const assign = () => teamChoice.value && send({ action: 'assign', team_id: teamChoice.value });
const resolve = () => send({ action: 'resolve' }, 'Marquer ce signalement comme terminé ?');

const directionsUrl = computed(() => {
    const s = props.selected;
    return s && s.lat !== null && s.lng !== null
        ? `https://www.google.com/maps/dir/?api=1&destination=${s.lat},${s.lng}`
        : null;
});

/* --- Rafraîchissement automatique (nouvelles alertes) --------------------- */
let poll = null;
onMounted(() => {
    poll = setInterval(() => {
        if (document.visibilityState === 'visible' && !busy.value) {
            router.reload({ only: ['reports', 'counts', 'metrics', 'markers'] });
        }
    }, 30000);
});
onBeforeUnmount(() => clearInterval(poll));
</script>

<template>
    <OperatorLayout :count="counts.all">
        <Head title="Espace opérateur" />

        <div class="dash-header">
            <div>
                <span class="eyebrow"><span></span> {{ today }}</span>
                <h1>Bonjour, {{ firstName }}</h1>
                <p>Voici ce qui demande votre attention aujourd’hui.</p>
            </div>
        </div>

        <div class="metrics">
            <article class="metric">
                <span class="metric-icon red"><Icon name="alert" /></span>
                <div>
                    <strong>{{ metrics.new_today }}</strong>
                    <p>Nouveaux signalements aujourd’hui</p>
                    <small class="muted">{{ counts.to_assign }} à affecter</small>
                </div>
            </article>
            <article class="metric">
                <span class="metric-icon green"><Icon name="clock" /></span>
                <div>
                    <strong>{{ metrics.avg_dispatch_minutes === null ? '—' : `${metrics.avg_dispatch_minutes} min` }}</strong>
                    <p>Délai moyen avant envoi d’une équipe</p>
                    <small class="muted">Sur 30 jours</small>
                </div>
            </article>
            <article class="metric">
                <span class="metric-icon blue"><Icon name="wrench" /></span>
                <div>
                    <strong>{{ metrics.resolved_month }}</strong>
                    <p>Interventions terminées</p>
                    <small class="muted">Ce mois-ci</small>
                </div>
            </article>
        </div>

        <div class="dash-content">
            <!-- Liste priorisée -->
            <section class="alert-list">
                <div class="section-title">
                    <div>
                        <h2>À prioriser</h2>
                        <p>Triés par score de priorité (règles explicables).</p>
                    </div>
                </div>

                <div class="filters">
                    <button
                        v-for="f in filters"
                        :key="f.key"
                        type="button"
                        :class="{ 'filter-active': filter === f.key }"
                        @click="setFilter(f.key)"
                    >
                        {{ f.label }} <b>{{ f.count }}</b>
                    </button>
                </div>

                <p v-if="!reports.length" class="empty">Aucun signalement dans cette vue.</p>

                <button
                    v-for="r in reports"
                    :key="r.code"
                    type="button"
                    class="alert-row"
                    :class="{ 'selected-row': selected && r.code === selected.code }"
                    @click="select(r.code)"
                >
                    <span class="severity" :class="r.color"></span>
                    <div>
                        <b>{{ r.title }}</b>
                        <small><Icon name="pin" :size="13" />{{ r.place }} · {{ r.ago }}</small>
                    </div>
                    <div class="alert-score" :class="r.color">
                        <b>{{ r.score }}</b>
                        <span>Priorité</span>
                    </div>
                    <Icon name="chevron" :size="17" />
                </button>
            </section>

            <!-- Carte + décision -->
            <section class="map-panel">
                <div class="map-top">
                    <span><Icon name="map" /> Carte des alertes ouvertes</span>
                </div>

                <ReportMap :markers="markers" :selected="selected?.code ?? null" @select="select" />

                <div v-if="selected" class="decision">
                    <div class="decision-head">
                        <span class="severity" :class="selected.color"></span>
                        <div>
                            <b>{{ selected.code }} · {{ selected.title }}</b>
                            <small><Icon name="pin" :size="13" />{{ selected.place }}</small>
                        </div>
                        <span class="chip" :class="`${selected.color}-chip`">{{ selected.score }}/100</span>
                    </div>

                    <div class="ai-note">
                        <Icon name="chart" />
                        <span>
                            <b>Pourquoi cette priorité ? ({{ selected.level_label }})</b>
                            <ul class="reasons">
                                <li v-for="reason in selected.reasons" :key="reason">{{ reason }}</li>
                            </ul>
                        </span>
                    </div>

                    <div v-if="selected.photo_url || selected.audio_url" class="evidence">
                        <img v-if="selected.photo_url" :src="selected.photo_url" alt="Photo envoyée par le citoyen" loading="lazy" />
                        <template v-if="selected.audio_url">
                            <audio :src="selected.audio_url" controls preload="none"></audio>
                            <small>Note vocale en {{ selected.audio_lang }}</small>
                        </template>
                    </div>

                    <!-- Étape : affecter -->
                    <div v-if="selected.status === 'received' || selected.status === 'prioritized'" class="assign-form">
                        <span class="field-note">Équipe (la plus proche proposée par défaut)</span>
                        <select v-model="teamChoice" aria-label="Équipe à envoyer">
                            <option :value="null" disabled>Choisir une équipe…</option>
                            <option v-for="t in selected.teams" :key="t.id" :value="t.id" :disabled="!t.is_available">
                                {{ t.name }}
                                {{ t.distance_km !== null ? `· à ${t.distance_km} km` : '' }}
                                {{ t.is_available ? '' : '· indisponible' }}
                            </option>
                        </select>
                        <button type="button" class="primary small" :disabled="!teamChoice || busy" @click="assign">
                            Affecter l’équipe <Icon name="arrow" :size="16" />
                        </button>
                    </div>

                    <!-- Étape : suivre / clôturer -->
                    <div v-else-if="selected.status === 'dispatched'" class="status-line">
                        <span>Équipe envoyée : <b>{{ selected.team }}</b></span>
                        <button type="button" class="primary small" :disabled="busy" @click="resolve">
                            Marquer terminé <Icon name="check" :size="16" />
                        </button>
                    </div>

                    <div v-else class="status-line">
                        <span>Intervention terminée<template v-if="selected.team"> par <b>{{ selected.team }}</b></template>.</span>
                    </div>

                    <div v-if="directionsUrl" class="status-line">
                        <a :href="directionsUrl" target="_blank" rel="noopener" class="ghost">Ouvrir l’itinéraire</a>
                        <span v-if="selected.gps_accuracy_m">Précision GPS ≈ {{ selected.gps_accuracy_m }} m</span>
                    </div>

                    <ul v-if="selected.events.length" class="event-list">
                        <li v-for="(e, i) in selected.events" :key="i">{{ e.at }} · {{ e.note }}</li>
                    </ul>
                </div>
            </section>
        </div>
    </OperatorLayout>
</template>
