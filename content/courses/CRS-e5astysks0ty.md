---
id: CRS-e5astysks0ty
official_item: OIT-dctf03ftx44f
title: "Workers"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/messenger.rst"
    branch: "8.0"
    symbol_or_lines: "messenger:consume, limits, messenger:stop-workers"
    verified_at: "2026-09-01"
---

## Objectif

Consommer une file, et comprendre pourquoi un worker doit s'arrêter tout seul.

## La commande

```bash
php bin/console messenger:consume async
php bin/console messenger:consume async -vv          # ce qui est traité
php bin/console messenger:consume --all              # tous les récepteurs
php bin/console messenger:consume async_high async   # priorité par l'ordre
```

Quand plusieurs transports sont passés, ils sont consultés **dans l'ordre
donné** : le worker ne prend un message du second que si le premier est vide.
C'est ainsi qu'on implémente une priorité.

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

Le worker garde les **mêmes instances de services** d'un message à l'autre. Un
service qui accumule de l'état — un `EntityManager` fermé, un tableau qui
grossit — contamine donc les messages suivants. C'est le pendant du point
précédent : un worker n'est pas une requête.

## Pièges d'examen

**Un worker exécute le code chargé à son démarrage** : sans redémarrage, un
déploiement ne l'atteint pas.

**`messenger:stop-workers` ne tue pas** : elle demande un arrêt après le message
courant.

**Les limites ne sont pas un pansement** : elles sont la façon prévue d'exploiter
un worker.

**Les services sont partagés entre messages**, contrairement à une requête HTTP.

## Points clés

- `messenger:consume` ; plusieurs transports = priorité par l'ordre.
- `--limit`, `--memory-limit`, `--time-limit`, `--failure-limit` ; un superviseur relance.
- `messenger:stop-workers` après un déploiement ; arrêt gracieux.
- Les services sont partagés d'un message au suivant.

## Sources officielles

- [Messenger, « Consuming Messages » et « Deploying to Production »](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/messenger.rst)
