---
id: CRS-8zmj5ntgdhjv
official_item: OIT-ar4h3zfskjsp
title: "Configuration parameters"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/configuration/env_var_processors.rst"
    branch: "8.0"
    symbol_or_lines: "env var processors"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/DependencyInjection/EnvVarProcessor.php"
    branch: "8.0"
    symbol_or_lines: "EnvVarProcessor::getProvidedTypes"
    verified_at: "2026-09-01"
---

## Objectif

Déclarer une valeur de configuration et l'injecter. *Quelle* valeur mérite un
paramètre plutôt qu'une variable d'environnement ou une constante est traité
dans *Official best practices* (lot Symfony Architecture).

## Le paramètre

```yaml
parameters:
    app.contents_dir: '%kernel.project_dir%/var/contents'

services:
    App\Service\Archiver:
        arguments: ['%app.contents_dir%']
```

Un paramètre est une **valeur figée à la compilation** : chaîne, nombre,
booléen, tableau. Les pourcents ne sont pas de l'interpolation à l'exécution,
ils sont résolus une fois pour toutes dans le conteneur compilé.

Pour écrire un pourcent littéral, on le double : `%%`.

En PHP, l'accès passe par `ParameterBagInterface` — ou, dans un contrôleur, par
`getParameter()`.

## La variable d'environnement

Une valeur qui dépend de l'endroit où tourne l'application ne peut pas être
figée à la compilation. `%env(...)%` diffère sa lecture à l'exécution :

```yaml
parameters:
    app.dsn: '%env(DATABASE_URL)%'
```

C'est la différence à retenir : **un paramètre est résolu à la compilation, une
variable d'environnement est lue à l'exécution**. Changer la variable ne demande
donc pas de recompiler le conteneur.

## Les processeurs

Une variable d'environnement est toujours une chaîne. Un **processeur** la
convertit :

```yaml
'%env(int:REDIS_PORT)%'
'%env(bool:FEATURE_FLAG)%'
'%env(json:CREDENTIALS)%'
'%env(csv:ALLOWED_HOSTS)%'
'%env(default:app.fallback:UNSET_VAR)%'
'%env(resolve:APP_SECRET)%'
```

Symfony 8.0 en fournit **vingt et un** — `EnvVarProcessor::getProvidedTypes()`
en est la liste autoritative. Ceux qui reviennent : `int`, `bool`, `not`,
`json`, `csv`, `const`, `default`, `resolve` et `enum`.

Ils se **composent**, de droite à gauche : `%env(json:base64:SECRETS)%` décode
d'abord le base64, puis lit le JSON.

## `bind`

Pour éviter de répéter un argument, `bind` associe un nom d'argument — ou un
type — à une valeur, pour tous les services concernés :

```yaml
services:
    _defaults:
        bind:
            string $projectDir: '%kernel.project_dir%'
```

Depuis PHP, l'attribut `#[Autowire]` fait la même chose sur un seul argument.

## Pièges d'examen

**`%param%` est résolu à la compilation**, `%env(VAR)%` à l'exécution. Une
valeur qui doit changer sans redéploiement est une variable d'environnement.

**Les processeurs se lisent de droite à gauche** : le plus interne s'applique
en premier.

**`%%` est un pourcent littéral** ; l'oublier fait chercher un paramètre
inexistant.

## Points clés

- Paramètre : figé à la compilation, injecté par `%nom%`, doublé en `%%`.
- Variable d'environnement : lue à l'exécution par `%env(VAR)%`.
- Vingt et un processeurs, composables de droite à gauche.
- `bind` et `#[Autowire]` évitent de répéter le même argument.

## Sources officielles

- [Environment Variable Processors](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/configuration/env_var_processors.rst)
- [`EnvVarProcessor`, branche 8.0](https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/DependencyInjection/EnvVarProcessor.php)
