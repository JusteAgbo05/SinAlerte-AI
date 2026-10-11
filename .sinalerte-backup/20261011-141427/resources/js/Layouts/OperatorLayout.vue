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

            <Link href="/operateur" class="side-link active">
                <Icon name="alert" /> Signalements
                <b v-if="count !== null">{{ count }}</b>
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

            <slot />
        </div>
    </section>
</template>
