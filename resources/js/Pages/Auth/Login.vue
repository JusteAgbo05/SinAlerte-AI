<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import PublicLayout from '../../Layouts/PublicLayout.vue';
import Icon from '../../Components/Icon.vue';

const form = useForm({ email: '', password: '', remember: false });

function submit() {
    form.post('/connexion', {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <PublicLayout>
        <Head title="Connexion opérateur" />

        <section class="auth-page">
            <form class="auth-card" @submit.prevent="submit">
                <span class="eyebrow"><span></span> Espace opérateur</span>
                <h1>Connexion</h1>
                <p>Réservé aux équipes habilitées.</p>

                <label for="email">Adresse e-mail</label>
                <input
                    id="email"
                    v-model="form.email"
                    class="text-input"
                    type="email"
                    autocomplete="username"
                    required
                    autofocus
                />
                <p v-if="form.errors.email" class="field-error">{{ form.errors.email }}</p>

                <label for="password">Mot de passe</label>
                <input
                    id="password"
                    v-model="form.password"
                    class="text-input"
                    type="password"
                    autocomplete="current-password"
                    required
                />
                <p v-if="form.errors.password" class="field-error">{{ form.errors.password }}</p>

                <label class="check-row">
                    <input v-model="form.remember" type="checkbox" /> Rester connecté
                </label>

                <button type="submit" class="primary full" :disabled="form.processing">
                    Se connecter <Icon name="arrow" />
                </button>
            </form>

            <Link href="/" class="link-btn">Retour à l’accueil</Link>
        </section>
    </PublicLayout>
</template>
