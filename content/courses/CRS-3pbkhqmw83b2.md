---
id: CRS-3pbkhqmw83b2
official_item: OIT-16at9vwzww43
title: "Events"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/messenger.rst"
    branch: "8.0"
    symbol_or_lines: "Messenger events, Worker* and SendMessageToTransportsEvent"
    verified_at: "2026-09-01"
---

## Objectif

Réagir au cycle de vie d'un message sans écrire de middleware, et savoir quel
événement porte quelle information. Le mécanisme d'abonnement lui-même est
traité dans *Event dispatcher and kernel events*.

## Événement ou middleware

Les deux permettent d'observer. La différence est la portée :

- un **middleware** est dans le chemin du message : il peut le modifier,
  l'enrichir, ou interrompre la chaîne ;
- un **événement** est une notification : on l'observe, on ne bloque pas le
  traitement.

Pour journaliser un échec ou incrémenter un compteur, l'événement suffit et
coûte moins. Pour ajouter un stamp, il faut un middleware.

## Le catalogue

Dix événements, dont deux à l'envoi et huit autour du worker :

| Événement | Quand |
|---|---|
| `SendMessageToTransportsEvent` | avant l'envoi vers les transports |
| `MessageSentToTransportsEvent` | après l'envoi |
| `WorkerStartedEvent` | le worker démarre |
| `WorkerRunningEvent` | à chaque boucle, y compris **à vide** |
| `WorkerMessageReceivedEvent` | un message vient d'être récupéré |
| `WorkerMessageHandledEvent` | un message a été traité avec succès |
| `WorkerMessageFailedEvent` | le handler a levé |
| `WorkerMessageRetriedEvent` | le message est remis en file pour un réessai |
| `WorkerRateLimitedEvent` | le worker est ralenti par un limiteur de débit |
| `WorkerStoppedEvent` | le worker s'arrête |

Le nombre exact se retient mal ; ce qui compte est la **forme** : un couple
avant/après à l'envoi, et un cycle complet côté worker — démarrage, boucle,
réception, succès ou échec, réessai, arrêt.

## Les deux qui comptent

**`WorkerMessageFailedEvent`** porte l'exception *et* dit si le message sera
rejoué : `willRetry()`. C'est ce qui distingue un échec transitoire d'un échec
définitif — journaliser au même niveau les deux noie l'information utile.

**`WorkerRunningEvent`** est émis à **chaque itération**, même quand la file est
vide, et `isWorkerIdle()` le signale. Un traitement coûteux placé là s'exécute en
boucle : c'est le piège de cet item.

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

## Pièges d'examen

**`WorkerRunningEvent` se déclenche à vide** : y placer du travail lourd le fait
tourner en permanence.

**`WorkerMessageFailedEvent` n'est pas un abandon** : `willRetry()` dit s'il
reste des tentatives.

**Un événement n'interrompt pas le traitement** ; seul un middleware le peut.

## Points clés

- Dix événements : deux à l'envoi, huit autour du worker.
- `WorkerMessageFailedEvent::willRetry()` distingue échec transitoire et définitif.
- `WorkerRunningEvent` est émis même à vide.
- Observer = événement ; modifier ou interrompre = middleware.

## Sources officielles

- [Messenger, « Messenger Events »](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/messenger.rst)
