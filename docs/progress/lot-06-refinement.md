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
| 3 | Auto escaping | STANDARD | 440 / 900 | 1 | **RAFFINÉE** (PR #218) |
| 4 | Template inheritance | STANDARD | 402 / 900 | 1 | **RAFFINÉE** (PR #219) |
| 5 | Global variables | STANDARD | 392 / 900 | 1 | **RAFFINÉE** (PR #220) |
| 6 | Filters and functions | STANDARD | 449 / 900 | 1 | **RAFFINÉE** (PR #221) |
| 7 | Template includes | STANDARD | 435 / 900 | 1 | **RAFFINÉE** (PR #222) |
| 8 | Loops and conditions | STANDARD | 531 / 900 | 2 | en cours |
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

**Déploiement de la page 3, lu dans le journal d'exécution.** PR #218 fusionnée
en squash (`371426e`). Run Pages 36046977873 : build, déploiement et smoke test
en succès ; la ligne `ok  lot-06  the auto escaping page carries its four
flashcard levels, the strategy class, the text extension and the custom escaper`
est écrite à **19:18:32 UTC** le 2026-09-24.

## Page 4 — Template inheritance, 2026-09-24

`CRS-k3v3wrt0hmtx` · `OIT-g3p8wdtww344` · STANDARD · **402 → 720 mots** sur 900.
Aucun niveau promu.

### Une affirmation fausse, jusque dans la bonne réponse d'une question

La page, la carte `FLC-mype643dt889` et la question `LEARNING`
`QST-f9dbbk5scdxt` affirmaient qu'un gabarit enfant **ignore silencieusement**
le contenu écrit hors d'un bloc. Twig 3.22 le **refuse à la compilation** :
`Parser::filterBodyNodes()` lève une `SyntaxError` — « A template that extends
another one cannot include content outside Twig blocks » — pour tout texte non
blanc ou toute sortie hors bloc, et la fixture de test de Twig
`child_contents_outside_blocks.test` en fait la preuve. Seuls les blancs et les
balises qui n'affichent rien, comme `set`, sont tolérés.

**Corrections.** La page et la carte énoncent l'erreur. Dans `QST-f9dbbk5scdxt`,
la **bonne réponse change** : « A Twig syntax error », que la question donnait
pour fausse, devient correcte ; « Nothing — a child renders only its blocks »
devient un distracteur. L'énoncé demande désormais ce qui se passe au rendu, et
non ce qui est rendu ; `version` 1 → 2 ; sources du code ajoutées ;
`reviewed_at` au 2026-09-24. La question `QST-81q6m585fqxs` disait qu'`extends`
**doit** être la première balise : la documentation écrit *should*, et le code
ne l'impose pas ; l'explication le dit. Aucune question holdout n'a été lue.

### Compléments, lus dans le code et la documentation 3.22

- `Multiple extends tags are forbidden.` ; `extends` refusée dans un bloc ou une
  macro (`ExtendsTokenParser`).
- Un nom de bloc une seule fois par gabarit ; `block()` réaffiche,
  `block('nom', 'autre.html.twig')` lit ailleurs, `is defined` teste.
- Héritage dynamique : variable, liste (le premier existant), ternaire.
- Un bloc dans un `if` reste défini ; dans un enfant, il lève « A block
  definition cannot be nested under non-capturing nodes ».
- `use` : blocs importés non affichés, priorité au gabarit courant, `with … as`,
  cible jamais dynamique.

**Flashcards.** 11 ajoutées ; la carte préexistante `FLC-mype643dt889`,
corrigée, reçoit le niveau RECALL. L'item en porte **12** (4 RECALL,
4 UNDERSTANDING, 2 APPLICATION, 2 TRAP). Une carte-piège remplacée avant commit :
elle répétait la carte corrigée.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`filterBodyNodes`, `child_contents_outside_blocks` et `Multiple extends`,
absentes de la version `master` de la page.

**Contrôles réellement exécutés le 2026-09-24**

| Contrôle | Résultat |
|---|---|
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun (dont `aud10`, après le changement de bonne réponse) |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 16 629 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 — 76 jours, 437 créneaux |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

**Un défaut introduit puis attrapé.** Le premier essai d'insertion des sources
de `QST-f9dbbk5scdxt` s'est placé avant le `verified_at` de la source d'origine :
`validate` a refusé le YAML (« Duplicate key "verified_at" »). Ordre corrigé
avant commit ; le libellé de cette source, qui décrivait l'affirmation fausse,
pointe désormais la section *Child Template*.

**Déploiement de la page 4, lu dans le journal d'exécution.** PR #219 fusionnée
en squash (`2a865f0`). Run Pages 36049149012 : build, déploiement et smoke test
en succès ; la ligne `ok  lot-06  the template inheritance page carries its four
flashcard levels, the parser method, the Twig fixture and the single-inheritance
error` est écrite à **19:37:30 UTC** le 2026-09-24.

## Page 5 — Global variables, 2026-09-24

`CRS-r56yzzb4pye2` · `OIT-dp7w7s85wxjg` · STANDARD · **392 → 664 mots** sur 900.
Aucun niveau promu.

### Une affirmation fausse, inversée dans une carte

La page — « Y accéder sans test produit une erreur sur une page publique » —,
la carte `FLC-6952dw009qzf` et l'explication de la question `LEARNING`
`QST-3q73gtbejzs9` présentaient l'erreur comme certaine. `CoreExtension::getAttribute()`
(v3.22.0) ne lève « Impossible to access an attribute on a null variable »
**qu'en mode strict** ; sinon il rend `null`. Et `strict_variables` vaut
`%kernel.debug%` (page 1) : l'erreur apparaît en `dev`, pas en `prod`. La
justification de la carte disait l'inverse — « casse une page publique en
production seulement ».

**Corrections.** Page, carte (réponse et justification) et explication de la
question énoncent le mode strict ; bonne réponse inchangée ; `reviewed_at` au
2026-09-24. Aucune question holdout n'a été lue.

### Compléments, lus dans le code et la documentation

- **Globales de Twig** : `_self`, `_context`, `_charset` (`doc/templates.rst`,
  v3.22.0).
- **`AppVariable`** (bridge Twig 8.0) : `current_route` et
  `current_route_parameters` lisent `_route` et `_route_params` ;
  `app.flashes` accepte une liste de types ; un service absent lève une
  `RuntimeException` — `app.user` sans Security, car `twig.php` n'appelle
  `setTokenStorage()` que si le service existe —, sauf `app.flashes`, qui rend
  `[]`.
- **Déclarer** : `twig.globals` (avec `@id` pour un service),
  `GlobalsInterface::getGlobals()`, `Environment::addGlobal()` ; les globales
  sont visibles dans les macros, qui ne voient pas les variables du gabarit
  (`doc/tags/macro.rst`).

**Flashcards.** 11 ajoutées ; la carte préexistante `FLC-6952dw009qzf`,
corrigée, reçoit le niveau RECALL. L'item en porte **12** (4 RECALL,
4 UNDERSTANDING, 2 APPLICATION, 2 TRAP).

**Aiguilles de smoke test.** Les quatre titres de niveau, plus `_charset`,
`AppVariable` et `GlobalsInterface`, absentes de la version `master` de la page.

**Contrôles réellement exécutés le 2026-09-24**

| Contrôle | Résultat |
|---|---|
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 16 640 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 — 76 jours, 437 créneaux |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

**Déploiement de la page 5, lu dans le journal d'exécution.** PR #220 fusionnée
en squash (`8b03120`). Run Pages 36050940109 : build, déploiement et smoke test
en succès ; la ligne `ok  lot-06  the global variables page carries its four
flashcard levels, the Twig global, the app variable class and the globals
interface` est écrite à **19:53:29 UTC** le 2026-09-24.

## Page 6 — Filters and functions, 2026-09-24

`CRS-306ppg4454wj` · `OIT-sqekx9pkbe5v` · STANDARD · **449 → 673 mots** sur 900.
Aucun niveau promu.

### Deux affirmations fausses dans le même exemple

- `{{ text|trim|lower|truncate(50) }}` : **aucun filtre `truncate`** dans le cœur
  de Twig 3.22 — l'index des filtres n'en a pas, et `doc/filters/truncate.rst`
  répond 404. On tronque avec `slice`, ou avec `|u.truncate(50)`, que fournit
  `StringExtension` du paquet `twig/string-extra` (`doc/filters/u.rst`).
- « `|trim|lower` et `|lower|trim` diffèrent dès que la chaîne contient des
  espaces significatifs » : faux, ces deux filtres **commutent** — l'un ne touche
  que les espaces, l'autre que la casse. La page donne désormais un vrai
  contre-exemple : `|slice(0, 5)|trim` contre `|trim|slice(0, 5)` sur
  `'  abcdef'`.

L'explication de la question `VALIDATION` `QST-1tw52kh5s9sg` reprenait la
seconde affirmation ; elle est corrigée, bonne réponse inchangée, `reviewed_at`
au 2026-09-24. Aucune question holdout n'a été lue.

### Compléments, lus dans la documentation 3.22

Les tests par `is`, troisième famille ; les arguments nommés
(`number_format(decimal: 2)`) ; les filtres à fonction fléchée (`filter`, `map`,
`reduce`, `sort`, `find`) ; les paquets `twig/*-extra` et `twig/extra-bundle` ;
les attributs `#[AsTwigFilter]`, `#[AsTwigFunction]`, `#[AsTwigTest]` (Twig
3.21, `Twig\Attribute`), autoconfigurés par TwigBundle 8.0.

### `CONTEXT.md` remis à jour

Le fichier de continuité (§23) datait du 2026-09-15. Il décrit désormais la
campagne de raffinement, les décisions du propriétaire en vigueur — correction
systématique, budgets 160/220, règles holdout — et la prochaine action ;
l'état antérieur est conservé en historique.

**Flashcards.** 11 ajoutées ; la carte préexistante `FLC-qhsttsmdnhx6` reçoit le
niveau UNDERSTANDING. L'item en porte **12** (4 RECALL, 4 UNDERSTANDING,
2 APPLICATION, 2 TRAP).

**Aiguilles de smoke test.** Les quatre titres de niveau, plus `string-extra`,
`AsTwigTest` et `commutent`, absentes de la version `master` de la page.

**Contrôles réellement exécutés le 2026-09-24**

| Contrôle | Résultat |
|---|---|
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 16 651 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 — 76 jours, 437 créneaux |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

**Déploiement de la page 6, lu dans le journal d'exécution.** PR #221 fusionnée
en squash (`088f43b`). Run Pages 36052708797 : build, déploiement et smoke test
en succès ; la ligne `ok  lot-06  the filters and functions page carries its
four flashcard levels, the extra package, the test attribute and the commute
statement` est écrite à **20:09:45 UTC** le 2026-09-24.

## Page 7 — Template includes, 2026-09-24

`CRS-w83y6pfa5edn` · `OIT-ds2p5d4eg0pq` · STANDARD · **435 → 642 mots** sur 900.
Aucun niveau promu.

### Rien de faux, deux affirmations incomplètes

- « La fonction est recommandée, parce qu'elle s'utilise dans une expression » :
  c'est une des trois raisons que donne la note de `doc/tags/include.rst` ; les
  deux autres — une balise ne devrait rien afficher, et les arguments nommés
  n'imposent aucun ordre — sont ajoutées.
- Le préfixe `_` des fragments était présenté sans nuance ; `templates.rst`
  (8.0) le dit **facultatif** — une convention.

### Compléments, lus dans le code et la documentation 3.22

- Arguments de la fonction : `with_context`, `ignore_missing` — qui rend `''`
  (`CoreExtension::include()`) — et `sandboxed`, qui n'agit que si
  `SandboxExtension` est enregistrée (même méthode).
- Ordre des mots de la balise : `IncludeTokenParser::parseArguments()` lit
  `ignore missing`, puis `with`, puis `only` ; `with {…} ignore missing` est
  une erreur de syntaxe.
- Nom dynamique, ternaire, liste dont le premier gabarit existant est inclus ;
  liste sans aucun gabarit existant : exception, ou rien avec `ignore missing`.
- `embed` prend exactement les arguments de `include` ; un gabarit embarqué n'a
  pas de nom, d'où l'avertissement sur la stratégie d'échappement et la balise
  `autoescape`.

**Questions.** Les quatre questions non holdout de l'item ont été relues contre
les mêmes sources : exactes, inchangées. Aucune question holdout n'a été lue.

### Un test de build a arrêté deux cartes

`BuildTest::testFlashcardMarkupEscapesBracesInsideTheJsxContext` a échoué : deux
rectos portaient une accolade **dans un code span**, que le générateur laisse
intacte et que MDX lit comme une expression dans `<summary>`. Le contenu a été
corrigé — liste citée sans `{% %}` pour l'un, recto sans code span (échappé par
le générateur, comme les rectos existants du lot) pour l'autre. Le test n'a pas
été touché.

**Flashcards.** 12 ajoutées ; la carte préexistante `FLC-hhtpt2r6ppfc` reçoit le
niveau TRAP. L'item en porte **13** (5 RECALL, 2 UNDERSTANDING, 2 APPLICATION,
4 TRAP).

**Aiguilles de smoke test.** Les quatre titres de niveau, plus `ignore_missing`,
`SandboxExtension` et `facultatif`, absentes de la version `master` de la page
et présentes dans le build local.

**Contrôles réellement exécutés le 2026-09-24**

| Contrôle | Résultat |
|---|---|
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun (après correction des deux rectos) |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | 1er passage exit 1 (test JSX ci-dessus) ; après correction exit 0 — 299 tests, 16 663 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 — 76 jours, 437 créneaux |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

**Déploiement de la page 7, lu dans le journal d'exécution.** PR #222 fusionnée
en squash (`7cb7f07`). Run Pages 36055918553 : build, déploiement et smoke test
en succès ; la ligne `ok  lot-06  the template includes page carries its four
flashcard levels, the function argument, the sandbox condition and the optional
prefix` est écrite à **20:38:29 UTC** le 2026-09-24.

## Page 8 — Loops and conditions, 2026-09-24

`CRS-zbnzvqkhjknh` · `OIT-84j0qwkbcgq6` · STANDARD · **531 → 864 mots** sur 900.
Aucun niveau promu.

### Une affirmation fausse : « `is empty` est vrai pour `0` »

`CoreExtension::testEmpty()` (v3.22.0) rend vrai pour `''`, `[]`, `null`,
`false`, un `Countable` de taille zéro, un `Traversable` sans élément et un
objet dont la chaîne est vide — **pas pour `0`**, ni pour `'0'`. La page
l'affirmait ; elle oppose désormais les trois questions distinctes : vrai dans
un `if` (règles de PHP, tableau de `doc/tags/if.rst`), `empty`, `defined`. Cas
croisé ajouté : une collection `Countable` vide est vraie dans un `if` nu, mais
`empty`.

### Une question à deux réponses défendables

`QST-p2safjk7jebk` (LEARNING) — « que introduit `is` qu'un opérateur de
comparaison n'introduit pas ? » — avait pour distracteur « une comparaison
d'identité stricte, comme `===` », expliqué par « c'est l'opérateur `same as`,
pas un test ». Faux : `same as` **est** un test (`CoreExtension::getTests()`),
et Twig 3.22 n'enregistre aucun opérateur `===`. Le distracteur décrivait donc
une chose que `is` introduit vraiment. Remplacé par un choix sans ambiguïté
(nouvel identifiant `CHO-svycm5xf2syy`), explication complétée, **version 2**,
`reviewed_at` au 2026-09-24. Les quatre autres questions non holdout de l'item
ont été relues : exactes, inchangées. Aucune question holdout n'a été lue.

L'inventaire `docs/audit/lot-27-practice-mode/*.csv` est l'instantané daté de
l'audit du lot 27 ; il n'est pas régénéré ici.

### Compléments, lus dans le code et la documentation 3.22

- Portée : une variable créée dans la boucle n'en sort pas ; déclarée avant,
  elle garde sa dernière valeur (`ForNode`, `array_intersect_key`).
- `ForTokenParser` n'accepte aucune condition sur `for` ; un `if` intérieur
  laisse `loop.index` compter les éléments sautés.
- `..` (borne incluse) et `range()` pour un pas.
- Les tests réellement enregistrés : treize dans `CoreExtension::getTests()`,
  dont `none`, `sequence`, `mapping` et `true`, absents de l'index de la
  documentation ; `IfNode` pose `TrueTest` sur chaque condition, et un `Markup`
  y est jugé sur son texte.

**Flashcards.** 11 ajoutées ; les cartes préexistantes `FLC-y4mtc33awnjy`
(RECALL) et `FLC-n6w80mr1j8s1` (TRAP) reçoivent leur niveau. L'item en porte
**13** (6 RECALL, 2 UNDERSTANDING, 2 APPLICATION, 3 TRAP).

**Aiguilles de smoke test.** Les quatre titres de niveau, plus `testEmpty`,
`ForTokenParser` et `TrueTest`, absentes de la version `master` de la page et
présentes dans le build local.

**Contrôles réellement exécutés le 2026-09-24**

| Contrôle | Résultat |
|---|---|
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 16 674 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 — 76 jours, 437 créneaux |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

## Prochaine étape

Page 9 — *URLs generation* (MINIMAL, 341 / 700).
