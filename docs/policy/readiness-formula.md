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

`R3` deserves a note: the required modes are the item's **own** declaration in
the matrix, not a shape imposed from outside. An item that declares
`[QUESTION]` is not marked short for having no flashcard, because §6 treats a
card for a fact already retained through application as pure revision cost.

## The automated criteria alone are not the measure, and here is the proof

At the time this was written, **all 163 items already met every automated
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

The dashboard carries no "generated at" timestamp on purpose: a clock in a
file CI diffs would fail the build the next day for no reason. The date shown is
the last refinement **recorded**, which is canonical and moves only when the
work does.
