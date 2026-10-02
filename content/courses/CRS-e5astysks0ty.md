---
id: CRS-e5astysks0ty
official_item: OIT-dctf03ftx44f
title: "Workers"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/messenger.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/messenger.rst"
    branch: "8.0"
    symbol_or_lines: "messenger:consume, limits, messenger:stop-workers"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Messenger/Worker.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/Worker.php"
    branch: "8.0"
    symbol_or_lines: "run() — receivers in order, back to the first after a handled envelope"
    verified_at: "2026-10-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Messenger/EventListener/ResetServicesListener.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/EventListener/ResetServicesListener.php"
    branch: "8.0"
    symbol_or_lines: "resetServices() on WorkerRunningEvent"
    verified_at: "2026-10-01"
---

## Objectif

Consommer une file, comprendre pourquoi un worker doit s'arrêter tout seul, et
ce qu'il garde — ou non — d'un message au suivant.

## La commande

```bash
php bin/console messenger:consume async
php bin/console messenger:consume async -vv          # ce qui est traité
php bin/console messenger:consume --all              # tous les récepteurs
php bin/console messenger:consume async_high async   # priorité par l'ordre
```

Quand plusieurs transports sont passés, ils sont consultés **dans l'ordre
donné** : après chaque message traité, le worker repart du premier ; il ne lit
le second que si le premier est vide. Exécuté avec Messenger 8.0.15 : deux
messages envoyés sur `low`, puis un sur `high`, et
`messenger:consume high low --limit=3` traite `high-1`, puis `low-1`, puis
`low-2`.

Les options de `messenger:consume` en 8.0 : `--limit`, `--failure-limit`,
`--memory-limit`, `--time-limit`, `--sleep`, `--bus`, `--queues`, `--no-reset`,
`--all`, `--exclude-receivers`, `--keepalive`.

## Un worker est un processus long

Et c'est là qu'est la difficulté. Un processus PHP qui tourne pendant des jours
accumule de la mémoire et garde en cache des objets périmés. La réponse n'est
pas de corriger les fuites : c'est de **s'arrêter régulièrement**, et de laisser
un superviseur relancer.

```bash
--limit=10          # après 10 messages
--memory-limit=128M # au-delà de ce seuil
--time-limit=3600   # après une heure
--failure-limit=5   # après trop d'échecs
```

Un superviseur — Supervisor, systemd — redémarre alors le processus. L'arrêt est
**gracieux** : le message en cours est terminé avant la sortie.

## Le déploiement

Un worker démarré avant un déploiement exécute **l'ancien code**. La commande
prévue pour cela :

```bash
php bin/console messenger:stop-workers
```

Elle ne tue rien : elle pose un signal que chaque worker lit **après le message
en cours**, puis s'arrête proprement. Le superviseur le relance avec le nouveau
code. Oublier cette étape est la cause classique d'un comportement mixte après
mise en production.

## L'état entre deux messages

Le worker garde les **mêmes instances de services** d'un message à l'autre —
mais entre deux messages, `ResetServicesListener` appelle le **réinitialiseur
de services** : chaque service qui implémente `ResetInterface` (ou porte le tag
`kernel.reset`) voit sa méthode `reset()` appelée. Exécuté, trois messages,
deux services qui comptent ce qu'ils ont vu :

| Worker | Service ordinaire | Service `ResetInterface` |
|---|---|---|
| par défaut | 1, 2, **3** — l'état s'accumule | 1, **1**, **1** — remis à zéro |
| `--no-reset` | 1, 2, 3 | 1, 2, **3** |

Un service qui garde de l'état sans implémenter `ResetInterface` contamine donc
les messages suivants. C'est le pendant du point précédent : un worker n'est
pas une requête.

Effet de bord observé pendant ces exécutions : le transport `in-memory://`
implémente lui-même `ResetInterface`. Consommé par un worker qui réinitialise
les services, il est **vidé** après le premier message — il sert aux tests, pas
à un worker.

## Pièges d'examen

**Un worker exécute le code chargé à son démarrage** : sans redémarrage, un
déploiement ne l'atteint pas.

**`messenger:stop-workers` ne tue pas** : elle demande un arrêt après le message
courant.

**Plusieurs transports : priorité stricte**, pas de tourniquet.

**Les services sont partagés entre messages**, sauf ceux qui implémentent
`ResetInterface` ; `--no-reset` supprime même cette remise à zéro.

**Les limites ne sont pas un pansement** : elles sont la façon prévue d'exploiter
un worker.

## Points clés

- `messenger:consume` ; plusieurs transports = priorité par l'ordre, reprise au
  premier après chaque message.
- `--limit`, `--memory-limit`, `--time-limit`, `--failure-limit` ; un superviseur
  relance.
- `messenger:stop-workers` après un déploiement ; arrêt gracieux.
- Entre deux messages, seuls les services `ResetInterface` sont remis à zéro.

## Sources officielles

- [Messenger, « Consuming Messages » et « Deploying to Production »](https://github.com/symfony/symfony-docs/blob/8.0/messenger.rst)
- [Messenger 8.0, `Worker`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/Worker.php)
- [Messenger 8.0, `ResetServicesListener`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/EventListener/ResetServicesListener.php)
- [Messenger 8.0, `ConsumeMessagesCommand`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/Command/ConsumeMessagesCommand.php)
