# Certification Readiness — the formula

**Regenerate the figure with `php bin/cert readiness`. It is computed from
canonical data on every run, and it is never written by hand.**

## What it is not

It is **not** Official Coverage. Coverage answers *"is every atomic official
item covered?"* and has exactly one formula (§3.5). This answers a different
question:

> **Has the content been refined to the point where a candidate could answer a
> question on this item that they have never seen?**

A corpus can be 100% covered and largely unprepared. Reporting one number as if
it were the other is the single most misleading thing this project could do, so
the two are computed separately, displayed side by side, and never merged.

It is also **not** a prediction of exam success, and nothing here should be read
as one. It measures the state of the material, not the state of the candidate.

## What it counts

$$
\text{Certification Readiness} = \frac{\text{items that are REFINED or MASTERED\_READY}}{163 \text{ atomic official items}} \times 100
$$

The denominator is the same 163 atomic official items coverage uses. It is never
lots, files, lines, questions or words — those measure volume, and volume is not
the thing being measured.

## The four statuses

| Status | Meaning |
|---|---|
| `NOT_REFINED` | fails at least one criterion its content level requires |
| `PARTIALLY_REFINED` | meets every automated criterion; its lot has had no refinement audit |
| `REFINED` | meets every criterion **and** sits in a lot whose refinement audit is recorded |
| `MASTERED_READY` | `REFINED`, and assessable in exam mode at the top of what its level requires: a `VALIDATION` question, a `DIAGNOSE` question and a `hard` question |

Only the last two count.

## The criteria are concept-aware

An item is asked only for what its **content level** requires. A `MINIMAL` item
is not penalised for lacking a diagnosis question it has no need of, and a
`DEEP` item is not called ready on a definition alone.

| Criterion | MINIMAL | STANDARD | DEEP |
|---|:---:|:---:|:---:|
| `R1_course` — a course exists | ✔ | ✔ | ✔ |
| `R2_two_questions` — at least two questions | ✔ | ✔ | ✔ |
| `R3_declared_modes` — every mode the item declares for itself is satisfied | ✔ | ✔ | ✔ |
| `R4_anchored_sources` — every source is version-anchored and carries an anchor | ✔ | ✔ | ✔ |
| `R5_beyond_recall` — a question at `UNDERSTAND` or `APPLY` | | ✔ | ✔ |
| `R6_distinguishes` — a question at `DISTINGUISH` or `DIAGNOSE` | | ✔ | ✔ |
| `R7_exam_mode` — a `VALIDATION` question, so it can be sat in exam mode | | ✔ | ✔ |
| `R8_diagnoses` — a `DIAGNOSE` question | | | ✔ |
| `R9_hard_question` — a `hard` question | | | ✔ |
| `R10_outcomes_identified` — every learning outcome carries a minted `OUT` id | ✔ | ✔ | ✔ |
| `R11_outcomes_assessed` — every outcome is named by at least one non-`HOLDOUT` question | ✔ | ✔ | ✔ |
| `R12_archetypes_declared` — every question declares a structural archetype | ✔ | ✔ | ✔ |
| `R13_archetype_variety` — the questions use at least two distinct archetypes | | ✔ | ✔ |
| `R14_revision_budget` — the course stays inside the budget for its level | ✔ | ✔ | ✔ |

`R11` excludes `HOLDOUT` on purpose. That pool reaches exactly one payload,
`mock-4.json`, sat once and unseen (ADR-0005, ADR-0006), so an outcome named
only there is one the learner can never practise — and the item's own
`minimum_evidence`, which asks for a success in exam mode, could not be produced
for it. Three Lot 01 outcomes were in exactly that position when the link was
first written.

`R10` to `R14` were added by refinement framework version 2
([ADR-0007](../adr/0007-refinement-framework-v2.md)); the section below records
what raising the bar cost. `R13` is concept-aware for the same reason as `R5`
to `R7`: a `MINIMAL` item asked for recognition and nothing more is not
penalised for assessing it twice the same way.

`R3` deserves a note: the required modes are the item's **own** declaration in
the matrix, not a shape imposed from outside. An item that declares
`[QUESTION]` is not marked short for having no flashcard, because §6 treats a
card for a fact already retained through application as pure revision cost.

## The automated criteria alone are not the measure, and here is the proof

When this metric first shipped, **all 163 items already met every automated
criterion.** A metric built on them alone would have read **100%** — which is
exactly the coverage-is-readiness confusion it exists to prevent.

What moves the figure is the second half: the item's lot must have passed an
**expert refinement audit**, recorded in
[`docs/progress/refinement-log.yml`](../progress/refinement-log.yml). That audit
does what no script here can:

- it reads every item of the lot, not only the weak ones;
- it checks that **every trap the courses teach is verified by an answer key**,
  by reading each one;
- it records the `DO_NOT_ADD` decisions, so what was refused is auditable too.

**That trap check is deliberately not automated.** A keyword-overlap detector was
written for it and flagged nine candidates in Lot 01; **four were false
positives**, disproved by reading the questions. A heuristic that wrong cannot be
allowed to move a percentage, so it is a human audit or it is nothing.

## Raising the bar lowers the number, and that is the point

Framework version 2 added `R10` to `R14`. The corpus did not change; what
"refined" means did. The published figure moved accordingly:

| | before ADR-0007 | after |
|---|---|---|
| Official Coverage | 100% | 100% |
| Certification Readiness | 5.5% (9/163) | **0% (0/163)** |
| Lots refined | 1/27 | 0/27 |

Lot 01 keeps its refinement record — the audit happened, and it found four exam
traps no question verified — but it is shown as *audited under framework v1*
and is not credited with structures it does not carry. `framework_version` in
the refinement log is what makes that distinction expressible instead of
forcing a choice between deleting real evidence and inflating a number.

A metric that only ever rises measures effort, not readiness.

## Why a lot can be removed

Deleting a lot's row from the refinement log lowers Readiness. That is intended.
The metric measures **evidence that currently exists**, not history: if a
refinement is withdrawn or a lot's corpus changes underneath it, the figure must
fall rather than remember a better past.

## Reproducing the number

```bash
php bin/cert readiness
```

It prints Coverage, Readiness, the lot count, and every item falling short of a
criterion with the criterion named. CI regenerates
`docs/progress/certification-readiness.md` and fails on any diff, so the
published dashboard cannot drift from the data.

## Proving the published figure is the computed one

CI regenerates `docs/progress/certification-readiness.md` and fails on any
diff, so the repository dashboard cannot drift from the canonical data. That
leaves one link unproved: what the *deployed site* serves.

A `200` on `data/readiness.json` says the file was published. It says nothing
about the number inside it — and a stale artifact serving a better-looking
older figure would have passed every check that existed before ADR-0007 moved
the number from 5.5% to 0%.

The production smoke test therefore fetches the deployed payload and compares
it against the repository dashboard, field by field
(`.github/scripts/readiness-smoke.py`). The two are produced independently —
one by `ReadinessMarkdownRenderer`, one by `PayloadBuilder` — so the comparison
is between two artifacts, never a value with itself. The chain is:

```text
canonical data → certification-readiness.md   (CI fails on any diff)
               → readiness.json               (same calculator, different renderer)
               → the deployed payload         (production smoke test)
```

The dashboard carries no "generated at" timestamp on purpose: a clock in a
file CI diffs would fail the build the next day for no reason. The date shown is
the last refinement **recorded**, which is canonical and moves only when the
work does.
