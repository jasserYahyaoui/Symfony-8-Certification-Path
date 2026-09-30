---
id: CRS-g4sth72ww979
official_item: OIT-ta627mbwfz7m
title: "Password hashers"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-30"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/security/passwords.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/security/passwords.rst"
    branch: "8.0"
    symbol_or_lines: "algorithm auto (currently Bcrypt), migrate_from, when@test cost 4, UserPasswordHasherInterface"
    verified_at: "2026-09-30"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/PasswordHasher/Hasher/PasswordHasherFactory.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/PasswordHasher/Hasher/PasswordHasherFactory.php"
    branch: "8.0"
    symbol_or_lines: "getHasherConfigFromAlgorithm() — auto: native, sodium, pbkdf2; migrate_from"
    verified_at: "2026-09-30"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/PasswordHasher/Hasher/NativePasswordHasher.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/PasswordHasher/Hasher/NativePasswordHasher.php"
    branch: "8.0"
    symbol_or_lines: "PASSWORD_BCRYPT default; cost 13"
    verified_at: "2026-09-30"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Security/Http/EventListener/PasswordMigratingListener.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Security/Http/EventListener/PasswordMigratingListener.php"
    branch: "8.0"
    symbol_or_lines: "onLoginSuccess() — PasswordUpgradeBadge; PasswordUpgraderInterface"
    verified_at: "2026-09-30"
---

## Objectif

Hacher et vérifier un mot de passe, savoir quels anciens hachages restent
lisibles après un changement d'algorithme, et faire migrer ceux qui ne le sont
pas sans demander aux utilisateurs de changer de mot de passe.

## Hacher

```php
public function register(UserPasswordHasherInterface $hasher): Response
{
    $user->setPassword($hasher->hashPassword($user, $plainPassword));
}
```

`hashPassword()` prend **l'utilisateur** et non seulement la chaîne, parce que
la configuration peut associer un algorithme différent à chaque classe
d'utilisateur. L'interface a deux autres méthodes, `isPasswordValid()` et
`needsRehash()`, sur le même modèle.

Un contrôleur ne vérifie normalement pas le mot de passe lui-même : c'est le
badge `PasswordCredentials` de l'authentificateur qui déclenche la comparaison.

## `auto` hache en bcrypt

```yaml
security:
    password_hashers:
        App\Entity\User:
            algorithm: auto
```

La documentation le dit : `auto` choisit « the best available hasher
(currently Bcrypt) ». Lu dans `PasswordHasherFactory` : `auto` construit une
**chaîne** — `native`, puis `sodium` s'il est disponible, puis `pbkdf2` — dont
le premier maillon hache, et dont les suivants ne servent qu'à **vérifier**.
`native` utilise bcrypt, coût 13 par défaut.

Exécuté avec password-hasher 8.0.8, sodium disponible :

| Opération | Résultat |
|---|---|
| hachage par `auto` | préfixe `$2y$13$`, du bcrypt |
| hachage argon2id existant, vérifié par `auto` | accepté, et `needsRehash()` vrai |

Exécuté de bout en bout avec un fournisseur `PasswordUpgraderInterface` : un
utilisateur stocké en argon2id se connecte (200), et son mot de passe est
**rehaché en bcrypt** (`$2y$13$`).

## Quels anciens hachages restent lisibles

Un hachage bcrypt ou argon2 **porte son algorithme** (`$2y$…`, `$argon2id$…`).
Un condensat `sha256` fait maison n'est qu'une suite de caractères
hexadécimaux : rien ne dit comment le relire. Exécuté, `auto` sans
`migrate_from` :

| Hachage stocké | Vérifié ? |
|---|---|
| argon2id | oui |
| pbkdf2, réglages par défaut | oui |
| `sha256`, 1 itération, hexadécimal | **non** — connexion refusée, 401 |

Changer d'algorithme ne casse donc rien **tant que le nouveau hacheur sait lire
l'ancien format**. Sinon, il faut `migrate_from`.

## Migrer

`migrate_from` nomme les anciens hacheurs, déclarés à côté du nouveau :

```yaml
security:
    password_hashers:
        legacy:
            algorithm: sha256
            encode_as_base64: false
            iterations: 1
        App\Entity\User:
            algorithm: auto
            migrate_from: [legacy]
```

À la connexion, le mot de passe est vérifié par `legacy`, puis
`PasswordMigratingListener` le rehache avec le nouvel algorithme. Deux
conditions : un `PasswordUpgradeBadge` sur le passeport — `form_login`,
`json_login` et `http_basic` l'ajoutent — et un objet
`PasswordUpgraderInterface` pour enregistrer le résultat, en pratique le
fournisseur. Exécuté, hachages `legacy` en base :

| Fournisseur | `migrate_from` | Résultat |
|---|---|---|
| implémente `PasswordUpgraderInterface` | oui | 200, `upgradePassword()` reçoit un `$2y$13$…` |
| ne l'implémente pas | oui | 200, hachage **inchangé** |
| l'un ou l'autre | non | **401** |

L'utilisateur ne change pas de mot de passe et ne voit rien.

## En test

Les algorithmes sûrs sont lents par construction. En environnement de test, on
abaisse volontairement le coût — `cost: 4`, « lowest possible value for
bcrypt » : exécuté, préfixe `$2y$04$`.

## Pièges d'examen

**`hashPassword()` reçoit l'utilisateur**, pas seulement le mot de passe.

**`auto` hache en bcrypt**, même quand sodium est disponible.

**Changer d'algorithme peut casser** : un condensat qui ne porte pas son
algorithme n'est plus vérifié sans `migrate_from`.

**La migration exige un `PasswordUpgraderInterface`**, sinon le réencodage n'est
jamais sauvegardé — et rien ne le signale.

## Points clés

- `UserPasswordHasherInterface::hashPassword($user, $plain)`.
- `auto` : bcrypt pour hacher ; sodium et pbkdf2 seulement pour vérifier.
- Un hachage auto-descriptif survit au changement ; un condensat maison exige
  `migrate_from`.
- Le réencodage a besoin d'un `PasswordUpgradeBadge` et d'un
  `PasswordUpgraderInterface`.

## Sources officielles

- [Hashing and Verifying Passwords](https://github.com/symfony/symfony-docs/blob/8.0/security/passwords.rst)
- [PasswordHasher 8.0, `PasswordHasherFactory`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/PasswordHasher/Hasher/PasswordHasherFactory.php)
- [PasswordHasher 8.0, `NativePasswordHasher`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/PasswordHasher/Hasher/NativePasswordHasher.php)
- [Security HTTP 8.0, `PasswordMigratingListener`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Security/Http/EventListener/PasswordMigratingListener.php)
