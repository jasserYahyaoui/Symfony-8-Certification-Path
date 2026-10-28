---
id: CRS-0a0d5bp6769e
official_item: OIT-gkhcbtygef69
title: "Service locators"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-28"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/service_container/service_subscribers_locators.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/service_container/service_subscribers_locators.rst"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    symbol_or_lines: "ServiceSubscriberInterface, optional services, AutowireLocator, ServiceLocator definition, indexing, ServiceMethodsSubscriberTrait, testing a subscriber"
    verified_at: "2026-10-28"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Contracts/Service/ServiceSubscriberInterface.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Contracts/Service/ServiceSubscriberInterface.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "public static function getSubscribedServices(): array"
    verified_at: "2026-10-28"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/DependencyInjection/ServiceLocator.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/ServiceLocator.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "createNotFoundException() — is a smaller service locator that"
    verified_at: "2026-10-28"
outcome_sections:
  OUT-3c8n410rjew2: "Le cas légitime : beaucoup de dependencies, une seule utilisée"
  OUT-treyye0w8xe4: "Ce que cela n'est pas : le container"
  OUT-g6613nkxjesh: "Hériter : fusionner avec le parent"
  OUT-7s1prxwgdhe5: "La forme courte : `#[AutowireLocator]`"
---

## Objectif

Recevoir un petit container restreint quand injecter tout est absurde, sans
retomber dans la récupération de services. Le cas particulier
d'`AbstractController` est traité dans son propre item (lot Controllers).

## Avant la règle : le problème à résoudre

Un `CommandBus` reçoit une commande et la confie au handler de cette commande.
Il connaît dix handlers. La version naïve les injecte tous :

```php
class CommandBus
{
    public function __construct(private array $handlerMap) {}
}
```

À chaque construction du bus, les **dix** handlers sont construits, avec leurs
propres dependencies — pour n'en appeler **qu'un**. La documentation 8.0 cite
aussi l'autre fausse solution : injecter le container entier, « discouraged
because it gives too broad access to existing services and it hides the actual
dependencies ».

Il faut un objet qui :

- ne contient **que** les handlers déclarés ;
- ne construit un handler **qu'au moment** où on le demande.

C'est un **service locator**.

## Le fil rouge : un CommandBus

```php
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

class CommandBus implements ServiceSubscriberInterface
{
    public function __construct(private ContainerInterface $locator) {}

    public static function getSubscribedServices(): array
    {
        return [
            'App\Handler\FooHandler',
            '?App\Handler\BarHandler',       // facultatif
            'logger' => LoggerInterface::class,
        ];
    }

    public function handle(Command $c): void
    {
        $this->locator->get($c->handlerId())->handle($c);
    }
}
```

## Le cycle complet en un schéma

```text
COMPILATION   getSubscribedServices() est lue (method STATIQUE, aucune instance)
              ── service manquant SANS ?  → la compilation échoue
              ── service manquant AVEC ?  → simplement absent du locator
     │
     ▼
INJECTION     le bus reçoit un ServiceLocator : AUCUN handler construit
     │
     ▼
get('Foo')    construit Foo à ce moment — la même instance que le container
get('Bar')    non déclaré → ServiceNotFoundException « smaller service locator »
has('?…')     false si le service facultatif n'existe pas
```

## Le cas légitime : beaucoup de dependencies, une seule utilisée

Une class qui dispatche vers dix handlers n'en utilise **qu'un** par appel.
Les injecter tous les construirait tous, pour rien.

Un **service locator** est un container minuscule, limité aux services déclarés,
qui ne construit chacun qu'au moment où on le demande.

`getSubscribedServices()` est **statique** — c'est la signature de
`ServiceSubscriberInterface` (8.0). La key `'logger'` donne un nom local au
service typé `LoggerInterface`.

### Ce que l'exécution montre

Exécuté dans une application FrameworkBundle 8.0.15 :

| Situation | Résultat |
|---|---|
| après construction du bus | aucun handler construit |
| `get(FooHandler::class)` | construit à ce moment, et **même instance** que celle du container |
| `?Loc\Missing` absent | `has()` rend `false`, rien n'échoue |
| le même, sans `?` | la compilation échoue : « The service "Loc\Bad" has a dependency on a non-existent service "Loc\Missing". » |
| `get()` d'un service non déclaré | `ServiceNotFoundException` : « even though it exists in the app's container, the container inside "Loc\Bus" is a smaller service locator that only knows about the "Loc\FooHandler" and "logger" services. Try using dependency injection instead. » |

Le préfixe `?` décide donc du moment de l'échec : sans lui, un service manquant
bloque la compilation ; avec lui, c'est à la class de tester `has()` — la
documentation 8.0 le recommande avant tout appel.

### Ce que le locator sait de lui-même

Le locator injecté est un `ServiceLocator` qui implémente aussi
`ServiceProviderInterface`. Exécuté :

- `getProvidedServices()` liste ses keys : `Loc\FooHandler` et `logger` — le
  service facultatif absent n'y figure pas ;
- `count()` rend `2` ;
- on peut le parcourir avec `foreach` (exécuté sur un locator de trois
  services : trois tours).

### Choisir le service derrière une key

Par défaut, l'autowiring choisit le service d'après le type. La documentation
8.0 montre deux moyens de viser un autre service :

```yaml
services:
    App\CommandBus:
        tags:
            - { name: 'container.service_subscriber', key: 'logger', id: 'monolog.logger.event' }
```

ou, dans `getSubscribedServices()`, un `SubscribedService` qui porte un
attribute d'injection :

```php
new SubscribedService('logger', LoggerInterface::class, attributes: new Autowire(service: 'monolog.logger.event')),
```

## Ce que cela n'est pas : le container

Ce n'est **pas** l'injection du container applicatif. Le locator ne contient
**que** ce que la class a déclaré — le message ci-dessus le dit en toutes
lettres : ses dependencies restent lisibles et vérifiables à la compilation, ce
qui est précisément ce que la récupération de services fait perdre.

| | Container injecté | Service locator |
|---|---|---|
| ce qu'il contient | tout, y compris ce qu'on ne devrait pas toucher | la liste déclarée |
| services privés | inaccessibles | accessibles, s'ils sont déclarés |
| dependency manquante | découverte à l'exécution | **compilation** (sans `?`) |
| lisibilité | dependencies cachées | dependencies écrites dans la class |

Exécuté : avec des handlers **privés**, `$container->has(FooHandler::class)`
rend `false` sur le container de l'application, mais le locator du bus rend
bien `FooHandler`.

## La forme courte : `#[AutowireLocator]`

`#[AutowireLocator]` évite d'implémenter l'interface :

```php
public function __construct(
    #[AutowireLocator([
        'foo' => FooHandler::class,
        'optionalBaz' => '?'.BazHandler::class,
        BarHandler::class,
    ])]
    private ContainerInterface $handlers,
) {}
```

Les keys donnent un nom local, `?` rend facultatif. Exécuté : keys `foo` et
`Loc\BarHandler` ; `has('optionalBaz')` vaut `false`, le service n'existant pas.

Un locator peut aussi être construit à partir d'un **tag** —
`#[AutowireLocator('app.handler')]` —, ce qui donne une table de correspondance
lazy : exécuté, les keys sont l'`index` de chaque service quand il en a un
(`foo`), son identifier sinon (`Loc\BarHandler`, `Loc\BazHandler`). Les options
`indexAttribute` (`index_by` en YAML) et `defaultIndexMethod` changent la source
de la key.

En YAML, le même locator s'écrit `!tagged_locator app.handler`, ou se déclare
à la main, keys obligatoires :

```yaml
services:
    app.command_handler_locator:
        class: Symfony\Component\DependencyInjection\ServiceLocator
        arguments:
            -
                App\FooCommand: '@app.command_handler.foo'
                App\BarCommand: '@app.command_handler.bar'
```

### Une method par service : `ServiceMethodsSubscriberTrait`

La documentation 8.0 décrit une troisième écriture : le trait
`ServiceMethodsSubscriberTrait` implémente `getSubscribedServices()` à partir
des methods marquées `#[SubscribedService]`, d'après leur type de retour.

```php
class MyService implements ServiceSubscriberInterface
{
    use ServiceMethodsSubscriberTrait;

    #[SubscribedService]
    private function router(): RouterInterface
    {
        return $this->container->get(__METHOD__);
    }
}
```

La key de chaque service est `__METHOD__`. Exécuté : le locator ne connaît que
la key `Loc\Methods::foo`, et la method rend le service. Dans un trait d'aide
(`LoggerAware`), la documentation demande `__CLASS__.'::'.__FUNCTION__`, car
`__METHOD__` y porterait le nom du trait.

## Hériter : fusionner avec le parent

Quand une class parente implémente déjà `ServiceSubscriberInterface`, la
sous-classe doit **fusionner** :

```php
return array_merge(parent::getSubscribedServices(), ['…']);
```

Oublier `parent::` retire silencieusement les services du parent. Exécuté : un
`ChildBus` qui ne déclare que `BarHandler` a un locator où
`has(FooHandler::class)` vaut `false` — aucune erreur avant l'usage. Avec la
fusion, les deux sont présents (exécuté).

## Concrètement dans ton application

- Un dispatcher qui appelle **un** service parmi plusieurs : un locator.
- Un service qui les appelle **tous** : un `#[AutowireIterator]`, pas un
  locator.
- Peu de dependencies, toutes utilisées : l'injection directe, tout simplement.
- Un handler qui peut ne pas exister : `?` et un `has()` avant le `get()`.
- Un test unitaire : la documentation 8.0 montre un faux locator, une class
  anonyme qui implémente `ServiceProviderInterface` avec `ServiceLocatorTrait`
  et reçoit des closures.

## À comprendre

- **Pourquoi statique ?** Parce que le container lit la liste **à la
  compilation**, quand aucune instance n'existe.
- **Pourquoi est-ce mieux que le container ?** Parce que la liste est écrite
  dans la class : le container peut vérifier chaque dependency avant de
  démarrer.
- **Pourquoi lazy ?** Parce que le locator garde des fabriques, pas des
  objects : chaque `get()` construit le service la première fois, puis rend le
  même.
- **Pourquoi `parent::` ?** Parce que redéfinir une method statique remplace
  celle du parent : rien ne fusionne tout seul.

## À mémoriser

- Locator = container **restreint** et **lazy**.
- `ServiceSubscriberInterface::getSubscribedServices()` est **public static**.
- Key de tableau = nom local ; `?` = facultatif (`has()` rend `false`).
- Sans `?`, service manquant = **compilation** échoue.
- `get()` d'un service non déclaré : « smaller service locator ».
- Forme courte : `#[AutowireLocator([...])]` ou `#[AutowireLocator('tag')]`.
- Héritage : `array_merge(parent::getSubscribedServices(), [...])`.
- Moyen mnémotechnique : **« Déclarer, puis demander »** — rien n'est construit
  avant le `get()`.

## Pièges d'examen

**`getSubscribedServices()` est statique.**

**Le locator n'est pas le container** : il est restreint à la liste déclarée, et
le dit dans son message d'erreur.

**Les services sont construits à la demande** — et partagés avec le container.

**Sans `?`, un service manquant bloque la compilation** ; avec `?`, `has()`
rend `false`.

**En inheritance, il faut fusionner avec `parent::`.**

**Un service facultatif absent disparaît du locator** : exécuté,
`getProvidedServices()` ne le liste pas et `count()` ne le compte pas.

## Application : trois scénarios

**Scénario 1 — Un exporteur à douze formats.** Un seul format sert par appel.
*Solution.* `#[AutowireLocator('app.exporter')]` avec un `index` par exporteur
(`csv`, `xlsx`…) : `$this->exporters->get($format)`.

**Scénario 2 — Un handler livré par un module optionnel.** Le `BillingHandler`
n'existe que si le module est installé. *Solution.*
`'?App\Billing\BillingHandler'` dans `getSubscribedServices()`, et
`if ($this->locator->has(...))` avant l'appel ; sans `?`, l'application ne
compilerait plus sans le module.

**Scénario 3 — Un bus spécialisé.** `AdminBus extends CommandBus` ajoute un
handler, et les handlers du parent « disparaissent ». *Solution.*
`array_merge(parent::getSubscribedServices(), [AdminHandler::class])`.

## QCM d'entraînement

Six questions sur les service locators. Décide avant d'ouvrir la correction.

**Question 1.** Un `NotificationRouter` reçoit un locator de cinq canaux. Juste après sa construction, combien de canaux sont construits ?

- **A.** Cinq, car un locator résout ses services à l'injection
- **B.** Un seul, le premier de la liste déclarée
- **C.** Cela dépend de `shared` : cinq pour des services partagés
- **D.** Aucun : chaque canal attend son premier `get()`

<details>
<summary>Correction</summary>

**Bonne réponse : D.** Le locator garde des fabriques ; exécuté, aucun handler
n'est construit avec le bus, et le premier `get()` construit le sien.

A est l'intuition fausse. B invente une construction partielle. C mélange le
partage d'instances et le moment de construction.

</details>

**Question 2.** Un subscriber déclare `[MailHandler::class]`. Le code appelle `$this->locator->get(SmsHandler::class)` ; `SmsHandler` existe dans l'application. Que se passe-t-il ?

- **A.** Le locator le cherche dans le container de l'application et le rend
- **B.** `ServiceNotFoundException` : service non déclaré
- **C.** `null`, parce que le service n'est pas déclaré
- **D.** La compilation échoue, car le code appelle un service non déclaré

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Exécuté : « even though it exists in the app's
container, the container inside … is a smaller service locator that only knows
about … ».

A est le piège : c'est ce qui distingue un locator du container. C invente un
retour `null`. D : le container ne lit pas les appels du code, seulement la
liste déclarée.

</details>

**Question 3.** `getSubscribedServices()` déclare `'App\Pdf\PdfRenderer'`, sans `?`, et aucun service de ce nom n'existe. Quand l'erreur apparaît-elle ?

- **A.** À la compilation du container
- **B.** Au premier `get()` du service, et seulement s'il est appelé
- **C.** Jamais : `has()` rend `false`, c'est à la class de vérifier
- **D.** À la construction du subscriber, quand le locator est injecté

<details>
<summary>Correction</summary>

**Bonne réponse : A.** Exécuté : « The service … has a dependency on a
non-existent service … ». Le préfixe `?` déplace la responsabilité vers `has()`.

B et D placent l'erreur trop tard. C décrit le comportement **avec** `?`.

</details>

**Question 4.** `ChildBus extends Bus` redéfinit `getSubscribedServices()` en ne rendant que `[BarHandler::class]`. `Bus` déclarait `FooHandler`. Que vaut `has(FooHandler::class)` dans le locator de `ChildBus` ?

- **A.** `true` : les services du parent sont fusionnés automatiquement
- **B.** Une erreur à la compilation : le parent n'est plus satisfait
- **C.** `false`, sans aucune erreur avant l'usage
- **D.** `true`, mais seulement si `Bus` est aussi un service

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Redéfinir la method statique remplace la liste du
parent ; exécuté, `has(FooHandler::class)` vaut `false`. Il faut
`array_merge(parent::getSubscribedServices(), [...])`.

A est le piège : rien ne fusionne seul. B et D inventent une vérification.

</details>

**Question 5.** Quelle écriture construit un locator **sans** implémenter `ServiceSubscriberInterface` ?

- **A.** `#[Autowire(service: 'service_container')] ContainerInterface $c`
- **B.** `#[AutowireLocator(['csv' => Csv::class])] ContainerInterface $c`
- **C.** `#[AutowireIterator(['csv' => CsvExporter::class])] iterable $exporters`
- **D.** `#[Required] ContainerInterface $c`, sur une property publique

<details>
<summary>Correction</summary>

**Bonne réponse : B.** `#[AutowireLocator]` accepte une liste, avec des keys
pour nommer, ou un nom de tag.

A injecterait le container entier, ce qu'un locator évite. C attend un nom de
tag et produit un itérable, pas un locator. D ne crée aucun locator.

</details>

**Question 6.** Un subscriber déclare `[PdfRenderer::class, '?App\Pdf\Watermarker']`, et `Watermarker` n'existe pas. Que rend `$this->locator->getProvidedServices()` ?

- **A.** Les deux keys, `Watermarker` marquée comme absente
- **B.** Une erreur, car un service facultatif manque
- **C.** Un tableau vide tant qu'aucun `get()` n'a eu lieu
- **D.** Seulement la key de `PdfRenderer`

<details>
<summary>Correction</summary>

**Bonne réponse : D.** Exécuté : le service facultatif absent n'apparaît ni
dans `getProvidedServices()` ni dans `count()`.

A invente un marquage. B oublie le rôle de `?`. C confond la liste des
services fournis et leur construction.

</details>

## Fiche express

**Si tu dois retenir seulement 7 choses :**

- Un locator = un container **restreint** à la liste déclarée et **lazy**.
- `getSubscribedServices()` est **public static** ; ou `#[AutowireLocator]`.
- Key = nom local ; `?` = facultatif, `has()` rend `false`.
- Sans `?`, un service manquant fait échouer la **compilation**.
- Un `get()` hors liste : `ServiceNotFoundException`, « smaller service
  locator ».
- Les services rendus sont **les mêmes** instances que dans le container.
- En héritage : `array_merge(parent::getSubscribedServices(), [...])`.

**En une phrase, pour tout reconstruire :**

```text
DÉCLARER (statique, ? pour facultatif) → VÉRIFIER à la compilation
  → INJECTER un ServiceLocator vide de toute instance → CONSTRUIRE au get()
```

## Sources officielles

- [Service Subscribers & Locators](https://github.com/symfony/symfony-docs/blob/8.0/service_container/service_subscribers_locators.rst)
- [Service Contracts, `ServiceSubscriberInterface`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Contracts/Service/ServiceSubscriberInterface.php)
- [DependencyInjection 8.0, `ServiceLocator`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/ServiceLocator.php)
