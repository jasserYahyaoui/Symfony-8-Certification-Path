---
id: CRS-se1jr6cxh2n7
official_item: OIT-ckr67pq9npyb
title: "Transports"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/messenger.rst"
    branch: "8.0"
    symbol_or_lines: "Transports, DSN, routing messages to a transport"
    verified_at: "2026-09-01"
---

## Objectif

Router un message vers un transport, et savoir ce que change l'absence de
routage.

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

Les transports fournis couvrent AMQP, Doctrine (une table de la base), Redis,
Amazon SQS, Beanstalkd, plus deux transports particuliers :

- **`sync://`** — traite le message immédiatement, dans le même processus ;
- **`in-memory://`** — ne traite rien et garde les messages en mémoire, pour
  qu'un test puisse vérifier ce qui a été envoyé sans démarrer de worker.

## La règle du routage

C'est le point central : **un message qui n'est routé nulle part est traité
immédiatement**, de façon synchrone. Ce n'est pas une erreur, et rien ne le
signale.

Une application sans `routing` fonctionne donc parfaitement — elle est
simplement entièrement synchrone. C'est aussi ce qui rend une mauvaise clé de
routage silencieuse : le message continue d'être traité, mais dans la requête.

Le routage accepte un **espace de noms** ou une **interface**, pas seulement une
classe, et un message peut être envoyé à **plusieurs** transports.

## Sérialisation

Un transport asynchrone sérialise le message. Le sérialiseur par défaut de
Messenger suffit dans la plupart des cas ; le composant Serializer peut le
remplacer quand un autre système doit lire la file.

C'est cette étape qui impose qu'un message soit sérialisable, et qui explique
qu'on n'y mette pas d'entité.

## Choisir plusieurs transports

Séparer les transports par latence est la recommandation : un flux de
synchronisation lent ne doit pas retarder une confirmation de paiement. Chaque
transport peut alors recevoir son propre worker.

## Pièges d'examen

**Sans routage, le message est traité tout de suite** — pas mis en attente, pas
perdu.

**`sync://` n'est pas « pas de transport »** : c'est un transport qui traite sur
place.

**`in-memory://` est réservé aux tests** ; il ne traite jamais rien.

**Un message peut aller vers plusieurs transports.**

## Points clés

- Un transport se déclare par un DSN ; `routing` associe message et transport.
- Non routé = traité immédiatement, sans avertissement.
- `sync://` traite sur place, `in-memory://` sert aux tests.
- Le routage accepte une classe, une interface ou un espace de noms.

## Sources officielles

- [Messenger, « Transports » et « Routing Messages to a Transport »](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/messenger.rst)
