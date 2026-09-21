---
id: CRS-kd67y4vnd0mm
official_item: OIT-xc4zfr70jjas
title: "Exception handling"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
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
---

## Objectif

Savoir ce que Symfony fait d'une exception non rattrapée, et surtout **comment
il choisit le code de statut** de la réponse produite.

## Le mécanisme

Le noyau dispatche `kernel.exception` avec un `ExceptionEvent`. Un écouteur y
dispose de deux leviers :

- `getThrowable()` / `setThrowable()` — remplacer l'exception ;
- `setResponse()` — fournir une réponse, ce qui met fin au traitement de
  l'erreur.

Si aucun écouteur ne fournit de réponse, l'`ErrorListener` intégré prend la main
et délègue à un contrôleur d'erreur, qui produit la page d'erreur.

## Le choix du code de statut

Le code de statut n'est **pas** simplement celui de la réponse construite par
l'écouteur. Le noyau applique, dans cet ordre :

1. si la réponse fournie est une erreur client (`isClientError()`), une erreur
   serveur (`isServerError()`) ou une redirection (`isRedirect()`), **son** code
   est conservé ;
2. sinon, si l'exception d'origine implémente `HttpExceptionInterface`, le noyau
   appelle `getStatusCode()` sur l'**exception** — et ajoute au passage les
   en-têtes retournés par `getHeaders()` ;
3. sinon, le code est **500**.

Conséquence : une réponse `200` ou `204` construite dans un écouteur d'exception
ne sortira pas avec ce statut — elle ne tombe dans aucun cas de la règle 1. Pour
l'imposer, appeler `allowCustomResponseCode()` **avant** de définir la réponse.

## `ErrorListener` s'abonne **deux** fois au même événement

Le listener intégré n'a pas un point d'entrée mais quatre, et deux d'entre eux
sont sur `kernel.exception` :

| Événement | Méthode | Priorité |
|---|---|---|
| `kernel.exception` | `logKernelException` | **0** |
| `kernel.exception` | `onKernelException` | **−128** |
| `kernel.controller_arguments` | `onControllerArguments` | — |
| `kernel.response` | `removeCspHeader` | **−128** |

L'ordre explique tout. Le **log** passe tôt : une exception est journalisée même
si un écouteur fournit ensuite une réponse. La **page d'erreur** se construit en
tout dernier, ce qui laisse n'importe quel écouteur la devancer — c'est de là que
vient le « `setResponse()` met fin au traitement ».

## La page d'erreur est une vraie sous-requête

`onKernelException` duplique la requête vers le contrôleur d'erreur et rappelle
le noyau : `handle($request, SUB_REQUEST, false)`.

Ce **`false`** est `$catch`. Une exception levée *dans* le contrôleur d'erreur
n'est donc **pas** rattrapée une seconde fois : elle remonte, l'originale chaînée
en `previous`.

En production, si l'exception survient pendant `kernel.terminate`,
`onKernelException` **renonce** — la réponse est déjà partie.

## HttpExceptionInterface

C'est cette interface qui relie une exception PHP à un statut HTTP. Les
exceptions du composant HttpKernel l'implémentent : `NotFoundHttpException`
(404), `AccessDeniedHttpException` (403), `BadRequestHttpException` (400), et la
classe générique `HttpException`, dont le statut est passé au constructeur.

Une exception applicative — une `\InvalidArgumentException`, une
`\PDOException` — ne l'implémente pas : sans intervention, elle donne 500.

**Le critère est l'interface, jamais la classe mère.** `HttpException` étend
`\RuntimeException` : une `\RuntimeException` peut donc parfaitement porter un
statut, et les trois exceptions citées en sont. Raisonner sur l'ascendance mène
à la mauvaise réponse dans le cas le plus courant.

### Deux façons de la faire entrer dans l'interface

`logKernelException` s'exécute **avant** la règle du statut, et peut remplacer
l'exception par une `HttpException` : si sa classe porte
l'attribut `#[WithHttpStatus(…)]`, ou si elle figure dans la table `exceptions`
de la configuration du framework, qui associe une classe à un `status_code`, un
`log_level` et un `log_channel`.

Le noyau voit alors une exception conforme et applique sa règle 2. Une
`\InvalidArgumentException` annotée `#[WithHttpStatus(422)]` sort en **422**,
sans un seul écouteur écrit à la main.

## Pièges d'examen

**Le statut de la réponse fournie par un écouteur n'est pas toujours conservé.**
Il ne l'est que si elle est 4xx, 5xx ou une redirection. Une réponse `200` ou
`204` construite dans `kernel.exception` ressort en `500`, sauf appel préalable
à `allowCustomResponseCode()`.

**`\RuntimeException` n'est pas le contre-exemple qu'on croit.**
`HttpException extends \RuntimeException implements HttpExceptionInterface` :
la classe mère ne dit rien du statut. Seule l'interface le dit.

**`setResponse()` met fin au traitement de l'erreur** — l'`ErrorListener`
intégré ne prend plus la main, et la page d'erreur n'est pas produite. Mais le
**log**, lui, a déjà eu lieu : il passe à la priorité 0, la page d'erreur à
−128.

**« Elle n'implémente pas l'interface, donc 500 » est trop rapide.**
`#[WithHttpStatus]` et la table `exceptions` du framework font remplacer
l'exception par une `HttpException` avant que la règle du statut ne s'applique.

**Une exception dans le contrôleur d'erreur n'est pas rattrapée.** La
sous-requête est lancée avec `$catch = false` ; elle remonte, l'originale
chaînée en `previous`.

## Tips d'examen

**Deux priorités à retenir, et ce sont les mêmes.** `0` pour le log, `−128`
pour la page d'erreur — et `−128` aussi pour `removeCspHeader`. Tout ce qui
construit passe en dernier.

**Pour un statut hors 4xx / 5xx / 3xx, il faut le demander.**
`allowCustomResponseCode()` **avant** `setResponse()`.

## Points clés

- `kernel.exception` : `setResponse()` termine le traitement, `setThrowable()`
  remplace l'exception.
- Statut : réponse 4xx/5xx/3xx → son code ; sinon `HttpExceptionInterface` →
  `getStatusCode()` ; sinon **500**.
- `allowCustomResponseCode()` est requis pour imposer un autre statut.
- Une exception qui n'implémente pas `HttpExceptionInterface` donne 500 —
  **sauf** si `#[WithHttpStatus]` ou la table `exceptions` l'a remplacée avant.
- `ErrorListener` est sur quatre hooks, dont `kernel.exception` **deux** fois :
  log à `0`, page d'erreur à `−128`.
- La page d'erreur est une sous-requête lancée avec `$catch = false`.

## Sources officielles

- [Built-in Symfony Events, section « kernel.exception »](https://github.com/symfony/symfony-docs/blob/8.0/reference/events.rst)
- [`ErrorListener`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/EventListener/ErrorListener.php)
- [`HttpKernel::handleThrowable()`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/HttpKernel.php)
