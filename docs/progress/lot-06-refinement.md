# Raffinement pédagogique — Lot 06 (Templating with Twig)

Suite de la mission ouverte au lot 02 : approfondir les **pages de cours**
existantes — pièges d'examen, comportements implicites, flashcards aux quatre
niveaux — un lot à la fois, dans l'ordre numérique. Le lot 01 reste hors
périmètre sur instruction explicite (voir le journal du lot 02).

Même méthode qu'aux lots 03 à 05 : chaque affirmation vérifiée contre le code
de la branche 8.0 de Symfony, le tag `v3.22.0` de Twig — la version que nomme
le syllabus — ou la documentation correspondante, jamais de mémoire ; budget
`REV-001` respecté sans promotion de niveau ; une branche, une PR, une CI
verte, une fusion et un smoke test de production **lu** par page.

## État par page (ordre officiel de l'item)

Chiffres relevés le 2026-09-24 par script sur les fichiers canoniques
(`syllabus-matrix.yml`, `content/**`) de `master` à `b1b6284`, avant la première
page.

| # | Page | Niveau | Mots / plafond | Flashcards | Statut |
|---|---|---|---|---|---|
| 1 | TwigBundle | STANDARD | 446 / 900 | 1 | en cours |
| 2 | Twig syntax up to 3.22 version | DEEP | 755 / 1200 | 2 | à faire |
| 3 | Auto escaping | STANDARD | 440 / 900 | 1 | à faire |
| 4 | Template inheritance | STANDARD | 402 / 900 | 1 | à faire |
| 5 | Global variables | STANDARD | 392 / 900 | 1 | à faire |
| 6 | Filters and functions | STANDARD | 449 / 900 | 1 | à faire |
| 7 | Template includes | STANDARD | 435 / 900 | 1 | à faire |
| 8 | Loops and conditions | STANDARD | 531 / 900 | 2 | à faire |
| 9 | URLs generation | MINIMAL | 341 / 700 | 1 | à faire |
| 10 | Controller rendering | STANDARD | 316 / 900 | 1 | à faire |
| 11 | Translations and pluralization | STANDARD | 450 / 900 | 1 | à faire |
| 12 | String interpolation | MINIMAL | 257 / 700 | 1 | à faire |
| 13 | Assets management | MINIMAL | 355 / 700 | 1 | à faire |
| 14 | Debugging variables | MINIMAL | 346 / 700 | 1 | à faire |

## Page 1 — TwigBundle, 2026-09-24

`CRS-2bj91rj9kvj0` · `OIT-b9x8az2bx4t8` · STANDARD · **446 → 652 mots** sur 900.
Aucun niveau promu.

### Une omission qui change le diagnostic : la valeur par défaut de `strict_variables`

La page expliquait l'effet de `strict_variables` sans sa valeur par défaut.
`Configuration` de TwigBundle (8.0) : `->defaultValue('%kernel.debug%')`. Elle
est donc **vraie en dev et fausse en prod** : une faute de frappe lève une
`RuntimeError` en développement et s'affiche vide en production. Twig seul, lui,
la désactive par défaut (`Environment`, tag `v3.22.0`).

L'explication de la question `LEARNING` `QST-k1qgrrevaw40` le dit désormais. Le
distracteur `twig.debug` s'expliquait par un effet « sur les pages d'erreur »
qu'aucune source lue ne montre ; il renvoie maintenant à ce que le code établit :
`debug` est la valeur par défaut d'`auto_reload`. Bonne réponse inchangée ;
`reviewed_at` passe au 2026-09-24. Aucune question holdout n'a été lue.

### Une imprécision : « en leur donnant un espace de noms »

`twig.paths` accepte un chemin **sans** espace de noms : la normalisation de
`Configuration` range une entrée à clé numérique dans l'espace principal.

### Compléments, lus dans le code 8.0

- **Surcharge d'un bundle.** `TwigExtension::getBundleTemplatePaths()` enregistre
  `templates/bundles/AcmeBlogBundle/` — nom complet, suffixe compris — avant le
  répertoire du bundle ; le dernier est aussi enregistré sous `!AcmeBlog`, d'où
  `{% extends '@!AcmeBlog/…' %}` pour étendre l'original.
- **`globals`.** Une valeur `@id` désigne un service ; `@@` échappe.
- **Paquets.** `symfony/twig-bundle` exige `symfony/twig-bridge`, qui exige
  `twig/twig` `^3.21|^4.0`.
- **Commandes.** `debug:twig <gabarit>` donne le fichier qui le fournit ;
  `lint:twig --show-deprecations`.

**Flashcards.** 11 ajoutées ; la carte préexistante `FLC-aj7ff8fdwg48` reçoit le
niveau RECALL. L'item en porte **12** (4 RECALL, 4 UNDERSTANDING, 2 APPLICATION,
2 TRAP).

**Aiguilles de smoke test.** Les quatre titres de niveau, plus `TwigExtension`,
`@!AcmeBlog` et `templates/bundles/AcmeBlogBundle`, absentes de la version
`master` de la page.

**Contrôles réellement exécutés le 2026-09-24**

| Contrôle | Résultat |
|---|---|
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` | régénérés ; `readiness` inchangé |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 16 639 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 — 76 jours, 444 créneaux |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

**Déploiement de la PR #214 (lot 05, doublon de questions), lu dans le journal
d'exécution.** Fusionnée en squash (`b1b6284`). Run Pages 36012122570 : build,
déploiement et smoke test en succès ; la ligne `ok  practice  516 questions, all
LEARNING, no holdout id or choice` est écrite à **14:23:42 UTC** le 2026-09-24.
Le smoke test ne vérifie pas le texte de la question réécrite, et l'accès direct
au site est refusé par le proxy de cet environnement : ce contenu servi n'est
**pas** vérifié en production, seulement construit depuis les données
canoniques par un build vert.

## Prochaine étape

Page 2 — *Twig syntax up to 3.22 version* (DEEP, 755 / 1200).
