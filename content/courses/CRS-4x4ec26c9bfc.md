---
id: CRS-4x4ec26c9bfc
official_item: OIT-bswvewkkkj1q
title: "Client configuration"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-02"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/testing.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/testing.rst"
    anchor: "sending-custom-headers"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    verified_at: "2026-09-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/Test/WebTestCase.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Test/WebTestCase.php"
    branch: "8.0"
    symbol_or_lines: "createClient(array $options = [], array $server = [])"
    verified_at: "2026-10-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/KernelBrowser.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/KernelBrowser.php"
    branch: "8.0"
    symbol_or_lines: "getSession(); catchExceptions()"
    verified_at: "2026-10-02"
outcome_sections:
  OUT-pgb3b6xmtm4e: "Les deux arguments de `createClient()`"
  OUT-2t00vt83c8pb: "La règle de nommage des headers"
  OUT-kn9vqpak4jb0: "Préparer la session"
---

## Objectif

Régler le client avant ou pendant la Request : headers, host, session,
comportement face aux exceptions.

## Prérequis

L'object client.

## Les deux arguments de `createClient()`

```php
$client = static::createClient([], [
    'HTTP_HOST'       => 'en.example.com',
    'HTTP_USER_AGENT' => 'MySuperBrowser/1.0',
]);
```

Le **premier** porte les options du kernel — `environment`, `debug` — celles-là
mêmes que `bootKernel()` accepte. Le **second** porte les parameters serveur,
donc les headers.

## La règle de nommage des headers

C'est le piège de l'item, et il est mécanique. Un header personnalisé se
transforme selon la section 4.1.18 de la RFC 3875 :

1. remplacer les tirets par des soulignés ;
2. passer en majuscules ;
3. préfixer par `HTTP_`.

`X-Session-Token` devient donc **`HTTP_X_SESSION_TOKEN`**.

Les headers standards de CGI échappent au préfixe — `CONTENT_TYPE`,
`REMOTE_ADDR` — mais l'examen porte sur la règle des headers personnalisés.

Exécuté avec FrameworkBundle 8.0.15, un controller qui renvoie ce qu'il lit
dans `$request->headers` :

| Key passée au client | `X-Session-Token` côté serveur |
|---|---|
| `HTTP_X_SESSION_TOKEN` | `abc` |
| `X-Session-Token` | `null` |
| `X_SESSION_TOKEN` | `null` |

`CONTENT_TYPE`, sans préfixe, arrive bien comme `Content-Type`. Sans réglage,
l'agent user vaut `Symfony BrowserKit`.

## Par Request plutôt que par client

Le même array se passe en **cinquième** argument de `request()` :

```php
$client->request('GET', '/', [], [], [
    'HTTP_HOST' => 'en.example.com',
]);
```

`createClient()` règle tout le test ; `request()` règle une Request. La position
compte : le cinquième argument, après les parameters et les files. Exécuté :
`HTTP_HOST` passé à `createClient()` vaut encore à la deuxième Request.

## Préparer la session

```php
$session = $client->getSession();
$session->set('_csrf/form', 'fhr8d5sha3a69tpv24s5');
$session->save();

$client->request('POST', '/form', ['form' => ['_token' => 'fhr8d5sha3a69tpv24s5']]);
```

`save()` est nécessaire : sans elle la valeur n'est pas persistée et la Request
ne la verra pas. Le cas d'usage typique est le token CSRF, qu'on ne peut pas
deviner sans avoir affiché le form. Exécuté :

| Avant la Request | Valeur lue par le controller |
|---|---|
| `set('k', 'unsaved')` | `null` |
| `set('k', 'saved')` puis `save()` | `saved` |
| `set` + `save()` **avant toute Request** | lue aussi : `getSession()` rend déjà une `Session` |

## Voir les exceptions

```php
$client->catchExceptions(false);
```

Sans cela, une exception est convertie en page d'erreur et le test échoue sur un
code 500 muet. Avec, l'exception remonte à PHPUnit, qui affiche sa class, son
message et sa trace.

## Remplacer le client

Le client est le service **`test.client`** dans le container, en environment
`test` ou dès que `framework.test` est activée. Il peut donc être redéfini
entièrement.

## Pièges d'examen

**`createClient()` prend d'abord les options du kernel, ensuite les headers.**

**Un header personnalisé devient `HTTP_` + majuscules + soulignés.**

**Les headers de Request sont le cinquième argument** de `request()`.

**`$session->save()` est obligatoire** pour que la valeur soit visible.

**`catchExceptions(false)` ne change pas l'application**, seulement ce que le
client fait de l'exception.

## Points clés

- `createClient($kernelOptions, $server)`.
- `X-Session-Token` → `HTTP_X_SESSION_TOKEN` (RFC 3875 §4.1.18).
- Cinquième argument de `request()` pour un réglage ponctuel.
- `getSession()` puis `save()` avant la Request.
- `catchExceptions(false)` pour déboguer un 500.

## Sources officielles

- [Testing](https://github.com/symfony/symfony-docs/blob/8.0/testing.rst)
