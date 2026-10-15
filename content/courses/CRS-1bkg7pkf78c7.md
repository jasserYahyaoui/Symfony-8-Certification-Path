---
id: CRS-1bkg7pkf78c7
official_item: OIT-h2n7d7dbr56p
title: "Factories"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-29"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/service_container/factories.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/service_container/factories.rst"
    branch: "8.0"
    symbol_or_lines: "factory, static and non-static factories"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/DependencyInjection/Loader/YamlFileLoader.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Loader/YamlFileLoader.php"
    symbol_or_lines: "parseCallable() — a @service string becomes [Reference, __invoke]"
    branch: "8.0"
    verified_at: "2026-09-29"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/DependencyInjection/Container.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Container.php"
    symbol_or_lines: "Container::make(): ?object"
    branch: "8.0"
    verified_at: "2026-09-29"
outcome_sections:
  OUT-rrswf59s7t5a: "Les écritures"
  OUT-mfkawre47dv3: "Les arguments"
  OUT-5tp6e11ygzgz: "Les écritures"
---

## Objectif

Faire construire un service par autre chose que son constructor, et connaître
les écritures de l'option `factory`.

## Quand c'est nécessaire

Le container sait faire `new Classe(...)`. Il ne sait pas choisir entre deux
implémentations selon la configuration, ni appeler `Connection::fromDsn()`, ni
demander un object à un client tiers. Une **fabrique** couvre ces cas : le
container appelle un callable, et enregistre ce qu'il retourne.

## Les écritures

| Forme | Écriture YAML | Ce qui est appelé |
|---|---|---|
| method statique d'une autre class | `factory: ['App\Factory', 'create']` | `App\Factory::create()` |
| method statique de la class créée | `factory: [null, 'create']` | `self::create()` |
| method d'un **service** | `factory: ['@app.factory', 'create']` | `$factory->create()` |
| service **invocable** | `factory: '@app.factory'` | `$factory->__invoke()` |
| function PHP | `factory: 'make_newsletter'` | la function |

Le `null` de la deuxième ligne est le point à connaître : il signifie « la class
du service lui-même », ce qui évite de répéter son nom.

```yaml
services:
    App\Mail\NewsletterManager:
        factory: [null, 'create']
        arguments: ['fabien@symfony.com']
```

Exécuté avec `symfony/dependency-injection` 8.0.15 dans une application
FrameworkBundle : les cinq formes produisent le service, chacune par son path.

## Un service est un object

La function doit **retourner un object**. Exécuté, `factory: 'strtoupper'` —
qui rend une string — échoue au premier `get()` : `TypeError`,
« Container::make(): Return value must be of type ?object, string returned ».
Le mécanisme accepte n'importe quel callable ; le container, lui, n'enregistre
que des objects.

## Les arguments

`arguments` est passé **à la factory**, pas au constructor — puisque le
constructor n'est pas appelé. C'est la confusion la plus fréquente.

Exécuté : avec `factory: [null, 'create']`, le constructor de la class est
appelé **0** fois, `create()` **1** fois, et deux `get()` rendent le même object
— un service issu d'une factory reste partagé.

## Depuis la class

`#[Autoconfigure(constructor: 'create')]` désigne une method statique de la
class elle-même comme fabrique, sans toucher à `services.yaml`. Exécuté : le
service est construit par la method, le constructor n'est pas appelé.

## Ce que le container retient

La valeur **retournée** par la factory devient le service. Le type déclaré de
la class reste utilisé pour l'autowiring, et **rien ne vérifie** que la
fabrique le respecte. Exécuté : une factory déclarée pour la class `Liar` qui
rend un `stdClass` passe la compilation ; `get()` rend le `stdClass`, et le
service qui attend `Liar` échoue — `TypeError`, « must be of type App\P9\Liar,
stdClass given ».

## Pièges d'examen

**Les arguments vont à la factory.** Le constructor du service n'est pas
appelé du tout.

**`[null, 'create']` désigne la class du service**, pas une function globale.

**Une factory de service utilise `@`** ; une factory statique nomme la class ;
`'@service'` seul appelle `__invoke()`.

**Le container ne contrôle pas le type retourné** : l'erreur arrive chez le
consommateur.

## Points clés

- Une factory construit le service à la place du constructor.
- Cinq écritures : class statique, `null` pour soi-même, `@service` + method,
  `@service` invocable, function.
- `arguments` alimente la factory ; le service reste partagé.
- `#[Autoconfigure(constructor: '…')]` fait la même chose depuis la class.

## Sources officielles

- [Using a Factory to Create Services](https://github.com/symfony/symfony-docs/blob/8.0/service_container/factories.rst)
- [DependencyInjection 8.0, `Loader\YamlFileLoader`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Loader/YamlFileLoader.php)
- [DependencyInjection 8.0, `Container`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Container.php)
