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
| 2 | Event | STANDARD | 469 / 900 | 1 | **RAFFINÉE** (PR #325) |

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

# Rapport de fin de lot 20

Toutes les figures ci-dessous sont **réconciliées par script** depuis
`docs/syllabus/syllabus-matrix.yml`, `content/courses/**`,
`content/flashcards/**` et `content/questions/**` — jamais depuis un rapport
antérieur ni de mémoire. Base de comparaison : `f618a94`, le commit de `master`
qui précède la première page refondue (PR #324). État mesuré : `b422283`
(fusion de la page 2). Un second script a confronté les décomptes de cartes
écrits dans les deux entrées de page aux fichiers : **2 / 2** concordent.

## Périmètre

**2** items officiels atomiques portent `lot: lot-20` dans la matrice, tous
`STANDARD` — niveaux inchangés. Cette répartition est une **observation** :
aucune cible n'existe.

## Couverture — formule unique (§3.5)

```text
EXAM_READY atomiques officiels / total atomiques officiels
= 163 / 163 = 100,0 %
```

Ce chiffre est **cumulatif et porte sur tout le projet**. Le sous-ensemble du
lot 20 est **2 / 2**. Aucun des deux n'a bougé : **ce lot n'a pas fait
progresser la couverture**.

## Volume de cours — corps en mots, front matter exclu

| | Avant campagne | Après | Nouveau |
|---|---|---|---|
| 2 cours du lot 20 | 1 048 | **1 224** | **+176** |

Aucune page ne dépasse son budget `REV-001` (`STANDARD`, 900). La plus proche :
*EventDispatcher*, 705.

## Flashcards

| | Avant campagne | Après | Nouveau |
|---|---|---|---|
| Cartes sur les items du lot 20 | 2 | **22** | **+20** |

Répartition par niveau — **observation, jamais une cible** :
`RECALL` 6 · `UNDERSTANDING` 6 · `APPLICATION` 4 · `TRAP` 6. **Zéro carte du
lot sans niveau** ; chaque item en porte 11. Les deux cartes préexistantes ont
reçu un niveau ; aucune n'a été supprimée ni corrigée au-delà.

## Questions et pools

**7** questions portent sur les items du lot 20 — aucune ajoutée, aucune
supprimée, **aucune modifiée** :

| Pool | Nombre | Fichier |
|---|---|---|
| `LEARNING` | 4 | `lot-20-miscellaneous.yml` |
| `VALIDATION` | 2 | `lot-20-miscellaneous.yml` |
| `HOLDOUT` | 1 | `mock-04-holdout.yml` |

Les six questions non holdout ont été relues contre la documentation et le code
8.0 : exactes. **0 question holdout modifiée** (comparaison par empreinte
SHA-256, sans lecture du contenu). `POOL-002` : 2 items `STANDARD` `EXAM_READY`,
**0** sans question `VALIDATION`. **Matrice** : aucun texte modifié.

## Affirmations corrigées sur les pages

Aucune : les deux pages étaient exactes. L'exécution les précise — méthode
d'écoute dérivée du nom d'événement (`onAcmeFooBar`), repli sur `__invoke()`,
`dispatch()` qui rend l'événement, ordre des priorités ; seuls les écouteurs
postérieurs à l'arrêt de propagation sont sautés, et `GenericEvent` est aussi
un `IteratorAggregate`, ce que la page 2 ne disait pas.

## Signaux pour le holdout — à revoir par l'owner

Une question holdout porte sur l'item *EventDispatcher*, dans
`mock-04-holdout.yml`. Elle n'a pas été lue. Faits établis qui pourraient la
concerner : sans `method`, l'écouteur appelle `on` + le nom d'événement en
camel case, puis `__invoke()` en repli ; sans `event`, l'attribut écoute la
classe du paramètre de `__invoke()`.

## Déploiements

Les deux pages ont été fusionnées par PR (#324, #325), chacune avec CI verte,
déployée par le workflow Pages, et sa ligne de smoke test lue en production. La
page 2 : run 37109707900, success — `ok  lot-20  the event page carries its four flashcard levels, the iterable generic event, the stopped listeners and the argument interfaces`.

## Portes, au moment du rapport

Exécutées le 2026-10-03 sur la branche du rapport, au-dessus de `b422283` :

| Contrôle | Résultat |
|---|---|
| `php bin/cert validate` | 0 bloquant (1 avertissement `PED-003` préexistant) |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `php bin/cert build` | exit 0 |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| `composer gate-full` | exit 0 — 299 tests, 17 561 assertions ; TOTAL VIOLATIONS: 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |
| décomptes de cartes des deux entrées de page contre les fichiers | 2 / 2 concordants |

## Résumé autonome

Lot 20 (*Miscellaneous* : *EventDispatcher*, *Event*), 2 items STANDARD :
couverture projet 163/163 inchangée ; cours 1 048 → 1 224 mots (+176), aucun
dépassement de budget ; flashcards 2 → 22 (+20), toutes niveau posé ; 7
questions, aucune modifiée, 0 holdout modifiée ; deux pages déployées, smoke
tests lus ; aucune affirmation fausse, des précisions exécutées.
