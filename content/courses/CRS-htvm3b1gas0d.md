---
id: CRS-htvm3b1gas0d
official_item: OIT-92ctf3sy3ddk
title: "Dependency Injection component"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-27"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/components/dependency_injection.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/components/dependency_injection.rst"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    symbol_or_lines: 'Basic Usage (register, addArgument, setParameter, Reference, addMethodCall); Getting Services That Don''t Exist — "EXCEPTION_ON_INVALID_REFERENCE: throws an exception at compile time (this is the default behavior)"; Avoiding your Code Becoming Dependent on the Container'
    verified_at: "2026-10-27"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/service_container/injection_types.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/service_container/injection_types.rst"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    symbol_or_lines: 'Types of Injection — Constructor Injection, Immutable-setter Injection, Setter Injection, Property Injection, with their advantages and disadvantages'
    verified_at: "2026-10-27"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/service_container.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/service_container.rst"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    symbol_or_lines: 'Public Versus Private Services — "Every service defined is private by default"; "you should fetch services using dependency injection instead of using $container->get()"'
    verified_at: "2026-10-27"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/DependencyInjection/ContainerBuilder.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/ContainerBuilder.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "ContainerBuilder::register(), compile(bool $resolveEnvPlaceholders = false), get()"
    verified_at: "2026-10-27"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpKernel/Kernel.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/Kernel.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "Kernel::initializeContainer() — includes the cached container, checks its freshness only in debug; otherwise buildContainer(), compile(), dumpContainer() with PhpDumper"
    verified_at: "2026-10-27"
outcome_sections:
  OUT-svr2vz7ebkv7: "Injecter ou récupérer : la distinction de fond"
  OUT-6ah70qtk60fe: "Les quatre formes d'injection"
  OUT-dvs57dgffn57: "Le component seul, ligne par ligne"
---

## Objectif

Comprendre ce que la dependency injection résout, distinguer recevoir un service
et le demander au container, choisir la forme d'injection adaptée à une
dependency, et savoir utiliser le component seul jusqu'à la compilation. Le
container dans une application — definitions, visibilité, configuration — a sa
propre page, *Service container*.

## Avant la règle : le problème à résoudre

Prends une class qui envoie des emails. La façon la plus spontanée de l'écrire
est de lui faire construire elle-même ce dont elle a besoin :

```php
class Mailer
{
    private SmtpTransport $transport;

    public function __construct()
    {
        $this->transport = new SmtpTransport('smtp.acme.test');   // choisi ici, pour toujours
    }
}
```

Ce code fonctionne. Le problème apparaît dès que l'application grandit, et il
a trois visages.

**Tu ne peux pas la tester seule.** Pour tester `Mailer`, il te faut un vrai
serveur SMTP, parce que la class fabrique son transport sans te demander ton
avis. Impossible de lui passer un faux transport qui enregistre les messages.

**Tu ne peux pas en changer sans la modifier.** Le jour où la production passe
par une API d'envoi plutôt que par SMTP, il faut ouvrir `Mailer` et réécrire
son constructor. La class connaît un détail qui ne la regarde pas.

**Tu ne vois pas ses besoins de l'extérieur.** En lisant `new Mailer()`, rien
ne dit que cet object ouvre une connexion réseau. Ses dependencies sont cachées
dans son body.

La **dependency injection** renverse la responsabilité. La class ne fabrique
plus ses collaborateurs : elle **déclare** ce dont elle a besoin, et quelqu'un
d'autre le lui **fournit**.

```php
class Mailer
{
    public function __construct(
        private TransportInterface $transport,
    ) {
    }
}
```

Les trois problèmes disparaissent d'un coup : le test passe un faux transport,
la production passe le vrai, et la signature du constructor dit à tout lecteur
ce dont la class a besoin. La documentation résume l'enjeu en une phrase :
rendre les dependencies d'une class explicites et exiger qu'on les lui injecte
la rend « more reusable, testable, and decoupled from the rest of the code ».

Il reste une question : si la class ne construit plus ses dependencies, **qui
le fait ?** Dans une petite application, toi, à la main. Dans une vraie, il y a
des centaines d'objects qui dépendent les uns des autres : c'est le travail du
**service container**, l'object qui sait construire chaque service avec ses
dependencies. Le component `symfony/dependency-injection` fournit ce container.

## Le fil rouge : un `NewsletterManager` et son `Mailer`

Toute la page suit le même exemple, celui que prend la documentation du
component. Deux classes :

```php
class Mailer
{
    public function __construct(
        private string $transport,
    ) {
    }
}

class NewsletterManager
{
    public function __construct(
        private Mailer $mailer,
    ) {
    }
}
```

`Mailer` a besoin d'un nom de transport (une simple valeur), `NewsletterManager`
a besoin d'un `Mailer` (un autre service). On va :

1. décrire ces deux services au container (des **definitions**) ;
2. lui dire comment relier l'un à l'autre (une **référence**) ;
3. le **compiler** ;
4. obtenir un `NewsletterManager` prêt à l'emploi, sans jamais écrire `new`.

Chaque section reviendra sur ces deux classes pour montrer une variante :
l'injection par setter, une dependency absente, un cycle.

## Le cycle complet en un schéma

```text
1. LA CLASS DÉCLARE         NewsletterManager::__construct(Mailer $mailer)
                            → elle ne fabrique rien, elle annonce ses besoins
        │
        ▼
2. ON DÉCRIT LES SERVICES   $container = new ContainerBuilder();
   (definitions)            $container->register('mailer', Mailer::class)->addArgument('%mailer.transport%');
                            $container->register('newsletter_manager', NewsletterManager::class)
                                      ->addArgument(new Reference('mailer'));
        │
        ▼
3. ON COMPILE               $container->compile();
                            → compiler passes, références vérifiées, services privés
                              retirés ou inlinés, parameters figés
        │
        ▼
4. ON OBTIENT, UNE FOIS     $manager = $container->get('newsletter_manager');   ← entry point
                            → le container construit Mailer, puis NewsletterManager
        │
        ▼
5. PARTOUT AILLEURS         les services reçoivent leurs dependencies,
                            ils ne demandent jamais rien au container
```

Les sections suivantes reprennent ce schéma. L'étape 1 est la distinction de
fond entre injecter et récupérer. Les étapes 2 et 3 sont le component, ligne
par ligne. L'étape 4 est l'unique appel légitime à `get()`. Les variantes de
l'étape 1 sont les formes d'injection.

## Injecter ou récupérer : la distinction de fond

C'est la notion la plus importante de la page, et la plus piégeuse à l'examen.
Il y a deux façons pour une class d'obtenir un collaborateur.

**Injecter**, c'est **recevoir** : la class déclare `Mailer $mailer` dans sa
signature, et quelqu'un le lui passe. Elle ne sait pas d'où il vient.

**Récupérer** (*service location*), c'est **demander** : la class reçoit le
container, et va chercher ce qu'elle veut quand elle en a besoin.

```php
class NewsletterManager
{
    public function __construct(
        private ContainerInterface $container,     // elle reçoit le container…
    ) {
    }

    public function send(): void
    {
        $mailer = $this->container->get('mailer');  // …et se sert elle-même
    }
}
```

Regarde bien le constructor : le container y est **injecté**. Et pourtant ce
n'est pas de la dependency injection au sens de la règle. Ce qui compte, ce
n'est pas la manière dont un object arrive dans la class, c'est **ce qu'il
arrive**. Ici arrive un object capable de fournir *n'importe quel* service.

### Pourquoi c'est un problème

Lis seulement la signature de cette class : `ContainerInterface $container`.
Qu'utilise-t-elle ? Impossible de le savoir sans lire tout son code. Ses
dependencies réelles ont **disparu de sa signature**. Trois conséquences :

- **le test redevient difficile** : il faut construire un container entier, ou
  un faux container qui sait répondre à `get('mailer')` ;
- **la class est liée à ce container-là** : la documentation du component le
  dit explicitement, injecter le container « would then be tied to this
  particular container making it difficult to reuse the class elsewhere » ;
- **les erreurs arrivent tard** : un identifier mal tapé dans `get()` n'est
  découvert qu'à l'exécution de cette ligne, alors qu'une référence mal tapée
  dans une definition fait échouer `compile()` (exécuté plus bas).

### Le seul endroit où l'on récupère

Il faut bien que quelqu'un, quelque part, demande le premier service au
container. La documentation le formule ainsi : « You will need to get a
service from the container at some point but this should be as few times as
possible at the entry point to your application. » Un appel à `get()` à
l'entry point, puis de l'injection partout ailleurs.

Dans une application Symfony, cet entry point n'est même pas dans ton code :
c'est le kernel qui obtient le controller et ses services. C'est pourquoi la
documentation du service container recommande des services **privés**, obtenus
« using dependency injection instead of using `$container->get()` ».

### Et quand récupérer est légitime ?

Un cas existe : une class qui a besoin de *beaucoup* de services possibles mais
n'en utilise qu'un ou deux à chaque appel (un dispatcher de commands, par
exemple). Les injecter tous serait coûteux ; injecter le container entier
masquerait tout. La solution officielle est un **service locator** : un petit
container qui ne contient **que** les services listés pour cette class. C'est
de la récupération, mais bornée et déclarée. Elle a sa propre page.

| | Injection | Récupération par le container entier | Service locator |
|---|---|---|---|
| La class reçoit | ses dependencies | un object qui fournit tout | un object qui fournit une liste fermée |
| La signature dit ce qu'elle utilise | oui | **non** | la liste est déclarée |
| Erreur d'identifier détectée | à la compilation | à l'exécution | à l'exécution, sur une liste connue |
| Statut | la voie normale | à éviter dans un service | cas particulier légitime |

## Les quatre formes d'injection

Une fois admis qu'une class *reçoit* ses dependencies, reste à savoir **par
où** elle les reçoit. La documentation 8.0, page *Types of Injection*, en
décrit **quatre**. Les trois premières se rencontrent constamment ; la
quatrième existe surtout pour du code que tu ne contrôles pas.

### 1. Par le constructor : la voie normale

```php
class NewsletterManager
{
    public function __construct(
        private MailerInterface $mailer,
    ) {
    }
}
```

La documentation donne deux avantages, et ce sont exactement les deux
raisons de la choisir :

- **la dependency est garantie présente** : l'object ne peut pas être construit
  sans elle. Si la class ne peut pas fonctionner sans, c'est le bon endroit ;
- **elle ne changera pas** : le constructor n'est appelé qu'une fois, donc la
  dependency reste la même pendant toute la vie de l'object.

Et deux limites : elle ne convient pas à une dependency **facultative**, et elle
complique l'inheritance : étendre la class puis redéfinir son constructor devient
délicat.

Le type déclaré compte aussi. La documentation conseille de typer par une
**interface** plutôt que par une class : tu obtiens une erreur claire si une
dependency inadaptée est passée, et la liberté de changer d'implémentation.

### 2. Par un setter : la dependency facultative

```php
use Symfony\Contracts\Service\Attribute\Required;

class NewsletterManager
{
    private ?MailerInterface $mailer = null;

    #[Required]
    public function setMailer(MailerInterface $mailer): void
    {
        $this->mailer = $mailer;
    }
}
```

Avantages officiels : elle convient aux dependencies **facultatives** (pas
besoin de la dependency ? on n'appelle pas le setter) ; on peut l'appeler
plusieurs fois, ce qui permet d'ajouter des éléments à une collection.

Inconvénients officiels, et ils sont symétriques des avantages du
constructor : rien ne garantit que le setter sera appelé, donc la class doit
vérifier que la dependency est là avant de s'en servir ; et rien n'empêche de
le rappeler plus tard, donc la dependency peut **changer** en cours de vie.

Un avantage pratique que l'on retrouve plus bas : un setter permet de casser un
**cycle** entre deux services qui ont besoin l'un de l'autre.

### 3. Par un immutable-setter : facultative, mais figée

C'est la forme la moins connue, et elle est bien dans la documentation 8.0.
La method ne modifie pas l'object : elle en renvoie un **clone** qui porte la
dependency.

```php
class NewsletterManager
{
    private ?MailerInterface $mailer = null;

    #[Required]
    public function withMailer(MailerInterface $mailer): static
    {
        $new = clone $this;
        $new->mailer = $mailer;

        return $new;
    }
}
```

Le container doit savoir que la return value **remplace** le service. En
YAML, on l'écrit avec le tag `!returns_clone` ; avec le component, c'est le
troisième argument de `addMethodCall()` :

```php
$container->register('newsletter_manager', NewsletterManager::class)
    ->addMethodCall('withMailer', [new Reference('mailer')], true);   // true : garder le clone renvoyé
```

Ce qu'elle garde du constructor : la dependency reste la même pendant toute la
vie du service. Ce qu'elle garde du setter : la dependency peut être
facultative, et la method peut être appelée plusieurs fois. Ses inconvénients
officiels : la dependency peut être `null` si l'appel n'a pas eu lieu, et sauf
service *lazy*, elle ne permet pas de casser un cycle.

### 4. Par une property publique : à éviter

```php
class NewsletterManager
{
    public ?MailerInterface $mailer = null;
}
```

```php
$container->register('newsletter_manager', NewsletterManager::class)
    ->setProperty('mailer', new Reference('mailer'));
```

La documentation est nette : « There are mainly only disadvantages ». On ne
contrôle pas quand la dependency est posée, et n'importe qui peut la changer à
tout moment. Elle existe pour une raison : du code **tiers** que tu ne peux pas
modifier et qui attend ses dependencies dans des properties publiques.

### Comment choisir

| La dependency est… | Forme | Pourquoi |
|---|---|---|
| **obligatoire** | constructor | l'object ne peut pas exister sans elle, et elle ne change plus |
| **facultative** | setter | on n'appelle pas le setter si on n'en a pas besoin |
| facultative, et le service doit rester **immuable** | immutable-setter | facultative comme un setter, figée comme un constructor |
| imposée par du **code tiers** (property publique) | property | aucune autre raison de la choisir |

La question à se poser est donc toujours la même : *la class peut-elle
fonctionner sans cette dependency ?* Non : constructor. Oui : setter (ou
immutable-setter).

### `#[Required]` : seulement avec l'autowiring

L'attribute `#[Required]` (namespace `Symfony\Contracts\Service\Attribute`)
marque une method que le container doit appeler. Mais il n'agit que si le
service est **autowiré**. Exécuté avec DependencyInjection 8.0.15 :

| Situation | Le setter est-il appelé ? |
|---|---|
| `#[Required]` sur `setMailer()`, service autowiré | **oui** |
| `#[Required]` sur `setMailer()`, service **non** autowiré | non : la property reste `null` |
| `setMailer()` sans `#[Required]`, service autowiré | non : l'autowiring ne devine pas les setters |
| `#[Required]` sur un immutable-setter qui renvoie `static`, autowiré | oui, et le clone remplace le service |
| le même immutable-setter typé `self` au lieu de `static`, autowiré | la method est appelée, mais son résultat est **jeté** : le service garde sa property à `null` |

Le dernier cas est celui que la documentation signale dans une note :
avec l'autowiring, un immutable-setter doit déclarer le return type
`static` (ou un docblock `@return static`) pour que le container comprenne que
la return value remplace le service.

### Les cycles : pourquoi le setter les casse

Deux services qui se demandent mutuellement **par le constructor** ne peuvent
pas être construits : pour construire A il faut B, pour construire B il faut A.
Exécuté :

```text
A(B) et B(A), tous deux par constructor
→ compile() lève ServiceCircularReferenceException :
  Circular reference detected for service "a", path: "a -> b -> a".
```

L'erreur tombe **à la compilation**, pas au premier appel. Il suffit qu'**un**
des deux côtés passe par un setter :

```text
C(D) par constructor, D reçoit C par setter
→ compile() réussit ; le container construit D, puis C avec D,
  puis appelle D::setC() avec ce même C : la boucle est refermée.
```

Un immutable-setter, lui, ne casse pas un cycle (sauf service lazy) : la
documentation le range dans ses inconvénients.

## Le component seul, ligne par ligne

`symfony/dependency-injection` est **autonome**. Tu peux l'installer dans
n'importe quel projet PHP sans le reste de Symfony :

```bash
composer require symfony/dependency-injection
```

Il fournit un container compatible **PSR-11** : l'interface
`Symfony\Component\DependencyInjection\ContainerInterface` étend
`Psr\Container\ContainerInterface` (vérifié en 8.0.15). Voici le fil rouge
complet, tel que la documentation du component le construit :

```php
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

$container = new ContainerBuilder();                              // 1

$container->setParameter('mailer.transport', 'sendmail');         // 2

$container
    ->register('mailer', Mailer::class)                           // 3
    ->addArgument('%mailer.transport%');                          // 4

$container
    ->register('newsletter_manager', NewsletterManager::class)
    ->addArgument(new Reference('mailer'))                        // 5
    ->setPublic(true);                                            // 6

$container->compile();                                            // 7

$manager = $container->get('newsletter_manager');                 // 8
```

**Ligne 1 — `new ContainerBuilder()`.** Un container que l'on peut encore
**décrire** : on y ajoute des definitions et des parameters. Ce n'est pas
encore un container figé.

**Ligne 2 — `setParameter()`.** Un parameter est une **valeur** nommée (ici,
le nom du transport). On le déclare une fois pour pouvoir le réutiliser dans
plusieurs services.

**Ligne 3 — `register(id, class)`.** Crée une **definition** : le plan de construction
d'un service. Elle n'instancie rien ; elle décrit. La method renvoie
la `Definition`, d'où l'enchaînement des appels.

**Ligne 4 — `addArgument('%mailer.transport%')`.** Le prochain argument du
constructor. Les `%…%` désignent un parameter : il sera remplacé par sa valeur.

**Ligne 5 — `addArgument(new Reference('mailer'))`.** Ici, l'argument n'est pas
une valeur mais **un autre service**. Au moment où l'on écrit cette ligne, le
service `mailer` n'existe pas encore : `Reference` dit au container « mets ici
le service `mailer` quand tu construiras celui-ci ».

> **Piège de lecture.** `addArgument('mailer')` passerait la **string**
> `'mailer'` au constructor, pas le service. Seul `new Reference('mailer')` (ou
> `@mailer` en YAML) désigne un service.

**Ligne 6 — `setPublic(true)`.** Un service est **privé par défaut** (exécuté :
`isPublic()` vaut `false` après `register()`). Privé veut dire : injectable
dans d'autres services, mais pas récupérable par `get()` une fois le container
compilé. On rend public ce seul service parce que la ligne 8 va le demander.

**Ligne 7 — `compile()`.** Le moment où la description devient un container
utilisable. Détails juste en dessous.

**Ligne 8 — `get()`.** L'unique récupération, à l'entry point. Le container
construit `Mailer` avec `'sendmail'`, puis `NewsletterManager` avec ce `Mailer`.

Pour les autres formes d'injection, la même definition s'écrit :

| Forme | Écriture avec le component |
|---|---|
| constructor | `->addArgument(new Reference('mailer'))` |
| setter | `->addMethodCall('setMailer', [new Reference('mailer')])` |
| immutable-setter | `->addMethodCall('withMailer', [new Reference('mailer')], true)` |
| property | `->setProperty('mailer', new Reference('mailer'))` |

Les quatre ont été exécutées en 8.0.15 : dans chaque cas, le service obtenu
porte bien son `Mailer`.

### Ce que `compile()` fait réellement

`compile()` n'est pas une formalité. Il :

1. **exécute les compiler passes** : ce sont elles qui résolvent l'autowiring,
   les tags, les décorations, et qui vérifient les références ;
2. **retire ou inline les services privés** : un service privé que personne
   n'utilise disparaît, un service privé utilisé une seule fois est construit
   directement là où il sert ;
3. **fige le container** : les parameters passent dans un
   `FrozenParameterBag`, et aucune definition ne peut plus être ajoutée.

Exécuté avec DependencyInjection 8.0.15 :

| Après `compile()` | Résultat |
|---|---|
| `isCompiled()` | `true` |
| `get()` d'un service **public** | l'object, construit avec ses dependencies |
| `get()` d'un service **privé** | `ServiceNotFoundException` : « The "mailer" service or alias has been removed or inlined when the container was compiled. You should either make it public, or stop using the container directly and use dependency injection instead. » |
| `has()` d'un service privé | `false` |
| `register()` d'un nouveau service | `BadMethodCallException` : « Adding definition to a compiled container is not allowed. » |
| `setParameter()` | `LogicException` : « Impossible to call set() on a frozen ParameterBag. » |
| `getParameter()` | fonctionne : figé ne veut pas dire illisible |
| deux `get()` du même service | la **même** instance ; `setShared(false)` en donne une nouvelle à chaque appel |

Le message de l'exception sur un service privé est à lire attentivement : il
te donne lui-même les deux sorties, « make it public » ou, mieux, « use
dependency injection instead ».

**Et avant `compile()` ?** Un `ContainerBuilder` non compilé sait déjà
construire un service : `get()` fonctionne, et les `%…%` sont résolus
(exécuté). Mais aucun compiler pass n'a tourné, aucune référence n'a été
vérifiée, rien n'est figé. C'est un container de construction, pas le
container final.

### Une dependency qui n'existe pas

Que se passe-t-il si le `NewsletterManager` référence un service `mailer` qui
n'a jamais été déclaré ? La documentation l'annonce, et l'exécution le
confirme : par défaut, l'erreur tombe **à la compilation**.

```text
register('newsletter_manager', …)->addArgument(new Reference('mailer'))   // 'mailer' jamais déclaré
compile() → ServiceNotFoundException :
            The service "newsletter_manager" has a dependency on a non-existent service "mailer".
```

C'est le comportement `ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE`,
le défaut. Pour une dependency **facultative** injectée par setter, on peut
demander au container d'ignorer l'appel si le service manque :

```php
use Symfony\Component\DependencyInjection\ContainerInterface;

$container->register('newsletter_manager', NewsletterManager::class)
    ->addMethodCall('setMailer', [
        new Reference('mailer', ContainerInterface::IGNORE_ON_INVALID_REFERENCE),
    ]);
```

Exécuté : `compile()` réussit, et le setter n'est **pas appelé** du tout (la
property reste `null`). C'est la cohérence de l'ensemble : une dependency
facultative passe par un setter, et l'absence du service supprime l'appel.

La documentation liste les autres comportements :
`RUNTIME_EXCEPTION_ON_INVALID_REFERENCE` (erreur reportée à l'exécution),
`NULL_ON_INVALID_REFERENCE` (renvoie `null`),
`IGNORE_ON_UNINITIALIZED_REFERENCE` (ignore aussi un service pas encore
construit).

## Concrètement dans ton application

Dans une application Symfony, **tu n'écris jamais `new ContainerBuilder()` ni
`compile()`**. Le kernel le fait pour toi. Ce qui se passe réellement, lu dans
`Kernel::initializeContainer()` en 8.0 :

```text
appel HTTP ou command
   │
   ▼
le kernel cherche le container compilé en cache (dans le build directory)
   │
   ├── trouvé, et (prod) ou (debug et toujours à jour) ──► il l'inclut : aucune compilation
   │
   └── absent, ou (debug et une configuration a changé)
          ► buildContainer() : un ContainerBuilder, rempli par ta configuration et les bundles
          ► compile()
          ► dumpContainer() : PhpDumper écrit une class PHP dans le cache
```

Trois conséquences directes pour ton travail :

- **en développement** (debug), modifier `config/services.yaml` suffit : le
  kernel voit que le cache n'est plus à jour et recompile au prochain appel ;
- **en production**, le kernel ne vérifie pas la fraîcheur du cache : après un
  deployment qui change la configuration des services, il faut vider le cache
  (`php bin/console cache:clear`), sinon l'ancien container compilé reste
  utilisé ;
- **une erreur de câblage** (service inexistant, cycle par constructor) casse
  la compilation, donc l'application entière dès le premier appel : c'est
  voulu, l'erreur ne peut pas rester cachée dans un recoin rarement exécuté.

Et dans ton code :

- tes services reçoivent leurs dependencies par le **constructor**, typées par
  une interface quand il y en a une ;
- tes controllers reçoivent aussi leurs services en argument d'action, sans
  jamais appeler `get()` ;
- si tu écris `$this->container->get(…)` dans un service, c'est le signe qu'il
  faut injecter à la place, ou déclarer un service locator.

## À comprendre

Ces idées ne s'apprennent pas par cœur ; elles s'expliquent.

- **Pourquoi injecter ?** Parce qu'une class qui fabrique ses dependencies les
  choisit à la place de l'application : elle devient intestable seule,
  impossible à reconfigurer, et opaque.
- **Pourquoi recevoir le container n'est pas de l'injection ?** Parce que ce
  qui arrive est un object qui fournit n'importe quoi : les besoins réels de la
  class sortent de sa signature, et les erreurs d'identifier sortent de la
  compilation.
- **Pourquoi le constructor par défaut ?** Parce qu'il garantit la présence de
  la dependency et qu'il ne s'exécute qu'une fois : l'object est complet et
  stable dès sa naissance.
- **Pourquoi compiler ?** Parce que c'est le moment où tout le câblage est
  vérifié d'un coup, optimisé (services privés retirés ou inlinés), puis figé
  et mis en cache : la vérification coûte une fois, pas à chaque appel.

## À mémoriser

- **Quatre formes d'injection** : constructor, immutable-setter, setter,
  property.
- Moyen mnémotechnique, de la plus sûre à la moins sûre : **« C-I-S-P :
  Constructor, Immuable, Setter, Public »**. Plus on descend, moins la
  dependency est garantie et figée.
- **Obligatoire → constructor. Facultative → setter.**
- `#[Required]` : `Symfony\Contracts\Service\Attribute\Required`, agit
  **seulement** si le service est autowiré.
- Service **privé par défaut** ; `setPublic(true)` pour le récupérer par `get()`
  après compilation.
- `Reference('id')` désigne un service ; `'%nom%'` désigne un parameter ; une
  string seule reste une string.
- Après `compile()` : `register()` → `BadMethodCallException`, `setParameter()`
  → `LogicException` (`FrozenParameterBag`).
- Défaut sur une référence absente : `EXCEPTION_ON_INVALID_REFERENCE`, erreur
  **à la compilation**.
- La command d'installation du component seul :

```bash
composer require symfony/dependency-injection
```

## Pièges d'examen

**« Le container est injecté, donc c'est de la dependency injection. »** Faux.
Le critère est ce que la class reçoit, pas la façon dont elle le reçoit. Une
class qui reçoit le container et appelle `get()` fait de la récupération de
services, et ses dependencies réelles ont disparu de sa signature.

**« Il existe trois formes d'injection. »** La documentation 8.0 en décrit
quatre : constructor, immutable-setter, setter et property. Un énoncé qui
présente l'immutable-setter (une method `withX()` qui renvoie un clone) comme
une invention ou une mauvaise pratique se trompe.

**Constructor et setter inversés.** Le constructor est la voie normale, pour la
dependency **obligatoire** ; le setter est réservé à la dependency
**facultative** (ou à un cycle). Un choix qui met une dependency
indispensable dans un setter crée un object qui peut exister sans elle.

**`#[Required]` sans autowiring.** L'attribute seul ne fait rien : sur un
service non autowiré, le setter n'est jamais appelé et la property reste
`null`. À l'inverse, l'autowiring n'appelle pas un setter qui n'a pas
`#[Required]`.

**Un service privé n'est pas « non injectable ».** Privé interdit seulement le
`get()` après compilation ; le service reste injectable dans les autres. Et
c'est le défaut : un `register()` sans `setPublic(true)` donne un service
privé.

**`compile()` fige.** Après compilation, ni nouvelle definition ni nouveau
parameter : `register()` lève une `BadMethodCallException`, `setParameter()` une
`LogicException`. Lire un parameter reste possible.

**Une référence absente casse `compile()`, pas `get()`.** Par défaut, un
service qui référence un service inexistant fait échouer la compilation, même
si personne ne demande jamais ce service.

**Un cycle entre deux constructors aussi.** Deux services qui se reçoivent
mutuellement par leur constructor lèvent `ServiceCircularReferenceException`
dès `compile()`, pas au premier appel. Un setter d'un côté suffit à le casser.

**`addArgument('mailer')` n'injecte pas le service `mailer`.** C'est la string
`'mailer'` qui part dans le constructor. Pour un service, il faut
`new Reference('mailer')`.

## Application : quatre scénarios

**Scénario 1 — Lire une class.** On te montre :

```php
class InvoiceSender
{
    public function __construct(private ContainerInterface $container) {}

    public function send(Invoice $invoice): void
    {
        $this->container->get('mailer')->send($this->container->get('pdf')->render($invoice));
    }
}
```

*Diagnostic.* Le container est injecté, mais la class fait de la récupération :
sa signature ne dit pas qu'elle utilise un mailer et un generator PDF. *Correction.*
Deux arguments de constructor, `MailerInterface $mailer` et le service PDF,
puis `$this->mailer->send(…)`.

**Scénario 2 — Choisir la forme.** Un `ReportBuilder` produit toujours un
rapport, et l'envoie en plus à un logger *si* un logger est configuré.

*Solution.* Ce dont il a toujours besoin pour produire le rapport (sa source de
données, par exemple) : constructor. Le logger, facultatif : setter. Si le service doit rester immuable, un
immutable-setter `withLogger(): static`.

**Scénario 3 — Un setter jamais appelé.** Un développeur ajoute
`#[Required] public function setLogger(LoggerInterface $logger)` à un service
déclaré à la main, sans `autowire: true`. Le logger reste `null`.

*Diagnostic.* `#[Required]` n'est lu que par l'autowiring. *Correction.* Activer
l'autowiring pour ce service, ou déclarer l'appel explicitement (`calls` en
YAML, `addMethodCall()` avec le component).

**Scénario 4 — Un deployment qui ne prend pas.** En production, un service a
été ajouté dans `config/services.yaml`, mais l'application se comporte comme
avant.

*Diagnostic.* En production, le kernel inclut le container compilé en cache
sans vérifier sa fraîcheur. *Correction.* Vider le cache au deployment :
`php bin/console cache:clear`.

## QCM d'entraînement

Choisis une lettre avant de déplier la correction : se tromper puis comprendre
pourquoi fixe mieux la notion que relire la solution.

**Question 1.** Une class `PaymentService` reçoit `ContainerInterface $c` dans son constructor et appelle `$c->get('gateway')` dans `pay()`. Comment qualifier ce code ?

- **A.** C'est de l'injection par constructor, puisque le container arrive bien par le constructor de la class
- **B.** C'est de la récupération de services : la vraie dependency n'apparaît pas dans la signature
- **C.** C'est un service locator, la forme recommandée pour toutes les dependencies d'un service
- **D.** C'est de l'autowiring, puisque le container fournit le service demandé

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Le critère est ce que la class reçoit : un object qui
peut fournir n'importe quel service. Sa dependency réelle, la passerelle de
paiement, n'est visible qu'en lisant le body de `pay()`.

A est le piège central : le *moyen* (le constructor) ne change rien au fait que
la class va chercher elle-même ce dont elle a besoin. C confond avec le service
locator, qui ne contient qu'une liste fermée de services et reste un cas
particulier, pas la règle. D confond avec l'autowiring, qui remplit des
arguments d'après leur type et n'a aucun rapport avec un appel à `get()`.

</details>

**Question 2.** Combien de formes d'injection la documentation Symfony 8.0 décrit-elle, page *Types of Injection* ?

- **A.** Deux : par constructor et par setter
- **B.** Trois : par constructor, par setter et par property
- **C.** Quatre : par constructor, immutable-setter, setter et property
- **D.** Cinq : par constructor, setter, property, attribute et container

<details>
<summary>Correction</summary>

**Bonne réponse : C.** La page décrit, dans l'ordre : *Constructor Injection*,
*Immutable-setter Injection*, *Setter Injection*, *Property Injection*.

B est le piège : c'est la liste que l'on retient souvent, en oubliant
l'immutable-setter (une method qui renvoie un clone portant la dependency). A
oublie la property, qui existe même si elle est déconseillée. D invente deux
formes : un attribute comme `#[Required]` marque une method à appeler, ce
n'est pas une forme d'injection à part, et recevoir le container est de la
récupération.

</details>

**Question 3.** Un `ExportService` ne peut produire aucun export sans son `Serializer`. Par où ce `Serializer` doit-il arriver ?

- **A.** Par un setter marqué `#[Required]`, pour pouvoir le remplacer plus tard
- **B.** Par une property publique, pour éviter un constructor trop long
- **C.** Par le constructor, qui garantit sa présence dès la création de l'object
- **D.** Par `$this->container->get()` au moment de l'export, pour ne le charger qu'au besoin

<details>
<summary>Correction</summary>

**Bonne réponse : C.** La dependency est **obligatoire** : la documentation
réserve ce cas au constructor, parce que l'object ne peut pas être construit
sans elle et qu'elle ne changera plus.

A se trompe de forme : le setter sert aux dependencies facultatives, et
« pouvoir le remplacer plus tard » est justement un inconvénient officiel du
setter. B choisit la seule forme dont la documentation dit qu'elle n'a
« mainly only disadvantages ». D revient à la récupération de services.

</details>

**Question 4.** Que se passe-t-il ?

```php
$c = new ContainerBuilder();
$c->register('cart', Cart::class)->addArgument(new Reference('pricing'));
$c->register('pricing', Pricing::class)->addArgument(new Reference('cart'));
$c->compile();
```

- **A.** `compile()` réussit, puis le premier `get('cart')` provoque une loop infinie
- **B.** `compile()` lève une `ServiceCircularReferenceException`
- **C.** `compile()` réussit et le container injecte `null` dans l'un des deux constructors
- **D.** `compile()` réussit, et l'erreur n'apparaît que si l'un des services est public

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Deux services qui se demandent mutuellement par leur
constructor ne peuvent pas être construits, et le container le détecte à la
compilation (exécuté : « Circular reference detected for service … »).

A suppose que l'erreur attend l'exécution : ce n'est pas le cas, la
vérification fait partie de la compilation. C invente un comportement de
repli : rien n'est injecté en silence. D lie l'erreur à la visibilité, qui n'a
rien à voir ici. La solution serait de faire passer l'un des deux côtés par un
setter.

</details>

**Question 5.** Un service est déclaré à la main, **sans** autowiring. Sa class porte `#[Required] public function setLogger(LoggerInterface $logger): void`. Que vaut sa property `$logger` une fois le service obtenu ?

- **A.** Le service logger, car `#[Required]` force l'appel quelle que soit la configuration
- **B.** `null`, car `#[Required]` n'est pris en compte que pour un service autowiré
- **C.** Rien : `compile()` échoue, car un setter requis n'a pas été configuré
- **D.** Le service logger, mais seulement après le premier appel à `get()` sur le logger lui-même

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Exécuté avec DependencyInjection 8.0.15 : sans
autowiring, le setter n'est pas appelé et la property reste à sa valeur
initiale.

A est le piège : le nom de l'attribute suggère une obligation, mais c'est
l'autowiring qui le lit. C invente une vérification : aucune erreur n'est levée,
le service est construit sans le setter. D n'a pas de sens : obtenir le logger
ailleurs ne déclenche pas d'appel sur un autre service.

</details>

**Question 6.** Le parameter `app.locale` a été déclaré, puis `$c->compile()` a été appelé. Quelle ligne fonctionne encore ?

- **A.** `$c->register('audit', AuditLogger::class);`
- **B.** `$c->setParameter('audit.level', 'debug');`
- **C.** `$c->getParameter('app.locale');`
- **D.** `$c->setDefinition('audit', new Definition(AuditLogger::class));`

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Compiler **fige** le container, mais ne le rend pas
illisible : on lit toujours les parameters (exécuté).

A et D ajoutent une definition : `BadMethodCallException`, « Adding definition
to a compiled container is not allowed. » B modifie un parameter :
`LogicException`, « Impossible to call set() on a frozen ParameterBag. » Le mot à
retenir est *frozen* : on lit, on n'écrit plus.

</details>

**Question 7.** Dans une definition du component, quelle écriture passe le **service** `mailer` au constructor ?

- **A.** `->addArgument('mailer')`
- **B.** `->addArgument('%mailer%')`
- **C.** `->addArgument(\App\Service\Mailer::class)`
- **D.** `->addArgument(new Reference('mailer'))`

<details>
<summary>Correction</summary>

**Bonne réponse : D.** `Reference` est la class qui désigne un service ; le
container la remplace par le service au moment de la construction.

A passe la string `'mailer'`. B désigne un **parameter** nommé `mailer`, pas un
service : s'il n'existe pas, `compile()` échoue (« You have requested a
non-existent parameter »). C passe la string du nom de class, que le container
ne transforme pas en object, même sur un service autowiré (exécuté). Les trois se ressemblent
assez pour piéger une lecture rapide.

</details>

**Question 8.** Une class `Newsletter` a une dependency facultative vers un `Tracker`. Avec le component, on veut que l'appel `setTracker()` soit simplement **omis** si aucun service `tracker` n'est déclaré. Quelle écriture convient ?

- **A.** `->addMethodCall('setTracker', [new Reference('tracker', ContainerInterface::IGNORE_ON_INVALID_REFERENCE)])`
- **B.** `->addMethodCall('setTracker', [new Reference('tracker')])`, car un setter est toujours facultatif
- **C.** `->addArgument(new Reference('tracker', ContainerInterface::NULL_ON_INVALID_REFERENCE))`, qui omet l'argument
- **D.** `->setProperty('tracker', new Reference('tracker'))`, qui reste vide si le service manque

<details>
<summary>Correction</summary>

**Bonne réponse : A.** Avec `IGNORE_ON_INVALID_REFERENCE`, le container ignore
l'appel qui portait la référence : exécuté, `compile()` réussit et
`setTracker()` n'est pas appelé.

B est le piège : la *forme* setter rend la dependency facultative pour la class,
mais la référence garde le comportement par défaut, qui fait échouer
`compile()` si le service n'existe pas. C passe par le constructor, et
`NULL_ON_INVALID_REFERENCE` ne retire pas l'argument : il passerait `null`. D
garde aussi le comportement par défaut, et choisit la forme déconseillée.

</details>

## Fiche express

**Si tu dois retenir seulement 7 choses :**

- La dependency injection, c'est **recevoir** ses dependencies au lieu de les
  fabriquer : la class devient testable, reconfigurable et lisible.
- **Recevoir le container n'est pas de l'injection** : c'est de la récupération,
  et les dependencies réelles disparaissent de la signature. Un seul `get()`,
  à l'entry point.
- **Quatre formes** : constructor, immutable-setter, setter, property
  (« C-I-S-P », de la plus sûre à la moins sûre).
- **Obligatoire → constructor. Facultative → setter.** Un cycle se casse par un
  setter d'un côté.
- `#[Required]` n'agit que sur un service **autowiré** ; un immutable-setter
  autowiré doit renvoyer `static`.
- Le component est **autonome** (PSR-11) : `ContainerBuilder`, `register()`,
  `addArgument()`, `Reference`, puis **`compile()`**, qui exécute les compiler
  passes, retire ou inline les services privés et **fige** le container.
- Service **privé par défaut** ; référence absente et cycle par constructor font
  échouer `compile()`, pas `get()`.

**En une phrase, pour tout reconstruire :**

```text
LA CLASS DÉCLARE (constructor si obligatoire, setter si facultative)
  → ON DÉCRIT (register + addArgument / addMethodCall + Reference + %parameter%)
  → ON COMPILE (passes, vérifications, privés retirés, tout figé, mis en cache)
  → UN SEUL get() À L'ENTRY POINT, DE L'INJECTION PARTOUT AILLEURS
```

## Sources officielles

- [The DependencyInjection Component](https://github.com/symfony/symfony-docs/blob/8.0/components/dependency_injection.rst)
- [Types of Injection](https://github.com/symfony/symfony-docs/blob/8.0/service_container/injection_types.rst)
- [Service Container, « Public Versus Private Services »](https://github.com/symfony/symfony-docs/blob/8.0/service_container.rst)
- [DependencyInjection 8.0, `ContainerBuilder`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/ContainerBuilder.php)
- [HttpKernel 8.0, `Kernel::initializeContainer()`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/Kernel.php)
