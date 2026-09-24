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
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/routing.rst"
    anchor: "routing-locale-parameter"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Routing/Attribute/Route.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Attribute/Route.php"
    symbol_or_lines: "__construct"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Routing/Loader/YamlFileLoader.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Loader/YamlFileLoader.php"
    symbol_or_lines: "AVAILABLE_KEYS"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Routing/Generator/UrlGenerator.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Generator/UrlGenerator.php"
    symbol_or_lines: "doGenerate"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Routing/Route.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Route.php"
    symbol_or_lines: "addDefaults"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Routing/RouteCollection.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/RouteCollection.php"
    symbol_or_lines: "addDefaults"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpFoundation/Response.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/Response.php"
    symbol_or_lines: "prepare"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpKernel/EventListener/LocaleListener.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/EventListener/LocaleListener.php"
    symbol_or_lines: "setLocale"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/php/php-src/PHP-8.4/Zend/tests/named_params/unknown_named_param.phpt"
    readable_url: "https://github.com/php/php-src/blob/PHP-8.4/Zend/tests/named_params/unknown_named_param.phpt"
    symbol_or_lines: "Unknown named parameter"
    repository: "php/php-src"
    branch: "PHP-8.4"
    verified_at: "2026-09-24"
---

## Objectif

Connaître les paramètres que Symfony réserve, leur effet réel dans le code 8.0,
et les formes courtes qui les posent — celles qui existent vraiment.

## Les cinq paramètres réservés

Outre les paramètres de l'application, une route peut porter ceux-ci, tous
préfixés d'un souligné. La documentation en liste cinq :

| Paramètre | Effet | Qui le lit (8.0) |
|---|---|---|
| `_controller` | désigne le contrôleur exécuté quand la route correspond | le résolveur de contrôleur |
| `_format` | fixe le *request format* de la requête | `Request::getRequestFormat()` |
| `_locale` | fixe la locale de la requête | `LocaleListener` |
| `_fragment` | ajoute l'identifiant de fragment — la partie après `#` | `UrlGenerator` |
| `_query` | ajoute des paramètres de chaîne de requête à l'URL générée | `UrlGenerator` |

`_format` mérite un mot : la valeur appariée devient le format de la requête.
Au moment de `Response::prepare()`, si la réponse **n'a pas encore** d'en-tête
`Content-Type`, Symfony en pose un d'après ce format — `json` donne
`application/json`. Un contrôleur qui fixe lui-même l'en-tête l'emporte. C'est
ce qui permet à `/search.json` et `/search.xml` de partager une route.

## Les formes courtes

Le constructeur de `#[Route]` (8.0) accepte des arguments nommés sans souligné,
qu'il recopie :

| Argument | Devient |
|---|---|
| `locale: 'en'` | la valeur par défaut `_locale` |
| `format: 'html'` | la valeur par défaut `_format` |
| `stateless: true` | la valeur par défaut `_stateless` |
| `utf8: true` | l'**option** `utf8`, pas une valeur par défaut |

```php
#[Route(
    path: '/articles/{_locale}/search.{_format}',
    locale: 'en',
    format: 'html',
    requirements: ['_locale' => 'en|fr', '_format' => 'html|xml'],
)]
```

Les deux écritures désignent la même chose : l'argument nommé pose simplement
la valeur par défaut du paramètre réservé correspondant. En YAML, les clés
`locale`, `format` et `stateless` jouent le même rôle.

**Écart avec la documentation.** Son exemple ajoute `query: ['page' => 1]`, à
l'attribut comme en YAML. Le code 8.0 n'a pas d'argument `query` dans le
constructeur de `#[Route]` : PHP rejette un argument nommé inconnu, par une
`Error` « Unknown named parameter » (test `unknown_named_param.phpt` de
php-src). Et `query` ne figure pas dans la liste des clés acceptées par le
chargeur YAML, qui lève une `InvalidArgumentException` pour clé non supportée.

## `_query` n'agit qu'à la génération

`UrlGenerator::doGenerate()` lit `_query` **uniquement** dans les paramètres
passés à `generate()` — donc à `path()` ou `url()` en Twig. Une valeur `_query`
posée dans les valeurs par défaut de la route n'apparaît pas dans l'URL
générée : le code ne la lit pas. La documentation la présente pourtant comme
utilisable dans une route ou un import.

`_fragment`, lui, est lu aux deux endroits : la valeur par défaut de la route
d'abord, remplacée par un paramètre `_fragment` passé à la génération.

## La limite documentée : `_fragment` et les imports

La documentation dit que ces paramètres s'emploient aussi bien dans une route
individuelle que dans un **import** de routes — à une exception près :
`_fragment`, qui ne s'utilise que dans une route.

Le code 8.0 ne fait pas respecter cette règle : les `defaults` d'un import sont
recopiés sur chaque route par `RouteCollection::addDefaults()`, sans contrôle
de nom. Retenez la règle documentée, c'est elle qu'une question citera.

Une autre subtilité, cette fois dans le code : `Route::addDefaults()` **ignore**
un `_locale` hérité d'un import pour une route localisée — celle-ci garde sa
propre locale.

## Pièges d'examen

**Les paramètres réservés commencent tous par un souligné ; les arguments
courts de l'attribut, non.** Ce sont deux écritures de la même chose :
l'argument pose la valeur par défaut du paramètre réservé.

**Un seul des cinq est exclu des imports par la documentation** : celui qui
pose l'identifiant de fragment. Rien dans le code ne le rejette.

**Pas d'argument `query` dans `#[Route]` en 8.0**, malgré l'exemple officiel.
`_query` se passe à la génération.

**Le format de requête ne décide du `Content-Type` qu'en l'absence d'en-tête.**
C'est ce qui permet à deux extensions d'URL de partager une seule route.

## Points clés

- Cinq paramètres réservés documentés : `_controller`, `_format`, `_locale`,
  `_fragment`, `_query`.
- `_format` fixe le format de requête ; `Response::prepare()` en déduit le
  `Content-Type` s'il manque.
- Formes courtes de `#[Route]` en 8.0 : `locale`, `format`, `stateless` ;
  `utf8` pose une option.
- `_query` : seulement dans les paramètres de génération.
- Tous utilisables dans un import, **sauf** `_fragment` selon la
  documentation.

## Sources officielles

- [Routing, section « Special Parameters »](https://github.com/symfony/symfony-docs/blob/8.0/routing.rst)
- [`Attribute\Route::__construct()`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Attribute/Route.php)
- [`YamlFileLoader::AVAILABLE_KEYS`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Loader/YamlFileLoader.php)
- [`UrlGenerator::doGenerate()`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Generator/UrlGenerator.php)
- [`Route::addDefaults()`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Route.php) et [`RouteCollection::addDefaults()`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/RouteCollection.php)
- [`Response::prepare()`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/Response.php) et [`LocaleListener`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/EventListener/LocaleListener.php)
- [php-src, `unknown_named_param.phpt`](https://github.com/php/php-src/blob/PHP-8.4/Zend/tests/named_params/unknown_named_param.phpt)
