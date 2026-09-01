---
id: CRS-nhebf7arqvn4
official_item: OIT-4pgc74ctc3vc
title: "Special internal routing attributes"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/routing.rst"
    anchor: "routing-locale-parameter"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
---

## Objectif

Connaître les paramètres que Symfony réserve, leur effet, et la forme courte que
l'attribut `#[Route]` en donne.

## Les cinq paramètres réservés

Outre les paramètres de l'application, une route peut porter ceux-ci, tous
préfixés d'un souligné :

| Paramètre | Effet |
|---|---|
| `_controller` | désigne le contrôleur exécuté quand la route correspond |
| `_format` | fixe le *request format* de la requête, d'où le `Content-Type` de la réponse |
| `_locale` | fixe la locale de la requête |
| `_fragment` | ajoute l'identifiant de fragment — la partie après `#` |
| `_query` | ajoute des paramètres de chaîne de requête à l'URL générée |

`_format` mérite un mot : la valeur appariée devient le format de la requête, et
`json` se traduit ensuite par un `Content-Type: application/json`. C'est ce qui
permet à `/search.json` et `/search.xml` de partager une route.

## Les formes courtes

L'attribut `#[Route]` expose ces paramètres sans le souligné, comme options
nommées — `locale`, `format`, `query` :

```php
#[Route(
    path: '/articles/{_locale}/search.{_format}',
    locale: 'en',
    format: 'html',
    query: ['page' => 1],
    requirements: ['_locale' => 'en|fr', '_format' => 'html|xml'],
)]
```

Les deux écritures désignent la même chose : l'option nommée pose simplement la
valeur par défaut du paramètre réservé correspondant.

## La limite

Ces paramètres s'emploient aussi bien dans une route individuelle que dans un
**import** de routes — à une exception près : `_fragment`, qui ne s'utilise que
dans une route.

## Points clés

- Cinq paramètres réservés : `_controller`, `_format`, `_locale`, `_fragment`,
  `_query`.
- `_format` fixe le format de requête, donc le type de contenu de la réponse.
- `locale`, `format` et `query` sont les formes courtes de l'attribut.
- Tous sont utilisables dans un import, **sauf** `_fragment`.

## Sources officielles

- [Routing, section « Special Parameters »](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/routing.rst)
