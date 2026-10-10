# Site membre — Hytale Assos

Espace membre de l'association **Hytale Assos**. Accès réservé aux membres : tableau de bord, serveurs de l'association, gestion de la whitelist in-game, historique des sessions de jeu, points « Points Open », invitations, liaison de comptes (Discord, Hytale) et export des données personnelles.

L'application s'appuie sur une **API core Hytale** externe (voir `docs/api.md`).

## Stack

- **Laravel 13** / **PHP 8.5** — backend
- **Inertia v3** + **Vue 3** + **Tailwind 4** — frontend (SSR désactivé)
- **PostgreSQL** — base de données
- **Sail / Docker** — environnement de développement
- **Podman / Quadlet** — déploiement de production (`container/`)
- **Fortify** — authentification (2FA, passkeys), **Socialite** — liaison Discord

## Prérequis

- PHP 8.4+ et Composer
- [Bun](https://bun.sh) (gestionnaire de paquets JS)
- Docker (pour Sail)

## Démarrage rapide

```bash
# 1. Installer les dépendances, préparer .env, migrer et compiler les assets
composer setup

# 2. Démarrer les services
vendor/bin/sail up -d
```

En développement, lancer Vite (HMR) :

```bash
vendor/bin/sail bun run dev
```

## Commandes utiles

| Commande                                 | Rôle                                      |
| ---------------------------------------- | ----------------------------------------- |
| `vendor/bin/sail artisan test --compact` | Lancer la suite de tests (Pest)           |
| `composer lint`                          | Formater le PHP (Pint)                    |
| `composer lint:check`                    | Vérifier le formatage PHP (sans corriger) |
| `vendor/bin/sail bun run check`          | Formater + linter le JS/TS/Vue            |
| `vendor/bin/sail bun run build`          | Compiler les assets de production         |
| `composer types:check`                   | Analyse statique PHP (Larastan)           |
| `composer ci:check`                      | Contrôle complet (équivalent CI)          |

Un **hook pre-commit** formate et lint automatiquement les fichiers modifiés (JS/Vue via Vite+, PHP via Pint). Il s'installe avec `composer setup` — ou manuellement : `bun x vp hooks enable`.

## Configuration

Toute la configuration passe par `.env` (modèle : `.env.example`). Valeurs clés :

| Variable                      | Description                                                                                                                         |
| ----------------------------- | ----------------------------------------------------------------------------------------------------------------------------------- |
| `SCHOOL_EMAIL_DOMAINS`        | Domaines email des membres internes (séparés par des virgules). Vide = inscriptions sur invitation uniquement.                      |
| `MEMBERS_INVITATION_LIMIT`    | Nombre maximal d'invitations actives par membre interne.                                                                            |
| `PASSKEYS_USER_HANDLE_SECRET` | Secret dédié aux passkeys. **Requis en production** (générer : `php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"`). |
| `HYTALE_API_*`                | Connexion à l'API core Hytale (`HYTALE_API_MOCK=true` active le mock en mémoire).                                                   |
| `DISCORD_*`                   | Liaison de comptes Discord (OAuth).                                                                                                 |
| `MAIL_*`                      | Envoi d'emails (vérification, réinitialisation). `smtp` en production, `log` en dev.                                                |

## Documentation interne

- [`docs/api.md`](docs/api.md) — contrat de l'API core Hytale
- [`docs/account-linking.md`](docs/account-linking.md) — liaison de comptes (Discord, Hytale)
- [`docs/audit.md`](docs/audit.md) — rapport d'audit sécurité/qualité et suivi des remédiations

## Conventions

- [`AGENTS.md`](AGENTS.md) — règles de travail (outillage, Sail, tests…)
- [`.ai/rules/`](.ai/rules/) — règles de commits, de tests et de conteneurisation
