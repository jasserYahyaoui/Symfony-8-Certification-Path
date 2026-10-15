---
id: CRS-2hpcy2rscq7m
official_item: OIT-rd9b27fkb72r
title: "Event dispatcher and kernel events"
content_level: DEEP
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-07"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/components/event_dispatcher.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/components/event_dispatcher.rst"
    anchor: "event_dispatcher-event-propagation"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpKernel/KernelEvents.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/KernelEvents.php"
    symbol_or_lines: "class KernelEvents constants"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/EventDispatcher/Attribute/AsEventListener.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/EventDispatcher/Attribute/AsEventListener.php"
    symbol_or_lines: "AsEventListener::__construct"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/EventDispatcher/EventDispatcher.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/EventDispatcher/EventDispatcher.php"
    symbol_or_lines: "dispatch, callListeners, sortListeners, getListenerPriority"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-21"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Contracts/EventDispatcher/Event.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Contracts/EventDispatcher/Event.php"
    symbol_or_lines: "class Event implements StoppableEventInterface"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-21"
outcome_sections:
  OUT-dbz79ewzqdg9: "Listener ou subscriber"
  OUT-ces5mycxj5zx: "Priority"
  OUT-xfcs32dwmgx2: "Propagation"
  OUT-2x9c1gjdj7t5: "Le catalogue du kernel"
  OUT-p34x8sjgctbp: "Le catalogue du kernel"
---

## Objectif

Maîtriser le component EventDispatcher — listeners, subscribers, priorities,
propagation — et connaître le catalogue des events du kernel avec ce que
chacun autorise. Le déroulé de la Request est traité dans *Request handling* ;
on s'intéresse ici au **mécanisme** et aux **pouvoirs** de chaque event.

## Prérequis

Le trajet d'une Request et le vocabulaire Request / Response.

## Listener ou subscriber

Deux façons de réagir à un event, avec la même mécanique dessous.

Un **listener** (*listener*) est un callable enregistré depuis l'extérieur :

```php
$dispatcher->addListener('acme.foo', [$listener, 'onFooAction'], 10);
```

Un **subscriber** (*subscriber*) est une class qui déclare elle-même à quoi elle
s'abonne, via la method **statique** `getSubscribedEvents()` de
`EventSubscriberInterface`. Elle retourne un array indexé par nom d'event,
dont la valeur est soit le nom d'une method, soit un couple
`[méthode, priorité]`, soit une liste de tels couples.

La différence est de **responsabilité**, pas de fonctionnement : avec un
listener, la configuration sait à quoi il s'abonne ; avec un subscriber, la class
le sait, et déplacer la class suffit à déplacer l'abonnement.

Dans le framework, l'enregistrement passe par le container : un subscriber est
détecté par autoconfiguration, un listener se déclare par le tag
`kernel.event_listener` ou par l'attribute `#[AsEventListener]`, dont les
parameters sont `event`, `method`, `priority` et `dispatcher`.

## Priority

La priority est un int, **positif ou négatif**, qui vaut `0` par défaut.

> **Plus le nombre est grand, plus tôt le listener est appelé.**

C'est le sens que l'intuition inverse volontiers : la priority `-255` s'exécute
*après* la priority `0`, et la priority `255` *avant*. À priority égale, les
listeners sont appelés dans l'ordre où ils ont été ajoutés au dispatcher.

`php bin/console debug:event-dispatcher <événement>` donne l'ordre réel.

## Propagation

Un listener peut appeler `$event->stopPropagation()`. Les listeners du **même
event** qui n'ont pas encore été appelés ne le seront pas. Cela n'interrompt
ni le traitement de la Request, ni les autres events — c'est une erreur
d'interprétation fréquente. `isPropagationStopped()` permet de le constater
après coup.

**Mais tout object n'est pas arrêtable.** `callListeners()` ne consulte
`isPropagationStopped()` que si l'event implémente
`StoppableEventInterface`, l'interface PSR-14. Un object quelconque dispatché
sans elle voit **tous** ses listeners appelés, quoi qu'ils fassent. En pratique
`Symfony\Contracts\EventDispatcher\Event` l'implémente, et `KernelEvent`
l'étend : les huit events du kernel sont donc arrêtables. C'est l'event
maison bâti sur un simple object qui ne l'est pas.

## Ce que fait `dispatch()`, exactement

```php
public function dispatch(object $event, ?string $eventName = null): object
```

Trois choses méritent d'être lues dans cette signature.

**Le nom est facultatif, et son défaut est le nom de la class.** `$eventName
??= $event::class`. Dispatcher un `OrderPlacedEvent` sans second argument
l'enregistre donc sous `App\Event\OrderPlacedEvent`, pas sous une string
pointée. Un listener branché sur `'order.placed'` ne sera jamais appelé.

**L'event est retourné.** D'où l'idiome
`$event = $dispatcher->dispatch(new OrderPlacedEvent($order));` — l'object revient
enrichi par les listeners.

**Chaque listener reçoit trois arguments**, pas un :
`$listener($event, $eventName, $this)`. Le deuxième est utile quand une même
method est branchée sur plusieurs events ; le troisième est le dispatcher
lui-même.

## `KernelEvents::ALIASES`

La class ne porte pas que les huit constantes : elle porte aussi une table
`ALIASES` qui associe **chaque class d'event à son nom**.

```php
RequestEvent::class  => self::REQUEST,
ResponseEvent::class => self::RESPONSE,
// …
```

C'est ce que lit `RegisterListenersPass`, et c'est ce qui permet d'écrire
`#[AsEventListener]` **sans** parameter `event` : le nom est déduit du type de
l'argument de la method. Retirer le type **fait échouer la compilation du
container** — exécuté : `InvalidArgumentException`, *must define the "event"
attribute*. Un type union, lui, abonne le listener à chacun des events.

## Le reste de l'API

`addListener()` et `addSubscriber()` ont leurs symétriques, `removeListener()`
et `removeSubscriber()`. Deux methods d'introspection complètent l'ensemble :
`hasListeners($nom)` et `getListenerPriority($nom, $listener)`, qui rend la
priority réelle ou `null` si le listener n'est pas enregistré.

L'ordre, lui, est établi par un `krsort()` sur les priorities — un tri **par key
décroissante**, ce qui est la formulation la plus directe de « plus le nombre
est grand, plus tôt ».

## Le catalogue du kernel

Tous les events du kernel étendent `KernelEvent`, qui donne `getRequest()`,
`getKernel()`, `getRequestType()` et `isMainRequest()`.

| Constante | Nom | Class d'event | Pouvoir spécifique |
|---|---|---|---|
| `REQUEST` | `kernel.request` | `RequestEvent` | `setResponse()` — court-circuite |
| `CONTROLLER` | `kernel.controller` | `ControllerEvent` | `setController()` |
| `CONTROLLER_ARGUMENTS` | `kernel.controller_arguments` | `ControllerArgumentsEvent` | `setArguments()` |
| `VIEW` | `kernel.view` | `ViewEvent` | `getControllerResult()`, `setResponse()` |
| `RESPONSE` | `kernel.response` | `ResponseEvent` | `getResponse()`, `setResponse()` |
| `FINISH_REQUEST` | `kernel.finish_request` | `FinishRequestEvent` | *aucun* — restauration d'état |
| `TERMINATE` | `kernel.terminate` | `TerminateEvent` | *aucun* — trop tard |
| `EXCEPTION` | `kernel.exception` | `ExceptionEvent` | `setThrowable()`, `setResponse()` |

`FINISH_REQUEST` et `TERMINATE` ne permettent pas de modifier la Response : le
premier intervient après `kernel.response`, le second après l'envoi.

## Pièges d'examen

- Priority haute = **tôt** ; une priority négative est parfaitement valide.
- `stopPropagation()` n'arrête qu'un event, pas la Request.
- `getSubscribedEvents()` est **statique**.
- `kernel.terminate` ne peut plus rien changer à la Response.
- Les constants sont sur `KernelEvents` ; leurs valeurs sont les strings
  `kernel.*`.
- `dispatch()` sans nom explicite utilise le **nom de la class**, pas une
  string pointée.
- Un event qui n'implémente pas `StoppableEventInterface` ne peut **pas**
  être arrêté — `stopPropagation()` n'existe même pas dessus.
- `#[AsEventListener]` sans `event` dépend du **type** de l'argument, via
  `KernelEvents::ALIASES`.

## Tips d'examen

**`krsort` est le mot à retenir pour la priority.** Tri par key décroissante :
255 avant 0 avant −255. Si le sens s'échappe, le nom de la function le rend.

**Deux questions pour classer un event.** La réponse existe-t-elle déjà ?
(oui → on ne peut que la modifier). Est-elle déjà partie ? (oui → on ne peut
plus rien).

## Points clés

- Listener et subscriber diffèrent par qui déclare l'abonnement, pas par l'effet.
- Priority entière, défaut `0`, décroissante dans l'ordre d'appel.
- Huit events de kernel, tous dérivés de `KernelEvent`.
- Chaque event a un pouvoir propre : ce que l'un permet, l'autre l'interdit.
- `dispatch(object $event, ?string $eventName = null): object` — nom par défaut
  = class, event retourné, listener appelé avec trois arguments.
- L'arrêt de propagation suppose `StoppableEventInterface`.
- `KernelEvents::ALIASES` relie class et nom ; c'est le socle de
  `#[AsEventListener]` sans `event`.

## Sources officielles

- [Component EventDispatcher](https://github.com/symfony/symfony-docs/blob/8.0/components/event_dispatcher.rst)
- [KernelEvents (branche 8.0)](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/KernelEvents.php)
- [`EventDispatcher` (le code du dispatch)](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/EventDispatcher/EventDispatcher.php)
- [`Event` des contrats, et `StoppableEventInterface`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Contracts/EventDispatcher/Event.php)
