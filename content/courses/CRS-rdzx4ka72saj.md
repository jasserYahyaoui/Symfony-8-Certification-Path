---
id: CRS-rdzx4ka72saj
official_item: OIT-z9c24d68et6w
title: "Authentication"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-30"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/security.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/security.rst"
    symbol_or_lines: 'Authenticating Users; Authentication (Identifying/Logging in the User)'
    branch: "8.0"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/SecurityBundle/Security.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/SecurityBundle/Security.php"
    symbol_or_lines: "Security::getUser(), Security::getToken()"
    branch: "8.0"
    verified_at: "2026-09-30"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/SecurityBundle/DependencyInjection/MainConfiguration.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/SecurityBundle/DependencyInjection/MainConfiguration.php"
    symbol_or_lines: "session_fixation_strategy, defaultValue MIGRATE"
    branch: "8.0"
    verified_at: "2026-09-30"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Security/Http/Firewall/ContextListener.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Security/Http/Firewall/ContextListener.php"
    symbol_or_lines: "Read existing security token from the session; User was reloaded from a user provider"
    branch: "8.0"
    verified_at: "2026-09-30"
---

## Objectif

Suivre ce qui se passe entre une Request anonyme et un user connu. Le
détail des authenticators, passports et badges a son propre item.

## La question posée

L'authentication répond à **« qui êtes-vous ? »**. L'authorization, traitée
séparément, répond à « avez-vous le droit ? ». Les deux sont indépendantes : un
user parfaitement authentifié peut n'avoir aucun droit.

## Le trajet

1. La Request entre dans **un** firewall, choisi par son `pattern`.
2. Les **authenticators** de ce firewall sont interrogés : l'un déclare prendre
   la Request en charge.
3. Il construit un **passport**, dont les badges sont vérifiés.
4. Un **token** (`TokenInterface`) est produit : il porte le user et ses
   roles.
5. Le token est déposé dans le **`TokenStorage`**, où tout le reste de
   l'application le lira.

`TokenStorage` est la mémoire de la Request courante. `Security::getUser()` —
service `Symfony\Bundle\SecurityBundle\Security` — ou `$this->getUser()` dans un
controller n'est qu'un raccourci vers lui.

## Pas de token sans user

Depuis la disparition du token anonyme, une Request non authentifiée n'a **aucun
token**. Exécuté avec SecurityBundle 8.0.15, firewall `main` en `http_basic` :

| Request | `getToken()` | `getUser()` | Response |
|---|---|---|---|
| `/public`, sans identifiers | `null` | `null` | 200 |
| `/whoami` (exige `ROLE_USER`), sans identifiers | — | — | **401**, entry point |
| `/whoami`, identifiers valides | `UsernamePasswordToken` | `alice` | 200 |

Le journal du firewall, pour la Request anonyme : « Access denied, the user is
not fully authenticated; redirecting to authentication entry point. »

## Avec ou sans état

Par défaut, le token est **sérialisé en session** entre deux Requests : c'est
`stateless: false`. Exécuté : après la connexion, « Stored the security token in
the session » ; à la Request suivante, avec le seul cookie, « Read existing
security token from the session » puis « User was reloaded from a user
provider » — le user est **rechargé** à chaque Request, pas recopié tel
quel.

Une API à tokens déclare `stateless: true` : le token n'est jamais écrit en
session. Exécuté sur un firewall `api` sans état : aucun cookie posé après une
connexion réussie, et la Request suivante sans identifiers reçoit **401**.

Après une connexion réussie, l'identifier de session est régénéré contre la
fixation de session : `session_fixation_strategy` vaut `migrate` par défaut.
Exécuté : une connexion portant un cookie de session existant en reçoit un
nouveau, d'identifier différent.

## Ce que le controller voit

```php
$this->getUser();                 // l'utilisateur, ou null
$this->isGranted('ROLE_ADMIN');   // autorisation, pas authentification
```

`getUser()` retournant `null` signifie « pas authentifié », **pas** « accès
refusé ». Exécuté : `alice`, authentifiée, obtient `isGranted('ROLE_ADMIN')`
faux.

## Pièges d'examen

**Authentication ≠ authorization.** `getUser()` répond à la première,
`isGranted()` à la seconde.

**Pas de user, pas de token** : `getToken()` rend `null` pour une Request
anonyme ; le token anonyme n'existe plus.

**`stateless: true` n'écrit pas le token en session** : le user n'est plus
mémorisé d'une Request à l'autre.

**Le token en session ne fige pas le user** : il est rechargé par le
provider à chaque Request.

## Points clés

- Un firewall, des authenticators, un passport, un token, un `TokenStorage`.
- `TokenStorage` porte le user courant ; `getUser()` y accède.
- `stateless` décide si le token survit à la Request ; la session migre à la
  connexion.
- Authentication et authorization sont deux questions distinctes.

## Sources officielles

- [Security](https://github.com/symfony/symfony-docs/blob/8.0/security.rst)
- [SecurityBundle 8.0, `Security`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/SecurityBundle/Security.php)
- [SecurityBundle 8.0, `MainConfiguration`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/SecurityBundle/DependencyInjection/MainConfiguration.php)
- [Security HTTP 8.0, `ContextListener`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Security/Http/Firewall/ContextListener.php)
