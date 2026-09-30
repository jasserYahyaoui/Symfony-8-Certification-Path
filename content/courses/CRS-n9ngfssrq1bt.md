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
---

## Objectif

Écrire une classe utilisateur conforme à Symfony 8.0, et connaître le contrat
minimal — plus court qu'on ne le croit.

## `UserInterface`, en 8.0

Deux méthodes. C'est tout :

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
Une classe qui déclare encore la méthode ne provoque pas d'erreur, mais aucun
code de Security Core 8.0.15, Security HTTP 8.0.14 ou SecurityBundle 8.0.15
ne l'appelle.

`getUserIdentifier()` retourne l'identifiant **d'affichage et de recherche** —
courriel, nom de connexion — pas nécessairement la clé primaire. C'est la valeur
que le fournisseur reçoit dans `loadUserByIdentifier()`.

## `getRoles()` : aucun minimum imposé

La documentation montre une classe dont `getRoles()` ajoute toujours
`ROLE_USER` — « guarantee every user at least has ROLE_USER ». C'est une
**convention de la classe**, pas une exigence du framework. Exécuté avec
SecurityBundle 8.0.15, un utilisateur dont `getRoles()` rend `[]` :

| Vérification | Résultat |
|---|---|
| connexion HTTP Basic | **200**, authentifié |
| `isGranted('IS_AUTHENTICATED_FULLY')` | `true` |
| `isGranted('ROLE_USER')` | `false` |

Sans la convention, une règle `access_control` sur `ROLE_USER` refuserait donc
un utilisateur pourtant connecté.

## Le mot de passe est une interface séparée

Un utilisateur n'a pas forcément de mot de passe : un jeton d'API, un
fournisseur d'identité externe s'en passent. Le mot de passe vit donc dans
`PasswordAuthenticatedUserInterface` :

```php
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function getPassword(): ?string { return $this->password; }
}
```

C'est cette interface que le système de hachage attend.

## Comparer deux utilisateurs

À chaque requête d'un pare-feu avec état, l'utilisateur est rechargé et
**comparé** à celui du jeton par `ContextListener::hasUserChanged()`. Par défaut,
la comparaison porte sur le **mot de passe**, les **rôles** et l'**identifiant** ;
une différence abandonne le jeton. Une classe qui implémente
`EquatableInterface` remplace cette règle par sa méthode `isEqualTo()`.

Exécuté : `alice` connectée, puis sa source modifiée avant la requête suivante.

| Classe | Changement | Requête suivante |
|---|---|---|
| sans `EquatableInterface` | aucun | `alice`, connectée |
| sans `EquatableInterface` | `ROLE_EDITOR` ajouté | **déconnectée** |
| sans `EquatableInterface` | mot de passe changé | **déconnectée** |
| `isEqualTo()` ne comparant que l'identifiant | `ROLE_EDITOR` ajouté | connectée, mais `isGranted('ROLE_EDITOR')` **faux** |

La dernière ligne est le point subtil : `getUser()->getRoles()` rend bien le
nouveau rôle, mais le jeton garde les rôles **de la connexion**, et c'est lui
que les votants lisent. Dans les deux cas, le nouveau rôle ne s'obtient qu'en se
reconnectant.

## Interdire un compte

Un compte désactivé ou expiré ne se refuse pas dans `getRoles()` : c'est le rôle
d'un **user checker**, déclaré sur le pare-feu par `user_checker`. Lu dans
`UserCheckerListener` :

- `checkPreAuth()` écoute `CheckPassportEvent` en priorité 256, donc **avant**
  la vérification du mot de passe ;
- `checkPostAuth()` écoute `AuthenticationSuccessEvent`, et reçoit en 8.0 le
  jeton en second argument.

Exécuté : un compte refusé par `checkPreAuth()` reçoit **401** ;
`checkPreAuth()` tourne aussi quand le mot de passe est faux, et aucun des deux
n'est appelé sur les requêtes suivantes portant le cookie de session.

## Pièges d'examen

**`UserInterface` n'a que deux méthodes en Symfony 8.0** ; `eraseCredentials()`
a été retirée.

**`getPassword()` vient de `PasswordAuthenticatedUserInterface`.**

**`getUserIdentifier()` n'est pas l'identifiant de base de données.**

**Un utilisateur sans rôle est authentifié** ; `ROLE_USER` est une convention.

**Les rôles sont déjà comparés par défaut** : en changer déconnecte.

**Le jeton fige les rôles de la connexion**, même quand `isEqualTo()` garde
l'utilisateur connecté.

**Un compte désactivé relève d'un user checker**, appelé à la connexion
seulement.

## Points clés

- `UserInterface` : `getRoles()` et `getUserIdentifier()`, rien d'autre.
- Le mot de passe est porté par `PasswordAuthenticatedUserInterface`.
- Comparaison par défaut : mot de passe, rôles, identifiant ;
  `EquatableInterface` la remplace.
- Un nouveau rôle exige une nouvelle connexion.
- User checker : avant le mot de passe, puis après le succès.

## Sources officielles

- [`UserInterface`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Security/Core/User/UserInterface.php)
- [Security Core 8.0, CHANGELOG](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Security/Core/CHANGELOG.md)
- [Security HTTP 8.0, `ContextListener`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Security/Http/Firewall/ContextListener.php)
- [Security HTTP 8.0, `UserCheckerListener`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Security/Http/EventListener/UserCheckerListener.php)
- [Security](https://github.com/symfony/symfony-docs/blob/8.0/security.rst)
- [User Checkers](https://github.com/symfony/symfony-docs/blob/8.0/security/user_checkers.rst)
