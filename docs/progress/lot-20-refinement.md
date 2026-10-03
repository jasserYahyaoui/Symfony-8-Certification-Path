# Raffinement pédagogique — Lot 20 (Miscellaneous)

Suite de la mission ouverte au lot 02 : approfondir les **pages de cours**
existantes — pièges d'examen, comportements implicites, flashcards aux quatre
niveaux — un lot à la fois, dans l'ordre numérique. Le lot 01 reste hors
périmètre sur instruction explicite (voir le journal du lot 02).

Même méthode qu'aux lots 03 à 19 : chaque affirmation vérifiée contre le code
de la branche 8.0 de Symfony ou la documentation correspondante, par exécution
chaque fois que c'est possible, jamais de mémoire ; budget `REV-001` respecté
sans promotion de niveau ; une branche, une PR, une CI verte, une fusion et un
smoke test de production **lu** par page.

## État par page (ordre officiel de l'item)

Chiffres relevés le 2026-10-03 par script sur les fichiers canoniques
(`syllabus-matrix.yml`, `content/**`) de `master` à `f618a94`, avant la
première page.

| # | Page | Niveau | Mots / plafond | Flashcards | Statut |
|---|---|---|---|---|---|
| 1 | EventDispatcher | STANDARD | 579 / 900 | 1 | **RAFFINÉE** (PR #324) |
| 2 | Event | STANDARD | 469 / 900 | 1 | à faire |

## Page 1 — *EventDispatcher* — RAFFINÉE

`CRS-wbgw87za3w5n` · `OIT-s601ppsyb9f7` · STANDARD · **579 → 705 mots** sur 900.
Aucun niveau promu. Exécutions sur EventDispatcher et DependencyInjection
8.0.15 : un conteneur compilé avec `RegisterListenersPass` et trois écouteurs
par attribut, puis un répartiteur nu.

### Déploiement précédent, lu en production

| Fusion | Run Pages | Ligne de smoke test |
|---|---|---|
| rapport de fin de lot 19 (PR #323, `f618a94`) | 37108120211, success | `ok  lot-19  the clock page carries its four flashcard levels, the autowired setter, the required attribute and the frozen interval` — le rapport ne touchant aucune page, c'est la dernière ligne de lot dans le journal du smoke test |

### La page était exacte ; l'exécution la précise

| Cas | Résultat |
|---|---|
| `event: 'acme.foo_bar'`, `onAcmeFooBar()` présente | `onAcmeFooBar()` appelée |
| `event: 'acme.other'`, pas de `onAcmeOther()` | `__invoke()`, en repli |
| `#[AsEventListener]` sans `event`, `__invoke(CustomEvent $e)` | écoute `CustomEvent::class` |
| `dispatch($e)` sans nom | l'écouteur reçoit le nom `CustomEvent::class` et le répartiteur |
| retour de `dispatch()` | le même objet événement |
| priorités `0`, `0`, `10`, `-5` | `10`, `0`, `0` (ordre d'ajout), `-5` |

Sans méthode correspondante ni `__invoke()`, la compilation échoue : lu dans
`RegisterListenersPass`, non exécuté.

**Questions.** `QST-xa6bq0yg86z5`, `QST-d95psbyzz9x4` (LEARNING) et
`QST-c6j2y7wpcswr` (VALIDATION) relues contre la documentation et le code :
exactes, inchangées. L'item a une question holdout : non lue, non modifiée.

**Flashcards.** 10 ajoutées ; `FLC-tz5z2qr23ew3` reçoit le niveau TRAP. L'item
en porte **11** (3 RECALL, 3 UNDERSTANDING, 2 APPLICATION, 3 TRAP), décompte
relevé par script sur tous les fichiers de cartes.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`onAcmeFooBar`, `repli` et `RegisterListenersPass`,
absentes de la version `master` de la page et des fichiers de cartes.

**Contrôles réellement exécutés le 2026-10-03**

| Contrôle | Résultat |
|---|---|
| exécutions Symfony 8.0.15 | résultats cités ci-dessus |
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| `php bin/cert build` | exit 0 |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun (dont `aud10`) |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 17 551 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

## Page 2 — *Event* — RAFFINÉE

`CRS-fhb1cbhnyaxx` · `OIT-8xcczyjyyanz` · STANDARD · **469 → 519 mots** sur 900.
Aucun niveau promu. Exécutions sur EventDispatcher 8.0.15.

### Déploiement précédent, lu en production

| Fusion | Run Pages | Ligne de smoke test |
|---|---|---|
| page 1 du lot 20 (PR #324, `fdca3fb`) | 37108917875, success | `ok  lot-20  the event dispatcher page carries its four flashcard levels, the default method, the invoke fallback and the compiler pass` |

### La page était exacte ; l'exécution la précise

| Cas | Résultat |
|---|---|
| écouteurs `10`, `5` (qui arrête), `0`, `-5` | seuls `10` et `5` tournent |
| `isPropagationStopped()` après `dispatch()` | `true` |
| `GenericEvent('subj', ['k' => 'v'])['k']` | `v` |
| `foreach` sur ce `GenericEvent` | la clé `k` |
| interfaces de `GenericEvent` | `StoppableEventInterface`, `ArrayAccess`, `IteratorAggregate` |

La page ne citait pas `IteratorAggregate` : ajouté.

**Questions.** `QST-dxvqjgxd3vyq`, `QST-99x1vnm3rtx4` (LEARNING) et
`QST-yxwtwshs91fd` (VALIDATION) relues : exactes, inchangées — la classe de base
`Event` n'est pas abstraite, ce que confirme la lecture. L'item n'a pas de
question holdout.

**Flashcards.** 10 ajoutées ; `FLC-6eg8g2bt06nt` reçoit le niveau RECALL. L'item
en porte **11** (3 RECALL, 3 UNDERSTANDING, 2 APPLICATION, 3 TRAP), décompte
relevé par script sur tous les fichiers de cartes.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`IteratorAggregate`, `seuls` et `toutes deux sur ses arguments`,
absentes de la version `master` de la page et des fichiers de cartes.

**Contrôles réellement exécutés le 2026-10-03**

| Contrôle | Résultat |
|---|---|
| exécutions Symfony 8.0.15 | résultats cités ci-dessus |
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| `php bin/cert build` | exit 0 |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun (dont `aud10`) |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 17 561 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

## Prochaine étape

Rapport de fin de lot 20, réconcilié par script, dans sa propre PR.
