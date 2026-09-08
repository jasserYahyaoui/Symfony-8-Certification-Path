# Lot 01 — PHP 8.4 Foundations · expert refinement audit

**Read-only phase completed 2026-09-08 against `89bea2b`. No file was modified
to produce this assessment.**

This unit does not re-open any proven status. B-1 is `PASS`, FR-2 is `DONE`, and
AUD-01 through AUD-08 are `PASS`; nothing here disturbs them. It adds a new
question about Lot 01 that none of those audits asks.

## The question this audit asks

Every existing gate asks whether the content is *present, consistent and
sourced*. All of them pass on Lot 01. This audit asks the one thing they do not:

> **Does Lot 01 prepare a candidate for a question they have never seen?**

A learner can answer every question this lot contains and still fail the exam,
because answering the questions you have already studied measures recall of
those questions, not command of the item. So each item is placed on a ladder,
and the ladder is the finding:

| Rung | Meaning | Evidence that would show it |
|---|---|---|
| **documented** | the fact is written down somewhere | it appears in the course |
| **understood** | the learner can restate it | a `KNOW` / `RECOGNIZE` question |
| **memorised** | it survives without re-reading | a flashcard, or repeated success |
| **mastered** | the learner can tell it apart from its neighbours | a `DISTINGUISH` question |
| **applicable** | the learner can decide an unseen case | an `APPLY` / `DIAGNOSE` question, in exam mode |

## Inventory

9 atomic items, 9 courses, 7 flashcards, **30 questions** (18 `LEARNING`,
8 `VALIDATION`, 4 `HOLDOUT`). All 9 items are `EXAM_READY`.

Level distribution, stated as an observation and never a target: 8 `STANDARD`,
1 `MINIMAL`.

Cognitive spread: `KNOW` 10, `UNDERSTAND` 8, `APPLY` 12.
Exam skill: `RECOGNIZE` 7, `DISTINGUISH` 10, `DIAGNOSE` 13.
Difficulty: easy 2, medium 16, hard 12.

## Verdict per item

| Item | Level | Highest rung reached | Exam-mode evidence |
|---|---|---|---|
| PHP API up to PHP 8.4 version | STANDARD | **mastered** — `DISTINGUISH`, hard | yes |
| Object Oriented Programming | STANDARD | **applicable** — `APPLY`, hard, LSB | yes |
| Attributes | STANDARD | **applicable** | yes |
| **Interfaces** | **MINIMAL** | **understood — and no further** | **none** |
| Anonymous functions and closures | STANDARD | **applicable** | yes |
| Abstract classes | STANDARD | **applicable** | yes |
| Exception and error handling | STANDARD | **applicable** | yes |
| Traits | STANDARD | **applicable** — precedence and conflict at `APPLY` | yes |
| Enums | STANDARD | **applicable** | yes |

**Eight of nine items are in good shape.** The courses are exam-shaped rather
than encyclopedic: each carries a *Pièges d'examen* section aimed at a real
confusion, and the hard questions test those confusions at `APPLY` level. The
Traits course's precedence rule and conflict resolution — the two things a
candidate most often gets wrong — are both tested, one of them in exam mode.

## Finding 1 — `Interfaces` is the lot's false-mastery risk

It is the only item whose entire assessment is **two `easy` `KNOW` /
`RECOGNIZE` questions**, and the only one with **no `VALIDATION` question**, so
it can never be served in exam mode at all.

```text
QST-z4f4sxjhrhk1  LEARNING KNOW easy  "Which of these may an interface declare?"
QST-9r75z716zt0h  LEARNING KNOW easy  "Combien d'interfaces … combien de classes ?"
```

A learner answers two recall questions, the item reports `EXAM_READY`, coverage
reports 100%, and **nothing in the system has tested whether they can apply an
interface rule to code they have not seen**. That is precisely the false sense
of mastery this refinement exists to find.

**The rules are not at fault.** `POOL-002` requires a `VALIDATION` question only
for `STANDARD` and `DEEP` items, and `Interfaces` is `MINIMAL`; the missing
flashcard is equally correct, because the item declares
`required_assessment_modes: [QUESTION]` and §6 treats an unnecessary card as
pure revision cost. The gap is in the **level**, and the level rests on a claim
about the item that PHP 8.4 has made false.

### The claim that stopped being true

The recorded justification is *"Item factuel et de reconnaissance : les règles
tiennent en un exemple et une liste de ce qui est interdit."* Two rules verified
from the official documentation say otherwise:

**1. Interface constants are overridable — since PHP 8.1.** The PHP manual, in
`php/doc-en` `language/oop5/interfaces.xml`, states verbatim: *"Prior to PHP
8.1.0, they cannot be overridden by a class/interface that inherits them."*
`php-src` `PHP-8.1` `UPGRADING` line 214 records the matching change — *"Added
support for the `final` modifier for class constants"* (RFC `final_class_const`).
Symfony 8.0 requires PHP 8.4, so the live rule is that an implementing class
**may** override an interface constant unless it is `final`. **The course does
not say this.** It says only *"Les constantes sont autorisées."*

**2. Interfaces may declare properties — since PHP 8.4.** Same source: *"As of
PHP 8.4.0, interfaces may also declare properties. If they do, the declaration
must specify if the property is to be readable, writeable, or both … an
interface property that is settable may not be `readonly`."* The course does
mention property hooks in a single aside, and what it says is **correct** — but
it does not give the declaration form, nor the rule that a `readonly` property
cannot satisfy a settable interface property.

Neither is encyclopedic. Both are version-boundary rules on an item whose
official constraint is *"PHP API up to PHP 8.4 version"*, and both are the shape
a certification question takes.

**Interfaces is therefore no longer a recognition item.** It carries a genuine
distinction (overridable vs `final` constant) and a genuine application
(which property declarations satisfy an interface). The level is promoted for
that reason and no other — **not** because a ratio looked low. There is no
target distribution, and a lot with zero `DEEP` items is complete.

## Finding 2 — four traps are taught but nothing verifies them

Each course asserts, in its own *Pièges d'examen* section, that a specific
confusion matters. Four of those are tested by no question in the corpus:

| Item | Trap taught | Verified against PHP 8.4.19 |
|---|---|---|
| PHP API | property hooks are **not** cached; `get` runs on every read | `[hook ran] [hook ran]` on two reads |
| Object Oriented Programming | `private` blocks override — the parent keeps calling its own | parent's `call()` returns `"P"` from a child redeclaring `h()` |
| Abstract classes | `abstract` and `final` are incompatible | `Fatal error: Cannot use the final modifier on an abstract class` |
| Exception and error handling | `catch` order decides; a broad type shadows a narrow one | `catch (\Throwable)` before `catch (\TypeError)` catches the `TypeError`, **with no error** |

Every one was executed on the controlled runtime rather than asserted from
memory. The `catch`-order case is the sharpest: PHP does **not** reject the
unreachable branch, so the mistake is silent.

**The admission test used here is narrow, and it is the course's own.** If a
course states that a confusion is an exam trap, then either it is worth
verifying, or it should not be taught. Nothing is added because it is
interesting; four questions are added because the courses already committed to
these four facts mattering.

## What this audit refuses to add — `DO_NOT_ADD`

Applying §1.2's admission test and §1.4's value gate, the following were
considered and **rejected**:

| Candidate | Why not |
|---|---|
| `__get` / `__set` and the full magic-method set | Not an official item in Lot 01; belongs to no syllabus line here |
| Interface covariance and contravariance in depth | The manual documents it, but no exam-shaped question needs more than the signature-compatibility sentence already present |
| A full PHP 8.0–8.3 feature catalogue | The version table already carries the *examinable* rows; a complete changelog is revision cost, not exam value |
| `Stringable` auto-implementation detail | Already implicit in the course example; a separate fact adds no answer probability |
| Enum interface constants, `enum` implementing interfaces | Owned by the `Enums` item; duplicating it here would double revision cost for no gain |
| Promoting any other item's level | Nothing in the other eight items is mis-levelled; their evidence already reaches `applicable` |

`Interfaces` keeping a second flashcard was also rejected: the item's rules are
verified through application after this refinement, and §6 is explicit that a
card for a fact already retained through application is pure revision cost.

## A note on this audit's own method

A keyword-overlap detector was written to find taught-but-untested traps
automatically. It flagged nine. **Four of the nine were false positives**, and
reading the questions disproved them — the Traits conflict rules, `as` not
discarding, closure capture-at-definition and arrow-function capture are all
tested, several at `APPLY`. The four in Finding 2 survived reading.

A keyword heuristic is not evidence, and it is recorded here as a method that
had to be checked by hand rather than as a check that passed.

## Decisions

| # | Decision | Warrant |
|---|---|---|
| D1 | `Interfaces`: teach the constant-override rule (8.1) and the interface-property rules (8.4) | verified in `php/doc-en` and `php-src` |
| D2 | `Interfaces`: `MINIMAL` → `STANDARD`, with justification, outcomes and `minimum_evidence` rewritten | the item itself now carries a distinction and an application |
| D3 | `Interfaces`: add one `LEARNING` question at `APPLY` and one `VALIDATION` question | `POOL-002`, and to reach *applicable* |
| D4 | Add four `LEARNING` questions for the four unverified traps | each course already asserts the trap matters |
| D5 | Add nothing else | the `DO_NOT_ADD` table above |

New questions total **six**; no item exceeds five questions, and no item gains a
second `VALIDATION` question, so the one-eligible-question-per-item rule the
training mocks depend on is preserved.

---

# Implementation outcome

Applied 2026-09-08 on branch `lot-01-refinement`.

| | |
|---|---|
| Items changed | 1 level, 5 with a new question |
| Questions added | **6** — 5 `LEARNING`, 1 `VALIDATION` |
| Course rewritten | `CRS-e4y7gtn5k4f0` (Interfaces), 144 → 396 body words |
| Second independent audit | [`second-audit.txt`](second-audit.txt) — 0 findings |
| Fail-proof | [`fail-proof.txt`](fail-proof.txt) — **44 of 44** |

## Result against the ladder

Every one of the nine items now has exam-mode evidence — **9 of 9 carry a
`VALIDATION` question**, where eight did before. No item is assessed by recall
alone. The six traps the courses assert are now each tested by an **answer
key**, not merely mentioned somewhere in a question.

`Interfaces` moves from *understood* to *applicable*: `MINIMAL` → `STANDARD`,
two new `APPLY` / `DIAGNOSE` questions, four learning outcomes instead of two,
and `required_assessment_modes` gains `EXAM`.

## What the change cost elsewhere, and why that is right

Adding one `VALIDATION` question changed measurements the training mocks are
built from, and `MocksBlueprintTest` re-derives every one of them rather than
trusting the file:

```text
mock-2  eligible 83 → 84   mean 59.6 → 59.8   cap_from_variation 55 → 56   unused 31 → 32
mock-3  eligible 67 → 68   mean 61.4 → 61.5   count 44 → 45   duration 52 → 54 min
mock-5  eligible 469 → 475
```

`mock-3`'s topic spread gains exactly one question, on **PHP** — the topic that
gained the question. `mock-1` is untouched, because nothing it draws on changed.

The language policy was re-measured the way it asks to be: **550 questions,
529 English, 21 French**, advanced English 208 of 209, `VALIDATION` 136 of 136 —
and the 2026-09-04 reading is kept beside it rather than overwritten.

Two stale statements in the mocks blueprint were corrected: its `status` and a
header comment both still said *"no mock implemented from this file yet"*, which
has been false since mocks 1, 2, 3 and 5 were deployed.

## A frozen reference date in AUD-03, found by this unit

`AUD-03` reported all six new questions as **"verified_at in the future"**. The
cause was in the audit, not the content: `TODAY` was hardcoded as
`dt.date(2026, 9, 7)`, the day the audit was written. Every citation verified
after that day would be flagged, for ever, and the number would only grow.

Back-dating the citations would have been recording a false verification date,
so the reference point was unfrozen to `dt.date.today()` instead. `ANCHOR-7`
still fires on an injected `2099-01-01`, proved in the fail-proof — **the check
was thawed, not weakened**.

## This unit's own second audit was partly vacuous, and the fail-proof said so

`L1-3` was written to check that each taught trap is verified by a question. Its
first version searched the stem, every distractor and the explanation, joined by
`|`. Breaking the **answer key** therefore left it passing — the harness injected
exactly that and `L1-3` still exited 0.

It now reads **only the correct choices**. The injection fails it, as it should.
That is the fifth check caught this way in the project, after `VOL-3`, `HOLD-1`,
`HOLD-6` and `TECH-7`.

## Statuses this unit did not touch

`B-1` stays `PASS`, `FR-2` stays `DONE`, and `AUD-01` through `AUD-08` stay
`PASS` — each was **re-run on the changed corpus** and re-earned its result
rather than carrying it forward:

```text
AUD-01 163/163 verbatim   AUD-02 0   AUD-03 --offline 0   AUD-04 0
AUD-05 0   AUD-06 0   AUD-07 0   AUD-08 0   FR-2 second audit 0
```

Lot 27 remains `NOT_DONE`. AUD-09 has not run, Mock 4 is
`PENDING_HUMAN_VALIDATION`, and Lots 02–26 are not refined.
