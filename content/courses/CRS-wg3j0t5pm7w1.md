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
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/routing.rst"
    anchor: "routing-requirements"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Routing/Generator/UrlGenerator.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Generator/UrlGenerator.php"
    symbol_or_lines: "strictRequirements, doGenerate"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/Configuration.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/Configuration.php"
    symbol_or_lines: "strict_requirements"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Routing/Requirement/Requirement.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Requirement/Requirement.php"
    symbol_or_lines: "DIGITS, POSITIVE_INT"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
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

C'est une énumération **sans aucun cas** : elle ne sert qu'à porter des
constantes, qui sont de simples chaînes. Deux d'entre elles se confondent :

| Constante | Expression | Accepte `0` ? |
|---|---|---|
| `Requirement::DIGITS` | `[0-9]+` | oui, et `007` aussi |
| `Requirement::POSITIVE_INT` | `[1-9][0-9]*` | non |

Pour un numéro de page, `DIGITS` laisse passer `/blog/0`.

La forme inline est plus concise mais devient illisible dès que l'expression est
complexe ; c'est un arbitrage, pas une règle.

## Ce qui est autorisé

Une contrainte peut contenir un **paramètre de configuration**, ce qui permet de
définir une expression compliquée une fois et de la réutiliser. Elle accepte
aussi les **propriétés Unicode PCRE** : `\p{Lu}` correspond à toute majuscule,
dans n'importe quelle langue.

## Deux pièges

Une valeur par défaut **n'est pas tenue** de satisfaire la contrainte. La
documentation le dit explicitement. La contrainte filtre l'URL entrante ; la
valeur par défaut, elle, n'est pas dans l'URL.

### La contrainte s'applique aussi en génération

Le générateur relit chaque contrainte avant de produire l'URL. Ce qu'il fait
d'une valeur non conforme dépend de l'option `framework.router.strict_requirements` :

| Valeur | Effet d'une valeur non conforme |
|---|---|
| `true` — **défaut** | `InvalidParameterException` : « Parameter "page" for route "blog_list" must match… » |
| `false` | erreur journalisée, et le générateur rend une **chaîne vide** |
| `null` | aucune vérification |

Le texte d'aide de l'option annonce `null` pour le cas `false` ; le code de la
branche 8.0 retourne `''`. La même aide suggère `true` en développement,
`false` ou `null` en production.

## Pièges d'examen

**Sans contrainte, deux routes de même forme sont départagées par l'ordre de
déclaration**, pas par ce qui « ressemble » le plus. Contraindre le paramètre
rend l'ordre indifférent — c'est le vrai service rendu.

**Une contrainte est une expression régulière PHP**, avec ce que cela implique :
les propriétés Unicode fonctionnent, et une expression fragile passe sans que
rien ne prévienne.

**La contrainte vaut aussi à la génération.** Générer `blog_list` avec
`page: 'abc'` lève une exception par défaut.

## Points clés

- `requirements` = expression régulière PHP ; toute la route échoue si elle n'est
  pas vérifiée.
- Trois écritures : option, inline `{p<regex>}`, énumération `Requirement`.
- Une contrainte peut contenir un paramètre de configuration.
- La valeur par défaut peut ne pas respecter la contrainte.
- Le générateur vérifie la contrainte : exception par défaut
  (`strict_requirements: true`).
- `DIGITS` accepte `0` ; `POSITIVE_INT` non.

## Sources officielles

- [Routing, section « Parameters Validation »](https://github.com/symfony/symfony-docs/blob/8.0/routing.rst)
