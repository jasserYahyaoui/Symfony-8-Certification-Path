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
| 1 | Clock | STANDARD | 686 / 900 | 1 | à faire |

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

## Prochaine étape

Rapport de fin de lot 19, réconcilié par script, dans sa propre PR.
