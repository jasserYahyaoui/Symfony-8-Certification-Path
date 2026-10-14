---
id: CRS-se1jr6cxh2n7
official_item: OIT-ckr67pq9npyb
title: "Transports"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/messenger.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/messenger.rst"
    branch: "8.0"
    symbol_or_lines: "Transports, DSN, routing messages to a transport"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/FrameworkExtension.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/FrameworkExtension.php"
    branch: "8.0"
    symbol_or_lines: "registerMessengerConfiguration() — Invalid Messenger routing configuration"
    verified_at: "2026-10-01"
---

## Objectif

Router un message vers un transport, savoir ce que change l'absence de routage,
et ce qui est vérifié — ou non — au démarrage.

## Déclarer

Un transport est un **DSN** :

```yaml
framework:
    messenger:
        transports:
            async: '%env(MESSENGER_TRANSPORT_DSN)%'
            sync:  'sync://'
        routing:
            'App\Message\SendWelcomeEmail': async
```

Messenger 8.0 fournit des ponts pour AMQP, Doctrine, Redis, Amazon SQS et
Beanstalkd, plus deux transports particuliers :

- **`sync://`** — traite le message immédiatement, dans le même process ;
- **`in-memory://`** — ne traite rien et garde les messages en mémoire, pour
  qu'un test puisse vérifier ce qui a été envoyé sans démarrer de worker.

## Router

Deux façons, qui disent la même chose à deux endroits :

```php
#[AsMessage(['async', 'audit'])]
final class SmsNotification {}
```

```yaml
routing:
    'App\Message\SmsNotification': [async, audit]
    'App\Message\AsyncMessageInterface': async
    'App\Message\*': async
```

Une key de routage peut être une class, une class parente, une **interface**,
un **namespace** terminé par `*`, ou `*` seul. Un message peut viser
**plusieurs** transports.

Quand le même message est routé par l'attribute **et** par la configuration, la
configuration gagne — la documentation le présente comme le moyen de changer le
routage par environment. Exécuté avec Messenger 8.0.15 :
`#[AsMessage(['async', 'audit'])]` seul envoie le message aux deux transports
(deux `SentStamp`) ; avec en plus une key `routing` vers un transport `sync://`,
seul ce dernier le reçoit.

## Non routé : traité tout de suite

C'est le point central : **un message qui n'est routé nulle part est traité
immédiatement**, de façon synchrone. Ce n'est pas une erreur.

Une application sans `routing` fonctionne donc parfaitement — elle est
simplement entièrement synchrone.

## Ce qui est vérifié au démarrage

Une faute de frappe dans le routage n'est **pas** toujours silencieuse.
FrameworkBundle contrôle chaque key en construisant le container. Exécuté :

| Key de `routing` | Résultat |
|---|---|
| `App\…\Welcom` (class mal orthographiée) | **`LogicException`** au build : « class or interface … not found » |
| la bonne class vers `asyncc` (transport inexistant) | **`LogicException`** : « This is not a valid transport or service id » |
| `App\…\Welcome`, `App\…\*`, `*` | message envoyé au transport |
| `App\Other\*` (namespace sans message) | **aucune erreur** : traité en synchrone |

Seul le joker de namespace qui ne correspond à rien reste silencieux — le
message continue d'être traité, mais dans la Request.

## `sync://` n'est pas « pas de routage »

Exécuté : un message routé vers un transport `sync://` est traité pendant
`dispatch()`, comme un message non routé — mais son Envelope porte un
`SentStamp`. C'est un vrai transport, utile pour rendre un type de message
synchrone quand le reste part en file.

## Serialization

Un transport asynchrone sérialise le message. Le sérialiseur par défaut de
Messenger suffit dans la plupart des cas ; le composant Serializer peut le
remplacer quand un autre système doit lire la file. C'est cette étape qui impose
qu'un message soit sérialisable, et qui explique qu'on n'y mette pas d'entité.

## Choisir plusieurs transports

Séparer les transports par latence est la recommandation : un flux lent ne doit
pas retarder une confirmation de paiement. Chaque transport reçoit alors son
propre worker.

## Pièges d'examen

**Sans routage, le message est traité tout de suite** — pas mis en attente, pas
perdu.

**Une class mal orthographiée dans `routing` fait échouer le build** ; seul un
namespace sans correspondance est silencieux.

**La configuration l'emporte sur `#[AsMessage]`.**

**`sync://` n'est pas « pas de transport »** : il traite sur place, et pose un
`SentStamp`.

**`in-memory://` est réservé aux tests** ; il ne traite jamais rien.

## Points clés

- Un transport se déclare par un DSN ; `routing` ou `#[AsMessage]` associent
  message et transport, la configuration primant.
- Keys : class, parent, interface, namespace avec `*`, ou `*`.
- Non routé = traité immédiatement ; class ou transport inconnus = échec au
  build.
- `sync://` traite sur place, `in-memory://` sert aux tests.

## Sources officielles

- [Messenger, « Transports » et « Routing Messages to a Transport »](https://github.com/symfony/symfony-docs/blob/8.0/messenger.rst)
- [FrameworkBundle 8.0, `FrameworkExtension`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/FrameworkExtension.php)
