---
id: CRS-2hpcy2rscq7m
official_item: OIT-rd9b27fkb72r
title: "Event dispatcher and kernel events"
content_level: DEEP
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
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
---

## Objectif

Maîtriser le composant EventDispatcher — écouteurs, abonnés, priorités,
propagation — et connaître le catalogue des événements du noyau avec ce que
chacun autorise. Le déroulé de la requête est traité dans *Request handling* ;
on s'intéresse ici au **mécanisme** et aux **pouvoirs** de chaque événement.

## Prérequis

Le trajet d'une requête et le vocabulaire requête / réponse.

## Écouteur ou abonné

Deux façons de réagir à un événement, avec la même mécanique dessous.

Un **écouteur** (*listener*) est un appelable enregistré depuis l'extérieur :

```php
$dispatcher->addListener('acme.foo', [$listener, 'onFooAction'], 10);
```

Un **abonné** (*subscriber*) est une classe qui déclare elle-même à quoi elle
s'abonne, via la méthode **statique** `getSubscribedEvents()` de
`EventSubscriberInterface`. Elle retourne un tableau indexé par nom d'événement,
dont la valeur est soit le nom d'une méthode, soit un couple
`[méthode, priorité]`, soit une liste de tels couples.

La différence est de **responsabilité**, pas de fonctionnement : avec un
écouteur, la configuration sait à quoi il s'abonne ; avec un abonné, la classe
le sait, et déplacer la classe suffit à déplacer l'abonnement.

Dans le framework, l'enregistrement passe par le conteneur : un abonné est
détecté par autoconfiguration, un écouteur se déclare par le tag
`kernel.event_listener` ou par l'attribut `#[AsEventListener]`, dont les
paramètres sont `event`, `method`, `priority` et `dispatcher`.

## Priorité

La priorité est un entier, **positif ou négatif**, qui vaut `0` par défaut.

> **Plus le nombre est grand, plus tôt l'écouteur est appelé.**

C'est le sens que l'intuition inverse volontiers : la priorité `-255` s'exécute
*après* la priorité `0`, et la priorité `255` *avant*. À priorité égale, les
écouteurs sont appelés dans l'ordre où ils ont été ajoutés au dispatcher.

`php bin/console debug:event-dispatcher <événement>` donne l'ordre réel.

## Propagation

Un écouteur peut appeler `$event->stopPropagation()`. Les écouteurs du **même
événement** qui n'ont pas encore été appelés ne le seront pas. Cela n'interrompt
ni le traitement de la requête, ni les autres événements — c'est une erreur
d'interprétation fréquente. `isPropagationStopped()` permet de le constater
après coup.

**Mais tout objet n'est pas arrêtable.** `callListeners()` ne consulte
`isPropagationStopped()` que si l'événement implémente
`StoppableEventInterface`, l'interface PSR-14. Un objet quelconque dispatché
sans elle voit **tous** ses écouteurs appelés, quoi qu'ils fassent. En pratique
`Symfony\Contracts\EventDispatcher\Event` l'implémente, et `KernelEvent`
l'étend : les huit événements du noyau sont donc arrêtables. C'est l'événement
maison bâti sur un simple objet qui ne l'est pas.

## Ce que fait `dispatch()`, exactement

```php
public function dispatch(object $event, ?string $eventName = null): object
```

Trois choses méritent d'être lues dans cette signature.

**Le nom est facultatif, et son défaut est le nom de la classe.** `$eventName
??= $event::class`. Dispatcher un `OrderPlacedEvent` sans second argument
l'enregistre donc sous `App\Event\OrderPlacedEvent`, pas sous une chaîne
pointée. Un écouteur branché sur `'order.placed'` ne sera jamais appelé.

**L'événement est retourné.** D'où l'idiome
`$event = $dispatcher->dispatch(new OrderPlacedEvent($order));` — l'objet revient
enrichi par les écouteurs.

**Chaque écouteur reçoit trois arguments**, pas un :
`$listener($event, $eventName, $this)`. Le deuxième est utile quand une même
méthode est branchée sur plusieurs événements ; le troisième est le dispatcher
lui-même.

## `KernelEvents::ALIASES`

La classe ne porte pas que les huit constantes : elle porte aussi une table
`ALIASES` qui associe **chaque classe d'événement à son nom**.

```php
RequestEvent::class  => self::REQUEST,
ResponseEvent::class => self::RESPONSE,
// …
```

C'est ce que lit `RegisterListenersPass`, et c'est ce qui permet d'écrire
`#[AsEventListener]` **sans** paramètre `event` : le nom est déduit du type de
l'argument de la méthode. Retirer le type rend l'attribut inopérant.

## Le reste de l'API

`addListener()` et `addSubscriber()` ont leurs symétriques, `removeListener()`
et `removeSubscriber()`. Deux méthodes d'introspection complètent l'ensemble :
`hasListeners($nom)` et `getListenerPriority($nom, $listener)`, qui rend la
priorité réelle ou `null` si l'écouteur n'est pas enregistré.

L'ordre, lui, est établi par un `krsort()` sur les priorités — un tri **par clé
décroissante**, ce qui est la formulation la plus directe de « plus le nombre
est grand, plus tôt ».

## Le catalogue du noyau

Tous les événements du noyau étendent `KernelEvent`, qui donne `getRequest()`,
`getKernel()`, `getRequestType()` et `isMainRequest()`.

| Constante | Nom | Classe d'événement | Pouvoir spécifique |
|---|---|---|---|
| `REQUEST` | `kernel.request` | `RequestEvent` | `setResponse()` — court-circuite |
| `CONTROLLER` | `kernel.controller` | `ControllerEvent` | `setController()` |
| `CONTROLLER_ARGUMENTS` | `kernel.controller_arguments` | `ControllerArgumentsEvent` | `setArguments()` |
| `VIEW` | `kernel.view` | `ViewEvent` | `getControllerResult()`, `setResponse()` |
| `RESPONSE` | `kernel.response` | `ResponseEvent` | `getResponse()`, `setResponse()` |
| `FINISH_REQUEST` | `kernel.finish_request` | `FinishRequestEvent` | *aucun* — restauration d'état |
| `TERMINATE` | `kernel.terminate` | `TerminateEvent` | *aucun* — trop tard |
| `EXCEPTION` | `kernel.exception` | `ExceptionEvent` | `setThrowable()`, `setResponse()` |

`FINISH_REQUEST` et `TERMINATE` ne permettent pas de modifier la réponse : le
premier intervient après `kernel.response`, le second après l'envoi.

## Pièges d'examen

- Priorité haute = **tôt** ; une priorité négative est parfaitement valide.
- `stopPropagation()` n'arrête qu'un événement, pas la requête.
- `getSubscribedEvents()` est **statique**.
- `kernel.terminate` ne peut plus rien changer à la réponse.
- Les constantes sont sur `KernelEvents` ; leurs valeurs sont les chaînes
  `kernel.*`.
- `dispatch()` sans nom explicite utilise le **nom de la classe**, pas une
  chaîne pointée.
- Un événement qui n'implémente pas `StoppableEventInterface` ne peut **pas**
  être arrêté — `stopPropagation()` n'existe même pas dessus.
- `#[AsEventListener]` sans `event` dépend du **type** de l'argument, via
  `KernelEvents::ALIASES`.

## Tips d'examen

**`krsort` est le mot à retenir pour la priorité.** Tri par clé décroissante :
255 avant 0 avant −255. Si le sens s'échappe, le nom de la fonction le rend.

**Deux questions pour classer un événement.** La réponse existe-t-elle déjà ?
(oui → on ne peut que la modifier). Est-elle déjà partie ? (oui → on ne peut
plus rien).

## Points clés

- Écouteur et abonné diffèrent par qui déclare l'abonnement, pas par l'effet.
- Priorité entière, défaut `0`, décroissante dans l'ordre d'appel.
- Huit événements de noyau, tous dérivés de `KernelEvent`.
- Chaque événement a un pouvoir propre : ce que l'un permet, l'autre l'interdit.
- `dispatch(object $event, ?string $eventName = null): object` — nom par défaut
  = classe, événement retourné, écouteur appelé avec trois arguments.
- L'arrêt de propagation suppose `StoppableEventInterface`.
- `KernelEvents::ALIASES` relie classe et nom ; c'est le socle de
  `#[AsEventListener]` sans `event`.

## Sources officielles

- [Composant EventDispatcher](https://github.com/symfony/symfony-docs/blob/8.0/components/event_dispatcher.rst)
- [KernelEvents (branche 8.0)](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/KernelEvents.php)
- [`EventDispatcher` (le code du dispatch)](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/EventDispatcher/EventDispatcher.php)
- [`Event` des contrats, et `StoppableEventInterface`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Contracts/EventDispatcher/Event.php)
