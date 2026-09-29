---
id: CRS-8zmj5ntgdhjv
official_item: OIT-ar4h3zfskjsp
title: "Configuration parameters"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-29"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/configuration/env_var_processors.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/configuration/env_var_processors.rst"
    branch: "8.0"
    symbol_or_lines: "env var processors"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/DependencyInjection/EnvVarProcessor.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/EnvVarProcessor.php"
    branch: "8.0"
    symbol_or_lines: "EnvVarProcessor::getProvidedTypes"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/DependencyInjection/ParameterBag/ParameterBag.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/ParameterBag/ParameterBag.php"
    symbol_or_lines: "ParameterBag::resolveString(), pattern %%|%([^%\\s]+)%"
    branch: "8.0"
    verified_at: "2026-09-29"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/DependencyInjection/Compiler/ResolveBindingsPass.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Compiler/ResolveBindingsPass.php"
    symbol_or_lines: "A binding is configured for an argument %s, but no corresponding argument has been found"
    branch: "8.0"
    verified_at: "2026-09-29"
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
ils sont résolus une fois pour toutes dans le conteneur compilé. Exécuté avec
`symfony/dependency-injection` 8.0.15 : dans le conteneur PHP généré, la valeur
du paramètre est écrite en dur.

En PHP, l'accès passe par `ParameterBagInterface` — ou, dans un contrôleur, par
`getParameter()` d'`AbstractController`.

## Le pourcent littéral

Une référence est un texte **sans espace** entre deux pourcents :
`ParameterBag::resolveString()` cherche `%%|%([^%\s]+)%`. D'où trois cas,
exécutés :

| Valeur | Résultat |
|---|---|
| `'100%%'` | `100%` — le doublement donne un pourcent littéral |
| `'from 5% to 10% off'` | inchangée : des espaces séparent les pourcents |
| `'5%off%now'` | `ParameterNotFoundException` : « non-existent parameter "off" » |

La règle sûre reste de **doubler** tout pourcent littéral : `%%`.

## La variable d'environnement

Une valeur qui dépend de l'endroit où tourne l'application ne peut pas être
figée à la compilation. `%env(...)%` diffère sa lecture à l'exécution :

```yaml
parameters:
    app.dsn: '%env(DATABASE_URL)%'
```

C'est la différence à retenir : **un paramètre est résolu à la compilation, une
variable d'environnement est lue à l'exécution**. Exécuté : un même conteneur
compilé et généré en PHP, instancié deux fois avec deux valeurs de `DSN`, rend
les deux valeurs. Changer la variable ne demande donc pas de recompiler.

Une variable absente, sans valeur par défaut, lève une `EnvNotFoundException` —
à l'exécution, quand on la lit.

## Les processeurs

Une variable d'environnement est toujours une chaîne — exécuté, `%env(PORT)%`
rend `'6379'`. Un **processeur** la convertit :

```yaml
'%env(int:REDIS_PORT)%'
'%env(bool:FEATURE_FLAG)%'
'%env(json:CREDENTIALS)%'
'%env(csv:ALLOWED_HOSTS)%'
'%env(default:app.fallback:UNSET_VAR)%'
'%env(resolve:APP_SECRET)%'
```

Symfony 8.0 en fournit **vingt et un** — exécuté,
`EnvVarProcessor::getProvidedTypes()` en compte 21. Ceux qui reviennent : `int`,
`bool`, `not`, `json`, `csv`, `const`, `default`, `resolve` et `enum`.

`default:app.fallback:UNSET_VAR` rend le **paramètre** `app.fallback` si la
variable manque ; `default::UNSET_VAR`, sans nom, rend `null`.

Ils se **composent**, de droite à gauche : `%env(json:base64:SECRETS)%` décode
d'abord le base64, puis lit le JSON. Exécuté : cet ordre rend le tableau,
l'ordre inverse lève « Invalid JSON in env var "SECRETS" ».

## `bind` et `#[Autowire]`

Un scalaire ne se résout jamais par son type. Pour éviter de répéter
l'argument, `bind` associe un nom d'argument — ou un type — à une valeur, pour
tous les services concernés :

```yaml
services:
    _defaults:
        bind:
            string $projectDir: '%kernel.project_dir%'
```

Depuis PHP, l'attribut `#[Autowire]` fait la même chose sur un seul argument :
`#[Autowire('%kernel.debug%')]`, `#[Autowire(param: 'kernel.environment')]`,
`#[Autowire(env: 'int:REDIS_PORT')]`. Exécuté dans une application
FrameworkBundle 8.0.15 : les trois formes et le `bind` injectent leur valeur.

Un `bind` qui ne correspond à aucun argument **n'est pas ignoré** : exécuté, il
lève une `InvalidArgumentException` — « A binding is configured for an argument
… but no corresponding argument has been found. It may be unused and should be
removed, or it may have a typo. »

## Pièges d'examen

**`%param%` est résolu à la compilation**, `%env(VAR)%` à l'exécution. Une
valeur qui doit changer sans redéploiement est une variable d'environnement.

**Les processeurs se lisent de droite à gauche** : le plus proche de la
variable s'applique en premier.

**`%%` est un pourcent littéral.** Un pourcent seul ne casse rien ; deux
pourcents encadrant un mot sans espace sont lus comme une référence.

**Un `bind` inutilisé est une erreur**, pas un réglage sans effet.

## Points clés

- Paramètre : figé à la compilation, injecté par `%nom%`, doublé en `%%`.
- Variable d'environnement : lue à l'exécution par `%env(VAR)%`, toujours une
  chaîne avant processeur.
- Vingt et un processeurs, composables de droite à gauche.
- `bind` et `#[Autowire]` injectent les scalaires ; un `bind` orphelin échoue.

## Sources officielles

- [Environment Variable Processors](https://github.com/symfony/symfony-docs/blob/8.0/configuration/env_var_processors.rst)
- [`EnvVarProcessor`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/EnvVarProcessor.php)
- [`ParameterBag::resolveString()`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/ParameterBag/ParameterBag.php)
- [`ResolveBindingsPass`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Compiler/ResolveBindingsPass.php)
