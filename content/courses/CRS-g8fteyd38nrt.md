---
id: CRS-g8fteyd38nrt
official_item: OIT-stze9x4aydp3
title: "Services registration (YAML and PHP attributes)"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/service_container.rst"
    branch: "8.0"
    symbol_or_lines: "_defaults, resource, exclude, autoconfigure"
    verified_at: "2026-09-01"
---

## Objectif

Déclarer un service, et comprendre pourquoi on n'a presque jamais à le faire.

## L'enregistrement automatique

`config/services.yaml` contient, dans une application neuve :

```yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true

    App\:
        resource: '../src/'
        exclude:
            - '../src/DependencyInjection/'
            - '../src/Entity/'
            - '../src/Kernel.php'
```

Trois mécanismes s'y superposent :

- **la découverte** — toute classe de `src/` devient un service dont l'identifiant
  est son nom pleinement qualifié, sauf celles listées dans `exclude` ;
- **`autowire`** — les arguments sont résolus par type ;
- **`autoconfigure`** — une classe qui implémente une interface connue reçoit
  automatiquement le tag correspondant : un `EventSubscriberInterface` devient un
  abonné, une `Command` une commande, sans une ligne de configuration.

Résultat : écrire la classe suffit. On ne déclare explicitement que l'exception.

## Le cas explicite

```yaml
services:
    App\Service\Archiver:
        arguments:
            $directory: '%app.contents_dir%'
            $logger: '@monolog.logger.archive'
        calls:
            - setFormat: ['zip']
        tags: ['app.archiver']
```

Les arguments se nomment `$nom` — le **nom de l'argument**, pas sa position — et
`@id` référence un autre service.

## Côté attributs

Plusieurs décisions se prennent désormais sur la classe :

| Attribut | Effet |
|---|---|
| `#[AsAlias]` | déclare un alias vers cette classe |
| `#[Autoconfigure]` | fixe `public`, `lazy`, `tags`, `bind`, `constructor`… |
| `#[AutoconfigureTag]` | tague toutes les classes qui implémentent l'interface portant l'attribut |
| `#[Autowire]` | câble un argument précis |
| `#[When]` | ne déclare le service que dans un environnement |
| `#[Exclude]` | retire la classe de la découverte |

## Le piège de `_defaults`

`_defaults` ne s'applique **qu'au fichier où il est écrit**, et pas aux blocs
`when@dev` ou `when@test` du même fichier : chaque bloc doit redéfinir son
`_defaults`. Un service déclaré dans un bloc d'environnement sans `_defaults`
n'est donc ni autowiré ni autoconfiguré.

## Pièges d'examen

**Un service découvert est privé** : la découverte ne le rend pas public.

**`exclude` ne supprime pas la classe** : elle l'exclut de l'enregistrement
automatique. Une classe exclue mais référencée explicitement est enregistrée.

**`_defaults` ne traverse pas les blocs `when@`.**

## Points clés

- `resource` découvre, `autowire` câble, `autoconfigure` tague — écrire la classe suffit.
- Les arguments explicites se nomment `$argument` ; `@id` référence un service.
- `#[AsAlias]`, `#[Autoconfigure]`, `#[AutoconfigureTag]`, `#[Autowire]`,
  `#[When]`, `#[Exclude]` déclarent depuis la classe.
- `_defaults` est local à son fichier et ne descend pas dans `when@…`.

## Sources officielles

- [Service Container, « Automatic Service Loading »](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/service_container.rst)
