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
| 1 | Validator component | STANDARD | 332 / 900 | 1 | en cours |
| 2 | PHP object validation | STANDARD | 420 / 900 | 1 | à faire |
| 3 | Built-in validation constraints | STANDARD | 398 / 900 | 1 | à faire |
| 4 | Validation scopes | STANDARD | 400 / 900 | 1 | à faire |
| 5 | Validation groups | STANDARD | 399 / 900 | 1 | à faire |
| 6 | Group sequence | DEEP | 545 / 1200 | 1 | à faire |
| 7 | Custom callback validators | STANDARD | 404 / 900 | 1 | à faire |
| 8 | Violations builder | STANDARD | 465 / 900 | 1 | à faire |

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

## Prochaine étape

Page 2 — *PHP object validation* (STANDARD, 420 / 900).
