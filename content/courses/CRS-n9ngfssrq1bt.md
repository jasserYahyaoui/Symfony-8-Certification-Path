---
id: CRS-n9ngfssrq1bt
official_item: OIT-gkh08ztwdme1
title: "Users"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-30"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Security/Core/User/UserInterface.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Security/Core/User/UserInterface.php"
    branch: "8.0"
    symbol_or_lines: "getRoles, getUserIdentifier"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Security/Core/CHANGELOG.md"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Security/Core/CHANGELOG.md"
    branch: "8.0"
    symbol_or_lines: "8.0 — Remove UserInterface::eraseCredentials(); 7.3 — Deprecate it"
    verified_at: "2026-09-30"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Security/Http/Firewall/ContextListener.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Security/Http/Firewall/ContextListener.php"
    branch: "8.0"
    symbol_or_lines: "hasUserChanged() — password, roles, identifier; EquatableInterface::isEqualTo()"
    verified_at: "2026-09-30"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Security/Http/EventListener/UserCheckerListener.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Security/Http/EventListener/UserCheckerListener.php"
    branch: "8.0"
    symbol_or_lines: "CheckPassportEvent priority 256; AuthenticationSuccessEvent"
    verified_at: "2026-09-30"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/security.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/security.rst"
    branch: "8.0"
    symbol_or_lines: "guarantee every user at least has ROLE_USER"
    verified_at: "2026-09-30"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/security/user_checkers.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/security/user_checkers.rst"
    branch: "8.0"
    symbol_or_lines: "User Checkers"
    verified_at: "2026-09-30"
outcome_sections:
  OUT-q8e80pz7kv3c: "`UserInterface`, en 8.0"
  OUT-9523w2t3cts8: "Le password est une interface séparée"
  OUT-tfqgyk6sjrcg: "`UserInterface`, en 8.0"
  OUT-5hk32whbfhrd: "Interdire un compte"
---

## Objectif

Écrire une class user conforme à Symfony 8.0, et connaître le contrat
minimal — plus court qu'on ne le croit.

## `UserInterface`, en 8.0

Deux methods. C'est tout :

```php
interface UserInterface
{
    public function getRoles(): array;
    public function getUserIdentifier(): string;
}
```

`eraseCredentials()` **n'existe plus** : dépréciée en 7.3, retirée en 8.0, avec
`TokenInterface::eraseCredentials()`. Le CHANGELOG de Security Core 8.0 indique
le remplaçant : effacer les données sensibles « e.g. using `__serialize()` ».
Une class qui déclare encore la method ne provoque pas d'erreur, mais aucun
code de Security Core 8.0.15, Security HTTP 8.0.14 ou SecurityBundle 8.0.15
ne l'appelle.

`getUserIdentifier()` retourne l'identifier **d'affichage et de recherche** —
courriel, nom de connexion — pas nécessairement la key primaire. C'est la valeur
que le provider reçoit dans `loadUserByIdentifier()`.

## `getRoles()` : aucun minimum imposé

La documentation montre une class dont `getRoles()` ajoute toujours
`ROLE_USER` — « guarantee every user at least has ROLE_USER ». C'est une
**convention de la class**, pas une exigence du framework. Exécuté avec
SecurityBundle 8.0.15, un user dont `getRoles()` rend `[]` :

| Vérification | Résultat |
|---|---|
| connexion HTTP Basic | **200**, authentifié |
| `isGranted('IS_AUTHENTICATED_FULLY')` | `true` |
| `isGranted('ROLE_USER')` | `false` |

Sans la convention, une règle `access_control` sur `ROLE_USER` refuserait donc
un user pourtant connecté.

## Le password est une interface séparée

Un user n'a pas forcément de password : un token d'API, un
provider d'identité externe s'en passent. Le password vit donc dans
`PasswordAuthenticatedUserInterface` :

```php
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function getPassword(): ?string { return $this->password; }
}
```

C'est cette interface que le système de hachage attend.

## Comparer deux users

À chaque Request d'un firewall avec état, le user est rechargé et
**comparé** à celui du token par `ContextListener::hasUserChanged()`. Par défaut,
la comparaison porte sur le **password**, les **roles** et l'**identifier** ;
une différence abandonne le token. Une class qui implémente
`EquatableInterface` remplace cette règle par sa method `isEqualTo()`.

Exécuté : `alice` connectée, puis sa source modifiée avant la Request suivante.

| Class | Changement | Request suivante |
|---|---|---|
| sans `EquatableInterface` | aucun | `alice`, connectée |
| sans `EquatableInterface` | `ROLE_EDITOR` ajouté | **déconnectée** |
| sans `EquatableInterface` | password changé | **déconnectée** |
| `isEqualTo()` ne comparant que l'identifier | `ROLE_EDITOR` ajouté | connectée, mais `isGranted('ROLE_EDITOR')` **faux** |

La dernière ligne est le point subtil : `getUser()->getRoles()` rend bien le
nouveau role, mais le token garde les roles **de la connexion**, et c'est lui
que les voters lisent. Dans les deux cas, le nouveau role ne s'obtient qu'en se
reconnectant.

## Interdire un compte

Un compte désactivé ou expiré ne se refuse pas dans `getRoles()` : c'est le role
d'un **user checker**, déclaré sur le firewall par `user_checker`. Lu dans
`UserCheckerListener` :

- `checkPreAuth()` écoute `CheckPassportEvent` en priorité 256, donc **avant**
  la vérification du password ;
- `checkPostAuth()` écoute `AuthenticationSuccessEvent`, et reçoit en 8.0 le
  token en second argument.

Exécuté : un compte refusé par `checkPreAuth()` reçoit **401** ;
`checkPreAuth()` tourne aussi quand le password est faux, et aucun des deux
n'est appelé sur les Requests suivantes portant le cookie de session.

## Pièges d'examen

**`UserInterface` n'a que deux methods en Symfony 8.0** ; `eraseCredentials()`
a été retirée.

**`getPassword()` vient de `PasswordAuthenticatedUserInterface`.**

**`getUserIdentifier()` n'est pas l'identifier de base de données.**

**Un user sans role est authentifié** ; `ROLE_USER` est une convention.

**Les roles sont déjà comparés par défaut** : en changer déconnecte.

**Le token fige les roles de la connexion**, même quand `isEqualTo()` garde
le user connecté.

**Un compte désactivé relève d'un user checker**, appelé à la connexion
seulement.

## Points clés

- `UserInterface` : `getRoles()` et `getUserIdentifier()`, rien d'autre.
- Le password est porté par `PasswordAuthenticatedUserInterface`.
- Comparaison par défaut : password, roles, identifier ;
  `EquatableInterface` la remplace.
- Un nouveau role exige une nouvelle connexion.
- User checker : avant le password, puis après le succès.

## Sources officielles

- [`UserInterface`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Security/Core/User/UserInterface.php)
- [Security Core 8.0, CHANGELOG](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Security/Core/CHANGELOG.md)
- [Security HTTP 8.0, `ContextListener`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Security/Http/Firewall/ContextListener.php)
- [Security HTTP 8.0, `UserCheckerListener`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Security/Http/EventListener/UserCheckerListener.php)
- [Security](https://github.com/symfony/symfony-docs/blob/8.0/security.rst)
- [User Checkers](https://github.com/symfony/symfony-docs/blob/8.0/security/user_checkers.rst)
