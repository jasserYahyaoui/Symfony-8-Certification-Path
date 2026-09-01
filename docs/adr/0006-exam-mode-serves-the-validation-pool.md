# ADR-0006 — Exam Mode serves the VALIDATION pool

**Status:** Accepted
**Date:** 2026-09-01
**Supersedes:** nothing. **Relates to:** [ADR-0005](0005-holdout-distribution-deferred.md)

## Context

Master Plan §7.3 defines three question pools: `LEARNING`, `VALIDATION`,
`HOLDOUT`. Five lots shipped with `VALIDATION` empty, and the emptiness was
reported each time as "the oldest open structural gap" without anyone asking
what the pool was *for*.

Two facts, read from the code rather than assumed, show why that mattered.

**Exam Mode was serving the holdout.** `PayloadBuilder::examPayload()` defaults
to `Pool::Holdout`, and `website/src/pages/exam.tsx` loads `exam.json`. So every
practice exam a learner sat consumed the pool §22 reserves for a *protected
unseen* final assessment. The holdout was being spent continuously, by design,
from the first lot.

**The minimum-evidence text was unsatisfiable.** Every `STANDARD` and `DEEP`
item in the matrix requires evidence "réussies sur 2 sessions distinctes, **dont
une en mode examen**". Meeting that honestly means sitting an exam-mode question
on that item — which, with an empty `VALIDATION` pool, could only be a holdout
question. The requirement and the reservation contradicted each other.

## Decision

`VALIDATION` is the **exam-mode bank used during study**.

| Pool | Practice Mode | Exam Mode (study) | Final mocks |
|---|---|---|---|
| `LEARNING` | yes | no | no |
| `VALIDATION` | **no** | **yes** | no |
| `HOLDOUT` | no | **no** | yes |

Concretely:

- `exam.json`, the payload the Exam page loads, is built from the `VALIDATION`
  pool. The filename is unchanged; its contents are not.
- The `HOLDOUT` pool is **not deployed at all**. No published payload contains a
  holdout question, and rule `POOL-001` now asserts that across every payload
  rather than only the practice one.
- Every `STANDARD` or `DEEP` item that is `EXAM_READY` must have at least one
  `VALIDATION` question, so that its stated minimum evidence is actually
  attainable. Rule `POOL-002` enforces this.
- `MINIMAL` items are exempt: their minimum evidence does not require exam mode.

## Consequences

**What improves.** The deployed application no longer hands out holdout
questions and answers. Exam Mode becomes repeatable during study without
degrading the final assessment. The minimum-evidence text becomes satisfiable.

**What does not improve, and must not be claimed.** The repository is
**public**. Holdout questions live in `content/questions/*.yml`, so their
answers remain readable by anyone who opens the repository, exactly as before.
Removing them from the deployed payload narrows the exposure from "served by the
application" to "readable in the source" — it does not create confidentiality.

ADR-0005 therefore stays **open and unchanged in substance**: a genuinely unseen
final assessment still requires a distribution channel that is not this public
repository. Its deadline — a decision before Lot 27 — stands. What changes is
only the accuracy of the risk statement.

## Alternatives rejected

- **Leave Exam Mode on the holdout and fill `VALIDATION` for some other use.**
  Rejected: it keeps spending the reserved pool and leaves §22 unachievable for
  a second reason.
- **Drop the `VALIDATION` pool and relabel the holdout.** Rejected: it would
  make §22's protected assessment unreachable by construction, which is a
  scope decision for the project owner, not a maintenance choice.
- **Keep publishing the holdout payload under a different name.** Rejected: an
  unused public payload of reserved answers has cost and no benefit.
