---
id: CRS-1bkg7pkf78c7
official_item: OIT-h2n7d7dbr56p
title: "Factories"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/service_container/factories.rst"
    branch: "8.0"
    symbol_or_lines: "factory, static and non-static factories"
    verified_at: "2026-09-01"
---

## Objectif

Faire construire un service par autre chose que son constructeur, et connaître
les quatre écritures de l'option `factory`.

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
| fonction ou appelable PHP | `factory: 'strtoupper'` | la fonction |

Le `null` de la deuxième ligne est le point à connaître : il signifie « la classe
du service lui-même », ce qui évite de répéter son nom.

```yaml
services:
    App\Mail\NewsletterManager:
        factory: [null, 'create']
        arguments: ['fabien@symfony.com']
```

## Les arguments

`arguments` est passé **à la fabrique**, pas au constructeur — puisque le
constructeur n'est pas appelé. C'est la confusion la plus fréquente.

## Depuis la classe

`#[Autoconfigure(constructor: 'create')]` désigne une méthode statique de la
classe elle-même comme fabrique, sans toucher à `services.yaml`.

## Ce que le conteneur retient

La valeur **retournée** par la fabrique devient le service. Le type déclaré de
la classe reste utilisé pour l'autowiring, ce qui suppose que la fabrique
retourne bien un objet compatible.

## Pièges d'examen

**Les arguments vont à la fabrique.** Le constructeur du service n'est pas
appelé du tout.

**`[null, 'create']` désigne la classe du service**, pas une fonction globale.

**Une fabrique de service utilise `@`** ; une fabrique statique nomme la classe.

## Points clés

- Une fabrique construit le service à la place du constructeur.
- Quatre écritures : classe statique, `null` pour soi-même, `@service`, appelable.
- `arguments` alimente la fabrique.
- `#[Autoconfigure(constructor: '…')]` fait la même chose depuis la classe.

## Sources officielles

- [Using a Factory to Create Services](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/service_container/factories.rst)
