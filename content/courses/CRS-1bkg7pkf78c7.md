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
---

## Objectif

Faire construire un service par autre chose que son constructeur, et connaître
les écritures de l'option `factory`.

## Quand c'est nécessaire

Le conteneur sait faire `new Classe(...)`. Il ne sait pas choisir entre deux
implémentations selon la configuration, ni appeler `Connection::fromDsn()`, ni
demander un objet à un client tiers. Une **fabrique** couvre ces cas : le
conteneur appelle un appelable, et enregistre ce qu'il retourne.

## Les écritures

| Forme | Écriture YAML | Ce qui est appelé |
|---|---|---|
| méthode statique d'une autre classe | `factory: ['App\Factory', 'create']` | `App\Factory::create()` |
| méthode statique de la classe créée | `factory: [null, 'create']` | `self::create()` |
| méthode d'un **service** | `factory: ['@app.factory', 'create']` | `$factory->create()` |
| service **invocable** | `factory: '@app.factory'` | `$factory->__invoke()` |
| fonction PHP | `factory: 'make_newsletter'` | la fonction |

Le `null` de la deuxième ligne est le point à connaître : il signifie « la classe
du service lui-même », ce qui évite de répéter son nom.

```yaml
services:
    App\Mail\NewsletterManager:
        factory: [null, 'create']
        arguments: ['fabien@symfony.com']
```

Exécuté avec `symfony/dependency-injection` 8.0.15 dans une application
FrameworkBundle : les cinq formes produisent le service, chacune par son chemin.

## Un service est un objet

La fonction doit **retourner un objet**. Exécuté, `factory: 'strtoupper'` —
qui rend une chaîne — échoue au premier `get()` : `TypeError`,
« Container::make(): Return value must be of type ?object, string returned ».
Le mécanisme accepte n'importe quel appelable ; le conteneur, lui, n'enregistre
que des objets.

## Les arguments

`arguments` est passé **à la fabrique**, pas au constructeur — puisque le
constructeur n'est pas appelé. C'est la confusion la plus fréquente.

Exécuté : avec `factory: [null, 'create']`, le constructeur de la classe est
appelé **0** fois, `create()` **1** fois, et deux `get()` rendent le même objet
— un service issu d'une fabrique reste partagé.

## Depuis la classe

`#[Autoconfigure(constructor: 'create')]` désigne une méthode statique de la
classe elle-même comme fabrique, sans toucher à `services.yaml`. Exécuté : le
service est construit par la méthode, le constructeur n'est pas appelé.

## Ce que le conteneur retient

La valeur **retournée** par la fabrique devient le service. Le type déclaré de
la classe reste utilisé pour l'autowiring, et **rien ne vérifie** que la
fabrique le respecte. Exécuté : une fabrique déclarée pour la classe `Liar` qui
rend un `stdClass` passe la compilation ; `get()` rend le `stdClass`, et le
service qui attend `Liar` échoue — `TypeError`, « must be of type App\P9\Liar,
stdClass given ».

## Pièges d'examen

**Les arguments vont à la fabrique.** Le constructeur du service n'est pas
appelé du tout.

**`[null, 'create']` désigne la classe du service**, pas une fonction globale.

**Une fabrique de service utilise `@`** ; une fabrique statique nomme la classe ;
`'@service'` seul appelle `__invoke()`.

**Le conteneur ne contrôle pas le type retourné** : l'erreur arrive chez le
consommateur.

## Points clés

- Une fabrique construit le service à la place du constructeur.
- Cinq écritures : classe statique, `null` pour soi-même, `@service` + méthode,
  `@service` invocable, fonction.
- `arguments` alimente la fabrique ; le service reste partagé.
- `#[Autoconfigure(constructor: '…')]` fait la même chose depuis la classe.

## Sources officielles

- [Using a Factory to Create Services](https://github.com/symfony/symfony-docs/blob/8.0/service_container/factories.rst)
- [DependencyInjection 8.0, `Loader\YamlFileLoader`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Loader/YamlFileLoader.php)
- [DependencyInjection 8.0, `Container`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Container.php)
