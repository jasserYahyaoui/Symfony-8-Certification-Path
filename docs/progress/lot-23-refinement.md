# Raffinement pédagogique — Lot 23 (Miscellaneous)

Suite de la mission ouverte au lot 02 : approfondir les **pages de cours**
existantes — pièges d'examen, comportements implicites, flashcards aux quatre
niveaux — un lot à la fois, dans l'ordre numérique. Le lot 01 reste hors
périmètre sur instruction explicite (voir le journal du lot 02).

Même méthode qu'aux lots 03 à 22 : chaque affirmation vérifiée contre le code
de la branche 8.0 de Symfony ou la documentation correspondante, par exécution
chaque fois que c'est possible, jamais de mémoire ; budget `REV-001` respecté
sans promotion de niveau ; une branche, une PR, une CI verte, une fusion et un
smoke test de production **lu** par page.

## État par page (ordre officiel de l'item)

Chiffres relevés le 2026-10-03 par script sur les fichiers canoniques
(`syllabus-matrix.yml`, `content/**`) de `master` à `740761f`, avant la
première page.

| # | Page | Niveau | Mots / plafond | Flashcards | Statut |
|---|---|---|---|---|---|
| 1 | Process | STANDARD | 551 / 900 | 1 | à faire |

## Page 1 — *Process* — RAFFINÉE

`CRS-am0qe9xa8vzk` · `OIT-qkcr4bat6dsb` · STANDARD · **551 → 863 mots** sur 900.
Aucun niveau promu. Exécutions sur Process 8.0.13, sous Linux.

### Déploiement précédent, lu en production

| Fusion | Run Pages | Ligne de smoke test |
|---|---|---|
| rapport de fin de lot 22 (PR #332, `740761f`) | 37115173048, success | `ok  lot-22  the mime page carries its four flashcard levels, the executed extension order, the stripped Bcc and the javascript type` — le rapport ne touchant aucune page, c'est la dernière ligne de lot dans le journal du smoke test |

### Une affirmation incomplète : le signal de `stop()`

La page disait que `stop()` envoie `SIGKILL` par défaut, comme la documentation
8.0. Lu dans `Process::stop()` (8.0) : il envoie d'abord `SIGTERM`, attend le
délai (10 s par défaut), puis envoie le signal passé, `SIGKILL` par défaut, si le
processus tourne encore. Exécuté : un `sleep 30` s'arrête aussitôt, signal `15` ;
un script qui ignore `SIGTERM` ne s'arrête qu'à l'échéance. Corrigé ; l'écart
avec la documentation est signalé sur la page.

### Confirmé par l'exécution

| Cas | Résultat |
|---|---|
| `getTimeout()` par défaut | `60` |
| délai d'une seconde dépassé, processus asynchrone | `isRunning()` et `getOutput()` ne lèvent rien ; `checkTimeout()` et `wait()` lèvent |
| `disableOutput()` et `setIdleTimeout()`, dans les deux ordres | `LogicException` |
| `disableOutput()` pendant l'exécution | `RuntimeException` |
| variable du parent, avec `['OTHER' => 'x']` / avec `false` | héritée / absente |
| `run()` sur une sortie `3` / `mustRun()` | `3` / `ProcessFailedException` |
| `"${:NOPE}"` sans valeur | `InvalidArgumentException` |

**`QST-1kkdfwcv8b04` (LEARNING) → v2.** L'explication d'un distracteur affirmait
qu'aucune des deux formes ne lève sur un code non nul ; `mustRun()`, forme
bloquante, lève. L'énoncé vise désormais l'appel bloquant **simple** ; choix
inchangés, explications citant l'exécution.

**`QST-qz4v99xfbh3t` (VALIDATION).** Exacte ; son explication précise désormais
que `wait()` vérifie le délai et que lire la sortie ne le fait pas —
explication seule, version 1 inchangée.

**Questions.** `QST-cayjxkrtsp55`, `QST-x7skxav0fpzg`, `QST-5naehn50xrx3`
(LEARNING) relues : exactes, inchangées. L'item n'a pas de question holdout.

**Flashcards.** 10 ajoutées ; `FLC-1ppn91nts4vh` reçoit le niveau TRAP. L'item
en porte **11** (3 RECALL, 3 UNDERSTANDING, 2 APPLICATION, 3 TRAP), décompte
relevé par script sur tous les fichiers de cartes.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`SIGTERM`, `toujours héritée` et `il vérifie le délai`,
absentes de la version `master` de la page et des fichiers de cartes.

**Contrôles réellement exécutés le 2026-10-03**

| Contrôle | Résultat |
|---|---|
| exécutions Symfony 8.0 (versions citées ci-dessus) | résultats cités ci-dessus |
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| `php bin/cert build` | exit 0 |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun (dont `aud10`) |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 17 611 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

## Prochaine étape

Rapport de fin de lot 23, réconcilié par script, dans sa propre PR.
