---
id: CRS-222wq64mqxye
official_item: OIT-d3nyk9z0q2pd
title: "HTTP request"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpFoundation/Request.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/Request.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "public bag properties lines 94-130, getClientIp line 821"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpFoundation/InputBag.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/InputBag.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "InputBag::get, BadRequestException on non-scalar"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/components/http_foundation.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/components/http_foundation.rst"
    anchor: "accessing-request-data"
    symbol_or_lines: "\"A Request object holds information about the client request. This information can be accessed via several public properties\"; Request::createFromGlobals and Request::create examples"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    verified_at: "2026-09-15"
---

## Objectif

Choisir le bon sac de la `Request` et connaître la contrainte que `InputBag`
impose — c'est là que se situe le piège.

## D'où vient l'objet

```php
$request = Request::createFromGlobals();   // depuis $_GET, $_POST, $_COOKIE, $_FILES, $_SERVER
$request = Request::create('/search?q=php', 'GET');   // fabriquée de toutes pièces
```

`createFromGlobals()` est ce que fait le *front controller* : il emballe les
superglobales de PHP dans un objet. `create()` construit une requête sans
superglobales — c'est l'outil des tests et des sous-requêtes. Dans un
contrôleur, on n'appelle ni l'une ni l'autre : on type-hinte `Request` et
Symfony passe celle qui circule.

## Les sacs

`Request` expose sept propriétés publiques typées, chacune un sac spécialisé :

| Propriété | Type | Contenu |
|---|---|---|
| `$query` | `InputBag` | Paramètres d'URL (`$_GET`) |
| `$request` | `InputBag` | Corps de formulaire (`$_POST`) |
| `$cookies` | `InputBag` | Cookies |
| `$attributes` | `ParameterBag` | Données internes à l'application (route, `_route`, `_controller`) |
| `$headers` | `HeaderBag` | En-têtes |
| `$server` | `ServerBag` | `$_SERVER` |
| `$files` | `FileBag` | Fichiers téléversés |

Le nom `$request` désigne le **corps POST**, pas la requête entière : c'est une
source de confusion classique.

## InputBag n'accepte que des scalaires

C'est la différence de fond avec `ParameterBag`.

```php
// URL : /search?tags[]=php&tags[]=http
$request->query->get('tags');   // BadRequestException : valeur non scalaire
$request->query->all('tags');   // ['php', 'http'] — correct
```

`InputBag::get()` lève une `BadRequestException` si la valeur n'est pas un
scalaire. La raison est défensive : les données utilisateur sont hostiles, et un
tableau reçu là où un scalaire est attendu doit échouer bruyamment plutôt que de
se propager. Pour une valeur multiple, `all()` est l'accesseur prévu.

`$attributes` est un `ParameterBag` sans cette restriction, parce que son
contenu vient de l'application, pas du client.

## Corps brut et méthode

```php
$request->getContent();          // corps brut, utile pour du JSON
$request->getPayload();          // InputBag depuis JSON ou form-data
$request->isMethod('POST');      // comparaison insensible à la casse
$request->getMethod();           // méthode effective
```

`getPayload()` retourne un **`InputBag`** : la contrainte scalaire ci-dessus s'y
applique donc aussi, quel que soit le format d'entrée.

## Client et proxys

```php
$request->getClientIp();
```

Derrière un reverse proxy, cette valeur n'est fiable **que si** les proxys de
confiance sont déclarés (`Request::setTrustedProxies()` ou la configuration
`trusted_proxies` du framework). Sans cela, Symfony ignore délibérément
`X-Forwarded-For`, car un client peut le falsifier.

**Quelle adresse est renvoyée, une fois les proxys déclarés.** `X-Forwarded-For`
est une liste : chaque proxy traversé ajoute la sienne à droite. Symfony retire
les proxys de confiance puis renvoie `getClientIps()[0]`, c'est-à-dire la valeur
**la plus à gauche** — le client d'origine, pas le dernier intermédiaire.

```php
// X-Forwarded-For: 203.0.113.7, 10.0.0.1, 10.0.0.2   (10.0.0.* = proxys déclarés)
$request->getClientIp();    // '203.0.113.7'
$request->getClientIps();   // liste complète, proxys de confiance retirés
```

`getClientIps()` existe, mais son propre docblock conseille `getClientIp()` :
la liste sert aux cas où l'on doit inspecter la chaîne elle-même.

## Pièges d'examen

**`$request->request` n'est pas la requête** : c'est le corps POST.

**`get()` sur un tableau lève.** Ce n'est pas `null`, ni le premier élément.

**`getClientIp()` sans trusted proxies renvoie l'IP du proxy**, pas celle du
client — et c'est le comportement sûr.

**Avec trusted proxies, c'est la valeur la plus à gauche** de `X-Forwarded-For`
qui est renvoyée, pas la plus proche du serveur.

**`Request::create()` n'est pas `createFromGlobals()`.** La première fabrique
une requête arbitraire — tests, sous-requêtes ; la seconde lit les superglobales.

## Points clés

- Sept sacs typés ; `$request` = corps POST, `$attributes` = données internes.
- `InputBag::get()` exige un scalaire et lève sinon ; `all()` pour un tableau.
- `getPayload()` lit JSON comme form-data, et retourne un `InputBag`.
- `getClientIp()` n'est fiable qu'avec des trusted proxies déclarés, et renvoie
  alors l'adresse la plus à gauche de `X-Forwarded-For`.
- `createFromGlobals()` lit les superglobales ; `create()` fabrique une requête.

## Aller lire la source

- [Composant HttpFoundation](https://github.com/symfony/symfony-docs/blob/8.0/components/http_foundation.rst) — *Accessing Request Data*,
  `createFromGlobals()`, `create()`
- [`Request`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/Request.php) — sacs publics l. 94-130, `getClientIp()` l. 821,
  `getClientIps()` l. 798 (branche 8.0, `6f841c0`)
- [`InputBag`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/InputBag.php) — `get()` et sa `BadRequestException` (branche 8.0, `6f841c0`)
