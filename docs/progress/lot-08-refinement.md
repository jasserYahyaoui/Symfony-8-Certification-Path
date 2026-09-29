# Raffinement pédagogique — Lot 08 (Data Validation)

Suite de la mission ouverte au lot 02 : approfondir les **pages de cours**
existantes — pièges d'examen, comportements implicites, flashcards aux quatre
niveaux — un lot à la fois, dans l'ordre numérique. Le lot 01 reste hors
périmètre sur instruction explicite (voir le journal du lot 02).

Même méthode qu'aux lots 03 à 07 : chaque affirmation vérifiée contre le code
de la branche 8.0 de Symfony ou la documentation correspondante, par exécution
chaque fois que c'est possible, jamais de mémoire ; quand la documentation et le
code divergent, le code l'emporte et l'écart est signalé sur la page ; budget
`REV-001` respecté sans promotion de niveau ; une branche, une PR, une CI verte,
une fusion et un smoke test de production **lu** par page.

## État par page (ordre officiel de l'item)

Chiffres relevés le 2026-09-29 par script sur les fichiers canoniques
(`syllabus-matrix.yml`, `content/**`) de `master` à `109dfd0`, avant la
première page.

| # | Page | Niveau | Mots / plafond | Flashcards | Statut |
|---|---|---|---|---|---|
| 1 | Validator component | STANDARD | 332 / 900 | 1 | **RAFFINÉE** (PR #245) |
| 2 | PHP object validation | STANDARD | 420 / 900 | 1 | **RAFFINÉE** (PR #246) |
| 3 | Built-in validation constraints | STANDARD | 398 / 900 | 1 | **RAFFINÉE** (PR #247) |
| 4 | Validation scopes | STANDARD | 400 / 900 | 1 | **RAFFINÉE** (PR #248) |
| 5 | Validation groups | STANDARD | 399 / 900 | 1 | **RAFFINÉE** (PR #249) |
| 6 | Group sequence | DEEP | 545 / 1200 | 1 | **RAFFINÉE** (PR #250) |
| 7 | Custom callback validators | STANDARD | 404 / 900 | 1 | **RAFFINÉE** (PR #251) |
| 8 | Violations builder | STANDARD | 465 / 900 | 1 | **RAFFINÉE** (PR #252) |

## Page 1 — Validator component, 2026-09-29

`CRS-way4aktj2d5m` · `OIT-6wd8860brzfy` · STANDARD · **332 → 653 mots** sur 900.
Aucun niveau promu. Exécutions avec `symfony/validator` 8.0.15 ;
`ValidatorBuilder.php` identique entre le paquet exécuté et la branche 8.0
(`diff -q`).

### Deux affirmations fausses

- **« `validate()` ne lève jamais d'exception. »** Exécuté : `validate('x')`
  sans contrainte lève une `RuntimeException`, « Cannot validate values of type
  "string" automatically. Please provide a constraint. » — de même pour un
  entier ou `null` ; un objet ou un tableau sans contrainte donne une liste
  vide. Une contrainte mal configurée lève aussi, dès sa construction
  (`new Assert\Length()` : `MissingOptionsException`). Une **donnée** invalide,
  elle, ne lève rien, même mal typée : un tableau contre `Email` donne « This
  value should be of type string. ».
- **L'explication d'un distracteur de `QST-m5f3k7za0tpt`** (LEARNING) disait
  que `validate($candidate)` sans contrainte « réussit toujours » : il lève.
  Explication corrigée, bonne réponse et version inchangées, `reviewed_at` mis à
  jour.

### Une affirmation incomplète

« Hors framework, `Validation::createValidator()` en construit un. » Exact,
mais ce validateur **ne lit pas les attributs** : `ValidatorBuilder` démarre
avec `$enableAttributeMapping = false`. Exécuté sur un objet portant
`#[Assert\NotBlank]` et `#[Assert\Email]` avec des valeurs invalides : **0**
violation ; **2** avec `createValidatorBuilder()->enableAttributeMapping()`.
`components/validator/resources.rst` (8.0) documente cette méthode.

### Compléments, exécutés

- `ConstraintViolationList` : `Countable`, parcourable, accès par indice,
  conversion en chaîne ; une liste vide convertie en booléen vaut `true`.
- Chemin `address.city` pour une violation imbriquée par `#[Assert\Valid]` ;
  code d'`Email` `bd79c0ab-ddba-46cc-a703-a7a4b08de310`.
- `validateProperty()` sur une propriété inexistante : liste vide, sans erreur.
- `Validation::createIsValidCallable()` retourne `false`,
  `Validation::createCallable()` lève `ValidationFailedException`.

**Questions.** `QST-ga6bhvbf0ecp` et `QST-aahvpedaf1ne` (LEARNING),
`QST-jwhsasxb0537` (VALIDATION) relues : exactes, inchangées. Une question
holdout porte sur l'item ; elle n'a pas été lue.

**Flashcards.** 10 ajoutées ; la carte préexistante `FLC-3sceej8y8a54` reçoit le
niveau TRAP. L'item en porte **11** (3 RECALL, 3 UNDERSTANDING, 2 APPLICATION,
3 TRAP), décompte relevé par script avant rédaction.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`enableAttributeMapping`, `createIsValidCallable` et `Cannot validate values`,
absentes de la version `master` de la page et des cartes, présentes dans le
build local.

**Contrôles réellement exécutés le 2026-09-29**

| Contrôle | Résultat |
|---|---|
| exécutions Validator 8.0.15 | résultats cités ci-dessus |
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 16 892 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 — 76 jours, 441 créneaux |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

Le rapport de fin de lot 07 (PR #244, `109dfd0`) a été déployé par le run Pages
36563497225, conclu en succès, smoke test compris.

**Déploiement de la page 1, lu dans le journal d'exécution.** PR #245 fusionnée
en squash (`c0b8e36`). Run Pages 36564545568 : build, déploiement et smoke test
en succès ; la ligne `ok  lot-08  the validator component page carries its four
flashcard levels, the attribute mapping, the callable helper and the scalar
exception` est écrite à **11:55:27 UTC** le 2026-09-29.

## Page 2 — PHP object validation, 2026-09-29

`CRS-ax2v94pgbk0g` · `OIT-d3yp0sq36xrx` · STANDARD · **420 → 671 mots** sur 900.
Aucun niveau promu. Exécutions avec `symfony/validator` 8.0.15 ;
`StaticMethodLoader.php` et `Cascade.php` identiques entre le paquet exécuté et
la branche 8.0 (`diff -q`).

### Une affirmation fausse

**« `loadValidatorMetadata()` écrite comme méthode d'instance n'est jamais
appelée. »** Elle n'est pas ignorée : exécuté, `StaticMethodLoader` lève une
`MappingException`, « The method "NonStatic::loadValidatorMetadata()" should be
static. ». La page le dit ; l'explication du distracteur correspondant de
`QST-5jrexz5sjxkf` (VALIDATION) est précisée, bonne réponse et version
inchangées.

### Une affirmation incomplète

**« Il faut le demander : `#[Assert\Valid]`. »** La référence 8.0 documente
aussi `#[Assert\Cascade]`, posée sur la **classe**, qui valide en profondeur
toutes les propriétés portant un objet. Exécuté : aucune violation sans
cascade ; `address.zipCode` avec `Valid` sur la propriété comme avec `Cascade`
sur la classe. La page, l'explication de `QST-g22z7fmy0cg3` (LEARNING, bonne
réponse inchangée) et la carte `FLC-qb5nx97jkjav` le précisent.

### Compléments, exécutés

- `Valid` sur une propriété à `null` : aucune violation ; sur un tableau
  d'objets : chemins `addresses[0].zipCode`, `addresses[1].zipCode`.
- Héritage : parent `NotBlank`, enfant `Length(min: 5)` — `'abc'` échoue sur
  `Length` ; la même `NotBlank` redéclarée dans l'enfant produit deux violations.
- Propriété privée lue sans accesseur.
- `#[ExtendsValidationFor]`, documenté en 8.0 : hors framework, l'attribut seul
  ne produit rien ; avec `addAttributeMappings()`, la contrainte ajoutée
  s'applique. Une propriété absente de la cible lève, au niveau du composant,
  une `ValidatorException` là où la documentation annonce une
  `MappingException` ; le chemin du framework n'a pas été exécuté, la page le
  dit sans trancher davantage.

**Questions.** `QST-6snh12yz2gz2` (LEARNING) relue : exacte, inchangée —
l'exécution confirme que deux contraintes identiques s'additionnent. L'item ne
porte pas de question holdout.

**Flashcards.** 10 ajoutées ; la carte préexistante `FLC-qb5nx97jkjav` reçoit le
niveau TRAP. L'item en porte **11** (3 RECALL, 2 UNDERSTANDING, 2 APPLICATION,
4 TRAP), décompte relevé par script avant rédaction.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus `Cascade`,
`ExtendsValidationFor` et `StaticMethodLoader`, absentes de la version `master`
de la page et des cartes, présentes dans le build local.

**Contrôles réellement exécutés le 2026-09-29**

| Contrôle | Résultat |
|---|---|
| exécutions Validator 8.0.15 | résultats cités ci-dessus |
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 16 902 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 — 76 jours, 441 créneaux |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

**Déploiement de la page 2, lu dans le journal d'exécution.** PR #246 fusionnée
en squash (`a537dca`). Run Pages 36565703782 : build, déploiement et smoke test
en succès ; la ligne `ok  lot-08  the PHP object validation page carries its
four flashcard levels, the cascade constraint, the extension attribute and the
static loader` est écrite à **12:06:23 UTC** le 2026-09-29.

## Page 3 — Built-in validation constraints, 2026-09-29

`CRS-569y6hb7fwj5` · `OIT-9x3strrjdng7` · STANDARD · **398 → 551 mots** sur 900.
Aucun niveau promu. Exécutions avec `symfony/validator` 8.0.15.

### Une question à deux bonnes réponses, et sa prémisse dans la matrice

`QST-9zsx244xw271` (LEARNING) demandait la contrainte d'une quantité
« obligatoire mais pouvant valoir 0 » : bonne réponse `NotNull`, `NotBlank` donné
faux au motif qu'il « rejetterait l'entier zéro ». Or `NotBlankValidator` teste
`false === $value || (!$value && '0' != $value)`, et en PHP 8 `'0' != 0` est
faux. Exécuté :

| Valeur | `NotBlank` | `NotNull` |
|---|---|---|
| `null` | refusée | refusée |
| `''`, `false`, `[]` | refusées | acceptées |
| `0`, `0.0`, `'0'`, `' '` | acceptées | acceptées |

Pour un entier `0`, **les deux** contraintes conviennent. La question passe en
**v2** sur un cas sans ambiguïté — un commentaire obligatoire dont la chaîne
vide est légitime : `NotNull` reste la bonne réponse, `NotBlank` est faux car il
refuse `''`, et le distracteur `Positive` devient `Length(min: 1)`
(`CHO-9tryz70snn5c`), qui refuse aussi `''`. L'objectif d'apprentissage
`OUT-wkzdab0t8y8n` de la matrice, « … sur une valeur zéro », reposait sur la même
prémisse : il devient « … sur une valeur vide ou nulle ». La page et la carte
`FLC-7p2ygq8ft3x4` disent désormais que `0`, `0.0` et `' '` passent `NotBlank`.

### Un tableau décalé de la référence

Le tableau des familles plaçait `DivisibleBy` parmi les nombres : `map.rst.inc`
(8.0) la range en **comparaison**. Il omettait `Week`, `Video`, `WordCount` et la
famille **financière** (`Iban`, `Bic`, `CardScheme`…). Aligné sur la référence.
L'explication d'un distracteur de `QST-56renepg88tq` (LEARNING) affirmait que
toutes les contraintes viennent du même composant : la contrainte `Twig` vit
dans le bridge Twig ; explication précisée, bonne réponse inchangée.

### Compléments, exécutés

- `normalizer: 'trim'` fait refuser `' '` à `NotBlank`.
- `'1'` satisfait `EqualTo(1)`, pas `IdenticalTo(1)`.
- `Sequentially` : une violation au lieu de deux ; `AtLeastOneOf` : une seule
  violation quand tout échoue.
- `Length` compte les caractères : `'été'` en fait 3.

**Questions.** `QST-fh0eh8ct9m8r` (LEARNING) et `QST-q91f8w8t909e` (VALIDATION)
relues : exactes, inchangées. Une question holdout porte sur l'item ; elle n'a
pas été lue. **Signal pour le propriétaire, sans lecture** : une question
holdout qui affirmerait que `NotBlank` refuse l'entier `0` serait fausse.

**Flashcards.** 10 ajoutées ; la carte préexistante `FLC-7p2ygq8ft3x4` reçoit le
niveau TRAP. L'item en porte **11** (3 RECALL, 2 UNDERSTANDING, 2 APPLICATION,
4 TRAP), décompte relevé par script avant rédaction.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus `CardScheme`,
`Week` et `entier <code>0</code> passe`, absentes de la version `master` de la
page et des cartes, présentes dans le build local.

**Contrôles réellement exécutés le 2026-09-29**

| Contrôle | Résultat |
|---|---|
| exécutions Validator 8.0.15 | résultats cités ci-dessus |
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 16 912 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 — 76 jours, 441 créneaux |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

**Déploiement de la page 3, lu dans le journal d'exécution.** PR #247 fusionnée
en squash (`daf298f`). Run Pages 36567003136 : build, déploiement et smoke test
en succès ; la ligne `ok  lot-08  the built-in constraints page carries its four
flashcard levels, the financial family, the Week constraint and the integer
zero` est écrite à **12:18:35 UTC** le 2026-09-29.

## Page 4 — Validation scopes, 2026-09-29

`CRS-wzmrks85zyh1` · `OIT-ttwpe00f32q9` · STANDARD · **400 → 564 mots** sur 900.
Aucun niveau promu. Exécutions avec `symfony/validator` 8.0.15 ;
`AttributeLoader.php` identique entre le paquet exécuté et la branche 8.0
(`diff -q`).

### Un échec présenté comme silencieux

La page disait qu'une méthode « nommée autrement ne peut pas porter de
contrainte », et `QST-5ryp5r5qg631` (LEARNING) partait d'une règle qui « ne se
déclenche jamais ». Le code lève : `AttributeLoader` teste
`/^(get|is|has)(.+)$/i` et, sinon, lève une `MappingException`, « Constraints can
only be added on methods beginning with "get", "is" or "has". » — reproduit sur
`checkPasswordSafety()`. La question passe en **v2** : l'énoncé décrit
l'exception ; bonne réponse et choix inchangés, explication alignée.

### Compléments, exécutés

- Accesseur `private` et accesseur `static` : acceptés.
- Accesseur attendant un argument : `ArgumentCountError` à la validation —
  l'absence d'argument n'est pas vérifiée au chargement.
- Préfixe insensible à la casse : `ISOK()` accepté, chemin `oK`.
- Chemin d'une violation d'accesseur : le nom sans préfixe, première lettre en
  minuscule — `isPasswordSafe` donne `passwordSafe`, `getFullName` `fullName`.
- `getTargets()` : `'property'` pour `NotBlank`, les deux cibles pour
  `Callback` ; règle croisée exécutée par un `Callback` de classe et `atPath()`.

**Questions.** `QST-6sxdmsbatps8` (LEARNING) et `QST-pa6gzs0cmgg0` (VALIDATION)
relues : exactes, inchangées. L'item ne porte pas de question holdout.

**Flashcards.** 10 ajoutées ; la carte préexistante `FLC-swztaetynk4y` reçoit le
niveau RECALL et mentionne l'exception. L'item en porte **11** (4 RECALL,
2 UNDERSTANDING, 2 APPLICATION, 3 TRAP), décompte relevé par script avant
rédaction.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`AttributeLoader`, `ArgumentCountError` et `passwordSafe`, absentes de la version
`master` de la page et de ses cartes, présentes dans le build local.

**Contrôles réellement exécutés le 2026-09-29**

| Contrôle | Résultat |
|---|---|
| exécutions Validator 8.0.15 | résultats cités ci-dessus |
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 16 922 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 — 76 jours, 441 créneaux |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

**Déploiement de la page 4, lu dans le journal d'exécution.** PR #248 fusionnée
en squash (`7d9ecc1`). Run Pages 36568175861 : build, déploiement et smoke test
en succès ; la ligne `ok  lot-08  the validation scopes page carries its four
flashcard levels, the attribute loader, the argument error and the getter path`
est écrite à **12:30:00 UTC** le 2026-09-29.

## Page 5 — Validation groups, 2026-09-29

`CRS-vskyr5zdwr2t` · `OIT-kkhb3wd341ex` · STANDARD · **399 → 567 mots** sur 900.
Aucun niveau promu. Exécutions avec `symfony/validator` 8.0.15 ;
`Constraint.php` identique entre le paquet exécuté et la branche 8.0
(`diff -q`).

### Une affirmation fausse, reprise de la documentation

La page disait, comme `groups.rst` (8.0), qu'une contrainte appartient à
`Default` si elle déclare « `Default` **ou le nom de la classe** ». Le code ne
va que dans un sens : `Constraint::addImplicitGroupName()` ajoute le nom de la
classe aux contraintes qui sont dans `Default`, jamais l'inverse. Exécuté : une
contrainte sans groupe ou déclarée `groups: ['Default']` reçoit
`["Default", "User"]` ; déclarée `groups: ['User']`, elle ne reçoit que
`["User"]` et **ne s'exécute pas** quand on valide `Default`. La page expose
l'écart ; le code l'emporte.

### Confirmé par exécution

- Sans groupe, seul `Default` : les contraintes `registration` restent inertes.
- Cascade par `#[Assert\Valid]` : `Default` atteint `address.zip`, `User` ne
  l'atteint pas ; un groupe nommé traverse la cascade (`address.street` pour
  `registration`).
- Le troisième argument de `validate()` accepte une chaîne seule.

**Questions.** `QST-1c6zphv3dzgc`, `QST-znrf5tj46jjt` (LEARNING) et
`QST-vzpzmqn8tz5q` (VALIDATION) relues : exactes, inchangées. L'item ne porte
pas de question holdout.

**Flashcards.** 10 ajoutées ; la carte préexistante `FLC-mvq0exxwwfxa` reçoit le
niveau RECALL. L'item en porte **11** (4 RECALL, 2 UNDERSTANDING, 2 APPLICATION,
3 TRAP), décompte relevé par script avant rédaction.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`addImplicitGroupName`, `entre la documentation et le code` et `address.street`,
absentes de la version `master` de la page et de ses cartes, présentes dans le
build local.

**Contrôles réellement exécutés le 2026-09-29**

| Contrôle | Résultat |
|---|---|
| exécutions Validator 8.0.15 | résultats cités ci-dessus |
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 16 932 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 — 76 jours, 441 créneaux |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

**Déploiement de la page 5, lu dans le journal d'exécution.** PR #249 fusionnée
en squash (`95b3fe1`). Run Pages 36569727497 : build, déploiement et smoke test
en succès ; la ligne `ok  lot-08  the validation groups page carries its four
flashcard levels, the implicit group method, the documentation gap and the
cascaded street` est écrite à **12:43:13 UTC** le 2026-09-29.

## Page 6 — Group sequence, 2026-09-29

`CRS-bthv5xmh5wea` · `OIT-rwavvsx2d7nq` · DEEP · **545 → 846 mots** sur 1 200.
Aucun niveau promu. Exécutions avec `symfony/validator` 8.0.15 ;
`ClassMetadata.php` identique entre le paquet exécuté et la branche 8.0
(`diff -q`).

### Une question publiée avec une mauvaise réponse, reprise de la documentation

`sequence_provider.rst` (8.0) dit qu'inclure `Default` dans une séquence produit
une **récursion infinie** ; la page, la carte `FLC-rstx3zwpz28w`,
`QST-467dyvf9ht74` (LEARNING, bonne réponse « Infinite recursion ») et la
justification de niveau de la matrice le reprenaient. Le code refuse la séquence
avant : `ClassMetadata::setGroupSequence()` lève une `GroupDefinitionException`.
Exécuté :

| Séquence de classe | Résultat |
|---|---|
| `['Default', 'Strict']` | « The group "Default" is not allowed in group sequences. » |
| `['Strict']` | « The group "MissingClass" is missing in the group sequence. » |

Corrections :

- `QST-467dyvf9ht74` passe en **v2** : bonne réponse « An exception refusing
  Default in the sequence » (`CHO-64fmhjevsea9`), « Infinite recursion » devient
  un distracteur (`CHO-wpg21mszpb8m`), le distracteur « Only Default is
  validated… » est retiré pour garder quatre choix, source du code ajoutée.
- La carte `FLC-rstx3zwpz28w` est corrigée et nivelée TRAP. Sa justification
  prétendait aussi que la séquence est « l'unique cas » où `Default` et le nom de
  la classe divergent ; la page 5 a montré qu'ils divergent aussi sur les objets
  imbriqués.
- La justification de niveau de `OIT-rwavvsx2d7nq` dans la matrice parle
  désormais d'une exception. Le niveau `DEEP` est inchangé.

### Compléments, exécutés

- Arrêt au premier groupe en échec : `username` vide, une seule violation ;
  rempli, la violation de `Strict`.
- `['Strict']` valide `Strict` seul ; le nom de la classe (`['Seq']`) n'applique
  que ses contraintes ; seul `Default` déroule la séquence.
- Provider : à plat, seules les violations de `User` ; imbriqué, celles de
  `User` et de `Premium`. Un provider sans le nom de la classe ne lève rien.
- Une `GroupSequence` passée à `validate()` accepte `Default` et s'arrête de même.

**Questions.** `QST-h9d44gg643q8`, `QST-0hcwz5ma0vpr` (LEARNING) et
`QST-z99j5z8mk6cf` (VALIDATION) relues : exactes, inchangées — l'exécution
confirme la question sur le tableau imbriqué. Une question holdout porte sur
l'item ; elle n'a pas été lue. **Signal pour le propriétaire, sans lecture** :
une question holdout qui donnerait la récursion infinie pour réponse serait
fausse au regard du code.

**Flashcards.** 9 ajoutées ; la carte préexistante reçoit le niveau TRAP. L'item
en porte **10** (3 RECALL, 2 UNDERSTANDING, 2 APPLICATION, 3 TRAP), décompte
relevé par script avant rédaction.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`GroupDefinitionException`, `ClassMetadata` et `ce que fait le code`, absentes de
la version `master` de la page et de ses cartes, présentes dans le build local.

**Contrôles réellement exécutés le 2026-09-29**

| Contrôle | Résultat |
|---|---|
| exécutions Validator 8.0.15 | résultats cités ci-dessus |
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 16 941 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 — 76 jours, 441 créneaux |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

**Déploiement de la page 6, lu dans le journal d'exécution.** PR #250 fusionnée
en squash (`c5639f5`). Run Pages 36571303519 : build, déploiement et smoke test
en succès ; la ligne `ok  lot-08  the group sequence page carries its four
flashcard levels, the group definition exception, the class metadata and the
documentation heading` est écrite à **12:56:36 UTC** le 2026-09-29.

## Page 7 — Custom callback validators, 2026-09-29

`CRS-96w05v20b8w1` · `OIT-hcdrp2y7kbct` · STANDARD · **404 → 582 mots** sur 900.
Aucun niveau promu. Exécutions avec `symfony/validator` 8.0.15.

### Rien de faux, confirmé par exécution

Chaque affirmation de la page tient au regard de `CallbackValidator` (8.0), qui
teste `isStatic()` et appelle `invoke(null, $object, $context, $payload)` ou
`invoke($object, $context, $payload)`. Exécuté :

- une méthode statique écrite avec la signature d'instance lève une `TypeError`
  — « Argument #1 ($c) must be of type … ExecutionContextInterface, StaticWrong
  given » ;
- retourner `false` ne produit aucune violation ;
- un appelable externe `[Ext::class, 'validate']` est appelé.

### Compléments, exécutés

- Un callback `private` est appelé.
- Un nom de méthode absent lève une `ConstraintDefinitionException` : « Method
  "nope" targeted by Callback constraint does not exist in class "Missing". ».
- Une fermeture passée à `new Assert\Callback(...)` sur une valeur nue reçoit la
  valeur et le contexte.
- `payload: ['severity' => 'warning']` arrive tel quel dans la méthode.

**Questions.** Les quatre questions non holdout de l'item (trois LEARNING, une
VALIDATION) relues : exactes, inchangées. Une question holdout porte sur
l'item ; elle n'a pas été lue.

**Flashcards.** 10 ajoutées ; la carte préexistante `FLC-6n0qg98nptxe` reçoit le
niveau RECALL. L'item en porte **11** (4 RECALL, 2 UNDERSTANDING,
2 APPLICATION, 3 TRAP), décompte relevé par script avant rédaction.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`CallbackValidator`, `ConstraintDefinitionException` et `Les formes du`,
absentes de la version `master` de la page et de ses cartes, présentes dans le
build local.

**Contrôles réellement exécutés le 2026-09-29**

| Contrôle | Résultat |
|---|---|
| exécutions Validator 8.0.15 | résultats cités ci-dessus |
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 16 951 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 — 76 jours, 441 créneaux |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

**Déploiement de la page 7, lu dans le journal d'exécution.** PR #251 fusionnée
en squash (`d4c907a`). Run Pages 36572924671 : build, déploiement et smoke test
en succès ; la ligne `ok  lot-08  the callback validators page carries its four
flashcard levels, the validator class, the definition exception and the callback
forms` est écrite à **13:11:11 UTC** le 2026-09-29.

## Page 8 — Violations builder, 2026-09-29

`CRS-d235sja3h7y2` · `OIT-0wnxbapegqhv` · STANDARD · **465 → 623 mots** sur 900.
Aucun niveau promu. Exécutions avec `symfony/validator` 8.0.15, depuis un
`Callback` de classe.

### Une affirmation incomplète

« `addViolation()` ajoute la violation avec les valeurs par défaut, rien à
régler. » `ExecutionContextInterface::addViolation(string $message, array
$params = [])` accepte déjà les paramètres du message ; exécuté, `'direct {{ v }}'`
devient `'direct p'`. `buildViolation()` les accepte aussi en second argument.
Ce que seule la forme constructeur permet : le chemin, le code, la valeur
fautive, le pluriel, le domaine de traduction. L'explication d'un distracteur de
`QST-m2hsqcvcwrej` (LEARNING), qui disait que les deux formes peuvent viser une
propriété, est corrigée : seul le constructeur déplace le chemin ; bonne réponse
inchangée.

### Compléments, exécutés

- Chaîne `buildViolation()->setParameter()` non terminée : aucune violation.
- `atPath('email')` : chemin `email`, mais valeur fautive = **l'objet entier** ;
  `setInvalidValue()` la corrige. `atPath('address.city')` compose le chemin.
- `setCode('MY_CODE')` relu par `getCode()` ; `setPlural(2)` par `getPlural()` ;
  `getMessageTemplate()` rend le gabarit, `getMessage()` le texte rempli.
- `ConstraintViolationBuilderInterface` (8.0) compte dix méthodes, listées sur la
  page.

**Questions.** `QST-egqjqhe0s1vv`, `QST-r3bv4np3ttcs` (LEARNING) et
`QST-vp3ys4f6xypm` (VALIDATION) relues : exactes, inchangées. L'item ne porte pas
de question holdout.

**Flashcards.** 10 ajoutées ; la carte préexistante `FLC-crtrxfbtxbrw` reçoit le
niveau TRAP. L'item en porte **11** (3 RECALL, 3 UNDERSTANDING, 2 APPLICATION,
3 TRAP), décompte relevé par script avant rédaction.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`getMessageTemplate`, `change pas la valeur fautive` et `Les dix méthodes`,
absentes de la version `master` de la page et de ses cartes, présentes dans le
build local. `setInvalidValue`, envisagée, figurait déjà sur la page : écartée.

**Contrôles réellement exécutés le 2026-09-29**

| Contrôle | Résultat |
|---|---|
| exécutions Validator 8.0.15 | résultats cités ci-dessus |
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 16 961 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 — 76 jours, 441 créneaux |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

**Déploiement de la page 8, lu dans le journal d'exécution.** PR #252 fusionnée
en squash (`de6e940`). Run Pages 36574321210 : build, déploiement et smoke test
en succès ; la ligne `ok  lot-08  the violations builder page carries its four
flashcard levels, the message template, the invalid value heading and the ten
methods` est écrite à **13:22:32 UTC** le 2026-09-29.

# Rapport de fin de lot 08

Toutes les figures ci-dessous sont **réconciliées par script** depuis
`docs/syllabus/syllabus-matrix.yml`, `content/courses/**`,
`content/flashcards/**` et `content/questions/**` — jamais depuis un rapport
antérieur ni de mémoire (`CLAUDE.md`, « Reporting a lot »). Base de comparaison :
`109dfd0`, le commit de `master` qui précède la première page refondue (PR #245).
État mesuré : `master` à `de6e940`. Le même script a vérifié que les huit
décomptes de mots et de niveaux écrits dans les entrées de page correspondent
aux fichiers.

## Périmètre

**8** items officiels atomiques portent `lot: lot-08` dans la matrice :
7 `STANDARD`, 1 `DEEP` — niveaux inchangés pendant la campagne. Cette
répartition est une **observation** : aucune cible n'existe.

## Couverture — formule unique (§3.5)

```text
EXAM_READY atomiques officiels / total atomiques officiels
= 163 / 163 = 100,0 %
```

Ce chiffre est **cumulatif et porte sur tout le projet**. Le sous-ensemble du
lot 08 est **8 / 8**. Aucun des deux n'a bougé : **ce lot n'a pas fait
progresser la couverture** — il a approfondi et corrigé des pages déjà comptées.

## Volume de cours — corps en mots, front matter exclu

| | Avant campagne | Après | Nouveau |
|---|---|---|---|
| 8 cours du lot 08 | 3 363 | **5 057** | **+1 694** |

Aucune page ne dépasse son budget `REV-001` :

| Niveau | Budget | Pages | Plus proche du plafond |
|---|---|---|---|
| `STANDARD` | 900 | 7 | PHP object validation, 671 |
| `DEEP` | 1200 | 1 | Group sequence, 846 |

## Flashcards

| | Avant campagne | Après | Nouveau |
|---|---|---|---|
| Cartes sur les items du lot 08 | 8 | **87** | **+79** |

Répartition par niveau — **observation, jamais une cible** :
`RECALL` 27 · `UNDERSTANDING` 18 · `APPLICATION` 16 · `TRAP` 26.
**Zéro carte du lot sans niveau.** Les huit cartes préexistantes ont reçu un
niveau ; aucune n'a été supprimée ; quatre ont été corrigées (pages 2, 3, 4, 6),
décompte relevé par script.

## Questions et pools

**33** questions portent sur les items du lot 08 — aucune ajoutée, aucune
supprimée :

| Pool | Nombre | Fichier |
|---|---|---|
| `LEARNING` | 21 | `lot-08-data-validation.yml` |
| `VALIDATION` | 8 | `lot-08-data-validation.yml` |
| `HOLDOUT` | 4 | 2 dans `lot-08-data-validation.yml`, 2 dans `mock-04-holdout.yml` |

**Huit questions modifiées** — sept `LEARNING`, une `VALIDATION`. Là où la
version n'a pas bougé, l'énoncé, le texte des choix et la bonne réponse sont
identiques à la base (**0 écart**, vérifié par script) :

| Question | Pool | Page | Modification |
|---|---|---|---|
| `QST-m5f3k7za0tpt` | LEARNING | 1 | explication d'un choix (`validate()` sans contrainte lève) |
| `QST-g22z7fmy0cg3` | LEARNING | 2 | explications (`Cascade`) |
| `QST-5jrexz5sjxkf` | VALIDATION | 2 | explication d'un choix (`MappingException`) |
| `QST-9zsx244xw271` | LEARNING | 3 | **deux bonnes réponses** : énoncé réécrit, un choix neuf, v1 → 2 |
| `QST-56renepg88tq` | LEARNING | 3 | explication d'un choix |
| `QST-5ryp5r5qg631` | LEARNING | 4 | énoncé aligné sur le code (`MappingException`), v1 → 2 |
| `QST-467dyvf9ht74` | LEARNING | 6 | **bonne réponse changée** (exception, pas récursion), v1 → 2 |
| `QST-m2hsqcvcwrej` | LEARNING | 8 | explication d'un choix |

La comparaison compte **zéro question `HOLDOUT` modifiée** ; le script n'en
teste que l'égalité, sans en afficher le contenu.

**`POOL-002` : 0 manquant.** Les huit items `STANDARD` ou `DEEP` `EXAM_READY`
portent chacun au moins une question `VALIDATION`.

### Matrice

Deux textes de la matrice reposaient sur une prémisse fausse et ont été
corrigés, sans toucher aux niveaux : l'objectif `OUT-wkzdab0t8y8n` (« … sur une
valeur zéro » devient « … sur une valeur vide ou nulle », page 3) et la
justification de niveau de `OIT-rwavvsx2d7nq` (la récursion infinie devient une
exception, page 6). Le rapport de couverture est inchangé.

### Holdout — isolation fonctionnelle, pas confidentialité

Les 4 questions `HOLDOUT` du lot sont **absentes de `practice.json` et de
`exam.json`** — `PayloadBuilder::assertNoHoldoutLeak()` l'assure à la
construction, et le smoke test de production le revérifie sur les octets servis
(« 516 questions, all LEARNING, no holdout id or choice »). C'est une
**isolation fonctionnelle**. Ce n'est **pas** de la confidentialité :
`mock-4.json` est publié et porte les réponses correctes.

Aucun contenu holdout n'a été lu ; les recherches ont exclu le pool `HOLDOUT` et
`mock-04-holdout.yml`. Deux signaux restent ouverts pour le propriétaire, sans
identifiant : une question holdout qui affirmerait que `NotBlank` refuse
l'entier `0` (page 3), ou qui donnerait la récursion infinie comme sanction de
`Default` dans une séquence (page 6), serait fausse au regard du code.

## Contrôles — état réel

| Contrôle | Résultat | Preuve |
|---|---|---|
| `php bin/cert validate` | **PASS** | 0 bloquant à chaque page ; 1 avertissement `PED-003` préexistant |
| `php bin/cert coverage` | **PASS** | aucun écart, à chaque page |
| `node website/tools/verify-reschedule.mjs` | **PASS** | exit 0 à chaque page |
| `composer gate-full` | **PASS** | 299 tests, 16 961 assertions sur la page 8, `TOTAL VIOLATIONS: 0` |
| Jeu d'audits de CI (11 scripts) | **PASS** | exit 0, `FINDINGS: 0`, à chaque page |
| `prove_framework_rules_fail.py` | **PASS** | `PROOF OK`, 11 cas ; empreinte SHA-256 de `content/` et `docs/` identique avant et après |
| `prove_flashcard_coverage_fails.py` | **PASS** | `PROOF OK` |
| `aud10 --prove`, `lot27_practice_audit.py --prove` | **PASS** | exit 0 |
| Accessibilité (§13, §17) | **PASS** | incluse dans `gate-full` ; verte en CI à chaque PR |
| Branche + PR par page (§15) | **PASS** | #245 à #252, une par page, CI verte avant chaque fusion en squash |
| Déploiement + smoke test de production | **PASS pour les 8 pages** | lignes `ok lot-08 …` lues dans les journaux d'exécution, page par page ; la dernière à 13:22:32 UTC le 2026-09-29 |

## Défauts trouvés dans le corpus existant

Vérifiés contre le code de Symfony 8.0, **par exécution** (`symfony/validator`
8.0.15, et `dependency-injection` 8.0.15 pour les extensions) :

| Page | Défaut corrigé |
|---|---|
| 01 Validator component | « `validate()` ne lève jamais » : un scalaire sans contrainte lève ; `createValidator()` ignore les attributs |
| 02 PHP object validation | `loadValidatorMetadata()` non statique lève une `MappingException` ; `Cascade` absent |
| 03 Built-in constraints | `NotBlank` accepte l'entier `0` — **une question à deux bonnes réponses** ; familles décalées de la référence |
| 04 Validation scopes | un mauvais nom d'accesseur lève, il n'est pas ignoré en silence |
| 05 Validation groups | une contrainte du seul nom de classe n'est pas dans `Default` |
| 06 Group sequence | `Default` dans une séquence lève, pas de récursion — **bonne réponse d'une question changée** |
| 07 Callback validators | rien de faux ; comportements confirmés et complétés |
| 08 Violations builder | `addViolation()` du contexte accepte des paramètres ; `atPath()` ne change pas la valeur fautive |

## Écarts entre la documentation et le code

Tranchés par la hiérarchie des sources — le code l'emporte — et signalés sur les
pages :

| Page | La documentation (8.0) | Le code, exécuté |
|---|---|---|
| 05 | `groups.rst` : une contrainte configurée avec le nom de la classe est dans `Default` | groupes `["User"]` seulement ; ne s'exécute pas en `Default` |
| 06 | `sequence_provider.rst` : `Default` dans une séquence produit une récursion infinie | `GroupDefinitionException` levée par `ClassMetadata::setGroupSequence()` |
| 02 | `validation.rst` : propriété absente de la cible d'`#[ExtendsValidationFor]` → `MappingException` | au niveau du composant, `ValidatorException` ; chemin du framework non exécuté, écart signalé sans être tranché |

Une règle documentée a au contraire été confirmée : `Validation::createValidator()`
ne lit pas les attributs sans `enableAttributeMapping()`
(`components/validator/resources.rst`).

## Défauts introduits par moi

**Aucun n'a atteint la production.**

| Défaut | Attrapé par |
|---|---|
| aiguille de smoke test `Assert\\Cascade` au motif fragile (page 2) | relecture du bloc avant contrôle ; remplacée par `Cascade` |
| aiguille `setInvalidValue` déjà présente sur la page en production (page 8) | contrôle scripté d'absence sur `master` ; remplacée |
| message cité avec des barres obliques inverses parasites dans une explication préparée (page 6) | relecture du script avant application ; message reformulé |

## Résumé auditable

> Le lot 08 compte **8** items officiels atomiques (7 `STANDARD`, 1 `DEEP`),
> tous `EXAM_READY` avant comme après. La couverture du projet —
> `EXAM_READY / total`, la seule formule admise — vaut **163/163 = 100,0 %** et
> **n'a pas bougé**.
>
> Les huit cours passent de **3 363** à **5 057** mots de corps (+1 694), aucun
> au-dessus de son budget, aucun niveau promu. Les flashcards passent de **8** à
> **87** (+79), toutes nivelées, réparties 27/18/16/26 — une **observation**, pas
> une cible. **33** questions portent sur le lot (21 `LEARNING`, 8 `VALIDATION`,
> 4 `HOLDOUT`), aucune ajoutée ni supprimée ; huit corrigées, dont trois en
> version 2 — une bonne réponse fausse changée, une question à deux bonnes
> réponses réécrite, un énoncé aligné sur le code ; aucune `HOLDOUT` modifiée ;
> `POOL-002` sans manquant.
>
> Le holdout est **fonctionnellement isolé** des payloads d'apprentissage, ce que
> le smoke test de production revérifie ; il n'est **pas confidentiel**.
>
> Sept pages sur huit portaient une affirmation fausse ou incomplète ; toutes
> corrigées contre le code, par exécution. Deux écarts entre documentation et
> code ont été tranchés en faveur du code, un troisième signalé. Les 8 pages sont
> en production, smoke test lu pour chacune.

## Prochaine étape

Lot 09, page 1, dans l'ordre officiel des items. Restent ouverts : les signaux
holdout des lots 06, 07 et 08 (revue par le propriétaire), l'avertissement
`PED-003` et la PR #148, sans lien avec ce lot.
