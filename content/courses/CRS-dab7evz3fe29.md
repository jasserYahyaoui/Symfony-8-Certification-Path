---
id: CRS-dab7evz3fe29
official_item: OIT-mrvrtxm4v2m5
title: "Content negotiation"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/httpwg/httpwg.github.io/master/specs/rfc9110.html"
    readable_url: "https://github.com/httpwg/httpwg.github.io/blob/master/specs/rfc9110.html"
    branch: "master"
    symbol_or_lines: "section 12 Content Negotiation, section 12.5 Proactive Negotiation Fields"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpFoundation/Request.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/Request.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "getAcceptableContentTypes line 1783"
    verified_at: "2026-09-01"
---

## Objectif

Choisir la représentation d'une ressource à partir des en-têtes `Accept*`, et
signaler ce choix correctement.

## Le principe

Une ressource a plusieurs **représentations** — JSON ou HTML, français ou
anglais, compressée ou non. Le client décrit ses préférences ; le serveur
choisit. C'est la *négociation proactive*, celle que l'examen vise.

## Les en-têtes de requête

| En-tête | Dimension négociée |
|---|---|
| `Accept` | Type de média |
| `Accept-Language` | Langue |
| `Accept-Encoding` | Compression |
| `Accept-Charset` | Jeu de caractères — **déprécié par la RFC elle-même** |

```http
Accept: text/html,application/xhtml+xml,application/json;q=0.9,*/*;q=0.8
```

Chaque valeur porte un facteur de qualité `q` entre `0` et `1`, valant `1.0`
par défaut. La spécificité ne départage pas deux types : elle décide **quel
motif fixe le `q` d'un type donné**. Pour `text/html`, c'est `text/html` avant
`text/*`, lui-même avant `*/*` — quelles que soient les qualités écrites.

## Côté Symfony

```php
$request->getAcceptableContentTypes();
// ['text/html', 'application/xhtml+xml', 'application/json', '*/*']
// déjà triés par préférence décroissante

$request->getPreferredFormat();     // 'html', 'json'…
$request->getRequestFormat();       // format demandé, 'html' par défaut
```

## Les en-têtes de réponse

Le serveur annonce son choix et déclare ce dont il a dépendu :

```http
Content-Type: application/json
Content-Language: fr
Vary: Accept, Accept-Language
```

`Vary` n'est pas facultatif dès qu'on négocie : sans lui, un cache partagé
servira la première représentation obtenue à tous les clients suivants.

Si aucune représentation ne convient, la réponse correcte est
`406 Not Acceptable` — mais servir une représentation par défaut reste
généralement préférable en pratique.

`getAcceptableContentTypes()` trie sur la qualité décroissante puis sur
**l'ordre d'écriture du client** — pas sur la spécificité, et sans retirer les
items `q=0`. La règle de spécificité ci-dessus est celle de la RFC ; elle ne
s'applique pas à cet accesseur.

`getPreferredFormat()` ne commence pas par négocier : il consulte d'abord
`getRequestFormat()`, donc l'attribut `_format` de la route ou un
`setRequestFormat()` explicite. `Accept` n'est consulté qu'à défaut — un
`_format=xml` l'emporte sur un `Accept: application/json`.

## Proactive contre réactive

RFC 9110 définit **deux** négociations, et l'examen ne vise que la première.

- **Proactive** (§12.1), dite aussi *server-driven* : le client envoie ses
  préférences, un algorithme côté serveur choisit. La sélection s'appuie sur les
  en-têtes `Accept*` **et** sur des caractéristiques implicites que le texte
  nomme — l'adresse réseau du client, des morceaux de `User-Agent`.
- **Réactive** (§12.2), dite aussi *agent-driven* : le serveur renvoie une
  **liste d'alternatives**, et le client choisit puis redemande. Le code `300
  Multiple Choices` sert cela.

La conséquence pratique tient en une phrase : en négociation proactive, le
serveur décide seul et doit dire sur quoi il s'est fondé — d'où `Vary`.

## Le facteur de qualité, exactement

§12.4.2 fixe quatre choses qu'on croit savoir :

- le nom du paramètre `q` est **insensible à la casse** ;
- l'échelle va de `0` à `1`, où **`0.001` est la préférence la plus faible** et
  `0` signifie *not acceptable* ;
- la grammaire n'autorise que **trois décimales** — `q=0.3333` est mal formé ;
- en l'absence de `q`, le poids vaut `1`.

Pour `Accept-Language`, la RFC ajoute un avertissement utile : certains
destinataires traitent l'ordre d'écriture comme une priorité décroissante entre
valeurs de même qualité, « however, this behavior cannot be relied upon ».

## `Vary: *`

La valeur `*` est légale et signifie que d'autres aspects de la requête ont pu
compter, « possibly including aspects outside the message syntax ». Un cache ne
peut alors rien réutiliser sans revalider. Un **proxy**, lui, n'a pas le droit
d'en générer : « A proxy MUST NOT generate `*` in a Vary field value ».

## Pièges d'examen

**`q=0` refuse explicitement.** `Accept: */*;q=0.8, image/png;q=0` signifie
« tout sauf du PNG ».

**Sans `q`, la valeur est `1.0`** — pas `0`, et pas une pondération implicite
par la position.

**Oublier `Vary` est le bug de cache classique** et n'a aucun symptôme
localement, où il n'y a pas de cache partagé.

**`Accept` est une préférence, pas une contrainte.** Un serveur peut répondre
autre chose ; il l'annonce par `Content-Type`.

## Tips d'examen

**Lire l'en-tête, pas la position.** Sans `q`, c'est `1.0` — pas « moins que le
précédent ». Seul `q` classe.

**`q=0` est un refus, pas une préférence faible.** La préférence faible, c'est
`q=0.001`.

**Proactive = le serveur choisit ; réactive = le client choisit** dans une liste.
Le mot *driven* du synonyme donne la réponse : *server-driven* contre
*agent-driven*.

**Côté Symfony, deux accesseurs ne négocient pas ce qu'on croit.**
`getAcceptableContentTypes()` trie sur la qualité puis sur l'ordre d'écriture —
ni sur la spécificité, ni en retirant les `q=0`. `getPreferredFormat()` consulte
`_format` **avant** `Accept`.

## Points clés

- Une ressource, plusieurs représentations ; le client préfère, le serveur choisit.
- `q` par défaut `1.0` ; `q=0` refuse ; la spécificité choisit le motif qui
  fixe le `q` d'un type, elle ne classe pas deux types entre eux.
- `getAcceptableContentTypes()` renvoie une liste déjà triée.
- Négocier impose `Vary` ; `406` si rien ne convient.

## Aller lire la source

- [RFC 9110 §12 — *Content Negotiation*](https://github.com/httpwg/httpwg.github.io/blob/master/specs/rfc9110.html#content.negotiation)
- [`Request`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/Request.php) — `getAcceptableContentTypes()` (branche 8.0, `6f841c0`)
