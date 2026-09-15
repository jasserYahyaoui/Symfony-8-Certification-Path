# ADR-0007 — Refinement framework version 2

**Status:** Accepted
**Date:** 2026-09-08
**Supersedes:** nothing. **Relates to:** [ADR-0002](0002-persistent-identifiers.md), [ADR-0006](0006-exam-mode-serves-the-validation-pool.md)

## Context

The Certification Readiness metric shipped in PR #83 with a finding recorded in
its own documentation: **all 163 items already satisfy every automated
criterion**. The published figure — 5.5% — was carried entirely by the audited
dimension, a human reading recorded in `docs/progress/refinement-log.yml`.

A metric whose automated half cannot discriminate is not measuring; it is
deferring. The reason it could not discriminate is that three things a refined
item ought to have were absent from the **model**, not merely from the content:

**No question archetype.** The bank's only structural field is `type`, and it
holds the single value `mcq` for all 550 questions. An axis with one value
distinguishes nothing, so an item could be assessed four times by the same
mould and read as fully assessed.

**No link from an outcome to the question that assesses it.** `learning_outcomes`
was a list of bare strings, so no question could name one. PED-002 asks only
whether an item has *an* assessment, which one question against five outcomes
satisfies. Measured on the corpus: **73 of 163 items (44.8%) carry fewer
questions than declared outcomes**, which makes one-to-one outcome coverage
arithmetically impossible for them — with every gate green.

**No measure of revision cost.** Nothing in the project could distinguish a
course that teaches from a course that is merely long, so the refinement work
about to begin — scenarios, rationale, targeted confusions — had no ceiling.

The full impact audit is in
[`docs/audit/framework-extension/README.md`](../audit/framework-extension/README.md).

## Decision

### 1. A learning outcome is an identified entity

`EntityType::LearningOutcome` and the prefix `OUT` already existed in
`src/Support/EntityType.php`; outcomes were declared as entities and then
stored as strings. They now carry their minted id:

```yaml
learning_outcomes:
  - id: OUT-3k9m2xq7bv4t
    outcome: "Attribuer une fonctionnalité du langage à sa version"
```

The link from a question is the id, never an index into the list: an index
remaps silently the moment somebody reorders the outcomes, and a link that can
be wrong without anybody noticing is worse than no link.

```yaml
assesses_outcomes:
  - OUT-3k9m2xq7bv4t
```

### 2. A question declares its structural archetype

A third axis beside `cognitive_level` (how deeply) and `exam_skill` (what to
do): `question_archetype` says what SHAPE the question takes. The nine values
are defined in [`docs/policy/question-archetypes.md`](../policy/question-archetypes.md).
Every one is verifiable **by reading the question**. None is a claim about the
official exam's composition — this project has no evidence of that composition
and does not pretend to (§19).

### 3. Revision cost has a budget

Body words per item, capped per content level, in
[`docs/policy/revision-budget.md`](../policy/revision-budget.md). This is the
one measure in the project where **more is worse**, which is why it does not
collide with CLAUDE.md's ban on counting files or lines as progress.

### 4. The schema version is NOT bumped; the loader is tolerant and the rules are staged

`MigrationRunner` carries no registered migrations. Bumping `syllabus-matrix`
to version 2 would require a migration whose job is to invent identifiers at
load time — random per load, or derived from the outcome text. ADR-0002 forbids
both.

Instead:

- `MatrixLoader` accepts a bare string *or* an `{id, outcome}` mapping;
- `ARC-001`, `PED-003` and `REV-001` raise **errors only for lots recorded as
  refined under the current framework**, and report as warnings elsewhere;
- each lot's refinement pass brings its own ids and archetypes in.

**Exit condition, so the tolerance cannot quietly become permanent:** when all
27 lots are recorded at framework version 2, the bare-string form is removed
from `MatrixLoader`, the schema is bumped to 2, and the rules drop their
staging. That is one deliberate act, recorded here in advance.

### 5. Refinement is versioned, and raising the bar lowers the number

`docs/progress/refinement-log.yml` gains `framework_version` per lot.
Lot 01 was audited under version 1, before any of the three structures existed.
It keeps its record — that audit found four exam traps no question verified,
and that work is real — but it is **not credited with structures it does not
carry**.

The consequence is deliberate and is the point of the decision:

| | before | after |
|---|---|---|
| Official Coverage | 100% | 100% |
| Certification Readiness | 5.5% (9/163) | **0% (0/163)** |
| Lots refined | 1/27 | 0/27 (lot-01 shown as audited under framework v1) |

A metric that only ever rises measures effort, not readiness. Lot 01 is
re-refined under version 2, not re-labelled.

## Consequences

**Accepted.** The published Readiness figure falls to 0%. The dashboard states
why, on the page, rather than in a commit message.

**Accepted.** Two shapes for `learning_outcomes` coexist until every lot is
refined. Mitigated by the exit condition above and by `PED-003`, which forbids
the old shape wherever refinement is claimed.

**Unchanged.** The published payloads keep `learning_outcomes: string[]`;
`PayloadBuilder` exports `learningOutcomeTexts()`. The React application, the
mock payloads and the deployed JSON are byte-compatible with before.

**Guarded.** All three rules report nothing on the current corpus, which is
exactly the shape the five vacuous checks found in this project had.
`tools/audit/prove_framework_rules_fail.py` injects one defect per rule into
real canonical data, asserts the rule fires with `[ERROR]`, and restores every
file byte-identically under SHA-256 comparison.

---

## Addendum — 2026-09-15 : la condition de sortie est exécutée, et sa rédaction était fausse

### Le défaut de rédaction

La condition de sortie écrite ci-dessus dit :

> when all **27 lots** are recorded at framework version 2, the bare-string form
> is removed from `MatrixLoader`, the schema is bumped to 2, and the rules drop
> their staging.

**Elle est insatisfiable par construction.** Le lot 27 ne porte aucun item de la
matrice et aucune question — il consolide la revue finale, les mocks et le
holdout. Les trois structures que le cadre version 2 ajoute (un `OUT` par
outcome, le lien `assesses_outcomes`, le `question_archetype`) et le budget de
révision n'ont, dans le lot 27, **rien sur quoi se poser**. Aucun audit de
raffinement ne peut l'y enregistrer, aujourd'hui ni jamais.

Vérifié plutôt que supposé : `lot-27` compte **0 item de matrice** et
**0 question**. La matrice ne connaît que 26 lots distincts.

Écrire « 27 » plutôt que « les lots qui portent des items officiels atomiques »
était une erreur de ma part au moment de rédiger l'ADR. Laissée telle quelle,
elle rendait la tolérance **permanente** — exactement ce que la phrase
« so the tolerance cannot quietly become permanent » voulait empêcher.

### La lecture retenue

La condition de sortie vise **les lots que le cadre peut saisir** : les
**vingt-six** qui portent des items officiels atomiques. Tous les vingt-six sont
enregistrés à `framework_version: 2` dans `docs/progress/refinement-log.yml`,
chacun avec sa *pull request*, son commit de fusion et son job de *smoke test*
de production.

### L'acte, exécuté

Un seul acte délibéré, comme annoncé :

- **`MatrixLoader`** n'accepte plus la chaîne nue. Un outcome sans identité est
  une `SchemaException` nommant `php bin/cert id:mint LearningOutcome`.
- **Le schéma `syllabus-matrix` passe à 2**, avec sa migration
  `SyllabusMatrixOutcomeIdentity`. L'objection de l'époque — une migration
  devrait inventer un identifiant, ce qu'ADR-0002 interdit — ne tient plus :
  les 603 outcomes en portent un. La migration ne convertit donc **pas les
  données, mais le contrat**, et refuse le document qui ne peut pas l'honorer.
  Elle n'invente rien.
- **`ARC-001`, `PED-003` et `REV-001` perdent leur staging.** Le
  `question_archetype` est exigé partout, un outcome non identifié est une
  erreur partout, le budget de révision est un plafond partout. Le champ
  `frameworkRefinedLots` disparaît de `ContentSet` : une tolérance qui ne couvre
  plus personne continue de dire au lecteur suivant que la barre est facultative.
- **`aud10`** gate les vingt-six lots au lieu des lots raffinés.

### Ce que l'acte ne change pas aujourd'hui

**Rien, sur le corpus actuel.** Tous les lots étant déjà raffinés, la tolérance
ne couvrait personne : `validate` sort à 0 avant comme après, avec le même
unique avertissement agrégé. L'acte n'est pas un durcissement du contenu, c'est
la suppression d'une porte de sortie pour le contenu futur.

L'avertissement agrégé de `PED-003` **n'est pas** du staging et reste en place :
il compte les items portant moins de questions que d'outcomes, ce qui ne prouve
rien seul — une question peut en évaluer deux — mais garde un chiffre visible.
Voir [`docs/audit/ped-003-shortfall-reading/`](../audit/ped-003-shortfall-reading/README.md).

### Ce qui reste ouvert

La **limite de dénominateur d'`aud10`** n'est pas traitée par cet acte et n'a
jamais été traitée en affaiblissant la règle. Sous quatre ou cinq questions, le
pas de la mesure est plus grand que l'effet mesuré. Trois unités consécutives
l'ont signalée ; la décision de gouvernance reste à prendre.

### Preuve

`prove_framework_rules_fail.py` — **7 cas**, chacun injecté dans des données
canoniques réelles, chaque fichier restauré byte-identique sous SHA-256. Un cas
a changé de cible plutôt que d'être supprimé : l'outcome non identifié est
désormais refusé **au parsing**, avant que les règles ne tournent, donc le cas
suit le contrôle qui l'attrape au lieu d'être retiré parce qu'il s'était tu.
