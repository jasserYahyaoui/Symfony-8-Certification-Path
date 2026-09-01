---
id: CRS-1bxqx6ks853z
official_item: OIT-d416348gfhde
title: "Components and Bridges"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/composer.json"
    symbol_or_lines: '"replace" and "provide" keys'
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bridge/Twig/composer.json"
    symbol_or_lines: '"description" and "require" keys'
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    verified_at: "2026-09-01"
---

## Objectif

Distinguer trois notions que le vocabulaire Symfony sépare strictement :
**composant**, **bridge** et **bundle**.

## Le composant

Un composant est une bibliothèque PHP **autonome**, sans dépendance au
framework, qui résout un problème précis : HttpFoundation, Routing, Console,
Finder, Validator. Chacun a son propre dépôt en lecture seule (`symfony/console`,
`symfony/routing`…), extrait du mono-dépôt `symfony/symfony`, et peut être
installé seul dans n'importe quel projet PHP.

Le mono-dépôt déclare cette équivalence dans la clé `replace` de son
`composer.json` : installer `symfony/symfony` remplace chacun des paquets
individuels.

## Le bridge

Un bridge est le code d'**intégration entre un composant Symfony et une
bibliothèque tierce**. Il n'a de sens que si les deux côtés sont présents, et
il ne fait aucune intégration dans le framework lui-même.

Le bridge Twig en est l'exemple canonique : il fournit les extensions Twig qui
donnent accès aux fonctionnalités des composants Symfony — génération d'URL,
rendu de formulaire, traduction — depuis un gabarit. Sans Twig il est inutile,
sans les composants il n'a rien à exposer.

Sur la branche 8.0, `src/Symfony/Bridge/` contient cinq bridges.

## Le bundle

Un bundle est le code d'intégration **dans le framework Symfony** : il fournit
une extension de configuration, enregistre des services dans le conteneur,
ajoute des commandes ou des routes. `FrameworkBundle`, `TwigBundle`,
`SecurityBundle` sont des bundles.

## Le triangle

| | Autonome ? | Dépend d'un tiers ? | Configure le framework ? |
|---|---|---|---|
| Composant | oui | non | non |
| Bridge | non | oui | non |
| Bundle | non | parfois | **oui** |

L'enchaînement usuel se lit dans cet ordre : un **composant** apporte la
fonctionnalité, un **bridge** la relie à une bibliothèque tierce, un **bundle**
la branche dans le framework et la rend configurable. C'est pourquoi un même
outil apparaît parfois trois fois sous trois noms voisins.

## Points clés

- Composant = bibliothèque autonome, dépôt propre, utilisable hors framework.
- Bridge = intégration composant ↔ bibliothèque tierce ; 5 sur la branche 8.0.
- Bundle = intégration dans le framework ; c'est lui qui configure.
- `replace` dans le `composer.json` du mono-dépôt liste les paquets remplacés.

## Sources officielles

- [composer.json de symfony/symfony (branche 8.0)](https://raw.githubusercontent.com/symfony/symfony/8.0/composer.json)
- [composer.json du bridge Twig](https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bridge/Twig/composer.json)
