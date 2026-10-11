<script setup>
import { computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import Icon from '../Components/Icon.vue';

defineProps({
    // Pastille à côté de "Signalements" (nombre de signalements ouverts)
    count: { type: Number, default: null },
});

const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);
// Page "Équipes" active ? (l'URL Inertia commence par le chemin de la page)
const onTeams = computed(() => (page.url ?? '').startsWith('/operateur/equipes'));
const initials = computed(() =>
    (user.value?.name ?? '?')
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((w) => w[0].toUpperCase())
        .join(''),
);

function logout() {
    router.post('/deconnexion');
}
</script>

<template>
    <section class="operator">
        <aside class="side">
            <Link href="/" class="brand">
                <span class="brand-mark"><Icon name="drop" :size="22" /></span>
                <span>SìnAlerte <b>AI</b></span>
            </Link>

            <div class="operator-label">ESPACE OPÉRATEUR</div>

            <Link href="/operateur" class="side-link" :class="{ active: !onTeams }">
                <Icon name="alert" /> Signalements
                <b v-if="count !== null">{{ count }}</b>
            </Link>
            <Link href="/operateur/equipes" class="side-link" :class="{ active: onTeams }">
                <Icon name="users" /> Équipes
            </Link>

            <div class="side-bottom">
                <Link href="/" class="side-link"><Icon name="info" /> Site citoyen</Link>
                <div class="profile">
                    <span>{{ initials }}</span>
                    <div>
                        <b>{{ user?.name }}</b>
                        <small>Opérateur</small>
                    </div>
                    <button class="icon-btn" type="button" title="Se déconnecter" @click="logout">
                        <Icon name="logout" :size="18" />
                    </button>
                </div>
            </div>
        </aside>

        <div class="dash-main">
            <div class="op-mobilebar">
                <Link href="/" class="brand">
                    <span class="brand-mark"><Icon name="drop" :size="22" /></span>
                    <span>SìnAlerte <b>AI</b></span>
                </Link>
                <button class="icon-btn" type="button" title="Se déconnecter" @click="logout">
                    <Icon name="logout" :size="20" />
                </button>
            </div>

            <!-- Navigation mobile (la barre latérale est masquée sur téléphone) -->
            <nav class="op-mobilenav">
                <Link href="/operateur" :class="{ active: !onTeams }">Signalements</Link>
                <Link href="/operateur/equipes" :class="{ active: onTeams }">Équipes</Link>
            </nav>

            <slot />
        </div>
    </section>
</template>
