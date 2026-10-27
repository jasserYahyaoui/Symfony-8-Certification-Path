---
id: CRS-p1694d5f7r8c
official_item: OIT-79f4n087b66c
title: "Service container"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-27"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/service_container.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/service_container.rst"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    symbol_or_lines: 'Fetching and using Services — "if you never ask for the service, it''s *never* constructed"; "the same instance is returned each time you ask for it"; Public Versus Private Services — "Every service defined is private by default"'
    verified_at: "2026-10-27"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/service_container/alias_private.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/service_container/alias_private.rst"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    symbol_or_lines: 'Marking Services as Public / Private — #[Autoconfigure(public: true)]; "Private services are special because they allow the container to optimize"; Aliasing — alias, public alias, shortcut app.mailer: ''@App\Mail\PhpMailer'', #[AsAlias]'
    verified_at: "2026-10-27"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/service_container/autowiring.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/service_container/autowiring.rst"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    symbol_or_lines: 'Working with Interfaces — "To fix that, add an alias"; tip "if only one service is discovered that implements an interface, configuring the alias is not mandatory and Symfony will automatically create one"'
    verified_at: "2026-10-27"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/testing.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/testing.rst"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    symbol_or_lines: '"The container from static::getContainer() is actually a special test container. It gives you access to both the public services and the non-removed private services"; config/services_test.yaml'
    verified_at: "2026-10-27"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpKernel/Kernel.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/Kernel.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "getContainerClass() — kernel class with _ + environment + Debug + Container; initializeContainer() — includes the cached class, checks freshness only in debug"
    verified_at: "2026-10-27"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/Kernel/MicroKernelTrait.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Kernel/MicroKernelTrait.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "configureContainer() — imports services.yaml, then {services}_<environment>.yaml"
    verified_at: "2026-10-27"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/Command/ContainerDebugCommand.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Command/ContainerDebugCommand.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "configure() — argument name; options show-hidden, tag, tags, parameter, parameters, types, env-var, env-vars, format, raw, deprecations; no show-private"
    verified_at: "2026-10-27"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/Console/Descriptor/TextDescriptor.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Console/Descriptor/TextDescriptor.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: 'describeContainerServices() — title "Symfony Container Hidden Services"; "if ($showHidden xor ''.'' === ($serviceId[0] ?? null))" filters out the other services'
    verified_at: "2026-10-27"
outcome_sections:
  OUT-4mx2cr2ydxtf: "Ce que le container contient : des definitions"
  OUT-rx9sxdh2gjgm: "Public ou privé : trois questions"
  OUT-21rjj7sevj21: "L'alias : un second identifier"
  OUT-8hr9dpyd6r1k: "La compilation : pourquoi la configuration n'est pas relue"
---

## Objectif

Savoir ce que le container contient et quand il construit un object, ce que
public et privé changent, à quoi sert un alias, et pourquoi une modification de
configuration reste sans effet tant que le container n'est pas recompilé. La
déclaration des services elle-même est traitée dans *Services registration*.

## Avant la règle : le problème à résoudre

Sur la page *Dependency Injection component*, tu as construit un container à la
main : quelques `register()`, un `compile()`, un `get()`. Dans une vraie
application Symfony, le container contient plusieurs centaines de services :
les tiens, ceux du framework, ceux de chaque bundle. Personne ne les déclare un
par un, et personne ne les construit tous à chaque appel.

Quatre questions se posent alors, et ce sont exactement celles de l'examen.

1. **Que contient vraiment le container ?** Des objects tout prêts ? Si c'était
   le cas, chaque appel HTTP construirait des centaines d'objects dont il
   n'utilise qu'une poignée.
2. **Qui a le droit de demander quoi ?** Si n'importe quel code pouvait
   récupérer n'importe quel service par son nom, on retomberait dans la
   récupération de services que la dependency injection veut éviter.
3. **Comment injecter une interface ?** Le container range ses services par
   identifier, souvent le nom d'une class. Une interface n'est pas une class
   concrète : à quel service correspond-elle ?
4. **Pourquoi ma modification ne marche pas ?** Tu changes `services.yaml` en
   production, et rien ne change.

Chaque question a sa solution précise, et chacune devient un réflexe de
diagnostic.

## Le fil rouge : `PhpMailer`, `MailerInterface` et la `Newsletter`

Trois classes de `src/`, enregistrées automatiquement comme services (la
configuration par défaut d'un projet) :

```php
namespace App\Mail;

interface MailerInterface
{
}

class PhpMailer implements MailerInterface
{
}

class Newsletter
{
    public function __construct(
        private MailerInterface $mailer,
    ) {
    }
}
```

La `Newsletter` demande une `MailerInterface`, et c'est `PhpMailer` qui doit
arriver. On va suivre ces trois classes de la configuration jusqu'à
l'exécution : ce que le container en garde, qui peut les obtenir, comment
l'interface trouve son implémentation, et ce que devient tout cela une fois
compilé.

## Le cycle complet en un schéma

```text
CONFIGURATION            config/services.yaml (+ services_<env>.yaml, + bundles)
   │                     App\ : resource 'src/'  → une definition par class
   ▼
DEFINITIONS              App\Mail\PhpMailer       class, arguments, tags… privée
(rien n'est construit)   App\Mail\Newsletter      argument : MailerInterface   privée
                         App\Mail\MailerInterface → alias vers PhpMailer       privé
   │
   ▼
COMPILATION              compiler passes : autowiring, alias résolus,
(une fois)               services privés retirés ou inlinés, tout est figé
   │
   ▼
CACHE                    var/cache/<env>/App_KernelProdContainer.php
                         une class PHP : la configuration n'est plus relue
   │
   ▼
EXÉCUTION                premier usage de Newsletter → construit PhpMailer,
                         puis Newsletter ; usage suivant → les mêmes instances
```

Chaque étage correspond à une section : les definitions (ce que contient le
container), la visibilité (qui peut demander), l'alias (comment l'interface
trouve `PhpMailer`), la compilation et le cache (pourquoi la configuration
n'est pas relue).

## Ce que le container contient : des definitions

Le container ne stocke pas des objects : il stocke des **definitions**. Une
definition est le **plan de construction** d'un service : sa class, ses arguments, les
methods à appeler, ses tags, sa visibilité, s'il est partagé, s'il est
autowiré.

L'object n'est construit **qu'au moment où l'on en a besoin**. La
documentation le dit : quand tu demandes le service, le container construit
l'object et te le rend, « But if you never ask for the service, it's *never*
constructed: saving memory and speed. »

Exécuté avec DependencyInjection 8.0.15, en comptant les constructions de
`PhpMailer` :

| Moment | Nombre d'instances de `PhpMailer` construites |
|---|---|
| après le chargement de la configuration | 0 |
| après `compile()` | **0** : compiler ne construit rien |
| après le premier usage de `Newsletter` | 1 |
| après un second usage | **toujours 1** |

La dernière ligne est la deuxième règle : un service est **partagé** par
défaut. La documentation : « the same instance is returned each time you ask
for it ». Deux services qui reçoivent tous deux `PhpMailer` reçoivent le même
object. Pour obtenir une instance neuve à chaque fois, on déclare le service
`shared: false` (`setShared(false)` avec le component) : exécuté, deux `get()`
donnent alors deux objects différents.

### L'identifier d'un service

Chaque definition a un **identifier**. Avec l'enregistrement automatique de
`src/`, la documentation précise que « the `id` of each service is its
fully-qualified class name » : le service `PhpMailer` s'appelle
`App\Mail\PhpMailer`. C'est ce qui rend l'autowiring possible : un argument typé
`PhpMailer` désigne directement le service du même nom.

Un identifier peut aussi être un nom court (`app.mailer`), utile quand une même
class sert à plusieurs services configurés différemment.

## Public ou privé : trois questions

**Privé est le défaut.** La documentation : « Every service defined is private
by default. » Exécuté : une definition créée par `register()` a `isPublic()` à
`false`, et un alias aussi.

Pour comprendre ce que « privé » change, pose trois questions :

| Question | Service privé | Service public |
|---|---|---|
| Peut-on l'**injecter** dans un autre service ? | **oui** | oui |
| Peut-on le **récupérer** par `$container->get('id')` une fois compilé ? | **non** | oui |
| Le compiler peut-il le **retirer** (inutilisé) ou l'**inliner** (utilisé une fois) ? | **oui** | non |

La première ligne est celle qui piège : privé ne veut **pas** dire « caché aux
autres services ». Privé concerne uniquement l'accès **depuis l'extérieur** du
container, par `get()`. Toute l'injection fonctionne.

Exécuté sur le fil rouge, après compilation :

```text
$container->get(Newsletter::class)          → l'object, avec son PhpMailer injecté (Newsletter rendue publique)
$container->get(PhpMailer::class)           → ServiceNotFoundException :
    The "App\Mail\PhpMailer" service or alias has been removed or inlined when the container
    was compiled. You should either make it public, or stop using the container directly
    and use dependency injection instead.
$container->has(PhpMailer::class)           → false
```

### Pourquoi privé par défaut

La documentation donne deux raisons, et elles se retiennent ensemble :

- **performance** : un service privé permet au container d'optimiser *si* et
  *comment* il est instancié (le retirer s'il ne sert à rien, le construire
  directement là où il sert) ;
- **meilleures erreurs** : la documentation promet une erreur claire « when you
  refresh *any* page, even if the problematic code would not have run on that
  page ».

Attention au mécanisme exact de la seconde raison, parce qu'il est facile de le
résumer de travers. Ce n'est pas la visibilité qui provoque l'erreur : **toute**
référence vers un service inexistant fait échouer la compilation, que le
service qui la porte soit public ou privé (exécuté sur la page *Dependency
Injection component*). Ce que le privé apporte, c'est d'interdire `get()` : un
service privé ne peut être obtenu **que par injection**, donc par une référence
que la compilation vérifie. Un `get('id')` mal tapé, lui, n'échouerait qu'au
moment où sa ligne s'exécute. Le défaut privé pousse donc vers la bonne
pratique, et c'est elle qui fait sortir les erreurs tôt.

### Rendre un service public

Trois façons, toutes dans la documentation :

```yaml
# config/services.yaml
services:
    App\Mail\Newsletter:
        public: true
```

```php
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(public: true)]
class Newsletter
{
}
```

Ou un **alias public** vers un service privé. La documentation : « if a service
has been marked as private, you can still alias it (see below) to access this
service (via the alias) ». On le voit dans la section suivante.

### Le cas des tests

Dans un functional test, `static::getContainer()` ne rend pas le container
ordinaire. La documentation : « a special test container. It gives you access
to both the public services and the non-removed private services ». Exécuté
avec FrameworkBundle 8.0.15 : ce container de test est un
`Symfony\Bundle\FrameworkBundle\Test\TestContainer` ; un service privé utilisé
par un autre service y est accessible, un service privé que rien n'utilise a
été retiré et lève toujours `ServiceNotFoundException`.

Pour tester un service privé retiré, la documentation conseille de le déclarer
public **uniquement en test**, dans `config/services_test.yaml`.

## L'alias : un second identifier

Un **alias** est un identifier qui pointe vers un autre service. Il ne crée
aucun object : demander l'alias, c'est obtenir le service qu'il désigne.
Exécuté : l'object obtenu par l'alias et celui injecté ailleurs sont la
**même** instance.

Deux écritures YAML, données par la documentation :

```yaml
services:
    # forme complète : l'alias peut avoir sa propre visibilité
    app.mailer:
        alias: App\Mail\PhpMailer
        public: true

    # raccourci : un @ suivi de l'identifier visé
    app.mailer_short: '@App\Mail\PhpMailer'
```

Et une écriture par attribute, sur la class elle-même :

```php
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(id: 'app.mailer', public: true)]
class PhpMailer implements MailerInterface
{
}
```

Un alias est **privé par défaut**, comme un service (exécuté :
`getAlias('app.mailer_short')->isPublic()` vaut `false`). Exécuté sur le fil
rouge : `get('app.mailer')` (alias public) rend `PhpMailer` alors que
`get(PhpMailer::class)` échoue ; `get('app.mailer_short')` (alias privé) échoue
aussi.

### À quoi sert vraiment l'alias : rendre une interface injectable

Voici le cas qui compte à l'examen. La `Newsletter` demande une
`MailerInterface`. Or aucun service ne s'appelle `App\Mail\MailerInterface` :
les services s'appellent par leur class concrète. La documentation le formule
ainsi : le type demandé « no longer matches the id of the service (…). This means
that the argument can no longer be autowired. To fix that, add an alias ».

```yaml
services:
    App\Mail\MailerInterface: '@App\Mail\PhpMailer'
```

L'alias porte le nom de l'interface et désigne l'implémentation. L'autowiring
cherche un service nommé comme le type de l'argument, trouve l'alias, et
injecte `PhpMailer`.

**Le cas d'une seule implémentation.** La documentation ajoute que, avec
l'enregistrement automatique de `src/`, si une **seule** class découverte
implémente l'interface, l'alias n'est pas obligatoire : Symfony le crée tout
seul. Exécuté avec DependencyInjection 8.0.15 :

| Classes de `src/` qui implémentent `MailerInterface` | Alias créé automatiquement ? | Résultat de la compilation |
|---|---|---|
| une seule (`PhpMailer`) | **oui** | la `Newsletter` reçoit `PhpMailer` |
| deux (`PhpMailer` et `SmtpMailer`) | **non** | échec : « Cannot autowire service "…\Newsletter": argument "$mailer" of method "__construct()" references interface "…\MailerInterface" but no such service exists. You should maybe alias this interface to one of these existing services: "…\PhpMailer", "…\SmtpMailer". » |

Lis bien le message de la seconde ligne : il te donne lui-même la solution,
déclarer l'alias, et la liste des candidats. C'est le diagnostic type de
l'examen : « une deuxième implémentation a été ajoutée et l'application ne
compile plus ».

## La compilation : pourquoi la configuration n'est pas relue

Le container que ton application utilise n'est **pas** construit à partir de
`services.yaml` à chaque appel. Il est **compilé une fois**, puis écrit sous
forme d'une **class PHP** dans le cache. Lu dans `Kernel` 8.0 et exécuté avec
un kernel minimal sur FrameworkBundle 8.0.15 :

| Kernel | Class du container | File écrit dans le cache |
|---|---|---|
| `App\Kernel`, environment `prod`, debug off | `App_KernelProdContainer` | `var/cache/prod/App_KernelProdContainer.php` (et un `.preload.php`) |
| `App\Kernel`, environment `test`, debug on | `App_KernelTestDebugContainer` | `var/cache/test/…` |

Le nom suit une règle simple, lue dans `getContainerClass()` : la class du
kernel (les `\` remplacés par `_`), puis l'environment, puis `Debug` si le
mode debug est actif, puis `Container`.

À chaque appel suivant, le kernel **inclut** ce file PHP : aucun YAML n'est
relu, aucune compiler pass ne tourne.

### Debug ou pas : la différence qui décide du diagnostic

Lu dans `Kernel::initializeContainer()` :

- **debug actif** (en général `dev` et `test`) : avant d'inclure le container en
  cache, le kernel vérifie qu'il est **à jour** par rapport à ses sources
  (files de configuration, classes) ; si une source a changé, il recompile.
  Modifier `services.yaml` en développement suffit donc ;
- **debug inactif** (en général `prod`) : le kernel inclut le file **sans
  vérifier** sa fraîcheur. Modifier `services.yaml` n'a **aucun effet** tant que
  le cache n'est pas vidé.

```bash
php bin/console cache:clear
```

### Le file d'environment s'ajoute

Lu dans `MicroKernelTrait::configureContainer()` : le kernel importe
`config/services.yaml`, **puis** `config/services_<env>.yaml` s'il existe
(`services_test.yaml` en test, `services_prod.yaml` en production). Le file
d'environment **s'ajoute** au file principal et peut redéfinir un service ; il
ne le remplace pas. C'est ce qui permet de rendre un service public seulement
en test.

## Inspecter le container

Le container compilé se lit avec deux commands de FrameworkBundle.

```bash
php bin/console debug:container                       # les services, privés compris
php bin/console debug:container Mailer                # recherche partielle sur l'identifier
php bin/console debug:container --show-hidden         # UNIQUEMENT les services internes
php bin/console debug:container --tag=kernel.event_listener
php bin/console debug:container --parameters
php bin/console debug:autowiring Mailer               # les types utilisables pour l'autowiring
```

Trois détails à connaître, chacun lu dans le code 8.0 et exécuté sur un kernel
`dev` :

- **les services privés sont listés par défaut** : il n'existe **pas**
  d'option `--show-private` (exécuté : « The "--show-private" option does not
  exist. ») ;
- un service **interne** (ou *hidden*) est un service dont l'identifier
  commence par un point, comme `.console.command.about.lazy` ; ils sont
  **masqués** par défaut ;
- **`--show-hidden` n'ajoute pas les services internes à la liste : il
  n'affiche qu'eux.** Le titre devient « Symfony Container Hidden Services », et
  les services ordinaires disparaissent (exécuté : 395 services sans l'option,
  79 avec, tous commençant par un point).

`debug:autowiring` répond à une autre question : non pas « quels services
existent », mais « quel **type** puis-je écrire dans un constructor pour être
autowiré ». Exécuté avec l'alias du fil rouge : par défaut, il n'affiche que
les alias (`App\Mail\MailerInterface - alias:App\Mail\PhpMailer`) ; l'option
`--all` ajoute les services concrets, comme `App\Mail\PhpMailer`. Il conseille
lui-même de typer par interface.

## Concrètement dans ton application

- Tes classes de `src/` deviennent des services **privés**, nommés par leur
  FQCN, autowirés : c'est la configuration par défaut. Tu n'as rien à
  déclarer pour les cas simples.
- Tu ne fais pas `$container->get()` : tu **injectes**. Si un `get()` lève
  « removed or inlined », la solution est presque toujours d'injecter, pas de
  rendre public.
- Tu types tes arguments par **interface**. Tant qu'une seule class l'implémente,
  l'alias est automatique ; le jour où tu en ajoutes une seconde, la
  compilation échoue et le message te dit quel alias déclarer.
- Après un deployment qui touche la configuration, le cache est **vidé**. En
  développement, le kernel recompile tout seul.
- Pour comprendre ce qui est réellement câblé, `debug:container` et
  `debug:autowiring` lisent le container compilé : ils disent la vérité,
  même quand la configuration semble dire autre chose.
- Pour un test qui a besoin d'un service privé retiré, un
  `config/services_test.yaml` qui le rend public, et jamais une modification
  de `services.yaml`.

## À comprendre

- **Pourquoi des definitions plutôt que des objects ?** Parce qu'un appel
  n'utilise qu'une poignée des centaines de services : les construire tous
  serait du temps et de la mémoire perdus. La definition coûte presque rien,
  l'object n'est payé que s'il sert.
- **Pourquoi privé par défaut ?** Parce qu'un service que personne ne peut
  demander de l'extérieur peut être optimisé ; et comme on ne l'obtient que
  par injection, chacun de ses usages passe par une référence que la
  compilation vérifie.
- **Pourquoi un alias pour une interface ?** Parce que l'autowiring cherche un
  service **nommé** comme le type demandé, et qu'aucun service ne porte le nom
  d'une interface. L'alias est ce nom.
- **Pourquoi la configuration n'est-elle pas relue ?** Parce que le container
  est compilé une fois en PHP : c'est ce qui le rend rapide. La contrepartie est
  que toute modification passe par une recompilation, automatique en debug,
  manuelle (vider le cache) sinon.

## À mémoriser

- Le container contient des **definitions** ; l'object est construit au
  **premier usage**, puis **réutilisé** (partagé) ; `shared: false` pour une
  instance par usage.
- Identifier par défaut : le **FQCN** de la class.
- **Privé par défaut**, services comme alias. Privé : injectable **oui**,
  `get()` **non**, retirable ou inlinable **oui**.
- Public : `public: true`, `#[Autoconfigure(public: true)]`, ou un alias
  public.
- Moyen mnémotechnique pour privé : **« I-G-R : Injectable, pas de Get,
  Retirable »**.
- Alias d'interface : `App\Mail\MailerInterface: '@App\Mail\PhpMailer'` ;
  automatique si **une seule** implémentation est découverte.
- Class compilée : `App_Kernel` + `Prod` + (`Debug`) + `Container`, dans
  `var/cache/<env>/`.
- Debug : recompilé si une source change. Sans debug : **vider le cache**.
- `debug:container` : privés listés par défaut, **pas** de `--show-private`,
  `--show-hidden` = **uniquement** les services dont l'identifier commence
  par un point.

## Pièges d'examen

**Un service privé est injectable.** « Privé » ne restreint que
`$container->get()` sur le container compilé. Un énoncé qui conclut qu'un
service privé ne peut pas être passé en argument à un autre service se trompe.

**Privé est le défaut.** Un `get()` qui échoue sur un service de `src/` parfaitement
enregistré et injecté ailleurs n'indique ni une erreur de déclaration ni un
container non compilé : le service est simplement privé.

**Le container ne construit rien à la compilation.** Les objects naissent au
premier usage, puis sont réutilisés. Un énoncé qui affirme que tous les
services sont instanciés au démarrage ou à `compile()` est faux.

**Deux injections, une instance.** Un service partagé (le défaut) est le même
object pour tous ceux qui le reçoivent. Il faut `shared: false` pour obtenir des
instances distinctes.

**L'alias d'interface n'est automatique qu'avec une seule implémentation.**
Ajouter une seconde class qui implémente l'interface fait échouer la
compilation (« Cannot autowire service … You should maybe alias this
interface ») : il faut déclarer l'alias.

**Un alias est privé par défaut, lui aussi.** Créer un alias ne donne pas accès
à un service par `get()` : il faut que l'alias soit public.

**La configuration n'est pas relue en production.** Le container est une class
PHP compilée dans `var/cache/<env>/` ; sans debug, le kernel ne vérifie pas sa
fraîcheur. Une modification de `services.yaml` reste sans effet tant que le
cache n'est pas vidé.

**`--show-private` n'existe pas, et `--show-hidden` ne fait pas ce que son nom
suggère.** Les services privés sont listés par défaut ; `--show-hidden`
affiche uniquement les services internes, ceux dont l'identifier commence par
un point.

## Application : quatre scénarios

**Scénario 1 — Le `get()` qui échoue.** Dans une command, un développeur écrit
`$this->container->get(ReportExporter::class)` et obtient « has been removed or
inlined when the container was compiled ». Le service est dans `src/`, et il
est injecté sans problème dans un controller.

*Diagnostic.* `ReportExporter` est privé, comme tout service par défaut.
*Correction.* Injecter `ReportExporter` dans le constructor de la command.
Rendre le service public serait une solution possible, mais c'est le
contournement que la documentation déconseille.

**Scénario 2 — La seconde implémentation.** L'application compilait. On ajoute
`SmtpMailer implements MailerInterface` à côté de `PhpMailer`. Toute
l'application tombe, sur toutes les pages.

*Diagnostic.* Avec une seule implémentation, l'alias de `MailerInterface` était
créé automatiquement. Avec deux, il ne l'est plus, et la compilation échoue sur
chaque service qui demande `MailerInterface`. *Correction.* Déclarer
`App\Mail\MailerInterface: '@App\Mail\PhpMailer'` (ou l'inverse), ou
`#[AsAlias]` sur la class choisie.

**Scénario 3 — La modification fantôme.** En production, un argument de service
a été modifié dans `services.yaml` et déployé. L'ancien comportement persiste.

*Diagnostic.* Le kernel de production inclut `App_KernelProdContainer.php` sans
vérifier sa fraîcheur. *Correction.* `php bin/console cache:clear` (ou un cache
reconstruit) à chaque deployment.

**Scénario 4 — Le test qui ne trouve pas son service.** Un functional test fait
`static::getContainer()->get(PriceCalculator::class)` et obtient
`ServiceNotFoundException`. Un autre service privé, lui, est accessible de la
même façon.

*Diagnostic.* Le container de test donne accès aux services privés **non
retirés**. `PriceCalculator` n'est injecté nulle part, il a été retiré à la
compilation. *Correction.* Le déclarer public dans `config/services_test.yaml`.

## QCM d'entraînement

Huit questions, une par piège de la page. Engage-toi sur une lettre avant
d'ouvrir chaque correction.

**Question 1.** Le container vient d'être compilé et aucun appel HTTP n'a encore eu lieu. Combien d'objects `PhpMailer` existent-ils ?

- **A.** Un seul, construit pendant la compilation pour vérifier ses arguments
- **B.** Autant que de services qui le reçoivent en argument de leur constructor
- **C.** Aucun : le container ne garde que la definition jusqu'au premier usage
- **D.** Un seul, construit au démarrage du kernel avant toute autre opération

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Le container contient des definitions ; l'object n'est
construit qu'au premier usage (exécuté : zéro construction après `compile()`).

A est le piège : la compilation vérifie les références en lisant les
definitions, sans rien instancier. B confond avec la règle de partage :
même au premier usage, un service partagé n'est construit qu'une fois, quel que
soit le nombre de consommateurs. D invente une instanciation au démarrage :
le kernel inclut le container compilé, il ne construit pas les services.

</details>

**Question 2.** Un service `App\Billing\InvoiceNumberer` est déclaré sans aucune option. Quelle affirmation est vraie une fois le container compilé ?

- **A.** Il ne peut être injecté que dans les services du même namespace
- **B.** Il peut être injecté dans d'autres services, mais `get()` ne peut pas le rendre
- **C.** Il peut être récupéré par `get()`, mais ne peut pas être injecté
- **D.** Il n'est ni injectable ni récupérable tant qu'il n'a pas été déclaré public dans la configuration

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Un service est privé par défaut, et privé ne restreint
que l'accès par `get()` sur le container compilé.

D est le piège classique : il prend « privé » au sens de « inaccessible à tous
». C inverse les deux droits. A invente une règle de namespace : la visibilité
d'un service n'a aucun rapport avec les namespaces PHP.

</details>

**Question 3.** Deux controllers reçoivent chacun `App\Pdf\PdfRenderer` dans leur action, au cours du même appel. Le service est déclaré sans option. Combien d'objects `PdfRenderer` sont construits ?

- **A.** Deux, car chaque controller reçoit sa propre copie de l'object
- **B.** Un seul, partagé par les deux controllers
- **C.** Deux, sauf si le service est explicitement déclaré `shared: true`
- **D.** Aucun, car un service injecté dans une action n'est jamais instancié

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Un service est partagé par défaut : la documentation dit
que « the same instance is returned each time you ask for it ».

C est le piège : il inverse le défaut, `shared: true` est déjà la default value ;
c'est `shared: false` qui donnerait deux objects. A suppose une copie
par consommateur, qui n'existe qu'avec `shared: false`. D est absurde : le
controller reçoit bien un object utilisable.

</details>

**Question 4.** `src/` contient `PhpMailer` et `SmtpMailer`, qui implémentent tous deux `MailerInterface`, et une `Newsletter` qui demande `MailerInterface`. Aucun alias n'est déclaré. Que se passe-t-il ?

- **A.** La compilation échoue et le message propose de déclarer un alias vers l'un des deux services
- **B.** Le container choisit la première class trouvée par ordre alphabétique
- **C.** Le container injecte les deux implémentations dans un array itérable
- **D.** La `Newsletter` reçoit `null`, et l'erreur n'apparaît qu'au premier envoi d'un email par l'application

<details>
<summary>Correction</summary>

**Bonne réponse : A.** Avec deux implémentations, aucun alias n'est créé
automatiquement, et l'autowiring ne peut pas choisir. Exécuté : « Cannot
autowire service … references interface … but no such service exists. You
should maybe alias this interface to one of these existing services ».

B invente une règle de choix. C confond avec l'injection d'un ensemble de
services, qui passe par des tags et un iterable déclaré explicitement, pas par
un argument typé par une interface. D suppose une erreur tardive : c'est la
compilation qui échoue, sur toutes les pages.

</details>

**Question 5.** Quelle ligne de `config/services.yaml` permet à un argument typé `App\Mail\MailerInterface` d'être autowiré avec `App\Mail\PhpMailer` ?

- **A.** `App\Mail\PhpMailer: { public: true }`
- **B.** `App\Mail\PhpMailer: { tags: ['App\Mail\MailerInterface'] }`
- **C.** `App\Mail\MailerInterface: '@App\Mail\PhpMailer'`
- **D.** `App\Mail\PhpMailer: '@App\Mail\MailerInterface'`

<details>
<summary>Correction</summary>

**Bonne réponse : C.** C'est un alias : un identifier nommé comme l'interface,
qui désigne l'implémentation. L'autowiring cherche un service nommé comme le
type de l'argument et trouve l'alias.

D est le piège symétrique : il crée un alias nommé `PhpMailer` qui pointerait
vers un service `MailerInterface` qui n'existe pas. A rend le service public, ce
qui ne lui donne aucun nom supplémentaire. B pose un tag, qui sert à regrouper
des services, pas à nommer un type.

</details>

**Question 6.** En production, un développeur modifie l'argument d'un service dans `services.yaml` et déploie le file. Rien ne change. Pourquoi ?

- **A.** La production ne lit que `services_prod.yaml`, jamais `services.yaml`
- **B.** Le kernel inclut le container compilé en cache sans vérifier s'il est à jour
- **C.** Les arguments de services ne sont lus qu'au démarrage du serveur web, qu'il faut redémarrer
- **D.** Le YAML est mis en cache par OPcache, qui doit être vidé séparément

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Le container est une class PHP compilée dans
`var/cache/prod/` ; sans debug, `Kernel::initializeContainer()` l'inclut sans
contrôle de fraîcheur. Il faut vider le cache.

A est faux deux fois : `services.yaml` est toujours importé, et
`services_prod.yaml` s'y **ajoute**. C invente une lecture au démarrage du
serveur : le container n'est pas relu du tout. D se trompe de cible : ce n'est
pas le YAML qui est en cache, c'est le container compilé.

</details>

**Question 7.** Que fait `php bin/console debug:container --show-hidden` ?

- **A.** Il affiche les services privés en plus des services publics, qui sont seuls listés par défaut
- **B.** Il affiche tous les services, internes compris
- **C.** Il affiche uniquement les services dont l'identifier commence par un point
- **D.** Il échoue, car l'option a été remplacée par `--show-private` en 8.0

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Lu dans le code 8.0 et exécuté : le titre devient
« Symfony Container Hidden Services » et seuls les services internes restent
(79 sur un kernel `dev` minimal, contre 395 services ordinaires sans l'option).

B est le piège : le nom de l'option suggère qu'elle *ajoute* les services
cachés, alors qu'elle *remplace* la liste. A confond « caché » et « privé » :
les services privés sont déjà listés par défaut. D inverse la réalité :
`--show-private` n'existe pas.

</details>

**Question 8.** Dans un functional test, `static::getContainer()->get(App\Tax\VatCalculator::class)` lève `ServiceNotFoundException`. `VatCalculator` est un service privé que rien n'injecte. Quelle est la correction recommandée ?

- **A.** Le rendre public dans `config/services.yaml`, pour tous les environments
- **B.** Remplacer `static::getContainer()` par `$client->getContainer()`, qui donne accès à tout
- **C.** Le déclarer public dans `config/services_test.yaml`
- **D.** Appeler `compile()` à nouveau dans le test avant de demander le service

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Le container de test expose les services privés **non
retirés** ; un service privé que rien n'utilise a été retiré à la compilation.
La documentation conseille de le rendre public dans `config/services_test.yaml`,
que le kernel importe en plus de `services.yaml` en environment `test`.

A fonctionne mais change la production pour un besoin de test. B se trompe de
remède : aucun container ne rend un service qui a été retiré. D est impossible :
le container compilé est figé, et le service n'existe plus.

</details>

## Fiche express

**Si tu dois retenir seulement 7 choses :**

- Le container contient des **definitions** ; l'object est construit au
  **premier usage** et **partagé** ensuite (`shared: false` pour changer).
- L'identifier par défaut est le **FQCN** de la class.
- **Privé par défaut** (services et alias) : **I-G-R**, injectable, pas de
  `get()`, retirable ou inlinable.
- Public : `public: true`, `#[Autoconfigure(public: true)]`, ou un **alias
  public**.
- Un **alias** est un second identifier ; nommé comme une interface, il la rend
  autowirable. Automatique avec **une seule** implémentation, obligatoire
  au-delà.
- Le container est **compilé** en `var/cache/<env>/App_Kernel…Container.php` ;
  sans debug, il n'est pas revérifié : **vider le cache** après un changement.
- `debug:container` : privés listés par défaut, pas de `--show-private`,
  `--show-hidden` = **uniquement** les services internes (identifier en `.`).

**En une phrase, pour tout reconstruire :**

```text
CONFIGURATION → DEFINITIONS (privées, partagées, nommées par FQCN, + alias)
  → COMPILATION (une fois : autowiring, alias résolus, privés retirés ou inlinés)
  → CLASS PHP EN CACHE (relue telle quelle ; vérifiée seulement en debug)
  → OBJECTS CONSTRUITS AU PREMIER USAGE, PUIS RÉUTILISÉS
```

## Sources officielles

- [Service Container, « Fetching and using Services », « Public Versus Private Services »](https://github.com/symfony/symfony-docs/blob/8.0/service_container.rst)
- [How to Create Service Aliases and Mark Services as Public](https://github.com/symfony/symfony-docs/blob/8.0/service_container/alias_private.rst)
- [Defining Services Dependencies Automatically (Autowiring), « Working with Interfaces »](https://github.com/symfony/symfony-docs/blob/8.0/service_container/autowiring.rst)
- [Testing, `static::getContainer()`](https://github.com/symfony/symfony-docs/blob/8.0/testing.rst)
- [HttpKernel 8.0, `Kernel`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/Kernel.php)
- [FrameworkBundle 8.0, `MicroKernelTrait`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Kernel/MicroKernelTrait.php)
- [FrameworkBundle 8.0, `ContainerDebugCommand`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Command/ContainerDebugCommand.php)
- [FrameworkBundle 8.0, `TextDescriptor`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Console/Descriptor/TextDescriptor.php)
