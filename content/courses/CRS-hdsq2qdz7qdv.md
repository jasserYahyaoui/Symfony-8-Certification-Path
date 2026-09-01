---
id: CRS-hdsq2qdz7qdv
official_item: OIT-jh06tkfrhthq
title: "HTTP response"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpFoundation/Response.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "isRedirect line 1254, isRedirection line 1194, isSuccessful line 1184"
    verified_at: "2026-09-01"
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

## Pièges d'examen

**`isRedirect()` n'est pas « le statut est 3xx ».** Retenir 201 inclus, 304
exclu.

**`isOk()` n'est pas `isSuccessful()`** : le premier teste 200 exactement, le
second toute la classe 2xx.

**Un `RedirectResponse` renvoie 302 par défaut** ; un permanent demande
`new RedirectResponse($url, 301)`.

## Points clés

- Sous-classes spécialisées plutôt que des en-têtes posés à la main.
- `isRedirection()` = 3xx ; `isRedirect()` = liste explicite incluant 201 et
  excluant 300 et 304.
- `isOk()` ≠ `isSuccessful()`.

## Sources officielles

- `Symfony\Component\HttpFoundation\Response` (branche 8.0, `6f841c0`)
