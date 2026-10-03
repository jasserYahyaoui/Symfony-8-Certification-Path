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
---

## Objectif

Brancher du code sur un événement sans modifier celui qui le déclenche. C'est le
mécanisme qui rend Symfony extensible : le noyau ne connaît pas vos écouteurs, il
publie des événements.

## Périmètre

Deux voisins possèdent leurs propres catalogues et ne sont pas redits ici : les
**événements du noyau HTTP** appartiennent à *Symfony Architecture*, les
**événements de la console** au sujet Console. Cette page porte sur le
répartiteur lui-même, quel que soit l'événement.

L'objet `Event` a son propre item officiel ; il est traité à part.

## Prérequis

Les services et l'autoconfiguration.

## Le contrat

Un événement porte un **nom unique**. Le répartiteur tient un registre
d'écouteurs par nom, et quand l'événement est distribué, il notifie tous ceux
qui y sont inscrits.

```php
$dispatcher->addListener('acme.foo.action', [$listener, 'onFooAction'], 10);
```

`addListener()` prend jusqu'à **trois** arguments : le nom, un appelable PHP, et
une **priorité** optionnelle.

## La priorité, et son départage

La priorité est un entier **positif ou négatif**, `0` par défaut.

**Plus le nombre est élevé, plus l'écouteur est appelé tôt.** C'est le point que
l'examen teste : l'intuition « priorité 1 avant priorité 10 » est fausse.

À **priorité égale**, les écouteurs s'exécutent dans **l'ordre où ils ont été
ajoutés** au répartiteur. Exécuté (EventDispatcher 8.0) avec quatre écouteurs
ajoutés dans l'ordre `0`, `0`, `10`, `-5` : `10`, puis les deux `0` dans leur
ordre d'ajout, puis `-5`.

## Écouteur ou abonné

Deux façons de s'inscrire, interchangeables.

L'**écouteur** est branché de l'extérieur — configuration, ou attribut
`#[AsEventListener]` :

```php
#[AsEventListener]
final class MyListener
{
    public function __invoke(CustomEvent $event): void {}
}
```

L'attribut se répète pour configurer plusieurs méthodes. Sa propriété `method`
est **optionnelle** : par défaut, c'est `on` suivi du nom de l'événement en
capitale initiale — un écouteur de `foo` appellera `onFoo()`.

Lu dans `RegisterListenersPass` (8.0) et exécuté :

| Cas | Méthode appelée |
|---|---|
| `event: 'acme.foo_bar'`, `onAcmeFooBar()` présente | `onAcmeFooBar()` — points et soulignés retirés |
| `event: 'acme.other'`, pas de `onAcmeOther()` | `__invoke()`, en repli |
| `#[AsEventListener]` sans `event`, `__invoke(CustomEvent $e)` | `__invoke()`, sur `CustomEvent::class` déduit du type |

Sans méthode correspondante ni `__invoke()`, la compilation du conteneur
échoue — lu dans le même fichier : *None of the … or "__invoke" methods exist*.

L'**abonné** se déclare lui-même, par `EventSubscriberInterface` et son unique
méthode statique `getSubscribedEvents()` :

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

Un abonné peut donc inscrire **plusieurs méthodes sur un même événement**, avec
des priorités distinctes.

Le choix relève surtout du goût, mais la documentation donne un avantage à
chacun : **l'abonné se réutilise plus facilement**, parce que la connaissance des
événements vit dans la classe et non dans une définition de service — c'est
pourquoi Symfony l'utilise en interne. **L'écouteur est plus souple**, parce
qu'un bundle peut l'activer ou non selon une valeur de configuration.

## Distribuer

```php
$dispatcher->dispatch($event);
$dispatcher->dispatch($event, 'foo.event');
```

`dispatch()` prend l'objet événement et, **optionnellement**, un nom. Si le nom
est omis, **c'est la classe de l'objet qui sert de nom** — d'où l'écriture
courante `OrderPlacedEvent::class` comme clé d'abonnement. Il **rend l'objet
événement** lui-même — exécuté : le même objet, modifié par les écouteurs.

## Ce que reçoit l'écouteur

Le répartiteur passe toujours **trois** choses : l'événement distribué, le **nom**
de l'événement, et une **référence à lui-même**. C'est ce qui permet à un
écouteur de distribuer d'autres événements, de les chaîner, ou de charger
paresseusement d'autres écouteurs.

## Pièges d'examen

**Priorité haute = appelé en premier**, et les valeurs négatives sont permises.

**À priorité égale, l'ordre d'ajout décide.**

**`dispatch()` sans nom utilise la classe de l'événement.**

**`method` est optionnelle sur `#[AsEventListener]`** ; le défaut est `on` +
nom de l'événement, et `__invoke()` sert de repli.

**Un abonné peut inscrire plusieurs méthodes sur un même événement.**

## Points clés

- Un nom, des écouteurs, un répartiteur qui notifie.
- `addListener(nom, appelable, priorité)` ; priorité haute d'abord, ordre
  d'ajout à égalité.
- Écouteur externe (`#[AsEventListener]`) contre abonné autodéclaré
  (`getSubscribedEvents()`).
- Abonné plus réutilisable, écouteur plus souple à activer.
- `dispatch($event)` sans nom : la classe fait office de nom.

## Sources officielles

- [The EventDispatcher Component](https://github.com/symfony/symfony-docs/blob/8.0/components/event_dispatcher.rst)
- [Events and Event Listeners](https://github.com/symfony/symfony-docs/blob/8.0/event_dispatcher.rst)
- [`RegisterListenersPass`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/EventDispatcher/DependencyInjection/RegisterListenersPass.php)
