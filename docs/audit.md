# Rapport d'audit — sécurité, qualité et gouvernance

Date : 9 octobre 2026 · Périmètre : code applicatif, configuration, règles
projet, conteneurisation, CI · Méthode : analyse statique en lecture seule.

> [!NOTE]
> Ce document sert de feuille de route de remédiation : chaque constat
> actionnable porte un statut (⬜ à traiter / ✅ traité) mis à jour au fil des
> correctifs.

## Sommaire

1. [Vue d'ensemble du projet](#1-vue-densemble-du-projet)
2. [Gouvernance et règles projet](#2-gouvernance-et-règles-projet)
3. [Audit de sécurité](#3-audit-de-sécurité)
4. [Qualité, architecture et maintenance](#4-qualité-architecture-et-maintenance)
5. [Plan d'action priorisé](#5-plan-daction-priorisé)

---

## 1. Vue d'ensemble du projet

| Aspect | Détail |
| --- | --- |
| Projet | Site membre de l'association « Hytale Assos » (pas de wiki ni de liste publique) |
| Stack | Laravel 13.32 / PHP 8.5 · Inertia v3 + Vue 3.5 · Tailwind 4 · PostgreSQL |
| Auth | Fortify (2FA + passkeys WebAuthn), Socialite/Discord, inscription fermée (email scolaire interne + invitations externes) |
| Métier | Dashboard membre, serveurs, whitelist in-game, sessions de jeu, « Points Open », export RGPD — via l'API core Hytale externe (clé API + signature HMAC, voir `docs/api.md`) |
| Tests | Pest — 36 fichiers, ~236 cas |
| Qualité | Pint, Larastan niveau 7, CI GitHub (actions épinglées par SHA), script `ci:check` |
| Déploiement | Podman/Quadlet rootless en prod (hardened), Sail en dev |

Architecture notable : découpage « ports & adapters » autour de
`HytaleApiClient` (transports Fake/HTTP/Cache interchangeables, décorateur de
cache), DTOs typés, chiffrement des données personnelles au repos avec index
aveugles HMAC (emails, IDs Discord/Hytale, noms, statuts).

## 2. Gouvernance et règles projet

### Règles existantes

| Fichier | Contenu |
| --- | --- |
| `AGENTS.md` | Règles Laravel Boost, Sail, Pest, Inertia, Wayfinder, Pint |
| `.ai/rules/index.md` | Index des règles par globs de chemins |
| `.ai/rules/commits.md` | Conventional Commits en anglais, branches `<type>/<slug>` depuis `develop`, commits atomiques, Pint + tests avant commit |
| `.ai/rules/tests.md` | Tests écrits par un sous-agent impartial briefé par spec seule (interdiction de lire l'implémentation) |
| `.ai/rules/containers.md` | Images épinglées par digest, runtime non-root durci, secrets Podman, networks pair-à-pair |

### Constat

✅ Les règles sont **cohérentes avec la pratique réelle** : historique git
conforme au format Conventional Commits, Containerfile conforme aux règles
conteneurs, couverture de tests réelle. Aucun écart gouvernance/pratique
détecté.

---

## 3. Audit de sécurité

### Verdict

**Aucune faille critique.** La posture est nettement au-dessus de la moyenne ;
les points à traiter relèvent du durcissement.

### 3.1 Points forts (à préserver)

- **Données personnelles chiffrées au repos** avec blind indexes HMAC-SHA256
  clé `APP_KEY` : casts `EncryptedWithHash` / `EncryptedEmailWithHash`,
  provider `HashedEloquentUserProvider`, broker de reset `HashedEmailTokenRepository`
  (l'email n'est jamais stocké en clair dans les tokens de reset).
- Politique de mot de passe forte en production : 12 caractères, mixte,
  chiffres, symboles, règle `uncompromised` (`app/Providers/AppServiceProvider.php`).
- 2FA avec confirmation **et** mot de passe (`config/fortify.php` :
  `confirm => true, confirmPassword => true`) ; passkeys protégées par mot de passe.
- **Invalidation des autres sessions** et rotation du remember token au
  changement de mot de passe (`SecurityController`), couvert par
  `tests/Feature/PasswordSessionInvalidationTest.php`.
- Rate limiting granulaire par endpoint d'écriture : login 5/min, 2FA 5/min,
  passkeys 10/min, invitations 10/min, whitelist 10/min, export RGPD 6/min,
  OAuth 10/min, mot de passe 6/min, settings 30/min.
- Validation systématique par Form Requests — **aucun `$request->all()`** ;
  mises à jour via `$request->validated()`.
- Autorisation : `InvitationPolicy`, vérification de propriété avant
  suppression d'une entrée de whitelist.
- Flux Socialite/Discord stateful (protection CSRF par `state`), provider en
  liste blanche, détection de collision de liaison.
- Trusted proxies configurés avec garde-fous documentés et testés
  (`config/trustedproxy.php`, `tests/Feature/TrustedProxyTest.php`).
- `DB::prohibitDestructiveCommands` en production.
- **Aucun secret commité** : `.env` jamais présent dans l'historique git
  (vérifié), `.gitignore` complet, secrets de prod = secrets Podman.
- Outils sensibles (pail, boost, sail, larastan) confinés en `require-dev`.
- XSS : un seul `v-html` (`resources/js/components/TwoFactorSetupModal.vue`)
  sur du SVG généré côté serveur par Fortify — sûr.
- `public/` propre : aucun dump, aucun PHP superflu, pas de symlink storage.
- Tests de sécurité réellement présents (18 fichiers couvrant auth, rate
  limiting, confidentialité, injection d'URL serveur, fuite de props Inertia…).

### 3.2 🟠 Constats importants

#### SEC-1 — Absence d'en-têtes de sécurité HTTP ✅ *(traité le 9 octobre 2026)*

**Correction livrée** : middleware `App\Http\Middleware\AddSecurityHeaders`
ajoutant `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY` et
`Referrer-Policy: strict-origin-when-cross-origin` à toutes les réponses de la
pile `web`, enregistré dans `bootstrap/app.php`, couvert par
`tests/Feature/SecurityHeadersTest.php` (page publique + redirection).
**Reste à faire** : une CSP compatible Inertia/Vite (le `v-html` 2FA
s'accommode d'une CSP restreinte) — traitée comme sujet séparé.

*Constat initial :* aucun en-tête de sécurité côté application : pas de CSP,
`X-Content-Type-Options`, `X-Frame-Options` / `frame-ancestors`,
`Referrer-Policy`, HSTS (recherche sur `app/`, `config/`, `bootstrap/` : rien).
La terminaison TLS/proxy peut en fournir, mais rien ne le garantit ici.

#### SEC-2 — `PASSKEYS_USER_HANDLE_SECRET` replie sur `APP_KEY` ✅ *(traité le 9 octobre 2026)*

**Correction livrée** :
- `config/fortify.php` : le repli explicite sur `APP_KEY` est supprimé
  (`env('PASSKEYS_USER_HANDLE_SECRET')` seul).
- `FortifyServiceProvider` : garde-fou — en production, l'application
  **refuse de démarrer** (`RuntimeException`) si le secret dédié est absent,
  au lieu de laisser Fortify retomber silencieusement sur `APP_KEY`.
- Variable documentée dans `.env.example` (avec commande de génération) et
  `container/website.env.example` ; renseignée dans le `.env` local et
  `phpunit.xml`.
- Secret Podman `hytale-website-passkeys-user-handle` câblé dans
  `container/systemd/hytale-website.container`.
- Couvert par `tests/Feature/PasskeysSecretTest.php`.

**Action déploiement requise** : provisionner
`podman secret create hytale-website-passkeys-user-handle ...` sur l'hôte
avant le prochain déploiement, sinon l'unité ne démarre plus (comportement
voulu).

*Constat initial :* `config/fortify.php` — si la variable dédiée est absente,
le secret des user handles WebAuthn tombe sur `APP_KEY`. Une fuite d'`APP_KEY`
compromettrait alors simultanément le chiffrement au repos, les blind indexes
**et** les passkeys.

### 3.3 🟡 Constats mineurs

| # | Statut | Constat | Détail / remédiation |
| --- | --- | --- | --- |
| SEC-3 | ✅ | Pas de throttle explicite sur `POST /register` | **Traitée le 9 octobre 2026.** Limiteurs dédiés dans `FortifyServiceProvider` : `registration` (5/min/IP) sur `register.store` et `password-email` (6/min par couple email+IP) sur `password.email`, attachés aux routes Fortify après leur enregistrement (avec `refreshNameLookups()` — la table de lookup n'est pas encore à jour dans le callback `booted`). Couvert par `tests/Feature/Auth/GuestEndpointThrottlingTest.php` (429 après épuisement, isolation des buckets). |
| SEC-4 | ✅ | Drapeaux `is_internal` / `is_external` dans le `$fillable` de `User` | **Traitée le 9 octobre 2026.** Les drapeaux de privilège sont retirés du fillable et posés par `forceFill` dans `CreateNewUser` (le statut dérive uniquement du domaine email). `is_public` reste fillable : c'est une préférence utilisateur (visibilité classement) validée `boolean` par `ProfileUpdateRequest`, pas un privilège — choix documenté dans `User.php`. Couvert par `tests/Feature/MemberStatusProtectionTest.php` (statut non forgeable à l'inscription ni via le profil). |
| SEC-5 | ✅ | Colonnes `*_hash` (blind indexes) exposées via la prop partagée `auth.user` | **Traitée le 9 octobre 2026.** Les quatre colonnes de hachage (`email_hash`, `discord_id_hash`, `hytale_id_hash`, `school_email_hash`) sont ajoutées au `#[Hidden]` de `User` (`Invitation` masquait déjà `email_hash`) — les blind indexes ne quittent plus jamais le serveur. Couvert par `tests/Feature/HashColumnsHiddenTest.php`, incluant une vérification générique « aucune clé `_hash` » résistante à l'ajout futur de colonnes. |
| SEC-6 | ✅ | `AUTH_PASSWORD_TIMEOUT` de 3 h | **Traitée le 9 octobre 2026.** Fenêtre ramenée à **1 h** : défaut `config/auth.php` passé à 3600 s, variable documentée dans `.env.example`. Couvert par `tests/Feature/PasswordConfirmationTimeoutTest.php` (borne ≤ 3600 s épinglée). |
| SEC-7 | ⬜ | Mot de passe CI trivial et ports exposés en dev | `.github/workflows/tests.yml` (`POSTGRES_PASSWORD: password`) — acceptable car CI éphémère ; `compose.yaml` expose PG 5432 / Redis 6379 côté hôte — standard Sail, à ne pas reproduire en prod. |
| SEC-8 | ⬜ | Audits de dépendances non exécutés lors de l'audit | Lancer `composer audit` et `bun audit` périodiquement ; Dependabot déjà configuré (`.github/dependabot.yml`). Vérifier aussi que l'image de prod est construite avec `composer install --no-dev` (exclure pail/sail/boost). |
| SEC-9 | ⬜ | Pas de test fonctionnel passkeys/WebAuthn | Couverture déléguée à Fortify amont ; acceptable, à noter. |
| SEC-10 | ⬜ | `laravel/chisel` ^0.1 en production | Package récent et peu répandu — garder sous surveillance des mises à jour. |

### 3.4 Points vérifiés sans anomalie

- Routes applicatives : toutes sous `auth` (+ `verified` où requis) ; seules
  `GET /` (redirect login), `/.well-known/passkey-endpoints` et `/up` sont
  publiques — toutes anodines.
- Aucun secret en dur dans le code ; tout passe par `env()`.
- CSRF : stack `web` Laravel par défaut ; cookies session `http_only`,
  `same_site=lax`, `secure` piloté par env ; cookies `appearance` /
  `sidebar_state` exclus du chiffrement (valeurs cosmétiques).
- Pas de `config/cors.php` → pas de CORS ouvert ; pas de `routes/api.php`.
- Pas de debugbar / telescope / horizon en dépendances.
- Signature HMAC sortante propre (`app/Hytale/Support/ApiSigner.php` :
  hex HMAC-SHA256 sur `timestamp.body`, tolérance d'horloge configurable).
- `APP_DEBUG` par défaut `false`, forcé par env.
- Fichiers générés Wayfinder git-ignorés.
- Env de test isolé (`phpunit.xml` : base dédiée, `BCRYPT_ROUNDS=4`, mock Hytale).

---

## 4. Qualité, architecture et maintenance

### 4.1 Points forts

- PHP moderne exemplaire : promotion de constructeurs, `readonly`, enums,
  attributs `#[Fillable]` / `#[Hidden]`, types explicites, array-shapes.
- Domaine Hytale bien délimité (`app/Hytale/` : contrat unique, transports
  interchangeables, DTOs) ; docs de compromis *in situ* d'excellente facture.
- Wayfinder utilisé partout côté front (aucune URL en dur), conventions
  homogènes, TypeScript strict (`vue-tsc`), i18n maison fr/en typé.
- CI épinglée par SHA, pipeline `ci:check` reproductible en local.
- Docs techniques utiles : `docs/api.md` (contrat core), `docs/account-linking.md`.

### 4.2 Constats à traiter

| # | Sévérité | Statut | Constat | Fichier(s) |
| --- | --- | --- | --- | --- |
| QUA-1 | 🟠 | ⬜ | **README vide** (contient littéralement « test ») — aucune doc d'onboarding | `README.md` |
| QUA-2 | 🟠 | ⬜ | ~300 lignes de traductions **mortes** (landing `hero.*`, `about.*`, `offer.*`, `event.*`, `join.*`, `footer.*`, fr+en) — aucune page ne les référence ; `/` redirige vers `login` | `resources/js/lang/index.ts` |
| QUA-3 | 🟡 | ⬜ | Résidus du starter kit Vue : `ExampleTest` ×2, helper `something()` vide, expectation `toBeOne` inutilisée, commande `inspire`, `name: "laravel/vue-starter-kit"` | `tests/{Unit,Feature}/ExampleTest.php`, `tests/Pest.php`, `routes/console.php`, `composer.json` |
| QUA-4 | 🟡 | ⬜ | `database/database.sqlite` versionné alors que la stack est PostgreSQL ; défaut `sqlite` dans la config | `database/database.sqlite`, `config/database.php` |
| QUA-5 | 🟡 | ⬜ | Pas de pre-commit hook (Pint / lint JS) malgré l'outillage présent — tout repose sur la CI | — |
| QUA-6 | 🟡 | ⬜ | Pages placeholder `Maps` / `Mods` publiées dans la navigation | `app/Http/Controllers/{Map,Mod}Controller.php` |
| QUA-7 | 🟡 | ⬜ | `HytaleData::forMember` : 3 appels séquentiels non paginés ; `WhitelistController` re-fetch la collection pour l'ownership — acceptable à cette échelle, à surveiller avec la croissance | `app/Hytale/HytaleData.php` |
| QUA-8 | 🟡 | ⬜ | `RefreshDatabase` répété dans chaque fichier de test au lieu d'être factorisé | `tests/Pest.php` (ligne commentée) |

### 4.3 Points d'information (aucune action requise)

- Aucun test frontend (vitest/playwright absent) — couverture entièrement PHP.
- i18n maison (détection navigateur + localStorage) — cohérent, à documenter
  si le besoin grandit (`resources/js/composables/useLocale.ts`).
- `HandleInertiaRequests` partage le modèle `User` complet (champs déchiffrés)
  en prop `auth.user` — conforme à l'approche Inertia, à garder en tête si des
  champs sensibles sont ajoutés (voir aussi SEC-5).

---

## 5. Plan d'action priorisé

| Priorité | Éléments | Nature |
| --- | --- | --- |
| P1 | SEC-1 (en-têtes HTTP) ✅ — reste la CSP en sujet séparé | Sécurité — durcissement |
| P2 | SEC-2 (secret passkeys dédié) ✅, SEC-4 (fillable) ✅, SEC-5 (hidden hashes) ✅ | Sécurité — réduction de surface |
| P3 | SEC-3 (throttle register) ✅, SEC-6 (timeout mot de passe) ✅ | Sécurité — réglages |
| P4 | QUA-1 (README), QUA-3 (résidus starter), QUA-4 (sqlite) | Nettoyage |
| P5 | QUA-2 (traductions mortes), QUA-6 (placeholders), SEC-8 (audits deps) | Hygiène régulière |

> Règle de suivi : marquer ✅ chaque élément traité, avec la date et le
> commit/PR associé. Refaire un audit complet après la vague P1–P2.
