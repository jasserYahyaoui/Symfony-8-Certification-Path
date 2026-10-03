# Raffinement pédagogique — Lot 21 (Miscellaneous)

Suite de la mission ouverte au lot 02 : approfondir les **pages de cours**
existantes — pièges d'examen, comportements implicites, flashcards aux quatre
niveaux — un lot à la fois, dans l'ordre numérique. Le lot 01 reste hors
périmètre sur instruction explicite (voir le journal du lot 02).

Même méthode qu'aux lots 03 à 20 : chaque affirmation vérifiée contre le code
de la branche 8.0 de Symfony ou la documentation correspondante, par exécution
chaque fois que c'est possible, jamais de mémoire ; budget `REV-001` respecté
sans promotion de niveau ; une branche, une PR, une CI verte, une fusion et un
smoke test de production **lu** par page.

## État par page (ordre officiel de l'item)

Chiffres relevés le 2026-10-03 par script sur les fichiers canoniques
(`syllabus-matrix.yml`, `content/**`) de `master` à `6899d5f`, avant la
première page.

| # | Page | Niveau | Mots / plafond | Flashcards | Statut |
|---|---|---|---|---|---|
| 1 | Filesystem | STANDARD | 516 / 900 | 1 | à faire |
| 2 | Finder | STANDARD | 542 / 900 | 1 | à faire |

## Page 1 — *Filesystem* — RAFFINÉE

`CRS-h9nryezt8wnb` · `OIT-tjn6kyvyc2h9` · STANDARD · **516 → 654 mots** sur 900.
Aucun niveau promu. Exécutions sur Filesystem 8.0.15, `umask` à `022`.

### Déploiement précédent, lu en production

| Fusion | Run Pages | Ligne de smoke test |
|---|---|---|
| rapport de fin de lot 20 (PR #326, `6899d5f`) | 37110506422, success | `ok  lot-20  the event page carries its four flashcard levels, the iterable generic event, the stopped listeners and the argument interfaces` — le rapport ne touchant aucune page, c'est la dernière ligne de lot dans le journal du smoke test |

### Une affirmation incomplète : `getPath()`

« `getPath()` nomme le chemin fautif » ne vaut pas toujours. Lu dans
`Filesystem::readFile()` (8.0) et exécuté : sur un répertoire, l'`IOException`
est construite sans chemin, et `getPath()` rend `null` ; le chemin n'est que
dans le message. Précisé sur la page.

### Confirmé par l'exécution

| Appel | Résultat |
|---|---|
| `mkdir('a/b/c')` | créé récursivement, mode `0755` |
| `mkdir()` sur l'existant | aucune exception |
| `readFile()` sur un fichier absent, sur un répertoire | `IOException` |
| `rename()` sur une cible existante / avec `true` | `IOException` / cible remplacée |
| `dumpFile('x/y/f.txt', …)` | `x/y` créé |
| `Path::canonicalize('/var/www/../lib/./x/')` | `/var/lib/x`, inexistant |

**Questions.** `QST-q1g70cf81t0a`, `QST-3xhbezx2h2e2` (LEARNING) et
`QST-at67jvy54v5b` (VALIDATION) relues : exactes, inchangées. L'item n'a pas de
question holdout.

**Flashcards.** 10 ajoutées ; `FLC-e2refpyf7ra0` reçoit le niveau UNDERSTANDING.
L'item en porte **11** (3 RECALL, 3 UNDERSTANDING, 2 APPLICATION, 3 TRAP),
décompte relevé par script sur tous les fichiers de cartes.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`0755`, `sans chemin` et `cible est remplacée`,
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
| `composer gate-full` | exit 0 — 299 tests, 17 571 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

## Prochaine étape

Page 2 — *Finder* (STANDARD, 542 / 900).
