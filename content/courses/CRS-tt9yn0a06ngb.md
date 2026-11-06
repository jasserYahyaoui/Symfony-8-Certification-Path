---
id: CRS-tt9yn0a06ngb
official_item: OIT-c6wd3f444qjn
title: "Unit tests with PHPUnit"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-02"
format: pedagogical
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

## L'essentiel

La documentation Symfony distingue trois types de tests : **unitaire**,
**intégration**, **application**. Un unit test n'a rien de spécifique à
Symfony : un test PHPUnit ordinaire, sans kernel ni container. La mise en
place tient en un package, `symfony/test-pack`, une command, `php bin/phpunit`,
et une convention : `tests/` reproduit `src/`.

## Le schéma

```text
unitaire      une class isolée               extends TestCase (PHPUnit)    aucun kernel
intégration   des classes via le container   extends KernelTestCase        bootKernel()
application   route → controller → vue       extends WebTestCase           createClient()
installation  composer require --dev symfony/test-pack  →  php bin/phpunit
config        phpunit.xml  >  phpunit.dist.xml  >  phpunit.xml.dist (ancien nom, encore lu)
```

## L'exemple : un calcul

```php
// tests/Util/CalculatorTest.php — reflète src/Util/Calculator.php
class CalculatorTest extends TestCase
{
    public function testAdd(): void
    {
        $this->assertSame(42, (new Calculator())->add(30, 12));
    }
}
```

Aucun kernel n'est démarré, aucun container n'est construit.

## Trois types, trois périmètres

| Type | Ce qu'il couvre | Class de base |
|---|---|---|
| **Unitaire** | une class, une method | `TestCase` de PHPUnit |
| **Intégration** | plusieurs classes ensemble, souvent via le container | `KernelTestCase` |
| **Application** | l'application complète, de la route à la vue | `WebTestCase` |

« Test d'application » et *functional test* sont synonymes. Dès qu'un test
appelle `bootKernel()`, il est d'intégration ; sans kernel désigné, il échoue
— « You must set the KERNEL_CLASS environment variable… » —, alors qu'un
`TestCase` passe sans rien configurer.

## L'installation

```bash
composer require --dev symfony/test-pack
php bin/phpunit
```

`symfony/test-pack` tire PHPUnit et d'autres packages utiles aux tests. Flex
crée `phpunit.dist.xml` (avant PHPUnit 10 : `phpunit.xml.dist`) et
`tests/bootstrap.php` ; la configuration par défaut suffit le plus souvent.

| Files présents à la racine | Configuration retenue |
|---|---|
| `phpunit.xml`, `phpunit.dist.xml`, `phpunit.xml.dist` | `phpunit.xml` |
| `phpunit.dist.xml`, `phpunit.xml.dist` | `phpunit.dist.xml` |
| `phpunit.xml.dist` seul | `phpunit.xml.dist` — encore lu |

`phpunit.xml`, non versionné, surcharge localement la configuration partagée.

## Où vivent les tests

Dans `tests/`, dont l'arborescence **reproduit celle de `src/`**, chaque class
se terminant par `Test`. Le suffixe compte : en parcourant un directory,
PHPUnit n'exécute que `CalcTest.php`, pas `CalcTests.php`, qui ne tourne que
désigné explicitement.

```bash
php bin/phpunit tests/Form                     # un répertoire
php bin/phpunit tests/Form/UserTypeTest.php    # un file
```

## Pièges d'examen

**Un unit test ne démarre pas le kernel.** Dès qu'un test appelle
`bootKernel()`, il est d'intégration.

**« Functional test » et « test d'application » désignent la même chose** dans
le vocabulaire Symfony.

**`php bin/phpunit`** est la command que donne la documentation 8.0.

**Un file `…Tests.php` est ignoré** quand PHPUnit parcourt un directory.

**`phpunit.xml` passe avant `phpunit.dist.xml`.**

**`tests/` reflète `src/`**, et les classes se terminent par `Test`.

## Application

- Tester une class de calcul sans dependency → `TestCase`, sans kernel.
- Vérifier que deux services réels coopèrent → `KernelTestCase`.

## QCM d'entraînement

**Question 1.** La racine contient `phpunit.dist.xml` et `phpunit.xml.dist`,
sans `phpunit.xml`. Quelle configuration PHPUnit 11 retient-il ?

- **A.** `phpunit.xml.dist`, l'ancien nom
- **B.** Les deux, fusionnées
- **C.** `phpunit.dist.xml`
- **D.** Aucune : deux files sont ambigus

<details>
<summary>Correction</summary>

**Bonne réponse : C.** `phpunit.dist.xml` passe avant l'ancien nom ; seul
`phpunit.xml`, s'il existait, passerait devant lui.

</details>

**Question 2.** Un vieux projet n'a que `phpunit.xml.dist` à la racine. Que
fait PHPUnit 11 ?

- **A.** Il refuse ce nom, abandonné depuis PHPUnit 10
- **B.** Il le lit encore
- **C.** Il le renomme en `phpunit.dist.xml`
- **D.** Il lance les tests sans configuration

<details>
<summary>Correction</summary>

**Bonne réponse : B.** L'ancien nom est encore lu quand aucun des deux autres
n'est présent ; la documentation 8.0 donne `phpunit.dist.xml`.

</details>

**Question 3.** Un test vérifie le calcul d'une class sans dependency, et
appelle `bootKernel()` par habitude. De quel type est-il, au sens de la
documentation ?

- **A.** Unitaire, puisqu'il teste une seule class
- **B.** Application, puisqu'il démarre Symfony
- **C.** Intégration
- **D.** Fonctionnel, synonyme d'unitaire

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Démarrer le kernel en fait un test d'intégration ; un
unit test reste un test PHPUnit ordinaire, sans kernel ni container.

</details>

**Question 4.** `php bin/phpunit tests/Util/CalcTests.php` est lancé en
désignant le file. Ses tests tournent-ils ?

- **A.** Oui
- **B.** Non : le suffixe `Tests` est toujours ignoré
- **C.** Non : il faut d'abord le renommer
- **D.** Seulement avec `--strict`

<details>
<summary>Correction</summary>

**Bonne réponse : A.** Le suffixe ne restreint que le parcours d'un directory ;
un file désigné explicitement est exécuté.

</details>

## Fiche express

**Si tu dois retenir seulement 6 choses :**

- Trois types : **unitaire**, **intégration**, **application** (= functional).
- Un unit test : **`TestCase`**, sans kernel ni container.
- `bootKernel()` → intégration ; **`KERNEL_CLASS`** requis.
- **`composer require --dev symfony/test-pack`**, puis **`php bin/phpunit`**.
- `phpunit.xml` > `phpunit.dist.xml` > `phpunit.xml.dist`.
- **`tests/` reflète `src/`**, suffixe `Test`.

Moyen mnémotechnique : **« Une class, un kernel, toute l'application »**.

## Sources officielles

- [Testing](https://github.com/symfony/symfony-docs/blob/8.0/testing.rst)
- [FrameworkBundle 8.0, `KernelTestCase`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Test/KernelTestCase.php)

