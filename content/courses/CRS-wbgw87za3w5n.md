---
id: CRS-wbgw87za3w5n
official_item: OIT-s601ppsyb9f7
title: "EventDispatcher"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-02"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/components/event_dispatcher.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/components/event_dispatcher.rst"
    anchor: "connecting-listeners"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    verified_at: "2026-09-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/EventDispatcher/DependencyInjection/RegisterListenersPass.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/EventDispatcher/DependencyInjection/RegisterListenersPass.php"
    branch: "8.0"
    symbol_or_lines: "default method on + camelized event name; __invoke fallback"
    verified_at: "2026-10-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/EventDispatcher/EventDispatcher.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/EventDispatcher/EventDispatcher.php"
    branch: "8.0"
    symbol_or_lines: "dispatch(object $event, ?string $eventName = null): object"
    verified_at: "2026-10-02"
outcome_sections:
  OUT-c43j65gh5pth: "La priority, et son départage"
  OUT-m0svgqcarref: "Listener ou subscriber"
  OUT-gawacg0bt5r5: "Distribuer"
---

## Objectif

Brancher du code sur un event sans modifier celui qui le déclenche. C'est le
mécanisme qui rend Symfony extensible : le kernel ne connaît pas vos listeners, il
publie des events.

## Périmètre

Deux voisins possèdent leurs propres catalogues et ne sont pas redits ici : les
**events du kernel HTTP** appartiennent à *Symfony Architecture*, les
**events de la console** au sujet Console. Cette page porte sur le
dispatcher lui-même, quel que soit l'event.

L'object `Event` a son propre item officiel ; il est traité à part.

## Prérequis

Les services et l'autoconfiguration.

## Le contrat

Un event porte un **nom unique**. Le dispatcher tient un registre
de listeners par nom, et quand l'event est distribué, il notifie tous ceux
qui y sont inscrits.

```php
$dispatcher->addListener('acme.foo.action', [$listener, 'onFooAction'], 10);
```

`addListener()` prend jusqu'à **trois** arguments : le nom, un callable PHP, et
une **priority** optionnelle.

## La priority, et son départage

La priority est un int **positif ou négatif**, `0` par défaut.

**Plus le nombre est élevé, plus le listener est appelé tôt.** C'est le point que
l'examen teste : l'intuition « priority 1 avant priority 10 » est fausse.

À **priority égale**, les listeners s'exécutent dans **l'ordre où ils ont été
ajoutés** au dispatcher. Exécuté (EventDispatcher 8.0) avec quatre listeners
ajoutés dans l'ordre `0`, `0`, `10`, `-5` : `10`, puis les deux `0` dans leur
ordre d'ajout, puis `-5`.

## Listener ou subscriber

Deux façons de s'inscrire, interchangeables.

L'**listener** est branché de l'extérieur — configuration, ou attribute
`#[AsEventListener]` :

```php
#[AsEventListener]
final class MyListener
{
    public function __invoke(CustomEvent $event): void {}
}
```

L'attribute se répète pour configurer plusieurs methods. Sa property `method`
est **optionnelle** : par défaut, c'est `on` suivi du nom de l'event en
capitale initiale — un listener de `foo` appellera `onFoo()`.

Lu dans `RegisterListenersPass` (8.0) et exécuté :

| Cas | Method appelée |
|---|---|
| `event: 'acme.foo_bar'`, `onAcmeFooBar()` présente | `onAcmeFooBar()` — points et soulignés retirés |
| `event: 'acme.other'`, pas de `onAcmeOther()` | `__invoke()`, en repli |
| `#[AsEventListener]` sans `event`, `__invoke(CustomEvent $e)` | `__invoke()`, sur `CustomEvent::class` déduit du type |

Sans method correspondante ni `__invoke()`, la compilation du container
échoue — lu dans le même file : *None of the … or "__invoke" methods exist*.

L'**subscriber** se déclare lui-même, par `EventSubscriberInterface` et son unique
method statique `getSubscribedEvents()` :

```php
public static function getSubscribedEvents(): array
{
    return [
        KernelEvents::RESPONSE => [
            ['onKernelResponsePre', 10],
            ['onKernelResponsePost', -10],
        ],
        OrderPlacedEvent::class => 'onPlacedOrder',
    ];
}
```

Un subscriber peut donc inscrire **plusieurs methods sur un même event**, avec
des priorities distinctes.

Le choix relève surtout du goût, mais la documentation donne un avantage à
chacun : **le subscriber se réutilise plus facilement**, parce que la connaissance des
events vit dans la class et non dans une definition de service — c'est
pourquoi Symfony l'utilise en interne. **Le listener est plus souple**, parce
qu'un bundle peut l'activer ou non selon une valeur de configuration.

## Distribuer

```php
$dispatcher->dispatch($event);
$dispatcher->dispatch($event, 'foo.event');
```

`dispatch()` prend l'object event et, **optionnellement**, un nom. Si le nom
est omis, **c'est la class de l'object qui sert de nom** — d'où l'écriture
courante `OrderPlacedEvent::class` comme key d'abonnement. Il **rend l'object
event** lui-même — exécuté : le même object, modifié par les listeners.

## Ce que reçoit le listener

Le dispatcher passe toujours **trois** choses : l'event distribué, le **nom**
de l'event, et une **référence à lui-même**. C'est ce qui permet à un
listener de distribuer d'autres events, de les chaîner, ou de charger
paresseusement d'autres listeners.

## Pièges d'examen

**Priority haute = appelé en premier**, et les valeurs négatives sont permises.

**À priority égale, l'ordre d'ajout décide.**

**`dispatch()` sans nom utilise la class de l'event.**

**`method` est optionnelle sur `#[AsEventListener]`** ; le défaut est `on` +
nom de l'event, et `__invoke()` sert de repli.

**Un subscriber peut inscrire plusieurs methods sur un même event.**

## Points clés

- Un nom, des listeners, un dispatcher qui notifie.
- `addListener(nom, appelable, priorité)` ; priority haute d'abord, ordre
  d'ajout à égalité.
- Listener externe (`#[AsEventListener]`) contre subscriber autodéclaré
  (`getSubscribedEvents()`).
- Subscriber plus réutilisable, listener plus souple à activer.
- `dispatch($event)` sans nom : la class fait office de nom.

## Sources officielles

- [The EventDispatcher Component](https://github.com/symfony/symfony-docs/blob/8.0/components/event_dispatcher.rst)
- [Events and Event Listeners](https://github.com/symfony/symfony-docs/blob/8.0/event_dispatcher.rst)
- [`RegisterListenersPass`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/EventDispatcher/DependencyInjection/RegisterListenersPass.php)
