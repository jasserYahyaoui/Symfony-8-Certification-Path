---
id: CRS-xkp7v8142jt1
official_item: OIT-bzkq4e7wks9a
title: "Request and response objects introspection"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-02"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/testing.rst"
    anchor: "accessing-internal-objects"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    verified_at: "2026-09-02"
---

## Objectif

Lire ce que le client a réellement envoyé et reçu. La difficulté tient à ce que
**deux couches** existent, avec deux objets pour la requête et deux pour la
réponse.

## Prérequis

L'objet client, et les objets `Request` et `Response` de HttpFoundation.

## Les deux couches

BrowserKit simule le navigateur ; HttpKernel exécute l'application. Le client
expose les objets des deux côtés, et l'examen teste qu'on ne les confonde pas :

| Méthode | Objet rendu | Couche |
|---|---|---|
| `getRequest()` | `Request` de HttpFoundation | HttpKernel |
| `getInternalRequest()` | `Request` de BrowserKit | navigateur simulé |
| `getResponse()` | `Response` de HttpFoundation | HttpKernel |
| `getInternalResponse()` | `Response` de BrowserKit | navigateur simulé |

La règle de lecture : **`internal` désigne le navigateur**, pas l'application.
L'intuition inverse — « interne, donc plus proche du framework » — est
exactement ce sur quoi la question porte.

En pratique, c'est `getResponse()` qu'on utilise : c'est l'objet
`HttpFoundation\Response` que le contrôleur a produit, avec son code, ses
en-têtes et son contenu.

## Les autres objets internes

```php
$history   = $client->getHistory();     // l'historique de navigation
$cookieJar = $client->getCookieJar();   // les cookies accumulés
$crawler   = $client->getCrawler();     // le crawler de la dernière requête
```

`getCrawler()` évite de conserver la valeur rendue par `request()` quand on en a
besoin plus loin dans le test.

## Introspecter la réponse

```php
$response = $client->getResponse();

$response->headers->get('content-type');
$response->getContent();
```

L'objet est un `HttpFoundation\Response` ordinaire : son code, ses en-têtes et
son contenu se lisent comme partout ailleurs.

Ces lectures directes ont leur place, mais les assertions de Symfony sont
préférables pour ce qu'on affirme : `assertResponseStatusCodeSame(404)` affiche
la réponse réelle quand elle échoue, là où une comparaison brute n'affiche que
deux nombres.

## Introspecter la requête

```php
$request = $client->getRequest();

$request->attributes->get('_route');
$request->getPathInfo();
```

L'attribut `_route` dit **quelle route a répondu**. C'est ce que vérifie
`assertRouteSame()`, et c'est une information qu'aucune inspection du HTML ne
donne : deux routes différentes peuvent rendre la même page.

## Pièges d'examen

**`getInternalRequest()` est l'objet BrowserKit**, pas celui de l'application.

**`getRequest()` et `getResponse()` sont les objets HttpFoundation** — ceux que
le contrôleur a vus et produits.

**Il faut avoir fait une requête** : ces méthodes n'ont pas de sens avant.

**Préférer les assertions Symfony** aux lectures brutes, pour le message
d'échec.

## Points clés

- Deux couches : BrowserKit (navigateur) et HttpKernel (application).
- `internal` = BrowserKit ; sans préfixe = HttpFoundation.
- `getHistory()`, `getCookieJar()`, `getCrawler()` pour le reste.
- L'attribut `_route` identifie la route qui a répondu.

## Sources officielles

- [Testing](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/testing.rst)
