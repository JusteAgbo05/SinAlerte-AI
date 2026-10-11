<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '../Layouts/PublicLayout.vue';
import Icon from '../Components/Icon.vue';

const steps = [
    { n: '01', title: 'Vous signalez', text: 'Photo, position et note vocale, sans formulaire compliqué.', icon: 'camera' },
    { n: '02', title: 'Nous priorisons', text: "Le système évalue l'urgence : gravité, photo, signalements voisins.", icon: 'chart' },
    { n: '03', title: 'Ils interviennent', text: 'Une équipe est affectée et vous pouvez suivre la suite avec votre code.', icon: 'wrench' },
];
</script>

<template>
    <PublicLayout>
        <Head title="Signaler une fuite d'eau" />

        <section class="hero">
            <div class="hero-copy">
                <div class="eyebrow"><span></span> Ensemble, préservons l'eau</div>
                <h1>Une fuite signalée.<br /><em>Une action lancée.</em></h1>
                <p>
                    Une plateforme simple pour signaler les fuites d’eau et aider les équipes à
                    intervenir là où c’est le plus urgent.
                </p>
                <div class="hero-actions">
                    <Link href="/signaler" class="primary">
                        Signaler une fuite <Icon name="arrow" />
                    </Link>
                    <Link href="/operateur" class="text-button">
                        Voir l’espace opérateur <Icon name="chevron" :size="17" />
                    </Link>
                </div>
                <div class="trust">
                    <div><b>Français &amp; Fɔngbè</b><span>Votre voix compte</span></div>
                    <i></i>
                    <div><b>Photo &amp; position</b><span>Un geste suffit</span></div>
                </div>
            </div>

            <div class="hero-visual" aria-hidden="true">
                <div class="halo"></div>
                <div class="leak-photo">
                    <div class="photo-label"><span class="pulse"></span> Signalement actif</div>
                    <div class="water-ripple r1"></div>
                    <div class="water-ripple r2"></div>
                    <div class="pipe"></div>
                    <div class="puddle"></div>
                </div>
                <div class="floating-card score">
                    <span class="mini-icon coral"><Icon name="alert" :size="16" /></span>
                    <div><small>Priorité automatique</small><strong>Intervention rapide</strong></div>
                </div>
                <div class="floating-card loc">
                    <span class="mini-icon blue"><Icon name="pin" :size="16" /></span>
                    <div><small>Localisé</small><strong>Cotonou</strong></div>
                </div>
            </div>
        </section>

        <section class="how">
            <div class="section-intro">
                <span class="eyebrow"><span></span> Simple et utile</span>
                <h2>Du signalement à la réparation</h2>
            </div>
            <div class="steps">
                <article v-for="s in steps" :key="s.n" class="step">
                    <div class="step-head"><span>{{ s.n }}</span><Icon :name="s.icon" /></div>
                    <h3>{{ s.title }}</h3>
                    <p>{{ s.text }}</p>
                </article>
            </div>
        </section>
    </PublicLayout>
</template>
