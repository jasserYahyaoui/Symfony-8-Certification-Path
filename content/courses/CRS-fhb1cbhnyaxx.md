---
id: CRS-fhb1cbhnyaxx
official_item: OIT-8xcczyjyyanz
title: "Event"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-02"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/components/event_dispatcher.rst"
    anchor: "event_dispatcher-event-propagation"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    verified_at: "2026-09-02"
---

## Objectif

Comprendre ce qui circule entre le code qui déclenche et le code qui écoute :
l'objet événement. C'est lui qui porte la donnée, et lui seul qui peut
interrompre la chaîne.

## Périmètre

Le **répartiteur** — inscription, priorités, écouteurs contre abonnés — a son
propre item officiel et n'est pas redit ici. Cette page porte sur l'objet.

## Prérequis

Le répartiteur d'événements.

## Une classe de base délibérément pauvre

`Symfony\Contracts\EventDispatcher\Event` est **volontairement minimale**. Elle
n'offre guère qu'un mécanisme d'arrêt de propagation, précisément pour laisser
créer par héritage des objets spécifiques à chaque API.

Quand un événement doit transporter de la donnée, on écrit une sous-classe :

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

Chaque écouteur accède alors à la commande par `getOrder()`. C'est le motif
qu'emploie le framework lui-même : `ResponseEvent`, par exemple, expose de quoi
lire **et remplacer** la réponse.

## Arrêter la propagation

```php
public function onPlacedOrder(OrderPlacedEvent $event): void
{
    $event->stopPropagation();
}
```

Les écouteurs de cet événement **qui n'ont pas encore été appelés** ne le seront
pas. Ceux déjà exécutés ne sont évidemment pas défaits.

Deux conséquences pratiques :

- l'arrêt dépend entièrement de **l'ordre d'appel**, donc des priorités ;
- côté déclencheur, `isPropagationStopped()` rend un booléen permettant de
  savoir, après le `dispatch()`, si la chaîne a été interrompue.

```php
$dispatcher->dispatch($event, 'foo.event');

if ($event->isPropagationStopped()) {
    // ...
}
```

## `GenericEvent`, quand une sous-classe ne se justifie pas

Pour qui veut un seul objet événement dans toute l'application,
`Symfony\Component\EventDispatcher\GenericEvent` suit le motif observateur
classique : il encapsule un **sujet**, plus des **arguments** optionnels.

```php
$event = new GenericEvent($subject, ['key' => 'value']);
$dispatcher->dispatch($event, 'foo');

$event->getSubject();
$event->getArgument('key');
$event->hasArgument('key');
```

Il ajoute `getSubject()`, `setArgument()`, `setArguments()`, `getArgument()`,
`getArguments()` et `hasArgument()` à la classe de base, et implémente
**`ArrayAccess`** sur ses arguments — d'où un accès par crochets.

Le compromis est clair : `GenericEvent` évite d'écrire une classe, au prix d'une
donnée non typée à laquelle on accède par clé. Une sous-classe dédiée donne un
contrat explicite, et c'est ce que fait le framework partout où l'événement
compte.

## Pièges d'examen

**La classe de base ne transporte aucune donnée** — il faut hériter, ou utiliser
`GenericEvent`.

**`stopPropagation()` n'annule rien** : elle empêche seulement les écouteurs
suivants d'être appelés.

**L'effet de l'arrêt dépend des priorités**, puisqu'il dépend de l'ordre.

**`isPropagationStopped()` se lit après le `dispatch()`**, côté déclencheur.

**`GenericEvent` implémente `ArrayAccess`** sur ses arguments, pas sur son sujet.

## Points clés

- `Event` est minimale par conception : arrêt de propagation, rien de plus.
- Transporter de la donnée = écrire une sous-classe exposant des accesseurs.
- `stopPropagation()` coupe la suite ; `isPropagationStopped()` le révèle.
- `GenericEvent` : un sujet, des arguments, `ArrayAccess`.

## Sources officielles

- [The EventDispatcher Component](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/components/event_dispatcher.rst)
- [The Generic Event Object](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/components/event_dispatcher/generic_event.rst)
