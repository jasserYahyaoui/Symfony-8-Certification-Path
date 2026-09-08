# Framework extension — read-only impact audit

**Date**: 2026-09-08 · **Branch**: `refine/framework-archetypes` · **Base**: `9b99c99`

§12 requires an impact audit before a schema change: inspect the validators,
the builders, the tests and the consumers, then propose the *minimal*
modification. Nothing in this document was changed while it was written; every
figure below was measured from the canonical files by script, on `9b99c99`.

## 1. Why the framework needs extending

The Certification Readiness metric published in PR #83 reports
`5.5% (9/163)`. Its own documentation records the finding that made it
necessary: **all 163 items already satisfy every automated criterion**, so the
number is carried entirely by the audited dimension — a human reading. Three
things a refined item ought to have are, today, not merely absent from the
content but **absent from the model**, which means no gate can ever object to
their absence:

| Gap | Consequence today |
|---|---|
| No `question_archetype` on a question | An item may be tested nine times by the same mould and read as fully assessed |
| No link from a learning outcome to the question that assesses it | An item may declare 5 outcomes and test 1; nothing measures the difference |
| No measure of revision cost | Content may grow without limit; "more" would always read as "better" |

A metric that cannot see a defect cannot report it. Adding content before
adding the measure would produce a larger corpus with the same blind spots.

## 2. What the data says now

Measured on `9b99c99`.

### 2.1 Outcomes versus questions

- 163 atomic official items, **603** declared learning outcomes, **550**
  questions.
- **73 of 163 items (44.8%) carry fewer questions than declared outcomes.**

Fewer questions than outcomes does not prove an outcome is unassessed — one
question may assess two outcomes — but the converse holds: with `q < o`, full
one-to-one outcome coverage is arithmetically impossible. It is a *necessary*
condition, and it fails for 73 items.

Per lot (items under-assessed / items in lot):

| Lot | | Lot | | Lot | |
|---|---|---|---|---|---|
| lot-01 | **0/9** | lot-10 | 5/12 | lot-19 | 0/1 |
| lot-02 | 4/10 | lot-11 | 4/7 | lot-20 | 0/2 |
| lot-03 | 10/15 | lot-12 | 0/9 | lot-21 | 1/2 |
| lot-04 | 9/14 | lot-13 | 0/9 | lot-22 | 2/2 |
| lot-05 | 6/12 | lot-14 | 0/3 | lot-23 | 1/1 |
| lot-06 | 12/14 | lot-15 | 0/2 | lot-24 | 1/1 |
| lot-07 | 12/13 | lot-16 | 0/1 | lot-25 | 1/1 |
| lot-08 | 1/8 | lot-17 | 0/1 | lot-26 | 1/1 |
| lot-09 | 3/12 | lot-18 | 0/1 | | |

Lot 01 — the one lot recorded as refined — is the only large lot at 0. That is
consistent with the refinement log, and it is the first independent
corroboration the Readiness figure has had.

### 2.2 Question archetypes

`question_archetype` appears **0 times in 550 questions** and is absent from
the schema, the domain object, the loader, the rules and the payloads. The
bank's only structural axis is `type`, which holds the single value `mcq` for
all 550 — an axis with one value distinguishes nothing.

The two axes that do vary are pedagogical, not structural:

- `cognitive_level`: APPLY 186, KNOW 135, UNDERSTAND 229
- `exam_skill`: DIAGNOSE 252, DISTINGUISH 151, RECOGNIZE 147

Neither says what *form* the question takes, so nothing prevents an item's
whole assessment being, say, four version-attribution questions in a row.

### 2.3 Revision cost

Course body words (front matter excluded), by content level:

| Level | items | min | median | p90 | max | total |
|---|---|---|---|---|---|---|
| MINIMAL | 27 | 184 | 246 | 319 | 450 | 6 903 |
| STANDARD | 125 | 238 | 395 | 543 | 880 | 52 098 |
| DEEP | 11 | 538 | 584 | 646 | 677 | 6 476 |

Corpus total: **65 477 body words** ≈ 4.4 h of reading at 250 words/minute.

**This measurement is honest about its own weakness**: revision cost is not a
problem today, and a ceiling installed now will flag almost nothing. Its value
is prospective — the refinement work about to begin *adds* prose (scenarios,
"why", targeted confusions), and a budget is only a budget if it exists before
the spending. This is recorded here so that a later reader does not mistake a
green REV-001 for evidence that revision load was ever audited under pressure.

## 3. Blast radius of each proposed change

### 3.1 `question_archetype` on the question

| Layer | Touched | Note |
|---|---|---|
| `src/Domain/Question.php` | yes | one nullable property |
| `src/Domain/QuestionLoader.php` | yes | one hydration line |
| `new Question(` call sites | **2** | the loader and `tests/Support/QuestionFactory.php` |
| `src/Build/PayloadBuilder.php` | no | payload shape unchanged |
| `website/src/**` | no | consumes `cognitive_level` / `exam_skill` only |
| Schema version | no | additive optional field |

### 3.2 Identity for learning outcomes

`EntityType::LearningOutcome` and the prefix `OUT` **already exist** in
`src/Support/EntityType.php`. Outcomes were modelled as identified entities
from the start and then stored as bare strings; giving them their minted ids
completes the declared design rather than introducing one.

| Layer | Touched | Note |
|---|---|---|
| `src/Domain/OfficialItem.php` | yes | `list<string>` → `list<LearningOutcome>` |
| `src/Domain/MatrixLoader.php` | yes | accepts a string *or* `{id, outcome}` |
| `src/Validation/Rule/LearningOutcomeRule.php` | no | tests emptiness only |
| `src/Build/PayloadBuilder.php` | yes | exports `->text`; **JSON shape unchanged** |
| `src/Build/DocsGenerator.php` | yes | one line |
| `website/src/**` | **no** | `learning_outcomes: string[]` still holds |
| `tests/**` | 3 files | factory, `CanonicalDataTest`, `Mock4PayloadTest` |
| `tools/fr2/*`, `tools/audit/fr2_second_audit.py` | yes | read the field's text |
| Schema version | no | see §4 |

### 3.3 Revision budget

Reads `content/courses/*.md` and the matrix. Touches no schema, no payload and
no consumer.

## 4. Why the schema version is not bumped

`MigrationRunner` is registered with **no migrations**. A bump of
`syllabus-matrix` from 1 to 2 would therefore require a migration whose job is
to *invent identifiers at load time* — random per load, or derived from the
outcome text. ADR-0002 forbids both: an id is minted once and recorded, never
derived and never regenerated.

The minimal correct change is therefore a **tolerant loader plus a rule that
bites where refinement is claimed**:

- `MatrixLoader` accepts both the bare string and the `{id, outcome}` mapping.
- `PED-003` requires the mapping form for every item in a lot recorded as
  refined in `docs/progress/refinement-log.yml`.
- The same staging applies to `question_archetype` (`ARC-001`).

No existing file becomes invalid, no green build is obtained by weakening a
check, and each lot's refinement pass carries its own ids in. When all 27 lots
are recorded as refined, the tolerance is removed and the schema bumped in one
deliberate act — recorded in ADR-0007 as the exit condition, so the tolerance
cannot quietly become permanent.

## 5. Vacuity risk, stated in advance

Four of the five vacuous checks found in this project so far compared a value
with itself. Each new rule is therefore paired with a hostile fixture in
`tools/audit/prove_framework_rules_fail.py`, which mutates canonical data,
asserts the rule fires, and restores the file **byte-identically with a
SHA-256 comparison**. A rule that cannot be made to fail is reported as
`VACUOUS`, not as `PASS`.

`REV-001` is the honest exception: it flags **1 item of 163** today
(`Handling legacy deprecated code`, lot-13, 450 body words). It is installed as
a ratchet against growth, and this document says so rather than letting a green
result imply a stress test that never happened.
