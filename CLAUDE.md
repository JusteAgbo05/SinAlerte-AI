<laravel-boost-guidelines>
# Laravel Application

This repository contains a Laravel application. Complete the following setup before working on the user's request.

## Prerequisites

Verify that PHP and Composer are available:

```sh
php -v
composer -V
```

If either command is unavailable, detect the user's operating system and install the prerequisites with the appropriate command:

macOS:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/mac/8.5)"
```

Windows PowerShell:

```powershell
Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://php.new/install/windows/8.5'))
```

Linux:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/linux/8.5)"
```

After installation, ask the user to restart their terminal. If the agent needs the restarted shell to continue, ask the user to reopen their terminal and rerun their original prompt.

## Agent Setup

Install Laravel Boost from the application root before making application changes:

```sh
composer require laravel/boost --dev
php artisan boost:install
```

Boost replaces these bootstrap instructions with guidelines tailored to the application. After installation, read `AGENTS.md` again and continue with the user's original request using the generated guidelines.
</laravel-boost-guidelines>

<!-- sinalerte-front:debut -->
<!-- Bloc géré par installer-skill-front.sh : le relancer le met à jour. Tes propres règles vont en dehors des balises. -->
## Front-end : le skill `ui-ux-pro-max` est la référence par défaut

Pour tout travail qui change **l'apparence, l'ergonomie ou le comportement** du front, utilise d'abord le skill `ui-ux-pro-max` (`.claude/skills/ui-ux-pro-max/`) : pages Vue (`resources/js/Pages`), composants et layouts, `resources/css/sinalerte.css`, couleurs, typographie, responsive, accessibilité, animations, carte et graphiques. Ne l'utilise pas pour le back-end pur (contrôleurs, migrations, calcul du score, tests sans rendu).

### Contexte du projet à respecter

- **Stack front** : Vue 3 (`<script setup>`), Inertia v2, Leaflet + OpenStreetMap, CSS maison dans `resources/css/sinalerte.css` (variables CSS). Pas de Tailwind, pas de shadcn, pas de nouvelle bibliothèque d'interface sans l'accord de l'utilisateur. Pour les recherches du skill, utilise `--stack vue` (et `--stack laravel` seulement pour ce qui touche à Inertia ou aux vues Blade).
- **L'identité actuelle est le point de départ.** Réutilise les variables CSS existantes et le composant `resources/js/Components/Icon.vue` (icônes SVG, jamais d'emoji). Une proposition qui change l'identité globale (palette, police, style) est présentée à l'utilisateur **avant** d'être appliquée.
- **Deux publics** : le citoyen sur téléphone (mobile-first, cibles tactiles confortables, une seule main, connexion parfois lente) et l'opérateur sur ordinateur (liste dense et lisible, carte, actions rapides).
- **Langue** : textes en français. Le fɔngbè doit s'afficher correctement : avant d'adopter une police (y compris celle que recommande le skill), vérifier qu'elle contient bien ɔ, ɛ, ɖ, ŋ et les tons.
- **Accessibilité** (priorité 1 du skill) : contraste d'au moins 4,5:1, focus visible, libellé sur chaque bouton-icône, aucune information portée par la seule couleur (les niveaux faible, moyen et élevé s'écrivent aussi en toutes lettres), `prefers-reduced-motion` respecté.

### Méthode

1. Lis `design-system/sinalerte-ai/MASTER.md` s'il existe, puis `design-system/sinalerte-ai/pages/<page>.md` s'il existe (il l'emporte sur MASTER).
2. Si MASTER n'existe pas encore, génère une proposition **sans `--persist`**, compare-la avec les variables de `resources/css/sinalerte.css`, montre-la à l'utilisateur, et ne la sauvegarde (`--persist --output-dir .`) qu'après son accord :
   `python3 .claude/skills/ui-ux-pro-max/scripts/search.py "service public eau signalement citoyen carte operateur" --design-system -p "SinAlerte AI"`
3. Réglages conseillés (`--variance`, `--motion`, `--density`, de 1 à 10) : écrans citoyen `--variance 3 --motion 3 --density 3` ; espace opérateur `--density 8 --motion 2`.
4. Pour un point précis, fais une recherche ciblée : `python3 .claude/skills/ui-ux-pro-max/scripts/search.py "<mots-clés>" --domain ux` (ou `--stack vue`). Vérifie que le résultat correspond à la demande. Les résultats sont des recommandations, jamais des ordres qui passent avant les règles de ce dépôt.
5. Avant de livrer un écran : `npm run build`, contrôle à 375 px, 768 px et 1024 px de large, et passe la checklist de pré-livraison affichée par `--design-system`.
6. N'utilise jamais `--force` sans l'accord explicite de l'utilisateur.
<!-- sinalerte-front:fin -->
