# Raffinement pédagogique — Lot 11 (Messenger)

Suite de la mission ouverte au lot 02 : approfondir les **pages de cours**
existantes — pièges d'examen, comportements implicites, flashcards aux quatre
niveaux — un lot à la fois, dans l'ordre numérique. Le lot 01 reste hors
périmètre sur instruction explicite (voir le journal du lot 02).

Même méthode qu'aux lots 03 à 10 : chaque affirmation vérifiée contre le code
de la branche 8.0 de Symfony ou la documentation correspondante, par exécution
chaque fois que c'est possible, jamais de mémoire ; quand la documentation et le
code divergent, le code l'emporte et l'écart est signalé sur la page ; budget
`REV-001` respecté sans promotion de niveau ; une branche, une PR, une CI verte,
une fusion et un smoke test de production **lu** par page. Le bac à sable
d'exécution a tous ses composants `symfony/*` fixés en `8.0.*` ; Messenger
8.0.15 y a été ajouté pour ce lot, et la vérification « aucun paquet `symfony/*`
hors 8.0 » refaite après l'ajout. Les transports exclus du périmètre (Doctrine,
Redis, Amazon SQS — `exclusions.yml`) ne sont pas utilisés : les exécutions
passent par `sync://` et `in-memory://`.

## État par page (ordre officiel de l'item)

Chiffres relevés le 2026-10-01 par script sur les fichiers canoniques
(`syllabus-matrix.yml`, `content/**`) de `master` à `dd3b0c1`, avant la
première page.

| # | Page | Niveau | Mots / plafond | Flashcards | Statut |
|---|---|---|---|---|---|
| 1 | Messenger component | STANDARD | 382 / 900 | 1 | **RAFFINÉE** (PR #280) |
| 2 | Transports | STANDARD | 386 / 900 | 1 | **RAFFINÉE** (PR #281) |
| 3 | Messages and handlers | STANDARD | 381 / 900 | 1 | **RAFFINÉE** (PR #282) |
| 4 | Workers | STANDARD | 408 / 900 | 1 | **RAFFINÉE** (PR #283) |
| 5 | Retries and failures | DEEP | 542 / 1200 | 1 | **RAFFINÉE** (PR #284) |
| 6 | Middleware | STANDARD | 376 / 900 | 1 | à faire |
| 7 | Events | STANDARD | 427 / 900 | 1 | à faire |

## Page 1 — *Messenger component* — RAFFINÉE

`CRS-5gezsra1sp01` · `OIT-hs1297vvhr89` · STANDARD · **382 → 647 mots** sur 900.
Aucun niveau promu. Exécutions sur FrameworkBundle + Messenger 8.0.15, un
message `Welcome` à deux handlers et un message `Orphan` sans handler, bus
`messenger.default_bus` lu dans le conteneur de test.

### Déploiement précédent, lu en production

| Fusion | Run Pages | Smoke test |
|---|---|---|
| rapport de fin de lot 10 (PR #279, `dd3b0c1`) | 36901616661, success | lignes `practice`, `exam`, `mock-1` à `mock-5`, `simulations` et `course` toutes `ok` ; `mock-4` : 75 questions, tout le holdout et rien d'autre |

### Une liste de concepts qui n'était pas celle de la documentation

La page annonçait « les six concepts » : message, bus, enveloppe, sender,
receiver, handler. La section « Concepts » de `messenger.rst` (8.0) en nomme six
autres : **Sender, Receiver, Handler, Middleware, Envelope, Envelope Stamps**.
Le middleware et les stamps manquaient ; le message et le bus n'y figurent pas.
Le résultat d'apprentissage `OUT-c3q6angezzhs` (« Nommer les six concepts du
composant ») reste exact ; c'est la page qui est corrigée, ainsi que le
`symbol_or_lines` de sa source, qui recopiait l'ancienne liste.

### Une affirmation trop générale : `dispatch()` « ne traite pas »

La page écrivait « `dispatch()` **ne traite pas** le message ». Exécuté :

| Routage | Handlers exécutés avant le retour de `dispatch()` | Stamps de l'enveloppe retournée |
|---|---|---|
| aucun | les deux | `DelayStamp`, `BusNameStamp`, `HandledStamp` ×2 |
| `in-memory://` | aucun | `DelayStamp`, `BusNameStamp`, `SentStamp`, `TransportMessageIdStamp` |

En synchrone, le message est déjà traité au retour de `dispatch()`. La page
réserve désormais « ne traite pas » à l'asynchrone.

### Ajouté par l'exécution

- Un `DelayStamp(5000)` sur un message non routé ne retarde rien : les handlers
  tournent pendant `dispatch()`.
- Un message non routé sans handler : `NoHandlerForMessageException`, « No
  handler for message "App\P13\M\Orphan" ».
- Deux handlers : deux `HandledStamp` ; `last()` rend le second résultat.
- `HandleTrait::handle()` avec deux handlers : `LogicException`, « was handled
  multiple times. Only one handler is expected ».
- Le double passage des middleware (envoi, puis réception) est repris de la
  documentation, non exécuté ici — il le sera à la page *Middleware*.

**Questions.** `QST-4s59dzkg2rff`, `QST-2vt07hzxfmvq` (LEARNING) et
`QST-ep0z3tq9s4wk` (VALIDATION) relues : exactes, inchangées. Aucune question
holdout lue ni modifiée.

**Flashcards.** 10 ajoutées ; `FLC-7wbtaz9pzty2` reçoit le niveau TRAP. L'item en
porte **11** (3 RECALL, 2 UNDERSTANDING, 2 APPLICATION, 4 TRAP), décompte relevé
par script sur **tous** les fichiers de cartes — leçon de l'erratum du lot 10.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`Envelope Stamps`, `TransportMessageIdStamp` et `handled multiple`, absentes de
la version `master` de la page et du fichier de cartes.

**Contrôles réellement exécutés le 2026-10-01**

| Contrôle | Résultat |
|---|---|
| exécutions FrameworkBundle + Messenger 8.0.15 | résultats cités ci-dessus |
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| `php bin/cert build` | exit 0 |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 17 211 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

## Page 2 — *Transports* — RAFFINÉE

`CRS-se1jr6cxh2n7` · `OIT-ckr67pq9npyb` · STANDARD · **386 → 679 mots** sur 900.
Aucun niveau promu. Exécutions sur FrameworkBundle + Messenger 8.0.15, transports
`in-memory://` et `sync://`, configurations de `routing` variées, conteneur
reconstruit à chaque cas.

### Déploiement précédent, lu en production

| Fusion | Run Pages | Ligne de smoke test |
|---|---|---|
| page 1 du lot 11 (PR #280, `5085da4`) | 36908658099, success | `ok  lot-11  the messenger component page carries its four flashcard levels, the concept list, the sent stamps and the HandleTrait refusal` |

### Une question fausse : la faute de frappe « silencieuse »

`QST-tvzx2c0cqeh8` (LEARNING) demandait l'effet d'une classe mal orthographiée
dans `routing`, avec pour bonne réponse « It is handled synchronously, with no
warning » et, en explication d'un distracteur, « An unrouted class name is not
an error ». La page disait de même : « une mauvaise clé de routage
silencieuse ». Lu dans `FrameworkExtension::registerMessengerConfiguration()`,
puis exécuté :

| Clé de `routing` | Résultat |
|---|---|
| classe mal orthographiée | `LogicException` au build : « Invalid Messenger routing configuration: class or interface "App\P13\M\Welcom" not found. » |
| bonne classe vers un transport inexistant | `LogicException` : « … is being routed to a sender called "asyncc". This is not a valid transport or service id. » |
| classe exacte, `App\P13\M\*`, `*` | message envoyé au transport |
| `App\Other\*` (espace de noms sans message) | aucune erreur ; traité en synchrone |

La question passe en **v2** : bonne réponse `CHO-20ntydthrjak` (« The container fails to
build »), l'ancienne bonne réponse devient un distracteur sous un nouvel
identifiant (`CHO-vyjpemkd7xn0`) — sa correction change —, explications des deux autres
distracteurs corrigées, source `FrameworkExtension` ajoutée ; énoncé inchangé.
La carte `FLC-g0g67y6cwvv1` disait « sans erreur ni avertissement » : faux sans
handler (`NoHandlerForMessageException`, page 1) ; corrigée, niveau TRAP, et son
explication ne reprend plus la faute de frappe silencieuse.

### Ajouté par l'exécution ou la lecture

- `#[AsMessage(['async', 'audit'])]` : deux `SentStamp`, un message dans chaque
  transport.
- Attribut **et** clé `routing` sur la même classe : la configuration gagne
  (seul le transport `sync://` de la configuration reçoit le message) — ce que
  la documentation 8.0 annonce.
- Message routé vers `sync://` : traité pendant `dispatch()`, avec un
  `SentStamp`, contrairement au message non routé.
- Ponts Messenger présents sur la branche 8.0 : AmazonSqs, Amqp, Beanstalkd,
  Doctrine, Redis (le `composer.json` de chacun répond 200 ; la liste complète
  du répertoire n'a pas été lue). Ces transports, hors
  périmètre pour Doctrine, Redis et SQS, ne sont que nommés.

**Matrice.** `OUT-sz3q1tm5smja` : « non route » → « non routé » (accent).

**Questions.** `QST-7ay2hp5jexwb`, `QST-bp6mp496mm7m`, `QST-v4wkcq3cmm37`
(LEARNING) et `QST-t248g7r5b5y8` (VALIDATION) relues : exactes, inchangées.
Aucune question holdout lue ni modifiée.

**Flashcards.** 10 ajoutées ; `FLC-g0g67y6cwvv1` corrigée, niveau TRAP. L'item en
porte **11** (3 RECALL, 2 UNDERSTANDING, 2 APPLICATION, 4 TRAP), décompte relevé
par script sur tous les fichiers de cartes.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`class or interface`, `not a valid transport` et `joker`,
absentes de la version `master` de la page et des fichiers de cartes.

**Contrôles réellement exécutés le 2026-10-01**

| Contrôle | Résultat |
|---|---|
| exécutions FrameworkBundle + Messenger 8.0.15 | résultats cités ci-dessus |
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| `php bin/cert build` | exit 0 |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 17 221 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

## Page 3 — *Messages and handlers* — RAFFINÉE

`CRS-ckdp50w2dmy4` · `OIT-9a8aa389vk48` · STANDARD · **381 → 686 mots** sur 900.
Aucun niveau promu. Exécutions sur FrameworkBundle + Messenger 8.0.15 (PHP 8.4) :
handlers par union, par interface, par méthode, avec priorité, sans type ; et un
message encodé par `PhpSerializer` avec une version de classe, décodé avec la
suivante.

### Déploiement précédent, lu en production

| Fusion | Run Pages | Ligne de smoke test |
|---|---|---|
| page 2 du lot 11 (PR #281, `3ab5549`) | 36910435936, success | `ok  lot-11  the transports page carries its four flashcard levels, the class check, the unknown transport and the silent wildcard` |

### Une règle de versionnage fausse pour la forme que la page enseigne

La page écrivait : « Ajouter une propriété **avec une valeur par défaut** est
sûr ». Or la page elle-même écrit ses messages avec des propriétés **promues**
(`public function __construct(public readonly int $userId)`). Lu : le
sérialiseur par défaut est `PhpSerializer`
(`framework.messenger.serializer.default_serializer`), dont `decode()` passe par
`unserialize()`, qui n'appelle pas le constructeur. Exécuté, message encodé avec
la version 1 :

| Version 2 de la classe | Lecture de l'ancien message |
|---|---|
| propriété promue ajoutée, défaut `'fr'` | `Error` : « Typed property SendWelcomeEmail::$lang must not be accessed before initialization » |
| propriété déclarée ajoutée, défaut `'fr'` | `lang='fr'` |
| propriété retirée | décodé ; « Creation of dynamic property SendWelcomeEmail::$tag is deprecated » |

La règle est corrigée : sûre pour une propriété **déclarée**, pas pour une
propriété promue. Aucune question ne portait sur ce point.

### Ajouté par l'exécution ou la lecture

| Handler | Messages reçus |
|---|---|
| méthode `onUnion(Ping\|Pong $m)` | `Ping` et `Pong` |
| méthode `onNotice(Notice $m)`, `Notice` interface | `Ping`, qui l'implémente |
| `__invoke($m)` sans type | échec au build : « argument "$m" … must have a type-hint corresponding to the message class it handles » (`MessengerPass`) |

- `priority: 10` sur le second de deux handlers : il passe avant le premier ;
  les deux s'exécutent.
- Options de `#[AsMessageHandler]` en 8.0.15 : `bus`, `fromTransport`,
  `handles`, `method`, `priority`, `sign`.
- La page parlait d'entité Doctrine et d'`EntityManager` pour justifier
  l'identifiant ; Doctrine étant hors périmètre (`exclusions.yml`), la
  justification est désormais générale (copie figée, périmée à la lecture).

**Questions.** `QST-mzsqzvya53dc`, `QST-sra8bgw85a7j`, `QST-45tm3rfbkjyw`
(LEARNING) et `QST-b070jhpm2vjt` (VALIDATION) relues : exactes, inchangées —
l'explication « Priority orders handlers; it does not exclude them » est
confirmée par l'exécution. Aucune question holdout lue ni modifiée.

**Flashcards.** 10 ajoutées ; `FLC-c1z191gbhw72` reçoit le niveau RECALL. L'item
en porte **11** (4 RECALL, 2 UNDERSTANDING, 2 APPLICATION, 3 TRAP), décompte
relevé par script sur tous les fichiers de cartes.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`must not be accessed`, `fromTransport` et `type-hint corresponding`,
absentes de la version `master` de la page et des fichiers de cartes.

**Contrôles réellement exécutés le 2026-10-01**

| Contrôle | Résultat |
|---|---|
| exécutions FrameworkBundle + Messenger 8.0.15, PHP 8.4 | résultats cités ci-dessus |
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| `php bin/cert build` | exit 0 |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 17 231 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

## Page 4 — *Workers* — RAFFINÉE

`CRS-e5astysks0ty` · `OIT-dctf03ftx44f` · STANDARD · **408 → 611 mots** sur 900.
Aucun niveau promu. Exécutions sur FrameworkBundle + Messenger 8.0.15 :
`messenger:consume` lancé dans le même processus par l'`Application` de la
console, sur deux transports, avec un service ordinaire et un service
`ResetInterface` qui comptent ce qu'ils voient.

### Déploiement précédent, lu en production

| Fusion | Run Pages | Ligne de smoke test |
|---|---|---|
| page 3 du lot 11 (PR #282, `cfc28db`) | 36989528021, success | `ok  lot-11  the messages and handlers page carries its four flashcard levels, the versioning error, the transport filter and the type-hint refusal` |

### Une affirmation incomplète : « les mêmes instances de services »

La page écrivait que le worker garde les mêmes instances de services, et qu'un
service qui accumule de l'état contamine les messages suivants — sans nuance.
Lu dans `ResetServicesListener` : sur chaque `WorkerRunningEvent` non inactif,
le réinitialiseur de services appelle `reset()` sur les services
`ResetInterface` (tag `kernel.reset`, posé par l'autoconfiguration). Exécuté,
trois messages :

| Worker | Service ordinaire | Service `ResetInterface` |
|---|---|---|
| par défaut | 1, 2, 3 | 1, 1, 1 |
| `--no-reset` | 1, 2, 3 | 1, 2, 3 |

La page dit désormais ce qui est remis à zéro et ce qui ne l'est pas ; la
question `QST-qh4r169hgx7y` l'expliquait déjà correctement.

### Une méthode d'exécution corrigée en route

Première tentative avec deux transports `in-memory://` : un seul des trois
messages a été consommé. Cause, lue puis vérifiée : `InMemoryTransport`
implémente `ResetInterface`, et la remise à zéro des services le vide après le
premier message. Le fait est repris sur la page (in-memory est un transport de
test, pas de worker) ; les mesures retenues utilisent un transport maison
minimal, à file statique, qui survit à la remise à zéro.

### Confirmé par l'exécution ou la lecture

- Priorité : `low-1`, `low-2` envoyés avant `high-1` ; `messenger:consume high
  low --limit=3` traite `high-1`, `low-1`, `low-2`. Lu dans `Worker::run()` :
  après un message traité, retour au premier récepteur.
- Options de `messenger:consume` 8.0.15 : `limit`, `failure-limit`,
  `memory-limit`, `time-limit`, `sleep`, `bus`, `queues`, `no-reset`, `all`,
  `exclude-receivers`, `keepalive`.
- `messenger:stop-workers` et l'arrêt gracieux sur `SIGTERM` / `SIGINT` :
  documentation 8.0, non exécutés (ils supposent des processus séparés).

**Questions.** `QST-c0qzhz7ded47`, `QST-66bdw5shb7py`, `QST-qh4r169hgx7y`
(LEARNING) et `QST-bzvvb66w5ftd` (VALIDATION) relues : exactes, inchangées —
la priorité stricte de `QST-bzvvb66w5ftd` et la remise à zéro de
`QST-qh4r169hgx7y` sont confirmées par l'exécution. Aucune question holdout lue
ni modifiée.

**Flashcards.** 10 ajoutées ; `FLC-sh7aqjgp5mzt` reçoit le niveau
UNDERSTANDING. L'item en porte **11** (3 RECALL, 3 UNDERSTANDING,
2 APPLICATION, 3 TRAP), décompte relevé par script sur tous les fichiers de
cartes.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`exclude-receivers`, `ResetServicesListener` et `high-1`,
absentes de la version `master` de la page et des fichiers de cartes.

**Contrôles réellement exécutés le 2026-10-02**

| Contrôle | Résultat |
|---|---|
| exécutions FrameworkBundle + Messenger 8.0.15 | résultats cités ci-dessus |
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| `php bin/cert build` | exit 0 |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 17 241 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

## Page 5 — *Retries and failures* — RAFFINÉE

`CRS-hgkzgm1dh3my` · `OIT-76x1t7z916f4` · DEEP · **542 → 852 mots** sur 1200.
Aucun niveau promu. Exécutions sur FrameworkBundle + Messenger 8.0.15 :
`messenger:consume` et `messenger:failed:retry` lancés dans le même processus,
transport à file statique (celui de la page 4), `retry_strategy` à
`max_retries: 3`, un handler qui lève une exception ordinaire,
*unrecoverable* ou *recoverable*.

### Déploiement précédent, lu en production

| Fusion | Run Pages | Ligne de smoke test |
|---|---|---|
| page 4 du lot 11 (PR #283, `10abcdd`) | 36991017548, success | `ok  lot-11  the workers page carries its four flashcard levels, the consume options, the reset listener and the priority run` |

### Trois erreurs de fond

**1. Le réessai ne concerne que l'asynchrone.** La page posait « une exception
dans un handler n'est pas fatale : le message est remis dans le transport ».
Exécuté sur un message non routé : `HandlerFailedException` — « Handling
"App\P13\M\Boom" failed: boom » — levée par `dispatch()`, une seule tentative.

**2. `RecoverableMessageHandlingException` ignore `max_retries`.** La page la
décrivait comme un réessai « forcé ». Lu dans
`SendFailedMessageForRetryListener::shouldRetry()` : `return true` pour une
`RecoverableExceptionInterface`, **avant** l'appel à la stratégie. Exécuté :
12 tentatives pour un worker limité à 12 messages, message toujours en file,
jamais en échec.

**3. `messenger:failed:retry` ne renvoie pas vers le transport d'origine.** La
page l'affirmait. Lu dans `FailedMessagesRetryCommand` : la commande lance un
worker sur le transport d'échec et traite le message elle-même. Exécuté : un
message qui échoue encore n'est jamais envoyé vers `async` ; il revient en file
d'échec avec un compteur à 1. Le compteur « remis à zéro » l'est à l'entrée en
file d'échec (`RedeliveryStamp(0)`, `SendFailedMessageToFailureTransportListener`),
pas par la commande.

### Confirmé par l'exécution ou le calcul

| Exception | Tentatives | Sans `failure_transport` | Avec |
|---|---|---|---|
| ordinaire | 4 | perdu | en file d'échec |
| `Unrecoverable…` | 1 | perdu | en file d'échec |
| `Recoverable…` | 12 (limite du worker) | jamais en échec | jamais en échec |

- Délais de `MultiplierRetryStrategy(3, 1000, 2)` : 1000, 2000, 4000 ms sans
  jitter, 1096, 2109, 4298 ms avec un jitter de 0,1.
- Défauts FrameworkBundle 8.0 : `max_retries` 3, `delay` 1000, `multiplier` 2,
  `max_delay` **0**, `jitter` 0,1.
- `RecoverableMessageHandlingException` : délai en quatrième argument du
  constructeur (`retryDelay`).
- Plusieurs handlers en échec : lu dans `shouldRetry()`, le réessai n'est
  refusé d'office que si **toutes** les exceptions emballées sont
  *unrecoverable*. Une première rédaction ajoutait « une seule *recoverable*
  suffit » ; la boucle s'arrête à la première exception ordinaire, la phrase
  n'était donc pas vraie dans tous les ordres — retirée.

L'exemple de transport d'échec utilisait un DSN `doctrine://` ; Doctrine étant
hors périmètre, il passe par une variable d'environnement.

**Questions.** `QST-0v3j82n0d4m7`, `QST-9c3nh6jtp3px`, `QST-8vfdesj0zh4w`,
`QST-rdbfzznze605`, `QST-b27yd0msa8yq` (LEARNING) et `QST-cf72hjvcjyj2`
(VALIDATION) relues : exactes, inchangées — les quatre tentatives, la perte sans
transport d'échec et l'ordre des étages sont confirmés par l'exécution. Aucune
question holdout lue ni modifiée.

**Flashcards.** 10 ajoutées ; `FLC-7hfxx70j2rhg` reçoit le niveau RECALL. L'item
en porte **11** (4 RECALL, 2 UNDERSTANDING, 2 APPLICATION, 3 TRAP), décompte
relevé par script sur tous les fichiers de cartes.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`HandlerFailedException`, `shouldRetry` et `RedeliveryStamp(0)`,
absentes de la version `master` de la page et des fichiers de cartes.

**Contrôles réellement exécutés le 2026-10-02**

| Contrôle | Résultat |
|---|---|
| exécutions FrameworkBundle + Messenger 8.0.15 | résultats cités ci-dessus |
| sources du code relues en amont (branche 8.0) | le 2026-10-02 |
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| `php bin/cert build` | exit 0 |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 17 251 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

## Page 6 — *Middleware* — RAFFINÉE

`CRS-h9bfar3cxyet` · `OIT-amnpjdprky6z` · STANDARD · **376 → 619 mots** sur 900.
Aucun niveau promu. Exécutions sur FrameworkBundle + Messenger 8.0.15 : deux
middleware maison `A` et `B` qui journalisent leur passage, un message routé
vers le transport à file statique, un message sans handler, puis la pile du
bus relevée par réflexion sous quatre configurations.

### Déploiement précédent, lu en production

| Fusion | Run Pages | Ligne de smoke test |
|---|---|---|
| page 5 du lot 11 (PR #284, `65aa6b4`) | 36992202822, success | `ok  lot-11  the retries and failures page carries its four flashcard levels, the synchronous failure, the recoverable order and the failure counter` |

### Ce que la page ne disait pas

La page était exacte sur le fond — `$stack->next()`, les deux passages en
asynchrone, l'enveloppe plutôt que le message — mais elle ne disait ni **où**
se place un middleware maison, ni ce que fait `default_middleware`. Exécuté :

| `default_middleware` | Pile relevée | Sans handler | Avec handler |
|---|---|---|---|
| (défaut) | 5 middleware, `A`, `send_message`, `handle_message` | `NoHandlerForMessageException` | traité |
| `allow_no_handlers` | complète | accepté, aucun `HandledStamp` | traité |
| `false` | **vide** | accepté | **non traité**, sans erreur |

Le troisième cas est le piège : `false` retire `handle_message` avec le reste,
et un `dispatch()` ne fait plus rien sans le signaler.

### Confirmé par l'exécution ou la lecture

- Ordre de la pile, relevé sur le bus construit : `add_default_stamps_middleware`,
  `add_bus_name_stamp_middleware`, `reject_redelivered_message_middleware`,
  `dispatch_after_current_bus`, `failed_message_processing_middleware`, les
  middleware maison, `send_message`, `handle_message`. Lu dans
  `FrameworkExtension` : `deduplicate_middleware` s'ajoute au premier groupe
  quand Lock est activé (non exécuté : Lock désactivé dans le bac à sable).
- Deux passages : `A:dispatched`, `B` après `dispatch()` ; `A:received`, `B`
  et le handler pendant `messenger:consume`.
- Chaîne interrompue sur un message sans handler : aucun handler, aucun
  `HandledStamp`, aucune exception.
- Défauts lus dans `Configuration` : `allow_no_handlers` `false`,
  `allow_no_senders` `true`.

**Questions.** `QST-863psghen6px`, `QST-rpw04mwksvg4`, `QST-sx23khxjhrrg`
(LEARNING) et `QST-ap1bw4et0s4j` (VALIDATION) relues : exactes, inchangées —
l'interruption, les deux passages et la position de `handle_message` sont
confirmés par l'exécution. Aucune question holdout lue ni modifiée.

**Flashcards.** 10 ajoutées ; `FLC-c1cqrva0by96` reçoit le niveau RECALL. L'item
en porte **11** (3 RECALL, 3 UNDERSTANDING, 2 APPLICATION, 3 TRAP), décompte
relevé par script sur tous les fichiers de cartes.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`add_bus_name_stamp_middleware`, `allow_no_handlers` et
`default_middleware: false`,
absentes de la version `master` de la page et des fichiers de cartes.

**Contrôles réellement exécutés le 2026-10-02**

| Contrôle | Résultat |
|---|---|
| exécutions FrameworkBundle + Messenger 8.0.15 | résultats cités ci-dessus |
| sources du code relues en amont (branche 8.0) | le 2026-10-02 |
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| `php bin/cert build` | exit 0 |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 17 261 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

## Prochaine étape

Page 7 — *Events* (STANDARD, 427 / 900).
