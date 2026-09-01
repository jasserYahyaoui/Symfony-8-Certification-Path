---
id: CRS-rdzx4ka72saj
official_item: OIT-z9c24d68et6w
title: "Authentication"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/security.rst"
    branch: "8.0"
    verified_at: "2026-09-01"
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

`TokenStorage` est la mémoire de la requête courante. `Security::getUser()` — ou
`$this->getUser()` dans un contrôleur — n'est qu'un raccourci vers lui.

## Avec ou sans état

Par défaut, le jeton est **sérialisé en session** entre deux requêtes : c'est
`stateless: false`. Une API à jetons déclare `stateless: true`, et chaque requête
se ré-authentifie alors intégralement.

Après une connexion réussie, l'identifiant de session est régénéré pour éviter
la fixation de session.

## Ce que le contrôleur voit

```php
$this->getUser();                 // l'utilisateur, ou null
$this->isGranted('ROLE_ADMIN');   // autorisation, pas authentification
```

`getUser()` retournant `null` signifie « pas authentifié », **pas** « accès
refusé ».

## Pièges d'examen

**Authentification ≠ autorisation.** `getUser()` répond à la première,
`isGranted()` à la seconde.

**Un jeton existe même sans utilisateur connecté** sur un pare-feu qui l'exige ;
c'est son contenu qui change.

**`stateless: true` supprime la session**, donc l'utilisateur n'est plus
mémorisé d'une requête à l'autre.

## Points clés

- Un pare-feu, des authenticators, un passport, un jeton, un `TokenStorage`.
- `TokenStorage` porte l'utilisateur courant ; `getUser()` y accède.
- `stateless` décide si le jeton survit à la requête.
- Authentification et autorisation sont deux questions distinctes.

## Sources officielles

- [Security](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/security.rst)
