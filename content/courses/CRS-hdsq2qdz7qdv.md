---
id: CRS-hdsq2qdz7qdv
official_item: OIT-jh06tkfrhthq
title: "HTTP response"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-07"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpFoundation/Response.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/Response.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "isRedirect line 1254, isRedirection line 1194, isSuccessful line 1184"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Runtime/Runner/Symfony/HttpKernelRunner.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Runtime/Runner/Symfony/HttpKernelRunner.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "run(): send(false) ligne 36, terminate() ligne 48"
    verified_at: "2026-09-16"
---

## Objectif

Construire la bonne sous-classe de `Response` et distinguer ses methods de
test — dont deux sont dangereusement proches.

## La class de base

```php
$response = new Response('<h1>Bonjour</h1>', Response::HTTP_OK, ['content-type' => 'text/html']);
$response->headers->set('X-Custom', 'value');
```

`$response->headers` est un `ResponseHeaderBag`, qui gère aussi les cookies.

Une `Response` fraîchement construite porte **`HTTP/1.0`** : c'est
`prepare(Request $request)` qui la promeut en 1.1 le cas échéant, vide le body
sur un `HEAD` ou un statut sans corps, et complète le `Content-Type`. Le kernel
l'appelle pour vous ; une Response fabriquée hors du cycle, non.

## Les sous-classes

| Class | Usage |
|---|---|
| `JsonResponse` | Encode un array en JSON et pose le `Content-Type` |
| `RedirectResponse` | Pose `Location` et un statut 302 par défaut |
| `BinaryFileResponse` | Sert un file, avec support des Requests de plage |
| `StreamedResponse` | Corps produit par un callable, sans le charger en mémoire |
| `StreamedJsonResponse` | JSON diffusé à partir d'un itérable |

`JsonResponse::fromJsonString()` prend du JSON déjà encodé, sans le
ré-encoder — utile lorsque la serialization a déjà eu lieu.

## `isRedirect()` contre `isRedirection()`

C'est le piège de cet item, et il est vérifiable dans le code source.

```php
public function isRedirection(): bool
{
    return $this->statusCode >= 300 && $this->statusCode < 400;
}

public function isRedirect(?string $location = null): bool
{
    return \in_array($this->statusCode, [201, 301, 302, 303, 307, 308], true)
        && (null === $location ?: $location == $this->headers->get('Location'));
}
```

- `isRedirection()` est la **class 3xx**, littéralement.
- `isRedirect()` est la liste des statuts qui portent réellement un header
  `Location`. Elle **inclut 201 Created** — qui est 2xx — et **exclut 300 et
  304**, qui sont 3xx.

Autrement dit, les deux methods ne sont ni équivalentes ni imbriquées. Un
`304 Not Modified` est un redirect au sens de `isRedirection()` mais pas au
sens de `isRedirect()`.

`isRedirect()` accepte en outre une URL, et vérifie alors que `Location`
correspond — pratique dans les functional tests.

## Autres methods de test

```php
$response->isSuccessful();   // 2xx
$response->isClientError();  // 4xx
$response->isServerError();  // 5xx
$response->isOk();           // exactement 200
$response->isNotFound();     // exactement 404
```

## L'émission : `send()`, et qui l'appelle

```php
public function send(bool $flush = true): static
{
    $this->sendHeaders();
    $this->sendContent();

    if (!$flush) {
        return $this;                 // on s'arrete ici
    }
    // sinon fastcgi_finish_request(), litespeed_finish_request(),
    // ou closeOutputBuffers(0, true) + flush() selon le SAPI
}
```

`send()` n'est pas une method de controller. Le controller **rend** une
`Response` ; elle est émise une seule fois, plus haut, par le runner du
composant Runtime derrière `public/index.php`.

`sendHeaders()` envoie le statut et les headers, `sendContent()` fait un `echo`
du body. Deux détails valent d'être connus :

- **Si les headers sont déjà partis**, `sendHeaders()` ne recommence pas : il
  tente seulement de réémettre la ligne de statut, hors des SAPI `cli`,
  `phpdbg` et `embed`. Ce `header()` tardif échoue : exécuté sous `php -S`, un
  `echo` avant `send()` produit un **`E_WARNING`** *Cannot modify header
  information*, et un 404 arrive au client en **200**. Pas d'exception, mais
  un avertissement, et un statut perdu.
- **`$flush = false`** saute la clôture des buffers — et c'est justement ce que
  Symfony passe. `HttpKernelRunner::run()` appelle `send(false)`, exécute
  lui-même `fastcgi_finish_request()` (sauf en mode debug), **puis** appelle
  `terminate()`. C'est de là que vient la promesse de `kernel.terminate` :
  ses listeners tournent après que le client a été relâché.

## Pièges d'examen

**`isRedirect()` n'est pas « le statut est 3xx ».** Retenir 201 inclus, 304
exclu.

**`send()` n'appartient pas au controller.** Un controller retourne une
`Response` ; il ne l'envoie pas. Et le runner l'appelle avec `false`, pas avec
le défaut.

**`isOk()` n'est pas `isSuccessful()`** : le premier teste 200 exactement, le
second toute la class 2xx.

**Un `RedirectResponse` renvoie 302 par défaut** ; un permanent demande
`new RedirectResponse($url, 301)`.

**Son constructeur valide le statut avec `isRedirect()`** et lève une
`\InvalidArgumentException` sinon. Conséquence directe de la liste ci-dessus :
`new RedirectResponse($url, 304)` **échoue**, alors que
`new RedirectResponse($url, 201)` est **accepté**.

## Tips d'examen

**Le controller rend, le runner émet.** `send()` n'apparaît jamais dans un
controller, et quand il est appelé c'est avec `false`.

**`isOk()` est plus strict que `isSuccessful()`** — 200 pile contre toute la
class 2xx. Même rapport entre `isNotFound()` et `isClientError()`.

**La liste de `isRedirect()` sert deux fois** : pour tester une Response, et
pour valider le statut d'un `RedirectResponse` à la construction. Retenir la
liste, c'est retenir les deux comportements.

**`JsonResponse` n'écrit pas le JSON de `json_encode()`** : elle ajoute quatre
options d'échappement HTML. Comparer les données décodées, jamais la string.

## Points clés

- Sous-classes spécialisées plutôt que des headers posés à la main.
- `isRedirection()` = 3xx ; `isRedirect()` = liste explicite incluant 201 et
  excluant 300 et 304.
- `isOk()` ≠ `isSuccessful()`.
- `send()` = `sendHeaders()` + `sendContent()`, puis clôture des buffers sauf si
  `$flush` est `false` — ce que le runner passe avant d'appeler `terminate()`.

## Aller lire la source

- [`Response`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/Response.php) — `isRedirect()` l. 1254, `isRedirection()` l. 1194,
  `isOk()` l. 1224, `isSuccessful()` l. 1184, `sendHeaders()` l. 316,
  `sendContent()` l. 385, `send()` l. 399 (branche 8.0, `6f841c0`)
- [`HttpKernelRunner`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Runtime/Runner/Symfony/HttpKernelRunner.php) — `run()` : `send(false)` l. 36 puis
  `terminate()` l. 48 (branche 8.0)
- [Composant HttpFoundation](https://github.com/symfony/symfony-docs/blob/8.0/components/http_foundation.rst)
