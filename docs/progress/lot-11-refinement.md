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
| 3 | Messages and handlers | STANDARD | 381 / 900 | 1 | à faire |
| 4 | Workers | STANDARD | 408 / 900 | 1 | à faire |
| 5 | Retries and failures | DEEP | 542 / 1200 | 1 | à faire |
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

## Prochaine étape

Page 4 — *Workers* (STANDARD, 408 / 900).
