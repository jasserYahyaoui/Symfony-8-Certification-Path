---
id: CRS-epx8j3fhb38n
official_item: OIT-y0ah7pq9c4hv
title: "HttpFoundation component"
content_level: MINIMAL
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
format: pedagogical
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

## L'essentiel

HttpFoundation remplace les superglobales PHP par des objects, `Request` et
`Response`, qu'on peut fabriquer — d'où une pile HTTP testable. C'est un
component **autonome**, et la brique la plus basse du framework : le kernel
est écrit dans son vocabulaire. Il fournit aussi `RequestStack`, la seule
façon pour un service de lire la Request en cours.

## Le schéma

```text
PHP nu       $_GET, $_POST, $_FILES, $_COOKIE, $_SERVER · header() + echo
HttpFoundation   Request (Request::create('/blog') sans serveur) · Response
dépendance   framework ──▶ HttpFoundation   (jamais l'inverse : handle(Request): Response)
RequestStack     [ main, sub A, sub B ]   ← le kernel empile et dépile
  getMainRequest()    → main (index 0)          getCurrentRequest() → sub B (le dernier)
  getParentRequest()  → sub A (count − 2)        pile vide → null ; getSession() → lève
```

## L'exemple : un service qui lit la Request

```php
final class LocaleReader
{
    public function __construct(private RequestStack $requestStack) {}

    public function locale(): ?string
    {
        return $this->requestStack->getCurrentRequest()?->getLocale();
    }
}
```

## Ce qu'il remplace

En PHP nu, la Request est un ensemble de globales et la Response un effet de
bord de `header()` et `echo`. Un object se fabrique : `Request::create('/blog')`
produit une Request complète sans serveur web — impossible avec des
superglobales. L'API des sacs et des headers relève du lot HTTP.

## Sa place

HttpFoundation s'installe dans n'importe quel projet PHP, sans le framework et
sans dépendre d'un autre component Symfony. L'inverse n'est pas vrai : le
contrat du kernel, `handle(Request): Response`, est écrit dans son vocabulaire.

Un service est construit une fois, la Request change à chaque appel : il reçoit
`RequestStack` et appelle `getCurrentRequest()`.

## Les trois accessors, exactement

| Accessor | Ce qu'il rend | Si la pile est vide |
|---|---|---|
| `getCurrentRequest()` | le **dernier** empilé | `null` |
| `getMainRequest()` | le **premier** | `null` |
| `getParentRequest()` | l'avant-dernier, index `count - 2` | `null` |

Sur la main Request seule, `getParentRequest()` rend `null`. `push()` et
`pop()` existent, mais « should generally not be called directly ». Le docblock
de `getMainRequest()` et `getParentRequest()` prévient qu'en dépendre « might
make it un-compatible with other features of your framework like ESI support » :
une sub-Request doit être traitée comme si elle était seule.

`getSession()`, lui, **lève** une `SessionNotFoundException` quand la pile est
vide ou que la Request courante n'a pas de session.

## Pièges d'examen

**On n'injecte jamais `Request` dans un service.** Un service est construit une
fois, la Request change à chaque appel : c'est `RequestStack` que l'on injecte,
et `getCurrentRequest()` que l'on appelle.

**La dependency va dans un seul sens.** HttpFoundation ne dépend d'aucun
component Symfony ; c'est le framework qui dépend de lui, puisque le contrat du
kernel est écrit dans son vocabulaire.

## Application

- Un service qui a besoin de la locale courante → injecter `RequestStack`.
- Un test d'un controller sans serveur → `Request::create('/blog')`.

## QCM d'entraînement

**Question 1.** La pile contient seulement la main Request. Que rend
`getParentRequest()` ?

- **A.** La main Request elle-même
- **B.** `null`
- **C.** Une `LogicException` : pas de parent
- **D.** Une Request vide, créée à la volée

<details>
<summary>Correction</summary>

**Bonne réponse : B.** La method lit l'index `count - 2`, qui n'existe pas
avec un seul élément : la main Request n'a pas de parent.

</details>

**Question 2.** La pile contient la main Request, puis la sub-Request A, puis
la sub-Request B, en cours. Que rend `getParentRequest()` ?

- **A.** La main Request
- **B.** La sub-Request B, en cours
- **C.** La sub-Request A
- **D.** `null` : seule la main a un rôle

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Le parent est l'avant-dernier élément empilé, pas le
premier ; avec une seule sub-Request, il se confondrait avec la main.

</details>

**Question 3.** Un service appelle `$requestStack->getCurrentRequest()`
depuis une command console. Que reçoit-il ?

- **A.** `null`
- **B.** Une Request factice, créée par la console
- **C.** Une `SessionNotFoundException`
- **D.** La dernière Request HTTP traitée par le serveur

<details>
<summary>Correction</summary>

**Bonne réponse : A.** Aucune Request n'a été empilée hors du cycle HTTP ;
les trois accessors rendent `null` sur une pile vide.

</details>

**Question 4.** Faut-il appeler `push()` et `pop()` soi-même ?

- **A.** Oui, avant chaque sub-Request
- **B.** Oui, dans chaque listener de `kernel.request`
- **C.** Seulement en production, pour la performance
- **D.** Non : le kernel gère la pile

<details>
<summary>Correction</summary>

**Bonne réponse : D.** Le kernel empile avant de traiter une Request et dépile
après ; le docblock de ces deux methods déconseille de les appeler directement.

</details>

## Fiche express

**Si tu dois retenir seulement 5 choses :**

- Superglobales → **objects** fabricables : `Request::create()`.
- Component **autonome** ; le framework dépend de lui, pas l'inverse.
- Service → **`RequestStack`**, jamais `Request`.
- `current` = dernier, `main` = premier, `parent` = **avant-dernier** ; vide →
  `null`.
- **`getSession()`** est le seul à lever.

Moyen mnémotechnique : **« Pile, pas registre »**.

## Sources officielles

- [Component HttpFoundation](https://github.com/symfony/symfony-docs/blob/8.0/components/http_foundation.rst)
- [RequestStack (branche 8.0)](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/RequestStack.php)

