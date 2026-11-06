---
id: CRS-nhebf7arqvn4
official_item: OIT-4pgc74ctc3vc
title: "Special internal routing attributes"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
format: pedagogical
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
outcome_sections:
  OUT-zsdbtcwh25g5: "Les cinq parameters réservés"
  OUT-zg0vs74c3yef: "Les cinq parameters réservés"
  OUT-4k0zcm86nfkk: "Les formes courtes"
  OUT-8f5nc3sevst2: "La limite documentée : `_fragment` et les imports"
---

## Objectif

Connaître les parameters que Symfony réserve, leur effet réel dans le code 8.0,
et les formes courtes qui les posent — celles qui existent vraiment.

## L'essentiel

Cinq parameters préfixés d'un souligné sont réservés : `_controller`,
`_format`, `_locale`, `_fragment`, `_query`. `#[Route]` pose les trois premiers
par des arguments courts — `locale`, `format`, `stateless` — mais n'a **pas**
d'argument `query`, malgré l'exemple officiel. `_query` n'agit qu'à la
génération, et `_fragment` est le seul que la documentation exclut des imports.

## Le schéma

```text
_controller   controller exécuté                     → resolver de controller
_format       format de la Request                   → Response::prepare() pose Content-Type s'il manque
_locale       locale de la Request                   → LocaleListener
_fragment     #ancre de l'URL générée                → default de la route, remplacée par celle de generate()
_query        query string de l'URL générée          → lue SEULEMENT dans les parameters de generate()
#[Route]      locale: → _locale · format: → _format · stateless: → _stateless · utf8: → option utf8
              query: → Error « Unknown named parameter » ; en YAML, key query → InvalidArgumentException
import        defaults recopiés sur chaque route ; une route localisée garde son _locale
```

## L'exemple

```php
#[Route(
    path: '/articles/{_locale}/search.{_format}',
    locale: 'en',
    format: 'html',
    requirements: ['_locale' => 'en|fr', '_format' => 'html|xml'],
)]
```

## Les cinq parameters réservés

| Parameter | Effet | Qui le lit (8.0) |
|---|---|---|
| `_controller` | le controller exécuté quand la route correspond | le resolver de controller |
| `_format` | le *request format* de la Request | `Request::getRequestFormat()` |
| `_locale` | la locale de la Request | `LocaleListener` |
| `_fragment` | la partie après `#` de l'URL générée | `UrlGenerator` |
| `_query` | des parameters de query string dans l'URL générée | `UrlGenerator` |

À `Response::prepare()`, si la Response **n'a pas** de header `Content-Type`,
Symfony en pose un d'après le format : `json` donne `application/json`. Un
controller qui fixe le header l'emporte. Ainsi `/search.json` et `/search.xml`
partagent une route.

## Les formes courtes

Le constructor de `#[Route]` recopie `locale`, `format` et `stateless` en
default values `_locale`, `_format` et `_stateless` ; `utf8: true` devient
l'**option** `utf8`. En YAML, les keys `locale`, `format` et `stateless` font de
même.

**Écart avec la documentation.** Son exemple ajoute `query: ['page' => 1]`. En
8.0, `#[Route]` n'a pas cet argument : PHP lève une `Error` « Unknown named
parameter ». Le loader YAML ne connaît pas non plus la key `query` et lève une
`InvalidArgumentException`.

## `_query` n'agit qu'à la génération

`UrlGenerator::doGenerate()` lit `_query` **uniquement** dans les parameters
passés à `generate()` — donc à `path()` ou `url()`. Posée en default value de
la route, elle n'apparaît pas dans l'URL. `_fragment`, lui, est lu aux deux
endroits : la default value d'abord, remplacée par un `_fragment` passé à la
génération.

## La limite documentée : `_fragment` et les imports

Selon la documentation, ces parameters s'emploient dans une route comme dans un
**import**, sauf `_fragment`, réservé à une route. Le code 8.0 ne contrôle rien :
`RouteCollection::addDefaults()` recopie tous les `defaults` d'un import. Retenez
la règle documentée. Autre détail du code : une route localisée **ignore** un
`_locale` hérité d'un import et garde sa propre locale.

## Pièges d'examen

**Les parameters réservés commencent tous par un souligné ; les arguments
courts de l'attribute, non.** Ce sont deux écritures de la même chose :
l'argument pose la default value du parameter réservé.

**Un seul des cinq est exclu des imports par la documentation** : celui qui
pose l'identifier de fragment. Rien dans le code ne le rejette.

**Pas d'argument `query` dans `#[Route]` en 8.0**, malgré l'exemple officiel.
`_query` se passe à la génération.

**Le format de Request ne décide du `Content-Type` qu'en l'absence de header.**
C'est ce qui permet à deux extensions d'URL de partager une seule route.

## Application

- Produire `/articles?page=2#top` en Twig →
  `path('articles', {_query: {page: 2}, _fragment: 'top'})`.
- Une route API sans session → `stateless: true`.

## QCM d'entraînement

**Question 1.** La route `/articles` déclare la default value
`_query: {page: 1}`, et rien d'autre. Que produit `generate('articles')` ?

- **A.** `/articles`
- **B.** `/articles?page=1`
- **C.** Une exception : key réservée
- **D.** `/articles/1`

<details>
<summary>Correction</summary>

**Bonne réponse : A.** Le generator ne lit `_query` que dans les parameters
qu'on lui passe ; une default value de route n'est jamais consultée.

</details>

**Question 2.** Une route a la default value `_fragment: 'top'`, et la
génération reçoit aussi `_fragment: 'c'`. Quelle ancre figure dans l'URL ?

- **A.** La default value de la route
- **B.** Celle de la génération
- **C.** Les deux, concaténées
- **D.** Aucune : conflit

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Le generator part de la default value, puis la remplace
par le `_fragment` passé à `generate()` : l'URL finit par `#c`.

</details>

**Question 3.** Une route YAML recopie l'exemple officiel avec la key
`query: { page: 1 }`. Que se passe-t-il au chargement ?

- **A.** La key est ignorée
- **B.** Une default value `_query` est posée
- **C.** Une `InvalidArgumentException`
- **D.** La query string devient obligatoire au match

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Le loader YAML refuse toute key absente de sa liste de
keys acceptées, et `query` n'y figure pas en 8.0.

</details>

**Question 4.** Un import pose `locale: 'fr'` sur un group qui contient la route
localisée `about`, déclinée en `en` et `de`. Quelle locale garde `about.en` ?

- **A.** `fr`, celle de l'import
- **B.** `null`
- **C.** Une exception : conflit de locales
- **D.** `en`

<details>
<summary>Correction</summary>

**Bonne réponse : D.** `Route::addDefaults()` ignore le `_locale` hérité quand
la route est localisée : chaque variante garde sa propre locale.

</details>

## Fiche express

**Si tu dois retenir seulement 6 choses :**

- Réservés : **`_controller`**, **`_format`**, **`_locale`**, **`_fragment`**, **`_query`**.
- `_format` → `Content-Type` **seulement s'il manque**.
- `#[Route]` : `locale`, `format`, **`stateless`** ; `utf8` est une **option**.
- **Pas** d'argument `query` en 8.0 (Error) ; en YAML, exception.
- `_query` : **génération** seulement ; `_fragment` : défaut puis génération.
- Imports : tout sauf **`_fragment`** (documentation) ; route localisée garde sa locale.

Moyen mnémotechnique : **« Query se demande, il ne se déclare pas »**.

## Sources officielles

- [Routing, section « Special Parameters »](https://github.com/symfony/symfony-docs/blob/8.0/routing.rst)
- [`Attribute\Route::__construct()`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Attribute/Route.php)
- [`YamlFileLoader::AVAILABLE_KEYS`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Loader/YamlFileLoader.php)
- [`UrlGenerator::doGenerate()`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Generator/UrlGenerator.php)
- [`Route::addDefaults()`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Route.php) et [`RouteCollection::addDefaults()`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/RouteCollection.php)
- [`Response::prepare()`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/Response.php) et [`LocaleListener`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/EventListener/LocaleListener.php)
- [php-src, `unknown_named_param.phpt`](https://github.com/php/php-src/blob/PHP-8.4/Zend/tests/named_params/unknown_named_param.phpt)

