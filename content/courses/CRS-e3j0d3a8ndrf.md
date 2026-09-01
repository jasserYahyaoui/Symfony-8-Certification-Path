---
id: CRS-e3j0d3a8ndrf
official_item: OIT-tc39jp8japfc
title: "Caching"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/httpwg/httpwg.github.io/master/specs/rfc9110.html"
    branch: "master"
    symbol_or_lines: "section 12.5.5 Vary, section 8.8 Validator Fields"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpFoundation/Response.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "setPublic 609, setMaxAge 793, setSharedMaxAge 841, setEtag 954, isNotModified 1118"
    verified_at: "2026-09-01"
---

## Objectif

Décrire la fraîcheur et la validation d'une réponse par ses en-têtes, et savoir
lequel s'adresse à quel cache.

> **Périmètre.** Cette page couvre les **en-têtes du protocole**. Le reverse
> proxy Symfony et les stratégies d'expiration et de validation qu'il implémente
> appartiennent à l'item *HTTP Caching (reverse proxies, expiration,
> validation)* du sujet Miscellaneous.

## Deux modèles

**Expiration** — le cache sait pendant combien de temps la réponse reste
fraîche, et n'interroge pas le serveur avant échéance.

**Validation** — le cache conserve la réponse et demande au serveur, à chaque
usage, si elle est toujours valable. Le serveur répond `304 Not Modified` sans
corps si c'est le cas.

L'expiration économise des requêtes ; la validation économise de la bande
passante.

## Expiration

```php
$response->setPublic();
$response->setMaxAge(3600);         // Cache-Control: max-age=3600  → caches privés
$response->setSharedMaxAge(86400);  // Cache-Control: s-maxage=86400 → caches partagés
```

- `private` (défaut Symfony) : seul le cache du navigateur peut stocker.
- `public` : les caches partagés — CDN, reverse proxy — peuvent stocker aussi.
- `s-maxage` ne concerne que les caches partagés et **prime sur `max-age`**
  pour eux.

`Expires` est l'équivalent historique, en date absolue ; `Cache-Control` prime
sur lui.

## Validation

```php
$response->setEtag('a1b2c3');                          // identifiant de la représentation
$response->setLastModified(new \DateTimeImmutable());  // date

if ($response->isNotModified($request)) {
    return $response;   // 304, corps vidé automatiquement
}
```

Le client renvoie ensuite `If-None-Match` (contre l'ETag) ou
`If-Modified-Since` (contre la date). `isNotModified()` compare et, en cas de
correspondance, met le statut à 304 et vide le corps.

Un ETag **faible** (`W/"a1b2c3"`) déclare une équivalence sémantique plutôt
qu'octet à octet : `setEtag('a1b2c3', true)`.

## Vary

```php
$response->setVary(['Accept-Language', 'Accept-Encoding']);
```

`Vary` déclare les en-têtes de requête qui font varier la représentation. Sans
lui, un cache partagé peut servir la version française à un client anglophone —
c'est le bug de cache classique.

## Pièges d'examen

**`private` est le défaut de Symfony.** Une réponse n'est pas mise en cache
partagé tant qu'on n'a pas appelé `setPublic()`.

**`s-maxage` prime sur `max-age`, mais seulement pour les caches partagés.**

**Négocier sans `Vary` casse le cache.** Toute réponse qui dépend d'un en-tête
de requête doit le déclarer.

**`no-cache` ne veut pas dire « ne pas stocker ».** Il impose une
revalidation avant chaque usage. « Ne pas stocker » s'écrit `no-store`.

## Points clés

- Expiration (`max-age`, `s-maxage`) vs validation (`ETag`, `Last-Modified`).
- `private` par défaut ; `setPublic()` pour les caches partagés.
- `isNotModified()` produit le `304` et vide le corps.
- `Vary` est obligatoire dès qu'on négocie.
- `no-cache` = revalider ; `no-store` = ne rien garder.

## Sources officielles

- RFC 9110 §8.8 et §12.5.5
- `Symfony\Component\HttpFoundation\Response` (branche 8.0, `6f841c0`)
