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
    readable_url: "https://github.com/httpwg/httpwg.github.io/blob/master/specs/rfc9110.html"
    branch: "master"
    symbol_or_lines: "section 12.5.5 Vary, section 8.8 Validator Fields"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpFoundation/Response.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/Response.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "setPublic 609, setMaxAge 793, setSharedMaxAge 841, setEtag 954, isNotModified 1118"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/http_cache/expiration.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/http_cache/expiration.rst"
    symbol_or_lines: '"Using the setSharedMaxAge() method is not equivalent to using both setPublic() and setMaxAge() methods ... That''s why it''s recommended to use both public and max-age directives"'
    repository: "symfony/symfony-docs"
    branch: "8.0"
    verified_at: "2026-09-15"
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
$response->setPublic();             // Cache-Control: public
$response->setMaxAge(3600);         // public, max-age=3600
$response->setSharedMaxAge(86400);  // public, max-age=3600, s-maxage=86400
```

Les directives **s'accumulent** : chaque appel ajoute la sienne. `setPublic()`
est ici redondant, car `setSharedMaxAge()` l'appelle lui-même — ce que
`setMaxAge()` ne fait pas. Seul, `setMaxAge(3600)` émet d'ailleurs
`max-age=3600, private`, le `private` étant ajouté par défaut tant que ni
`public` ni `s-maxage` n'est posé.

**La documentation officielle recommande pourtant `setPublic()` + `setMaxAge()`**
plutôt que `setSharedMaxAge()` : `s-maxage` interdit à un cache de servir une
réponse périmée en scénario `stale-if-error`. Le raccourci est exact, il n'est
pas conseillé.

- `private` (défaut Symfony) : seul le cache du navigateur peut stocker. La
  valeur réellement émise par une réponse à laquelle on n'a rien demandé est
  `no-cache, private` — ou `private, must-revalidate` dès qu'elle porte un
  `Last-Modified` ou un `Expires`.
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
`If-Modified-Since` (contre la date). Les deux ne sont **pas symétriques**, mais
la condition est plus fine qu'il n'y paraît : la comparaison de dates est dans un
`elseif` dont la branche `if` exige **deux** choses — un `If-None-Match` dans la
requête **et** un ETag sur la réponse. Si la réponse ne porte pas d'ETag, la date
est évaluée malgré `If-None-Match`, et peut produire un 304.

RFC 9110 §13.1.3 est plus stricte : le destinataire **doit** ignorer
`If-Modified-Since` dès que `If-None-Match` est présent, sans condition sur la
réponse. Symfony s'en écarte ; c'est le code qui fait foi ici. `isNotModified()` compare et, en cas de
correspondance, met le statut à 304 et vide le corps.

**`isNotModified()` commence par une garde sur la méthode.** Son premier geste
est `if (!$request->isMethodCacheable()) { return false; }` : sur une requête
qui n'est ni `GET`, ni `HEAD`, ni `QUERY`, elle rend `false` **avant même de
comparer quoi que ce soit**, ETag correspondant ou non. La validation n'a de
sens que pour les méthodes cacheables.

Un ETag **faible** (`W/"a1b2c3"`) déclare une équivalence sémantique plutôt
qu'octet à octet : `setEtag('a1b2c3', true)`.

## Vary

```php
$response->setVary(['Accept-Language', 'Accept-Encoding']);
```

`Vary` déclare les en-têtes de requête qui font varier la représentation. Sans
lui, un cache partagé peut servir la version française à un client anglophone —
c'est le bug de cache classique.

## Trois directives voisines

| Directive | Effet |
|---|---|
| `must-revalidate` | interdit de servir une réponse périmée |
| `stale-while-revalidate=N` | autorise au contraire le périmé pendant `N` secondes, le temps de revalider en arrière-plan |
| `immutable` | la réponse ne changera pas : ne pas revalider avant échéance |

## Pièges d'examen

**`private` est le défaut de Symfony.** Une réponse n'est pas mise en cache
partagé tant qu'on n'a pas appelé `setPublic()`.

**`s-maxage` prime sur `max-age`, mais seulement pour les caches partagés.**

**Négocier sans `Vary` casse le cache.** Toute réponse qui dépend d'un en-tête
de requête doit le déclarer.

**`no-cache` ne veut pas dire « ne pas stocker ».** Il impose une
revalidation avant chaque usage. « Ne pas stocker » s'écrit `no-store`.

**`isNotModified()` sur un `POST` renvoie `false`.** La garde de méthode passe
avant la comparaison d'ETag : ce n'est pas « l'ETag ne correspond pas », c'est
« la question n'est pas posée ».

**`setSharedMaxAge()` rend la réponse publique** — mais la doc recommande
`setPublic()` + `setMaxAge()`, à cause de `stale-if-error`.

**`max-age` n'est pas « pour les caches privés ».** Un cache partagé l'utilise
dès que `s-maxage` est absent ; c'est bien pourquoi `s-maxage` « prime » sur lui.

## Points clés

- Expiration (`max-age`, `s-maxage`) vs validation (`ETag`, `Last-Modified`).
- `private` par défaut ; `setPublic()` pour les caches partagés.
- `isNotModified()` produit le `304` et vide le corps — mais seulement si la
  requête est cacheable ; sinon elle rend `false` d'emblée.
- `setSharedMaxAge()` appelle `setPublic()` ; `setMaxAge()` non.
- `Vary` est obligatoire dès qu'on négocie.
- `no-cache` = revalider ; `no-store` = ne rien garder.
- Mnémonique : `s-` = *shared*, et il **prime** sans être exclusif.

## Aller lire la source

- [RFC 9110](https://github.com/httpwg/httpwg.github.io/blob/master/specs/rfc9110.html) — §8.8 *Validator Fields*, §12.5.5 *Vary*
- [`Response`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/Response.php) — `isNotModified()` l. 1118, `setSharedMaxAge()` l. 841,
  `setPublic()`, `setMaxAge()` (branche 8.0, `6f841c0`)
- [Cache HTTP](https://github.com/symfony/symfony-docs/blob/8.0/http_cache.rst)
- [Expiration](https://github.com/symfony/symfony-docs/blob/8.0/http_cache/expiration.rst) —
  la note qui recommande `setPublic()` + `setMaxAge()` plutôt que `setSharedMaxAge()`
- [RFC 9111](https://github.com/httpwg/httpwg.github.io/blob/master/specs/rfc9111.html) —
  §5.2 (`must-revalidate`, `s-maxage`) ; RFC 5861 §3 (`stale-while-revalidate`) ; RFC 8246 (`immutable`)
