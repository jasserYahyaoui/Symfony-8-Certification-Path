# Raffinement pédagogique — Lot 18 (Miscellaneous)

Suite de la mission ouverte au lot 02 : approfondir les **pages de cours**
existantes — pièges d'examen, comportements implicites, flashcards aux quatre
niveaux — un lot à la fois, dans l'ordre numérique. Le lot 01 reste hors
périmètre sur instruction explicite (voir le journal du lot 02).

Même méthode qu'aux lots 03 à 17 : chaque affirmation vérifiée contre le code
de la branche 8.0 de Symfony ou la documentation correspondante, par exécution
chaque fois que c'est possible, jamais de mémoire ; budget `REV-001` respecté
sans promotion de niveau ; une branche, une PR, une CI verte, une fusion et un
smoke test de production **lu** par page.

## État par page (ordre officiel de l'item)

Chiffres relevés le 2026-10-02 par script sur les fichiers canoniques
(`syllabus-matrix.yml`, `content/**`) de `master` à `2c2a0a5`, avant la
première page.

| # | Page | Niveau | Mots / plafond | Flashcards | Statut |
|---|---|---|---|---|---|
| 1 | Cache | STANDARD | 880 / 900 | 1 | à faire |

## Page 1 — *Cache* — RAFFINÉE

`CRS-p6yhxmpxrzbs` · `OIT-78k7kbfpgfxt` · STANDARD · **880 → 896 mots** sur 900.
Aucun niveau promu. La page était déjà à 20 mots du plafond : l'apport porte
sur les cartes et sur une question. Exécutions sur Cache 8.0.15 : `ArrayAdapter`,
deux `FilesystemAdapter` sur un même répertoire, `TagAwareAdapter`.

### Déploiement précédent, lu en production

| Fusion | Run Pages | Ligne de smoke test |
|---|---|---|
| rapport de fin de lot 17 (PR #319, `2c2a0a5`) | 37104889940, success | `ok  lot-17  the HTTP caching page carries its four flashcard levels, the cacheable methods, the attribute naming and the passed POST` — le rapport ne touchant aucune page, c'est la dernière ligne de lot dans le journal du smoke test |

### Confirmé par l'exécution

| Cas | Résultat |
|---|---|
| deux `get()` sur la même clé | un seul calcul |
| `beta = INF` sur une clé présente | rappel exécuté, `isHit()` = `true` dedans |
| grand `beta`, `FilesystemAdapter` | recalcul anticipé, `isHit()` = `true` dedans |
| `$save = false` | `hasItem()` = `false` ensuite |
| deux pools, même répertoire, même clé | deux valeurs distinctes |
| `invalidateTags(['bar'])` | l'item `foo`+`bar` disparaît, l'item `foo` reste |

La page en garde une ligne, sur `INF` ; le reste passe dans les cartes.

### Une question au distracteur défendable

`QST-7kszpvjs383j` (LEARNING) proposait « They are two names for the same
object » pour opposer adaptateur et pool, expliqué par « a pool is created from
an adapter ». En code, `new FilesystemAdapter()` rend l'objet même qui sert de
pool : le distracteur était défendable. **→ v2** : remplacé par « The pool
implements storage, the adapter names it », faux ; nouvel identifiant de choix.

**Questions.** `QST-ch9mc2vedk60`, `QST-w6g2sgj7apm1` (LEARNING) et
`QST-jr033rm9hfx5` (VALIDATION) relues : exactes, inchangées. L'item a une
question holdout : non lue, non modifiée.

**Flashcards.** 10 ajoutées ; `FLC-5ph49r9vszd1` reçoit le niveau UNDERSTANDING.
L'item en porte **11** (3 RECALL, 3 UNDERSTANDING, 2 APPLICATION, 3 TRAP),
décompte relevé par script sur tous les fichiers de cartes.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`clé présente`, `préfixe ses clés` et `FilesystemAdapter`,
absentes de la version `master` de la page et des fichiers de cartes.

**Contrôles réellement exécutés le 2026-10-02**

| Contrôle | Résultat |
|---|---|
| exécutions Symfony 8.0.15 | résultats cités ci-dessus |
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| `php bin/cert build` | exit 0 |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun (dont `aud10`) |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 17 531 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

## Prochaine étape

Rapport de fin de lot 18, réconcilié par script, dans sa propre PR.
