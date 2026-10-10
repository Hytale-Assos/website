# Modèle de rapport d'audit — sécurité, qualité et gouvernance

> À recopier pour chaque audit. Remplacer les `…`, attribuer un statut
> (⬜ / 🔶 / ✅) et numéroter les constats (`SEC-1`, `QUA-1`…). Cocher la
> checklist en fin de document au fil de la revue.

Date : `…` · Périmètre : `…` · Méthode : `…`

## Légende

| Symbole | Signification |
| --- | --- |
| Statut `⬜` / `🔶` / `✅` | à traiter / partiel / traité |
| Sévérité `🔴` / `🟠` / `🟡` | critique / important / mineur |

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
| Projet | `…` |
| Stack | `…` |
| Auth | `…` |
| Métier | `…` |
| Tests | `…` |
| Qualité | `…` |
| Déploiement | `…` |

Architecture notable : `…`

---

## 2. Gouvernance et règles projet

### Règles existantes

| Fichier | Contenu |
| --- | --- |
| `AGENTS.md` | `…` |
| `.ai/rules/index.md` | `…` |
| `.ai/rules/commits.md` | `…` |
| `.ai/rules/tests.md` | `…` |
| `.ai/rules/containers.md` | `…` |

### Constat

`…` — cohérence entre les règles et la pratique réelle, écarts éventuels.

---

## 3. Audit de sécurité

### Verdict

`…` — synthèse globale : fautes critiques, posture, thème (durcissement vs correction).

### 3.1 Points forts (à préserver)

- `…`

### 3.2 🔴🟠 Constats critiques / importants

Chaque constat important mérite sa propre sous-section :

```markdown
#### SEC-x — <titre> <statut>

**Correction livrée** : `…`
**Reste à faire** : `…`

*Constat initial :* `…`
```

### 3.3 🟡 Constats mineurs

| # | Statut | Constat | Détail / remédiation |
| --- | --- | --- | --- |
| SEC-`n` | ⬜ | `…` | `…` |

### 3.4 Points vérifiés sans anomalie

- `…`

---

## 4. Qualité, architecture et maintenance

### 4.1 Points forts

- `…`

### 4.2 Constats à traiter

| # | Sévérité | Statut | Constat | Fichier(s) |
| --- | --- | --- | --- | --- |
| QUA-`n` | 🟡 | ⬜ | `…` | `…` |

### 4.3 Points d'information (aucune action requise)

- `…`

---

## 5. Plan d'action priorisé

| Priorité | Éléments | Nature |
| --- | --- | --- |
| P1 | `…` | Sécurité — durcissement |
| P2 | `…` | Sécurité — réduction de surface |
| P3 | `…` | Sécurité — réglages |
| P4 | `…` | Nettoyage |
| P5 | `…` | Hygiène régulière |

> Règle de suivi : marquer ✅ chaque élément traité avec la date et le
> commit/PR associé. Refaire un audit complet après la vague P1–P2.

---

## Annexe — Checklist des dimensions à auditer

### Sécurité

- [ ] **Secrets** : `.env`/`.env.*` ignorés et absents de l'historique git ; aucun secret en dur (`grep` `password`/`secret`/`token`/`api_key`) ; secrets prod via un gestionnaire de secrets.
- [ ] **Routes** : protection `auth` / `verified`, endpoints sensibles exposés, routes publiques volontaires documentées.
- [ ] **Validation & mass assignment** : Form Requests utilisés, aucun `$request->all()`, `$fillable`/`$guarded` explicites, drapeaux de privilège hors du fillable.
- [ ] **Auth** : 2FA / passkeys (confirmation + mot de passe), vérification d'email, politique de mot de passe, rate limiters (login, 2FA, passkeys, register, reset), flux OAuth stateful + liste blanche + détection de collision.
- [ ] **CSRF / XSS / headers** : middleware CSRF, usages de `v-html` (XSS), en-têtes HTTP (CSP, `nosniff`, `frame-ancestors`, `Referrer-Policy`, HSTS), cookies (`http_only`, `same_site`, `secure`).
- [ ] **Configuration** : `APP_DEBUG`, CORS, session (`encrypt`/`secure`), trusted proxies, filesystems, `DB::prohibitDestructiveCommands`.
- [ ] **Dépendances** : `composer audit`, `bun audit`, packages sensibles, image prod en `--no-dev`, outils dev confinés en `require-dev`.
- [ ] **Tests de sécurité** : auth, rate limiting, confidentialité, injection, fuite de props.
- [ ] **Fichiers publics** : aucun dump / PHP superflu / symlink `storage`.
- [ ] **bootstrap/app.php** : middleware, redirections, rendu JSON des exceptions.

### Qualité / architecture / maintenance

- [ ] **Architecture & structure** : couches, providers, domaines bien délimités.
- [ ] **Modèles & migrations** : casts, relations, index, contraintes.
- [ ] **Contrôleurs & services** : responsabilités, réutilisation, pas de logique métier dans les vues.
- [ ] **Frontend** : composants réutilisables, Wayfinder (aucune URL en dur), types TS, i18n.
- [ ] **Tests** : couverture, organisation Pest, factories, tests des chemins à risque.
- [ ] **Outillage** : Pint, PHPStan/Larastan, CI, pre-commit hooks.
- [ ] **Hygiène** : code mort, `TODO`/`FIXME`, résidus de starter kit, configs non standard.

### Gouvernance

- [ ] Règles `.ai/rules/` cohérentes avec la pratique réelle.
- [ ] Historique git conforme au format de commit défini.
- [ ] Conteneurisation conforme aux règles (`container/`).
