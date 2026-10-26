# ADR-0011 — The page preamble is outside the revision budget

**Status:** Accepted
**Date:** 2026-10-24
**Supersedes:** nothing. **Amends:** [ADR-0007](0007-refinement-framework-v2.md),
[ADR-0008](0008-revision-budget-recalibration.md) and
[ADR-0009](0009-mermaid-diagrams-outside-the-revision-budget.md), whose `REV-001`
measure this changes. **Relates to:** `docs/policy/revision-budget.md`,
`docs/policy/course-structure.md`

## Context

On 2026-10-24 a correction to *HTTP Caching* (lot 17) took the course to 951
words against its `STANDARD` budget of 900. It shipped at 898, after trimming
its own scope paragraph. The owner's instruction that day, verbatim:

> « Fait aussi raffiner les cours , ninclut pas ,Objectif, Limites de
> périmètre,Justification du niveau, Objectifs d'apprentissage,et le tableau ne
> les fait pas inclure dans budget mits dans toutes les cours »

The five elements it names are, read upward from where the course begins, the
whole preamble of an item page:

| On the page | Written by | Counted by `REV-001` before this ADR |
|---|---|---|
| the table (sujet, niveau, classification, statut, version) | `DocsGenerator::itemPage()`, from the matrix | no |
| « Objectifs d'apprentissage » | the generator, from the item's `learning_outcomes` | no |
| « Justification du niveau » | the generator, from `content_level_justification` | no |
| « Limites de périmètre » | the generator, from `exclusion_boundaries` | no |
| « Objectif » | the course file, first section of its body | **yes** |

« Le tableau » is read as the table at the top of the page — the only table
of the preamble. Tables inside a course body teach, and stay counted.

`REV-001` counted `Course::wordCount()`, which reads the course file only. Four
of the five were therefore never in the budget, and stay out by construction:
they are not in `Course::$body`. The fifth was. Measured on 2026-10-24 over the
163 courses: 4 094 tokens, heading included — 23 per course at the median. The
prose under the heading: a median of 21 words, a p95 of 39, a maximum of 75.

## Decision

**1. The `## Objectif` section is not counted against the budget.** It runs
from its heading to the next `## ` heading. One pattern defines it,
`Course::OBJECTIVE_SECTION`, mirrored verbatim in `tools/audit/collect.py`.
`Course::budgetWordCount()` is body words without it; `REV-001` and readiness
criterion `R14` read that measure.

**2. Body words, as a size, are unchanged.** `Course::wordCount()` — the unit
CLAUDE.md names for course size, and the input to the revision roadmap — still
counts the objective. The difference from ADR-0009 is deliberate. A diagram's
source is not prose read at the roadmap's 110 words a minute, so it left both
measures. An objective is read like any sentence: taking it out of the
roadmap would shorten the plan by about 37 minutes per full pass for time the
learner still spends. The budget is a ceiling on what a page teaches; the
roadmap estimates what reading it costs.

**3. A new mandatory rule, `OBJ-001`.** An exemption that accepted any text
would be a place to put the teaching the budget refuses. Each course has at
most one `## Objectif`, of at most 60 words — three sentences of twenty — in
prose: no fenced block, table, list or sub-heading.

One course was above the ceiling. *Clock* (lot 19) carried, under its
objective, a paragraph on the problem the component solves. That is teaching,
and it maps to the outcome « pourquoi le component existe »: it moved to the
start of « Injecter la clock », the section that outcome points to, where the
budget counts it.

## Consequences

- Every course gains its objective's tokens of room: 23 at the median, 57 at
  most, 4 051 over the corpus once *Clock*'s paragraph moved. Budget words
  over the corpus: 109 882, against 113 933 body words — the same figures from
  PHP and from the Python mirror. For *HTTP Caching*, 898 counted words become
  863. *Clock* counts 857: its objective went from 75 words to 34, and the 45
  of teaching now sit in the body.
- `AUD-04` reports the objective tokens beside the Mermaid tokens, so the size
  of each exemption stays visible.
- `tools/audit/prove_framework_rules_fail.py` injects three `OBJ-001` defects —
  an objective past 60 words, code inside it, a second objective — into a real
  course, and asserts each fires. Its `REV-001` case beside the diagrams of
  *Request handling* now computes its margin, so it still adds exactly one word
  over the ceiling once the objective has left the count. Unit tests pin that
  words inside the objective are not counted, that words after it are, and
  that « Objectifs d'apprentissage » is not mistaken for it.
- The revision roadmap is unchanged: it counts body words. Only *Clock* moves,
  889 to 891 words, because its moved paragraph now names its subject
  (« le problème que le component résout »).
- A heading the generator writes — « Objectifs d'apprentissage »,
  « Justification du niveau », « Limites de périmètre » — written by hand in a
  course body would be counted: the exemption is the generated preamble's and
  the objective's, not a heading's.
