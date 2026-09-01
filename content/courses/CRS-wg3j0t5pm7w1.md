---
id: CRS-wg3j0t5pm7w1
official_item: OIT-1cj08dhtp9hj
title: "Restrict URL parameters"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/routing.rst"
    anchor: "routing-requirements"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
---

## Objectif

Contraindre la valeur d'un paramètre de route, et savoir quand cette contrainte
s'applique.

## Le problème qu'elle résout

`/blog/{page}` et `/blog/{slug}` sont indiscernables : un paramètre accepte
n'importe quelle valeur. Sans contrainte, `/blog/mon-article` correspond aux
deux, et c'est la **première route déclarée** qui gagne.

`requirements` associe à chaque paramètre une **expression régulière PHP** que
la valeur doit vérifier pour que la route entière corresponde :

```php
#[Route('/blog/{page}', name: 'blog_list', requirements: ['page' => '[0-9]+'])]
```

Désormais `/blog/2` va à `blog_list` et `/blog/mon-article` à `blog_show`,
quel que soit l'ordre de déclaration.

## Trois façons de l'écrire

| Forme | Exemple |
|---|---|
| option `requirements` | `requirements: ['page' => '[0-9]+']` |
| inline dans le chemin | `/blog/{page<[0-9]+>}` |
| constante de l'énumération | `requirements: ['page' => Requirement::DIGITS]` |

L'énumération `Symfony\Component\Routing\Requirement\Requirement` rassemble les
expressions courantes — chiffres, dates, UUID — et évite de réécrire des
expressions régulières fragiles. En YAML elle s'utilise avec `!php/const`.

La forme inline est plus concise mais devient illisible dès que l'expression est
complexe ; c'est un arbitrage, pas une règle.

## Ce qui est autorisé

Une contrainte peut contenir un **paramètre de configuration**, ce qui permet de
définir une expression compliquée une fois et de la réutiliser. Elle accepte
aussi les **propriétés Unicode PCRE** : `\p{Lu}` correspond à toute majuscule,
dans n'importe quelle langue.

## Le piège

Une valeur par défaut **n'est pas tenue** de satisfaire la contrainte. La
documentation le dit explicitement. La contrainte filtre l'URL entrante ; la
valeur par défaut, elle, n'est pas dans l'URL.

## Points clés

- `requirements` = expression régulière PHP ; toute la route échoue si elle n'est
  pas vérifiée.
- Trois écritures : option, inline `{p<regex>}`, énumération `Requirement`.
- Une contrainte peut contenir un paramètre de configuration.
- La valeur par défaut peut ne pas respecter la contrainte.

## Sources officielles

- [Routing, section « Parameters Validation »](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/routing.rst)
