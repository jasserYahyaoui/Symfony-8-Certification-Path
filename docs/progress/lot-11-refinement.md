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
| 1 | Messenger component | STANDARD | 382 / 900 | 1 | à faire |
| 2 | Transports | STANDARD | 386 / 900 | 1 | à faire |
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

## Prochaine étape

Page 2 — *Transports* (STANDARD, 386 / 900).
