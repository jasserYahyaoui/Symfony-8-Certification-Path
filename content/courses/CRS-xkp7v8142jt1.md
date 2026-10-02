---
id: CRS-xkp7v8142jt1
official_item: OIT-bzkq4e7wks9a
title: "Request and response objects introspection"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-02"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/testing.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/testing.rst"
    anchor: "accessing-internal-objects"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    verified_at: "2026-09-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/BrowserKit/AbstractBrowser.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/BrowserKit/AbstractBrowser.php"
    branch: "8.0"
    symbol_or_lines: "getRequest(); getInternalRequest(); getResponse(); getInternalResponse(); getCrawler(); getHistory(); getCookieJar()"
    verified_at: "2026-10-02"
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

| Méthode | Classe rendue (exécuté, 8.0) | Couche |
|---|---|---|
| `getRequest()` | `HttpFoundation\Request` | application |
| `getInternalRequest()` | `BrowserKit\Request` | navigateur simulé |
| `getResponse()` | `HttpFoundation\Response` | application |
| `getInternalResponse()` | `BrowserKit\Response` | navigateur simulé |

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

| Méthode | Classe rendue (exécuté, 8.0) |
|---|---|
| `getHistory()` | `BrowserKit\History` |
| `getCookieJar()` | `BrowserKit\CookieJar` |
| `getCrawler()` | `DomCrawler\Crawler` |

`getCrawler()` évite de conserver la valeur rendue par `request()` quand on en a
besoin plus loin dans le test.

## Avant la première requête

Les quatre objets de requête et de réponse, ainsi que le crawler, n'existent pas
encore. Le client ne rend pas `null` : il **lève une exception**. Exécuté sur
BrowserKit 8.0.14 :

| Appel avant toute requête | Résultat |
|---|---|
| `getRequest()`, `getResponse()` | `BadMethodCallException` |
| `getInternalRequest()`, `getInternalResponse()` | `BadMethodCallException` |
| `getCrawler()` | `BadMethodCallException` |
| `getHistory()` | un historique vide |
| `getCookieJar()` | un pot utilisable : on peut y poser un cookie |

Le message nomme la méthode fautive : *The "request()" method must be called
before …*. L'historique et le pot de cookies, eux, existent dès la création du
client — c'est ce qui permet de poser un cookie avant la première requête.

## Introspecter la réponse

```php
$response = $client->getResponse();

$response->headers->get('content-type');
$response->getContent();
```

L'objet est un `HttpFoundation\Response` ordinaire : son code, ses en-têtes et
son contenu se lisent comme partout ailleurs.

Ces lectures directes ont leur place, mais les assertions de Symfony sont
préférables pour ce qu'on affirme. Exécuté sur une réponse 200 :

| Assertion qui échoue | Message d'échec |
|---|---|
| `assertSame()` sur le code de statut brut | deux nombres : 200 contre 404 |
| `assertResponseStatusCodeSame(404)` | le code attendu, puis la réponse entière : statut, en-têtes, corps |

La seconde montre **pourquoi** la réponse n'est pas celle attendue.

## Introspecter la requête

```php
$request = $client->getRequest();

$request->attributes->get('_route');
$request->getPathInfo();
```

L'attribut `_route` dit **quelle route a répondu**. C'est ce que vérifie
`assertRouteSame()`, et c'est une information qu'aucune inspection du HTML ne
donne : deux routes différentes peuvent rendre la même page.

Le nom de la route est sous `_route` ; le contrôleur appelé est sous
`_controller`, au format `Classe::méthode`.

Les deux requêtes n'exposent pas l'adresse de la même façon. Exécuté sur
`/hello` :

| Lecture | Valeur |
|---|---|
| `getInternalRequest()->getUri()` | `http://localhost/hello` |
| `getRequest()->getPathInfo()` | `/hello` |
| `getRequest()->getUri()` | `http://localhost/hello` |

## Après une redirection

Le client ne suit pas les redirections par défaut. Après une requête sur une
route qui redirige, `getRequest()` est **la requête qui a redirigé**, pas sa
cible. Exécuté :

| Étape | `getResponse()` | `_route` |
|---|---|---|
| `request('GET', '/go')` | 302 | `go` |
| `followRedirect()` | 200 | `hello` |

L'historique conserve les deux adresses : `current()` rend la cible,
`back()` revient à la page qui a redirigé.

## Pièges d'examen

**`getInternalRequest()` est l'objet BrowserKit**, pas celui de l'application.

**`getRequest()` et `getResponse()` sont les objets HttpFoundation** — ceux que
le contrôleur a vus et produits.

**Avant toute requête, ils lèvent `BadMethodCallException`** — ils ne rendent pas
`null`. L'historique et les cookies, eux, existent déjà.

**Une redirection non suivie** laisse `_route` sur la route qui a redirigé.

**Préférer les assertions Symfony** aux lectures brutes, pour le message
d'échec.

## Points clés

- Deux couches : BrowserKit (navigateur) et HttpKernel (application).
- `internal` = BrowserKit ; sans préfixe = HttpFoundation.
- `getHistory()`, `getCookieJar()`, `getCrawler()` pour le reste.
- Avant la première requête : exception, sauf historique et cookies.
- L'attribut `_route` identifie la route qui a répondu ; `_controller`, le
  contrôleur.

## Sources officielles

- [Testing](https://github.com/symfony/symfony-docs/blob/8.0/testing.rst)
- [`AbstractBrowser`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/BrowserKit/AbstractBrowser.php)
