---
id: CRS-4x4ec26c9bfc
official_item: OIT-bswvewkkkj1q
title: "Client configuration"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-29"
format: pedagogical
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
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/BrowserKit/AbstractBrowser.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/BrowserKit/AbstractBrowser.php"
    branch: "8.0"
    symbol_or_lines: "setServerParameters() — HTTP_USER_AGENT defaults to Symfony BrowserKit; setServerParameter(); request() merges the per-request server array over the client's"
    verified_at: "2026-10-29"
outcome_sections:
  OUT-pgb3b6xmtm4e: "Les deux arguments de `createClient()`"
  OUT-2t00vt83c8pb: "La règle de nommage des headers"
  OUT-kn9vqpak4jb0: "Préparer la session"
---

## Objectif

Régler le client avant ou pendant la Request : headers, host, session,
comportement face aux exceptions.

## L'essentiel

Le client de test se règle à deux niveaux : **`createClient()`** pour tout le
test, le **5ᵉ argument** de `request()` pour une seule Request. Un header s'y
écrit sous son nom **CGI** — `HTTP_` + majuscules + soulignés. Et une valeur
posée en session n'est vue qu'après **`save()`**.

## Le schéma

```mermaid
---
title: D'où vient chaque réglage d'une Request de test
---
flowchart TD
  accTitle: Comment les réglages du client se combinent dans une Request
  accDescr: Les options du kernel passent en premier argument de createClient, les parameters serveur en second ; setServerParameter modifie ensuite ceux du client. Le cinquième argument de request les complète pour une seule Request et l'emporte sur eux. Un header personnalisé n'est reconnu que nommé HTTP_ suivi du nom en majuscules, tirets remplacés par des soulignés. Une valeur posée en session n'est vue par la Request que si save a été appelée.
  CC["createClient(options du kernel, parameters serveur)"] --> CLI["parameters du client"]
  SSP["setServerParameter()"] --> CLI
  CLI --> MERGE["fusion : le 5ᵉ argument de request() l'emporte"]
  R5["request(…, 5ᵉ argument)"] -->|"cette Request seulement"| MERGE
  MERGE --> NAME{"key nommée HTTP_ + MAJUSCULES + _ ?"}
  NAME -->|"oui"| HDR(["header reçu par le controller"])
  NAME -->|"non, header personnalisé"| LOST(["ignoré : null côté serveur"])
  SES["getSession()->set()"] --> SAVE{"save() appelée ?"}
  SAVE -->|"oui"| SEEN(["la Request lit la valeur"])
  SAVE -->|"non"| NULL(["la Request lit null"])
```

## L'exemple

```php
$client = static::createClient([], [                 // 1ᵉʳ : kernel ; 2ᵉ : serveur
    'HTTP_HOST' => 'en.example.com',                  // toutes les Requests
]);

$client->request('GET', '/api/me', [], [], [
    'HTTP_X_SESSION_TOKEN' => 'abc',                  // X-Session-Token, une fois
]);

$session = $client->getSession();
$session->set('_csrf/form', 'fhr8d5sha3a69tpv24s5');
$session->save();                                     // sinon, invisible
```

## Les deux arguments de `createClient()`

Le **premier** porte les options du kernel — `environment`, `debug`, celles de
`bootKernel()`. Le **second** porte les parameters serveur, donc les headers,
pour toutes les Requests du client.

## La règle de nommage des headers

Un header personnalisé suit la RFC 3875 : tirets → soulignés, majuscules,
préfixe `HTTP_`. `X-Session-Token` devient **`HTTP_X_SESSION_TOKEN`**.

| Key passée au client | `X-Session-Token` côté serveur |
|---|---|
| `HTTP_X_SESSION_TOKEN` | `abc` |
| `X-Session-Token` | `null` |
| `X_SESSION_TOKEN` | `null` |

- Les headers standards de CGI échappent au préfixe : `CONTENT_TYPE`, sans
  préfixe, arrive bien comme `Content-Type`.
- `HTTP_AUTHORIZATION` arrive comme `Authorization` ; `AUTHORIZATION` donne
  `null`.
- Sans réglage, le user agent vaut `Symfony BrowserKit`.

## Par Request plutôt que par client

L'array de la Request est fusionné **par-dessus** celui du client, pour cette
Request seulement. Client créé avec `HTTP_HOST` `a.example.com` :

| Request | Host reçu |
|---|---|
| 5ᵉ argument `HTTP_HOST` `b.example.com` | `b.example.com` |
| la suivante, sans 5ᵉ argument | `a.example.com` |
| après `setServerParameter('HTTP_HOST', 'c.example.com')` | `c.example.com` |

`setServerParameter()` modifie le client pour la suite du test.

## Préparer la session

| Avant la Request | Valeur lue par le controller |
|---|---|
| `set('k', 'unsaved')` | `null` |
| `set('k', 'saved')` puis `save()` | `saved` |
| `set` + `save()` **avant toute Request** | lue aussi |

Le cas typique : un token CSRF, qu'on ne peut pas deviner sans afficher le
form.

## Les cookies

`$client->getCookieJar()->set(new Cookie('flavor', 'choco'))` — le `Cookie` de
BrowserKit — et la Request suivante porte le cookie `flavor`.

## Voir les exceptions

`$client->catchExceptions(false)` : l'exception remonte à PHPUnit au lieu d'un
500 muet ; l'application, elle, ne change pas. Le client est le service
**`test.client`**, en environment `test` ou avec `framework.test`.

## Pièges d'examen

**`createClient()` prend d'abord les options du kernel, ensuite les headers.**

**Un header personnalisé devient `HTTP_` + majuscules + soulignés.**

**Les headers de Request sont le cinquième argument** de `request()`.

**`$session->save()` est obligatoire** pour que la valeur soit visible.

**`catchExceptions(false)` ne change pas l'application**, seulement ce que le
client fait de l'exception.

## Application

- Le test passe `['X-Api-Key' => 'k']` et le controller lit `null` →
  `HTTP_X_API_KEY`.
- Le token CSRF posé en session est ignoré et le form est invalide →
  `$session->save()` manquait.

## QCM d'entraînement

**Question 1.** Le client est créé avec `['HTTP_HOST' => 'a.example.com']`.
Une Request passe `['HTTP_HOST' => 'b.example.com']` en 5ᵉ argument ; la
suivante n'en passe aucun. Quels hosts reçoit l'application ?

- **A.** `a.example.com`, puis `a.example.com`
- **B.** `b.example.com`, puis `b.example.com`
- **C.** `b.example.com`, puis `a.example.com`
- **D.** Une exception : deux hosts sont déclarés

<details>
<summary>Correction</summary>

**Bonne réponse : C.** L'array de la Request est fusionné par-dessus celui du
client, pour cette Request seulement ; B supposerait que le client change.

</details>

**Question 2.** En cours de test, toutes les Requests **suivantes** doivent
viser `c.example.com`. Quel appel ?

- **A.** `$client->setServerParameter('HTTP_HOST', 'c.example.com')`
- **B.** `$client->request('GET', '/', ['HTTP_HOST' => 'c.example.com'])`
- **C.** `$client->getRequest()->headers->set('Host', 'c.example.com')`
- **D.** `$client->setHost('c.example.com')`

<details>
<summary>Correction</summary>

**Bonne réponse : A.** B place la valeur dans les parameters de la Request, C
modifie une Request déjà traitée, et D n'existe pas.

</details>

**Question 3.** Sans aucun réglage, quel `User-Agent` reçoit l'application ?

- **A.** `Mozilla/5.0 (compatible; Symfony)`
- **B.** Aucun : le header est absent
- **C.** `PHPUnit`
- **D.** `Symfony BrowserKit`

<details>
<summary>Correction</summary>

**Bonne réponse : D.** C'est la default value de `HTTP_USER_AGENT`, posée par
`AbstractBrowser` à la création du client.

</details>

**Question 4.** Chaque Request d'un test doit porter le cookie `flavor=choco`,
préparé par `$cookie = new Cookie('flavor', 'choco')`. Quel appel, avant les
Requests ?

- **A.** `$client->setServerParameter('flavor', 'choco')`
- **B.** `$client->getCookieJar()->set($cookie)`
- **C.** `$client->getSession()->set('flavor', 'choco')`
- **D.** `$client->request('GET', '/', ['flavor' => 'choco'])`

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Les autres posent un parameter serveur, une valeur de
session ou un parameter de Request : aucun n'est un cookie.

</details>

## Fiche express

**Si tu dois retenir seulement 7 choses :**

- **`createClient()`** : options du **kernel**, puis parameters **serveur**.
- Header personnalisé : **`HTTP_` + MAJUSCULES + `_`**.
- **`CONTENT_TYPE`** échappe au préfixe.
- **5ᵉ argument** de `request()` : une Request, prioritaire.
- **`setServerParameter()`** pour la suite du test.
- **`save()`** après `set()` en session.
- **`catchExceptions(false)`** : l'exception remonte, l'application ne change pas.

Moyen mnémotechnique :
**« Kernel, puis serveur ; HTTP_ en majuscules ; save »**.

```text
createClient(kernel, serveur) ; X-Foo-Bar → HTTP_X_FOO_BAR (CONTENT_TYPE à part) ; request(…, 5ᵉ) l'emporte une fois ;
setServerParameter() pour la suite ; session : set + save ; cookies : getCookieJar() ; catchExceptions(false)
```

## Sources officielles

- [Testing](https://github.com/symfony/symfony-docs/blob/8.0/testing.rst)
- [`WebTestCase`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Test/WebTestCase.php)
- [`KernelBrowser`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/KernelBrowser.php)
- [`AbstractBrowser`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/BrowserKit/AbstractBrowser.php)

