# First exposure and parallel forms

Remediation P3.2 asks for « formes parallèles d'évaluation et règles de première
exposition explicites ». This page states them. Each rule names what enforces
it; a rule nothing enforces says so.

## Definitions

- **Exposure** — the learner answers a question, in any mode. In a corrected
  mode (Practice, comprehension checks, a mock's correction) they also see its
  key and explanation.
- **First exposure** — the first answer given to a question **as it stands**:
  a question rewritten under a new version (P3.3, `question-versions.json`) is
  a new question. `website/src/lib/exposure.ts` (`isUnseen`) is the one
  definition the site uses.
- **Re-exposure** — any later answer. A right answer on a re-exposure says as
  much about the memory of that question, and of its correction, as about the
  notion.
- **Parallel forms** — two assessments built to measure the same thing with
  different questions.

## Where each pool is met first

| Pool | Met first in | Never in | Enforced by |
|---|---|---|---|
| `LEARNING` | Practice Mode (also from a course, `?item=`), the « série inédite » and the parallel replay, exams 1 to 6 and drawn exams, Mock 5 | Exam Mode, Mocks 1 to 3, Mock 4 | `Pool::isExposedInPracticeMode()`; `PayloadBuilder::assertPoolsAllowed()` at build, on every payload below |
| `VALIDATION` | Exam Mode, Mocks 1 to 3, exams 1 to 6 and drawn exams, Mock 5 | Practice Mode | the same; `FirstExposureRulesTest` |
| `HOLDOUT` | Mock 4, its first sitting | every other payload | `assertNoHoldoutLeak()`, `assertMockMatchesBlueprint()` (the whole holdout, nothing else), `assertPoolsAllowed()`, rule `POOL-001` |
| `COMPREHENSION` | the lot's end-of-lot check | every exam payload | `assertComprehensionIsolated()`, `assertPoolsAllowed()` (a comprehension id is refused in an exam payload); rule `CMP-002` refuses a prompt 60 % similar to any exam question |

`assertPoolsAllowed()` reads each question. Before it, Practice Mode's
guarantee was a filter and a `pool` label that `PracticePayloadTest` asserted:
making `isExposedInPracticeMode()` accept `VALIDATION` too left that test green
and fails `FirstExposureRulesTest` (proved 2026-10-17, file restored under
SHA-256). The four injections of `FirstExposureRulesTest` — `VALIDATION` in
Practice, `LEARNING` in Exam Mode, `HOLDOUT` in the exam series (refused
without printing its id), a comprehension question in Mock 1 — run on every
`phpunit`.

One consequence is deliberate and stated on `/examens`: the exam series and
Mock 5 serve `LEARNING` questions too: the 141 English `VALIDATION` questions
cannot fill six disjoint 75-question exams. A score there is
training, not an independent measure.

## Which figures read first exposures

| Figure | What it counts | First exposure? |
|---|---|---|
| « Série inédite » (`/practice?serie=inedite`) | ten `LEARNING` questions never answered in their current version, dealt lot by lot so that two consecutive questions share a lot only when no other lot has one left; nothing before the answer names the lot, the item or the topic | yes, by construction |
| Parallel replay (`/practice?serie=parallele`) | for each weak point (a question whose latest answer was wrong, in any mode), one never-answered `LEARNING` question on the same learning outcome, or on the same item when the bank has none; never the missed question | yes, by construction — and on the notion missed, not on the question |
| Drawn exams 7 and up | the questions met least often first; the page says how many were already met | partly, and said |
| « Ma progression », per channel | the first and the latest answer to each question **within a channel** | not always: a `LEARNING` question met first in an exam series and later in Practice has a Practice « first answer » that is a re-exposure. The table keeps channels apart by design and says what each is; it does not claim first exposures |
| Delayed checks (`/controle-differe`) | comprehension questions asked again 7, then 14, then 28 days after the last answer on the lot | no, by design: they measure what remains of a question met before |
| Mock 4, first sitting | the holdout | yes, and the only bank kept unseen by the content itself |

## Parallel forms in this project

| Forms | Same thing measured | Disjoint | Equal difficulty | Enforced by |
|---|---|---|---|---|
| A missed question and its parallel replay | the same learning outcome (or, failing that, the same item) | yes | not measured | `parallelReplay()`, `verify-exposure.mjs` |
| `LEARNING` and `VALIDATION` questions of a `PARALLEL` pair | the same knowledge, in another situation | yes | not measured | `semantic_repetition.py` refuses a `REPEAT` — the same situation and conclusion — so practising one does not hand over the key of the other |
| Comprehension and exam questions | the item's outcomes | yes | not measured | `CMP-002` |
| Drawn exams 7 and up | the official format and topic spread | no: drawn from the pool of exams 1 to 6, least-met questions first | not measured | `drawExam()`, `verify-exams.mjs` |

**Not parallel forms:** exams 1 to 6 share format and topic spread and no
question, but rise in estimated difficulty on purpose (`assertExamSeries()`
fails if one is easier than the one before). They are graded forms: a better
score on exam 4 than on exam 2 is not progress measured on equal terms.
Mocks 1 to 3 draw on different filters (skills, `DIAGNOSE`, `hard`) for
different purposes.

**Not claimed.** No form here is equated. Difficulty is the author's estimate
(P3.1), and item statistics — the data that would show two forms of equal
difficulty — are `MISSING` until learners' answers are collected with
consent. « Parallel » means built to measure the same thing, nothing more.

## Questions that cross components

The « série inédite » mixes components across questions. Some questions also
mix them inside one situation: the rule that decides the answer belongs to
one component, the trap to another. The exam-bank schema anchors a question on
one atomic item (`related_items` exists only on comprehension questions,
ADR-0010), so such a question is anchored on the item whose rule decides, and
tagged `integration`. Each is executed (`tools/behavior/single/integration-p32.php`):

| Question | Components | Deciding rule (item) |
|---|---|---|
| `QST-xfq1w6p4khfq` | HttpKernel (`#[MapRequestPayload]`), Serializer, Validator | collected denormalization errors become violations, so 422 (Serializer) |
| `QST-ayq8xg4be7j8` | EventDispatcher, HttpKernel, Routing | a `kernel.request` listener above priority 32 runs before `RouterListener` (EventDispatcher) |

A third, Twig's `trans` filter at `fr_CA` falling back to `en`, is written and
executed but held back: with it, the revision plan no longer fits the four
mocks before the exam (see the tracker, P3.2 second part).

## What remains outside these rules

- Measured transfer: whether a right answer on a parallel form predicts the
  exam needs answers from more than one learner.
