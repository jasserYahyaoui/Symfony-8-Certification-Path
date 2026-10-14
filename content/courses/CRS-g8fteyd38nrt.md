---
id: CRS-g8fteyd38nrt
official_item: OIT-stze9x4aydp3
title: "Services registration (YAML and PHP attributes)"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-29"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/service_container.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/service_container.rst"
    branch: "8.0"
    symbol_or_lines: "_defaults, resource, exclude, autoconfigure"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/DependencyInjection/Attribute/Autoconfigure.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Attribute/Autoconfigure.php"
    symbol_or_lines: "Autoconfigure::__construct(tags, calls, bind, lazy, public, shared, autowire, properties, configurator, constructor, resourceTags)"
    branch: "8.0"
    verified_at: "2026-09-29"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/DependencyInjection/Compiler/AutowirePass.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Compiler/AutowirePass.php"
    symbol_or_lines: "needs an instance of %s but this type has been excluded"
    branch: "8.0"
    verified_at: "2026-09-29"
outcome_sections:
  OUT-bqf7brva4nwe: "L'enregistrement automatique"
  OUT-j5rpdd6qsy3y: "Le cas explicite"
  OUT-rf33669d3w01: "Le piège de `_defaults`"
  OUT-112hac96j8hg: "Côté attributes"
---

## Objectif

Déclarer un service, et comprendre pourquoi on n'a presque jamais à le faire.

## L'enregistrement automatique

La configuration par défaut d'un projet neuf, telle que la documente
`service_container.rst` (8.0) :

```yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true

    App\:
        resource: '../src/'
```

Trois mécanismes s'y superposent :

- **la découverte** — toute class de `src/` devient un service dont l'identifier
  est son nom pleinement qualifié ;
- **`autowire`** — les arguments sont résolus par type ;
- **`autoconfigure`** — une class qui implémente une interface connue reçoit
  automatiquement le tag correspondant : un `EventSubscriberInterface` devient un
  abonné, une `Command` une command, sans une ligne de configuration.

Résultat : écrire la class suffit. On ne déclare explicitement que l'exception.

Une class découverte mais inutilisée ne gêne pas : exécuté avec FrameworkBundle
8.0.15, une entité placée dans `src/` avec un argument `string` non résolu
n'empêche pas le démarrage — le service, privé et jamais référencé, est retiré.
Pour sortir un dossier de la découverte, on ajoute une key `exclude` :

```yaml
    App\:
        resource: '../src/'
        exclude: '../src/{Entity,Kernel.php}'
```

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

## Côté attributes

Plusieurs décisions se prennent désormais sur la class :

| Attribute | Effet |
|---|---|
| `#[AsAlias]` | déclare un alias vers cette class |
| `#[Autoconfigure]` | fixe `public`, `lazy`, `shared`, `tags`, `calls`, `bind`, `constructor`… |
| `#[AutoconfigureTag]` | tague toutes les classes qui implémentent l'interface portant l'attribute |
| `#[Autowire]` | câble un argument précis |
| `#[When]` | ne déclare le service que dans un environment |
| `#[Exclude]` | retire la class de la découverte |

Exécuté, dans une application FrameworkBundle 8.0.15 :

| Class | Résultat |
|---|---|
| `#[AsAlias('app.sms')]` | `app.sms` est un alias **privé** vers la class |
| interface `#[AutoconfigureTag('app.notifier')]` | la class qui l'implémente porte le tag `app.notifier` |
| `#[When(env: 'prod')]`, injectée en `dev` | « needs an instance of … but this type has been excluded » |
| la même, en `prod` | injectée |
| `#[Exclude]`, injectée | même erreur, dans tous les environments |

`#[When]` n'efface donc pas la class : hors de son environment, la definition
existe, marquée `container.excluded`, et toute injection échoue avec un message
clair.

## Le piège de `_defaults`

`_defaults` ne s'applique **qu'au block où il est écrit**, et pas aux blocks
`when@dev` ou `when@test` du même file : chaque block doit redéfinir son
`_defaults`. La documentation 8.0 le signale en avertissement.

Exécuté : un service déclaré dans `when@dev` sans `_defaults`, dont le
constructeur attend un `LoggerInterface`, n'est pas autowiré. L'erreur n'arrive
qu'à l'instanciation, et elle ne nomme pas la cause : `ArgumentCountError` —
« Too few arguments to function …::__construct(), 0 passed ». Avec un
`_defaults: { autowire: true }` dans le block, le même service reçoit le logger.

## Pièges d'examen

**Un service découvert est privé** : la découverte ne le rend pas public.

**`exclude` ne supprime pas la class** : elle l'exclut de l'enregistrement
automatique. Une class exclue mais **déclarée** explicitement est enregistrée —
exécuté, avec la visibilité demandée.

**`_defaults` ne traverse pas les blocks `when@`**, et l'oubli se paie par une
`ArgumentCountError` qui ne le mentionne pas.

**`#[When]` exclut hors de son environment** ; `#[Exclude]` exclut partout.

## Points clés

- `resource` découvre, `autowire` câble, `autoconfigure` tague — écrire la class suffit.
- Les arguments explicites se nomment `$argument` ; `@id` référence un service.
- `#[AsAlias]`, `#[Autoconfigure]`, `#[AutoconfigureTag]`, `#[Autowire]`,
  `#[When]`, `#[Exclude]` déclarent depuis la class.
- `_defaults` est local à son block et ne descend pas dans `when@…`.

## Sources officielles

- [Service Container, « Automatic Service Loading »](https://github.com/symfony/symfony-docs/blob/8.0/service_container.rst)
- [DependencyInjection 8.0, `Attribute\Autoconfigure`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Attribute/Autoconfigure.php)
- [DependencyInjection 8.0, `Compiler\AutowirePass`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Compiler/AutowirePass.php)
