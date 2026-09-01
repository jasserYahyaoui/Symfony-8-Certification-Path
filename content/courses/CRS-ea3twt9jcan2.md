---
id: CRS-ea3twt9jcan2
official_item: OIT-6cr9b8ea8g32
title: "The request"
content_level: MINIMAL
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/controller.rst"
    anchor: "the-request-object-as-a-controller-argument"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
---

## Objectif

Savoir comment un contrôleur obtient la requête et où atterrissent les
paramètres de route. Le modèle des sacs appartient au lot HTTP, le composant
lui-même au lot Symfony Architecture.

## L'obtenir

Il n'y a rien à faire : il suffit de **typer un argument** avec
`Symfony\Component\HttpFoundation\Request`. Symfony le remplit.

```php
public function index(Request $request): Response
```

Le nom de l'argument est libre ; c'est le type qui déclenche l'injection. Dans
un **service**, en revanche, la requête ne s'injecte pas : on injecte
`RequestStack` et on appelle `getCurrentRequest()`.

## Les paramètres de route

Un paramètre déclaré dans le chemin de la route est déposé dans
`$request->attributes`, puis passé au contrôleur **comme argument nommé** :

```php
#[Route('/lucky/number/{max}')]
public function number(int $max): Response
```

La correspondance se fait sur le **nom**, jamais sur la position. Réordonner les
arguments ne change rien ; les renommer casse tout.

Le sac `attributes` contient aussi les clés internes que le framework y place,
notamment `_route` et `_controller`.

## Corps et chaîne de requête

`$request->query` porte la chaîne de requête. Pour le corps, `getPayload()` est
la méthode à connaître : elle retourne les données envoyées, qu'elles arrivent
en formulaire ou en JSON, là où `$request->request` ne couvre que le premier
cas.

## Points clés

- Typer un argument `Request` suffit ; dans un service, injecter `RequestStack`.
- Les paramètres de route passent par `attributes` puis par le **nom** de
  l'argument.
- `_route` et `_controller` vivent dans `attributes`.
- `getPayload()` lit le corps quel que soit son format.

## Sources officielles

- [Controller, « The Request Object as a Controller Argument »](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/controller.rst)
