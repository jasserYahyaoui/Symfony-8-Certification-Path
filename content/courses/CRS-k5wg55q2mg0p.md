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

Piloter l'application comme un navigateur : requêtes, navigation, redirections,
authentification.

## Prérequis

Les tests fonctionnels avec `WebTestCase`.

## Ce qu'est le client

`static::createClient()` rend un `KernelBrowser`. Il **simule** un navigateur :
aucune socket n'est ouverte, aucun serveur web n'intervient. La requête est
construite en mémoire et passée au noyau. C'est pourquoi ces tests sont rapides
— et pourquoi ils n'exécutent aucun JavaScript.

Le client est disponible dans le conteneur sous le service **`test.client`**, en
environnement `test` ou partout où l'option `framework.test` est activée ; on
peut donc le remplacer entièrement.

## Faire une requête

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

`request()` **retourne un `Crawler`**, pas une réponse. La réponse se lit par
`$client->getResponse()`.

Pour une requête AJAX, `xmlHttpRequest()` a les mêmes arguments et ajoute
l'en-tête `HTTP_X_REQUESTED_WITH` automatiquement — exécuté :
`$client->getRequest()->isXmlHttpRequest()` vaut `true`.

## Naviguer

```php
$client->back();
$client->forward();
$client->reload();
$client->restart();   // vide les cookies et l'historique
```

`back()` et `forward()` sautent les redirections, comme un navigateur — mais
**seulement celles que le client a suivies seul**, en mode `followRedirects()`
(voir plus bas). Lu dans le code 8.0 : `AbstractBrowser::request()` ne mémorise
une redirection que dans ce mode. Exécuté, `/created` puis `/go`, qui redirige
vers `/hello` :

| Mode du client | Après `back()` |
|---|---|
| `followRedirects()` | `/created`, 201 |
| par défaut, puis `followRedirect()` | `/go`, de nouveau 302 |

## Les redirections

Le client **ne suit pas** les redirections par défaut. Deux méthodes, à ne pas
confondre :

| Méthode | Effet |
|---|---|
| `followRedirect()` | suit **la** redirection en attente, une fois |
| `followRedirects()` | change le **mode** : toutes les suivantes seront suivies |
| `followRedirects(false)` | revient au comportement par défaut |

La différence est le singulier : l'une agit sur la réponse courante, l'autre
règle le client. `followRedirects()` doit être appelée **avant** la requête.

Le défaut n'est pas celui de BrowserKit. Lu dans le code 8.0 :
`AbstractBrowser` déclare `$followRedirects = true`, mais `HttpKernelBrowser`
— parent de `KernelBrowser` — le passe à `false` dans son constructeur. Un
`HttpBrowser`, qui parle à un vrai serveur, suit donc les redirections ; le
client de test, non. Exécuté, `GET /go` qui redirige vers `/hello` :

| Client | Après `request()` | Après `followRedirect()` |
|---|---|---|
| `createClient()`, par défaut | 302, `isFollowingRedirects()` faux | 200 sur `/hello` |

## Plusieurs requêtes dans un test

Après une requête, la suivante **redémarre le noyau** et reconstruit le
conteneur, pour isoler les requêtes. Conséquence pratique : le jeton de sécurité
est effacé entre deux requêtes. Exécuté : deux requêtes, deux objets conteneur
différents ; après `disableReboot()`, le même conteneur sert la requête
suivante.

Ce jeton effacé n'implique pas toujours une déconnexion. `loginUser()` le
place dans le stockage de jetons **et**, s'il y a une session, dans la session.
Exécuté avec SecurityBundle 8.0.15, `loginUser()` puis deux requêtes :

| Pare-feu | 1ʳᵉ requête | 2ᵉ requête |
|---|---|---|
| avec état (par défaut) | `alice` | `alice` — restauré depuis la session |
| `stateless: true` | `alice` | anonyme |

`disableReboot()` réinitialise le noyau au lieu de le redémarrer — Symfony
appelle alors `reset()` sur les services marqués `kernel.reset`. Cela efface
également le jeton de sécurité ; pour le conserver, il faut retirer l'étiquette
`kernel.reset` du service concerné par une passe de compilation en environnement
de test.

## S'authentifier

```php
$client->loginUser($user);
```

Rejouer un formulaire de connexion à chaque test le rendrait lent.
`loginUser()` place directement l'utilisateur dans le pare-feu — `main` par
défaut, modifiable par le deuxième argument.

## Voir l'exception

```php
$client->catchExceptions(false);
```

Par défaut les exceptions sont interceptées et rendues en page d'erreur ; le
test échoue alors sur un code 500 sans dire pourquoi. Cette méthode les laisse
remonter jusqu'à PHPUnit. Exécuté, un contrôleur qui lève
`RuntimeException('kaboom')` : réponse 500 par défaut, puis l'exception
elle-même après `catchExceptions(false)`.

## Pièges d'examen

**`request()` retourne un `Crawler`**, pas une `Response`.

**Le client de test ne suit pas les redirections par défaut** — alors que le
`AbstractBrowser` de BrowserKit, lui, les suit.

**`followRedirect()` ≠ `followRedirects()`** : une fois, contre un réglage.

**`back()` ne saute que les redirections suivies en mode `followRedirects()`** ;
après un `followRedirect()` manuel, il revient sur l'URL qui a redirigé.

**Le noyau redémarre entre deux requêtes** et le jeton en mémoire est perdu ;
un pare-feu avec état le restaure depuis la session, un pare-feu `stateless`
non.

**Aucun JavaScript n'est exécuté** : c'est un client simulé, pas un navigateur.

## Points clés

- `createClient()` rend un `KernelBrowser`, disponible comme service `test.client`.
- `request()` rend un `Crawler` ; `getResponse()` rend la réponse.
- `back()`, `forward()`, `reload()`, `restart()` pour naviguer.
- `followRedirect()` une fois, `followRedirects()` en mode.
- `loginUser()` pour s'authentifier, `catchExceptions(false)` pour déboguer.

## Sources officielles

- [Testing](https://github.com/symfony/symfony-docs/blob/8.0/testing.rst)
- [`KernelBrowser`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/KernelBrowser.php)
- [`HttpKernelBrowser`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/HttpKernelBrowser.php)
- [`AbstractBrowser`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/BrowserKit/AbstractBrowser.php)
