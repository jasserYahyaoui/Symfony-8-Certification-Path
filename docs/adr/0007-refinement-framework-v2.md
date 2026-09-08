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
