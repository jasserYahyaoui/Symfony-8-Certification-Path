---
id: CRS-2hpcy2rscq7m
official_item: OIT-rd9b27fkb72r
title: "Event dispatcher and kernel events"
content_level: DEEP
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-26"
format: pedagogical
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

## L'essentiel

Le component EventDispatcher appelle, pour un event, ses listeners par
**priority décroissante**. Listener et subscriber ne diffèrent que par qui
déclare l'abonnement. `stopPropagation()` n'arrête que les listeners restants
du **même** event, et seulement sur un event arrêtable. Le kernel dispatche
huit events, chacun avec son pouvoir propre.

## Le schéma

```text
dispatch(object $event, ?string $eventName = null): object   nom par défaut = la class ; l'event est rendu
listener appelé avec ($event, $eventName, $dispatcher)
ordre      krsort() : 255 → 0 (défaut) → −255 ; à égalité, ordre d'ajout
arrêt      stopPropagation() : listeners restants de CET event ; il faut StoppableEventInterface
kernel     request → controller → controller_arguments → [view] → response → finish_request → terminate
           exception : à tout moment ; KernelEvents::ALIASES relie class d'event et nom
```

## L'exemple : un subscriber

```php
final class OrderSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [OrderPlacedEvent::class => [['notify', 10], ['log', -10]]];
    }
}
```

## Listener ou subscriber

Un **listener** est un callable enregistré de l'extérieur
(`addListener($nom, $callable, $priority)`) ; un **subscriber** déclare
lui-même ses events par la method **statique** `getSubscribedEvents()` : un nom
de method, un couple `[méthode, priorité]`, ou une liste de couples. Dans le
framework, un subscriber est autoconfiguré ; un listener se déclare par le tag
`kernel.event_listener` ou `#[AsEventListener]` (`event`, `method`,
`priority`, `dispatcher`). Sans parameter `event`, le nom se déduit du **type**
de l'argument par `KernelEvents::ALIASES` ; retirer le type
fait échouer la compilation du container. Un type union abonne le listener à
chacun des events.

## Priority

Un int, positif ou négatif, `0` par défaut : **plus il est grand, plus tôt** le
listener est appelé — l'ordre est établi par un `krsort()`. À priority égale,
l'ordre d'ajout. `getListenerPriority($nom, $listener)` rend la priority réelle,
ou `null` si le listener n'est pas enregistré ; `debug:event-dispatcher` montre
l'ordre.

## Propagation

`stopPropagation()` saute les listeners **restants du même event** ; ni la
Request ni les autres events ne sont interrompus. `callListeners()` ne consulte
`isPropagationStopped()` que si l'event implémente `StoppableEventInterface`
(PSR-14) : un object maison sans elle voit **tous** ses listeners appelés.
`Symfony\Contracts\EventDispatcher\Event` l'implémente, donc les events du
kernel sont arrêtables.

## Le catalogue du kernel

Tous étendent `KernelEvent` : `getRequest()`, `getKernel()`,
`getRequestType()`, `isMainRequest()`.

| Nom | Class d'event | Pouvoir spécifique |
|---|---|---|
| `kernel.request` | `RequestEvent` | `setResponse()` — court-circuite |
| `kernel.controller` | `ControllerEvent` | `setController()` |
| `kernel.controller_arguments` | `ControllerArgumentsEvent` | `setArguments()` |
| `kernel.view` | `ViewEvent` | `getControllerResult()`, `setResponse()` |
| `kernel.response` | `ResponseEvent` | `getResponse()`, `setResponse()` |
| `kernel.finish_request` | `FinishRequestEvent` | *aucun* — restauration d'état |
| `kernel.terminate` | `TerminateEvent` | *aucun* — la Response est partie |
| `kernel.exception` | `ExceptionEvent` | `setThrowable()`, `setResponse()` |

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

## Application

- Transformer en JSON les arrays rendus par les controllers → un listener de
  `kernel.view`.
- Ajouter un header à toutes les Responses → `kernel.response`, jamais
  `kernel.terminate`.

## QCM d'entraînement

**Question 1.** `$dispatcher->dispatch(new OrderPlacedEvent())` est appelé sans
second argument ; un listener attend `'order.placed'`. Est-il appelé ?

- **A.** Oui : le nom est déduit en snake_case
- **B.** Oui, si la class porte un attribute de nom
- **C.** Non : il part sous le nom de sa class
- **D.** Non : `dispatch()` exige un nom

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Le nom par défaut est le nom complet de la class ; seul
un listener branché sur `OrderPlacedEvent::class` est appelé.

</details>

**Question 2.** Combien d'arguments un listener reçoit-il quand le dispatcher
l'appelle ?

- **A.** Un seul : l'event
- **B.** Trois : event, nom, dispatcher
- **C.** Deux : l'event et sa priority
- **D.** Autant que la method en déclare

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Le nom sert à une même method branchée sur plusieurs
events ; le troisième argument est le dispatcher lui-même.

</details>

**Question 3.** Que rend `getListenerPriority($nom, $listener)` pour un listener
qui n'est pas enregistré ?

- **A.** `null`
- **B.** `0`, la priority par défaut
- **C.** `-1`
- **D.** Une `InvalidArgumentException`

<details>
<summary>Correction</summary>

**Bonne réponse : A.** La method ne rend une priority que pour un listener
présent ; sinon, rien — à distinguer du défaut `0`.

</details>

**Question 4.** Que rend `$dispatcher->dispatch($event)` ?

- **A.** `null`, l'appel ne sert qu'à notifier
- **B.** Le nombre de listeners appelés
- **C.** Un bool : la propagation a-t-elle été arrêtée
- **D.** L'object event lui-même

<details>
<summary>Correction</summary>

**Bonne réponse : D.** L'event revient enrichi par les listeners, d'où l'idiome
`$event = $dispatcher->dispatch(new OrderPlacedEvent($order));`.

</details>

## Fiche express

**Si tu dois retenir seulement 7 choses :**

- Listener / subscriber : **qui déclare**, pas ce qui change.
- `getSubscribedEvents()` est **statique**.
- Priority : **haute = tôt**, défaut `0`, négative valide (`krsort`).
- `stopPropagation()` : **cet event seulement**, et s'il est
  `StoppableEventInterface`.
- `dispatch()` : nom par défaut = **class** ; l'event est **rendu**.
- Huit events de kernel ; `terminate` ne change plus rien.
- `#[AsEventListener]` sans `event` : le **type** décide (`ALIASES`).

Moyen mnémotechnique : **« Grand nombre, premier servi »**.

## Sources officielles

- [Component EventDispatcher](https://github.com/symfony/symfony-docs/blob/8.0/components/event_dispatcher.rst)
- [KernelEvents (branche 8.0)](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/KernelEvents.php)
- [`EventDispatcher` (le code du dispatch)](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/EventDispatcher/EventDispatcher.php)
- [`Event` des contrats, et `StoppableEventInterface`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Contracts/EventDispatcher/Event.php)

