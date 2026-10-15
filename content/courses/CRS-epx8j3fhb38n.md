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
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/components/http_foundation.rst"
    anchor: "the-httpfoundation-component"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpFoundation/RequestStack.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/RequestStack.php"
    symbol_or_lines: "getCurrentRequest, getMainRequest, getParentRequest"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    verified_at: "2026-09-01"
outcome_sections:
  OUT-m2fxjk4j2mc1: "Ce qu'il remplace"
  OUT-qr20ybf8za07: "Sa place"
  OUT-jgk2rwhep0vm: "Les trois accessors, exactement"
---

## Objectif

Situer HttpFoundation dans l'architecture : ce qu'il remplace, la place qu'il
occupe sous le reste du framework, et ce qu'il fournit au-delà de `Request` et
`Response`.

L'API de `Request` et de `Response` — les sacs, `InputBag`, les headers — est
traitée dans le lot HTTP ; leur emploi dans un controller, dans le lot
Controllers.

## Ce qu'il remplace

En PHP nu, la Request est un ensemble de variables globales — `$_GET`, `$_POST`,
`$_FILES`, `$_COOKIE`, `$_SERVER` — et la Response un effet de bord de `header()`
et `echo`. HttpFoundation remplace ces globales par des objects.

L'intérêt n'est pas cosmétique : un object se fabrique. `Request::create('/blog')`
produit une Request complète sans serveur web, ce qui rend une pile HTTP
testable — impossible avec des superglobales.

## Sa place

C'est un component **autonome** : il s'installe et s'utilise dans n'importe quel
projet PHP, sans le framework et sans dépendre d'un autre component Symfony.

L'inverse n'est pas vrai. Le contrat du kernel — `handle(Request): Response` —
est écrit dans le vocabulaire de HttpFoundation, et tout le framework en dépend.
C'est la brique la plus basse de la pile HTTP de Symfony.

## RequestStack

Le component fournit `RequestStack`, la pile des Requests en cours de
traitement. Un service ne reçoit pas la Request par injection — elle change à
chaque appel, un service non : il reçoit `RequestStack` et demande
`getCurrentRequest()`. La pile distingue aussi la main Request
(`getMainRequest()`) de la Request parente (`getParentRequest()`).

## Les trois accessors, exactement

`RequestStack` est une pile, et les trois accessors sont trois positions dans
cette pile :

| Accessor | Ce qu'il rend | Si la pile est vide |
|---|---|---|
| `getCurrentRequest()` | le **dernier** empilé — `end($requests)` | `null` |
| `getMainRequest()` | le **premier** — `$requests[0]` | `null` |
| `getParentRequest()` | l'avant-dernier — l'index `count - 2` | `null` |

`getParentRequest()` rend donc `null` quand la Request courante **est** la
main Request : il n'y a pas d'élément avant elle.

La pile se remplit toute seule : le kernel empile avant de traiter une Request et
dépile après. `push()` et `pop()` existent, mais leur docblock prévient qu'ils
« should generally not be called directly ».

## L'avertissement que portent deux de ces methods

`getMainRequest()` et `getParentRequest()` portent la même mise en garde dans
leur docblock : rendre son code conscient de la main Request ou parente

> might make it un-compatible with other features of your framework like ESI
> support.

La raison est structurelle : une sub-Request existe pour être traitée **comme
si** elle était seule — c'est ce qui permet à un fragment ESI d'être rendu par un
cache séparé. Un service qui regarde par-dessus son épaule casse cette
indépendance.

## `getSession()` ne rend pas `null`

```php
$session = $requestStack->getSession();   // SessionNotFoundException si aucune
```

Contrairement aux trois accessors de Request, celui-ci **lève** une
`SessionNotFoundException` quand la pile est vide ou quand la Request courante
n'a pas de session — cohérent avec son return type, `SessionInterface` sans
`?`.

## Tips d'examen

**Pile, pas registre.** `current` = le dernier empilé, `main` = le premier,
`parent` = celui d'avant. Trois positions, pas trois notions.

**`getParentRequest()` sur la main Request rend `null`**, pas la Request
elle-même.

**Le seul accessor qui lève est `getSession()`** — les autres rendent `null`.

**Un service reçoit `RequestStack`, jamais `Request`.** Le service est construit
une fois ; la Request change à chaque appel.

## Pièges d'examen

**On n'injecte jamais `Request` dans un service.** Un service est construit une
fois, la Request change à chaque appel : c'est `RequestStack` que l'on injecte,
et `getCurrentRequest()` que l'on appelle.

**La dependency va dans un seul sens.** HttpFoundation ne dépend d'aucun
component Symfony ; c'est le framework qui dépend de lui, puisque le contrat du
kernel est écrit dans son vocabulaire.

## Points clés

- HttpFoundation remplace les superglobales PHP par des objects fabricables.
- `Request::create()` construit une Request sans serveur web : c'est ce qui rend
  la pile testable.
- Component autonome, utilisable hors framework ; c'est le framework qui en
  dépend, pas l'inverse.
- La Request courante s'obtient par `RequestStack`, jamais par injection.

## Sources officielles

- [Component HttpFoundation](https://github.com/symfony/symfony-docs/blob/8.0/components/http_foundation.rst)
- [RequestStack (branche 8.0)](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/RequestStack.php)
