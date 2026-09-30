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

Suivre ce qui se passe entre une requête anonyme et un utilisateur connu. Le
détail des authenticators, passports et badges a son propre item.

## La question posée

L'authentification répond à **« qui êtes-vous ? »**. L'autorisation, traitée
séparément, répond à « avez-vous le droit ? ». Les deux sont indépendantes : un
utilisateur parfaitement authentifié peut n'avoir aucun droit.

## Le trajet

1. La requête entre dans **un** pare-feu, choisi par son `pattern`.
2. Les **authenticators** de ce pare-feu sont interrogés : l'un déclare prendre
   la requête en charge.
3. Il construit un **passport**, dont les badges sont vérifiés.
4. Un **jeton** (`TokenInterface`) est produit : il porte l'utilisateur et ses
   rôles.
5. Le jeton est déposé dans le **`TokenStorage`**, où tout le reste de
   l'application le lira.

`TokenStorage` est la mémoire de la requête courante. `Security::getUser()` —
service `Symfony\Bundle\SecurityBundle\Security` — ou `$this->getUser()` dans un
contrôleur n'est qu'un raccourci vers lui.

## Pas de jeton sans utilisateur

Depuis la disparition du jeton anonyme, une requête non authentifiée n'a **aucun
jeton**. Exécuté avec SecurityBundle 8.0.15, pare-feu `main` en `http_basic` :

| Requête | `getToken()` | `getUser()` | Réponse |
|---|---|---|---|
| `/public`, sans identifiants | `null` | `null` | 200 |
| `/whoami` (exige `ROLE_USER`), sans identifiants | — | — | **401**, point d'entrée |
| `/whoami`, identifiants valides | `UsernamePasswordToken` | `alice` | 200 |

Le journal du pare-feu, pour la requête anonyme : « Access denied, the user is
not fully authenticated; redirecting to authentication entry point. »

## Avec ou sans état

Par défaut, le jeton est **sérialisé en session** entre deux requêtes : c'est
`stateless: false`. Exécuté : après la connexion, « Stored the security token in
the session » ; à la requête suivante, avec le seul cookie, « Read existing
security token from the session » puis « User was reloaded from a user
provider » — l'utilisateur est **rechargé** à chaque requête, pas recopié tel
quel.

Une API à jetons déclare `stateless: true` : le jeton n'est jamais écrit en
session. Exécuté sur un pare-feu `api` sans état : aucun cookie posé après une
connexion réussie, et la requête suivante sans identifiants reçoit **401**.

Après une connexion réussie, l'identifiant de session est régénéré contre la
fixation de session : `session_fixation_strategy` vaut `migrate` par défaut.
Exécuté : une connexion portant un cookie de session existant en reçoit un
nouveau, d'identifiant différent.

## Ce que le contrôleur voit

```php
$this->getUser();                 // l'utilisateur, ou null
$this->isGranted('ROLE_ADMIN');   // autorisation, pas authentification
```

`getUser()` retournant `null` signifie « pas authentifié », **pas** « accès
refusé ». Exécuté : `alice`, authentifiée, obtient `isGranted('ROLE_ADMIN')`
faux.

## Pièges d'examen

**Authentification ≠ autorisation.** `getUser()` répond à la première,
`isGranted()` à la seconde.

**Pas d'utilisateur, pas de jeton** : `getToken()` rend `null` pour une requête
anonyme ; le jeton anonyme n'existe plus.

**`stateless: true` n'écrit pas le jeton en session** : l'utilisateur n'est plus
mémorisé d'une requête à l'autre.

**Le jeton en session ne fige pas l'utilisateur** : il est rechargé par le
fournisseur à chaque requête.

## Points clés

- Un pare-feu, des authenticators, un passport, un jeton, un `TokenStorage`.
- `TokenStorage` porte l'utilisateur courant ; `getUser()` y accède.
- `stateless` décide si le jeton survit à la requête ; la session migre à la
  connexion.
- Authentification et autorisation sont deux questions distinctes.

## Sources officielles

- [Security](https://github.com/symfony/symfony-docs/blob/8.0/security.rst)
- [SecurityBundle 8.0, `Security`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/SecurityBundle/Security.php)
- [SecurityBundle 8.0, `MainConfiguration`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/SecurityBundle/DependencyInjection/MainConfiguration.php)
- [Security HTTP 8.0, `ContextListener`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Security/Http/Firewall/ContextListener.php)
