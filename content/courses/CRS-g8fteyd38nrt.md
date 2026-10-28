---
id: CRS-g8fteyd38nrt
official_item: OIT-stze9x4aydp3
title: "Services registration (YAML and PHP attributes)"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-28"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/service_container.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/service_container.rst"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    symbol_or_lines: 'Automatic Service Loading — _defaults, resource, "this creates a service per class whose id is the fully-qualified class name", "order is important in this file because service definitions always *replace* previous ones"; "The _defaults section applies only to services defined in the same services block"; App::config() "Autowiring and autoconfiguration are enabled by default"'
    verified_at: "2026-10-28"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/DependencyInjection/Loader/Configurator/AppReference.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Loader/Configurator/AppReference.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: '"Services declared with the config() method below are autowired and autoconfigured by default."'
    verified_at: "2026-10-28"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/DependencyInjection/Attribute/Autoconfigure.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Attribute/Autoconfigure.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "Autoconfigure::__construct(tags, calls, bind, lazy, public, shared, autowire, properties, configurator, constructor, resourceTags)"
    verified_at: "2026-10-28"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/DependencyInjection/Compiler/AutowirePass.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Compiler/AutowirePass.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "needs an instance of %s but this type has been excluded"
    verified_at: "2026-10-28"
outcome_sections:
  OUT-bqf7brva4nwe: "Trois mécanismes : découvrir, câbler, configurer"
  OUT-j5rpdd6qsy3y: "Le cas explicite : des named arguments"
  OUT-rf33669d3w01: "Le scope de `_defaults`"
  OUT-112hac96j8hg: "Déclarer depuis la class : les attributes"
---

## Objectif

Comprendre comment une class de `src/` devient un service sans une ligne de
configuration, en séparant les trois mécanismes en jeu ; savoir déclarer un
service explicitement, connaître le scope de `_defaults` et l'effet de l'ordre
dans le file, et reconnaître les attributes qui déclarent depuis la class.

## Avant la règle : le problème à résoudre

Sur la page *Dependency Injection component*, chaque service se déclarait à la
main : `register()`, `addArgument()`, `Reference`. Une application réelle en a
des centaines. Les déclarer un par un serait interminable, et chaque nouvelle
class demanderait de toucher la configuration.

Symfony inverse le réflexe : **écrire la class suffit**. Mais cette magie
apparente repose sur trois mécanismes distincts, et la plupart des erreurs de
configuration viennent de ce qu'on les confond :

- pourquoi ma class est-elle un service alors que je ne l'ai déclarée nulle
  part ?
- pourquoi son argument `string` n'est-il pas rempli ?
- pourquoi mon subscriber est-il appelé sans que je l'aie tagué ?
- pourquoi ma configuration explicite est-elle ignorée ?

## Le fil rouge : un `Archiver` et son subscriber

Deux classes dans `src/` :

```php
namespace App\Service;

class Archiver
{
    public function __construct(
        private LoggerInterface $logger,     // un service : autowirable
        private string $directory,           // un scalaire : à fournir
    ) {
    }
}
```

```php
namespace App\EventSubscriber;

class ArchiveSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::TERMINATE => 'onTerminate'];
    }
}
```

On va voir comment la première devient un service, pourquoi son `$directory`
doit être fourni à la main, et comment la seconde devient un subscriber sans
aucun tag écrit.

## Le cycle complet en un schéma

```text
config/services.yaml
   _defaults: { autowire: true, autoconfigure: true }   ← s'applique à CE block
   App\: { resource: '../src/' }                        ← DÉCOUVERTE
   App\Service\Archiver: { arguments: { $directory: … } }   ← cas EXPLICITE (après !)
        │
        ▼
1. DÉCOUVRIR    chaque class de src/ → une definition, id = FQCN, privée
        │
        ▼
2. CÂBLER       autowire: true → chaque argument typé par un service est résolu par son type
                (LoggerInterface → logger) ; un scalaire reste à fournir
        │
        ▼
3. CONFIGURER   autoconfigure: true → une class qui implémente une interface connue reçoit
                son tag (EventSubscriberInterface → kernel.event_subscriber)
        │
        ▼
4. AFFINER      les definitions écrites PLUS BAS remplacent celles du dessus ;
                les attributes de la class (#[Autoconfigure], #[AsAlias], #[When]…) s'ajoutent
```

## Trois mécanismes : découvrir, câbler, configurer

La configuration par défaut d'un projet neuf, telle que la documente
`service_container.rst` (8.0) :

```yaml
# config/services.yaml
services:
    # default configuration for services in *this* file
    _defaults:
        autowire: true
        autoconfigure: true

    # this creates a service per class whose id is the fully-qualified class name
    App\:
        resource: '../src/'
```

Trois mécanismes s'y superposent. Les séparer est le point de départ de tout diagnostic.

| Mécanisme | Écriture | Ce qu'il fait | Ce qu'il ne fait pas |
|---|---|---|---|
| **découvrir** | `App\: { resource: '../src/' }` | une definition par class, id = FQCN | ne câble rien, ne tague rien, ne rend pas public |
| **câbler** | `autowire: true` | résout chaque argument **typé par un service** | ne devine pas un scalaire (`string $directory`) |
| **configurer** | `autoconfigure: true` | ajoute les tags d'après l'interface ou l'attribute de la class | ne crée pas de service |

Appliqué au fil rouge :

- `Archiver` est **découvert** (service `App\Service\Archiver`) ; son
  `LoggerInterface` est **câblé** ; son `string $directory` ne l'est pas : il
  faudra le fournir (named argument, `bind` ou `#[Autowire]`) ;
- `ArchiveSubscriber` est **découvert**, puis **configuré** : comme il implémente
  `EventSubscriberInterface`, il reçoit le tag du dispatcher d'events, sans une
  ligne. Même chose pour une `Command`, un `Voter`, une extension Twig.

### Une class découverte mais inutilisée

Ce n'est pas un problème. Exécuté avec FrameworkBundle 8.0.15 : une entité
placée dans `src/`, avec un argument `string` non résolu, n'empêche pas le
démarrage. Le service est **privé** et jamais référencé, il est retiré à la
compilation. Pour sortir un dossier de la découverte, une key `exclude` :

```yaml
    App\:
        resource: '../src/'
        exclude: '../src/{Entity,Kernel.php}'
```

`exclude` retire des classes de la **découverte**, pas du projet : une class
exclue mais déclarée explicitement plus bas est bien enregistrée (exécuté, avec
la visibilité demandée).

### En PHP, avec `App::config()`, les défauts changent

En 8.0, la configuration PHP peut s'écrire sous forme d'array avec
`App::config()`. La documentation et le code (`AppReference`) le disent : les
services déclarés ainsi sont **autowirés et autoconfigurés par défaut**, sans
`_defaults`. Exécuté avec DependencyInjection 8.0.15, deux déclarations sans
`_defaults` du même service qui demande une `Clock` :

| Format | `isAutowired()` | Résultat |
|---|---|---|
| `App::config([…])` en PHP | `true` | le service reçoit sa `Clock` |
| YAML, sans `_defaults` | `false` | `ArgumentCountError` à l'instanciation |

## Le cas explicite : des named arguments

Quand la découverte ne suffit pas, on déclare le service, en général **pour
compléter** la definition découverte :

```yaml
services:
    # … _defaults et App\ au-dessus …

    App\Service\Archiver:
        arguments:
            $directory: '%kernel.project_dir%/var/archive'
            $logger: '@monolog.logger.archive'
        calls:
            - setFormat: ['zip']
        tags: ['app.archiver']
```

Lecture ligne à ligne :

- `$directory` : un argument désigné par son **nom**, pas par sa position. Les
  autres arguments restent autowirés ;
- `'@monolog.logger.archive'` : le `@` désigne un **service** par son id (sans
  `@`, ce serait une string) ;
- `calls` : un appel de method après la construction (un setter) ;
- `tags` : un tag explicite, pour un mécanisme que l'autoconfiguration ne
  connaît pas.

### L'ordre du file compte

La documentation le dit dans le file par défaut : « order is important in this
file because service definitions always *replace* previous ones ». Exécuté avec
DependencyInjection 8.0.15, la même definition explicite d'`Archiver` (publique,
avec `$directory`) :

| Position de la definition explicite | Definition finale d'`Archiver` |
|---|---|
| **après** `App\: resource` | publique, `$directory` fourni |
| **avant** `App\: resource` | privée, **aucun argument** : la découverte l'a remplacée en silence |

D'où la règle de la documentation : les configurations explicites se placent
**sous** le block de découverte.

## Le scope de `_defaults`

`_defaults` ne s'applique **qu'au block où il est écrit**. La documentation
8.0 l'écrit en avertissement : « The `_defaults` section applies only to services
defined in the same `services` block. Each `when@<env>` block has its own
scope ». Chaque block `when@dev` ou `when@test` doit donc redéfinir son
`_defaults`.

Exécuté : un service déclaré dans `when@dev` sans `_defaults`, dont le
constructor attend un `LoggerInterface`, n'est pas autowiré. L'erreur n'arrive
qu'à l'instanciation, et elle ne nomme pas la cause : `ArgumentCountError` —
« Too few arguments to function …::__construct(), 0 passed ». Avec un
`_defaults: { autowire: true }` dans le block, le même service reçoit le logger.

## Déclarer depuis la class : les attributes

Plusieurs décisions se prennent désormais sur la class elle-même :

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

Pour le fil rouge, `Archiver` peut recevoir son dossier sans aucune ligne de
YAML :

```php
public function __construct(
    private LoggerInterface $logger,
    #[Autowire('%kernel.project_dir%/var/archive')] private string $directory,
) {
}
```

## Concrètement dans ton application

- Tu crées une class dans `src/` : c'est un service, privé, autowiré,
  autoconfiguré. Tu n'écris rien.
- Elle demande un scalaire : `#[Autowire]` sur l'argument, ou un `bind`, ou un
  named argument dans `services.yaml`.
- Tu dois configurer un service à la main : écris sa definition **sous**
  `App\:`, et ne répète que ce qui change (`arguments`, `calls`, `tags`).
- Tu ajoutes un block `when@test` : redéclare `_defaults` dedans.
- Une erreur `ArgumentCountError` « 0 passed » sur un service : regarde s'il
  est déclaré dans un block sans `_defaults`, ou au-dessus de `App\:`.
- Pour voir la definition réelle : `php bin/console debug:container <id>` (elle
  dit si le service est autowiré, autoconfiguré, public, et ses tags).

## À comprendre

- **Pourquoi trois mécanismes séparés ?** Parce qu'ils répondent à trois
  questions : *quelles classes* sont des services (découverte), *comment* les
  construire (câblage), *pour quel usage* (configuration par tags). On peut
  activer l'un sans l'autre.
- **Pourquoi un scalaire n'est-il jamais câblé ?** Parce que l'autowiring
  résout un **type** en service ; `string` ne désigne aucun service.
- **Pourquoi l'ordre compte-t-il ?** Parce que chaque definition remplace la
  précédente pour le même id : la découverte, si elle vient après, réécrit la
  definition explicite.
- **Pourquoi `_defaults` est-il local ?** Pour qu'un block d'environment puisse
  avoir ses propres défauts ; la contrepartie est qu'il faut les répéter.

## À mémoriser

- Projet neuf : `_defaults: { autowire: true, autoconfigure: true }` et
  `App\: { resource: '../src/' }`.
- **Découvrir** = une definition par class (id FQCN, privée) ; **câbler** =
  arguments typés par un service ; **configurer** = tags selon l'interface.
- Named argument : `$nom` ; `@id` = un service ; sans `@`, une string.
- Les definitions **se remplacent** : explicite **sous** `App\:`.
- `exclude` sort de la découverte, pas du projet.
- `_defaults` : **un block**, pas les `when@…`.
- `App::config()` en PHP : autowire et autoconfigure **par défaut**.
- `#[When]` exclut hors de son environment ; `#[Exclude]` exclut partout.
- Moyen mnémotechnique : **« D-C-C : Découvrir, Câbler, Configurer »**.

## Pièges d'examen

**Un service découvert est privé** : la découverte ne le rend pas public.

**`exclude` ne supprime pas la class** : elle l'exclut de l'enregistrement
automatique. Une class exclue mais **déclarée** explicitement est enregistrée —
exécuté, avec la visibilité demandée.

**`_defaults` ne traverse pas les blocks `when@`**, et l'oubli se paie par une
`ArgumentCountError` qui ne le mentionne pas.

**`#[When]` exclut hors de son environment** ; `#[Exclude]` exclut partout.

**Une definition explicite placée au-dessus de `App\:` est perdue.** La
découverte, écrite plus bas, la remplace en silence : le service redevient privé
et perd ses arguments. Les configurations explicites se placent dessous.

**`autowire` et `autoconfigure` sont deux réglages distincts.** Le premier
résout les arguments ; le second pose les tags. Un subscriber qui n'est pas
appelé pointe vers l'autoconfiguration, un argument vide vers l'autowiring.

**En PHP avec `App::config()`, autowire et autoconfigure sont actifs par
défaut** ; en YAML, il faut `_defaults`.

## Application : quatre scénarios

**Scénario 1 — L'argument vide.** `Archiver` est découvert, son logger arrive,
mais la compilation échoue sur `$directory`. *Diagnostic.* L'autowiring ne câble
pas un scalaire. *Correction.* `#[Autowire('%kernel.project_dir%/var/archive')]`
sur l'argument, ou un named argument `$directory` sous `App\:`.

**Scénario 2 — La configuration ignorée.** Un développeur déclare `Archiver`
avec `public: true` en tête de `services.yaml`, puis `App\:` en dessous.
`$container->get(Archiver::class)` échoue. *Diagnostic.* La découverte a
remplacé la definition. *Correction.* Déplacer la definition sous `App\:`.

**Scénario 3 — Le service de test.** Un block `when@test` déclare un faux
client HTTP qui demande un logger ; en test, `ArgumentCountError`.
*Diagnostic.* Le `_defaults` du haut ne s'applique pas à ce block.
*Correction.* `_defaults: { autowire: true }` dans `when@test`.

**Scénario 4 — Un service seulement en production.** Un `SmsNotifier` ne doit
exister qu'en `prod`. *Solution.* `#[When(env: 'prod')]` sur la class. En `dev`,
un service qui l'injecte échouera avec « this type has been excluded » : c'est
voulu, et le message est clair.

## QCM d'entraînement

Sept questions sur la déclaration des services ; décide avant de déplier.

**Question 1.** Dans un projet neuf, une class `App\Report\PdfExporter` est créée dans `src/`, sans aucune ligne de configuration. Que devient-elle ?

- **A.** Rien, tant qu'elle n'est pas déclarée dans `services.yaml`
- **B.** Un service public, récupérable par `$container->get()`
- **C.** Un service privé d'id `App\Report\PdfExporter`, autowiré et autoconfiguré
- **D.** Un service, mais ni autowiré ni autoconfiguré, faute de definition explicite

<details>
<summary>Correction</summary>

**Bonne réponse : C.** `App\: { resource: '../src/' }` crée une definition par
class, d'id le FQCN, et `_defaults` (autowire, autoconfigure) s'y applique. Comme
tout service, elle est privée.

A ignore la découverte. B est le piège : être découvert ne rend pas public. D
oublie que `_defaults` s'applique aux services découverts du même block.

</details>

**Question 2.** Un service découvert est bien construit, mais sa method `getSubscribedEvents()` n'est jamais appelée : il n'écoute aucun event. Quel réglage manque probablement ?

- **A.** `autowire: true`, qui résout les arguments du constructor
- **B.** `autoconfigure: true`, qui ajoute le tag de subscriber d'après l'interface
- **C.** `public: true`, sans lequel un subscriber est ignoré par le dispatcher d'events au démarrage
- **D.** `shared: false`, pour que le subscriber soit reconstruit à chaque event

<details>
<summary>Correction</summary>

**Bonne réponse : B.** C'est l'autoconfiguration qui, voyant
`EventSubscriberInterface`, pose le tag que le dispatcher lit.

A est le piège : l'autowiring câble les arguments, il ne pose aucun tag. C
invente une exigence de visibilité. D change la strategy d'instanciation,
sans rapport avec l'enregistrement auprès du dispatcher.

</details>

**Question 3.** `services.yaml` déclare d'abord `App\Service\Archiver: { public: true, arguments: { $directory: '/srv' } }`, puis `App\: { resource: '../src/' }`. Quelle est la definition finale d'`Archiver` ?

- **A.** Publique, avec `$directory`, car une definition explicite l'emporte toujours
- **B.** Une erreur de compilation, à cause du doublon d'identifier
- **C.** Les deux definitions fusionnées : publique, et autowirée avec `$directory`
- **D.** Privée et sans arguments, remplacée par la découverte écrite plus bas

<details>
<summary>Correction</summary>

**Bonne réponse : D.** Les definitions se remplacent dans l'ordre du file
(« service definitions always *replace* previous ones ») ; exécuté, la
découverte écrite après efface la configuration explicite.

A est le piège : rien ne fait passer une definition explicite avant les autres, seul l'ordre
compte. B invente une erreur : redéfinir un id est légal. C invente une fusion.

</details>

**Question 4.** Un block `when@dev:` déclare `App\Debug\Profiler` sans `_defaults`. Le file principal a `_defaults: { autowire: true }`. Le constructor attend un `LoggerInterface`. Que se passe-t-il en `dev` ?

- **A.** Le logger est injecté : `_defaults` vaut pour tout le file
- **B.** `ArgumentCountError` à l'instanciation, car le service n'est pas autowiré
- **C.** Le service n'est pas créé, car un block `when@` exige une definition complète
- **D.** Une erreur de compilation qui signale l'absence de `_defaults` dans le block

<details>
<summary>Correction</summary>

**Bonne réponse : B.** `_defaults` ne vaut que pour son block ; exécuté, le
service n'est pas autowiré et l'erreur, « Too few arguments … 0 passed », arrive
à l'instanciation.

A est le piège, et la raison du réglage local. C invente une exigence. D suppose
un message explicite : l'erreur réelle ne mentionne pas `_defaults`.

</details>

**Question 5.** En 8.0, `config/services.php` déclare un service avec `App::config(['services' => [Reporter::class => null]])`, sans `_defaults`. Le constructor de `Reporter` demande un autre service. Que se passe-t-il ?

- **A.** `ArgumentCountError`, faute de `_defaults` dans le file PHP
- **B.** Une erreur de syntaxe : `null` n'est pas une definition valide dans un array de services
- **C.** Le service est autowiré et autoconfiguré par défaut, et reçoit sa dependency
- **D.** Le service est autowiré mais pas autoconfiguré, sans tag ajouté

<details>
<summary>Correction</summary>

**Bonne réponse : C.** `AppReference` : « Services declared with the config()
method below are autowired and autoconfigured by default. » Exécuté :
`isAutowired()` et `isAutoconfigured()` valent `true`.

A est le piège : c'est le comportement du YAML sans `_defaults`. B est faux :
une definition `null` (ou `~` en YAML) accepte tous les défauts. D sépare deux
réglages que `App::config()` active ensemble.

</details>

**Question 6.** Une class porte `#[When(env: 'prod')]`. Un controller l'injecte, et l'application tourne en `dev`. Que se passe-t-il ?

- **A.** La compilation échoue : « needs an instance of … but this type has been excluded »
- **B.** L'injection reçoit `null` en `dev`, puisque la class n'existe qu'en environment `prod`
- **C.** La class est injectée normalement, `#[When]` ne concernant que les routes
- **D.** Le controller est retiré du routing en `dev`, faute de dependency

<details>
<summary>Correction</summary>

**Bonne réponse : A.** Exécuté : hors de son environment, la definition existe,
marquée `container.excluded`, et toute injection échoue avec ce message.

B est le piège : rien n'injecte `null` à la place d'un service exclu. C confond
avec un autre usage de l'environment. D invente une suppression de route.

</details>

**Question 7.** Dans une definition YAML, quelle écriture passe le **service** `monolog.logger.archive` à l'argument `$logger` ?

- **A.** `$logger: 'monolog.logger.archive'`
- **B.** `$logger: '%monolog.logger.archive%'`
- **C.** `$logger: 'service(monolog.logger.archive)'`
- **D.** `$logger: '@monolog.logger.archive'`

<details>
<summary>Correction</summary>

**Bonne réponse : D.** Le `@` désigne un service par son id ; la documentation
le commente : « the leading '@' tells this is a service ID, not a string ».

A passe la string. B désigne un **parameter** de ce nom. C mélange la syntaxe
du format PHP (`service('…')`) avec le YAML, où elle n'est qu'une string.

</details>

## Fiche express

**Si tu dois retenir seulement 7 choses :**

- **D-C-C** : `resource` **découvre** (id FQCN, privé), `autowire` **câble** (arguments
  typés par un service), `autoconfigure` **configure** (tags selon l'interface).
- Un **scalaire** n'est jamais câblé : named argument `$nom`, `bind` ou `#[Autowire]`.
- `@id` = un service ; sans `@`, une string ; `%nom%`, un parameter.
- Les definitions **se remplacent** : l'explicite va **sous** `App\:`.
- `_defaults` vaut pour **son block**, pas pour les `when@…`.
- `App::config()` (PHP, 8.0) : autowire et autoconfigure **par défaut**.
- `#[When]` (un environment) et `#[Exclude]` (partout) retirent une class ;
  l'injection échoue alors avec « this type has been excluded ».

**En une phrase, pour tout reconstruire :**

```text
CLASS DANS src/ → DÉCOUVERTE (service privé, id FQCN) → CÂBLAGE (types) → CONFIGURATION (tags)
  → definitions explicites PLUS BAS pour compléter · _defaults par block · attributes sur la class
```

## Sources officielles

- [Service Container, « Automatic Service Loading in services.yaml »](https://github.com/symfony/symfony-docs/blob/8.0/service_container.rst)
- [DependencyInjection 8.0, `Loader\Configurator\AppReference`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Loader/Configurator/AppReference.php)
- [DependencyInjection 8.0, `Attribute\Autoconfigure`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Attribute/Autoconfigure.php)
- [DependencyInjection 8.0, `Compiler\AutowirePass`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Compiler/AutowirePass.php)
