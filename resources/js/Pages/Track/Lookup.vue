<script setup>
import { computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import PublicLayout from '../../Layouts/PublicLayout.vue';
import Icon from '../../Components/Icon.vue';

const form = useForm({ code: '' });
const page = usePage();

// Erreur de ce formulaire, ou erreur venant d'une redirection (code introuvable dans l'URL).
const error = computed(() => form.errors.code || page.props.errors?.code || '');

function submit() {
    form.post('/suivi');
}
</script>

<template>
    <PublicLayout>
        <Head title="Suivre mon signalement" />

        <section class="track-page">
            <span class="eyebrow"><span></span> Suivi citoyen</span>
            <h1>Où en est<br /><em>votre signalement ?</em></h1>
            <p>Saisissez le code reçu après votre signalement (par exemple SA-K7M2QX).</p>

            <form class="lookup-form" @submit.prevent="submit">
                <input
                    v-model="form.code"
                    class="text-input"
                    type="text"
                    autocomplete="off"
                    autocapitalize="characters"
                    placeholder="SA-XXXXXX"
                    aria-label="Code de suivi"
                />
                <p v-if="error" class="field-error">{{ error }}</p>
                <button type="submit" class="primary full" :disabled="form.processing || !form.code.trim()">
                    Suivre mon signalement <Icon name="arrow" />
                </button>
            </form>

            <Link href="/signaler" class="link-btn">Signaler une fuite</Link>
        </section>
    </PublicLayout>
</template>
