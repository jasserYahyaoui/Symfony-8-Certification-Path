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
    readable_url: "https://github.com/symfony/symfony/blob/8.0/composer.json"
    symbol_or_lines: '"replace" and "provide" keys'
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bridge/Twig/composer.json"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/composer.json"
    symbol_or_lines: '"description" and "require" keys'
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bridge/PhpUnit/composer.json"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/PhpUnit/composer.json"
    symbol_or_lines: '"name", "type", "description" and "require" keys'
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-18"
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

Sur la branche 8.0, **trois** bridges sont publiés comme paquets séparés et
listés dans la clé `replace` du mono-dépôt : `symfony/doctrine-bridge`,
`symfony/monolog-bridge` et `symfony/twig-bridge`. Un quatrième répertoire
existe, `PhpUnit`, et il est **absent** de `replace` — voir ci-dessous.

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

## `replace` et `provide`, deux clés distinctes

Le `composer.json` du mono-dépôt en porte deux, et elles ne disent pas la même
chose.

`replace` liste **65 paquets**, et pas seulement des composants : cinq bundles y
figurent — `framework-bundle`, `security-bundle`, `twig-bundle`,
`debug-bundle`, `web-profiler-bundle`. Un projet qui exige `symfony/console` est
donc satisfait par `symfony/symfony`, sans que le paquet séparé soit installé.

`provide` ne liste **aucun** paquet Symfony : uniquement des noms terminés par
`-implementation` — `psr/log-implementation`, `psr/cache-implementation`,
`symfony/event-dispatcher-implementation`. La clé déclare que ce dépôt fournit
une implémentation de ces interfaces, ce qui satisfait une bibliothèque tierce
qui l'exige.

## Le bridge PhpUnit, qui dément la définition

`src/Symfony/Bridge/PhpUnit/` existe sur la branche 8.0, porte
`"type": "symfony-bridge"`, et pourtant :

```json
"name": "symfony/phpunit-bridge",
"description": "Provides utilities for PHPUnit, especially user deprecation notices management",
"require": { "php": ">=8.1.0" }
```

**Il ne dépend d'aucun composant Symfony et d'aucune bibliothèque tierce.** Son
seul prérequis est PHP. Il n'a donc pas les « deux côtés » que la définition
générale suppose — c'est un bridge par son nom et par son type déclaré, pas par
sa structure de dépendances.

Comparer avec le bridge Twig, qui est le cas canonique :

```json
"require": { "php": ">=8.4", "symfony/translation-contracts": "^2.5|^3", "twig/twig": "^3.21|^4.0" }
```

Deux détails valent d'être remarqués. D'abord la contrainte PHP : `>=8.1.0`
pour le bridge PHPUnit contre `>=8.4` pour le bridge Twig — le premier doit
pouvoir s'exécuter sur des PHP plus anciens, puisqu'il sert à tester. Ensuite
son absence de `replace` : installer `symfony/symfony` ne fournit **pas**
`symfony/phpunit-bridge`, qui s'installe séparément, en `require-dev`.

## Tips d'examen

**Trois questions, trois réponses.** Peut-il vivre seul ? → composant. A-t-il
besoin d'une bibliothèque tierce ? → bridge. Configure-t-il le framework ? →
bundle.

**`replace` ≠ `require`.** Le mono-dépôt déclare remplacer 65 paquets ; il ne
les installe pas en plus.

**Le bridge PHPUnit est l'exception à connaître** : aucun côté tiers, aucune
entrée dans `replace`, une contrainte PHP plus basse que le reste du framework.

## Pièges d'examen

**Un bridge ne configure rien.** C'est la ligne qui sépare bridge et bundle :
le bridge relie un composant à une bibliothèque tierce, le bundle branche le
tout dans le framework et apporte la configuration. Un bridge posé seul dans un
projet Symfony n'enregistre aucun service.

**Un composant ne dépend pas du framework.** L'installer dans un projet PHP
quelconque est le test : si c'est impossible, ce n'est pas un composant.

**`replace` n'est pas `require`.** Le `composer.json` du mono-dépôt déclare que
`symfony/symfony` *remplace* chaque paquet individuel ; il ne les installe pas
en plus.

## Points clés

- Composant = bibliothèque autonome, dépôt propre, utilisable hors framework.
- Bridge = intégration composant ↔ bibliothèque tierce ; trois sont publiés
  comme paquets et listés dans `replace`, et `PhpUnit` en est l'exception.
- Bundle = intégration dans le framework ; c'est lui qui configure.
- `replace` dans le `composer.json` du mono-dépôt liste les paquets remplacés.

## Sources officielles

- [composer.json de symfony/symfony (branche 8.0)](https://github.com/symfony/symfony/blob/8.0/composer.json)
- [composer.json du bridge Twig](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/composer.json)
- [composer.json du bridge PHPUnit](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/PhpUnit/composer.json)
