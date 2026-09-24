# Raffinement pédagogique — Lot 07 (Forms)

Suite de la mission ouverte au lot 02 : approfondir les **pages de cours**
existantes — pièges d'examen, comportements implicites, flashcards aux quatre
niveaux — un lot à la fois, dans l'ordre numérique. Le lot 01 reste hors
périmètre sur instruction explicite (voir le journal du lot 02).

Même méthode qu'aux lots 03 à 06 : chaque affirmation vérifiée contre le code
de la branche 8.0 de Symfony ou la documentation correspondante, jamais de
mémoire ; quand la documentation et le code divergent, le code l'emporte et
l'écart est signalé sur la page ; budget `REV-001` respecté sans promotion de
niveau ; une branche, une PR, une CI verte, une fusion et un smoke test de
production **lu** par page.

## État par page (ordre officiel de l'item)

Chiffres relevés le 2026-09-24 par script sur les fichiers canoniques
(`syllabus-matrix.yml`, `content/**`) de `master` à `9d6d9ae`, avant la première
page.

| # | Page | Niveau | Mots / plafond | Flashcards | Statut |
|---|---|---|---|---|---|
| 1 | Form component | STANDARD | 500 / 900 | 1 | **RAFFINÉE** (PR #231) |
| 2 | Forms creation | STANDARD | 422 / 900 | 1 | en cours |
| 3 | Forms handling | STANDARD | 442 / 900 | 1 | à faire |
| 4 | Form types (built-in and custom) | STANDARD | 427 / 900 | 1 | à faire |
| 5 | Forms rendering with Twig | STANDARD | 410 / 900 | 1 | à faire |
| 6 | Forms theming | STANDARD | 465 / 900 | 2 | à faire |
| 7 | CSRF protection | STANDARD | 455 / 900 | 1 | à faire |
| 8 | Handling file upload | MINIMAL | 290 / 700 | 1 | à faire |
| 9 | Built-in form types | MINIMAL | 383 / 700 | 1 | à faire |
| 10 | Data transformers | STANDARD | 452 / 900 | 1 | à faire |
| 11 | Form events | DEEP | 584 / 1200 | 3 | à faire |
| 12 | Form type extensions | MINIMAL | 328 / 700 | 1 | à faire |
| 13 | Form options (OptionsResolver component) | STANDARD | 424 / 900 | 1 | à faire |

## Page 1 — Form component, 2026-09-24

`CRS-txp6tjhjtfy8` · `OIT-tsxxgp3ppj1n` · STANDARD · **500 → 704 mots** sur 900.
Aucun niveau promu.

### Un exemple de la documentation que le code contredit

La page, reprenant `forms.rst` (8.0), donnait pour donnée **normalisée** d'un
`DateType` rendu en trois listes un tableau d'entiers
`['year' => 2026, 'month' => 10, 'day' => 18]`. Le code dit autre chose :
`DateType::buildForm()` n'ajoute un transformateur de **modèle** que pour les
inputs autres que `datetime`, et son transformateur de vue,
`DateTimeToArrayTransformer`, part d'un `DateTime`.

**Vérifié par exécution**, avec `symfony/form` **v8.0.15** installé dans le
bac à sable (`DateType.php` identique à la branche 8.0 sur les lignes en
cause) :

| `input` | model | norm | view |
|---|---|---|---|
| `datetime` | `DateTime` | `DateTime` | `{"year":"2026","month":"10","day":"18"}` |
| `array` | `{"year":2026,"month":10,"day":18}` | `DateTime` | idem |
| `string` | `"2026-10-18"` | `DateTime` | idem |

Après `submit()`, même résultat : norm et model `DateTime`. Le tableau d'entiers
de la documentation est la donnée **modèle** quand `input` vaut `array`.

**Corrections.** La page expose l'écart et le tableau mesuré.
`QST-174t76nj5hh1` (LEARNING) avait pour **bonne réponse** le tableau d'entiers :
la bonne réponse devient « A DateTime object », l'énoncé précise « with the
default input option », explications et sources (code) ajoutées, **version 2**.
`QST-a4xhs81g86kj` (LEARNING) : l'explication d'un distracteur citait `DateType`
comme type dont la couche normalisée diffère ; corrigée (bonne réponse
inchangée). Les trois autres questions non holdout de l'item ont été relues :
exactes, inchangées. Aucune question holdout n'a été lue.

**Signal pour le propriétaire, sans lecture** : toute question holdout qui
reprendrait l'exemple `DateType` de la documentation serait fausse au regard du
code ; aucune n'a été ouverte pour le vérifier.

### Compléments

- `getData()`, `getNormData()`, `getViewData()` (« Accessing Form Data »).
- Transformateurs de modèle entre modèle et normalisée, de vue entre normalisée
  et vue ; `transform()` au rendu, `reverseTransform()` à la soumission.
- Niveau de donnée de `PRE_SUBMIT`, `SUBMIT`, `POST_SUBMIT` (`form/events.rst`).

**Flashcards.** 11 ajoutées ; la carte préexistante `FLC-jd951drcz26t` reçoit le
niveau RECALL. L'item en porte **12** (5 RECALL, 2 UNDERSTANDING, 2 APPLICATION,
3 TRAP).

**Aiguilles de smoke test.** Les quatre titres de niveau, plus `getNormData`,
`DateTimeToArrayTransformer` et `format pivot`, absentes de la version `master`
de la page et présentes dans le build local.

**Contrôles réellement exécutés le 2026-09-24**

| Contrôle | Résultat |
|---|---|
| exécution `symfony/form` v8.0.15 (`DateType`, trois inputs, puis `submit()`) | résultats cités ci-dessus |
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 16 768 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 — 76 jours, 441 créneaux |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

Le rapport de fin de lot 06 (PR #230, `9d6d9ae`) a été déployé par le run Pages
36070903670, conclu en succès, smoke test compris.

**Déploiement de la page 1, lu dans le journal d'exécution.** PR #231 fusionnée
en squash (`e67dfa8`). Run Pages 36072354592 : build, déploiement et smoke test
en succès ; la ligne `ok  lot-07  the form component page carries its four
flashcard levels, the norm accessor, the view transformer and the pivot
statement` est écrite à **23:25:07 UTC** le 2026-09-24.

## Page 2 — Forms creation, 2026-09-24

`CRS-8msvfwe13mpb` · `OIT-fhpttc4c5x0k` · STANDARD · **422 → 577 mots** sur 900.
Aucun niveau promu.

Environnement d'exécution : `symfony/form` 8.0.15, `property-access` 8.0.8,
`property-info` 8.0.15 — toute la pile ramenée en 8.0 par
`composer update --prefer-source` dans le bac à sable.

### Deux affirmations fausses

- **L'ordre d'accès.** « Une propriété publique `$dueDate`, puis
  `getDueDate()`, `isDueDate()`, `hasDueDate()` » : c'est l'inverse.
  `ReflectionExtractor::getReadInfo()` essaie `get`, `is`, `has`, **`can`**,
  puis une méthode **`dueDate()`**, puis `__get()`, et seulement ensuite la
  propriété publique. Exécuté : un objet à `public $dueDate` et `getDueDate()`
  est lu par le getter ; une méthode nue `dueDate()` est lue ; une propriété
  privée sans accesseur lève `NoSuchPropertyException`.
- **Le type deviné.** « Omis, Symfony le devine d'après le type de la
  propriété » : `FormFactory::createBuilderForProperty()` interroge les *type
  guessers* (validation, Doctrine, `EnumFormTypeGuesser`), `TextType` à défaut ;
  sans `data_class`, `FormBuilder::add()` prend `TextType` sans rien deviner.

### Une question VALIDATION à deux bonnes réponses

`QST-3pfgr2whbm74` donnait pour faux « A method named dueDate() » (« pas une
convention d'accesseur ») : exécution faite, cette méthode **est** lue. Sa bonne
réponse omettait aussi `canDueDate()`. Réécrite sur un cas sans ambiguïté —
propriété publique et getter qui divergent : le getter l'emporte —, quatre
choix neufs (`CHO-7h6abksz4s6q`, `CHO-0wzcb5aj4hhm`, `CHO-j0kv7qs1ctam`,
`CHO-atxfp95171ep`), même résultat d'apprentissage `OUT-75g8bz40n09y`,
**version 2**, source du code ajoutée.

`QST-6ygjf6k55wpk` : l'explication disait que `data_class` décide seule entre
objet et tableau ; elle est aussi devinée de l'objet passé. Explication
précisée, bonne réponse inchangée. La carte `FLC-18xdehm33xa9` reprenait
l'ordre inversé : réponse et explication corrigées, niveau TRAP. Les trois
autres questions non holdout ont été relues : exactes, inchangées. Aucune
question holdout n'a été lue.

### Compléments, exécutés

- `data_class` devinée de l'objet ; déclarée sans objet, un `Task` neuf est
  instancié (`empty_data`) ; ni l'un ni l'autre, `getData()` rend un tableau.
- Champ sans propriété et sans `mapped: false` : `NoSuchPropertyException` dès
  `getForm()`.
- Options devinées : `required`, `maxlength`, `pattern`.

**Flashcards.** 10 ajoutées ; `FLC-18xdehm33xa9` corrigée et nivelée TRAP.
L'item en porte **11** (3 RECALL, 2 UNDERSTANDING, 2 APPLICATION, 4 TRAP).

**Un défaut introduit puis attrapé.** La correction de `FLC-18xdehm33xa9` a
écrit deux valeurs YAML non quotées, dont une contenait `: ` ; le contrôle de
chargement a échoué avant tout commit. Valeurs requotées.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus `canDueDate`,
`EnumFormTypeGuesser` et `empty_data`, absentes de la version `master` de la
page et présentes dans le build local.

**Contrôles réellement exécutés le 2026-09-24**

| Contrôle | Résultat |
|---|---|
| exécutions pile Form 8.0 (accès, `data_class`, champ non mappé) | résultats cités ci-dessus |
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 16 778 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 — 76 jours, 441 créneaux |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

## Prochaine étape

Page 3 — *Forms handling* (STANDARD, 442 / 900).
