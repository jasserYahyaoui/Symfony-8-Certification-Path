---
id: CRS-1en96h22twv7
official_item: OIT-favt42nvgdqh
title: "Functional tests with PHPUnit"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-02"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/testing.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/testing.rst"
    anchor: "application-tests"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    verified_at: "2026-09-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/Test/WebTestCase.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Test/WebTestCase.php"
    branch: "8.0"
    symbol_or_lines: "createClient() — LogicException if the kernel is already booted"
    verified_at: "2026-10-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/Test/BrowserKitAssertionsTrait.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Test/BrowserKitAssertionsTrait.php"
    branch: "8.0"
    symbol_or_lines: "setBrowserKitAssertionsAsVerbose(); $verbose on response assertions"
    verified_at: "2026-10-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Dotenv/Dotenv.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Dotenv/Dotenv.php"
    branch: "8.0"
    symbol_or_lines: "loadEnv() — .env.local ignored for test envs"
    verified_at: "2026-10-02"
---

## Objectif

Écrire un test qui traverse l'application entière, et connaître les assertions
que Symfony ajoute à PHPUnit — c'est là que se joue la lisibilité du test.

## Prérequis

Les trois types de tests, et le cycle requête-réponse.

## Le squelette

```php
namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PostControllerTest extends WebTestCase
{
    public function testSomething(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Hello World');
    }
}
```

`WebTestCase` **étend `KernelTestCase`** et lui ajoute le client. Appeler
`createClient()` démarre le kernel : il ne faut pas appeler `bootKernel()` en
plus. Exécuté avec FrameworkBundle 8.0.15 et PHPUnit 11.5, `bootKernel()` puis
`createClient()` : `LogicException` — « Booting the kernel before calling
"…WebTestCase::createClient()" is not supported, the kernel should only be
booted once. »

## Le rythme du test

La documentation le décrit en quatre temps, et ce rythme structure tout test
d'application :

1. faire une Request ;
2. interagir avec la page — cliquer, soumettre ;
3. tester la Response ;
4. recommencer.

## L'environment `test`

Le kernel démarre dans l'environment `test`. La configuration propre aux tests
va donc dans `config/packages/test/` ou sous la key **`when@test`** :

```yaml
when@test:
    twig:
        strict_variables: true
```

Les variables d'environment sont lues dans cet ordre — le dernier gagne :
`.env`, puis `.env.test`, puis `.env.test.local`. **`.env.local` n'est pas lu**
en environment de test, délibérément : une machine de développement ne doit
pas modifier le résultat des tests. Exécuté avec Dotenv 8.0.15, une variable
`A` définie dans les quatre files :

| Situation | Valeur de `A` |
|---|---|
| `APP_ENV=test`, les quatre files | celle de `.env.test.local` |
| `APP_ENV=test`, sans `.env.test.local` | celle de `.env.test` |
| `APP_ENV=dev` | celle de `.env.local` |
| `APP_ENV=test`, `A` déjà définie dans le shell | celle du shell |

Une variable présente **seulement** dans `.env.local` est absente en `test`.
Et une vraie variable d'environment l'emporte sur tous les files.

## Les assertions de Symfony

Elles rendent l'échec lisible : `assertResponseIsSuccessful()` affiche la
Response reçue, là où `assertEquals(200, $code)` n'affiche qu'un nombre.
Exécuté sur un 404 : l'échec imprime headers **et** corps. Après
`self::setBrowserKitAssertionsAsVerbose(false)`, seulement les headers ; le
parameter `$verbose` de chaque assertion fait de même au cas par cas.

**Sur la Response** — `assertResponseIsSuccessful()` (2xx),
`assertResponseStatusCodeSame()`, `assertResponseRedirects()`,
`assertResponseHasHeader()`, `assertResponseHeaderSame()`,
`assertResponseHasCookie()`, `assertResponseIsUnprocessable()` (422).

**Sur la Request** — `assertRouteSame()`, `assertRequestAttributeValueSame()`.

**Sur le navigateur** — `assertBrowserHasCookie()`,
`assertBrowserHistoryIsOnFirstPage()`.

**Sur le contenu** — `assertSelectorExists()`, `assertSelectorCount()`,
`assertSelectorTextContains()`, `assertPageTitleSame()`,
`assertInputValueSame()`, `assertCheckboxChecked()`.

`assertRouteSame()` mérite d'être connue : elle vérifie **quelle route a
répondu**, ce qu'aucune assertion sur le HTML ne dit.

## Une URL en dur

La documentation recommande d'écrire l'URL littéralement plutôt que de la
générer par le router. La raison est nette : un test qui génère son URL suit
automatiquement un changement de route, et cesse donc de détecter ce changement
— alors que le user, lui, le subit.

## Pièges d'examen

**`WebTestCase` étend `KernelTestCase`** ; `createClient()` démarre déjà le
kernel.

**`.env.local` est ignoré en test**, contrairement à `.env.test.local`.

**`assertResponseIsSuccessful()` couvre tout le 2xx**, pas seulement 200 —
exécuté : une Response 201 passe.

**Une variable du shell bat `.env.test.local`.**

**Une URL en dur est la bonne pratique**, à rebours de l'intuition.

## Points clés

- `WebTestCase`, `static::createClient()`, Request, assertions.
- Quatre temps : Request, interaction, assertion, recommencer.
- `when@test` et l'ordre `.env` → `.env.test` → `.env.test.local`.
- Les assertions Symfony couvrent Response, Request, navigateur et contenu.

## Sources officielles

- [Testing](https://github.com/symfony/symfony-docs/blob/8.0/testing.rst)
- [FrameworkBundle 8.0, `WebTestCase`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Test/WebTestCase.php)
- [Dotenv 8.0, `Dotenv::loadEnv()`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Dotenv/Dotenv.php)
