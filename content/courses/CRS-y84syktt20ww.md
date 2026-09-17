---
id: CRS-y84syktt20ww
official_item: OIT-347k8phdewem
title: "License"
content_level: MINIMAL
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/LICENSE"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/LICENSE"
    symbol_or_lines: "lines 1-21 (MIT text and copyright holder)"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/composer.json"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/composer.json"
    symbol_or_lines: '"license" key'
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    verified_at: "2026-09-01"
---

## Objectif

Savoir sous quelle licence Symfony est publié et ce que cette licence autorise.

## La licence

Symfony est publié sous licence **MIT**. La clé `license` du `composer.json` du
dépôt `symfony/symfony` porte la valeur `MIT`, et le fichier `LICENSE` à la
racine en donne le texte, avec pour titulaire du copyright **Fabien Potencier**.

## Ce qu'elle autorise

La MIT est une licence permissive. Elle accorde le droit d'utiliser, copier,
modifier, fusionner, publier, distribuer, sous-licencier et vendre le logiciel,
y compris dans un produit propriétaire et commercial.

Elle impose une seule obligation : **reproduire l'avis de copyright et le texte
de la licence** dans toute copie ou portion substantielle du logiciel. Le
logiciel est fourni « en l'état », sans garantie.

Ce n'est donc pas une licence *copyleft* : rien n'oblige à publier sous MIT le
code qui utilise Symfony.

## Le texte, clause par clause

Le fichier `LICENSE` tient en trois paragraphes, et chacun a un rôle distinct.

**L'attribution.** `Copyright (c) 2004-present Fabien Potencier` — une personne
physique, pas une société ni la SensioLabs.

**La concession.** Le droit est accordé « free of charge, to any person
obtaining a copy », et il porte sur huit verbes : *use, copy, modify, merge,
publish, distribute, sublicense, and/or sell*. « Sublicense » est celui qui
compte pour un éditeur : on peut redistribuer sous **une autre licence**.

**La condition, au singulier.** « The above copyright notice and this permission
notice shall be included in all copies or **substantial portions** of the
Software. » L'obligation porte sur les deux avis — copyright **et** licence — et
non sur le seul nom de Symfony.

**Les deux clauses finales**, souvent lues comme une seule : le logiciel est
fourni *AS IS*, sans garantie (dont la qualité marchande et l'adéquation à un
usage particulier), **et** les auteurs ne peuvent être tenus responsables
d'aucun dommage. Absence de garantie et absence de responsabilité sont deux
protections distinctes.

## MIT contre copyleft

| | MIT | Copyleft (GPL et apparentées) |
|---|---|---|
| Usage propriétaire | autorisé | restreint |
| Publier son propre code | non exigé | exigé pour les œuvres dérivées |
| Sous-licencier | autorisé | interdit |
| Obligation | reproduire les deux avis | reproduire **et** partager à l'identique |

## Tips d'examen

**Une seule obligation, et elle est documentaire.** Reproduire l'avis de
copyright et le texte de la licence. Rien sur le code, rien sur la publication.

**« Substantial portions »**, pas « le logiciel entier » : copier un fichier
significatif suffit à déclencher l'obligation.

**Le titulaire est une personne.** Fabien Potencier, depuis 2004.

## Pièges d'examen

**MIT n'est pas copyleft.** Une application qui utilise Symfony n'a aucune
obligation d'être publiée sous MIT, ni d'être publiée du tout. Usage
propriétaire et commercial compris.

**L'unique obligation porte sur l'avis, pas sur le code.** Il faut reproduire
l'avis de copyright et le texte de la licence dans toute copie substantielle du
logiciel — rien de plus.

## Points clés

- Symfony est sous licence **MIT**, titulaire du copyright Fabien Potencier.
- Usage commercial et propriétaire autorisés, sans obligation de réciprocité.
- Seule contrainte : conserver l'avis de copyright et le texte de la licence.

## Sources officielles

- [Fichier LICENSE de symfony/symfony (branche 8.0)](https://github.com/symfony/symfony/blob/8.0/LICENSE)
