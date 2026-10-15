---
id: CRS-kd67y4vnd0mm
official_item: OIT-xc4zfr70jjas
title: "Exception handling"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-07"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/reference/events.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/reference/events.rst"
    anchor: "kernel-kernel.exception"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpKernel/EventListener/ErrorListener.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/EventListener/ErrorListener.php"
    symbol_or_lines: "getSubscribedEvents, logKernelException, onKernelException"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-18"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpKernel/HttpKernel.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/HttpKernel.php"
    symbol_or_lines: "handleThrowable"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-18"
outcome_sections:
  OUT-9f4yqqyqwecq: "Le choix du status code"
  OUT-wbfptxs3qm46: "HttpExceptionInterface"
  OUT-dbm0023ax4bv: "Le choix du status code"
  OUT-9gyxnzp7ngzp: "Le mécanisme"
---

## Objectif

Savoir ce que Symfony fait d'une exception non rattrapée, et surtout **comment
il choisit le status code** de la Response produite.

## Le mécanisme

Le kernel dispatche `kernel.exception` avec un `ExceptionEvent`. Un listener y
dispose de deux leviers :

- `getThrowable()` / `setThrowable()` — remplacer l'exception ;
- `setResponse()` — fournir une Response, ce qui met fin au traitement de
  l'erreur.

Si aucun listener ne fournit de Response, l'`ErrorListener` intégré prend la main
et délègue à un controller d'erreur, qui produit la page d'erreur.

## Le choix du status code

Le status code n'est **pas** simplement celui de la Response construite par
le listener. Le kernel applique, dans cet ordre :

1. si la Response fournie est une erreur client (`isClientError()`), une erreur
   serveur (`isServerError()`) ou un redirect (`isRedirect()`), **son** code
   est conservé ;
2. sinon, si l'exception d'origine implémente `HttpExceptionInterface`, le kernel
   appelle `getStatusCode()` sur l'**exception** — et ajoute au passage les
   headers retournés par `getHeaders()` ;
3. sinon, le code est **500**.

Conséquence : une Response `200` ou `204` construite dans un listener d'exception
ne sortira pas avec ce statut — elle ne tombe dans aucun cas de la règle 1. Pour
l'imposer, appeler `allowCustomResponseCode()` **avant** de définir la Response.

## `ErrorListener` s'abonne **deux** fois au même event

Le listener intégré n'a pas un entry point mais quatre, et deux d'entre eux
sont sur `kernel.exception` :

| Event | Method | Priority |
|---|---|---|
| `kernel.exception` | `logKernelException` | **0** |
| `kernel.exception` | `onKernelException` | **−128** |
| `kernel.controller_arguments` | `onControllerArguments` | — |
| `kernel.response` | `removeCspHeader` | **−128** |

L'ordre explique tout. Le **log** passe tôt : une exception est journalisée même
si un listener fournit ensuite une Response. La **page d'erreur** se construit en
tout dernier, ce qui laisse n'importe quel listener la devancer — c'est de là que
vient le « `setResponse()` met fin au traitement ».

## La page d'erreur est une vraie sub-Request

`onKernelException` duplique la Request vers le controller d'erreur et rappelle
le kernel : `handle($request, SUB_REQUEST, false)`.

Ce **`false`** est `$catch`. Une exception levée *dans* le controller d'erreur
n'est donc **pas** rattrapée une seconde fois : elle remonte, l'originale chaînée
en `previous`.

En production, si l'exception survient pendant `kernel.terminate`,
`onKernelException` **renonce** — la Response est déjà partie.

## HttpExceptionInterface

C'est cette interface qui relie une exception PHP à un statut HTTP. Les
exceptions du component HttpKernel l'implémentent : `NotFoundHttpException`
(404), `AccessDeniedHttpException` (403), `BadRequestHttpException` (400), et la
class générique `HttpException`, dont le statut est passé au constructor.

Une exception applicative — une `\InvalidArgumentException`, une
`\PDOException` — ne l'implémente pas : sans intervention, elle donne 500.

**Le critère est l'interface, jamais la class mère.** `HttpException` étend
`\RuntimeException` : une `\RuntimeException` peut donc parfaitement porter un
statut, et les trois exceptions citées en sont. Raisonner sur l'ascendance mène
à la mauvaise réponse dans le cas le plus courant.

### Deux façons de la faire entrer dans l'interface

`logKernelException` s'exécute **avant** la règle du statut, et peut remplacer
l'exception par une `HttpException` : si sa class porte
l'attribute `#[WithHttpStatus(…)]`, ou si elle figure dans la table `exceptions`
de la configuration du framework, qui associe une class à un `status_code`, un
`log_level` et un `log_channel`.

Le kernel voit alors une exception conforme et applique sa règle 2. Une
`\InvalidArgumentException` annotée `#[WithHttpStatus(422)]` sort en **422**,
sans un seul listener écrit à la main.

## Pièges d'examen

**Le statut de la Response fournie par un listener n'est pas toujours conservé.**
Il ne l'est que si elle est 4xx, 5xx ou un redirect. Une Response `200` ou
`204` prend le statut **de l'exception** — exécuté : `404` pour une
`NotFoundHttpException` — et `500` dans le seul cas où l'exception n'implémente
pas `HttpExceptionInterface`. Sauf `allowCustomResponseCode()` préalable.

**`\RuntimeException` n'est pas le contre-exemple qu'on croit.**
`HttpException extends \RuntimeException implements HttpExceptionInterface` :
la class mère ne dit rien du statut. Seule l'interface le dit.

**`setResponse()` met fin au traitement de l'erreur** — l'`ErrorListener`
intégré ne prend plus la main, et la page d'erreur n'est pas produite. Mais le
**log**, lui, a déjà eu lieu : il passe à la priority 0, la page d'erreur à
−128.

**« Elle n'implémente pas l'interface, donc 500 » est trop rapide.**
`#[WithHttpStatus]` et la table `exceptions` du framework font remplacer
l'exception par une `HttpException` avant que la règle du statut ne s'applique.

**Une exception dans le controller d'erreur n'est pas rattrapée.** La
sub-Request est lancée avec `$catch = false` ; elle remonte, l'originale
chaînée en `previous`.

## Tips d'examen

**Deux priorities à retenir, et ce sont les mêmes.** `0` pour le log, `−128`
pour la page d'erreur — et `−128` aussi pour `removeCspHeader`. Tout ce qui
construit passe en dernier.

**Pour un statut hors 4xx / 5xx / 3xx, il faut le demander.**
`allowCustomResponseCode()` **avant** `setResponse()`.

## Points clés

- `kernel.exception` : `setResponse()` termine le traitement, `setThrowable()`
  remplace l'exception.
- Statut : Response 4xx/5xx/3xx → son code ; sinon `HttpExceptionInterface` →
  `getStatusCode()` ; sinon **500**.
- `allowCustomResponseCode()` est requis pour imposer un autre statut.
- Une exception qui n'implémente pas `HttpExceptionInterface` donne 500 —
  **sauf** si `#[WithHttpStatus]` ou la table `exceptions` l'a remplacée avant.
- `ErrorListener` est sur quatre hooks, dont `kernel.exception` **deux** fois :
  log à `0`, page d'erreur à `−128`.
- La page d'erreur est une sub-Request lancée avec `$catch = false`.

## Sources officielles

- [Built-in Symfony Events, section « kernel.exception »](https://github.com/symfony/symfony-docs/blob/8.0/reference/events.rst)
- [`ErrorListener`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/EventListener/ErrorListener.php)
- [`HttpKernel::handleThrowable()`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/HttpKernel.php)
