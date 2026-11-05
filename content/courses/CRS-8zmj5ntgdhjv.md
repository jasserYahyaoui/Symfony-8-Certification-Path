---
id: CRS-8zmj5ntgdhjv
official_item: OIT-ar4h3zfskjsp
title: "Configuration parameters"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-28"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/configuration.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/configuration.rst"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    symbol_or_lines: 'Configuration Parameters — "escape it by adding another %"; parameters starting with a dot "are available only during the container compilation"; parameterCannotBeEmpty() "not made at compile time but when attempting to retrieve the value"; env vars "the value is not read at compile time … only resolved at runtime, when the service using it is instantiated"; getParameter()'
    verified_at: "2026-10-28"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/configuration/env_var_processors.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/configuration/env_var_processors.rst"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    symbol_or_lines: "Environment Variable Processors — env(string:FOO), env(int:FOO), env(json:FOO), env(default:…), parameters: env(SECRET): 'some_secret'"
    verified_at: "2026-10-28"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/service_container.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/service_container.rst"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    symbol_or_lines: '"You can only set a parameter before the container is compiled, not at runtime."'
    verified_at: "2026-10-28"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/DependencyInjection/EnvVarProcessor.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/EnvVarProcessor.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "EnvVarProcessor::getProvidedTypes() — 21 prefixes"
    verified_at: "2026-10-28"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/DependencyInjection/ParameterBag/ParameterBag.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/ParameterBag/ParameterBag.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "ParameterBag::resolveString(), pattern %%|%([^%\\s]+)%"
    verified_at: "2026-10-28"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/DependencyInjection/Compiler/ResolveBindingsPass.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Compiler/ResolveBindingsPass.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "A binding is configured for an argument %s, but no corresponding argument has been found"
    verified_at: "2026-10-28"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpKernel/Kernel.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/Kernel.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "getKernelParameters() — kernel.project_dir, kernel.environment, kernel.debug, kernel.build_dir, kernel.cache_dir, kernel.logs_dir, kernel.charset, kernel.runtime_environment '%env(default:kernel.environment:APP_RUNTIME_ENV)%'"
    verified_at: "2026-10-28"
outcome_sections:
  OUT-b81c2y9edqrn: "Compilation ou exécution : `%nom%` contre `%env()%`"
  OUT-q6ks7q0n73de: "Le pourcent littéral : `%%`"
  OUT-eatwchm2rs41: "Les processeurs : convertir et composer"
  OUT-ddcr84rvx9ap: "`bind` et `#[Autowire]` : injecter un scalaire"
---

## Objectif

Déclarer une valeur de configuration et l'injecter, en choisissant entre un
parameter, figé à la compilation, et une variable d'environment, lue à
l'exécution ; écrire un pourcent littéral, composer des processeurs dans le bon
ordre, et éviter de répéter un scalaire avec un bind ou un attribute. Le choix
de *quelle* valeur mérite un parameter relève d'*Official best practices*.

## L'essentiel

`%nom%` désigne un **parameter**, résolu à la **compilation** et écrit en dur
dans le container. `%env(NOM)%` désigne une **variable d'environment**, lue à
l'**exécution**, quand le service est construit — toujours une string, sauf
processeur. Un pourcent littéral se double. Et `bind` ou `#[Autowire]`
injectent un scalaire sans le répéter.

## Le schéma

```text
configuration   '%kernel.project_dir%/var/archive'  |  '%env(ARCHIVE_DSN)%'  |  '%env(int:ARCHIVE_MAX)%'
compilation     %param% → valeur écrite en dur  |  %env()% → placeholder  |  %% → %  |  .param → supprimé
exécution       à la construction du service : variable lue (string), convertie par son processeur ;
                variable absente → EnvNotFoundException, pour ce service seulement
```

## Le fil rouge : un `Archiver`

```php
class Archiver
{
    public function __construct(
        private string $archiveDir,      // un dossier du projet : un parameter
        private string $storageDsn,      // change selon le serveur : une variable d'environment
        private int $maxFiles,           // réglé par l'exploitation : variable + processeur int
    ) {
    }
}
```

## Compilation ou exécution : `%nom%` contre `%env()%`

| La valeur… | Écriture | Résolue |
|---|---|---|
| est propre au projet | `%app.archive_dir%` | à la **compilation** |
| change selon le serveur, sans recompiler | `%env(ARCHIVE_DSN)%` | à l'**exécution** |
| sert seulement à en calculer d'autres | `%.nom_temporaire%` | à la compilation, puis supprimée |

- « You can only set a parameter before the container is compiled. » Les
  parameters `kernel.*` : `kernel.project_dir`, `kernel.environment`,
  `kernel.debug`, `kernel.cache_dir`…
- Le même container compilé, instancié avec `PORT=6379` puis `6380`, lit
  `6379` puis `6380` : pas de recompilation.
- Variable jamais définie : `compile()` réussit ; la **construction du service
  qui la lit** lève `EnvNotFoundException`. Un parameter `env(NOM)` sert de
  default value.
- Un parameter `.build.hash` n'existe que pendant la compilation ; ensuite :
  « It was probably deleted during the compilation of the container. »
- `parameterCannotBeEmpty()` ne vérifie qu'**à la lecture**.

## Le pourcent littéral : `%%`

Un `%` littéral se **double**. Est une référence : `%%`, ou un texte **sans
espace** entre deux pourcents.

| Valeur écrite | Valeur obtenue |
|---|---|
| `'100%%'` | `100%` |
| `'50%'`, `'from 5% to 10% off'` | inchangées |
| `'5%off%now'` | `ParameterNotFoundException` : « …has a dependency on a non-existent parameter "off". » |

## Les processeurs : convertir et composer

```yaml
$maxFiles: '%env(int:ARCHIVE_MAX)%'                              # string → int
$options:  '%env(json:ARCHIVE_OPTIONS)%'                         # JSON → array
$dsn:      '%env(default:app.fallback_dsn:ARCHIVE_DSN)%'         # parameter de secours
```

- Symfony 8.0 en fournit **vingt et un** (`bool`, `int`, `json`, `csv`,
  `base64`, `default`, `enum`, `trim`…).
- Ils se composent **de droite à gauche** : `%env(json:base64:SECRETS)%`
  décode le base64, puis lit le JSON.
- `default::VAR`, sans parameter, rend `null` si la variable manque.
- `kernel.runtime_environment` vaut
  `%env(default:kernel.environment:APP_RUNTIME_ENV)%`.

## `bind` et `#[Autowire]` : injecter un scalaire

L'autowiring résout par type ; un `string` ne se devine pas.

```yaml
services:
    _defaults:
        bind:
            string $archiveDir: '%app.archive_dir%'
```

```php
public function __construct(
    #[Autowire('%app.archive_dir%')] private string $archiveDir,
    #[Autowire(env: 'int:ARCHIVE_MAX')] private int $maxFiles,
    #[Autowire(param: 'kernel.environment')] private string $env,
) {}
```

- `bind` vaut pour tous les arguments de ce nom (et type) du file ;
  `#[Autowire]`, pour cet argument.
- Un `bind` qui ne correspond à **aucun** argument fait échouer la compilation
  (`ResolveBindingsPass`) : une faute de frappe est détectée.

## Pièges d'examen

**`%param%` est résolu à la compilation**, `%env(VAR)%` à l'exécution. Une
valeur qui doit changer sans redéploiement est une variable d'environment.

**Les processeurs se lisent de droite à gauche** : le plus proche de la
variable s'applique en premier.

**`%%` est un pourcent littéral.** Un pourcent seul ne casse rien ; deux
pourcents encadrant un mot sans espace sont lus comme une référence.

**Un `bind` inutilisé est une erreur**, pas un réglage sans effet.

**Une variable d'environment manquante ne casse pas la compilation.** Elle
échoue seulement quand le service qui la lit est construit, avec
`EnvNotFoundException` ; les autres services fonctionnent.

**Sans processeur, une variable d'environment est une string**, même quand elle
contient un nombre : `%env(PORT)%` rend `'6379'`, il faut `int:` pour un int.

**Un parameter dont le nom commence par un point disparaît après la
compilation.** Il sert à calculer d'autres valeurs ; le demander ensuite lève
« Parameter … not found ».

## Application

- Le DSN du stockage diffère entre serveurs → `%env(ARCHIVE_DSN)%`, pas un
  parameter.
- Une remise s'écrit `'20% le lundi'` sans souci, mais `'5%off%now'` casse →
  doubler tout pourcent littéral.

## QCM d'entraînement

**Question 1.** Dans une class, quelle écriture injecte la variable
`REDIS_PORT` convertie en entier ?

- **A.** `#[Autowire(param: 'int:REDIS_PORT')] int $port`
- **B.** `#[Autowire('%REDIS_PORT%')] int $port`
- **C.** `#[Autowire(service: 'env.REDIS_PORT')] int $port`
- **D.** `#[Autowire(env: 'int:REDIS_PORT')] int $port`

<details>
<summary>Correction</summary>

**Bonne réponse : D.** `env:` prend la variable avec ses processeurs, sans
`%env()` ; `param:` attend un nom de parameter, et B désigne un parameter.

</details>

**Question 2.** Un parameter `.build.hash` sert à calculer
`app.version: 'v1-%.build.hash%'`. Après `compile()`, que donnent les deux
`getParameter()` ?

- **A.** Les deux valeurs : tout parameter survit à la compilation
- **B.** Deux erreurs : le parameter en point casse ceux qui l'utilisaient
- **C.** `app.version` répond ; `.build.hash` est « not found »
- **D.** Une erreur pour `app.version`, et la valeur de `.build.hash`

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Un parameter en point n'existe que pendant la
compilation ; celui qu'il a servi à calculer garde sa valeur.

</details>

**Question 3.** Que donne le parameter `'5%off%now'` ?

- **A.** `ParameterNotFoundException`
- **B.** La valeur inchangée, `5%off%now`
- **C.** `5now`, la référence vide étant retirée
- **D.** `5%now`

<details>
<summary>Correction</summary>

**Bonne réponse : A.** `%off%` est un texte sans espace entre deux pourcents :
une référence vers un parameter `off`, qui n'existe pas.

</details>

**Question 4.** `ARCHIVE_DSN` n'est défini nulle part. Que reçoit un argument
configuré `'%env(default::ARCHIVE_DSN)%'` ?

- **A.** Une string vide
- **B.** `null`
- **C.** Une `EnvNotFoundException` à la construction du service
- **D.** La string `'default'`

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Sans nom de parameter de secours, `default` rend `null`
au lieu de lever l'exception de C.

</details>

## Fiche express

**Si tu dois retenir seulement 7 choses :**

- **`%nom%`** : compilation, écrit en dur ; **`%env()%`** : exécution.
- Variable absente : **`EnvNotFoundException`** à la construction du service.
- Sans processeur : **une string**.
- **`%%`** = un pourcent ; `%mot%` sans espace = une référence.
- **21 processeurs**, composés **de droite à gauche** ; `default::VAR` → `null`.
- **`bind`** (le file) et **`#[Autowire]`** (l'argument) ; un `bind` orphelin
  casse la compilation.
- Parameter **`.nom`** : supprimé après la compilation.

Moyen mnémotechnique :
**« Pourcent = Parameter = Passé (compilé) ; env = Exécution »**.

## Sources officielles

- [Configuring Symfony, « Configuration Parameters » et variables d'environment](https://github.com/symfony/symfony-docs/blob/8.0/configuration.rst)
- [Environment Variable Processors](https://github.com/symfony/symfony-docs/blob/8.0/configuration/env_var_processors.rst)
- [Service Container](https://github.com/symfony/symfony-docs/blob/8.0/service_container.rst)
- [`EnvVarProcessor`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/EnvVarProcessor.php)
- [`ParameterBag::resolveString()`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/ParameterBag/ParameterBag.php)
- [`ResolveBindingsPass`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Compiler/ResolveBindingsPass.php)
- [`Kernel::getKernelParameters()`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/Kernel.php)

