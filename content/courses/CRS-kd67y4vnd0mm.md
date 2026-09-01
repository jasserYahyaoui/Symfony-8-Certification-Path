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
    anchor: "kernel-kernel.exception"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
---

## Objectif

Savoir ce que Symfony fait d'une exception non rattrapée, et surtout **comment
il choisit le code de statut** de la réponse produite.

## Le mécanisme

Dès qu'une exception survient pendant le traitement de la requête, le noyau
dispatche `kernel.exception` avec un objet `ExceptionEvent`. Un écouteur y
dispose de deux leviers :

- `getThrowable()` / `setThrowable()` — remplacer l'exception ;
- `setResponse()` — fournir une réponse, ce qui met fin au traitement de
  l'erreur.

Si aucun écouteur ne fournit de réponse, l'`ErrorListener` intégré prend la main
et délègue à un contrôleur d'erreur, qui produit la page d'erreur.

## Le choix du code de statut

C'est le point à retenir, parce qu'il n'est pas intuitif : le code de statut
n'est **pas** simplement celui de la réponse construite par l'écouteur. Le noyau
applique cette logique, dans cet ordre :

1. si la réponse fournie est une erreur client (`isClientError()`), une erreur
   serveur (`isServerError()`) ou une redirection (`isRedirect()`), **son** code
   est conservé ;
2. sinon, si l'exception d'origine implémente `HttpExceptionInterface`, le noyau
   appelle `getStatusCode()` sur l'**exception** — et ajoute au passage les
   en-têtes retournés par `getHeaders()` ;
3. sinon, le code est **500**.

Conséquence directe : une réponse construite avec un statut `204` ou `200` dans
un écouteur d'exception ne sortira pas avec ce statut, puisqu'elle ne tombe dans
aucun des trois cas de la règle 1. Pour l'imposer, il faut appeler
`allowCustomResponseCode()` sur l'événement **avant** de définir la réponse.

## HttpExceptionInterface

C'est cette interface qui relie une exception PHP à un statut HTTP. Les
exceptions du composant HttpKernel l'implémentent : `NotFoundHttpException`
(404), `AccessDeniedHttpException` (403), `BadRequestHttpException` (400), et la
classe générique `HttpException`, dont le statut est passé au constructeur.

Une exception ordinaire — une `\RuntimeException`, une `\LogicException` — ne
l'implémente pas : elle donne donc 500.

## Environnement de débogage

`handle()` prend un argument `$catch`. Quand il vaut `false`, l'exception est
relancée au lieu d'être transformée en réponse. C'est ce qui permet à un test
fonctionnel d'observer l'exception réelle plutôt qu'une page d'erreur.

## Points clés

- `kernel.exception` : `setResponse()` termine le traitement, `setThrowable()`
  remplace l'exception.
- Statut : réponse 4xx/5xx/3xx → son code ; sinon `HttpExceptionInterface` →
  `getStatusCode()` ; sinon **500**.
- `allowCustomResponseCode()` est requis pour imposer un autre statut.
- Une exception qui n'implémente pas `HttpExceptionInterface` donne 500.

## Sources officielles

- [Built-in Symfony Events, section « kernel.exception »](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/reference/events.rst)
