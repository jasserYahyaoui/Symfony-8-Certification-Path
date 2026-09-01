---
id: CRS-2ec5yksjfm9s
official_item: OIT-cwfr5t1ngj23
title: "Access Control Rules"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/security/access_control.rst"
    branch: "8.0"
    symbol_or_lines: "only the first matching access_control is used"
    verified_at: "2026-09-01"
---

## Objectif

Protéger des URL par la configuration, et connaître la règle d'ordre — la même
que pour les routes, avec la même conséquence.

## La règle

```yaml
security:
    access_control:
        - { path: '^/admin/users', roles: ROLE_SUPER_ADMIN }
        - { path: '^/admin', roles: ROLE_ADMIN }
        - { path: '^/profile', roles: IS_AUTHENTICATED_FULLY }
```

Les règles sont évaluées **dans l'ordre**, et **seule la première qui
correspond est appliquée**. Les suivantes sont ignorées, même si elles
correspondent aussi.

D'où la règle d'écriture : **du plus spécifique au plus général**. Inverser les
deux premières lignes rendrait `ROLE_SUPER_ADMIN` inatteignable, puisque
`^/admin` capturerait déjà `/admin/users`.

`path` est une expression régulière, pas un préfixe littéral.

## Les critères disponibles

Au-delà du chemin, une règle peut porter sur :

| Clé | Ce qu'elle contraint |
|---|---|
| `ips` | l'adresse IP du client |
| `host` | le nom d'hôte |
| `methods` | les méthodes HTTP |
| `port` | le port |
| `route` | le nom de la route |
| `attributes` | des attributs de la requête |
| `allow_if` | une expression |
| `requires_channel` | `https`, ce qui force une redirection |

Tous les critères d'une même règle doivent correspondre pour qu'elle s'applique.

## Ce que cela ne fait pas

`access_control` protège des **URL**. Il ne sait rien des objets : « l'auteur
peut modifier son article » ne s'y exprime pas — c'est le travail d'un votant.

Une règle qui refuse l'accès produit une `AccessDeniedException`, traitée comme
partout ailleurs : 403 pour un utilisateur connu, entrée en authentification
pour un anonyme.

## Pièges d'examen

**Seule la première règle correspondante s'applique** ; les autres ne sont pas
cumulées.

**Du plus spécifique au plus général**, sinon la règle fine devient inatteignable.

**`path` est une expression régulière.**

**`access_control` ne remplace pas un votant** : il ignore les objets.

## Points clés

- Première correspondance gagnante ; ordonner du spécifique au général.
- `path` est une regex ; d'autres critères existent — `ips`, `host`, `methods`,
  `route`, `allow_if`, `requires_channel`.
- Tous les critères d'une règle doivent correspondre.
- Protège des URL, pas des objets.

## Sources officielles

- [How Does the Security access_control Work?](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/security/access_control.rst)
