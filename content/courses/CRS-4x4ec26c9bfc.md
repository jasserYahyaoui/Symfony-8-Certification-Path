---
id: CRS-4x4ec26c9bfc
official_item: OIT-bswvewkkkj1q
title: "Client configuration"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-02"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/testing.rst"
    anchor: "sending-custom-headers"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    verified_at: "2026-09-02"
---

## Objectif

Régler le client avant ou pendant la requête : en-têtes, hôte, session,
comportement face aux exceptions.

## Prérequis

L'objet client.

## Les deux arguments de `createClient()`

```php
$client = static::createClient([], [
    'HTTP_HOST'       => 'en.example.com',
    'HTTP_USER_AGENT' => 'MySuperBrowser/1.0',
]);
```

Le **premier** porte les options du noyau — `environment`, `debug` — celles-là
mêmes que `bootKernel()` accepte. Le **second** porte les paramètres serveur,
donc les en-têtes.

## La règle de nommage des en-têtes

C'est le piège de l'item, et il est mécanique. Un en-tête personnalisé se
transforme selon la section 4.1.18 de la RFC 3875 :

1. remplacer les tirets par des soulignés ;
2. passer en majuscules ;
3. préfixer par `HTTP_`.

`X-Session-Token` devient donc **`HTTP_X_SESSION_TOKEN`**.

Les en-têtes standards de CGI échappent au préfixe — `CONTENT_TYPE`,
`REMOTE_ADDR` — mais l'examen porte sur la règle des en-têtes personnalisés.

## Par requête plutôt que par client

Le même tableau se passe en **cinquième** argument de `request()` :

```php
$client->request('GET', '/', [], [], [
    'HTTP_HOST' => 'en.example.com',
]);
```

`createClient()` règle tout le test ; `request()` règle une requête. La position
compte : le cinquième argument, après les paramètres et les fichiers.

## Préparer la session

```php
$session = $client->getSession();
$session->set('_csrf/form', 'fhr8d5sha3a69tpv24s5');
$session->save();

$client->request('POST', '/form', ['form' => ['_token' => 'fhr8d5sha3a69tpv24s5']]);
```

`save()` est nécessaire : sans elle la valeur n'est pas persistée et la requête
ne la verra pas. Le cas d'usage typique est le jeton CSRF, qu'on ne peut pas
deviner sans avoir affiché le formulaire.

## Voir les exceptions

```php
$client->catchExceptions(false);
```

Sans cela, une exception est convertie en page d'erreur et le test échoue sur un
code 500 muet. Avec, l'exception remonte à PHPUnit, qui affiche sa classe, son
message et sa trace.

## Remplacer le client

Le client est le service **`test.client`** dans le conteneur, en environnement
`test` ou dès que `framework.test` est activée. Il peut donc être redéfini
entièrement.

## Pièges d'examen

**`createClient()` prend d'abord les options du noyau, ensuite les en-têtes.**

**Un en-tête personnalisé devient `HTTP_` + majuscules + soulignés.**

**Les en-têtes de requête sont le cinquième argument** de `request()`.

**`$session->save()` est obligatoire** pour que la valeur soit visible.

**`catchExceptions(false)` ne change pas l'application**, seulement ce que le
client fait de l'exception.

## Points clés

- `createClient($kernelOptions, $server)`.
- `X-Session-Token` → `HTTP_X_SESSION_TOKEN` (RFC 3875 §4.1.18).
- Cinquième argument de `request()` pour un réglage ponctuel.
- `getSession()` puis `save()` avant la requête.
- `catchExceptions(false)` pour déboguer un 500.

## Sources officielles

- [Testing](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/testing.rst)
