---
id: CRS-f92d09x14ggx
official_item: OIT-e2zrm9qkpx7j
title: "Conditional request matching"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/routing.rst"
    anchor: "matching-expressions"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
---

## Objectif

Conditionner l'appariement d'une route à une logique arbitraire, et connaître la
limite de ce mécanisme.

## L'option `condition`

Elle prend une expression du composant ExpressionLanguage, évaluée à
l'appariement. Si elle est fausse, la route ne correspond pas.

```php
#[Route(
    '/contact',
    name: 'contact',
    condition: "context.getMethod() in ['GET', 'HEAD'] and request.headers.get('User-Agent') matches '/firefox/i'",
)]
```

C'est la porte de sortie pour ce que `methods`, `host` et `schemes` ne savent
pas exprimer.

## Les variables disponibles

| Variable | Contenu |
|---|---|
| `context` | le `RequestContext` : méthode, hôte, schéma, port |
| `request` | l'objet `Request` complet |
| `params` | les paramètres de route déjà appariés |

`params` permet de conditionner sur une valeur d'URL :
`condition: "params['id'] < 1000"`.

Deux fonctions complètent le jeu :

- `env('NOM')` lit une variable d'environnement, avec ses processeurs ;
- `service('alias')` appelle un service déclaré par l'attribut
  `#[AsRoutingConditionService]` ou par le tag `routing.condition_service`.

Une expression peut aussi contenir un paramètre de configuration, entre
pourcents.

## La limite, et elle est importante

> Les conditions **ne sont pas prises en compte lors de la génération d'URL**.

Le générateur ne connaît ni la requête courante ni l'agent utilisateur : il ne
peut pas évaluer l'expression. Une route protégée par une `condition` reste donc
parfaitement générable, y compris vers un contexte où elle ne correspondrait
pas. Une condition filtre l'entrée, elle ne documente pas la sortie.

C'est la différence de fond avec `requirements`, qui compte dans les deux sens.

## Points clés

- `condition` = expression ExpressionLanguage évaluée à l'appariement.
- Variables : `context`, `request`, `params`. Fonctions : `env()`, `service()`.
- `#[AsRoutingConditionService]` expose un service à `service()`.
- Les conditions sont **ignorées** à la génération d'URL.

## Sources officielles

- [Routing, section « Matching Expressions »](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/routing.rst)
