---
id: CRS-g4sth72ww979
official_item: OIT-ta627mbwfz7m
title: "Password hashers"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/security/passwords.rst"
    branch: "8.0"
    symbol_or_lines: "algorithm auto, migrate_from, UserPasswordHasherInterface"
    verified_at: "2026-09-01"
---

## Objectif

Hacher et vérifier un mot de passe, et faire migrer un algorithme sans demander
aux utilisateurs de changer de mot de passe.

## Hacher

```php
public function register(UserPasswordHasherInterface $hasher): Response
{
    $user->setPassword($hasher->hashPassword($user, $plainPassword));
}
```

`hashPassword()` prend **l'utilisateur** et non seulement la chaîne, parce que
la configuration peut associer un algorithme différent à chaque classe
d'utilisateur.

Un contrôleur ne vérifie normalement pas le mot de passe lui-même : c'est le
badge `PasswordCredentials` de l'authentificateur qui déclenche la comparaison.

## `auto`

```yaml
security:
    password_hashers:
        App\Entity\User:
            algorithm: auto
```

`auto` délègue le choix à Symfony, qui prend le meilleur algorithme disponible —
aujourd'hui bcrypt ou sodium/argon2 selon l'installation. C'est la valeur
recommandée, parce qu'elle suit l'état de l'art sans changer la configuration.

Le hachage stocké **porte son algorithme** : changer `auto` ne casse donc pas les
mots de passe existants, il change ceux qui seront écrits ensuite.

## Migrer

`migrate_from` déclare les anciens algorithmes encore acceptés :

```yaml
        App\Entity\User:
            algorithm: auto
            migrate_from: ['legacy_sha256']
```

Le mécanisme est transparent : à la connexion, le mot de passe est vérifié avec
l'ancien algorithme, puis **réencodé avec le nouveau** et sauvegardé — à
condition que le fournisseur implémente `PasswordUpgraderInterface`, et que le
passport porte un `PasswordUpgradeBadge`.

L'utilisateur ne change pas de mot de passe et ne voit rien.

## En test

Les algorithmes sûrs sont lents par construction. En environnement de test, on
abaisse volontairement le coût — `cost: 4` pour bcrypt — pour que la suite ne
passe pas son temps à hacher.

## Pièges d'examen

**`hashPassword()` reçoit l'utilisateur**, pas seulement le mot de passe.

**Changer d'algorithme ne casse rien** : le hachage stocké porte le sien.

**La migration exige un fournisseur `PasswordUpgraderInterface`**, sinon le
réencodage n'est jamais sauvegardé.

## Points clés

- `UserPasswordHasherInterface::hashPassword($user, $plain)`.
- `algorithm: auto` suit l'état de l'art ; le hachage stocké porte son algorithme.
- `migrate_from` accepte l'ancien et réencode à la connexion.
- Le réencodage a besoin de `PasswordUpgraderInterface` côté fournisseur.

## Sources officielles

- [Hashing and Verifying Passwords](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/security/passwords.rst)
