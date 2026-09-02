---
id: CRS-tt9yn0a06ngb
official_item: OIT-c6wd3f444qjn
title: "Unit tests with PHPUnit"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-02"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/testing.rst"
    anchor: "unit-tests"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    verified_at: "2026-09-02"
---

## Objectif

Situer le test unitaire parmi les trois types que la documentation Symfony
distingue, et connaître la mise en place attendue d'un projet.

## Prérequis

Les classes et les interfaces PHP.

## Trois types, trois périmètres

La documentation nomme explicitement trois familles, et l'examen s'appuie sur
ces définitions plutôt que sur celles d'un autre projet :

| Type | Ce qu'il couvre | Classe de base |
|---|---|---|
| **Unitaire** | une unité de code isolée — une classe, une méthode | aucune, `TestCase` de PHPUnit |
| **Intégration** | plusieurs classes ensemble, souvent via le conteneur | `KernelTestCase` |
| **Application** | l'application complète, de la route à la vue | `WebTestCase` |

Le test d'application est ce que beaucoup appellent *test fonctionnel* ; la
documentation retient les deux termes comme synonymes.

## Le test unitaire n'a rien de spécifique à Symfony

C'est le point de fond de cet item. Écrire un test unitaire dans une application
Symfony revient à écrire un test PHPUnit ordinaire : on instancie la classe, on
appelle la méthode, on assertit. **Aucun noyau n'est démarré, aucun conteneur
n'est construit.** Une classe qui a besoin du conteneur pour être testée n'est
plus testée unitairement.

## L'installation

```bash
composer require --dev symfony/test-pack
php bin/phpunit
```

`symfony/test-pack` tire PHPUnit et ce qui l'accompagne. La commande à connaître
est **`php bin/phpunit`**, pas `vendor/bin/phpunit` : Flex installe ce
lanceur.

## Où vivent les tests

Dans `tests/`, dont l'arborescence **reproduit celle de `src/`** : une classe de
`src/Form/` se teste dans `tests/Form/`. Chaque classe de test se termine par
`Test` — `UserTypeTest`.

```bash
php bin/phpunit                          # tout
php bin/phpunit tests/Form               # un répertoire
php bin/phpunit tests/Form/UserTypeTest.php
```

## La configuration

Le fichier est `phpunit.dist.xml` à la racine. Le nom compte : à partir de
PHPUnit 10 c'est `phpunit.dist.xml` ; avant, `phpunit.xml.dist`. Flex le crée,
avec `tests/bootstrap.php`, et la configuration par défaut suffit dans la
plupart des cas. L'autochargement passe par `vendor/autoload.php`.

## Pièges d'examen

**Un test unitaire ne démarre pas le noyau.** Dès qu'un test appelle
`bootKernel()`, il est d'intégration.

**« Test fonctionnel » et « test d'application » désignent la même chose** dans
le vocabulaire Symfony.

**`php bin/phpunit`** est le lanceur installé par Flex.

**`tests/` reflète `src/`**, et les classes se terminent par `Test`.

## Points clés

- Trois types : unitaire, intégration, application.
- Un test unitaire est un test PHPUnit ordinaire, sans noyau ni conteneur.
- `composer require --dev symfony/test-pack`, puis `php bin/phpunit`.
- `phpunit.dist.xml` depuis PHPUnit 10 ; `tests/` reproduit `src/`.

## Sources officielles

- [Testing](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/testing.rst)
