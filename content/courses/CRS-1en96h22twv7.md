---
id: CRS-1en96h22twv7
official_item: OIT-favt42nvgdqh
title: "Functional tests with PHPUnit"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-02"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/testing.rst"
    anchor: "application-tests"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    verified_at: "2026-09-02"
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
`createClient()` démarre le noyau : il ne faut pas appeler `bootKernel()` en
plus.

## Le rythme du test

La documentation le décrit en quatre temps, et ce rythme structure tout test
d'application :

1. faire une requête ;
2. interagir avec la page — cliquer, soumettre ;
3. tester la réponse ;
4. recommencer.

## L'environnement `test`

Le noyau démarre dans l'environnement `test`. La configuration propre aux tests
va donc dans `config/packages/test/` ou sous la clé **`when@test`** :

```yaml
when@test:
    twig:
        strict_variables: true
```

Les variables d'environnement sont lues dans cet ordre — le dernier gagne :
`.env`, puis `.env.test`, puis `.env.test.local`. **`.env.local` n'est pas lu**
en environnement de test, délibérément : une machine de développement ne doit
pas modifier le résultat des tests.

## Les assertions de Symfony

Elles rendent l'échec lisible : `assertResponseIsSuccessful()` affiche la
réponse reçue, là où `assertEquals(200, $code)` n'affiche qu'un nombre.

**Sur la réponse** — `assertResponseIsSuccessful()` (2xx),
`assertResponseStatusCodeSame()`, `assertResponseRedirects()`,
`assertResponseHasHeader()`, `assertResponseHeaderSame()`,
`assertResponseHasCookie()`, `assertResponseIsUnprocessable()` (422).

**Sur la requête** — `assertRouteSame()`, `assertRequestAttributeValueSame()`.

**Sur le navigateur** — `assertBrowserHasCookie()`,
`assertBrowserHistoryIsOnFirstPage()`.

**Sur le contenu** — `assertSelectorExists()`, `assertSelectorCount()`,
`assertSelectorTextContains()`, `assertPageTitleSame()`,
`assertInputValueSame()`, `assertCheckboxChecked()`.

`assertRouteSame()` mérite d'être connue : elle vérifie **quelle route a
répondu**, ce qu'aucune assertion sur le HTML ne dit.

## Une URL en dur

La documentation recommande d'écrire l'URL littéralement plutôt que de la
générer par le routeur. La raison est nette : un test qui génère son URL suit
automatiquement un changement de route, et cesse donc de détecter ce changement
— alors que l'utilisateur, lui, le subit.

## Pièges d'examen

**`WebTestCase` étend `KernelTestCase`** ; `createClient()` démarre déjà le
noyau.

**`.env.local` est ignoré en test**, contrairement à `.env.test.local`.

**`assertResponseIsSuccessful()` couvre tout le 2xx**, pas seulement 200.

**Une URL en dur est la bonne pratique**, à rebours de l'intuition.

## Points clés

- `WebTestCase`, `static::createClient()`, requête, assertions.
- Quatre temps : requête, interaction, assertion, recommencer.
- `when@test` et l'ordre `.env` → `.env.test` → `.env.test.local`.
- Les assertions Symfony couvrent réponse, requête, navigateur et contenu.

## Sources officielles

- [Testing](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/testing.rst)
