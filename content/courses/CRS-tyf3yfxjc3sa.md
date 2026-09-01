---
id: CRS-tyf3yfxjc3sa
official_item: OIT-132nwnh9c6bc
title: "Symfony Flex"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/setup.rst"
    anchor: "symfony-flex"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/quick_tour/flex_recipes.rst"
    anchor: "flex-recipes-and-aliases"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
---

## Objectif

Savoir ce qu'est Flex techniquement, ce qu'une recette fait, et quels fichiers
Flex écrit dans le projet.

## Ce que Flex est

Flex est un **plugin Composer**, pas une commande Symfony ni un bundle. Il n'y a
donc rien à appeler : il modifie le comportement de `composer require`,
`composer update` et `composer remove`. Sans lui, `composer require twig`
échouerait, parce que `twig` n'est le nom d'aucun paquet Composer.

## Alias et recettes

Flex ajoute deux mécanismes.

Un **alias** est un nom court qui pointe vers un paquet réel : `twig` est résolu
en `symfony/twig-bundle`. C'est Flex qui fait la résolution, pas Composer.

Une **recette** est la configuration par défaut que le paquet apporte avec lui.
Elle peut activer le bundle dans `config/bundles.php`, déposer des fichiers de
configuration dans `config/packages/`, créer des répertoires, ajouter des
variables dans `.env`. Installer une fonctionnalité et la configurer deviennent
une seule commande.

## symfony.lock

Flex tient la liste des recettes appliquées dans un fichier `symfony.lock`, à la
racine du projet. Ce fichier **doit être committé** : il est ce qui permet de
savoir quelles recettes ont été installées, dans quelle version, et donc de
détecter qu'une recette a évolué. Il joue pour les recettes le rôle que
`composer.lock` joue pour les dépendances — ce sont deux fichiers distincts.

## Les deux dépôts de recettes

| Dépôt | Contenu | Comportement de Flex |
|---|---|---|
| `symfony/recipes` | liste **curée**, paquets maintenus | consulté par défaut, sans question |
| `symfony/recipes-contrib` | toutes les recettes de la communauté | demande une autorisation avant d'installer |

La distinction est un choix de sécurité : le dépôt principal est revu, le dépôt
contrib ne l'est pas, et Flex ne l'applique donc jamais silencieusement.

## Les packs

Un *pack* est un paquet qui ne contient aucun code : seulement des dépendances,
regroupées pour un usage (débogage, tests). Flex le **dépaquette** — il inscrit
les dépendances réelles dans `composer.json` et retire le pack, pour que le
fichier reste lisible.

## Points clés

- Flex est un plugin Composer qui détourne `require`, `update` et `remove`.
- Un alias résout un nom court ; une recette configure le paquet installé.
- `symfony.lock` recense les recettes appliquées et se committe.
- `symfony/recipes` est appliqué par défaut, `symfony/recipes-contrib` demande
  une confirmation.

## Sources officielles

- [Setup, section « Symfony Flex »](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/setup.rst)
- [Quick Tour, « Flex Recipes and Aliases »](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/quick_tour/flex_recipes.rst)
