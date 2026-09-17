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
| `symfony/recipes` | liste **curée**, pour des paquets de qualité et maintenus | seul consulté par défaut |
| `symfony/recipes-contrib` | toutes les recettes de la communauté | demande votre permission avant d'installer |

**Ce qui distingue les deux n'est pas la qualité de la recette.** La
documentation est précise : les recettes contrib « are guaranteed to work » —
c'est le **paquet associé** qui « could be unmaintained ». Les deux dépôts sont
alimentés par la communauté ; le principal est une liste *curée*, et c'est le
seul que Flex consulte sans demander.

## Les packs

Un *pack* est un paquet qui ne contient aucun code : seulement des dépendances,
regroupées pour un usage (débogage, tests). Flex le **dépaquette** — il inscrit
les dépendances réelles dans `composer.json` et retire le pack, pour que le
fichier reste lisible.

## Ce qu'une recette fait, concrètement

L'exemple de la documentation vaut mieux qu'une définition. `composer require
twig` installe `symfony/twig-bundle`, active le bundle dans
`config/bundles.php`, et ajoute **trois** choses :

- `config/packages/twig.yaml` — une configuration par défaut raisonnable ;
- `config/packages/test/twig.yaml` — des options différentes en environnement de
  test ;
- `templates/`, avec un `base.html.twig` déjà écrit.

Une recette peut donc créer des fichiers, en modifier, créer des répertoires et
ajouter des variables dans `.env`. La liste complète des recettes et des alias
est publiée dans `RECIPES.md`, sur le dépôt des recettes.

## Les packs, et pourquoi ils disparaissent

Un pack est un **métapaquet** Composer : aucun code, seulement des dépendances
regroupées pour un usage. `composer require --dev debug` installe
`symfony/debug-pack`, qui tire `symfony/debug-bundle`, `symfony/monolog-bundle`,
`symfony/var-dumper`…

Le pack ne reste pas dans `composer.json` : Flex le **dépaquette**, et ce sont
les paquets réels qui apparaissent — `symfony/var-dumper` dans `require-dev`,
par exemple. Chercher `symfony/debug-pack` dans son `composer.json` après
installation, c'est chercher ce que Flex a délibérément retiré.

Un alias peut pointer vers un pack : `composer require api` installe
`api-platform/api-pack`, et la documentation note qu'il a fallu **cinq**
recettes pour le configurer.

## Tips d'examen

**Trois mots, trois choses distinctes.** Un **alias** est un nom court résolu
par Flex. Une **recette** est la configuration qu'un paquet apporte. Un **pack**
est un métapaquet sans code. Une seule commande peut mettre les trois en jeu.

**`symfony.lock` recense les recettes ; `composer.lock` recense les
dépendances.** Les deux se committent, pour deux raisons différentes.

**Le contrib demande la permission à cause du paquet, pas de la recette.**

## Pièges d'examen

**Flex est un plugin Composer, pas un bundle ni une commande.** Il n'y a rien à
appeler : il modifie `composer require`, `update` et `remove`. C'est aussi
pourquoi `composer require twig` fonctionne alors qu'aucun paquet ne s'appelle
`twig`.

**`symfony.lock` n'est pas `composer.lock`.** Il recense les **recettes**
appliquées, et il se committe.

**Les deux dépôts de recettes ne se comportent pas pareil.**
`symfony/recipes` est appliqué sans question ; `symfony/recipes-contrib` demande
une permission. La raison n'est pas que ses recettes seraient douteuses — la
documentation les dit « guaranteed to work » — mais que les **paquets** qu'elles
configurent peuvent être abandonnés.

## Points clés

- Flex est un plugin Composer qui détourne `require`, `update` et `remove`.
- Un alias résout un nom court ; une recette configure le paquet installé.
- `symfony.lock` recense les recettes appliquées et se committe.
- `symfony/recipes` est appliqué par défaut, `symfony/recipes-contrib` demande
  une confirmation.

## Sources officielles

- [Setup, section « Symfony Flex »](https://github.com/symfony/symfony-docs/blob/8.0/setup.rst)
- [Quick Tour, « Flex Recipes and Aliases »](https://github.com/symfony/symfony-docs/blob/8.0/quick_tour/flex_recipes.rst)
