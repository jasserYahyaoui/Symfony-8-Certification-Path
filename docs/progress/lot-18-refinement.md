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

Chiffres relevés le 2026-10-03 par script sur les fichiers canoniques
(`syllabus-matrix.yml`, `content/**`) de `master` à `2c2a0a5`, avant la
première page.

| # | Page | Niveau | Mots / plafond | Flashcards | Statut |
|---|---|---|---|---|---|
| 1 | Cache | STANDARD | 880 / 900 | 1 | **RAFFINÉE** (PR #320) |

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
| `composer gate-full` | exit 0 — 299 tests, 17 531 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |


# Rapport de fin de lot 18

Toutes les figures ci-dessous sont **réconciliées par script** depuis
`docs/syllabus/syllabus-matrix.yml`, `content/courses/**`,
`content/flashcards/**` et `content/questions/**` — jamais depuis un rapport
antérieur ni de mémoire. Base de comparaison : `2c2a0a5`, le commit de `master`
qui précède la page refondue (PR #320). État mesuré : `c300d05`. Le décompte de
cartes écrit dans l'entrée de page concorde avec les fichiers : **1 / 1**.

## Périmètre et couverture

**1** item officiel atomique porte `lot: lot-18` : `STANDARD`, niveau inchangé —
une **observation**, aucune cible.

```text
EXAM_READY atomiques officiels / total atomiques officiels
= 163 / 163 = 100,0 %
```

Chiffre **cumulatif, sur tout le projet** ; sous-ensemble du lot : **1 / 1**.
Aucun des deux n'a bougé : **ce lot n'a pas fait progresser la couverture**.

## Volume, cartes, questions

| | Avant | Après | Nouveau |
|---|---|---|---|
| Cours, corps en mots (plafond 900) | 880 | **896** | **+16** |
| Cartes sur l'item | 1 | **11** | **+10** |

Niveaux des cartes — **observation, jamais une cible** : `RECALL` 3 ·
`UNDERSTANDING` 3 · `APPLICATION` 2 · `TRAP` 3 ; aucune sans niveau. La carte
préexistante a reçu un niveau, sans autre modification.

**5** questions (3 `LEARNING`, 1 `VALIDATION`, 1 `HOLDOUT` dans
`mock-04-holdout.yml`), aucune ajoutée ni supprimée. **1** corrigée :
`QST-7kszpvjs383j` (LEARNING), **v1 → v2**, un distracteur défendable remplacé
(nouvel identifiant de choix). **0 question holdout modifiée**.
`POOL-002` : **0** item sans `VALIDATION`. **Matrice** : aucun texte modifié.

## Affirmations corrigées

Aucune affirmation de la page n'était fausse. La page, déjà à 20 mots du
plafond, gagne une ligne exécutée (`beta = INF` sur une clé présente) ; le reste
de l'apport passe dans les cartes et dans une question.

| Question | Défaut | Décision |
|---|---|---|
| `QST-7kszpvjs383j` | distracteur « two names for the same object », défendable : `new FilesystemAdapter()` rend l'objet même qui sert de pool | remplacé, v2 |

## Signal pour le holdout — à revoir par l'owner

Une question holdout porte sur l'item, dans `mock-04-holdout.yml` ; non lue.
Fait qui pourrait la concerner : en code, adaptateur et pool sont le même objet
(`new FilesystemAdapter()`), ce qui rend fragile toute opposition stricte entre
les deux termes.

## Erreur de méthode : deux dates

L'entrée de page du lot 18 datait du 2026-10-02 son relevé initial et ses
contrôles, et le rapport du lot 17 ses portes : tous ont été exécutés le
2026-10-03 (UTC), comme l'attestent les commits `9a309f2` et `d8053e0`. Le
gabarit avait été rédigé la veille. Les trois dates sont corrigées dans ce
rapport ; les brouillons des lots suivants aussi.

## Déploiement

Page fusionnée par PR (#320), CI verte, déployée, smoke test lu : run 37105694680,
success — `ok  lot-18  the cache page carries its four flashcard levels, the forced recompute, the namespace prefix and the shared directory`.

## Portes, au moment du rapport

Exécutées le 2026-10-03 sur la branche du rapport, au-dessus de `c300d05` :

| Contrôle | Résultat |
|---|---|
| `php bin/cert validate` | 0 bloquant (1 avertissement `PED-003` préexistant) |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `php bin/cert build` | exit 0 |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| `composer gate-full` | exit 0 — 299 tests, 17 531 assertions ; TOTAL VIOLATIONS: 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

## Résumé autonome

Lot 18 (*Cache*), 1 item STANDARD : couverture projet 163/163 inchangée ; cours
880 → 896 mots (+16) ; cartes 1 → 11, toutes niveau posé ; 5 questions, 1
corrigée (v2, distracteur défendable), 0 holdout modifiée ; page déployée, smoke
test lu ; aucune affirmation de la page fausse.
