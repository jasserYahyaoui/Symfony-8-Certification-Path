---
id: CRS-k5wg55q2mg0p
official_item: OIT-bc3brs4a4mtt
title: "Client object"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-08"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/KernelBrowser.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/KernelBrowser.php"
    branch: "8.0"
    symbol_or_lines: "KernelBrowser"
    verified_at: "2026-09-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpKernel/HttpKernelBrowser.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/HttpKernelBrowser.php"
    branch: "8.0"
    symbol_or_lines: "__construct() — followRedirects = false"
    verified_at: "2026-10-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/BrowserKit/AbstractBrowser.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/BrowserKit/AbstractBrowser.php"
    branch: "8.0"
    symbol_or_lines: "$followRedirects = true; followRedirect(); xmlHttpRequest(); request() fills $redirects only when followRedirects; back(); forward()"
    verified_at: "2026-10-08"
---

## Objectif

Piloter l'application comme un navigateur : Requests, navigation, redirects,
authentication.

## Prérequis

Les functional tests avec `WebTestCase`.

## Ce qu'est le client

`static::createClient()` rend un `KernelBrowser`. Il **simule** un navigateur :
aucune socket n'est ouverte, aucun serveur web n'intervient. La Request est
construite en mémoire et passée au kernel. C'est pourquoi ces tests sont rapides
— et pourquoi ils n'exécutent aucun JavaScript.

Le client est disponible dans le container sous le service **`test.client`**, en
environment `test` ou partout où l'option `framework.test` est activée ; on
peut donc le remplacer entièrement.

## Faire une Request

```php
$crawler = $client->request('GET', '/post/hello-world');
```

La signature complète :

```php
request(
    string $method,
    string $uri,
    array $parameters = [],
    array $files = [],
    array $server = [],
    ?string $content = null,
    bool $changeHistory = true,
): Crawler
```

`request()` **retourne un `Crawler`**, pas une Response. La Response se lit par
`$client->getResponse()`.

Pour une Request AJAX, `xmlHttpRequest()` a les mêmes arguments et ajoute
le header `HTTP_X_REQUESTED_WITH` automatiquement — exécuté :
`$client->getRequest()->isXmlHttpRequest()` vaut `true`.

## Naviguer

```php
$client->back();
$client->forward();
$client->reload();
$client->restart();   // vide les cookies et l'historique
```

`back()` et `forward()` sautent les redirects, comme un navigateur — mais
**seulement celles que le client a suivies seul**, en mode `followRedirects()`
(voir plus bas). Lu dans le code 8.0 : `AbstractBrowser::request()` ne mémorise
un redirect que dans ce mode. Exécuté, `/created` puis `/go`, qui redirige
vers `/hello` :

| Mode du client | Après `back()` |
|---|---|
| `followRedirects()` | `/created`, 201 |
| par défaut, puis `followRedirect()` | `/go`, de nouveau 302 |

## Les redirects

Le client **ne suit pas** les redirects par défaut. Deux methods, à ne pas
confondre :

| Method | Effet |
|---|---|
| `followRedirect()` | suit **la** redirect en attente, une fois |
| `followRedirects()` | change le **mode** : toutes les suivantes seront suivies |
| `followRedirects(false)` | revient au comportement par défaut |

La différence est le singulier : l'une agit sur la Response courante, l'autre
règle le client. `followRedirects()` doit être appelée **avant** la Request.

Le défaut n'est pas celui de BrowserKit. Lu dans le code 8.0 :
`AbstractBrowser` déclare `$followRedirects = true`, mais `HttpKernelBrowser`
— parent de `KernelBrowser` — le passe à `false` dans son constructeur. Un
`HttpBrowser`, qui parle à un vrai serveur, suit donc les redirects ; le
client de test, non. Exécuté, `GET /go` qui redirige vers `/hello` :

| Client | Après `request()` | Après `followRedirect()` |
|---|---|---|
| `createClient()`, par défaut | 302, `isFollowingRedirects()` faux | 200 sur `/hello` |

## Plusieurs Requests dans un test

Après une Request, la suivante **redémarre le kernel** et reconstruit le
container, pour isoler les Requests. Conséquence pratique : le token de sécurité
est effacé entre deux Requests. Exécuté : deux Requests, deux objects container
différents ; après `disableReboot()`, le même container sert la Request
suivante.

Ce token effacé n'implique pas toujours une déconnexion. `loginUser()` le
place dans le stockage de tokens **et**, s'il y a une session, dans la session.
Exécuté avec SecurityBundle 8.0.15, `loginUser()` puis deux Requests :

| Firewall | 1ʳᵉ Request | 2ᵉ Request |
|---|---|---|
| avec état (par défaut) | `alice` | `alice` — restauré depuis la session |
| `stateless: true` | `alice` | anonyme |

`disableReboot()` réinitialise le kernel au lieu de le redémarrer — Symfony
appelle alors `reset()` sur les services marqués `kernel.reset`. Cela efface
également le token de sécurité ; pour le conserver, il faut retirer le tag
`kernel.reset` du service concerné par un compiler pass en environment
de test.

## S'authentifier

```php
$client->loginUser($user);
```

Rejouer un form de connexion à chaque test le rendrait lent.
`loginUser()` place directement le user dans le firewall — `main` par
défaut, modifiable par le deuxième argument.

## Voir l'exception

```php
$client->catchExceptions(false);
```

Par défaut les exceptions sont interceptées et rendues en page d'erreur ; le
test échoue alors sur un code 500 sans dire pourquoi. Cette method les laisse
remonter jusqu'à PHPUnit. Exécuté, un controller qui lève
`RuntimeException('kaboom')` : Response 500 par défaut, puis l'exception
elle-même après `catchExceptions(false)`.

## Pièges d'examen

**`request()` retourne un `Crawler`**, pas une `Response`.

**Le client de test ne suit pas les redirects par défaut** — alors que le
`AbstractBrowser` de BrowserKit, lui, les suit.

**`followRedirect()` ≠ `followRedirects()`** : une fois, contre un réglage.

**`back()` ne saute que les redirects suivis en mode `followRedirects()`** ;
après un `followRedirect()` manuel, il revient sur l'URL qui a redirigé.

**Le kernel redémarre entre deux Requests** et le token en mémoire est perdu ;
un firewall avec état le restaure depuis la session, un firewall `stateless`
non.

**Aucun JavaScript n'est exécuté** : c'est un client simulé, pas un navigateur.

## Points clés

- `createClient()` rend un `KernelBrowser`, disponible comme service `test.client`.
- `request()` rend un `Crawler` ; `getResponse()` rend la Response.
- `back()`, `forward()`, `reload()`, `restart()` pour naviguer.
- `followRedirect()` une fois, `followRedirects()` en mode.
- `loginUser()` pour s'authentifier, `catchExceptions(false)` pour déboguer.

## Sources officielles

- [Testing](https://github.com/symfony/symfony-docs/blob/8.0/testing.rst)
- [`KernelBrowser`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/KernelBrowser.php)
- [`HttpKernelBrowser`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/HttpKernelBrowser.php)
- [`AbstractBrowser`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/BrowserKit/AbstractBrowser.php)
