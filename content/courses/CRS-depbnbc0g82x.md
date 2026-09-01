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
    branch: "master"
    symbol_or_lines: "sections 3 Terminology, 6 Message Abstraction, 9 Methods, 15 Status Codes"
    verified_at: "2026-09-01"
---

## Objectif

Situer RFC 9110 dans la famille des spécifications HTTP et reconnaître le
vocabulaire qu'elle impose.

## Ce que RFC 9110 remplace

RFC 9110 — *HTTP Semantics* (2022) — remplace les anciennes RFC 7230 à 7235,
elles-mêmes successeurs de RFC 2616. C'est le document de référence actuel pour
la **sémantique** d'HTTP.

| RFC | Périmètre |
|---|---|
| **9110** | Sémantique : méthodes, codes de statut, champs d'en-tête, négociation |
| 9111 | Mise en cache |
| 9112 | HTTP/1.1 (syntaxe de la trame) |
| 9113 | HTTP/2 |
| 9114 | HTTP/3 |

La sémantique est **indépendante de la version** : une méthode ou un code de
statut signifie la même chose en HTTP/1.1, HTTP/2 et HTTP/3. Seul le transport
change.

## Vocabulaire imposé

- **Message** : requête ou réponse, composé d'une ligne de départ, de champs
  d'en-tête, et éventuellement d'un corps.
- **Ressource** : la cible identifiée par un URI. Ce n'est pas un fichier.
- **Représentation** : une forme concrète de l'état d'une ressource à un instant
  donné. Une même ressource peut en avoir plusieurs — JSON ou HTML, français ou
  anglais — et c'est le fondement de la négociation de contenu.
- **Champ d'en-tête** : nom insensible à la casse, valeur associée.

La distinction ressource / représentation est celle qui rend intelligibles la
négociation de contenu et l'en-tête `Vary`.

## Points clés

- RFC 9110 = sémantique HTTP, indépendante de la version de transport.
- RFC 9111 = cache ; 9112 / 9113 / 9114 = HTTP/1.1, /2, /3.
- Une ressource a des représentations ; c'est ce qui permet la négociation.
- Les noms de champs d'en-tête sont insensibles à la casse.

## Sources officielles

- RFC 9110 — *HTTP Semantics*
