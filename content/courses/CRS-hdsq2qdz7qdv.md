---
id: CRS-hdsq2qdz7qdv
official_item: OIT-jh06tkfrhthq
title: "HTTP response"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-16"
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

Construire la bonne sous-classe de `Response` et distinguer ses méthodes de
test — dont deux sont dangereusement proches.

## La classe de base

```php
$response = new Response('<h1>Bonjour</h1>', Response::HTTP_OK, ['content-type' => 'text/html']);
$response->headers->set('X-Custom', 'value');
```

`$response->headers` est un `ResponseHeaderBag`, qui gère aussi les cookies.

Une `Response` fraîchement construite porte **`HTTP/1.0`** : c'est
`prepare(Request $request)` qui la promeut en 1.1 le cas échéant, vide le corps
sur un `HEAD` ou un statut sans corps, et complète le `Content-Type`. Le noyau
l'appelle pour vous ; une réponse fabriquée hors du cycle, non.

## Les sous-classes

| Classe | Usage |
|---|---|
| `JsonResponse` | Encode un tableau en JSON et pose le `Content-Type` |
| `RedirectResponse` | Pose `Location` et un statut 302 par défaut |
| `BinaryFileResponse` | Sert un fichier, avec support des requêtes de plage |
| `StreamedResponse` | Corps produit par un callable, sans le charger en mémoire |
| `StreamedJsonResponse` | JSON diffusé à partir d'un itérable |

`JsonResponse::fromJsonString()` prend du JSON déjà encodé, sans le
ré-encoder — utile lorsque la sérialisation a déjà eu lieu.

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

- `isRedirection()` est la **classe 3xx**, littéralement.
- `isRedirect()` est la liste des statuts qui portent réellement un en-tête
  `Location`. Elle **inclut 201 Created** — qui est 2xx — et **exclut 300 et
  304**, qui sont 3xx.

Autrement dit, les deux méthodes ne sont ni équivalentes ni imbriquées. Un
`304 Not Modified` est une redirection au sens de `isRedirection()` mais pas au
sens de `isRedirect()`.

`isRedirect()` accepte en outre une URL, et vérifie alors que `Location`
correspond — pratique dans les tests fonctionnels.

## Autres méthodes de test

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

`send()` n'est pas une méthode de contrôleur. Le contrôleur **rend** une
`Response` ; elle est émise une seule fois, plus haut, par le runner du
composant Runtime derrière `public/index.php`.

`sendHeaders()` envoie le statut et les en-têtes, `sendContent()` fait un `echo`
du corps. Deux détails valent d'être connus :

- **Si les en-têtes sont déjà partis**, `sendHeaders()` ne recommence pas : il
  réémet seulement la ligne de statut, et uniquement hors des SAPI `cli`,
  `phpdbg` et `embed`. Aucune exception, aucun avertissement.
- **`$flush = false`** saute la clôture des tampons — et c'est justement ce que
  Symfony passe. `HttpKernelRunner::run()` appelle `send(false)`, exécute
  lui-même `fastcgi_finish_request()` (sauf en mode debug), **puis** appelle
  `terminate()`. C'est de là que vient la promesse de `kernel.terminate` :
  ses écouteurs tournent après que le client a été relâché.

## Pièges d'examen

**`isRedirect()` n'est pas « le statut est 3xx ».** Retenir 201 inclus, 304
exclu.

**`send()` n'appartient pas au contrôleur.** Un contrôleur retourne une
`Response` ; il ne l'envoie pas. Et le runner l'appelle avec `false`, pas avec
le défaut.

**`isOk()` n'est pas `isSuccessful()`** : le premier teste 200 exactement, le
second toute la classe 2xx.

**Un `RedirectResponse` renvoie 302 par défaut** ; un permanent demande
`new RedirectResponse($url, 301)`.

**Son constructeur valide le statut avec `isRedirect()`** et lève une
`\InvalidArgumentException` sinon. Conséquence directe de la liste ci-dessus :
`new RedirectResponse($url, 304)` **échoue**, alors que
`new RedirectResponse($url, 201)` est **accepté**.

## Tips d'examen

**Le contrôleur rend, le runner émet.** `send()` n'apparaît jamais dans un
contrôleur, et quand il est appelé c'est avec `false`.

**`isOk()` est plus strict que `isSuccessful()`** — 200 pile contre toute la
classe 2xx. Même rapport entre `isNotFound()` et `isClientError()`.

**La liste de `isRedirect()` sert deux fois** : pour tester une réponse, et
pour valider le statut d'un `RedirectResponse` à la construction. Retenir la
liste, c'est retenir les deux comportements.

**`JsonResponse` n'écrit pas le JSON de `json_encode()`** : elle ajoute quatre
options d'échappement HTML. Comparer les données décodées, jamais la chaîne.

## Points clés

- Sous-classes spécialisées plutôt que des en-têtes posés à la main.
- `isRedirection()` = 3xx ; `isRedirect()` = liste explicite incluant 201 et
  excluant 300 et 304.
- `isOk()` ≠ `isSuccessful()`.
- `send()` = `sendHeaders()` + `sendContent()`, puis clôture des tampons sauf si
  `$flush` est `false` — ce que le runner passe avant d'appeler `terminate()`.

## Aller lire la source

- [`Response`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/Response.php) — `isRedirect()` l. 1254, `isRedirection()` l. 1194,
  `isOk()` l. 1224, `isSuccessful()` l. 1184, `sendHeaders()` l. 316,
  `sendContent()` l. 385, `send()` l. 399 (branche 8.0, `6f841c0`)
- [`HttpKernelRunner`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Runtime/Runner/Symfony/HttpKernelRunner.php) — `run()` : `send(false)` l. 36 puis
  `terminate()` l. 48 (branche 8.0)
- [Composant HttpFoundation](https://github.com/symfony/symfony-docs/blob/8.0/components/http_foundation.rst)
