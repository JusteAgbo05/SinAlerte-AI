<script setup>
import { computed, ref } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import OperatorLayout from '../../Layouts/OperatorLayout.vue';
import Icon from '../../Components/Icon.vue';

defineProps({
    teams: { type: Array, default: () => [] },
});

const page = usePage();
// Erreur de suppression refusée (équipe avec historique)
const deleteError = computed(() => page.props.errors?.team ?? '');

/* --- Formulaire (création ou modification) -------------------------------- */
const editingId = ref(null);
const form = useForm({
    name: '',
    zone: '',
    latitude: '',
    longitude: '',
    is_available: true,
});

function startEdit(team) {
    editingId.value = team.id;
    form.clearErrors();
    form.name = team.name;
    form.zone = team.zone ?? '';
    form.latitude = team.latitude ?? '';
    form.longitude = team.longitude ?? '';
    form.is_available = team.is_available;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function cancel() {
    editingId.value = null;
    geoError.value = '';
    form.reset();
    form.clearErrors();
}

function submit() {
    const options = { preserveScroll: true, onSuccess: () => cancel() };
    if (editingId.value) form.patch(`/operateur/equipes/${editingId.value}`, options);
    else form.post('/operateur/equipes', options);
}

/* --- Position de la base : GPS de l'appareil ------------------------------- */
const locating = ref(false);
const geoError = ref('');

function locate() {
    geoError.value = '';
    if (!('geolocation' in navigator)) {
        geoError.value = "La géolocalisation n'est pas disponible. Saisissez les coordonnées à la main.";
        return;
    }
    locating.value = true;
    navigator.geolocation.getCurrentPosition(
        (pos) => {
            form.latitude = Number(pos.coords.latitude.toFixed(6));
            form.longitude = Number(pos.coords.longitude.toFixed(6));
            form.clearErrors('latitude', 'longitude');
            locating.value = false;
        },
        () => {
            locating.value = false;
            geoError.value = 'Position introuvable ou refusée. Saisissez les coordonnées à la main.';
        },
        { enableHighAccuracy: true, timeout: 15000 },
    );
}

/* --- Actions sur une équipe -------------------------------------------------- */
function toggle(team) {
    router.patch(`/operateur/equipes/${team.id}`, { is_available: !team.is_available }, { preserveScroll: true });
}

function remove(team) {
    router.delete(`/operateur/equipes/${team.id}`, {
        preserveScroll: true,
        onBefore: () => window.confirm(`Supprimer l’équipe « ${team.name} » ?`),
    });
}
</script>

<template>
    <OperatorLayout>
        <Head title="Équipes" />

        <div class="dash-header">
            <div>
                <span class="eyebrow"><span></span> Équipes de terrain</span>
                <h1>Équipes</h1>
                <p>Ce sont les équipes proposées lorsque vous affectez un signalement.</p>
            </div>
        </div>

        <div class="teams-layout">
            <!-- Formulaire -->
            <section class="team-panel">
                <h2>{{ editingId ? 'Modifier l’équipe' : 'Ajouter une équipe' }}</h2>
                <p>La position de la base permet de proposer l’équipe la plus proche d’une fuite.</p>

                <form class="team-form" @submit.prevent="submit">
                    <label for="team-name">Nom de l’équipe</label>
                    <input
                        id="team-name"
                        v-model="form.name"
                        class="text-input"
                        type="text"
                        maxlength="80"
                        placeholder="Ex. Aqua Maintenance · Équipe 4"
                        required
                    />
                    <p v-if="form.errors.name" class="field-error">{{ form.errors.name }}</p>

                    <label for="team-zone">Zone <em class="optional">Optionnel</em></label>
                    <input
                        id="team-zone"
                        v-model="form.zone"
                        class="text-input"
                        type="text"
                        maxlength="80"
                        placeholder="Ex. Cotonou nord, Abomey-Calavi"
                    />
                    <p v-if="form.errors.zone" class="field-error">{{ form.errors.zone }}</p>

                    <label>Position de la base <em class="optional">Optionnel</em></label>
                    <div class="row2">
                        <input
                            v-model="form.latitude"
                            class="text-input"
                            type="number"
                            step="any"
                            placeholder="Latitude (ex. 6.3654)"
                            aria-label="Latitude"
                        />
                        <input
                            v-model="form.longitude"
                            class="text-input"
                            type="number"
                            step="any"
                            placeholder="Longitude (ex. 2.4183)"
                            aria-label="Longitude"
                        />
                    </div>
                    <p v-if="form.errors.latitude" class="field-error">{{ form.errors.latitude }}</p>
                    <p v-if="form.errors.longitude" class="field-error">{{ form.errors.longitude }}</p>
                    <button type="button" class="ghost fit" :disabled="locating" @click="locate">
                        <Icon name="pin" :size="14" />
                        {{ locating ? 'Recherche…' : 'Utiliser ma position actuelle' }}
                    </button>
                    <p v-if="geoError" class="field-error">{{ geoError }}</p>

                    <label class="check-row">
                        <input v-model="form.is_available" type="checkbox" /> Équipe disponible
                    </label>

                    <div class="form-actions">
                        <button v-if="editingId" type="button" class="ghost" @click="cancel">Annuler</button>
                        <button type="submit" class="primary small" :disabled="form.processing">
                            {{ editingId ? 'Enregistrer' : 'Ajouter l’équipe' }}
                            <Icon name="check" :size="16" />
                        </button>
                    </div>
                </form>
            </section>

            <!-- Liste -->
            <section class="team-panel">
                <h2>Équipes enregistrées <span class="count-pill">{{ teams.length }}</span></h2>
                <p>Une équipe indisponible n’est plus proposée à l’affectation.</p>

                <p v-if="deleteError" class="field-error banner-error">{{ deleteError }}</p>
                <p v-if="!teams.length" class="empty">Aucune équipe : ajoutez-en une pour pouvoir affecter des signalements.</p>

                <article v-for="t in teams" :key="t.id" class="team-card">
                    <div class="team-head">
                        <span class="avatar">{{ t.name.split(/[\s·\-]+/).filter(Boolean).slice(0, 2).map((w) => w[0].toUpperCase()).join('') }}</span>
                        <div class="team-title">
                            <b>{{ t.name }}</b>
                            <small>
                                {{ t.zone || 'Zone non précisée' }}
                                ·
                                <template v-if="t.latitude !== null && t.longitude !== null">
                                    base {{ t.latitude }}, {{ t.longitude }}
                                </template>
                                <template v-else>position non renseignée (jamais proposée par proximité)</template>
                            </small>
                        </div>
                        <span class="chip" :class="t.is_available ? 'green-chip' : 'grey-chip'">
                            {{ t.is_available ? 'Disponible' : 'Indisponible' }}
                        </span>
                    </div>

                    <small class="team-stats">
                        {{ t.active_count }} intervention{{ t.active_count > 1 ? 's' : '' }} en cours
                        · {{ t.total_count }} au total
                    </small>

                    <div class="team-actions">
                        <button type="button" class="ghost" @click="toggle(t)">
                            {{ t.is_available ? 'Rendre indisponible' : 'Rendre disponible' }}
                        </button>
                        <button type="button" class="ghost" @click="startEdit(t)">Modifier</button>
                        <button type="button" class="ghost danger" @click="remove(t)">Supprimer</button>
                    </div>
                </article>
            </section>
        </div>
    </OperatorLayout>
</template>
