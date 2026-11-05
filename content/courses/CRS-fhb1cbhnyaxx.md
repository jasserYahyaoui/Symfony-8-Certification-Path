---
id: CRS-fhb1cbhnyaxx
official_item: OIT-8xcczyjyyanz
title: "Event"
content_level: STANDARD
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
interrompre la propagation.

## L'essentiel

L'object event circule entre le code qui déclenche et les listeners. La class
de base, `Event`, est **volontairement pauvre** : elle sait seulement arrêter
la propagation. Pour transporter une donnée, on écrit une sous-classe, ou l'on
prend `GenericEvent` — un sujet et des arguments, au prix du typage.

## Le schéma

```text
Event (Contracts)      stopPropagation() · isPropagationStopped() — rien d'autre
sous-classe            OrderPlacedEvent : getOrder() (et setX() si les listeners remplacent)
GenericEvent           sujet + arguments ; ArrayAccess et IteratorAggregate sur les ARGUMENTS
propagation            10 → 5 (stopPropagation) ✗ 0 ✗ -5 ; ce qui a tourné reste fait
```

## L'exemple : un achat validé

```php
final class OrderPlacedEvent extends Event
{
    public function __construct(private Order $order) {}

    public function getOrder(): Order
    {
        return $this->order;
    }
}

$event = $dispatcher->dispatch(new OrderPlacedEvent($order));
if ($event->isPropagationStopped()) {
    // un listener a coupé la suite
}
```

## Une class de base délibérément pauvre

`Symfony\Contracts\EventDispatcher\Event` n'offre guère que l'arrêt de
propagation, pour laisser chaque API définir son object. Le framework fait de
même : `ResponseEvent` expose de quoi **lire et remplacer** la Response
(`getResponse()`, `setResponse()`).

## Arrêter la propagation

- `$event->stopPropagation()` : les listeners **pas encore appelés** ne le
  seront pas ; ceux qui ont tourné ne sont pas défaits.
- Listeners de priorities `10`, `5` (qui arrête), `0`, `-5` : seuls `10` et `5`
  tournent. L'effet dépend donc de l'ordre, c'est-à-dire des priorities.
- Côté déclencheur, `isPropagationStopped()` se lit après le `dispatch()`.

## `GenericEvent`, quand une sous-classe ne se justifie pas

```php
$event = new GenericEvent($subject, ['key' => 'value']);
$dispatcher->dispatch($event, 'foo');
$event->getSubject();
$event->getArgument('key');      // ou $event['key']
$event->hasArgument('key');
```

`setArgument()`, `setArguments()`, `getArguments()` complètent l'ensemble.
`ArrayAccess` et `IteratorAggregate` portent sur les **arguments**, pas sur le
sujet. Le compromis : pas de class à écrire, mais une donnée non typée lue par
key.

## Pièges d'examen

**La class de base ne transporte aucune donnée** — il faut hériter, ou utiliser
`GenericEvent`.

**`stopPropagation()` n'annule rien** : elle empêche seulement les listeners
suivants d'être appelés.

**L'effet de l'arrêt dépend des priorities**, puisqu'il dépend de l'ordre.

**`isPropagationStopped()` se lit après le `dispatch()`**, côté déclencheur.

**`GenericEvent` implémente `ArrayAccess`** sur ses arguments, pas sur son sujet —
et `IteratorAggregate`, sur les mêmes arguments.

## Application

- Des listeners qui doivent remplacer l'`Invoice` transportée → une
  sous-classe avec `getInvoice()` et `setInvoice()`.
- Un listener prioritaire qui décide seul → `stopPropagation()`, et une
  priority plus haute que les autres.

## QCM d'entraînement

**Question 1.** `$event = new GenericEvent($user, ['k' => 'v'])`. Que donne
`$event['subject']` ?

- **A.** `$user`, le sujet
- **B.** `null` : la key n'existe pas
- **C.** Une exception
- **D.** Le tableau des arguments

<details>
<summary>Correction</summary>

**Bonne réponse : C.** L'accès par crochets lit les arguments, pas le sujet :
une `InvalidArgumentException`, « Argument "subject" not found. ». Le sujet se lit par `getSubject()`.

</details>

**Question 2.** Même event. Que rend `$event->hasArgument('missing')` ?

- **A.** `false`
- **B.** Une exception : argument inconnu
- **C.** `null`
- **D.** `true` : tout argument est optionnel

<details>
<summary>Correction</summary>

**Bonne réponse : A.** `hasArgument()` teste la présence sans lever ; c'est
`getArgument()`, ou l'accès par crochets, qui lève sur une key absente.

</details>

**Question 3.** Le **dernier** listener appelé, à priority `-5`, appelle
`stopPropagation()`. Quel effet ?

- **A.** Les listeners déjà appelés sont annulés
- **B.** Une exception : trop tard pour arrêter
- **C.** Le dispatcher relance les listeners à l'envers
- **D.** Aucun listener n'est sauté

<details>
<summary>Correction</summary>

**Bonne réponse : D.** Il ne reste personne à sauter ; le drapeau, lui, est
posé : `isPropagationStopped()` vaut `true` après le `dispatch()`.

</details>

**Question 4.** Un listener de `kernel.response` doit remplacer la Response
entière. Que lui fournit `ResponseEvent` ?

- **A.** Rien : la Response est en lecture seule
- **B.** `setResponse()`
- **C.** `stopPropagation()` suivi d'un nouveau `dispatch()`
- **D.** `getRequest()->setResponse()`

<details>
<summary>Correction</summary>

**Bonne réponse : B.** L'event du framework expose de quoi lire et remplacer
sa donnée : c'est le role d'une sous-classe dédiée, que la base n'a pas.

</details>

## Fiche express

**Si tu dois retenir seulement 5 choses :**

- `Event` : **arrêt de propagation**, rien d'autre.
- Donnée à transporter : **sous-classe** avec accessors.
- `stopPropagation()` saute la suite, **n'annule rien**.
- `isPropagationStopped()` **après** le `dispatch()`.
- `GenericEvent` : sujet + arguments ; **`ArrayAccess` sur les arguments**.

Moyen mnémotechnique : **« Porter, arrêter, ou tout mettre dans un sac »**.

## Sources officielles

- [The EventDispatcher Component](https://github.com/symfony/symfony-docs/blob/8.0/components/event_dispatcher.rst)
- [The Generic Event Object](https://github.com/symfony/symfony-docs/blob/8.0/components/event_dispatcher/generic_event.rst)
- [`GenericEvent`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/EventDispatcher/GenericEvent.php)

