# SìnAlerte AI — maquette interactive

Cette maquette React présente le parcours MVP décrit dans le TDR :

- Accueil citoyen et présentation de la valeur du service
- Création d'un signalement (photo, position, note vocale français/Fongbè)
- Confirmation et suivi de l'intervention
- Tableau de bord opérateur avec score IA explicable, carte, alertes et affectation d'équipe

## Lancer le projet

Prérequis : Node.js 20 ou une version plus récente.

```bash
npm install
npm run dev
```

Ouvrez ensuite l'adresse indiquée dans le terminal (généralement `http://localhost:5173`).

## Construire pour le déploiement Vercel

```bash
npm run build
```

Vercel détecte automatiquement Vite : importez le dossier du projet, ou poussez-le sur GitHub puis importez le dépôt dans Vercel. Les données, analyses IA, carte et enregistrement sont illustratifs dans cette maquette et ne font pas encore appel à une API.
