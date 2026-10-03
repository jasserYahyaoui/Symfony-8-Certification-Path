# Raffinement pédagogique — Lot 19 (Miscellaneous)

Suite de la mission ouverte au lot 02 : approfondir les **pages de cours**
existantes — pièges d'examen, comportements implicites, flashcards aux quatre
niveaux — un lot à la fois, dans l'ordre numérique. Le lot 01 reste hors
périmètre sur instruction explicite (voir le journal du lot 02).

Même méthode qu'aux lots 03 à 18 : chaque affirmation vérifiée contre le code
de la branche 8.0 de Symfony ou la documentation correspondante, par exécution
chaque fois que c'est possible, jamais de mémoire ; quand la documentation et le
code divergent, le code l'emporte et l'écart est signalé sur la page ; budget
`REV-001` respecté sans promotion de niveau ; une branche, une PR, une CI verte,
une fusion et un smoke test de production **lu** par page.

## État par page (ordre officiel de l'item)

Chiffres relevés le 2026-10-03 par script sur les fichiers canoniques
(`syllabus-matrix.yml`, `content/**`) de `master` à `cf9a9dc`, avant la
première page.

| # | Page | Niveau | Mots / plafond | Flashcards | Statut |
|---|---|---|---|---|---|
| 1 | Clock | STANDARD | 686 / 900 | 1 | **RAFFINÉE** (PR #322) |

## Page 1 — *Clock* — RAFFINÉE

`CRS-6hcrc92xv6vt` · `OIT-qpjnpc56hjj0` · STANDARD · **686 → 882 mots** sur 900.
Aucun niveau promu. Exécutions sur Clock 8.0.8, DependencyInjection 8.0.15 et
PHPUnit 11.5.56.

### Déploiement précédent, lu en production

| Fusion | Run Pages | Ligne de smoke test |
|---|---|---|
| rapport de fin de lot 18 (PR #321, `cf9a9dc`) | 37106547182, success | `ok  lot-18  the cache page carries its four flashcard levels, the forced recompute, the namespace prefix and the shared directory` — le rapport ne touchant aucune page, c'est la dernière ligne de lot dans le journal du smoke test |

### Une divergence documentation / code : qui appelle `setClock()`

La page disait, comme `components/clock.rst` (8.0), que l'autoconfiguration
appelle `setClock()`. Lu dans `ClockAwareTrait` (8.0) : la méthode porte
`#[Required]`, que traite l'autocâblage. Exécuté sur un conteneur compilé :

| Service | `setClock()` appelé |
|---|---|
| `autowire` seul | oui |
| `autoconfigure` seul | **non** |
| les deux | oui |

Sans injection, `now()` se replie sur `new Clock()`, l'horloge globale :
exécuté, un `Clock::set(new MockClock(...))` s'y applique. Le code l'emporte,
l'écart est signalé sur la page.

**`QST-j5mgcztv2w1x` (VALIDATION) → v2.** Sa bonne réponse, « Autoconfiguration
calls the setter », était fausse au regard du code ; elle devient « Autowiring
calls the setter ». Le distracteur « The trait instantiates a system clock on
first use » était en partie vrai (le repli existe) : remplacé. Deux nouveaux
identifiants de choix ; l'énoncé précise les réglages par défaut du service ;
l'explication d'un autre distracteur, qui niait le repli sur l'horloge globale,
est corrigée.

### Confirmé ou précisé par l'exécution

| Cas | Résultat |
|---|---|
| `NativeClock::now()`, `now()` | un `DatePoint` |
| deux `now()` sur une `MockClock`, 100 ms d'écart | même heure, à la microseconde |
| `sleep(600)` | 0 s réelle, `15:20` → `15:30` |
| `mockTime('1996-07-01')` puis `'+2 days'` | `1996-07-03` |
| `mockTime(false)` / test suivant | `NativeClock` / `NativeClock` restaurée |

**Questions.** `QST-f2th6k9hk377`, `QST-39ytp7h11m85`, `QST-j8hmpd45jp1s`
(LEARNING) relues : exactes, inchangées. L'item a une question holdout : non
lue, non modifiée — **signal pour l'owner** : si elle reprend « autoconfiguration
appelle `setClock()` », elle est fausse au regard du code.

**Flashcards.** 10 ajoutées ; `FLC-bhhw8akr7mb5` reçoit le niveau TRAP. L'item
en porte **11** (3 RECALL, 3 UNDERSTANDING, 2 APPLICATION, 3 TRAP), décompte
relevé par script sur tous les fichiers de cartes.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`autocâblage`, `Required` et `déjà figée`,
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
| `composer gate-full` | exit 0 — 299 tests, 17 541 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |


# Rapport de fin de lot 19

Toutes les figures ci-dessous sont **réconciliées par script** depuis
`docs/syllabus/syllabus-matrix.yml`, `content/courses/**`,
`content/flashcards/**` et `content/questions/**` — jamais depuis un rapport
antérieur ni de mémoire. Base de comparaison : `cf9a9dc`, le commit de `master`
qui précède la page refondue (PR #322). État mesuré : `42306d2`. Le décompte de
cartes écrit dans l'entrée de page concorde avec les fichiers : **1 / 1**.

## Périmètre et couverture

**1** item officiel atomique porte `lot: lot-19` : `STANDARD`, niveau inchangé —
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
| Cours, corps en mots (plafond 900) | 686 | **882** | **+196** |
| Cartes sur l'item | 1 | **11** | **+10** |

Niveaux des cartes — **observation, jamais une cible** : `RECALL` 3 ·
`UNDERSTANDING` 3 · `APPLICATION` 2 · `TRAP` 3 ; aucune sans niveau. La carte
préexistante a reçu un niveau, sans autre modification.

**5** questions (3 `LEARNING`, 1 `VALIDATION`, 1 `HOLDOUT` dans
`mock-04-holdout.yml`), aucune ajoutée ni supprimée. **1** corrigée :
`QST-j5mgcztv2w1x` (VALIDATION), **v1 → v2**, bonne réponse corrigée et un
distracteur remplacé (deux nouveaux identifiants de choix). **0 question holdout modifiée**.
`POOL-002` : **0** item sans `VALIDATION`. **Matrice** : aucun texte modifié.

## Affirmations corrigées

| Affirmation | Exécuté ou lu | Décision |
|---|---|---|
| l'autoconfiguration appelle `setClock()` | `#[Required]` traité par l'autocâblage : injecté avec `autowire` seul, pas avec `autoconfigure` seul | page et `QST-j5mgcztv2w1x` corrigées |

## Ce que dit la documentation, ce que fait le code

| Documentation 8.0 | Code 8.0, exécuté | Décision |
|---|---|---|
| `components/clock.rst` : l'autoconfiguration appelle `setClock()` | l'autocâblage l'appelle ; sans injection, `now()` se replie sur l'horloge globale | le code l'emporte, l'écart est signalé sur la page |

## Signal pour le holdout — à revoir par l'owner

Une question holdout porte sur l'item, dans `mock-04-holdout.yml` ; non lue.
Si elle reprend « l'autoconfiguration appelle `setClock()` », elle est fausse au
regard du code 8.0.

## Déploiement

Page fusionnée par PR (#322), CI verte, déployée, smoke test lu : run 37107229522,
success — `ok  lot-19  the clock page carries its four flashcard levels, the autowired setter, the required attribute and the frozen interval`.

## Portes, au moment du rapport

Exécutées le 2026-10-03 sur la branche du rapport, au-dessus de `42306d2` :

| Contrôle | Résultat |
|---|---|
| `php bin/cert validate` | 0 bloquant (1 avertissement `PED-003` préexistant) |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `php bin/cert build` | exit 0 |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| `composer gate-full` | exit 0 — 299 tests, 17 541 assertions ; TOTAL VIOLATIONS: 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

## Résumé autonome

Lot 19 (*Clock*), 1 item STANDARD : couverture projet 163/163 inchangée ; cours
686 → 882 mots (+196) ; cartes 1 → 11, toutes niveau posé ; 5 questions, 1
corrigée (v2, la bonne réponse disait « autoconfiguration »), 0 holdout
modifiée ; page déployée, smoke test lu ; un écart documentation/code signalé
(`setClock()` appelé par l'autocâblage).
