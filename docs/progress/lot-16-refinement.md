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
| 1 | Internationalization and localization | STANDARD | 749 / 900 | 1 | à faire |

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

## Prochaine étape

Rapport de fin de lot 16, réconcilié par script, dans sa propre PR.
