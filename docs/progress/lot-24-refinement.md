# Raffinement pédagogique — Lot 24 (Miscellaneous)

Suite de la mission ouverte au lot 02 : approfondir les **pages de cours**
existantes — pièges d'examen, comportements implicites, flashcards aux quatre
niveaux — un lot à la fois, dans l'ordre numérique. Le lot 01 reste hors
périmètre sur instruction explicite (voir le journal du lot 02).

Même méthode qu'aux lots 03 à 23 : chaque affirmation vérifiée contre le code
de la branche 8.0 de Symfony ou la documentation correspondante, par exécution
chaque fois que c'est possible, jamais de mémoire ; budget `REV-001` respecté
sans promotion de niveau ; une branche, une PR, une CI verte, une fusion et un
smoke test de production **lu** par page.

## État par page (ordre officiel de l'item)

Chiffres relevés le 2026-10-03 par script sur les fichiers canoniques
(`syllabus-matrix.yml`, `content/**`) de `master` à `f807e8b`, avant la
première page.

| # | Page | Niveau | Mots / plafond | Flashcards | Statut |
|---|---|---|---|---|---|
| 1 | PropertyAccess | STANDARD | 426 / 900 | 1 | à faire |

## Page 1 — *PropertyAccess* — RAFFINÉE

`CRS-5vz7w1ny9shd` · `OIT-43pt66xsft9f` · STANDARD · **426 → 751 mots** sur 900.
Aucun niveau promu. Exécutions sur PropertyAccess 8.0.8, avec des accesseurs
qui journalisent leurs appels.

### Déploiement précédent, lu en production

| Fusion | Run Pages | Ligne de smoke test |
|---|---|---|
| rapport de fin de lot 23 (PR #334, `f807e8b`) | 37116715289, success | `ok  lot-23  the process page carries its four flashcard levels, the two stop signals, the timeout checkers and the inherited variable` — le rapport ne touchant aucune page, c'est la dernière ligne de lot dans le journal du smoke test |

### Une affirmation fausse : « `isReadable()` ne lit pas »

La page disait qu'`isReadable()` et `isWritable()` répondent « sans l'appeler »,
et en piège qu'`isReadable()` ne lit pas. Lu dans
`PropertyAccessor::isReadable()` (8.0) : elle parcourt le chemin entier par
`readPropertiesUntil()` et convertit l'exception en `false`. Exécuté : le getter
est appelé. `isWritable()` n'appelle pas le setter, mais lit les maillons qui
précèdent le dernier (`getChildren()` pour `children[0].firstName`). La
documentation 8.0 dit qu'`isReadable()` évite d'appeler `getValue()`, ce qui est
exact ; la page en avait tiré une conclusion fausse. Corrigé.

### Confirmé par l'exécution

| Cas | Résultat |
|---|---|
| `[age]` absent / avec `enableExceptionOnInvalidIndex()` | `null` / `NoSuchIndexException` |
| `birthday` absent / avec `disableExceptionOnInvalidPropertyPath()` | `NoSuchPropertyException` / `null` |
| `a` sur un tableau ; `[x]` sur un `stdClass` | `NoSuchPropertyException` ; `NoSuchIndexException` |
| `person.firstname` / `person?.firstname`, `person` à `null` | `UnexpectedTypeException` / `null` |
| `person?.firstname`, `person` sans `firstname` | `NoSuchPropertyException` |
| `__get()`, `__set()` sans configuration ; `__call()` | utilisés ; seulement après `enableMagicCall()` |
| écriture de `children` avec `setChildren()` présent | `getChildren()`, `removeChild()`, `addChild()` |

**`QST-jfzfmtt469xk` (LEARNING).** Exacte pour l'écriture ; son explication
étendait la même absence d'effet à la lecture. Explication corrigée, citant
l'exécution — version 1 inchangée.

**Questions.** `QST-rx52h6xb2zvd` (VALIDATION), `QST-rxnqtgnv8gnf` et
`QST-jtsy60arwa1r` (LEARNING) relues : exactes, inchangées. L'item n'a pas de
question holdout.

**Flashcards.** 10 ajoutées ; `FLC-zggyac2jqrp5` reçoit le niveau TRAP. L'item
en porte **11** (3 RECALL, 3 UNDERSTANDING, 2 APPLICATION, 3 TRAP), décompte
relevé par script sur tous les fichiers de cartes.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`appelle le getter`, `person?.firstname` et `emporte sur le setter`,
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
| `composer gate-full` | exit 0 — 299 tests, 17 621 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

## Prochaine étape

Rapport de fin de lot 24, réconcilié par script, dans sa propre PR.
