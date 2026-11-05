---
id: CRS-e5astysks0ty
official_item: OIT-dctf03ftx44f
title: "Workers"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-11-01"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/messenger.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/messenger.rst"
    branch: "8.0"
    symbol_or_lines: "messenger:consume, limits, messenger:stop-workers"
    verified_at: "2026-11-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Messenger/Worker.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/Worker.php"
    branch: "8.0"
    symbol_or_lines: "run() — receivers in order, back to the first after a handled envelope"
    verified_at: "2026-11-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Messenger/EventListener/ResetServicesListener.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/EventListener/ResetServicesListener.php"
    branch: "8.0"
    symbol_or_lines: "resetServices() on WorkerRunningEvent"
    verified_at: "2026-11-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Messenger/EventListener/StopWorkerOnRestartSignalListener.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/EventListener/StopWorkerOnRestartSignalListener.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "shouldRestart() — only a worker started before the signal stops"
    verified_at: "2026-11-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Messenger/EventListener/StopWorkerOnMessageLimitListener.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/EventListener/StopWorkerOnMessageLimitListener.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "onWorkerRunning() — every received message counts, failed ones included"
    verified_at: "2026-11-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Messenger/Command/ConsumeMessagesCommand.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/Command/ConsumeMessagesCommand.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "configure(), interact(), execute() — options, receivers, --exclude-receivers with --all"
    verified_at: "2026-11-01"
outcome_sections:
  OUT-n57edsf6gz2k: "La command"
  OUT-k3t8watc85cw: "Un worker est un process long"
  OUT-d5r5yrf58x1s: "Le deployment"
  OUT-q8eq8avwpyh2: "L'état entre deux messages"
---

## Objectif

Consommer une file, comprendre pourquoi un worker doit s'arrêter tout seul, et
ce qu'il garde — ou non — d'un message au suivant.

## L'essentiel

Un worker est un **process long** : il lit ses files **dans l'ordre donné**, et
s'arrête **de lui-même** sur une limite pour qu'un superviseur le relance.
Après un deployment, `messenger:stop-workers` lui fait prendre le nouveau code.
Entre deux messages, il garde ses services — sauf ceux qu'il **remet à zéro**.

## Le schéma

```mermaid
---
title: La loop d'un worker
---
flowchart TD
  accTitle: La loop de Worker::run, de la lecture des transports à l'arrêt
  accDescr: Au démarrage, le worker émet WorkerStartedEvent. À chaque tour, il s'arrête si la time-limit est dépassée. Sinon il lit les transports dans l'ordre donné. Dès qu'un transport rend des messages, il les traite un par un ; après chacun, WorkerRunningEvent déclenche les listeners de limite, de signal de redémarrage et de remise à zéro des services, et le worker s'arrête si l'un d'eux l'a demandé. Après le lot d'un transport, il repart du premier transport. Si aucun transport n'a de message, il émet WorkerRunningEvent en état idle, dort, puis recommence.
  START(["WorkerStartedEvent"]) --> TIME{"time-limit dépassée ?"}
  TIME -->|"oui"| STOP(["arrêt : WorkerStoppedEvent"])
  TIME -->|"non"| READ["get() sur les transports, dans l'ordre donné"]
  READ -->|"un transport rend des messages"| HANDLE["traiter un message du lot"]
  HANDLE --> RUN["WorkerRunningEvent : limites, signal de redémarrage, reset des services"]
  RUN --> ASK{"arrêt demandé ?"}
  ASK -->|"oui"| STOP
  ASK -->|"non, message suivant du lot"| HANDLE
  ASK -->|"non, lot fini"| TIME
  READ -->|"aucun message"| IDLE["WorkerRunningEvent idle, puis sleep"]
  IDLE --> TIME
```

Tout ce qui arrête un worker passe par **un listener**, entre deux messages :
aucune option ni command n'interrompt un traitement en cours.

## L'exemple

```bash
# lancé par Supervisor ou systemd, relancé dès qu'il s'arrête
php bin/console messenger:consume async_high async \
    --limit=100 --memory-limit=128M --time-limit=3600

# à chaque deployment, après la mise en place du nouveau code
php bin/console messenger:stop-workers
```

## La command

- Plusieurs transports sont consultés **dans l'ordre donné** : après chaque
  message, le worker repart du premier, et ne lit le second que si le premier
  est vide. Deux messages sur `low`, puis un sur `high` :
  `messenger:consume high low --limit=3` traite `high-1`, `low-1`, `low-2`.
- Les options de `messenger:consume` en 8.0 : `--limit`, `--failure-limit`,
  `--memory-limit`, `--time-limit`, `--sleep`, `--bus`, `--queues`,
  `--no-reset`, `--all`, `--exclude-receivers`, `--keepalive`.

| Appel | Résultat |
|---|---|
| `messenger:consume low --exclude-receivers=high` | **exit 1** : l'option exige `--all` |
| sans récepteur, en mode interactif | une question : quels récepteurs consommer ? |
| sans récepteur, **non interactif** | **exit 0** : « Consuming messages from transport "" » — le worker ne lit rien |

## Un worker est un process long

Un process PHP qui tourne des jours accumule de la mémoire et des objects
périmés. La solution prévue n'est pas de corriger les fuites : c'est de
**s'arrêter régulièrement**.

```bash
--limit=10          # après 10 messages
--memory-limit=128M # au-delà de ce seuil
--time-limit=3600   # après une heure
--failure-limit=5   # après trop d'échecs
```

- L'arrêt est **gracieux** : le message en cours est terminé.
- **`--limit` compte les messages reçus, pas les réussis** : trois messages,
  le premier en échec, `--limit=2` — l'échec et le deuxième, puis l'arrêt.

## Le deployment

Un worker démarré avant un deployment exécute **l'ancien code**.
`messenger:stop-workers` ne tue rien : elle pose un signal — une date dans un
cache — que chaque worker lit **après le message en cours**. Seul un worker
**démarré avant** cette date s'arrête ; celui que le superviseur relance n'est
pas visé.

## L'état entre deux messages

Le worker garde les **mêmes instances de services**, mais entre deux messages
`ResetServicesListener` appelle `reset()` sur chaque service qui implémente
`ResetInterface` (ou porte le tag `kernel.reset`). Trois messages, deux
services qui comptent ce qu'ils ont vu :

| Worker | Service ordinaire | Service `ResetInterface` |
|---|---|---|
| par défaut | 1, 2, **3** — l'état s'accumule | 1, **1**, **1** |
| `--no-reset` | 1, 2, 3 | 1, 2, **3** |

Le transport `in-memory://` est lui-même réinitialisable : le reset **vide sa
file**. Il sert aux tests, pas à un worker.

## Pièges d'examen

**Un worker exécute le code chargé à son démarrage** : sans redémarrage, un
deployment ne l'atteint pas.

**`messenger:stop-workers` ne tue pas** : elle demande un arrêt après le message
courant.

**Plusieurs transports : priority stricte**, pas de tourniquet.

**Les services sont partagés entre messages**, sauf ceux qui implémentent
`ResetInterface` ; `--no-reset` supprime même cette remise à zéro.

**Les limites ne sont pas un pansement** : elles sont la façon prévue d'exploiter
un worker.

## Application

- Supervisor lance `messenger:consume --time-limit=3600` sans transport, et
  aucun message n'est traité, sans erreur → nommer les récepteurs, ou `--all`.
- Un service garde les tarifs du client courant ; le client suivant les voit →
  `ResetInterface` et un `reset()` qui vide la property.

## QCM d'entraînement

**Question 1.** Un superviseur lance `messenger:consume --time-limit=3600`, non
interactif, sans nom de récepteur. Que se passe-t-il ?

- **A.** Une exception : « Please pass at least one receiver. »
- **B.** Le worker consomme tous les transports
- **C.** Il consomme le transport nommé `async` par défaut
- **D.** Le worker tourne une heure sans rien lire

<details>
<summary>Correction</summary>

**Bonne réponse : D.** L'exception de A est levée dans `interact()`, que la
command n'appelle pas hors mode interactif ; B demande `--all`.

</details>

**Question 2.** Trois messages, le premier en échec. Le worker tourne avec
`--limit=2`. Combien de messages traite-t-il ?

- **A.** Trois : l'échec ne compte pas
- **B.** Deux : l'échec et le suivant
- **C.** Un : le premier échec arrête le worker
- **D.** Deux réussis, après avoir sauté l'échec

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Chaque message reçu compte, réussi ou non ; C confond
avec `--failure-limit=1`.

</details>

**Question 3.** Un worker a démarré après un `messenger:stop-workers`. Le
signal l'arrête-t-il ?

- **A.** Oui, au prochain message
- **B.** Oui, immédiatement
- **C.** Non : il a démarré après le signal
- **D.** Non : le signal est effacé dès le premier arrêt

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Le worker compare sa date de démarrage à celle du
signal, qui reste en cache ; c'est cette comparaison qui protège le nouveau.

</details>

**Question 4.** `messenger:consume async --exclude-receivers=failed`. Résultat ?

- **A.** Une erreur : l'option exige `--all`
- **B.** Le worker consomme `async`, `failed` est ignoré
- **C.** Le worker consomme tous les transports sauf `failed`
- **D.** L'option est ignorée en silence

<details>
<summary>Correction</summary>

**Bonne réponse : A.** La command s'arrête en exit 1 ; C décrit le bon usage,
`--all --exclude-receivers=failed`.

</details>

## Fiche express

**Si tu dois retenir seulement 7 choses :**

- **`messenger:consume high low`** : priority stricte, par l'ordre.
- Un worker **s'arrête de lui-même** ; un **superviseur** le relance.
- **`--limit`** compte les messages reçus, échecs compris.
- **`messenger:stop-workers`** : après le message courant, workers plus anciens
  seulement.
- **`ResetInterface`** : remis à zéro entre deux messages, sauf `--no-reset`.
- Sans récepteur, **non interactif** : un worker qui ne lit rien.
- **`--exclude-receivers`** exige **`--all`**.

Moyen mnémotechnique :
**« lire dans l'ordre, s'arrêter entre deux, repartir neuf »**.

```text
consume a b (ordre = priority, repart du premier) ; arrêt entre deux messages par listeners : limit (tout message), memory, time, failure ;
stop-workers = date en cache, workers démarrés avant ; reset ResetInterface sauf --no-reset ; --exclude-receivers ⇒ --all
```

## Sources officielles

- [Messenger, « Consuming Messages » et « Deploying to Production »](https://github.com/symfony/symfony-docs/blob/8.0/messenger.rst)
- [Messenger 8.0, `Worker`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/Worker.php)
- [Messenger 8.0, `ResetServicesListener`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/EventListener/ResetServicesListener.php)
- [Messenger 8.0, `ConsumeMessagesCommand`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/Command/ConsumeMessagesCommand.php)
- [Messenger 8.0, `StopWorkerOnRestartSignalListener`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/EventListener/StopWorkerOnRestartSignalListener.php)
- [Messenger 8.0, `StopWorkerOnMessageLimitListener`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Messenger/EventListener/StopWorkerOnMessageLimitListener.php)

