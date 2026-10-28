---
id: CRS-1bkg7pkf78c7
official_item: OIT-h2n7d7dbr56p
title: "Factories"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-28"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/service_container/factories.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/service_container/factories.rst"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    symbol_or_lines: "static, non-static and invokable factories, constructor option, expressions, factory arguments"
    verified_at: "2026-10-28"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/DependencyInjection/Loader/YamlFileLoader.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Loader/YamlFileLoader.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "parseDefinition() — constructor becomes [null, constructor], never with factory; parseCallable() — @service becomes [Reference, __invoke], @= needs ExpressionLanguage"
    verified_at: "2026-10-28"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/DependencyInjection/Definition.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Definition.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "setFactory() — a Class::method string is split in two"
    verified_at: "2026-10-28"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/DependencyInjection/Container.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Container.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "Container::make(): ?object"
    verified_at: "2026-10-28"
outcome_sections:
  OUT-rrswf59s7t5a: "Les écritures : choisir selon la nature du callable"
  OUT-mfkawre47dv3: "Les arguments vont à la factory"
  OUT-5tp6e11ygzgz: "`[null, 'create']` : la class du service elle-même"
---

## Objectif

Faire construire un service par autre chose que `new`, choisir l'écriture de
l'option `factory` selon le callable, savoir à qui vont les `arguments`, et
prévoir ce que le container fait de la valeur retournée.

## Avant la règle : le problème à résoudre

Par défaut, le container construit un service avec son constructor :
`new NewsletterManager(...)`. Mais certains objects ne se construisent pas
ainsi :

- une connexion se crée par `Connection::fromDsn($dsn)`, pas par `new` ;
- un client tiers se demande à un autre object : `$sdk->createClient()` ;
- l'implémentation dépend de la configuration : en debug, une version qui
  trace ; sinon, la version normale.

Il faut pouvoir dire au container : « pour obtenir ce service, **appelle cette
fonction-là** et garde ce qu'elle retourne ». C'est une **factory** (fabrique) :
un callable que le container appelle à la place de `new`.

## Le fil rouge : un NewsletterManager

```php
namespace App\Email;

class NewsletterManager
{
    public function __construct(private string $sender = 'none')
    {
    }

    public static function create(string $sender): self
    {
        return new self('factory:'.$sender);
    }
}

class NewsletterManagerFactory
{
    public function createNewsletterManager(string $sender): NewsletterManager
    {
        return NewsletterManager::create($sender);
    }
}
```

Tout le cours tourne autour d'une question : **qui** construit le
`NewsletterManager`, et **avec quoi** ?

## Le cycle complet en un schéma

```text
définition :  class: App\Email\NewsletterManager
              factory: <callable>          arguments: [...]
                    │
                    ▼  premier get() — rien à la compilation
1. RÉSOUDRE   le callable : class statique | null = la class du service
              | @service + method | @service invocable | function | @= expression
                    │
                    ▼
2. APPELER    la factory, avec les arguments (autowirés si autowire est actif)
              ── le container n'appelle PAS le constructor lui-même
                    │
                    ▼
3. RETENIR    la valeur retournée = le service
              ── doit être un object (sinon TypeError) ; son type n'est PAS contrôlé
                    │
                    ▼
4. PARTAGER   comme tout service : shared par défaut → une seule fois
              shared: false → la factory est rappelée à chaque get()
```

## Les écritures : choisir selon la nature du callable

Le bon choix dépend d'une seule question : **qu'est-ce qui fabrique ?**

| Ce qui fabrique | Écriture YAML | Ce que le container appelle |
|---|---|---|
| une method **statique** d'une autre class | `factory: ['App\Email\NewsletterManagerStaticFactory', 'createNewsletterManager']` | `NewsletterManagerStaticFactory::createNewsletterManager()` |
| une method statique de la class **du service** | `factory: [null, 'create']` | `NewsletterManager::create()` |
| une method d'un **service** | `factory: ['@App\Email\NewsletterManagerFactory', 'createNewsletterManager']` | `$factory->createNewsletterManager()` |
| un service **invocable** | `factory: '@App\Email\InvokableNewsletterManagerFactory'` | `$factory->__invoke()` |
| une function PHP | `factory: 'App\make_newsletter'` | la function |

La différence décisive est le **`@`** :

- **sans `@`**, le premier élément est un **nom de class** : la method est
  appelée **statiquement** ;
- **avec `@`**, c'est un **id de service** : le container récupère ce service
  (construit, avec ses propres dependencies), puis appelle la method dessus.

Exécuté avec DependencyInjection 8.0.15 dans une application FrameworkBundle :
les cinq formes produisent le service, chacune par son path. Le service fabrique
peut rester privé : le container y accède de l'intérieur.

### Le piège de la method non statique

Exécuté : `factory: ['App\Factory', 'build']` où `build()` n'est **pas**
statique passe la compilation, puis le premier `get()` lève `Error` —
« Non-static method App\Factory::build() cannot be called statically ». Une
method d'instance exige la forme `@` : il faut un object pour l'appeler.

Une string `'App\Factory::build'` équivaut à l'array : `Definition::setFactory()`
la coupe en deux sur `::`.

### En PHP

La configuration PHP accepte les mêmes formes, et la documentation 8.0 montre
aussi la syntaxe *first-class callable*. Exécuté : les trois déclarations ci-dessous
produisent leur service.

```php
$services->set(NewsletterManager::class)
    ->factory(NewsletterManagerStaticFactory::createNewsletterManager(...));

$services->set(NewsletterManager::class)
    ->factory([service(NewsletterManagerFactory::class), 'createNewsletterManager']);

$services->set('date', \DateTime::class)
    ->factory(\date_create(...));
```

## `[null, 'create']` : la class du service elle-même

Quand la method statique est sur la class **créée**, on ne répète pas son nom :
`null` signifie « la class de ce service ».

```yaml
services:
    App\Email\NewsletterManager:
        factory: [null, 'create']
        arguments:
            $sender: 'fabien@symfony.com'
```

Ce n'est **pas** une function globale `create()` (une function s'écrit en une
seule string), ni un service dont l'id serait `null`.

La key **`constructor`** dit la même chose plus lisiblement. Relu dans
`YamlFileLoader` 8.0 : `constructor: 'create'` devient `setFactory([null,
'create'])`.

```yaml
services:
    App\Email\NewsletterManager:
        constructor: 'create'
        arguments:
            $sender: 'fabien@symfony.com'
```

Les deux keys s'excluent. Exécuté : `factory` et `constructor` sur le même
service lèvent `LogicException` au chargement — « The "s" service cannot declare
a factory as well as a constructor. »

### Depuis la class : `#[Autoconfigure(constructor: …)]`

```php
#[Autoconfigure(bind: ['$sender' => 'fabien@symfony.com'], constructor: 'create')]
class NewsletterManager
{
    public static function create(string $sender): self { /* … */ }
}
```

Exécuté : le service est construit par `create()`. C'est un attribute
d'**autoconfiguration** : avec `autoconfigure: false` sur le service, il est
ignoré, et le container revient à `new NewsletterManager()` (exécuté).

## Les arguments vont à la factory

C'est la confusion la plus fréquente. Avec une factory, le container **n'appelle
pas le constructor lui-même** : il appelle la factory, en lui passant
`arguments`.

Exécuté, avec le `NewsletterManager` du fil rouge et
`arguments: ['fabien']` :

| Appel | Valeur reçue |
|---|---|
| `NewsletterManager::create()`, par le container | `'fabien'` |
| `__construct()`, par `create()` via `new self(...)` | `'factory:fabien'` |

Le constructor ne s'exécute que si la factory fait elle-même `new`, et il
reçoit **ce que la factory choisit** de lui passer, jamais les `arguments` de la
definition. Une factory qui obtient l'object autrement (un client tiers, un
cache) ne déclenche aucun appel au constructor par le container.

Les arguments de la factory sont **autowirés** si l'autowiring est actif pour
le service (documentation 8.0). Exécuté : une factory `withMailer(Mailer $m)`
sans `arguments` reçoit le service `Mailer`.

## Ce que le container retient : la valeur retournée

La valeur **retournée** par la factory **devient le service**. Trois
conséquences.

**1. Ce doit être un object.** Exécuté : `factory: 'strtoupper'` — qui rend une
string — passe la compilation, puis le premier `get()` lève `TypeError` :
« Container::make(): Return value must be of type ?object, string returned ».

**2. Son type n'est pas contrôlé.** La documentation 8.0 le dit : avec une
factory, la valeur de `class` n'a **aucun effet** sur le service obtenu ; seule
compte la valeur retournée. Mais `class` reste lue par les compiler passes et
l'autowiring : il faut la garder juste. Exécuté : une factory déclarée pour la
class `Liar` qui rend un `stdClass` passe la compilation ; `get()` rend le
`stdClass`, et le service qui attend `Liar` échoue — `TypeError`, « must be of
type App\Liar, stdClass given ».

**3. Le service reste un service.** Il est partagé par défaut : exécuté, la
factory est appelée **une** fois et deux `get()` rendent le même object. Avec
`shared: false`, elle est rappelée à chaque `get()` (exécuté : deux appels, deux
objects différents).

## Une expression comme factory

La documentation 8.0 permet aussi une **expression** (préfixe `@=`), utile pour
choisir le service selon un parameter :

```yaml
services:
    App\Email\NewsletterManagerInterface:
        factory: '@=parameter("kernel.debug") ? service("tracable_newsletter") : service("newsletter")'
```

`arg(0)` y lit le premier des `arguments` de la definition :

```yaml
services:
    App\Email\NewsletterManagerInterface:
        factory: '@=arg(0).createNewsletterManager() ?: service("default_newsletter_manager")'
        arguments:
            - '@App\Email\NewsletterManagerFactory'
```

Exécuté avec ExpressionLanguage 8.0.14 : la première expression rend un service
en debug, l'autre hors debug ; `arg(0)` atteint bien le service passé en
argument.

Sans le component ExpressionLanguage, le file ne se charge même pas. Exécuté :
`LogicException`, « The "@=" expression syntax cannot be used without the
ExpressionLanguage component ». Relu dans `YamlFileLoader` 8.0 : `@=` n'est
accepté que pour `factory`, pas pour `configurator`.

## Concrètement dans ton application

- Un object créé par une **method nommée** (`fromDsn()`, `create()`) de sa
  propre class : `constructor: 'create'`, ou `[null, 'create']`.
- Un object fourni par **un autre service** (un SDK, un client) :
  `['@App\Sdk', 'createClient']`.
- Le choix d'une implémentation selon la configuration : une **expression**, ou
  une factory de service qui décide.
- `class` doit rester le vrai type retourné : l'autowiring et les compiler
  passes s'y fient, sans vérification.
- Une erreur au premier `get()` alors que la compilation passe : regarde la
  factory — method non statique appelée sans `@`, valeur retournée qui n'est pas
  un object, ou type différent de `class`.
- Pour vérifier ce que le container a compris : `php bin/console
  debug:container <id>`. Exécuté : `[null, 'create']` s'affiche « Factory
  Class App\Newsletter » et « Factory Method create » ; un service invocable,
  « Factory Service » et « Factory Method __invoke ».

## À comprendre

- **Pourquoi `arguments` va-t-il à la factory ?** Parce que c'est elle que le
  container appelle ; le constructor n'est qu'un détail d'implémentation de la
  factory.
- **Pourquoi le `@` change-t-il tout ?** Sans lui, il n'y a qu'un nom de class :
  seul un appel statique est possible. Avec lui, il y a un object à utiliser.
- **Pourquoi le type n'est-il pas vérifié ?** Parce que le container ne connaît
  la valeur qu'à l'exécution de la factory ; `class` n'est qu'une déclaration.
- **Pourquoi `null` ?** Pour ne pas répéter le nom de la class quand la factory
  est sur elle-même — `constructor` est la même idée, nommée.

## À mémoriser

- Factory = callable appelé **à la place de `new`** ; la valeur retournée est le
  service.
- **Sans `@`** : class, appel **statique**. **Avec `@`** : service, appel sur
  l'object. **`'@service'` seul** : `__invoke()`.
- `[null, 'create']` = `constructor: 'create'` = `#[Autoconfigure(constructor:
  'create')]` (ce dernier seulement avec l'autoconfiguration).
- `factory` et `constructor` ensemble : `LogicException`.
- `arguments` → la **factory** (autowirés si `autowire`) ; le container n'appelle
  pas le constructor.
- Retour : un **object** (sinon `TypeError` au `get()`) ; type **non contrôlé**.
- `@=` : expression, seulement pour `factory`, avec ExpressionLanguage.
- Moyen mnémotechnique : **« Qui fabrique ? Avec quoi ? Que rend-il ? »** — le
  `@`, les `arguments`, la valeur retournée.

## Pièges d'examen

**Les arguments vont à la factory.** Le container n'appelle pas le constructor
du service lui-même ; si la factory fait `new self(…)`, c'est elle qui choisit
ce qu'elle lui passe.

**`[null, 'create']` désigne la class du service**, pas une function globale.

**Une factory de service utilise `@`** ; une factory statique nomme la class ;
`'@service'` seul appelle `__invoke()`.

**Une method d'instance sans `@` échoue au premier `get()`**, pas à la
compilation : `Error`, « Non-static method … cannot be called statically ».

**Le container ne contrôle pas le type retourné** : l'erreur arrive chez le
consommateur.

**`factory` et `constructor` ne se combinent pas** : `LogicException` dès le
chargement de la configuration.

**`#[Autoconfigure(constructor: …)]` dépend de l'autoconfiguration** : avec
`autoconfigure: false`, l'attribute est ignoré et le constructor est utilisé.

## Application : trois scénarios

**Scénario 1 — Une connexion créée par une method statique.**
`Connection::fromDsn(string $dsn)` construit l'object. *Solution.*
`constructor: 'fromDsn'` (ou `factory: [null, 'fromDsn']`) et
`arguments: ['%env(DSN)%']` : la string DSN va à `fromDsn()`.

**Scénario 2 — Un client fourni par un SDK.** Le service `App\Sdk` a une method
`createClient(string $region)`. *Solution.*
`factory: ['@App\Sdk', 'createClient']` et `arguments: ['eu-west']`. Sans `@`,
le premier `get()` lèverait `Error`, car `createClient()` n'est pas statique.

**Scénario 3 — Un service qui ne marche pas chez son consommateur.** La
compilation passe, mais le controller reçoit un `TypeError` « must be of type
App\Mailer\Mailer, App\Mailer\NullMailer given ». *Solution.* La factory rend un
autre type que la `class` déclarée ; le container ne l'a pas vérifié. Corrige la
factory, ou déclare le vrai type.

## QCM d'entraînement

Six questions sur les factories. Choisis avant de déplier la correction.

**Question 1.** Un service déclare `factory: ['App\Mail\ClientFactory', 'build']`, et `build()` est une method **non statique**. Que se passe-t-il ?

- **A.** Le container crée une instance de `ClientFactory`, puis appelle `build()` sur cette instance
- **B.** La compilation passe ; le premier `get()` lève une `Error` sur l'appel statique
- **C.** La compilation échoue avec un message qui exige une factory statique
- **D.** Le container ignore la factory et construit le service avec son constructor

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Sans `@`, le premier élément est un nom de class et la
method est appelée statiquement. Exécuté : `Error`, « Non-static method
App\Factory::build() cannot be called statically », au premier `get()`.

A décrit la forme avec `@`. C invente une vérification à la compilation. D
invente un repli sur le constructor.

</details>

**Question 2.** Avec `App\Email\NewsletterManager: { factory: [null, 'create'], arguments: ['fabien@symfony.com'] }`, quel appel le container fait-il ?

- **A.** Une function globale `create('fabien@symfony.com')`
- **B.** `new NewsletterManager('fabien@symfony.com')`, puis `create()` sur cet object
- **C.** La method statique `create()` de `NewsletterManager`, avec l'adresse
- **D.** Aucun : `null` désactive la factory, et le constructor reçoit l'adresse

<details>
<summary>Correction</summary>

**Bonne réponse : C.** `null` désigne la class du service lui-même ; c'est
l'équivalent de `constructor: 'create'`.

A confond avec une function, qui s'écrirait en une seule string. B invente un
double appel. D lit `null` comme une désactivation.

</details>

**Question 3.** `Manager::create(string $sender)` contient `return new self('factory:'.$sender);`. Le service a `factory: [null, 'create']` et `arguments: ['fabien']`. Que reçoit le constructor ?

- **A.** `'fabien'`, passé par le container avant l'appel à `create()`
- **B.** Rien : avec une factory, le constructor ne s'exécute jamais
- **C.** `'fabien'` deux fois : une par le container, une par `create()`
- **D.** `'factory:fabien'`, passé par `create()`

<details>
<summary>Correction</summary>

**Bonne réponse : D.** Le container appelle `create('fabien')` et n'appelle pas
le constructor lui-même ; `create()` fait `new self(...)` avec sa propre valeur.
Exécuté : `create(fabien)`, puis `__construct(factory:fabien)`.

A est le piège : `arguments` ne va jamais au constructor. B est trop fort : le
container ne l'appelle pas, mais la factory peut le faire. C invente un appel
par le container.

</details>

**Question 4.** Une factory déclarée pour la class `App\Liar` rend un `stdClass`. Un service `Consumer` attend `Liar` dans son constructor. Que se passe-t-il ?

- **A.** La compilation passe ; `Consumer` échoue sur un `TypeError` quand il reçoit le `stdClass`
- **B.** La compilation échoue, car le container compare la valeur retournée à la class déclarée
- **C.** Le container enveloppe le `stdClass` dans un proxy de `Liar` pour respecter le type attendu
- **D.** Le container rejette le `stdClass` et appelle le constructor de `Liar` à la place

<details>
<summary>Correction</summary>

**Bonne réponse : A.** Le type retourné n'est pas contrôlé : la valeur de
`class` n'a aucun effet sur le service obtenu. Exécuté : « must be of type
App\Liar, stdClass given ».

B invente une vérification. C invente un proxy. D invente un repli.

</details>

**Question 5.** `NewsletterManager` porte `#[Autoconfigure(constructor: 'create')]`, mais sa definition a `autoconfigure: false`. Comment le service est-il construit ?

- **A.** Par `create()` : un attribute PHP est toujours lu par le container
- **B.** La compilation échoue, car l'attribute exige `autoconfigure: true`
- **C.** Par le constructor : l'attribute est alors ignoré
- **D.** Par les deux : le constructor, puis `create()` sur l'object obtenu

<details>
<summary>Correction</summary>

**Bonne réponse : C.** `#[Autoconfigure]` n'est appliqué qu'aux services
autoconfigurés. Exécuté : avec `autoconfigure: false`, le service vient de
`new`, pas de `create()`.

A oublie cette condition. B invente une erreur. D invente un double appel.

</details>

**Question 6.** Un service déclare `factory: '@app.newsletter_factory'`, sans nom de method. Qu'appelle le container ?

- **A.** Le constructor de `app.newsletter_factory`, dont l'instance devient le service
- **B.** La method `__invoke()` du service `app.newsletter_factory`
- **C.** Une method statique `create()`, utilisée par défaut quand la method manque
- **D.** Rien : la compilation échoue, car une method doit toujours être nommée

<details>
<summary>Correction</summary>

**Bonne réponse : B.** `YamlFileLoader` transforme `'@service'` en
`[Reference, '__invoke']` : le service fabrique doit être invocable.

A confond le service fabrique et le service produit. C invente une method par
défaut. D ignore la forme invocable, documentée en 8.0.

</details>

## Fiche express

**Si tu dois retenir seulement 7 choses :**

- Une factory est appelée **à la place de `new`** ; ce qu'elle **retourne** est
  le service.
- **Sans `@`** : appel **statique** sur une class ; **avec `@`** : appel sur un
  service ; **`'@service'` seul** : `__invoke()`.
- `[null, 'create']` = la class du service = `constructor: 'create'` ; les deux
  keys ensemble : `LogicException`.
- `arguments` va à la **factory** ; le container n'appelle jamais le constructor
  lui-même.
- La valeur doit être un **object** ; son **type n'est pas vérifié** — l'erreur
  arrive chez le consommateur.
- Le service reste **partagé** ; `shared: false` rappelle la factory.
- `#[Autoconfigure(constructor:)]` exige l'autoconfiguration ; `@=` exige
  ExpressionLanguage.

**En une phrase, pour tout reconstruire :**

```text
QUI FABRIQUE ? (class statique | null | @service + method | @service invocable | function | @=)
  → AVEC QUOI ? (arguments → la factory, autowirés)
  → QUE REND-IL ? (un object, type non vérifié, partagé par défaut)
```

## Sources officielles

- [Using a Factory to Create Services](https://github.com/symfony/symfony-docs/blob/8.0/service_container/factories.rst)
- [DependencyInjection 8.0, `Loader\YamlFileLoader`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Loader/YamlFileLoader.php)
- [DependencyInjection 8.0, `Definition`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Definition.php)
- [DependencyInjection 8.0, `Container`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Container.php)
