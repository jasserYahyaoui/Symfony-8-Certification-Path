---
id: CRS-epx8j3fhb38n
official_item: OIT-y0ah7pq9c4hv
title: "HttpFoundation component"
content_level: MINIMAL
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/components/http_foundation.rst"
    anchor: "the-httpfoundation-component"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpFoundation/RequestStack.php"
    symbol_or_lines: "getCurrentRequest, getMainRequest, getParentRequest"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    verified_at: "2026-09-01"
---

## Objectif

Situer HttpFoundation dans l'architecture : ce qu'il remplace, la place qu'il
occupe sous le reste du framework, et ce qu'il fournit au-delà de `Request` et
`Response`.

L'API de `Request` et de `Response` — les sacs, `InputBag`, les en-têtes — est
traitée dans le lot HTTP ; leur emploi dans un contrôleur, dans le lot
Controllers.

## Ce qu'il remplace

En PHP nu, la requête est un ensemble de variables globales — `$_GET`, `$_POST`,
`$_FILES`, `$_COOKIE`, `$_SERVER` — et la réponse un effet de bord de `header()`
et `echo`. HttpFoundation remplace ces globales par des objets.

L'intérêt n'est pas cosmétique : un objet se fabrique. `Request::create('/blog')`
produit une requête complète sans serveur web, ce qui rend une pile HTTP
testable — impossible avec des superglobales.

## Sa place

C'est un composant **autonome** : il s'installe et s'utilise dans n'importe quel
projet PHP, sans le framework et sans dépendre d'un autre composant Symfony.

L'inverse n'est pas vrai. Le contrat du noyau — `handle(Request): Response` —
est écrit dans le vocabulaire de HttpFoundation, et tout le framework en dépend.
C'est la brique la plus basse de la pile HTTP de Symfony.

## RequestStack

Le composant fournit `RequestStack`, la pile des requêtes en cours de
traitement. Un service ne reçoit pas la requête par injection — elle change à
chaque appel, un service non : il reçoit `RequestStack` et demande
`getCurrentRequest()`. La pile distingue aussi la requête principale
(`getMainRequest()`) de la requête parente (`getParentRequest()`).

## Points clés

- HttpFoundation remplace les superglobales PHP par des objets fabricables.
- `Request::create()` construit une requête sans serveur web : c'est ce qui rend
  la pile testable.
- Composant autonome, utilisable hors framework ; c'est le framework qui en
  dépend, pas l'inverse.
- La requête courante s'obtient par `RequestStack`, jamais par injection.

## Sources officielles

- [Composant HttpFoundation](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/components/http_foundation.rst)
- [RequestStack (branche 8.0)](https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpFoundation/RequestStack.php)
