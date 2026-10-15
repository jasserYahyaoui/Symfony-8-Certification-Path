---
id: CRS-fhb1cbhnyaxx
official_item: OIT-8xcczyjyyanz
title: "Event"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-02"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/components/event_dispatcher.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/components/event_dispatcher.rst"
    anchor: "event_dispatcher-event-propagation"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    verified_at: "2026-09-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/EventDispatcher/GenericEvent.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/EventDispatcher/GenericEvent.php"
    branch: "8.0"
    symbol_or_lines: "implements \\ArrayAccess, \\IteratorAggregate"
    verified_at: "2026-10-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Contracts/EventDispatcher/Event.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Contracts/EventDispatcher/Event.php"
    branch: "8.0"
    symbol_or_lines: "stopPropagation(); isPropagationStopped()"
    verified_at: "2026-10-02"
outcome_sections:
  OUT-90fn81pm7q7x: "Une class de base délibérément pauvre"
  OUT-ck4dc8hb0b3j: "Arrêter la propagation"
  OUT-2e82py415svq: "`GenericEvent`, quand une sous-classe ne se justifie pas"
---

## Objectif

Comprendre ce qui circule entre le code qui déclenche et le code qui écoute :
l'object event. C'est lui qui porte la donnée, et lui seul qui peut
interrompre la string.

## Périmètre

Le **dispatcher** — inscription, priorities, listeners contre subscribers — a son
propre item officiel et n'est pas redit ici. Cette page porte sur l'object.

## Prérequis

Le dispatcher d'events.

## Une class de base délibérément pauvre

`Symfony\Contracts\EventDispatcher\Event` est **volontairement minimale**. Elle
n'offre guère qu'un mécanisme d'arrêt de propagation, précisément pour laisser
créer par inheritance des objects spécifiques à chaque API.

Quand un event doit transporter de la donnée, on écrit une sous-classe :

```php
final class OrderPlacedEvent extends Event
{
    public function __construct(private Order $order) {}

    public function getOrder(): Order
    {
        return $this->order;
    }
}
```

Chaque listener accède alors à la command par `getOrder()`. C'est le pattern
qu'emploie le framework lui-même : `ResponseEvent`, par exemple, expose de quoi
lire **et remplacer** la Response.

## Arrêter la propagation

```php
public function onPlacedOrder(OrderPlacedEvent $event): void
{
    $event->stopPropagation();
}
```

Les listeners de cet event **qui n'ont pas encore été appelés** ne le seront
pas. Ceux déjà exécutés ne sont évidemment pas défaits. Exécuté avec des
listeners de priorities `10`, `5` (qui arrête), `0` et `-5` : seuls `10` et `5`
tournent.

Deux conséquences pratiques :

- l'arrêt dépend entièrement de **l'ordre d'appel**, donc des priorities ;
- côté déclencheur, `isPropagationStopped()` rend un bool permettant de
  savoir, après le `dispatch()`, si la propagation a été interrompue.

```php
$dispatcher->dispatch($event, 'foo.event');

if ($event->isPropagationStopped()) {
    // ...
}
```

## `GenericEvent`, quand une sous-classe ne se justifie pas

Pour qui veut un seul object event dans toute l'application,
`Symfony\Component\EventDispatcher\GenericEvent` suit le pattern observateur
classique : il encapsule un **sujet**, plus des **arguments** optionnels.

```php
$event = new GenericEvent($subject, ['key' => 'value']);
$dispatcher->dispatch($event, 'foo');

$event->getSubject();
$event->getArgument('key');
$event->hasArgument('key');
```

Il ajoute `getSubject()`, `setArgument()`, `setArguments()`, `getArgument()`,
`getArguments()` et `hasArgument()` à la class de base, et implémente
**`ArrayAccess`** sur ses arguments — d'où un accès par crochets — ainsi
qu'**`IteratorAggregate`** : un `foreach` parcourt ses arguments. Exécuté :
`$event['k']` rend `v`, et l'itération rend la key `k`.

Le compromis est clair : `GenericEvent` évite d'écrire une class, au prix d'une
donnée non typée à laquelle on accède par key. Une sous-classe dédiée donne un
contrat explicite, et c'est ce que fait le framework partout où l'event
compte.

## Pièges d'examen

**La class de base ne transporte aucune donnée** — il faut hériter, ou utiliser
`GenericEvent`.

**`stopPropagation()` n'annule rien** : elle empêche seulement les listeners
suivants d'être appelés.

**L'effet de l'arrêt dépend des priorities**, puisqu'il dépend de l'ordre.

**`isPropagationStopped()` se lit après le `dispatch()`**, côté déclencheur.

**`GenericEvent` implémente `ArrayAccess`** sur ses arguments, pas sur son sujet —
et `IteratorAggregate`, sur les mêmes arguments.

## Points clés

- `Event` est minimale par conception : arrêt de propagation, rien de plus.
- Transporter de la donnée = écrire une sous-classe exposant des accessors.
- `stopPropagation()` coupe la suite ; `isPropagationStopped()` le révèle.
- `GenericEvent` : un sujet, des arguments, `ArrayAccess`.

## Sources officielles

- [The EventDispatcher Component](https://github.com/symfony/symfony-docs/blob/8.0/components/event_dispatcher.rst)
- [The Generic Event Object](https://github.com/symfony/symfony-docs/blob/8.0/components/event_dispatcher/generic_event.rst)
- [`GenericEvent`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/EventDispatcher/GenericEvent.php)
