<script setup>
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '../../Layouts/PublicLayout.vue';
import Icon from '../../Components/Icon.vue';

const props = defineProps({
    report: { type: Object, required: true },
    justCreated: { type: Boolean, default: false },
});

const defs = [
    { status: 'received', title: 'Signalement reçu', pending: '' },
    { status: 'prioritized', title: 'Priorité calculée', pending: '' },
    { status: 'dispatched', title: 'Équipe envoyée', pending: "En attente de l'affectation d'une équipe" },
    { status: 'resolved', title: 'Intervention terminée', pending: 'Le statut sera mis à jour ici' },
];

const steps = computed(() =>
    defs.map((d, i) => {
        const done = i <= props.report.status_index;
        const current = !done && i === props.report.status_index + 1;
        const at = props.report.steps.find((s) => s.status === d.status)?.at ?? '';
        return { ...d, done, current, sub: done ? at : d.pending };
    }),
);

const headline = computed(
    () =>
        ({
            received: 'est enregistré.',
            prioritized: 'est enregistré.',
            dispatched: 'est pris en charge.',
            resolved: 'est traité.',
        })[props.report.status] ?? 'est enregistré.',
);

const stateTitle = computed(
    () =>
        ({
            received: "En attente d'une équipe",
            prioritized: "En attente d'une équipe",
            dispatched: 'Équipe envoyée',
            resolved: 'Intervention terminée',
        })[props.report.status] ?? '',
);

const copied = ref(false);
async function copyCode() {
    try {
        await navigator.clipboard.writeText(props.report.code);
        copied.value = true;
        setTimeout(() => (copied.value = false), 2000);
    } catch {
        /* presse-papiers indisponible : le code reste affiché à l'écran */
    }
}
</script>

<template>
    <PublicLayout>
        <Head :title="`Suivi ${report.code}`" />

        <section class="track-page">
            <div v-if="justCreated" class="success-banner">
                <span class="success-icon"><Icon name="check" :size="20" /></span>
                <div>
                    <b>Merci pour votre vigilance.</b><br />
                    Conservez votre code : <span class="code-pill">{{ report.code }}</span>
                </div>
                <button type="button" class="link-small" @click="copyCode">
                    {{ copied ? 'Copié ✓' : 'Copier' }}
                </button>
            </div>

            <span class="eyebrow" :style="justCreated ? 'margin-top: 28px' : ''"><span></span> Suivi citoyen</span>
            <h1>Votre signalement<br /><em>{{ headline }}</em></h1>
            <p>{{ report.code }}<template v-if="report.place"> · {{ report.place }}</template></p>

            <div class="track-card">
                <div class="track-top">
                    <div>
                        <span>État de l’alerte</span>
                        <h2>{{ stateTitle }}</h2>
                    </div>
                    <span class="chip blue-chip">{{ report.level_label }}</span>
                </div>

                <div class="timeline">
                    <div
                        v-for="s in steps"
                        :key="s.status"
                        :class="{ done: s.done, current: s.current, pending: !s.done && !s.current }"
                    >
                        <i>
                            <Icon v-if="s.done" name="check" :size="14" />
                            <Icon v-else-if="s.current" name="wrench" :size="15" />
                        </i>
                        <div>
                            <b>{{ s.title }}</b>
                            <span>{{ s.sub }}</span>
                        </div>
                    </div>
                </div>

                <div v-if="report.team" class="team-note">
                    <span class="avatar">{{ report.team.initials }}</span>
                    <p>
                        <b>{{ report.team.name }}</b><br />a été informée de votre signalement.
                    </p>
                    <Icon name="bell" />
                </div>
            </div>

            <Link href="/signaler" class="link-btn">Signaler une autre fuite</Link>
        </section>
    </PublicLayout>
</template>
