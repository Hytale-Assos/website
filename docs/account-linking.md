# Linked accounts (comptes liés)

## Contexte

Le système d'authentification OAuth de Hytale n'est pas encore disponible (en
attente d'une réponse des développeurs). En attendant, l'association entre un
compte du site et un compte Hytale se fait **sur la base de la confiance** :
l'utilisateur saisit lui-même ses identifiants, l'équipe assure la modération.

Discord, lui, est lié par **OAuth** (Laravel Socialite +
`socialiteproviders/discord`).

## Architecture commune des liaisons OAuth

- Routes génériques par provider :
  - `GET auth/{provider}/redirect` → départ vers la page d'autorisation
  - `GET auth/{provider}/callback` → retour du provider, liaison du compte
  - `DELETE settings/accounts/{provider}` → dissociation
- `App\Http\Controllers\Settings\LinkedAccountOAuthController` gère tous les
  providers : une whitelist interne mappe chaque provider aux colonnes
  utilisateurs (`id_column`, `nickname_column`). Ajouter un provider = une
  entrée dans la map + l'entrée `config/services.php` correspondante.
- URL de callback à déclarer chez le provider :
  `http://localhost/auth/{provider}/callback` (en local).
- Un compte provider déjà lié à un autre utilisateur du site ne peut pas être
  lié deux fois (erreur remontée à l'utilisateur).

## Phase 1 Hytale — actuelle (déclaration manuelle)

- L'utilisateur renseigne depuis les paramètres (`/settings/accounts`,
  onglet « Linked accounts ») :
  - `hytale_nickname` (pseudo en jeu, optionnel)
  - `hytale_id` (UUID de compte Hytale, optionnel, unique entre utilisateurs)
- La colonne `users.hytale_account_verified_at` (timestamp, nullable) suit
  l'état de vérification :
  - `null` → identifiants déclarés par l'utilisateur, **non vérifiés**
  - non-null → identifiants **confirmés par Hytale** (via OAuth, phase 2)
- Ce champ n'est **pas fillable** : seule l'application (futur flux OAuth) peut
  le positionner, jamais une requête utilisateur.
- Tant que le compte n'est pas vérifié, l'utilisateur peut modifier ses
  identifiants ; dès qu'il est vérifié, la saisie est refusée côté serveur
  (règle `prohibited`) et désactivée côté interface.

## Discord — opérationnel (OAuth)

- Provider `socialiteproviders/discord` (scopes `identify`, `email`),
  configuré dans `config/services.php` (`DISCORD_CLIENT_ID`,
  `DISCORD_CLIENT_SECRET`, `DISCORD_REDIRECT_URI`).
- L'utilisateur lie son compte depuis la carte Discord de la page
  « Linked accounts » : bouton **Link** → flot OAuth → `discord_id` +
  `discord_nickname` stockés ; bouton **Unlink** pour dissocier.
- Pas d'équivalent `discord_account_verified_at` : un `discord_id` non-null
  est par nature vérifié, puisqu'il vient de l'OAuth Discord.

## Chiffrement au repos (pattern commun)

Le cast custom `App\Casts\EncryptedWithHash` (déclaré en chaîne
`EncryptedWithHash::class.':<colonne>_hash'` dans `User::casts()`)
encapsule toute la logique pour les colonnes id chiffrées :

- **écriture** : chiffre la valeur (`Crypt::encryptString`, AES via
  `APP_KEY`) et remplit le hash SHA-256 déterministe de la colonne
  compagnon — quel que soit le code appelant (formulaire, factory,
  seeder, flux OAuth) ; écrire `null` vide les deux colonnes
- **lecture** : déchiffre via le cast
- les nicknames (`discord_nickname`, `hytale_nickname`) utilisent le
  cast standard `encrypted` (pas de hash nécessaire)

Ajouter un provider chiffré = une ligne dans `casts()`.

Le chiffrement étant non déterministe (IV aléatoire), il ne permet ni
recherche ni unicité : c'est la colonne de hash (`discord_id_hash`,
`hytale_id_hash`, indexée unique) qui porte la contrainte
« un compte provider = un utilisateur » et sert aux détections de
conflit. Le hash est irréversible : il ne permet pas de retrouver un ID
inconnu (mais permet de vérifier qu'un ID connu est présent).

Conséquence `APP_KEY` : si la clé est perdue, les valeurs chiffrées
deviennent indéchiffrables. En cas de rotation de clé, renseigner
`APP_PREVIOUS_KEYS` pour le déchiffrement gracieux des anciennes valeurs.

## Hytale — spécificités

- `users.hytale_id_hash` : SHA-256 de l'UUID, indexée unique. La
  validation de la saisie manuelle (`LinkedAccountUpdateRequest`)
  vérifie l'unicité via ce hash (une valeur chiffrée ne peut pas être
  requêtée).
- Le futur OAuth Hytale remplira exactement ces colonnes et positionnera
  `hytale_account_verified_at` — aucun schéma supplémentaire à prévoir.

## Phase 2 Hytale — roadmap (OAuth)

Quand l'API/OAuth Hytale sera disponible :

1. **Brancher l'OAuth Hytale** : nouvelle entrée dans la whitelist du
   controller + config `services.php` ; les routes `auth/hytale/...` suivent
   immédiatement (pattern générique déjà en place).
2. **Migrer les comptes existants** : à la première liaison OAuth, comparer
   les identifiants déclarés avec ceux renvoyés par Hytale, puis positionner
   `hytale_account_verified_at`.
3. **Désactiver la saisie manuelle** : les champs `hytale_nickname` et
   `hytale_id` deviennent fournis par Hytale uniquement. Le blocage existe
   déjà (règle `prohibited` + interface en lecture seule dès que
   `hytale_account_verified_at` est non-null) ; il restera à retirer le mode
   de saisie manuel pour les comptes non encore liés.
