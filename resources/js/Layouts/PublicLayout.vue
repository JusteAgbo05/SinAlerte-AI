<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import Icon from '../Components/Icon.vue';

const open = ref(false);
</script>

<template>
    <main>
        <header class="topbar">
            <Link href="/" class="brand">
                <span class="brand-mark"><Icon name="drop" :size="24" /></span>
                <span>SìnAlerte <b>AI</b></span>
            </Link>

            <nav class="desktop-nav">
                <Link href="/signaler">Signaler une fuite</Link>
                <Link href="/suivi">Suivre mon signalement</Link>
                <Link href="/operateur" class="portal">
                    Espace opérateur <Icon name="arrow" :size="16" />
                </Link>
            </nav>

            <button
                class="mobile-menu"
                type="button"
                aria-label="Menu"
                :aria-expanded="open"
                @click="open = !open"
            >
                <Icon :name="open ? 'close' : 'menu'" />
            </button>
        </header>

        <nav v-if="open" class="mobile-drawer" @click="open = false">
            <Link href="/signaler">Signaler une fuite</Link>
            <Link href="/suivi">Suivre mon signalement</Link>
            <Link href="/operateur">Espace opérateur</Link>
        </nav>

        <slot />

        <footer>© 2026 SìnAlerte AI <span>•</span> Protéger chaque goutte, ensemble.</footer>
    </main>
</template>
