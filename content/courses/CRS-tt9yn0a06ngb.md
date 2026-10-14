---
id: CRS-tt9yn0a06ngb
official_item: OIT-c6wd3f444qjn
title: "Unit tests with PHPUnit"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-02"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/testing.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/testing.rst"
    anchor: "unit-tests"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    verified_at: "2026-09-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/Test/KernelTestCase.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Test/KernelTestCase.php"
    branch: "8.0"
    symbol_or_lines: "getKernelClass() — LogicException without KERNEL_CLASS"
    verified_at: "2026-10-02"
outcome_sections:
  OUT-tn4gp01mcsfz: "Trois types, trois périmètres"
  OUT-mytdwrbfbyr0: "Où vivent les tests"
  OUT-s84n2y8t4g36: "L'installation"
---

## Objectif

Situer le unit test parmi les trois types que la documentation Symfony
distingue, et connaître la mise en place attendue d'un projet.

## Prérequis

Les classes et les interfaces PHP.

## Trois types, trois périmètres

La documentation nomme explicitement trois familles, et l'examen s'appuie sur
ces definitions plutôt que sur celles d'un autre projet :

| Type | Ce qu'il couvre | Class de base |
|---|---|---|
| **Unitaire** | une unité de code isolée — une class, une method | aucune, `TestCase` de PHPUnit |
| **Intégration** | plusieurs classes ensemble, souvent via le container | `KernelTestCase` |
| **Application** | l'application complète, de la route à la vue | `WebTestCase` |

Le test d'application est ce que beaucoup appellent *functional test* ; la
documentation retient les deux termes comme synonymes.

## Le unit test n'a rien de spécifique à Symfony

C'est le point de fond de cet item. Écrire un unit test dans une application
Symfony revient à écrire un test PHPUnit ordinaire : on instancie la class, on
appelle la method, on assertit. **Aucun kernel n'est démarré, aucun container
n'est construit.** Une class qui a besoin du container pour être testée n'est
plus testée unitairement.

La frontière se voit à l'exécution. Exécuté avec PHPUnit 11.5 et FrameworkBundle
8.0.15, dans la même suite : un test qui étend `TestCase` passe sans rien
configurer ; un test qui étend `KernelTestCase` et appelle `bootKernel()` échoue
tant que le kernel n'est pas désigné — `LogicException` : « You must set the
KERNEL_CLASS environment variable… ».

## L'installation

```bash
composer require --dev symfony/test-pack
php bin/phpunit
```

`symfony/test-pack` tire PHPUnit (`phpunit/phpunit`) et d'autres packages utiles
aux tests. La documentation 8.0 lance la suite par **`php bin/phpunit`**.

## Où vivent les tests

Dans `tests/`, dont l'arborescence **reproduit celle de `src/`** : une class de
`src/Form/` se teste dans `tests/Form/`. Chaque class de test se termine par
`Test` — `UserTypeTest`.

Le suffixe n'est pas décoratif. Exécuté, un directory contenant `CalcTest.php`
et `CalcTests.php` : le parcours du directory n'exécute **que** `CalcTest.php`
(1 test). `CalcTests.php` ne tourne que si on le désigne explicitement.

```bash
php bin/phpunit                          # tout
php bin/phpunit tests/Form               # un répertoire
php bin/phpunit tests/Form/UserTypeTest.php
```

## La configuration

Le file est `phpunit.dist.xml` à la racine ; la documentation précise
qu'avant PHPUnit 10 il s'appelait `phpunit.xml.dist`. Flex le crée, avec
`tests/bootstrap.php`, et la configuration par défaut suffit dans la plupart
des cas. L'autochargement passe par `vendor/autoload.php`.

Exécuté avec PHPUnit 11.5, les trois noms à la racine :

| Files présents | Configuration retenue |
|---|---|
| `phpunit.xml`, `phpunit.dist.xml`, `phpunit.xml.dist` | `phpunit.xml` |
| `phpunit.dist.xml`, `phpunit.xml.dist` | `phpunit.dist.xml` |
| `phpunit.xml.dist` seul | `phpunit.xml.dist` — encore lu |

`phpunit.xml`, non versionné, sert donc à surcharger localement la
configuration partagée.

## Pièges d'examen

**Un unit test ne démarre pas le kernel.** Dès qu'un test appelle
`bootKernel()`, il est d'intégration.

**« Functional test » et « test d'application » désignent la même chose** dans
le vocabulaire Symfony.

**`php bin/phpunit`** est la command que donne la documentation 8.0.

**Un file `…Tests.php` est ignoré** quand PHPUnit parcourt un directory.

**`phpunit.xml` passe avant `phpunit.dist.xml`.**

**`tests/` reflète `src/`**, et les classes se terminent par `Test`.

## Points clés

- Trois types : unitaire, intégration, application.
- Un unit test est un test PHPUnit ordinaire, sans kernel ni container.
- `composer require --dev symfony/test-pack`, puis `php bin/phpunit`.
- `phpunit.dist.xml` depuis PHPUnit 10, `phpunit.xml` le surcharge ; `tests/`
  reproduit `src/`, suffixe `Test`.

## Sources officielles

- [Testing](https://github.com/symfony/symfony-docs/blob/8.0/testing.rst)
- [FrameworkBundle 8.0, `KernelTestCase`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Test/KernelTestCase.php)
