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
| 2 | Forms creation | STANDARD | 422 / 900 | 1 | **RAFFINÉE** (PR #232) |
| 3 | Forms handling | STANDARD | 442 / 900 | 1 | **RAFFINÉE** (PR #233) |
| 4 | Form types (built-in and custom) | STANDARD | 427 / 900 | 1 | **RAFFINÉE** (PR #234) |
| 5 | Forms rendering with Twig | STANDARD | 410 / 900 | 1 | **RAFFINÉE** (PR #235) |
| 6 | Forms theming | STANDARD | 465 / 900 | 2 | en cours |
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

**Déploiement de la page 2, lu dans le journal d'exécution.** PR #232 fusionnée
en squash (`3d6de09`). Run Pages 36073676089 : build, déploiement et smoke test
en succès ; la ligne `ok  lot-07  the forms creation page carries its four
flashcard levels, the can accessor, the enum guesser and the empty data option`
est écrite à **23:39:57 UTC** le 2026-09-24.

## Page 3 — Forms handling, 2026-09-24

`CRS-nfkzx2s1n3r3` · `OIT-mgvdw7cfpwyz` · STANDARD · **442 → 589 mots** sur 900.
Aucun niveau promu. Exécutions avec `symfony/form`, `validator` et
`http-foundation` 8.0.15.

### Une question dont un distracteur était vrai

`QST-zkgdt4kc1h0g` (LEARNING) expliquait le garde `isSubmitted() && isValid()`
par « rien à valider » et tenait pour faux « isValid() throws when the form was
not submitted » (« It does not throw »). Or `Form::isValid()` **lève une
`LogicException`** — « Cannot check if an unsubmitted form is valid » — sur un
formulaire non soumis ; reproduit par exécution. Deux choix neufs
(`CHO-978c27jygct4`, bonne réponse ; `CHO-dz36kv9t215z`), explication et source
du code, **version 2**. La page le dit désormais.

### Un exemple de la documentation qui échoue

`forms.rst` (8.0) illustre `submit()` avec
`$request->getPayload()->get($form->getName())`. Pour un formulaire composé, la
valeur est un tableau et `InputBag::get()` lève une `BadRequestException`,
« Input value "form" contains a non-scalar value » — reproduit avec
HttpFoundation 8.0.15. La page emploie `all()` et signale l'écart. La seule
autre occurrence de `getPayload()->get(` hors holdout (`QST-pg8rnbwrhn85`, lot
04) lit un champ scalaire : correcte.

### Compléments, exécutés

- `handleRequest()` ne fait rien si la méthode de la requête diffère de
  l'option `method` (formulaire `POST`, requête `GET` garnie : non soumis) ;
  il passe `clearMissing = ('PATCH' !== $method)`.
- Validation partielle : un `NotBlank` sur un champ vide absent passe avec
  `clearMissing = false`, échoue quand la clé est ajoutée avec `null`.
- `render()` : 422 seulement si la réponse est encore à 200 ; conversion des
  formulaires en vue. Formulaire soumis et `disabled` : toujours valide.

**Questions.** Les quatre autres questions non holdout ont été relues : exactes
— la validation partielle et le comportement de `clearMissing` sont confirmés
par exécution —, inchangées. Aucune question holdout n'a été lue.

**Flashcards.** 10 ajoutées ; la carte préexistante `FLC-jsbfc1cnv01y` reçoit le
niveau RECALL. L'item en porte **11** (4 RECALL, 2 UNDERSTANDING, 2 APPLICATION,
3 TRAP), décompte relevé par script.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus `LogicException`,
`BadRequestException` et `HttpFoundationRequestHandler`, absentes de la version
`master` de la page et présentes dans le build local.

**Contrôles réellement exécutés le 2026-09-24**

| Contrôle | Résultat |
|---|---|
| exécutions Form, Validator, HttpFoundation 8.0.15 | résultats cités ci-dessus |
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 16 788 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 — 76 jours, 441 créneaux |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

**Déploiement de la page 3, lu dans le journal d'exécution.** PR #233 fusionnée
en squash (`38385e5`). Run Pages 36074918821 : build, déploiement et smoke test
en succès ; la ligne `ok  lot-07  the forms handling page carries its four
flashcard levels, the logic exception, the payload exception and the request
handler` est écrite à **23:55:04 UTC** le 2026-09-24.

## Page 4 — Form types (built-in and custom), 2026-09-24

`CRS-f9pen0bdyrr9` · `OIT-5xvjqa4203xe` · STANDARD · **427 → 579 mots** sur 900.
Aucun niveau promu.

### Rien de faux, une liste incomplète

La page présentait « les quatre méthodes » d'un type. `AbstractType` (8.0) en
définit **six** — `buildView()` et `finishView()` manquaient —, toutes avec un
défaut ; l'affirmation « aucune n'est obligatoire » reste juste. L'explication
de `QST-yyzehfrg9w4n` (LEARNING), qui parlait aussi de « the four methods », est
précisée ; bonne réponse inchangée. Les quatre autres questions non holdout ont
été relues : exactes, inchangées. Aucune question holdout n'a été lue.

### Compléments, lus dans le code et exécutés

- `ResolvedFormType` : parent, puis type, puis extensions, pour `buildForm()`
  comme pour `buildView()` ; résolveur d'options **cloné** du parent.
- `getBlockPrefix()` : `StringUtil::fqcnToBlockPrefix()`, `DeliveryAddressType`
  → `delivery_address`.
- Exécuté avec `symfony/form` 8.0.15 : un champ `shipping` (`ShippingType`,
  parent `ChoiceType`) du formulaire `order` porte
  `["form", "choice", "shipping", "_order_shipping"]` ; `expanded` hérité puis
  surchargé à `true`.
- `FormRenderer` cherche du préfixe le plus spécifique au plus général.
- `FormType::getParent()` rend `null` ; `AbstractType::getParent()` rend
  `FormType::class`.

**Flashcards.** 10 ajoutées ; la carte préexistante `FLC-cafrwa53g55w` reçoit le
niveau TRAP. L'item en porte **11** (3 RECALL, 3 UNDERSTANDING, 2 APPLICATION,
3 TRAP), décompte relevé par script.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus `finishView`,
`delivery_address` et `ResolvedFormType`, absentes de la version `master` de la
page et présentes dans le build local.

**Contrôles réellement exécutés le 2026-09-24**

| Contrôle | Résultat |
|---|---|
| exécution `symfony/form` 8.0.15 (préfixes, héritage d'options) | résultats cités ci-dessus |
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 16 798 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 — 76 jours, 441 créneaux |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

**Déploiement de la page 4, lu dans le journal d'exécution.** PR #234 fusionnée
en squash (`9241921`). Run Pages 36076144072 : build, déploiement et smoke test
en succès ; la ligne `ok  lot-07  the form types page carries its four flashcard
levels, the finish view method, the block prefix and the resolved type` est
écrite à **00:10:40 UTC** le 2026-09-25.

## Page 5 — Forms rendering with Twig, 2026-09-25

`CRS-b5nj93xzz10b` · `OIT-gxew257vwhm8` · STANDARD · **410 → 516 mots** sur 900.
Aucun niveau promu. Rendu réel exécuté avec `symfony/twig-bridge` 8.0.15, Twig
3.22.2 et le thème `form_div_layout.html.twig`.

### Une question VALIDATION à deux bonnes réponses

`QST-m9pvk08gts1f` demandait quel appel règle l'action et la méthode, et tenait
`form(form, {'action': …})` pour faux. Exécuté :
`form(form, {'action': '/target', 'method': 'GET'})` produit
`<form name="task_form" method="get" action="/target">` — le bloc `form` appelle
`form_start(form)`, qui reçoit ces variables. L'énoncé précise désormais que les
champs sont rendus un par un entre `form_start()` et `form_end()` ; le
distracteur devenu vrai est remplacé par un appel `form_row()`
(`CHO-z3axn6924asr`), explication et source du thème, **version 2**.

### Une affirmation fausse

« Mélanger ligne et widget donne des libellés en double ou absents » : pour un
même champ, c'est une **exception**. `FormRenderer` retient les champs rendus, et
`form_row(form.task)` suivi de `form_widget(form.task)` lève une
`BadMethodCallException`, « Field "task" has already been rendered ». Exécuté.

### Compléments, exécutés

- Sortie réelle de `form_row()` (libellé, saisie, aide, `aria-describedby`) et
  de `form_widget()` (saisie seule, sans lien vers l'aide).
- `form_end()` appelle `form_rest()` sauf `render_rest: false` : un champ caché
  non rendu apparaît, puis disparaît avec l'option.

**Questions.** Les trois autres questions non holdout ont été relues : exactes,
inchangées. Aucune question holdout n'a été lue.

**Flashcards.** 10 ajoutées ; la carte préexistante `FLC-yvey5gkx1zhd` reçoit le
niveau RECALL. L'item en porte **11** (5 RECALL, 2 UNDERSTANDING, 2 APPLICATION,
2 TRAP), décompte relevé par script.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`BadMethodCallException`, `form_div_layout` et `FormRenderer`, absentes de la
version `master` de la page et présentes dans le build local.
`aria-describedby` a été écarté comme aiguille : l'interface du site peut le
produire elle-même.

**Contrôles réellement exécutés le 2026-09-25**

| Contrôle | Résultat |
|---|---|
| rendu twig-bridge 8.0.15 / Twig 3.22.2 (cinq gabarits) | résultats cités ci-dessus |
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 16 808 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 — 76 jours, 441 créneaux |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

**Déploiement de la page 5, lu dans le journal d'exécution.** PR #235 fusionnée
en squash (`6b6fc4a`). Run Pages 36077343562 : build, déploiement et smoke test
en succès ; la ligne `ok  lot-07  the forms rendering page carries its four
flashcard levels, the twice exception, the theme file and the renderer` est
écrite à **00:25:39 UTC** le 2026-09-25.

## Page 6 — Forms theming, 2026-09-25

`CRS-r3xywnpwrwh7` · `OIT-j2vjdxcer4ft` · STANDARD · **465 → 618 mots** sur 900.
Aucun niveau promu. Rendu réel avec twig-bridge 8.0.15 et Twig 3.22.2.

### Une affirmation fausse sur la chaîne de recherche

Pour un champ `EmailType`, la page donnait `_user_contact_widget` → `email_widget`
(« absent, on remonte ») → `text_widget` (« trouvé dans form_div_layout »). Or
`form_div_layout.html.twig` **définit `email_widget`** (ligne 218) et **aucun**
des douze thèmes de twig-bridge 8.0 ne définit `text_widget` (0 occurrence).
Exécuté : un `text_widget` personnalisé change un champ `TextType` et laisse un
champ `EmailType` en `<input type="email">`. La carte `FLC-8yczcp0s4a1c` et les
explications de `QST-6tbd9rk49r20` et `QST-9wnradgcgyvz` (LEARNING) reprenaient
« … puis text_partie » : corrigées, bonnes réponses inchangées.

### Trois exemples de la documentation qui échouent

`form_themes.rst` (8.0) s'appuie sur `text_widget`. Rendu réel :

- l'enveloppe `email_widget` → `{{ form_widget(form) }}` rend
  `<input type="text">` : le type `email` est perdu ;
- `{% use 'form_div_layout.html.twig' %}` + `text_widget` + `parent()` :
  `RuntimeError`, « no parent and no traits defining the "text_widget" block » ;
- `use … with text_widget as base_text_widget` : `RuntimeError`, « Block
  "text_widget" is not defined in trait ».

Parade vérifiée : `use` puis `parent()` dans `email_widget` garde
`type="email"`.

### Confirmé par exécution

- `twig.form_themes` parcouru de la fin vers le début : deux thèmes définissant
  le même bloc, le dernier gagne dans les deux ordres.
- `_self` sans `extends` : le bloc s'affiche en tête de page et le champ garde
  son rendu par défaut — l'avertissement de la documentation.

**Questions.** Les deux autres questions non holdout ont été relues : exactes,
inchangées. Aucune question holdout n'a été lue.

**Flashcards.** 10 ajoutées ; les deux cartes préexistantes reçoivent un niveau
— `FLC-8yczcp0s4a1c` RECALL (réponse corrigée), `FLC-5g65x0xtgdn7` TRAP.
L'item en porte **12** (3 RECALL, 2 UNDERSTANDING, 2 APPLICATION, 5 TRAP),
décompte relevé par script avant rédaction.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus `RuntimeError`,
`block_prefixes` et `exemples documentés qui échouent`, absentes de la version
`master` de la page et de ses cartes, présentes dans le build local.

**Contrôles réellement exécutés le 2026-09-25**

| Contrôle | Résultat |
|---|---|
| rendus twig-bridge 8.0.15 (chaîne, ordre des thèmes, `_self`, trois exemples) | résultats cités ci-dessus |
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 16 818 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 — 76 jours, 441 créneaux |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

## Prochaine étape

Page 7 — *CSRF protection* (STANDARD, 455 / 900).
