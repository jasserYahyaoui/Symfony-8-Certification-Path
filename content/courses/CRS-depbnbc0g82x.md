---
id: CRS-depbnbc0g82x
official_item: OIT-7801mj6w73ky
title: "HTTP Specification (RFC 9110)"
content_level: MINIMAL
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/httpwg/httpwg.github.io/master/specs/rfc9110.html"
    readable_url: "https://github.com/httpwg/httpwg.github.io/blob/master/specs/rfc9110.html"
    branch: "master"
    symbol_or_lines: "Abstract ; section 1.4 Specifications Obsoleted by This Document ; section 3 Terminology ; section 6 Message Abstraction"
    verified_at: "2026-09-01"
---

## Objectif

Situer RFC 9110 parmi les spécifications HTTP et reconnaître son vocabulaire.

## Ce que RFC 9110 remplace

RFC 9110 — *HTTP Semantics* (2022) — est la référence actuelle pour la
**sémantique** d'HTTP. Son propre résumé énonce la liste, et elle est plus
longue qu'on ne la retient : elle « obsolète les RFC **2818, 7231, 7232, 7233,
7235, 7538, 7615, 7694, et des parties de 7230** ».

Trois choses s'y cachent.

**7230 n'est obsolétée qu'en partie.** Ce qui relevait de la sémantique passe
dans 9110 ; ce qui relevait de la syntaxe de trame HTTP/1.1 passe dans **9112**.
La Table 1 du §1.4 marque d'ailleurs cette ligne d'un astérisque, seule de son
tableau.

**7234 n'y figure pas du tout** : la mise en cache est reprise par **RFC 9111**.

**2818 y figure**, alors qu'on l'oublie presque toujours : c'est *HTTP Over
TLS*, et son contenu rejoint 9110.

Un détail qui départage à l'examen : **obsolète et met à jour ne sont pas la même
chose**. 9110 porte aussi `Updates: 3864`, sur l'enregistrement des noms de
champs : 3864 reste en vigueur, amendée. Et l'en-tête du document liste `7230`
sans réserve là où le résumé écrit « portions of 7230 » — c'est le résumé et la
Table 1 qui donnent la nuance.

| RFC | Périmètre |
|---|---|
| **9110** | Sémantique : méthodes, codes de statut, champs d'en-tête, négociation |
| 9111 | Mise en cache |
| 9112 | HTTP/1.1 (syntaxe de la trame) |
| 9113 | HTTP/2 |
| 9114 | HTTP/3 |

Une méthode ou un code de statut signifie la même chose en HTTP/1.1, HTTP/2 et
HTTP/3 : seul le transport change.

## Vocabulaire imposé

- **Message** : **données de contrôle**, champs d'en-tête, **contenu**, champs
  de fin (*trailers*). §6 le définit ainsi pour rester indépendant de la
  version ; la « ligne de départ » est la forme HTTP/1.1, donc RFC 9112.
- **Ressource** : la cible identifiée par un URI. Ce n'est pas un fichier.
- **Représentation** : une forme concrète de l'état d'une ressource à un instant
  donné. Une même ressource peut en avoir plusieurs — JSON ou HTML, français ou
  anglais — et c'est le fondement de la négociation de contenu.
- **Champ d'en-tête** : nom insensible à la casse, valeur.

La distinction ressource / représentation fonde la négociation de contenu et
`Vary`.

## Pièges d'examen

**« RFC 9110 remplace RFC 2616 » est un raccourci.** 2616 a d'abord été éclatée
en 7230-7235 ; c'est cette série-là que les 911x remplacent, et pas d'un bloc :
9110 prend la sémantique, 9111 le cache, 9112 la trame HTTP/1.1.

**Une ressource n'est pas sa représentation, et ce n'est pas un détail de
vocabulaire.** `/article/42` désigne une ressource ; le JSON français et le HTML
anglais qu'elle peut rendre sont des représentations. C'est pourquoi la
négociation de contenu porte sur la représentation, et pourquoi `Vary` nomme les
champs qui ont servi à la choisir. Confondre les deux rend `Vary` inexplicable.

**Les propriétés des méthodes — sûre, idempotente — sont bien définies par RFC
9110 (§9.2), mais elles sont évaluées sous l'item *HTTP methods*** de ce lot.

**RFC 9110 ne décrit aucune syntaxe de trame.** Le codage `chunked` ou la ligne
de requête relèvent de RFC 9112.

## Tips d'examen

**« 10, 11, 12 » du plus général au plus concret** : 9110 la sémantique, 9111 le
cache, 9112 HTTP/1.1. Ensuite les numéros suivent les versions : 9113 = HTTP/2,
9114 = HTTP/3.

**`Obsoletes` remplace, `Updates` amende.** RFC 3864 est amendée par 9110, donc
toujours en vigueur.

**Devant un énoncé absolu — « toute », « aucune », « jamais » — relire.** §3.1
écrit « *most* resources are identified by a URI ».

## Points clés

- RFC 9110 = sémantique HTTP, indépendante de la version de transport.
- RFC 9111 = cache ; 9112 / 9113 / 9114 = HTTP/1.1, /2, /3.
- Une ressource a des représentations ; c'est ce qui permet la négociation.
- Les noms de champs d'en-tête sont insensibles à la casse.

## Aller lire la source

- [RFC 9110 — *HTTP Semantics*](https://github.com/httpwg/httpwg.github.io/blob/master/specs/rfc9110.html) — §3 *Terminology*, §6 *Message Abstraction*
