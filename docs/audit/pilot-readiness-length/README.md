# Pilot unit — readiness 9/163 and the answer-length cue

**READ-ONLY.** No course, no question, no status, no `framework_version` was
modified. The only files added are an audit script and this report.

**Base**: `1150b43` · **Date**: 2026-09-10

---

# Part 1 — why readiness is 9/163 when coverage is 163/163

## The two definitions, from the code

**Coverage** — `OfficialItem::isCovered()`:

```php
$this->classification->countsTowardCoverage()
    && $this->examReady
    && $this->status->isExamReady()
    && $this->verificationStatus->mayBeScored();
```

Four flags on the item itself. It asks *"is this item declared finished and
verified?"* — 163/163 = 100%.

**Readiness** — `ReadinessCalculator` — counts an item only when it is
`REFINED` or `MASTERED_READY`, which requires **every** criterion its content
level demands to be met **and** its lot to be recorded as refined under the
current framework. It asks a different question: *"could a candidate answer an
unseen question on this item?"*

## The fourteen criteria, and which ones actually fail

| Criterion | Levels | Items failing |
|---|---|---|
| `R1_course` · a course exists | all | **0** |
| `R2_two_questions` | all | **0** |
| `R3_declared_modes` · the item's own declared modes are satisfied | all | **0** |
| `R4_anchored_sources` | all | **0** |
| `R5_beyond_recall` | STANDARD, DEEP | **0** |
| `R6_distinguishes` | STANDARD, DEEP | **0** |
| `R7_exam_mode` · a VALIDATION question | STANDARD, DEEP | **0** |
| `R8_diagnoses` | DEEP | **0** |
| `R9_hard_question` | DEEP | **0** |
| `R10_outcomes_identified` · outcomes carry minted `OUT` ids | all | **154** |
| `R11_outcomes_assessed` · each outcome named by a non-HOLDOUT question | all | **154** |
| `R12_archetypes_declared` · every question declares an archetype | all | **154** |
| `R13_archetype_variety` · ≥ 2 distinct archetypes | STANDARD, DEEP | **127** |
| `R14_revision_budget` | all | **1** |

**Criteria R1 to R9 — everything the project asked of an item before
2026-09-09 — fail for zero items.** Every one of the 163 has a course, at least
two questions, its declared assessment modes satisfied, anchored sources, and
where its level requires it: beyond-recall, distinguishing and exam-mode
evidence, plus diagnosis and a hard question at DEEP.

## Why exactly 9 pass, and exactly 154 fail

The 9 are the nine items of **lot-01**, the only lot recorded at
`framework_version: 2`. All nine reach `MASTERED_READY`.

The 154 are every other item, and the split matters:

| | Items |
|---|---|
| Failing **only** `R10`–`R13` — the framework-v2 markers | **153** |
| Failing something else as well | **1** |

The one is `Handling legacy deprecated code` (lot-13), on `R14_revision_budget`:
450 body words against a `MINIMAL` budget of 400. Even that is not a
pedagogical error — it is either 50 words too many or a level that should be
`STANDARD`.

**So the honest reading of 9/163 is this: it measures migration progress, not
pedagogical quality.** 153 items are complete against every criterion the
project defined before ADR-0007, and fail only for lacking markers introduced
on 2026-09-09. Presenting them as "not ready to teach" would be false.

## Three distinct categories, not one

The 154 must not be treated alike.

| Category | Items | What it means |
|---|---|---|
| **Not yet migrated** | 50 | The item has at least as many non-HOLDOUT questions as declared outcomes. Minting `OUT` ids, writing the `assesses_outcomes` links and declaring archetypes is *plausibly* enough. No new content is implied. |
| **Possibly under-assessed** | 104 | Fewer non-HOLDOUT questions than declared outcomes, so a one-to-one mapping is arithmetically impossible. |
| **Actually defective** | 1 | `R14` on the lot-13 item. |

**The 104 is an upper bound, not a proven deficit.** `R11` requires each outcome
to be named by at least one question — it does not require one question per
outcome, and one question may legitimately name two. Whether that is true here
cannot be decided by counting; it needs the outcomes read against the questions.

Sizing it: outside lot-01 there are **575 outcomes and 443 non-HOLDOUT
questions**, an arithmetic shortfall of **132 questions** if every question
names exactly one outcome. In lot-01 — the only migrated evidence available —
**0 of 41 questions name more than one outcome**, which suggests the real
figure is closer to 132 than to 0, but one lot is not a corpus.

## Per rule, as requested

| Rule | Objective | Items affected | Blocking reason | Content actually wrong? | Minimum remediation |
|---|---|---|---|---|---|
| `R10` | outcomes are addressable | 154 | outcomes are bare strings | **No** | mint one `OUT` id per outcome |
| `R11` | every promise is tested | 154 | no ids ⇒ no question can name one | **Unknown for 104, no for 50** | write `assesses_outcomes`; add questions only where reading proves an outcome untested |
| `R12` | the question's shape is recorded | 154 | field absent | **No** | declare `question_archetype`, verified against the question |
| `R13` | an item is not tested by one mould | 127 | consequence of `R12` | **No** | achievable everywhere: 0 STANDARD/DEEP item has fewer than 2 non-HOLDOUT questions |
| `R14` | revision stays affordable | 1 | 450 words vs 400 | **Borderline** | trim ~50 words, or justify `STANDARD` |

---

# Part 2 — the answer-length cue, question by question

`tools/audit/aud11_length_cue_triage.py`, read-only.

## Classification of all 555 questions

| Class | Questions | |
|---|---|---|
| `NO_BIAS` | **354** | 63.8% |
| `JUSTIFIED_LENGTH` | **12** | 2.2% |
| `ARTIFICIAL_DISTRACTOR_RISK` | **16** | 2.9% |
| `REVIEW_REQUIRED` | **173** | 31.2% |
| `CONFIRMED_LENGTH_CUE` | **0** | never assigned by a script |

`CONFIRMED_LENGTH_CUE` is deliberately unreachable by the classifier. It is the
verdict of someone who has read the question and its distractors.

So the headline figure changes: **AUD-10's 50.5% is the rate at which the
correct answer happens to be longest. 31.2% is the rate at which that could
plausibly be exploited**, and the confirmed rate is a subset of *that*, not yet
established.

## A defect in this classifier, found by reading

The first implementation compared **means** for multiple-answer questions.
`QST-f2m90kz2ya28` has its two correct answers at **125 and 12 characters** —
the longest choice and the shortest. The mean flagged it at +145%, yet "pick the
two longest" gets it **wrong**.

Fixed: a multiple-answer question carries a cue only if the *N* longest choices
**are** the correct set, and the margin is then measured from the shortest of
them. Multiple-answer `REVIEW_REQUIRED` fell from 7 to 4; the total from 176 to
173.

## Seven strong candidates read, with verdicts

| Id | Pool | Verdict on reading |
|---|---|---|
| `QST-f2m90kz2ya28` | LEARNING | **NO_BIAS** — classifier false positive, now fixed |
| `QST-3pfgr2whbm74` | VALIDATION | **JUSTIFIED_LENGTH** — the answer enumerates the full accessor set; each distractor is a deliberate subset. Shortening makes it wrong |
| `QST-w6g2sgj7apm1` | LEARNING | **JUSTIFIED_LENGTH** — the trailing clause defines the behaviour asked about |
| `QST-x86878b8vrmn` | LEARNING | **CONFIRMED, mild** — the answer is correct in its first clause; the second is teaching material parked inside an option |
| `QST-sgmgf58d4tja` | LEARNING | **CONFIRMED** — the answer names two mechanisms, every distractor names one |
| `QST-0y00gjhre739` | LEARNING | **CONFIRMED** — same shape: a triple against three singles |
| `QST-08dprb9gp7c9` | LEARNING | **CONFIRMED** — 167 characters against 58–77 |

**4 confirmed of 7 read.** Extrapolating 173 × 4/7 would be inventing a figure
from a sample of seven, chosen for being extreme. It is not done here.

What the four share is one shape: **the correct answer names two or three
things and every distractor names one.** The remedy is therefore not to shorten
the answer — it would become wrong — but to give the distractors comparable
substance. That is writing better distractors, which is refinement work.

## A second, distinct cue — measured, and small

Reading suggested another pattern: the correct answer being a lexical
**superset** of its distractors ("which is the complete set?"), which is
readable even at equal length.

Measured: **12 of 537** single-answer questions = **2.2%**, of which only 1 is
also the longest. 11 LEARNING, 1 VALIDATION.

It is real and it is minor. Recorded, not acted on.

---

# Part 3 — 25% is a reference, not a quota

Nothing in `aud11` compares a lot against 25%. The unit of judgement is the
question. `aud10` does gate on the chance baseline, but only inside a lot
recorded as refined, and lot-01 already sits below it without any question
having been rewritten for that purpose — it got there through refinement.

The three constraints stated by the owner are adopted as written:

- a correct answer may legitimately be the longest;
- a distractor is never padded with filler;
- a correct answer never loses a necessary condition to become shorter.

`JUSTIFIED_LENGTH` and `ARTIFICIAL_DISTRACTOR_RISK` exist to hold the first and
second. `QST-3pfgr2whbm74` is the worked example of the third: its 72-character
answer enumerates four accessors, and every shorter form is a wrong answer.

---

# Part 4 — the real correction scope

## Where the cue sits

| | REVIEW_REQUIRED | of pool |
|---|---|---|
| HOLDOUT | **38 / 75** | **50.7%** |
| VALIDATION | 39 / 136 | 28.7% |
| LEARNING | 96 / 344 | 27.9% |

| Mode | | |
|---|---|---|
| single | 169 / 537 | 31.5% |
| multiple | 4 / 18 | 22.2% |

## Impact per deployed payload

| Payload | Questions | `REVIEW_REQUIRED` | |
|---|---|---|---|
| **mock-4** | 75 | **38** | **50.7%** |
| mock-2 | 84 | 26 | 31.0% |
| exam | 136 | 39 | 28.7% |
| mock-3 | 68 | 19 | 27.9% |
| mock-5 | 480 | 135 | 28.1% |
| practice | 344 | 96 | 27.9% |
| mock-1 | 61 | 14 | 23.0% |

*Measured on the built payloads with the corrected classifier. An earlier run of
this table used the pre-fix classifier and reported mock-4 at 39/52.0%,
mock-5 at 137 and practice at 98; those three figures were wrong by one to two
questions and are replaced here rather than left standing.*

**Mock 4 is the worst payload in the corpus, by a wide margin.** It is also the
only unseen measurement the project has, reserved for one sitting. A length cue
there does not just inflate a practice score — it degrades the single verdict
the whole plan defers to.

That matches the owner's stated priority exactly, and it is now evidence rather
than intuition.

## Priority, with the constraint that blocks it

1. **Mock 4 / HOLDOUT** — 38 questions. **Blocked from being reported here.**
   Confirming a `REVIEW_REQUIRED` needs the question and its distractors read,
   and printing holdout content into a session transcript would burn the
   sitting it is reserved for. This has to be a dedicated unit whose per-question
   verdicts are written **to the repository only**, never to a chat.
2. **VALIDATION** — 39 questions, of which those seated in mocks 1–3.
3. **LEARNING** — 96 questions.

---

# What this pilot did not do

No course, question, status, `framework_version` or threshold was touched. No
question was rewritten. No lot was declared refined. Certification Readiness is
unchanged at 9/163, and coverage at 163/163.
