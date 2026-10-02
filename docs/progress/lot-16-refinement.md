# Raffinement pédagogique — Lot 16 (Miscellaneous)

Suite de la mission ouverte au lot 02 : approfondir les **pages de cours**
existantes — pièges d'examen, comportements implicites, flashcards aux quatre
niveaux — un lot à la fois, dans l'ordre numérique. Le lot 01 reste hors
périmètre sur instruction explicite (voir le journal du lot 02).

Même méthode qu'aux lots 03 à 15 : chaque affirmation vérifiée contre le code
de la branche 8.0 de Symfony ou la documentation correspondante, par exécution
chaque fois que c'est possible, jamais de mémoire ; budget `REV-001` respecté
sans promotion de niveau ; une branche, une PR, une CI verte, une fusion et un
smoke test de production **lu** par page. Le bac à sable d'exécution a reçu pour
ce lot `symfony/translation` 8.0.14 ; la vérification « aucun composant
`symfony/*` hors 8.0 » a été refaite après l'ajout.

## État par page (ordre officiel de l'item)

Chiffres relevés le 2026-10-02 par script sur les fichiers canoniques
(`syllabus-matrix.yml`, `content/**`) de `master` à `3ae7fce`, avant la
première page.

| # | Page | Niveau | Mots / plafond | Flashcards | Statut |
|---|---|---|---|---|---|
| 1 | Internationalization and localization | STANDARD | 749 / 900 | 1 | **RAFFINÉE** (PR #316) |

## Page 1 — *Internationalization and localization* — RAFFINÉE

`CRS-v1808qnchx6g` · `OIT-3dd7821h069b` · STANDARD · **749 → 813 mots** sur 900.
Aucun niveau promu. Exécutions sur Translation 8.0.14, TwigBridge 8.0.15 et
Twig 3.30 : un traducteur à quatre catalogues (`es_419`, `es`, `en`, un domaine
`+intl-icu`), puis quatre gabarits Twig.

### Déploiement précédent, lu en production

| Fusion | Run Pages | Ligne de smoke test |
|---|---|---|
| rapport de fin de lot 15 (PR #315, `3ae7fce`) | 37071619214, success | `ok  lot-15  the web profiler page carries its four flashcard levels, the body tag, the purge threshold and the terminate save` — le rapport ne touchant aucune page, c'est la dernière ligne de lot dans le journal du smoke test |

### Une affirmation incomplète : l'échappement

« Les messages traduits sont échappés par défaut » ne vaut que pour le filtre.
`translation.rst` (8.0) l'écrit : *automatic output escaping is **not** applied
to translations using a tag*. Exécuté avec `<i>x</i>` en paramètre : `&lt;i&gt;`
par `|trans`, `<i>x</i>` brut par `{% trans %}` — le paramètre n'est pas échappé
non plus.

### Une affirmation imprécise : « `{name}` reste littéral »

Sans `+intl-icu`, le remplacement est un `strtr` des clés passées. Exécuté sur
`Hello {name}!` : `['name' => 'Ann']` donne `Hello {Ann}!` ; `['{name}' =>
'Ann']` donne `Hello Ann!`. Corrigé : la page, la carte `FLC-7ar3ewagp1y0` et
l'explication de `QST-fad080pztznw` (VALIDATION ; énoncé, choix et version
inchangés).

### Confirmé par l'exécution

| Cas | Résultat |
|---|---|
| clé seulement dans `es_419`, locale `es_AR` | trouvée |
| clé dans `es_419` et `es` | `es_419` gagne |
| clé seulement dans `en`, `fallbacks: [en]` | trouvée |
| clé absente partout | la clé elle-même |
| pluriel ICU, 0 / 1 / 5 | les trois formes |
| `trans_default_domain 'admin'` + `include` | `admin` dans le parent, `messages` dans l'inclus |

Sans l'extension `intl`, `IntlFormatter` (8.0) lève une `LogicException` : le
distracteur de `QST-fad080pztznw` qui l'écarte reste exact.

**Erreur de méthode, sans effet.** Le script de correction cherchait la question
suivante pour délimiter `QST-fad080pztznw`, la dernière du fichier : il a échoué
avant toute écriture, l'arbre est resté propre, et les portes lancées sur l'arbre
inchangé ont été arrêtées. Corrigé, relancé.

**Questions.** `QST-qtng554v6wp6`, `QST-p0bkmxc5kxfs` (LEARNING) relues :
exactes, inchangées. `QST-fad080pztznw` : explication seule. L'item n'a pas de
question holdout.

**Flashcards.** 10 ajoutées ; `FLC-7ar3ewagp1y0` reçoit le niveau RECALL. L'item
en porte **11** (3 RECALL, 3 UNDERSTANDING, 2 APPLICATION, 3 TRAP), décompte
relevé par script sur tous les fichiers de cartes.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`strtr`, `échappe rien` et `paramètres compris`,
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
| `composer gate-full` | exit 0 — 299 tests, 17 511 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

# Rapport de fin de lot 16

Toutes les figures ci-dessous sont **réconciliées par script** depuis
`docs/syllabus/syllabus-matrix.yml`, `content/courses/**`,
`content/flashcards/**` et `content/questions/**` — jamais depuis un rapport
antérieur ni de mémoire. Base de comparaison : `3ae7fce`, le commit de `master`
qui précède la page refondue (PR #316). État mesuré : `69aa8b1`. Le décompte de
cartes écrit dans l'entrée de page concorde avec les fichiers : **1 / 1**.

## Périmètre et couverture

**1** item officiel atomique porte `lot: lot-16` : `STANDARD`, niveau inchangé —
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
| Cours, corps en mots (plafond 900) | 749 | **813** | **+64** |
| Cartes sur l'item | 1 | **11** | **+10** |

Niveaux des cartes — **observation, jamais une cible** : `RECALL` 3 ·
`UNDERSTANDING` 3 · `APPLICATION` 2 · `TRAP` 3 ; aucune sans niveau. La carte
préexistante `FLC-7ar3ewagp1y0` a été corrigée au-delà du niveau (« `{name}`
reste littéral »).

**3** questions (2 `LEARNING`, 1 `VALIDATION`, aucune `HOLDOUT`), aucune
ajoutée ni supprimée. **1** corrigée : `QST-fad080pztznw` (VALIDATION),
explication seule, version 1 inchangée. **0 question holdout modifiée**.
`POOL-002` : **0** item sans `VALIDATION`. **Matrice** : aucun texte modifié.

## Affirmations corrigées

| Affirmation | Exécuté | Décision |
|---|---|---|
| « les messages traduits sont échappés par défaut » | vrai pour `\|trans`, faux pour `{% trans %}` — paramètres compris | page précisée, conforme à `translation.rst` (8.0) |
| « sans `+intl-icu`, `{name}` reste littéral » | `strtr` des clés passées : `['name' => …]` donne `{Ann}` | page, carte et explication corrigées |

Aucune divergence documentation / code.

## Erreur de méthode

Le script de correction a d'abord échoué sur la dernière question du fichier,
avant toute écriture ; les portes lancées sur l'arbre inchangé ont été arrêtées,
le script corrigé, puis relancé.

## Déploiement

Page fusionnée par PR (#316), CI verte, déployée, smoke test lu : run 37072898808,
success — `ok  lot-16  the internationalization page carries its four flashcard levels, the literal strtr, the unescaped tag and its parameters`.

## Portes, au moment du rapport

Exécutées le 2026-10-02 sur la branche du rapport, au-dessus de `69aa8b1` :

| Contrôle | Résultat |
|---|---|
| `php bin/cert validate` | 0 bloquant (1 avertissement `PED-003` préexistant) |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `php bin/cert build` | exit 0 |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| `composer gate-full` | exit 0 — 299 tests, 17 511 assertions ; TOTAL VIOLATIONS: 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

## Résumé autonome

Lot 16 (*Internationalization and localization*), 1 item STANDARD : couverture
projet 163/163 inchangée ; cours 749 → 813 mots (+64) ; cartes 1 → 11, toutes
niveau posé ; 3 questions, 1 explication corrigée, 0 holdout modifiée ; page
déployée, smoke test lu ; deux affirmations précisées (échappement de la balise,
`strtr` du format classique).
