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
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/setup.rst"
    anchor: "symfony-flex"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/quick_tour/flex_recipes.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/quick_tour/flex_recipes.rst"
    anchor: "flex-recipes-and-aliases"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
outcome_sections:
  OUT-p7r2daxac21t: "Ce que Flex est"
  OUT-n6w2cz5vwgsw: "Alias et recipes"
  OUT-ach5wc85h25x: "symfony.lock"
  OUT-svxn0t2pskq8: "Les deux repositories de recipes"
---

## Objectif

Savoir ce qu'est Flex techniquement, ce qu'une recipe fait, et quels files
Flex écrit dans le projet.

## Ce que Flex est

Flex est un **plugin Composer**, pas une command Symfony ni un bundle. Il n'y a
donc rien à appeler : il modifie le comportement de `composer require`,
`composer update` et `composer remove`. Sans lui, `composer require twig`
échouerait, parce que `twig` n'est le nom d'aucun package Composer.

## Alias et recipes

Flex ajoute deux mécanismes.

Un **alias** est un nom court qui pointe vers un package réel : `twig` est résolu
en `symfony/twig-bundle`. C'est Flex qui fait la résolution, pas Composer.

Une **recipe** est la configuration par défaut que le package apporte avec lui.
Elle peut activer le bundle dans `config/bundles.php`, déposer des files de
configuration dans `config/packages/`, créer des directories, ajouter des
variables dans `.env`. Installer une fonctionnalité et la configurer deviennent
une seule command.

## symfony.lock

Flex tient la liste des recipes appliquées dans un file `symfony.lock`, à la
racine du projet. Ce file **doit être committé** : il est ce qui permet de
savoir quelles recipes ont été installées, dans quelle version, et donc de
détecter qu'une recipe a évolué. Il joue pour les recipes le role que
`composer.lock` joue pour les dependencies — ce sont deux files distincts.

## Les deux repositories de recipes

| Repository | Contenu | Comportement de Flex |
|---|---|---|
| `symfony/recipes` | liste **curée**, pour des packages de qualité et maintenus | seul consulté par défaut |
| `symfony/recipes-contrib` | toutes les recipes de la communauté | demande votre permission avant d'installer |

**Ce qui distingue les deux n'est pas la qualité de la recipe.** La
documentation est précise : les recipes contrib « are guaranteed to work » —
c'est le **package associé** qui « could be unmaintained ». Les deux repositories sont
alimentés par la communauté ; le principal est une liste *curée*, et c'est le
seul que Flex consulte sans demander.

## Les packs

Un *pack* est un package qui ne contient aucun code : seulement des dependencies,
regroupées pour un usage (debug, tests). Flex le **dépaquette** — il inscrit
les dependencies réelles dans `composer.json` et retire le pack, pour que le
file reste lisible.

## Ce qu'une recipe fait, concrètement

L'exemple de la documentation vaut mieux qu'une definition. `composer require
twig` installe `symfony/twig-bundle`, active le bundle dans
`config/bundles.php`, et ajoute **trois** choses :

- `config/packages/twig.yaml` — une configuration par défaut raisonnable ;
- `config/packages/test/twig.yaml` — des options différentes en environment de
  test ;
- `templates/`, avec un `base.html.twig` déjà écrit.

Une recipe peut donc créer des files, en modifier, créer des directories et
ajouter des variables dans `.env`. La liste complète des recipes et des alias
est publiée dans `RECIPES.md`, sur le repository des recipes.

## Les packs, et pourquoi ils disparaissent

Un pack est un **métapaquet** Composer : aucun code, seulement des dependencies
regroupées pour un usage. `composer require --dev debug` installe
`symfony/debug-pack`, qui tire `symfony/debug-bundle`, `symfony/monolog-bundle`,
`symfony/var-dumper`…

Le pack ne reste pas dans `composer.json` : Flex le **dépaquette**, et ce sont
les packages réels qui apparaissent — `symfony/var-dumper` dans `require-dev`,
par exemple. Chercher `symfony/debug-pack` dans son `composer.json` après
installation, c'est chercher ce que Flex a délibérément retiré.

Un alias peut pointer vers un pack : `composer require api` installe
`api-platform/api-pack`, et la documentation note qu'il a fallu **cinq**
recipes pour le configurer.

## Tips d'examen

**Trois mots, trois choses distinctes.** Un **alias** est un nom court résolu
par Flex. Une **recipe** est la configuration qu'un package apporte. Un **pack**
est un métapaquet sans code. Une seule command peut mettre les trois en jeu.

**`symfony.lock` recense les recipes ; `composer.lock` recense les
dependencies.** Les deux se committent, pour deux raisons différentes.

**Le contrib demande la permission à cause du package, pas de la recipe.**

## Pièges d'examen

**Flex est un plugin Composer, pas un bundle ni une command.** Il n'y a rien à
appeler : il modifie `composer require`, `update` et `remove`. C'est aussi
pourquoi `composer require twig` fonctionne alors qu'aucun package ne s'appelle
`twig`.

**`symfony.lock` n'est pas `composer.lock`.** Il recense les **recipes**
appliquées, et il se committe.

**Les deux repositories de recipes ne se comportent pas pareil.**
`symfony/recipes` est appliqué sans question ; `symfony/recipes-contrib` demande
une permission. La raison n'est pas que ses recipes seraient douteuses — la
documentation les dit « guaranteed to work » — mais que les **packages** qu'elles
configurent peuvent être abandonnés.

## Points clés

- Flex est un plugin Composer qui détourne `require`, `update` et `remove`.
- Un alias résout un nom court ; une recipe configure le package installé.
- `symfony.lock` recense les recipes appliquées et se committe.
- `symfony/recipes` est appliqué par défaut, `symfony/recipes-contrib` demande
  une confirmation.

## Sources officielles

- [Setup, section « Symfony Flex »](https://github.com/symfony/symfony-docs/blob/8.0/setup.rst)
- [Quick Tour, « Flex Recipes and Aliases »](https://github.com/symfony/symfony-docs/blob/8.0/quick_tour/flex_recipes.rst)
