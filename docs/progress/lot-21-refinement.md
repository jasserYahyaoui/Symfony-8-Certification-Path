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
| 1 | Filesystem | STANDARD | 516 / 900 | 1 | **RAFFINÉE** (PR #327) |
| 2 | Finder | STANDARD | 542 / 900 | 1 | **RAFFINÉE** (PR #328) |

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

## Page 2 — *Finder* — RAFFINÉE

`CRS-jwhqec35jczn` · `OIT-a1anzcv85my3` · STANDARD · **542 → 629 mots** sur 900.
Aucun niveau promu. Exécutions sur Finder 8.0.14.

### Déploiement précédent, lu en production

| Fusion | Run Pages | Ligne de smoke test |
|---|---|---|
| page 1 du lot 21 (PR #327, `2b5de52`) | 37111130275, success | `ok  lot-21  the filesystem page carries its four flashcard levels, the umask mode, the pathless exception and the overwrite` |

### Une précision : quand les clés se heurtent

La page reprenait l'avertissement de la documentation : après plusieurs `in()`,
`iterator_to_array()` peut perdre des entrées. Exécuté : la clé est le chemin du
fichier. Deux `a.txt` dans deux répertoires distincts donnent deux clés ; seuls
deux `in()` qui se recouvrent (`f1` et `f1/sub`) atteignent le même fichier deux
fois — 5 comptés, 4 dans le tableau, 5 avec `false`.

**`QST-y8xczgk8cn9z` (VALIDATION) → v2.** L'énoncé présentait la perte comme un
effet de toute recherche multi-emplacements ; il précise désormais que les
emplacements s'emboîtent. Choix inchangés ; l'explication d'un distracteur et
l'explication générale citent l'exécution.

### Confirmé par l'exécution

| Cas | Résultat |
|---|---|
| deux `name()` successifs sur le même objet | 1 puis 2 fichiers |
| sans `files()` ni `directories()` | 5 résultats : 4 fichiers, 1 répertoire |
| itération sans `in()` | `LogicException` |

**Questions.** `QST-9vdyrx07zz9j`, `QST-5a5z84vv3kvc`, `QST-tbsm9m352jya`
(LEARNING) relues : exactes, inchangées. L'item n'a pas de question holdout.

**Flashcards.** 10 ajoutées ; `FLC-zw88x7tcje1m` reçoit le niveau TRAP. L'item
en porte **11** (3 RECALL, 3 UNDERSTANDING, 2 APPLICATION, 3 TRAP), décompte
relevé par script sur tous les fichiers de cartes.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`se recouvrent`, `LogicException` et `sans collision`,
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
| `composer gate-full` | exit 0 — 299 tests, 17 581 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

# Rapport de fin de lot 21

Toutes les figures ci-dessous sont **réconciliées par script** depuis
`docs/syllabus/syllabus-matrix.yml`, `content/courses/**`,
`content/flashcards/**` et `content/questions/**` — jamais depuis un rapport
antérieur ni de mémoire. Base de comparaison : `6899d5f`, le commit de `master`
qui précède la première page refondue (PR #327). État mesuré : `11ec6b9`
(fusion de la page 2). Un second script a confronté les décomptes de cartes
écrits dans les deux entrées de page aux fichiers : **2 / 2** concordent.

## Périmètre

**2** items officiels atomiques portent `lot: lot-21` dans la matrice, tous
`STANDARD` — niveaux inchangés. Cette répartition est une **observation** :
aucune cible n'existe.

## Couverture — formule unique (§3.5)

```text
EXAM_READY atomiques officiels / total atomiques officiels
= 163 / 163 = 100,0 %
```

Ce chiffre est **cumulatif et porte sur tout le projet**. Le sous-ensemble du
lot 21 est **2 / 2**. Aucun des deux n'a bougé : **ce lot n'a pas fait
progresser la couverture**.

## Volume de cours — corps en mots, front matter exclu

| | Avant campagne | Après | Nouveau |
|---|---|---|---|
| 2 cours du lot 21 | 1 058 | **1 283** | **+225** |

Aucune page ne dépasse son budget `REV-001` (`STANDARD`, 900). La plus proche :
*Filesystem*, 654.

## Flashcards

| | Avant campagne | Après | Nouveau |
|---|---|---|---|
| Cartes sur les items du lot 21 | 2 | **22** | **+20** |

Répartition par niveau — **observation, jamais une cible** :
`RECALL` 6 · `UNDERSTANDING` 6 · `APPLICATION` 4 · `TRAP` 6. **Zéro carte du
lot sans niveau** ; chaque item en porte 11. Les deux cartes préexistantes ont
reçu un niveau ; aucune n'a été supprimée ni corrigée au-delà.

## Questions et pools

**7** questions portent sur les items du lot 21 — aucune ajoutée, aucune
supprimée, **une modifiée** :

| Pool | Nombre | Fichier |
|---|---|---|
| `LEARNING` | 5 | `lot-21-miscellaneous.yml` |
| `VALIDATION` | 2 | `lot-21-miscellaneous.yml` |
| `HOLDOUT` | 0 | — |

`QST-y8xczgk8cn9z` (VALIDATION), **v1 → v2** : l'énoncé présentait la perte
d'entrées comme l'effet de toute recherche multi-emplacements ; il précise
désormais que les emplacements s'emboîtent. Choix inchangés. Les six autres ont
été relues : exactes. **Aucune question holdout** ne porte sur ce lot.
`POOL-002` : 2 items `STANDARD` `EXAM_READY`, **0** sans question `VALIDATION`.
**Matrice** : aucun texte modifié.

## Affirmations corrigées sur les pages

| Page | Affirmation | Décision |
|---|---|---|
| 1 | `getPath()` nomme le chemin fautif | incomplète — `readFile()` sur un répertoire lève une `IOException` sans chemin ; précisée |
| 2 | plusieurs `in()` peuvent faire perdre des entrées à `iterator_to_array()` | précisée — la clé est le chemin : seuls des emplacements qui se recouvrent se heurtent |

Aucune divergence documentation / code : les deux précisions complètent la
documentation sans la contredire.

## Signaux pour le holdout

Aucun : le lot n'a pas de question holdout.

## Déploiements

Les deux pages ont été fusionnées par PR (#327, #328), chacune avec CI verte,
déployée par le workflow Pages, et sa ligne de smoke test lue en production. La
page 2 : run 37111867715, success — `ok  lot-21  the finder page carries its four flashcard levels, the overlapping locations, the missing location and the distinct keys`.

## Portes, au moment du rapport

Exécutées le 2026-10-03 sur la branche du rapport, au-dessus de `11ec6b9` :

| Contrôle | Résultat |
|---|---|
| `php bin/cert validate` | 0 bloquant (1 avertissement `PED-003` préexistant) |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `php bin/cert build` | exit 0 |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| `composer gate-full` | exit 0 — 299 tests, 17 581 assertions ; TOTAL VIOLATIONS: 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |
| décomptes de cartes des deux entrées de page contre les fichiers | 2 / 2 concordants |

## Résumé autonome

Lot 21 (*Miscellaneous* : *Filesystem*, *Finder*), 2 items STANDARD :
couverture projet 163/163 inchangée ; cours 1 058 → 1 283 mots (+225), aucun
dépassement de budget ; flashcards 2 → 22 (+20), toutes niveau posé ; 7
questions, 1 corrigée (v2), aucune holdout sur le lot ; deux pages déployées,
smoke tests lus ; deux affirmations précisées (`getPath()` sans chemin, collision
des clés de `Finder`).
