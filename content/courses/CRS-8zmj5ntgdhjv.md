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

## Avant la règle : le problème à résoudre

Un service a souvent besoin de valeurs qui ne sont pas des services : un
dossier, une adresse email d'administrateur, une URL, un port. On pourrait les
écrire en dur dans la class. Trois problèmes apparaissent vite :

- **la même valeur sert à plusieurs endroits** : le dossier d'archives est
  utilisé par trois services, et le changer veut dire en modifier trois ;
- **certaines valeurs changent d'un serveur à l'autre** : le DSN de la base, le
  port Redis, un secret d'API ne sont pas les mêmes en développement et en
  production, et ne doivent pas être dans le code ;
- **l'autowiring ne sait pas les deviner** : il résout un argument par son
  **type**, et un `string $dir` ne désigne aucun service.

Symfony répond par deux outils qui se ressemblent beaucoup et qui ne vivent
pas au même moment : le **parameter** (`%nom%`) et la **variable
d'environment** (`%env(NOM)%`). Tout le chapitre tient dans cette différence de
moment.

## Le fil rouge : un `Archiver`

Un service qui archive des documents. Il a besoin de trois valeurs :

```php
class Archiver
{
    public function __construct(
        private string $archiveDir,      // un dossier du projet : ne change pas d'un serveur à l'autre
        private string $storageDsn,      // le stockage distant : change selon le serveur
        private int $maxFiles,           // une limite réglée par l'exploitation, sans redéployer
    ) {
    }
}
```

On va voir quelle valeur devient un parameter, laquelle devient une variable
d'environment, comment chacune arrive dans le constructor, et ce qui casse si
l'on se trompe de syntaxe.

## Le cycle complet en un schéma

```text
CONFIGURATION (YAML ou PHP)
   app.archive_dir: '%kernel.project_dir%/var/archive'      ← PARAMETER
   storage DSN    : '%env(ARCHIVE_DSN)%'                    ← VARIABLE D'ENVIRONMENT
   max files      : '%env(int:ARCHIVE_MAX)%'                ← VARIABLE + PROCESSEUR
        │
        ▼
COMPILATION (une fois, au build du container)
   %app.archive_dir%  → remplacé par sa valeur : '/srv/app/var/archive'  (écrite en dur)
   %env(…)%           → laissé en PLACEHOLDER : rien n'est lu
   %%                 → devient un pourcent littéral
   paramètres « .xxx » → supprimés après usage
        │
        ▼
CACHE                  la class PHP du container (valeurs des parameters incluses)
        │
        ▼
EXÉCUTION (à chaque construction du service)
   %env(ARCHIVE_DSN)%     → lue dans l'environment : toujours une string
   %env(int:ARCHIVE_MAX)% → lue, puis convertie en int par le processeur
   variable absente       → EnvNotFoundException, quand le service est construit
```

## Compilation ou exécution : `%nom%` contre `%env()%`

Les deux syntaxes se ressemblent, et c'est tout le piège : l'une est résolue
une fois pour toutes quand le container est compilé, l'autre est lue à chaque
construction du service. Voici chacune, puis comment choisir.

### Le parameter : une valeur figée à la compilation

```yaml
# config/services.yaml
parameters:
    app.archive_dir: '%kernel.project_dir%/var/archive'

services:
    App\Service\Archiver:
        arguments:
            $archiveDir: '%app.archive_dir%'
```

Un parameter est une **valeur** nommée : string, nombre, booléen, array. Les
`%…%` ne sont **pas** une interpolation à l'exécution : ils sont résolus une
fois, à la compilation, et la valeur est écrite en dur dans le container PHP
généré (exécuté avec DependencyInjection 8.0.15). La documentation est
explicite : « You can only set a parameter before the container is compiled,
not at runtime. »

Un parameter peut en référencer un autre : `'%kernel.project_dir%/var/archive'`
est résolu en remplaçant `%kernel.project_dir%`. Symfony fournit plusieurs
parameters `kernel.*`, lus dans `Kernel::getKernelParameters()` (8.0) :
`kernel.project_dir`, `kernel.environment`, `kernel.debug`,
`kernel.build_dir`, `kernel.cache_dir`, `kernel.logs_dir`, `kernel.charset`,
entre autres.

Pour lire un parameter en PHP : un argument injecté (`bind`, `#[Autowire]`, plus
bas), le service `ParameterBagInterface`, ou, dans un controller qui étend
`AbstractController`, `$this->getParameter('app.archive_dir')`.

### La variable d'environment : une valeur lue à l'exécution

Le DSN du stockage n'est pas le même en développement et en production. S'il
était un parameter, il serait figé dans le container compilé, et changer de
serveur demanderait de recompiler. `%env(…)%` règle cela :

```yaml
services:
    App\Service\Archiver:
        arguments:
            $storageDsn: '%env(ARCHIVE_DSN)%'
```

La documentation décrit le mécanisme : la valeur « is **not** read at compile
time. Instead, Symfony replaces it with a unique placeholder. The actual
environment variable is only resolved at runtime, when the service using it is
instantiated. »

Exécuté avec DependencyInjection 8.0.15 :

| Situation | Résultat |
|---|---|
| un même container compilé et généré en PHP, instancié avec `PORT=6379` puis `PORT=6380` | `%env(int:PORT)%` rend `6379`, puis `6380` : **pas de recompilation** |
| `%env(PORT)%` sans processeur, `PORT=6379` | la **string** `'6379'` |
| variable `NOPE_X` jamais définie | `compile()` réussit, le container se construit, les autres services marchent ; **la construction du service qui la lit** lève `EnvNotFoundException` : « Environment variable not found: "NOPE_X". » |

### Une default value pour une variable

Un parameter nommé `env(NOM)` sert de default value quand la variable n'est
pas définie. La documentation l'utilise dans ses exemples
(`env(SECRET): 'some_secret'`). Exécuté :

```yaml
parameters:
    env(ARCHIVE_DSN): 'sqlite:///%kernel.project_dir%/var/archive.db'
```

Sans `ARCHIVE_DSN` dans l'environment, `%env(ARCHIVE_DSN)%` rend cette valeur ;
avec, la variable gagne.

### Deux variantes de parameter à connaître

Lues dans la documentation et exécutées :

- **un parameter dont le nom commence par un point** (`.mailer.transport`)
  n'existe que pendant la compilation : il peut servir à en construire un autre,
  puis il est supprimé. Après `compile()`, `getParameter('.mailer.transport')`
  lève « Parameter ".mailer.transport" not found. It was probably deleted during
  the compilation of the container. », alors que le parameter qui l'utilisait a
  bien gardé sa valeur ;
- **un parameter déclaré non vide** (`parameterCannotBeEmpty()`) ne lève son
  exception qu'**à la lecture** : `compile()` réussit, puis `getParameter()`
  échoue avec le message fourni. La documentation : « This validation is **not**
  made at compile time but when attempting to retrieve the value ».

### Choisir

| La valeur… | Écriture | Résolue |
|---|---|---|
| est propre au projet, identique sur tous les serveurs | `%app.archive_dir%` (parameter) | à la **compilation** |
| change selon le serveur, ou doit changer sans recompiler | `%env(ARCHIVE_DSN)%` | à l'**exécution**, à la construction du service |
| sert seulement à calculer d'autres parameters | `%.nom_temporaire%` | à la compilation, puis supprimée |

## Le pourcent littéral : `%%`

Les pourcents ont un sens dans une valeur de configuration. Et si l'on veut
**écrire** un pourcent ? La documentation : « If some parameter value includes
the `%` character, you need to escape it by adding another `%` ». Son exemple :
`'https://symfony.com/?foo=%%s&bar=%%d'` donne `?foo=%s&bar=%d`.

Le mécanisme est lu dans `ParameterBag::resolveString()` (8.0) : il cherche
`%%`, ou un texte **sans espace** entre deux pourcents (`%([^%\s]+)%`). D'où
quatre cas, exécutés :

| Valeur écrite | Valeur obtenue |
|---|---|
| `'100%%'` | `100%` : le doublement donne un pourcent littéral |
| `'50%'` | `50%` : un pourcent seul ne casse rien |
| `'from 5% to 10% off'` | inchangée : des espaces séparent les deux pourcents |
| `'5%off%now'` | `ParameterNotFoundException` : « The parameter "x" has a dependency on a non-existent parameter "off". » |

Le dernier cas est le piège : `%off%` ressemble exactement à une référence. La
règle sûre ne demande pas de réfléchir : **doubler tout pourcent littéral**.

## Les processeurs : convertir et composer

Une variable d'environment est **toujours une string**. Un **processeur** la
transforme, avec un préfixe dans `%env(…)%` :

```yaml
$maxFiles:   '%env(int:ARCHIVE_MAX)%'        # string → int
$enabled:    '%env(bool:ARCHIVE_ENABLED)%'   # string → bool
$options:    '%env(json:ARCHIVE_OPTIONS)%'   # JSON → array
$hosts:      '%env(csv:ALLOWED_HOSTS)%'      # "a,b,c" → array
$dsn:        '%env(default:app.fallback_dsn:ARCHIVE_DSN)%'   # parameter de secours
```

Symfony 8.0 en fournit **vingt et un**. Exécuté, `EnvVarProcessor::getProvidedTypes()`
rend : `base64`, `bool`, `not`, `const`, `csv`, `file`, `float`, `int`, `json`,
`key`, `url`, `query_string`, `resolve`, `default`, `string`, `trim`, `require`,
`enum`, `shuffle`, `defined`, `urlencode`.

### `default` : le secours

`%env(default:app.fallback_dsn:ARCHIVE_DSN)%` rend le **parameter**
`app.fallback_dsn` si la variable manque. Sans nom de parameter,
`%env(default::ARCHIVE_DSN)%` rend `null` (exécuté).

### La composition, de droite à gauche

Les processeurs s'enchaînent, et **le plus proche de la variable s'applique en
premier**. Exécuté avec une variable qui contient `{"a":1}` encodé en base64 :

```text
%env(json:base64:SECRETS)%   → base64 décodé d'abord, puis JSON lu → l'array ['a' => 1]
%env(base64:json:SECRETS)%   → JSON lu d'abord sur du base64 → « Invalid JSON in env var "SECRETS" »
```

Symfony lui-même compose ses processeurs : `Kernel` déclare
`kernel.runtime_environment` comme `'%env(default:kernel.environment:APP_RUNTIME_ENV)%'`,
c'est-à-dire la variable `APP_RUNTIME_ENV`, et à défaut le parameter
`kernel.environment`.

## `bind` et `#[Autowire]` : injecter un scalaire

L'autowiring résout par **type**. `Archiver` demande `string $archiveDir` : aucun
service ne s'appelle `string`, rien n'est deviné. Il faut dire quelle valeur
mettre, et deux mécanismes évitent de le répéter partout.

### `bind` : une règle pour tous les services

```yaml
services:
    _defaults:
        autowire: true
        bind:
            string $archiveDir: '%app.archive_dir%'
            int $maxFiles: '%env(int:ARCHIVE_MAX)%'
```

Tout named argument `$archiveDir` et typé `string`, dans **n'importe quel**
service de ce file, reçoit la valeur. Le `bind` peut viser un nom, ou un type et
un nom.

Piège exécuté : un `bind` qui ne correspond à **aucun** argument n'est pas
ignoré. La compilation échoue avec `InvalidArgumentException` : « A binding is
configured for an argument … but no corresponding argument has been found. It
may be unused and should be removed, or it may have a typo. » Une faute de
frappe dans le nom de l'argument est donc détectée.

### `#[Autowire]` : une règle sur un argument

```php
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class Archiver
{
    public function __construct(
        #[Autowire('%app.archive_dir%')] private string $archiveDir,
        #[Autowire(env: 'ARCHIVE_DSN')] private string $storageDsn,
        #[Autowire(env: 'int:ARCHIVE_MAX')] private int $maxFiles,
    ) {
    }
}
```

Trois écritures, toutes exécutées sur FrameworkBundle 8.0.15 :
`#[Autowire('%kernel.debug%')]` (une expression avec des `%`),
`#[Autowire(param: 'kernel.environment')]` (le nom du parameter, sans `%`) et
`#[Autowire(env: 'int:REDIS_PORT')]` (la variable, processeurs compris, sans
`%env()`).

| | `bind` | `#[Autowire]` |
|---|---|---|
| Où | dans la configuration (`_defaults` ou un service) | sur l'argument, dans la class |
| Portée | tous les arguments de ce nom (et type) | cet argument seulement |
| Si rien ne correspond | erreur de compilation | non applicable |

## Concrètement dans ton application

- Les valeurs propres au projet (dossiers, noms de file, limites fixes) sont
  des **parameters**, déclarés dans `config/services.yaml`.
- Les valeurs propres au serveur (DSN, secrets, URL de services externes) sont des
  **variables d'environment**, définies dans `.env`, `.env.local` ou sur le
  serveur, et référencées par `%env(…)%`.
- Pour un nombre ou un bool venant de l'environment, **un processeur**
  (`int:`, `bool:`) : sans lui, le constructor reçoit une string.
- Pour inspecter : `php bin/console debug:container --parameters` liste les
  parameters, `php bin/console debug:container --env-vars` les variables
  d'environment utilisées par le container.
- Une variable manquante ne casse **pas** le deployment : elle casse le
  premier appel qui construit le service concerné. Vérifie les variables avant
  de mettre en production.

## À comprendre

- **Pourquoi deux syntaxes ?** Parce qu'il y a deux moments. Le container est
  compilé une fois puis mis en cache : tout ce qui est figé à ce moment est
  rapide mais ne peut plus changer. Ce qui doit changer selon le serveur doit
  être lu plus tard, à l'exécution.
- **Pourquoi la variable est-elle une string ?** Parce que l'environment d'un
  process ne contient que des strings. Le type vient du processeur.
- **Pourquoi de droite à gauche ?** Parce que chaque processeur reçoit l'output
  du suivant : le plus proche de la variable lit la valeur brute.
- **Pourquoi un `bind` orphelin est-il une erreur ?** Parce qu'un `bind` inutile
  est presque toujours une faute de frappe : le laisser passer en silence
  laisserait l'argument visé sans valeur.

## À mémoriser

- `%nom%` : parameter, résolu à la **compilation**, écrit en dur dans le
  container.
- `%env(NOM)%` : variable, lue à l'**exécution**, à la construction du service ;
  toujours une **string** sans processeur.
- Variable absente : `EnvNotFoundException` à la **construction du service** ;
  `env(NOM)` en parameter donne une default value.
- Pourcent littéral : **`%%`**. Un pourcent seul passe ; `%mot%` sans espace est
  une référence.
- **21 processeurs** ; ils se composent **de droite à gauche**.
- `default:param:VAR` → le parameter de secours ; `default::VAR` → `null`.
- `bind` (configuration, tous les services) et `#[Autowire]` (un argument) ;
  un `bind` orphelin fait échouer la compilation.
- Parameter en `.nom` : supprimé après compilation.
- Moyen mnémotechnique : **« Pourcent = Parameter = Passé (compilé) ; env =
  Exécution »**.

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

## Application : quatre scénarios

**Scénario 1 — Classer les trois valeurs de l'`Archiver`.** *Solution.*
`archiveDir` : un parameter, `'%kernel.project_dir%/var/archive'`.
`storageDsn` : une variable d'environment, `%env(ARCHIVE_DSN)%`. `maxFiles` :
une variable avec processeur, `%env(int:ARCHIVE_MAX)%`, puisque l'exploitation la
règle sans redéployer et que le constructor attend un `int`.

**Scénario 2 — Une URL avec des `%`.** Une valeur
`'https://cdn.example.org/img?w=%width%'` doit garder `%width%` littéralement.
*Diagnostic.* `%width%` est lu comme une référence : « non-existent parameter
"width" ». *Correction.* Doubler : `'…?w=%%width%%'`.

**Scénario 3 — Le deployment passe, la page plante.** La production n'a pas la
variable `ARCHIVE_DSN`. Le deployment et le `cache:clear` réussissent ; le
premier appel qui construit `Archiver` lève `EnvNotFoundException`.
*Correction.* Définir la variable, ou déclarer `env(ARCHIVE_DSN)` comme default value, ou utiliser `default:`.

**Scénario 4 — La faute de frappe.** Un collègue écrit, sous `_defaults`,
`bind: { string $archiveDri: '%app.archive_dir%' }`. *Diagnostic.* Aucun
argument ne s'appelle `$archiveDri` : la compilation échoue avec « A binding is
configured for an argument … ». C'est voulu : sans cette erreur,
`$archiveDir` resterait sans valeur.

## QCM d'entraînement

Huit questions sur les valeurs de configuration. Engage-toi sur une lettre avant
d'ouvrir chaque correction.

**Question 1.** L'équipe d'exploitation doit pouvoir changer une limite de téléchargement d'un serveur à l'autre, sans reconstruire le cache. Quelle écriture convient ?

- **A.** `'%app.download_limit%'`, déclarée dans `parameters`
- **B.** `'%env(int:DOWNLOAD_LIMIT)%'`
- **C.** Une constant de class `Downloader::LIMIT`, modifiée sur chaque serveur
- **D.** `'%%DOWNLOAD_LIMIT%%'`, pour forcer une lecture à l'exécution

<details>
<summary>Correction</summary>

**Bonne réponse : B.** `%env(…)%` est lu à l'exécution, à la construction du
service : changer la variable suffit, sans recompiler (exécuté). `int:` donne
un int au lieu d'une string.

A est le piège : un parameter est figé dans le container compilé ; le changer
demande de recompiler. C modifie le code sur chaque serveur. D produit le
texte littéral `%DOWNLOAD_LIMIT%` : `%%` est un pourcent échappé, pas une
lecture d'environment.

</details>

**Question 2.** Que vaut, après compilation, le parameter `'remise de 20%% le lundi'` ?

- **A.** `remise de 20%% le lundi`, inchangé
- **B.** Une erreur : `%% le lundi` est lu comme une référence
- **C.** `remise de 20% le lundi`
- **D.** `remise de 20 le lundi`, le pourcent étant supprimé

<details>
<summary>Correction</summary>

**Bonne réponse : C.** `%%` est l'escaping documenté d'un pourcent
littéral (exécuté : `'100%%'` donne `100%`).

A suppose que l'escaping reste visible. B confond `%%` avec une référence :
une référence est un texte **sans espace** entre deux pourcents simples. D
invente une suppression.

</details>

**Question 3.** La variable `TOKEN` contient une valeur encodée en base64, qui décodée donne `{"key":"x"}`. Quelle écriture donne l'array `['key' => 'x']` ?

- **A.** `'%env(base64:json:TOKEN)%'`
- **B.** `'%env(json:base64:TOKEN)%'`
- **C.** `'%env(json:TOKEN)%'`, le base64 étant détecté automatiquement
- **D.** `'%env(base64:TOKEN)%'`, qui décode et convertit en array

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Les processeurs s'appliquent de droite à gauche :
`base64` décode d'abord, puis `json` lit le texte obtenu (exécuté).

A est le piège : il lit d'abord du JSON dans une valeur encore encodée, ce qui
lève « Invalid JSON in env var ». C invente une détection automatique. D décode
mais laisse une string JSON, pas un array.

</details>

**Question 4.** Un service lit `%env(PAYMENT_KEY)%`, et la variable n'est définie nulle part, sans default value. Quand l'erreur apparaît-elle ?

- **A.** À la compilation du container, dès que la configuration est chargée
- **B.** Jamais : la variable vaut une string vide
- **C.** Pendant `cache:clear`, quand le container est écrit dans le cache
- **D.** À la construction de ce service, avec `EnvNotFoundException`

<details>
<summary>Correction</summary>

**Bonne réponse : D.** La documentation : la variable est « only resolved at
runtime, when the service using it is instantiated ». Exécuté : `compile()`
réussit, le container se construit, les autres services marchent, et seul ce
service échoue.

A est le piège : c'est le comportement d'un **parameter** manquant, pas d'une
variable. B invente une valeur vide. C se trompe de moment : le container
compilé ne contient qu'un placeholder.

</details>

**Question 5.** La variable `WORKERS` vaut `4`. Quelle valeur reçoit un argument configuré `'%env(WORKERS)%'` ?

- **A.** La string `'4'`
- **B.** L'int `4`, puisque la valeur est numérique
- **C.** `null`, car l'argument n'a pas de processeur
- **D.** Une erreur de type, levée à la compilation

<details>
<summary>Correction</summary>

**Bonne réponse : A.** Une variable d'environment est toujours une string
avant processeur ; exécuté, `%env(PORT)%` rend `'6379'`. Pour un int :
`%env(int:WORKERS)%`.

B est le piège : aucune conversion n'est déduite du contenu. C invente une
valeur nulle. D se trompe de moment : rien n'est lu à la compilation.

</details>

**Question 6.** Sous `_defaults`, un file déclare `bind: { int $retryCount: '%app.retries%' }`, mais aucun constructor n'a d'argument `$retryCount`. Que se passe-t-il ?

- **A.** Le `bind` est ignoré en silence
- **B.** La compilation échoue : « A binding is configured for an argument … but no corresponding argument has been found »
- **C.** Tous les arguments de type `int` reçoivent `%app.retries%`
- **D.** Une deprecation est ajoutée au log de compilation, sans autre effet sur le container ni sur les services

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Lu dans `ResolveBindingsPass` et exécuté : un `bind`
orphelin est une erreur, pour attraper les fautes de frappe.

A est le piège : on imagine un réglage facultatif. C étend le `bind` à tous les
`int`, alors qu'il vise un nom précis. D invente un simple avertissement.

</details>

**Question 7.** Dans une class, quelle écriture injecte la variable `REDIS_PORT` convertie en entier ?

- **A.** `#[Autowire(param: 'int:REDIS_PORT')] int $port`
- **B.** `#[Autowire('%REDIS_PORT%')] int $port`
- **C.** `#[Autowire(service: 'env.REDIS_PORT')] int $port`
- **D.** `#[Autowire(env: 'int:REDIS_PORT')] int $port`

<details>
<summary>Correction</summary>

**Bonne réponse : D.** L'argument `env:` prend le nom de la variable,
processeurs compris, sans `%env()` (exécuté sur FrameworkBundle 8.0.15).

A utilise `param:`, qui attend un **nom de parameter**. B référence un parameter
`REDIS_PORT` qui n'existe pas : ce n'est pas une variable d'environment. C
cherche un service qui n'existe pas.

</details>

**Question 8.** Pendant la compilation, un parameter `.build.hash` sert à calculer `app.version: 'v1-%.build.hash%'`. Après `compile()`, que donnent `getParameter('app.version')` et `getParameter('.build.hash')` ?

- **A.** Les deux valeurs, car tout parameter survit à la compilation
- **B.** Deux erreurs, car un parameter en point casse aussi tous les parameters qui l'utilisaient pendant la compilation
- **C.** La valeur calculée pour `app.version`, et une erreur « not found » pour `.build.hash`
- **D.** Une erreur pour `app.version`, et la valeur de `.build.hash`

<details>
<summary>Correction</summary>

**Bonne réponse : C.** La documentation : les parameters dont le nom commence
par un point « are available only during the container compilation ».
Exécuté : le parameter qui l'utilisait garde sa valeur résolue, le parameter en
point est supprimé (« It was probably deleted during the compilation of the
container. »).

A ignore la convention du point. B suppose que la suppression arrive avant la
résolution : elle arrive après. D inverse les deux résultats.

</details>

## Fiche express

**Si tu dois retenir seulement 7 choses :**

- **`%nom%` = compilation** (écrit en dur dans le container) ; **`%env(NOM)%` =
  exécution** (lu à la construction du service).
- Une variable est **toujours une string** sans processeur ; absente, elle lève
  `EnvNotFoundException` à la construction du service, pas à la compilation.
- `env(NOM)` déclaré en parameter = **default value** de la variable.
- Pourcent littéral : **`%%`** ; un pourcent seul passe ; `%mot%` est une
  référence.
- **21 processeurs**, composés **de droite à gauche** (`json:base64:VAR`) ;
  `default:param:VAR`, `default::VAR` → `null`.
- `bind` (configuration, tous les services ; orphelin = erreur) et
  `#[Autowire]` (un argument : expression, `param:`, `env:`).
- Parameter en **`.nom`** : supprimé après compilation.

**En une phrase, pour tout reconstruire :**

```text
VALEUR DU PROJET → %parameter% → figée à la COMPILATION (%% = pourcent littéral)
VALEUR DU SERVEUR → %env(processeurs:VAR)% → lue à l'EXÉCUTION, string → processeurs de droite à gauche
INJECTION D'UN SCALAIRE → bind (tous) ou #[Autowire] (un argument)
```

## Sources officielles

- [Configuring Symfony, « Configuration Parameters » et variables d'environment](https://github.com/symfony/symfony-docs/blob/8.0/configuration.rst)
- [Environment Variable Processors](https://github.com/symfony/symfony-docs/blob/8.0/configuration/env_var_processors.rst)
- [Service Container](https://github.com/symfony/symfony-docs/blob/8.0/service_container.rst)
- [`EnvVarProcessor`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/EnvVarProcessor.php)
- [`ParameterBag::resolveString()`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/ParameterBag/ParameterBag.php)
- [`ResolveBindingsPass`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Compiler/ResolveBindingsPass.php)
- [`Kernel::getKernelParameters()`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/Kernel.php)
