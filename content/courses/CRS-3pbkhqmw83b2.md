---
id: CRS-3pbkhqmw83b2
official_item: OIT-16at9vwzww43
title: "Events"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-11-03"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/messenger.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/messenger.rst"
    branch: "8.0"
    symbol_or_lines: "Messenger events, Worker* and SendMessageToTransportsEvent"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Messenger/Middleware/SendMessageMiddleware.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/Middleware/SendMessageMiddleware.php"
    branch: "8.0"
    symbol_or_lines: "handle() — SendMessageToTransportsEvent only when the envelope has senders"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Messenger/Worker.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/Worker.php"
    branch: "8.0"
    symbol_or_lines: "handleMessage() — WorkerMessageReceivedEvent::shouldHandle() checked before dispatch"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Messenger/EventListener/SendFailedMessageForRetryListener.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/EventListener/SendFailedMessageForRetryListener.php"
    branch: "8.0"
    symbol_or_lines: "getSubscribedEvents() — WorkerMessageFailedEvent at priority 100"
    verified_at: "2026-11-03"
outcome_sections:
  OUT-32c5zqv7br2d: "Le catalogue"
  OUT-syfrg3ycvcq9: "Les deux qui comptent"
  OUT-52jpm5kq981q: "Les deux qui comptent"
  OUT-y0bea85rjt47: "Event ou middleware"
---

## Objectif

Réagir au cycle de vie d'un message sans écrire de middleware, savoir quel
event porte quelle information — et ce qu'un listener peut y changer. Le
mécanisme d'abonnement lui-même est traité dans *Event dispatcher and kernel
events*.

## L'essentiel

Messenger émet des **events** à deux endroits seulement : à l'**envoi** vers un
transport et dans le **worker**. Un message traité en synchrone, non routé,
n'en émet **aucun**. Un listener peut lire l'échec, savoir s'il sera rejoué —
s'il écoute au bon moment —, et même empêcher le traitement d'un message reçu.

## Le schéma

Un message routé puis consommé, `max_retries: 1`, handler qui échoue :

```mermaid
---
title: Les events Messenger, de l'envoi à l'arrêt du worker
---
flowchart TD
  accTitle: L'ordre des events Messenger pour un message envoyé puis consommé
  accDescr: À l'envoi, SendMessageToTransportsEvent puis MessageSentToTransportsEvent, une seule fois même pour plusieurs transports. Dans le worker, WorkerStartedEvent, puis pour chaque message WorkerMessageReceivedEvent. Si un listener appelle shouldHandle(false), le handler n'est pas appelé et aucun event de succès ou d'échec n'est émis. Sinon le handler tourne. En cas de succès, WorkerMessageHandledEvent. En cas d'échec, WorkerMessageFailedEvent ; pendant sa diffusion, le listener de retry, à la priority 100, émet WorkerMessageRetriedEvent si le message sera rejoué. Après chaque message, et à chaque tour à vide, WorkerRunningEvent. À l'arrêt, WorkerStoppedEvent.
  S1["SendMessageToTransportsEvent"] --> S2["MessageSentToTransportsEvent, une fois"]
  S2 -.->|"plus tard, dans le worker"| W1["WorkerStartedEvent"]
  W1 --> R["WorkerMessageReceivedEvent"]
  R -->|"shouldHandle(false)"| RUN
  R --> H{"le handler réussit ?"}
  H -->|"oui"| OK["WorkerMessageHandledEvent"]
  H -->|"non"| F["WorkerMessageFailedEvent"]
  F -->|"rejoué : émis pendant F, à la priority 100"| RT["WorkerMessageRetriedEvent"]
  OK --> RUN["WorkerRunningEvent"]
  F --> RUN
  RUN -->|"file vide : idle"| RUN
  RUN --> STOP(["WorkerStoppedEvent"])
```

## L'exemple

```php
#[AsEventListener]
public function onFailed(WorkerMessageFailedEvent $event): void
{
    if (!$event->willRetry()) {      // juste à la priority par défaut, 0
        $this->alerting->notify($event->getThrowable());
    }
}
```

Au premier échec d'un message qui sera rejoué, `willRetry()` vaut `true` : pas
d'alerte. Au dernier, `false` : l'alerte part.

## Event ou middleware

La frontière n'est pas « observer contre modifier » : plusieurs events se
modifient. Elle tient à **où** ils sont émis.

- La **chaîne de middleware** est traversée à chaque `dispatch()`, message
  synchrone compris.
- Les **events** ne sont émis qu'à l'envoi vers un transport et dans un worker :
  `SendMessageToTransportsEvent` exige des *senders*.

Pour agir sur **tous** les messages : un middleware. À l'envoi ou autour d'un
worker : un listener suffit.

## Le catalogue

La documentation 8.0 en liste dix :

| Event | Quand |
|---|---|
| `SendMessageToTransportsEvent` | avant l'envoi vers les transports |
| `MessageSentToTransportsEvent` | après l'envoi |
| `WorkerStartedEvent` | le worker démarre |
| `WorkerRunningEvent` | à chaque loop, y compris **à vide** |
| `WorkerMessageReceivedEvent` | un message vient d'être récupéré |
| `WorkerMessageHandledEvent` | un message a été traité avec succès |
| `WorkerMessageFailedEvent` | le handler a levé |
| `WorkerMessageRetriedEvent` | le message est remis en file pour un retry |
| `WorkerRateLimitedEvent` | le worker est ralenti par un limiteur de débit |
| `WorkerStoppedEvent` | le worker s'arrête |

- Le code 8.0 en contient un onzième, `WorkerMessageSkipEvent`, émis par
  `messenger:failed:retry` quand on choisit de ne pas rejouer un message.
- Un message routé vers **deux** transports donne **un seul**
  `MessageSentToTransportsEvent` : « the event is dispatched only once ».

## Ce qu'un listener peut changer

| Event | Method | Effet |
|---|---|---|
| `SendMessageToTransportsEvent` | `setEnvelope()` | le stamp ajouté voyage jusqu'au worker |
| `WorkerMessageReceivedEvent` | `shouldHandle(false)` | handler **jamais** appelé, ni succès ni échec |
| `WorkerMessageReceivedEvent` | `addStamps()` | le stamp atteint le handler |

Après `shouldHandle(false)`, l'event suivant est directement
`WorkerRunningEvent`.

## Les deux qui comptent

**`WorkerMessageFailedEvent`** porte l'exception et `willRetry()`. Mais
`willRetry()` est **positionné** par `SendFailedMessageForRetryListener`, à la
priority 100 :

| Listener | `willRetry()` d'un message qui sera rejoué |
|---|---|
| priority 150 | `false` |
| priority 0 (défaut) | `true` |

Ce même listener émet `WorkerMessageRetriedEvent` **pendant** la diffusion de
l'échec : un listener de priority 0 sur l'échec écrit **après** celui du retry.
Le failure transport écoute à -100.

**`WorkerRunningEvent`** est émis à **chaque itération**, même file vide ;
`isWorkerIdle()` le signale. Un traitement coûteux placé là tourne en boucle.

## Pièges d'examen

**`WorkerRunningEvent` se déclenche à vide** : y placer du travail lourd le fait
tourner en permanence.

**`WorkerMessageFailedEvent` n'est pas un abandon** : `willRetry()` dit s'il
reste des tentatives — à condition d'écouter après la priority 100.

**Un message non routé n'émet aucun event Messenger** : seul un middleware
le voit.

**Un listener peut empêcher le traitement** : `shouldHandle(false)` sur
`WorkerMessageReceivedEvent`.

## Application

- L'alerte part au premier timeout d'un message qui réussit au retry → tester
  `!willRetry()`, à la priority par défaut.
- Les messages d'un client en maintenance doivent attendre sans échouer →
  `shouldHandle(false)` sur `WorkerMessageReceivedEvent`.

## QCM d'entraînement

**Question 1.** Un message est routé vers `async` et `audit`. Combien de
`MessageSentToTransportsEvent` sont émis au `dispatch()` ?

- **A.** Deux : un par transport
- **B.** Un seul, pour les deux
- **C.** Aucun : il est émis par le worker
- **D.** Trois : avant, pendant et après l'envoi

<details>
<summary>Correction</summary>

**Bonne réponse : B.** L'event couvre l'envoi entier, quel que soit le nombre de
transports ; `SendMessageToTransportsEvent`, avant l'envoi, est lui aussi unique.

</details>

**Question 2.** Un listener de priority 0 sur `WorkerMessageFailedEvent` et un
listener de `WorkerMessageRetriedEvent` journalisent. Le message sera rejoué.
Quelle ligne est écrite en premier ?

- **A.** Celle du listener de `WorkerMessageFailedEvent`
- **B.** Les deux en même temps
- **C.** Aucune : un échec rejoué n'émet pas d'event
- **D.** Celle du retry

<details>
<summary>Correction</summary>

**Bonne réponse : D.** À la priority 100, pendant la diffusion de l'échec,
`SendFailedMessageForRetryListener` émet `WorkerMessageRetriedEvent` ; le
listener de priority 0 passe ensuite.

</details>

**Question 3.** `max_retries: 1`, un handler qui échoue toujours. Que lit un
listener de priority 0 dans `willRetry()`, au premier puis au second échec ?

- **A.** `true`, puis `false`
- **B.** `false`, puis `false`
- **C.** `true`, puis `true`
- **D.** `false`, puis `true`

<details>
<summary>Correction</summary>

**Bonne réponse : A.** Une tentative de retry reste après le premier échec,
aucune après le second ; B serait la lecture d'un listener placé avant 100.

</details>

**Question 4.** Un listener appelle `shouldHandle(false)` sur
`WorkerMessageReceivedEvent`. Quel event le worker émet-il ensuite ?

- **A.** `WorkerMessageHandledEvent`
- **B.** `WorkerMessageFailedEvent`
- **C.** `WorkerRunningEvent`
- **D.** `WorkerMessageSkipEvent`

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Le handler ne tourne pas, donc ni succès ni échec ; D est
émis par `messenger:failed:retry`, pas par un message sauté ainsi.

</details>

## Fiche express

**Si tu dois retenir seulement 7 choses :**

- **Non routé** : aucun event Messenger ; un middleware le voit.
- À l'envoi : **`SendMessageToTransportsEvent`**, puis **`MessageSentToTransportsEvent`**, une fois.
- Worker : started, **received**, handled ou **failed**, **running**, stopped.
- **`willRetry()`** : après la priority 100.
- **`WorkerMessageRetriedEvent`** : pendant l'échec, par le listener de retry.
- **`WorkerRunningEvent`** : même à vide.
- **`shouldHandle(false)`** : ni handler, ni succès, ni échec.

Moyen mnémotechnique :
**« envoyer, recevoir, réussir ou échouer, tourner, s'arrêter »**.

```text
envoi : SendMessageToTransports (setEnvelope) → MessageSentToTransports (×1) ; worker : Started → Received (shouldHandle, addStamps)
→ Handled | Failed (willRetry après 100 ; Retried émis dedans) → Running (même idle) → Stopped ; non routé : rien
```

## Sources officielles

- [Messenger, « Messenger Events »](https://github.com/symfony/symfony-docs/blob/8.0/messenger.rst)
- [Messenger 8.0, `SendMessageMiddleware`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/Middleware/SendMessageMiddleware.php)
- [Messenger 8.0, `Worker`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/Worker.php)
- [Messenger 8.0, `SendFailedMessageForRetryListener`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/EventListener/SendFailedMessageForRetryListener.php)

