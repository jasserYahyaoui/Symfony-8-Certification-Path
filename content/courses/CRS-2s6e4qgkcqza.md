---
id: CRS-2s6e4qgkcqza
official_item: OIT-fw6db7ryrk4q
title: "Roles"
content_level: MINIMAL
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/security.rst"
    symbol_or_lines: 'Roles — "ROLE_ prefix - otherwise, things won''t work as expected. Other than that, a role is just a string"'
    branch: "8.0"
    verified_at: "2026-09-01"
---

## Objectif

Nommer un rôle correctement et connaître la seule règle que Symfony impose.

## La règle

Un rôle est une chaîne libre, avec **une** contrainte : elle doit commencer par
`ROLE_`. Sans ce préfixe, la chaîne n'est pas traitée comme un rôle par le
système de sécurité.

`getRoles()` retourne le tableau des rôles de l'utilisateur ; la convention est
d'y garantir `ROLE_USER`.

## La hiérarchie

```yaml
security:
    role_hierarchy:
        ROLE_ADMIN: ROLE_USER
        ROLE_SUPER_ADMIN: [ROLE_ADMIN, ROLE_ALLOWED_TO_SWITCH]
```

Un `ROLE_ADMIN` obtient alors `ROLE_USER` sans qu'on l'écrive sur l'utilisateur.
La hiérarchie est **statique** : elle vit dans la configuration, et ne peut pas
être calculée depuis la base de données.

## Les attributs qui ne sont pas des rôles

`IS_AUTHENTICATED_FULLY`, `IS_AUTHENTICATED_REMEMBERED` et `PUBLIC_ACCESS`
s'emploient comme des rôles dans `isGranted()`, mais décrivent l'**état de
l'authentification**, pas l'utilisateur. `IS_AUTHENTICATED_FULLY` exige une
connexion de cette session ; `IS_AUTHENTICATED_REMEMBERED` accepte aussi un
retour par cookie « se souvenir de moi ».

## Pièges d'examen

**`in_array('ROLE_ADMIN', $user->getRoles())` ignore la hiérarchie.**
`getRoles()` retourne les rôles **écrits sur l'utilisateur** ; la hiérarchie est
appliquée par le système d'autorisation. La documentation marque cette ligne
`BAD` et lui oppose `isGranted('ROLE_ADMIN')`.

**La hiérarchie ne se calcule pas.** `role_hierarchy` est de la configuration,
statique : une hiérarchie stockée en base demande un voter.

**`IS_AUTHENTICATED_FULLY` n'est pas un rôle** et n'a rien à faire dans
`getRoles()`. Il décrit l'état de l'authentification et s'emploie uniquement
comme attribut passé à `isGranted()`.

## Points clés

- Un rôle doit commencer par `ROLE_` ; c'est la seule contrainte.
- `role_hierarchy` donne un rôle sans l'écrire sur l'utilisateur, et reste statique.
- `IS_AUTHENTICATED_*` décrit l'état de l'authentification, pas l'utilisateur.

## Sources officielles

- [Security, « Roles » et « Hierarchical Roles »](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/security.rst)
