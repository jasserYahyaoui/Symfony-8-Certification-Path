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
| 1 | TwigBundle | STANDARD | 446 / 900 | 1 | **RAFFINÉE** (PR #215) |
| 2 | Twig syntax up to 3.22 version | DEEP | 755 / 1200 | 2 | **RAFFINÉE** (PR #217) |
| 3 | Auto escaping | STANDARD | 440 / 900 | 1 | en cours |
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

**Déploiement de la page 1, lu dans le journal d'exécution.** PR #215 fusionnée
en squash (`88eafb0`). Run Pages 36014093440 : build, déploiement et smoke test
en succès ; la ligne `ok  lot-06  the TwigBundle page carries its four flashcard
levels, the extension, the bang namespace and the override directory` est
écrite à **14:39:28 UTC** le 2026-09-24.

## Page 2 — Twig syntax up to 3.22 version, 2026-09-24

`CRS-x2f8reencvcs` · `OIT-vhd83fn6w9wy` · DEEP · **755 → 980 mots** sur 1200.
Aucun niveau promu.

### Deux affirmations périmées en Twig 3.22

- « `attribute(foo, 'bar')` lorsque le nom est dynamique » : la fonction est
  **dépréciée depuis Twig 3.15** (`doc/functions/attribute.rst`, tag
  `v3.22.0`) ; la même version a ajouté `user.(name)` et `user.('first-name')`.
- « `{% apply spaceless %}` retire le blanc entre balises » : le filtre est
  **déprécié depuis Twig 3.12** (`doc/filters/spaceless.rst`) ; la documentation
  renvoie aux modificateurs de blanc.

### Une étape manquante dans l'ordre de résolution

`CoreExtension::getAttribute()` (v3.22.0) se rabat sur `__call()` après les
*getters*, *issers* et *hassers*, avant l'échec final. La recherche de méthode
ignore la casse, et un *hasser* est écarté quand un *isser* du même nom existe.
La page, la carte `FLC-2e8x0yx9hbbp` et la question `LEARNING`
`QST-2xamk5ahfyyn` sont alignées : dans cette question, deux explications de
distracteurs plaçaient `bar()` troisième et `getBar()` quatrième alors que la
constante de classe, énoncée par la même question, les décale d'un rang. Bonne
réponse inchangée ; `reviewed_at` passe au 2026-09-24.

### Compléments, lus dans la documentation 3.22

Opérateurs absents de la page : `xor`, `b-and`/`b-or`/`b-xor`, `starts with`,
`ends with`, `matches`, `in`/`not in`, `..`, `has some`/`has every` (valeurs
sur un itérable vide), `=>` et `...` (3.15). Modificateurs de blanc : `-` et
`~`, et le retrait automatique du premier saut de ligne. L'affirmation sur
`{{ 'a' + 'b' }}` est désormais sourcée : `+` est compilé tel quel
(`AddBinary`), et le test php-src `add_006.phpt` (PHP-8.4) établit la
`TypeError` « Unsupported operand types ».

### Un signal holdout, sans lecture

Une recherche plein texte lancée sur tout `content/` a touché une ligne d'une
question `HOLDOUT`. Elle n'a été ni ouverte ni modifiée, et n'est désignée ici
par aucun identifiant, item ni contenu. Signal pour le propriétaire : **au moins
une question holdout du lot 06 mérite sa revue.** Leçon appliquée dès
maintenant : les recherches plein texte excluent les questions holdout.

**Flashcards.** 10 ajoutées ; les deux cartes préexistantes reçoivent un
niveau — `FLC-2e8x0yx9hbbp` RECALL, `FLC-zv7yngs0n7zm` TRAP — et la première
voit sa réponse corrigée. L'item en porte **12** (4 RECALL, 4 UNDERSTANDING,
2 APPLICATION, 2 TRAP).

**Aiguilles de smoke test.** Les quatre titres de niveau, plus `__call`,
`add_006.phpt` et `has every`, absentes de la version `master` de la page.

**Contrôles réellement exécutés le 2026-09-24**

| Contrôle | Résultat |
|---|---|
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` | régénérés ; `readiness` inchangé |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 16 649 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 — 76 jours, 444 créneaux |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

**Déploiement de la page 2, lu dans le journal d'exécution.** PR #217 fusionnée
en squash (`71bb7a7`). Run Pages 36016337414 : build, déploiement et smoke test
en succès ; la ligne `ok  lot-06  the Twig syntax page carries its four flashcard
levels, the call fallback, the php-src test and the iterable operator` est
écrite à **14:57:10 UTC** le 2026-09-24.

## Page 3 — Auto escaping, 2026-09-24

`CRS-w82wtddwhexg` · `OIT-ns36thqnh2jk` · STANDARD · **440 → 660 mots** sur 900.
Aucun niveau promu.

### Une affirmation fausse : « actif, avec la stratégie `html` »

La page — et l'explication de la question `LEARNING` `QST-hqqnvh8n266m` —
donnaient `html` comme stratégie par défaut dans une application Symfony.
`TwigExtension` (TwigBundle 8.0) règle l'option `autoescape` de Twig sur
`'name'` ; `EscaperExtension` (v3.22.0) la traduit en
`FileExtensionEscapingStrategy::guess()`, qui lit l'extension du gabarit, `.twig`
retiré : `js` et `json` → `js`, `css` → `css`, `txt` → **aucun échappement**,
tout le reste → `html`. Un courriel `email.txt.twig` n'est donc pas échappé.

**Corrections.** La page énonce la règle par extension ; l'explication de
`QST-hqqnvh8n266m` aussi. Bonne réponse inchangée ; `reviewed_at` passe au
2026-09-24. Aucune question holdout n'a été lue.

### Compléments, lus dans la documentation 3.22

- Twig n'échappe pas les **expressions statiques** ; macros et `parent()`
  retournent un contenu sûr (`doc/tags/autoescape.rst`).
- `raw` n'agit que s'il est le **dernier** filtre (`doc/filters/raw.rst`).
- Le double échappement n'est évité que si la stratégie du filtre est écrite en
  dur ; `html_attr` sur une valeur sans guillemets est moins performant que
  `html` entre guillemets ; `EscaperRuntime::setEscaper()` (3.10) enregistre une
  stratégie (`doc/filters/escape.rst`).
- `{% autoescape %}` sans argument applique `html`.

**Flashcards.** 11 ajoutées ; la carte préexistante `FLC-a64hqxfhfzpc` reçoit le
niveau RECALL. L'item en porte **12** (4 RECALL, 4 UNDERSTANDING, 2 APPLICATION,
2 TRAP).

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`FileExtensionEscapingStrategy`, `txt.twig` et `setEscaper`, absentes de la
version `master` de la page.

**Contrôles réellement exécutés le 2026-09-24**

| Contrôle | Résultat |
|---|---|
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` | **exit 1 avec 140/200** (« Pas de place : 4 mocks… 2 jours de week-end ») ; exit 0 avec 160/220, régénérés ; `readiness` inchangé |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 16 618 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 — 76 jours, 437 créneaux |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

**Le planning ne tenait plus, et la décision revient au propriétaire.** Avec
les paramètres fixés — `--max-new 4 --weekday 140 --weekend 200` —,
`build_roadmap.py` refusait de produire un plan : les cartes du raffinement
repoussaient la fin des lots au 2026-11-30, laissant deux jours de week-end pour
quatre mocks. Mesures présentées avant toute modification : 150/200 tenait sans
marge, 160/220 ramène la fin des lots au 2026-11-26, au-delà rien ne change
(`--max-new 4` décide alors). Le propriétaire a retenu la hausse des budgets ;
**160/220** est appliqué à l'étape de CI, à `study-roadmap.md` — qui trace la
décision — et à `exam-readiness.md`. Ces budgets dépassent les disponibilités
déclarées dans `DAY_START` : c'est écrit.

**Une baisse d'assertions, expliquée.** Le premier `gate-full` de la page
comptait 16 660 assertions ; celui-ci, 16 618. `RevisionPlanTest` vérifie chaque
événement du plan, et le plan régénéré en compte 437 au lieu de 444 : sept
événements de moins, six assertions chacun. Aucun test ni aucun code source n'a
changé (`git diff` vide sur `tests/` et `src/`).

## Prochaine étape

Page 4 — *Template inheritance* (STANDARD, 402 / 900).
