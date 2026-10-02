---
id: CRS-3pbkhqmw83b2
official_item: OIT-16at9vwzww43
title: "Events"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-02"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/messenger.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/messenger.rst"
    branch: "8.0"
    symbol_or_lines: "Messenger events, Worker* and SendMessageToTransportsEvent"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Messenger/Middleware/SendMessageMiddleware.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/Middleware/SendMessageMiddleware.php"
    branch: "8.0"
    symbol_or_lines: "handle() — SendMessageToTransportsEvent only when the envelope has senders"
    verified_at: "2026-10-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Messenger/Worker.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/Worker.php"
    branch: "8.0"
    symbol_or_lines: "handleMessage() — WorkerMessageReceivedEvent::shouldHandle() checked before dispatch"
    verified_at: "2026-10-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Messenger/EventListener/SendFailedMessageForRetryListener.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/EventListener/SendFailedMessageForRetryListener.php"
    branch: "8.0"
    symbol_or_lines: "getSubscribedEvents() — WorkerMessageFailedEvent at priority 100"
    verified_at: "2026-10-02"
---

## Objectif

Réagir au cycle de vie d'un message sans écrire de middleware, savoir quel
événement porte quelle information — et ce qu'un listener peut y changer. Le
mécanisme d'abonnement lui-même est traité dans *Event dispatcher and kernel
events*.

## Événement ou middleware

La frontière n'est pas « observer contre modifier » : plusieurs événements
Messenger se modifient. Elle tient à **où** et **quand** ils sont émis.

- La **chaîne de middleware** est traversée à chaque `dispatch()`, message
  synchrone compris.
- Les **événements** ne sont émis qu'à deux endroits : à l'envoi vers un
  transport, et dans un worker.

Exécuté avec Messenger 8.0.15, un message **non routé**, traité pendant
`dispatch()` : le middleware et le handler s'exécutent, **aucun** événement
Messenger n'est émis. Lu dans `SendMessageMiddleware` :
`SendMessageToTransportsEvent` n'est émis que si le message a des *senders*.

Pour agir sur **tous** les messages, il faut donc un middleware ; pour agir à
l'envoi vers un transport ou autour d'un worker, un listener suffit.

## Le catalogue

La documentation 8.0 en liste dix :

| Événement | Quand |
|---|---|
| `SendMessageToTransportsEvent` | avant l'envoi vers les transports |
| `MessageSentToTransportsEvent` | après l'envoi, une fois même pour plusieurs transports |
| `WorkerStartedEvent` | le worker démarre |
| `WorkerRunningEvent` | à chaque boucle, y compris **à vide** |
| `WorkerMessageReceivedEvent` | un message vient d'être récupéré |
| `WorkerMessageHandledEvent` | un message a été traité avec succès |
| `WorkerMessageFailedEvent` | le handler a levé |
| `WorkerMessageRetriedEvent` | le message est remis en file pour un réessai |
| `WorkerRateLimitedEvent` | le worker est ralenti par un limiteur de débit |
| `WorkerStoppedEvent` | le worker s'arrête |

Le code 8.0 en contient un onzième, `WorkerMessageSkipEvent`, émis par
`messenger:failed:retry` quand on choisit de ne pas rejouer un message.

Ce qui compte est la **forme** : un couple avant/après à l'envoi, et un cycle
complet côté worker — démarrage, boucle, réception, succès ou échec, réessai,
arrêt.

## Ce qu'un listener peut changer

| Événement | Méthode | Effet, exécuté |
|---|---|---|
| `SendMessageToTransportsEvent` | `setEnvelope()` | le stamp ajouté est lu par le worker à la réception |
| `WorkerMessageReceivedEvent` | `shouldHandle(false)` | le handler n'est **jamais** appelé, aucun événement de succès ni d'échec |
| `WorkerMessageReceivedEvent` | `addStamps()` | le stamp est présent sur l'enveloppe qui atteint le handler |

Un listener peut donc **interrompre** le traitement d'un message reçu — ce
qu'on attribue souvent au seul middleware.

## Les deux qui comptent

**`WorkerMessageFailedEvent`** porte l'exception et dit si le message sera
rejoué : `willRetry()`. Mais `willRetry()` est **positionné** par
`SendFailedMessageForRetryListener`, à la priorité 100. Exécuté, un message qui
sera réessayé :

| Listener | `willRetry()` |
|---|---|
| priorité 150 | `false` |
| priorité 0 (défaut) | `true` |

Un listener qui passe avant 100 lit toujours `false`. Le transport d'échec, lui,
écoute à -100.

**`WorkerRunningEvent`** est émis à **chaque itération**, même quand la file est
vide, et `isWorkerIdle()` le signale. Exécuté, file vide, `--time-limit=2
--sleep=0.5` : 4 émissions, toutes à vide. Un traitement coûteux placé là
s'exécute en boucle.

## Un exemple

```php
#[AsEventListener]
public function onFailed(WorkerMessageFailedEvent $event): void
{
    if (!$event->willRetry()) {
        $this->alerting->notify($event->getThrowable());
    }
}
```

La priorité par défaut, 0, place ce listener **après** la décision de réessai.

## Pièges d'examen

**`WorkerRunningEvent` se déclenche à vide** : y placer du travail lourd le fait
tourner en permanence.

**`WorkerMessageFailedEvent` n'est pas un abandon** : `willRetry()` dit s'il
reste des tentatives — à condition d'écouter après la priorité 100.

**Un message non routé n'émet aucun événement Messenger** : seul un middleware
le voit.

**Un listener peut empêcher le traitement** : `shouldHandle(false)` sur
`WorkerMessageReceivedEvent`.

## Points clés

- Dix événements documentés, onze dans le code : deux à l'envoi, le reste
  autour du worker.
- Les événements ne couvrent que l'envoi vers un transport et le worker.
- `willRetry()` n'est fiable qu'après la priorité 100.
- `WorkerRunningEvent` est émis même à vide.

## Sources officielles

- [Messenger, « Messenger Events »](https://github.com/symfony/symfony-docs/blob/8.0/messenger.rst)
- [Messenger 8.0, `SendMessageMiddleware`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/Middleware/SendMessageMiddleware.php)
- [Messenger 8.0, `Worker`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/Worker.php)
- [Messenger 8.0, `SendFailedMessageForRetryListener`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/EventListener/SendFailedMessageForRetryListener.php)
