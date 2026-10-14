---
id: CRS-2s6e4qgkcqza
official_item: OIT-fw6db7ryrk4q
title: "Roles"
content_level: MINIMAL
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-30"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/security.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/security.rst"
    symbol_or_lines: 'Roles — "ROLE_ prefix - otherwise, things won''t work as expected"; Hierarchical Roles — BAD in_array, static values, debug:security:role-hierarchy'
    branch: "8.0"
    verified_at: "2026-09-30"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Security/Core/Authorization/Voter/RoleVoter.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Security/Core/Authorization/Voter/RoleVoter.php"
    branch: "8.0"
    symbol_or_lines: "prefix ROLE_; supportsAttribute()"
    verified_at: "2026-09-30"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Security/Core/Authorization/Voter/AuthenticatedVoter.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Security/Core/Authorization/Voter/AuthenticatedVoter.php"
    branch: "8.0"
    symbol_or_lines: "six attribute constants"
    verified_at: "2026-09-30"
---

## Objectif

Nommer un role correctement et connaître la seule règle que Symfony impose.

## La règle

Un role est une string libre, avec **une** constraint : elle doit commencer par
`ROLE_`. La documentation : « otherwise, things won't work as expected ». La
raison est dans `RoleVoter` : il ne vote que sur les attributes qui commencent
par ce préfixe, et s'abstient sur les autres.

Exécuté avec SecurityBundle 8.0.15, un user dont `getRoles()` rend
`['ADMIN']` : `isGranted('ADMIN')` est **faux**, alors que la string figure bien
dans `getRoles()`. Aucun voter ne s'en charge, et l'abstention générale vaut
refus.

`getRoles()` retourne le tableau des roles du user ; la convention est
d'y garantir `ROLE_USER`.

## La hiérarchie

```yaml
security:
    role_hierarchy:
        ROLE_ADMIN: ROLE_USER
        ROLE_SUPER_ADMIN: [ROLE_ADMIN, ROLE_ALLOWED_TO_SWITCH]
```

Un `ROLE_ADMIN` obtient alors `ROLE_USER` sans qu'on l'écrive sur le user.
Exécuté, user portant `ROLE_ADMIN` seul :

| Test | Résultat |
|---|---|
| `isGranted('ROLE_USER')` | vrai |
| `in_array('ROLE_USER', $user->getRoles())` | **faux** |

La hiérarchie est appliquée par le voter, pas par le user.

Les **valeurs** de `role_hierarchy` sont statiques : la documentation précise
qu'on ne peut pas y stocker une hiérarchie venue d'une base de données, et
recommande un voter pour ce besoin. Ce n'est pas une impossibilité du
framework : exécuté, un service qui décore `security.role_hierarchy` et
implémente `RoleHierarchyInterface` ajoute un role calculé à l'exécution, et
`isGranted()` l'accorde.

`php bin/console debug:security:role-hierarchy` affiche la hiérarchie
configurée, au format Mermaid.

## Les attributes qui ne sont pas des roles

`AuthenticatedVoter` 8.0 traite six attributes : `IS_AUTHENTICATED_FULLY`,
`IS_AUTHENTICATED_REMEMBERED`, `IS_AUTHENTICATED`, `IS_REMEMBERED`,
`IS_IMPERSONATOR` et `PUBLIC_ACCESS`. Ils s'emploient comme des roles dans
`isGranted()`, mais décrivent l'**état de l'authentication**, pas
le user. `IS_AUTHENTICATED_FULLY` exige une connexion de cette session ;
`IS_AUTHENTICATED_REMEMBERED` accepte aussi un retour par cookie « se souvenir
de moi ».

## Pièges d'examen

**Un role sans `ROLE_` n'est jamais accordé**, même présent dans `getRoles()`.

**`in_array('ROLE_ADMIN', $user->getRoles())` ignore la hiérarchie.** La
documentation marque cette ligne `BAD` et lui oppose `isGranted('ROLE_ADMIN')`.

**Les valeurs de `role_hierarchy` sont statiques** ; une hiérarchie en base
demande du code — un voter, selon la documentation.

**`IS_AUTHENTICATED_FULLY` n'est pas un role** et n'a rien à faire dans
`getRoles()`.

## Points clés

- Un role doit commencer par `ROLE_` ; sans le préfixe, `RoleVoter` s'abstient.
- `role_hierarchy` donne un role sans l'écrire sur le user ; ses valeurs
  sont statiques.
- `IS_AUTHENTICATED_*`, `IS_REMEMBERED`, `IS_IMPERSONATOR` et `PUBLIC_ACCESS`
  décrivent l'état de l'authentication.

## Sources officielles

- [Security, « Roles » et « Hierarchical Roles »](https://github.com/symfony/symfony-docs/blob/8.0/security.rst)
- [Security Core 8.0, `RoleVoter`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Security/Core/Authorization/Voter/RoleVoter.php)
- [Security Core 8.0, `AuthenticatedVoter`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Security/Core/Authorization/Voter/AuthenticatedVoter.php)
