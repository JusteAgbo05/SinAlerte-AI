<script setup>
import { computed, onBeforeUnmount, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import PublicLayout from '../../Layouts/PublicLayout.vue';
import Icon from '../../Components/Icon.vue';

const MAX_SECONDS = 60;

const form = useForm({
    perceived_severity: 'medium',
    latitude: null,
    longitude: null,
    gps_accuracy_m: null,
    location_hint: '',
    photo: null,
    audio: null,
    audio_lang: 'fr',
});

const severities = [
    { value: 'low', label: 'Petite fuite', hint: 'Goutte à goutte, suintement' },
    { value: 'medium', label: 'Écoulement continu', hint: "L'eau coule sans arrêt" },
    { value: 'high', label: 'Forte fuite', hint: 'Jaillissement, route inondée' },
];

/* ---------------------------------------------------------------- Photo */
const photoInput = ref(null);
const photoUrl = ref(null);

function onPhoto(event) {
    const file = event.target.files?.[0];
    if (!file) return;
    if (photoUrl.value) URL.revokeObjectURL(photoUrl.value);
    form.photo = file;
    photoUrl.value = URL.createObjectURL(file);
}

function removePhoto() {
    if (photoUrl.value) URL.revokeObjectURL(photoUrl.value);
    photoUrl.value = null;
    form.photo = null;
    if (photoInput.value) photoInput.value.value = '';
}

/* ------------------------------------------------------------- Position */
const locating = ref(false);
const geoError = ref('');
const hasPosition = computed(() => form.latitude !== null && form.longitude !== null);

function locate() {
    geoError.value = '';
    if (!('geolocation' in navigator)) {
        geoError.value = "La géolocalisation n'est pas disponible sur cet appareil. Indiquez un repère ci-dessous.";
        return;
    }
    locating.value = true;
    navigator.geolocation.getCurrentPosition(
        (pos) => {
            form.latitude = Number(pos.coords.latitude.toFixed(6));
            form.longitude = Number(pos.coords.longitude.toFixed(6));
            form.gps_accuracy_m = Math.round(pos.coords.accuracy);
            form.clearErrors('latitude');
            locating.value = false;
        },
        (err) => {
            locating.value = false;
            geoError.value =
                err.code === 1
                    ? 'Accès à la position refusé. Autorisez-le dans le navigateur, ou indiquez un repère ci-dessous.'
                    : 'Position introuvable. Réessayez, ou indiquez un repère ci-dessous.';
        },
        { enableHighAccuracy: true, timeout: 15000, maximumAge: 30000 },
    );
}

/* ----------------------------------------------------------- Note vocale */
const recording = ref(false);
const seconds = ref(0);
const audioUrl = ref(null);
const micError = ref('');

let recorder = null;
let stream = null;
let chunks = [];
let timer = null;

const clock = computed(() => {
    const m = String(Math.floor(seconds.value / 60)).padStart(2, '0');
    const s = String(seconds.value % 60).padStart(2, '0');
    return `${m}:${s}`;
});

function pickMime() {
    if (!window.MediaRecorder) return '';
    const candidates = ['audio/webm;codecs=opus', 'audio/webm', 'audio/mp4', 'audio/ogg;codecs=opus'];
    return candidates.find((m) => MediaRecorder.isTypeSupported(m)) || '';
}

function releaseStream() {
    stream?.getTracks().forEach((t) => t.stop());
    stream = null;
}

async function startRecording() {
    micError.value = '';
    if (!navigator.mediaDevices?.getUserMedia || !window.MediaRecorder) {
        micError.value = "L'enregistrement vocal n'est pas disponible sur ce navigateur.";
        return;
    }
    try {
        stream = await navigator.mediaDevices.getUserMedia({ audio: true });
    } catch {
        micError.value = 'Accès au micro refusé. Autorisez-le dans le navigateur.';
        return;
    }

    const mime = pickMime();
    recorder = new MediaRecorder(stream, mime ? { mimeType: mime } : undefined);
    chunks = [];

    recorder.ondataavailable = (e) => {
        if (e.data && e.data.size) chunks.push(e.data);
    };
    recorder.onstop = () => {
        const type = (recorder.mimeType || mime || 'audio/webm').split(';')[0];
        const ext = type.includes('mp4') ? 'm4a' : type.includes('ogg') ? 'ogg' : 'webm';
        const blob = new Blob(chunks, { type });
        form.audio = new File([blob], `note-vocale.${ext}`, { type });
        if (audioUrl.value) URL.revokeObjectURL(audioUrl.value);
        audioUrl.value = URL.createObjectURL(blob);
        releaseStream();
    };

    recorder.start();
    recording.value = true;
    seconds.value = 0;
    timer = setInterval(() => {
        seconds.value += 1;
        if (seconds.value >= MAX_SECONDS) stopRecording();
    }, 1000);
}

function stopRecording() {
    clearInterval(timer);
    timer = null;
    recording.value = false;
    if (recorder && recorder.state !== 'inactive') recorder.stop();
}

function toggleRecording() {
    if (recording.value) stopRecording();
    else startRecording();
}

function removeAudio() {
    if (audioUrl.value) URL.revokeObjectURL(audioUrl.value);
    audioUrl.value = null;
    form.audio = null;
}

onBeforeUnmount(() => {
    clearInterval(timer);
    if (recorder && recorder.state !== 'inactive') recorder.stop();
    releaseStream();
    if (photoUrl.value) URL.revokeObjectURL(photoUrl.value);
    if (audioUrl.value) URL.revokeObjectURL(audioUrl.value);
});

/* --------------------------------------------------------------- Envoi */
function submit() {
    if (recording.value) stopRecording();
    form.post('/signaler', { forceFormData: true, preserveScroll: true });
}
</script>

<template>
    <PublicLayout>
        <Head title="Signaler une fuite" />

        <section class="report-page">
            <div class="report-heading">
                <Link href="/" class="back" aria-label="Retour à l'accueil">←</Link>
                <div>
                    <span class="eyebrow"><span></span> Nouveau signalement</span>
                    <h1>Que se passe-t-il ?</h1>
                    <p>Quelques informations suffisent. Vous pourrez envoyer votre alerte en moins d’une minute.</p>
                </div>
            </div>

            <form class="report-grid" style="margin-top: 32px" @submit.prevent="submit">
                <div class="form-card">
                    <!-- Gravité -->
                    <label>Gravité de la fuite</label>
                    <div class="sev-options" role="radiogroup">
                        <button
                            v-for="s in severities"
                            :key="s.value"
                            type="button"
                            class="sev-option"
                            :class="{ selected: form.perceived_severity === s.value }"
                            role="radio"
                            :aria-checked="form.perceived_severity === s.value"
                            @click="form.perceived_severity = s.value"
                        >
                            <b>{{ s.label }}</b>
                            <small>{{ s.hint }}</small>
                        </button>
                    </div>
                    <p v-if="form.errors.perceived_severity" class="field-error">{{ form.errors.perceived_severity }}</p>

                    <!-- Photo -->
                    <label>Photo de la fuite <em>Recommandée</em></label>
                    <input ref="photoInput" type="file" accept="image/*" hidden @change="onPhoto" />
                    <div v-if="photoUrl" class="photo-preview">
                        <img :src="photoUrl" alt="Aperçu de la photo de la fuite" />
                        <button type="button" @click="removePhoto">Retirer</button>
                    </div>
                    <button v-else type="button" class="upload" @click="photoInput.click()">
                        <Icon name="camera" :size="27" />
                        <b>Ajouter une photo</b>
                        <span>Prendre une photo ou choisir depuis la galerie</span>
                    </button>
                    <p v-if="form.errors.photo" class="field-error">{{ form.errors.photo }}</p>

                    <!-- Position -->
                    <label>Votre position <em>Recommandée</em></label>
                    <button
                        type="button"
                        class="location-row"
                        :class="{ ok: hasPosition }"
                        :disabled="locating"
                        @click="locate"
                    >
                        <span class="mini-icon green"><Icon :name="hasPosition ? 'check' : 'pin'" /></span>
                        <span>
                            <b v-if="locating">Recherche de votre position…</b>
                            <b v-else-if="hasPosition">Position enregistrée</b>
                            <b v-else>Utiliser ma position actuelle</b>
                            <small v-if="hasPosition">
                                Précision d'environ {{ form.gps_accuracy_m }} m · appuyez pour actualiser
                            </small>
                            <small v-else>Elle aide l’équipe à trouver la fuite</small>
                        </span>
                        <Icon name="chevron" />
                    </button>
                    <p v-if="geoError" class="field-error">{{ geoError }}</p>
                    <input
                        v-model="form.location_hint"
                        class="text-input"
                        type="text"
                        maxlength="160"
                        placeholder="Repère : quartier, rue, bâtiment proche (ex. face à la pharmacie)"
                        aria-label="Repère pour retrouver la fuite"
                    />
                    <p v-if="form.errors.latitude" class="field-error">{{ form.errors.latitude }}</p>
                    <p v-if="form.errors.location_hint" class="field-error">{{ form.errors.location_hint }}</p>

                    <!-- Note vocale -->
                    <label>Décrivez la situation <em>Optionnel</em></label>
                    <div class="language">
                        <span>Langue de votre note vocale</span>
                        <div>
                            <button
                                type="button"
                                :class="{ selected: form.audio_lang === 'fr' }"
                                @click="form.audio_lang = 'fr'"
                            >Français</button>
                            <button
                                type="button"
                                :class="{ selected: form.audio_lang === 'fon' }"
                                @click="form.audio_lang = 'fon'"
                            >Fɔngbè</button>
                        </div>
                    </div>

                    <div v-if="audioUrl && !recording" class="audio-preview">
                        <audio :src="audioUrl" controls></audio>
                        <button type="button" class="link-small" @click="removeAudio">Refaire</button>
                    </div>
                    <button v-else type="button" class="record" :class="{ recording }" @click="toggleRecording">
                        <span><Icon :name="recording ? 'close' : 'mic'" :size="23" /></span>
                        <b>{{ recording ? "Arrêter l’enregistrement" : 'Enregistrer une note vocale' }}</b>
                        <small>
                            {{ recording ? `${clock} · appuyez pour terminer` : `Parlez en ${form.audio_lang === 'fon' ? 'Fɔngbè' : 'Français'} (1 min max)` }}
                        </small>
                    </button>
                    <p v-if="micError" class="field-error">{{ micError }}</p>
                    <p v-if="form.errors.audio" class="field-error">{{ form.errors.audio }}</p>

                    <button type="submit" class="primary full" :disabled="form.processing">
                        {{ form.processing ? 'Envoi en cours…' : 'Envoyer mon signalement' }}
                        <Icon name="arrow" />
                    </button>
                </div>

                <aside class="privacy">
                    <Icon name="info" />
                    <h3>Vos informations sont protégées</h3>
                    <p>
                        Nous utilisons votre position et vos médias uniquement pour traiter votre
                        signalement. Aucun compte n’est nécessaire.
                    </p>
                </aside>
            </form>
        </section>
    </PublicLayout>
</template>
