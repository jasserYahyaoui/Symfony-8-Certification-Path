# Syllabus matrix field guide

Every item in `docs/syllabus/syllabus-matrix.yml` requires all fields of Master
Plan §3.3. The loader (`src/Domain/MatrixLoader.php`) rejects a missing field
rather than defaulting it: a silently defaulted `classification` or
`content_level` would distort both coverage and scoring.

## Template

```yaml
- id: OIT-xxxxxxxxxxxx          # bin/cert id:mint OfficialItem — never derived from a slug (§11)
  official_topic_order: 1
  official_topic: "…"           # verbatim from the syllabus
  official_item_order: 1
  official_item: "…"            # verbatim
  official_wording: "…"         # verbatim; changing it is a syllabus refresh (SYL-002)
  learning_domain: php | http | symfony | twig
  lot: lot-01
  chapter: null
  classification: OFFICIAL      # OFFICIAL | PREREQUISITE | ENRICHMENT | OUT_OF_SCOPE
  content_level: STANDARD       # MINIMAL | STANDARD | DEEP
  content_level_justification: "…"
  learning_outcomes: ["…"]
  required_assessment_modes: [QUESTION]
  minimum_evidence: "…"
  exclusion_boundaries: "…"
  version_constraints: "Symfony 8.0"
  official_sources:
    - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/routing.rst"
      anchor: "…"
      repository: "symfony/symfony-docs"
      branch: "8.0"
      commit_sha: "…"
      verified_at: "2026-08-31"
      verified_by: "…"
  course_refs: []
  flashcard_refs: []
  question_refs: []
  exercise_refs: []
  exam_refs: []
  prerequisites: []
  status: NOT_STARTED
  verification_status: UNVERIFIED
  exam_ready: false
  last_verified_at: null
  reviewed_by: null
  notes: null
```

## The fields that carry the most weight

**`classification`** decides whether the item is part of the coverage
denominator. Only `OFFICIAL` counts (§3.5). `PREREQUISITE` is teachable and
scorable but not counted; `ENRICHMENT` is optional, unscored and capped below
10% (`SCOPE-002`); `OUT_OF_SCOPE` may never be scored.

**`content_level`** must be justified, and `DEEP` must never be the default
(§4.1). `PED-001` rejects a `DEEP` justification shorter than 40 characters —
not because length is quality, but because a one-word justification is
demonstrably not an argument.

### Level distribution is an outcome, never a target

The proportion of `MINIMAL` / `STANDARD` / `DEEP` across the project is a
**measurement, not a goal**. There is no target ratio, no expected shape, and
no minimum number of `DEEP` items — for the project or for any single lot.

This cuts both ways, and both failures are real:

- **Inflation.** Reaching for `DEEP` to make a lot look thorough. §4.1 names
  this one directly: `DEEP` must never be the default.
- **Quota-filling.** Promoting an item to `DEEP` because a lot has none yet,
  or because the running percentage looks low. This is the subtler failure,
  because it produces content that passes every automated check while costing
  the learner revision time it never earns back.

A lot with **zero `DEEP` items is a valid, complete lot** — Lot 01 (PHP) is
one. A project that ends at 60% `MINIMAL`, 35% `STANDARD`, 5% `DEEP` is a
correct outcome if that is what the items warrant.

The only admissible reason for a level is the item itself: how complex it
genuinely is, how often it is confused in practice, and what capability
(`KNOW`, `RECOGNIZE`, `DISTINGUISH`, `APPLY`, `DIAGNOSE`) a learner must
actually reach to answer an official-scope question about it.

When reporting, state the distribution as an observation and never as progress
toward a shape. The figure worth watching is the **revision burden**, which is
what the learner actually pays.

**`status`** and **`exam_ready`** must agree. `RDY-001` rejects an item whose
`exam_ready: true` contradicts its lifecycle status, and rejects any
`EXAM_READY` claim lacking sources, teaching content, an assessment, declared
minimum evidence or a verification date. Coverage counts an item only when the
flag, the status *and* the verification status all agree — so flipping a
boolean cannot inflate the percentage.

## Lifecycle

```text
NOT_STARTED → RESEARCHED → SPECIFIED → IMPLEMENTED
            → SOURCE_VERIFIED → ASSESSMENT_VERIFIED → TESTED → EXAM_READY
```

`PED-002` starts enforcing teaching content and assessments at `IMPLEMENTED`;
`SRC-001` starts enforcing anchored sources at `SOURCE_VERIFIED`. An item at
`RESEARCHED` is legitimately still empty.
