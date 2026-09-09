# Lot 01 — re-refined under framework version 2

**Date**: 2026-09-09 · **Branch**: `refine/lot-01-framework-v2` · **Base**: `eab44e3`

Lot 01 was refined on 2026-09-08 under framework version 1 and its audit stands:
it found *Interfaces* documented but never made applicable, and four exam traps
the courses teach that no question verified. Version 2 ([ADR-0007](../../adr/0007-refinement-framework-v2.md))
added three structures that did not exist then — identified learning outcomes
with a link from the question that assesses them, question archetypes, and a
revision budget. This pass brings the lot up to them.

Every archetype below was assigned **by reading the question**, and every
behaviour asserted by a new question was **executed on PHP 8.4.19** before it
was written. Nothing here is inferred from the model's memory (§19).

## 1. What the framework found that no earlier gate could

### 1.1 Three outcomes were assessed only by a HOLDOUT question

| Item | Outcome | Named only by |
|---|---|---|
| Attributes | `OUT-0rkkxvd0e76f` — declare an attribute with its targets and `IS_REPEATABLE` | `QST-2vdpk7cg3dy9` (HOLDOUT) |
| Anonymous functions and closures | `OUT-pj3m73g0x58y` — `$this` binding and the effect of `static` | `QST-t77g2sjg2cte` (HOLDOUT) |
| Traits | `OUT-x8c9nce1wqh5` — what a trait may contain, and why it is not a type | `QST-95yb2ee8eb52` (HOLDOUT) |

This is a defect, not a technicality. The holdout reaches exactly one payload,
`mock-4.json`, sat once and unseen (ADR-0005, ADR-0006). An outcome named only
there is one the learner **can never practise**, and the item's own
`minimum_evidence` — a success in exam mode — could not be produced for it.

The framework did not see this at first: `R11` and `PED-003` counted any
question, holdout included. Both were tightened, and
`RefinementFrameworkRuleTest::testAnOutcomeNamedOnlyByAHoldoutQuestionIsNotAssessed`
now holds the line. **The rule was changed because the content exposed it,
which is the only honest order.**

### 1.2 The vocabulary was missing half a quadrant

`question_archetype` shipped with nine values. Assigning them to 36 real
questions showed two shapes with nowhere to go: a behaviour **described** rather
than shown, asked either *why* it happens or *what results*. Nine questions of
Lot 01 are of those two shapes.

Forcing them into `CODE_DIAGNOSIS` or `CODE_OUTPUT` would have meant declaring a
`code_language` that is not there — a small lie that would have made the
consistency check stop meaning anything. `BEHAVIOR_DIAGNOSIS` and
`BEHAVIOR_PREDICTION` were added instead, completing a 2×2:

| | asks what results | asks why |
|---|---|---|
| **ships a listing** | `CODE_OUTPUT` | `CODE_DIAGNOSIS` |
| **describes the behaviour** | `BEHAVIOR_PREDICTION` | `BEHAVIOR_DIAGNOSIS` |

Both new values are constrained: they are rejected on a question that declares
`code_language`, and `BEHAVIOR_DIAGNOSIS` additionally requires
`exam_skill: DIAGNOSE`. `BEHAVIOR_PREDICTION` carries no skill requirement on
purpose — it asks what results, not why.

### 1.3 The item promised four language features; the source and the course give five

`OUT-ayahqn48ev7g` read *"Énumérer les **quatre** ajouts de langage de PHP 8.4"*.
The course teaches **five** in prose — property hooks, asymmetric visibility,
lazy objects, `#[\Deprecated]`, and `new` being dereferencable — but its summary
table listed only the first four.

Checked against `php/php-src` at `PHP-8.4`, section *2. New Features > Core*:
all five are there, each with an RFC. The outcome and the table were wrong, not
the prose. Both corrected.

No gate could have caught this: no rule compares an outcome's wording with the
course that serves it.

## 2. What was added

Five questions, all `LEARNING`, all English, each verified by execution on
PHP 8.4.19 before being written.

| Id | Item | Archetype | Difficulty | Verified behaviour |
|---|---|---|---|---|
| `QST-604t8tj8gx9c` | Attributes | `BEHAVIOR_DIAGNOSIS` | hard | `getAttributes()` returns **both** placements of a non-repeatable attribute and raises nothing; `newInstance()` then raises `Error: Attribute "Once" must not be repeated` |
| `QST-mhsb9ctp310m` | Anonymous functions | `BEHAVIOR_DIAGNOSIS` | hard | `use ($this)` is refused at compile time: `Cannot use $this as lexical variable` |
| `QST-4rr5p2mvc7g5` | Traits | `BEHAVIOR_DIAGNOSIS` | hard | a class using a trait is **not** `instanceof` it, and a trait name as a parameter type raises `TypeError` |
| `QST-6t35fhgzx5x2` | Abstract classes | `BEHAVIOR_DIAGNOSIS` | hard | narrowing `abstract protected` to `private` in the child: `Access level to Impl::run() must be protected (as in class Base) or weaker` |
| `QST-f2m90kz2ya28` | PHP API up to 8.4 | `VERSION_ATTRIBUTION` | hard | the five core additions of 8.4, read from `php-src` `PHP-8.4` `UPGRADING` |

The first three close §1.1. The fourth closes the two gaps the readiness metric
had already named for *Abstract classes* — no `DIAGNOSE` question and no `hard`
one. The fifth closes §1.3 and gives `OUT-ayahqn48ev7g` an assessment that
actually asks for enumeration.

### Why these are not near-duplicates of the holdout questions they relieve

Each attacks its outcome from an angle the holdout question does not use.
`QST-2vdpk7cg3dy9` **states** the declaration rules; `QST-604t8tj8gx9c` asks
**when** a violation of them surfaces, and the answer — at `newInstance()`, not
at `getAttributes()` — is a fact the holdout question never touches. The same
holds for the other two. `DuplicateQuestionRule` passes.

Note the qualifier §14 requires: the holdout remains **functionally isolated**
(absent from `practice.json` and `exam.json`, `assertNoHoldoutLeak()`), which is
not confidentiality — `mock-4.json` is published and carries correct answers.

## 3. Archetype distribution across the lot, as an observation

Stated as an observation and never as a target, exactly like level distribution.

| Archetype | Questions |
|---|---|
| `BEHAVIOR_DIAGNOSIS` | 8 |
| `BEHAVIOR_PREDICTION` | 8 |
| `API_SIGNATURE` | 7 |
| `DEFINITION_RECALL` | 4 |
| `SCENARIO_CHOICE` | 4 |
| `CODE_OUTPUT` | 3 |
| `CONCEPT_DISTINCTION` | 3 |
| `VERSION_ATTRIBUTION` | 2 |
| `CODE_DIAGNOSIS` | 1 |
| `SEQUENCE_ORDER` | 1 |
| **total** | **41** |

Counted from `syllabus-matrix.yml` and `content/questions/**` by script, not
from the assignment notes that produced them — a first draft of this table was
written from those notes and had four of the ten rows wrong.

Every one of the nine items uses at least two distinct archetypes among its
non-holdout questions (`R13`): four items use four, two use three, and three
use two.
`CONFIG_BEHAVIOR` does not appear, and that is not a gap: Lot 01 is the PHP
language, which has no configuration to show.

## 4. What was deliberately NOT added

- **A question per outcome as a matter of form.** `R11` asks that every outcome
  be assessed, not that the count match. Where one question genuinely assesses
  one outcome, one question is the answer.
- **A `DEEP` promotion for any item.** Nothing here changed what a concept
  requires, and §4.1 admits no other reason.
- **A second question on `new` being dereferencable.** It is in the enumeration
  question and in the course; §1.4's value gate does not carry a second.
- **Rewriting the two near-mirror OOP questions** (`new self()` in
  `QST-9be1jmm121k3`, `new static()` in `QST-5565an30ezds`). They are close in
  shape, but the pair *is* the distinction the item teaches, and `R13` is
  satisfied by the item's other archetypes. Changing verified content to satisfy
  an aesthetic judgement is not refinement.

## 5. Result

| | before this pass | after |
|---|---|---|
| Lot 01 framework version | 1 | **2** |
| Identified outcomes | 0 of 28 | **28 of 28** |
| Outcomes assessed outside the holdout | 25 of 28 | **28 of 28** |
| Questions carrying an archetype | 0 of 36 | **41 of 41** |
| Items using ≥ 2 archetypes | not measurable | **9 of 9** |
| Certification Readiness | 0% (0/163) | **5.5% (9/163)** |
| Lots refined | 0 of 27 | **1 of 27** |

Coverage is unchanged at 100% — this pass added no atomic official item, and
was never going to.
