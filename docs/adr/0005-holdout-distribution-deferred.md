# ADR-0005 — Holdout distribution

- **Status:** `ACCEPTED` — option 1, in force from 2026-09-03. Extended the same day by the owner's **Option A** decision for Mock 4, recorded below.
  The 2026-09-03 revision recorded this as `PENDING_HUMAN_APPROVAL` on the
  reading that "§15 reserves this class of decision to the owner". That reading
  was made while §15 was unreadable and it does not survive the restored text:
  §15 requires approval **only** for an irreversible architecture change, an
  official-scope change, a major deletion, authentication/permissions/secrets,
  an unresolved source contradiction, a disabled test or CI rule, and a
  deployment blocker needing human access. Option 1 is none of these — it
  changes the wording of a claim and adds a learner protocol sentence.
  Options 2 and 3 *would* qualify, because both amend
  [ADR-0001](0001-build-time-php-static-runtime.md); they remain unapproved.
  The owner independently stated option 1 as the recommended course on
  2026-09-03 and instructed that the 27 holdout questions **not** be moved to a
  new content root without a demonstrated need.
- **Date:** 2026-09-01, decision proposed 2026-09-03
- **Supersedes the "Deferred" status of the 2026-09-01 revision.** The context,
  the options and the deadline below are unchanged from that revision; what is
  new is the *Proposed decision* section and the measured state of the pool.

## Context

Holdout questions are functionally isolated: the build assembles
`practice.json` from the learning pool alone, so Practice Mode cannot serve one.
That guarantee is real and tested.

They are not confidential.

**Amended 2026-09-01 by [ADR-0006](0006-exam-mode-serves-the-validation-pool.md).**
When this ADR was written, `exam.json` was built from the holdout pool and
published at `/data/exam.json` with each question's `correct` flags and
explanation, so the deployed site handed the answers to anyone who asked. That
is no longer true: Exam Mode now serves the `VALIDATION` pool and **the holdout
is not deployed in any payload**.

**Amended again 2026-09-04, Lot 27 Mock 4 Unit C.** The holdout is now deployed
in one payload, `mock-4.json`, which serves Mock 4 and nothing else. That is
this ADR's Option A carried out rather than departed from: *unseen* means never
served by a learning mode, and the final mock is not one. The learning payloads
are unchanged and still carry none. Nothing about confidentiality changes
either — the answers were already readable in `content/questions/*.yml`, and
publishing the payload adds no exposure that the public repository did not
already have.

The exposure is narrowed, not removed. This repository is **public**, so holdout
questions and their answers remain readable in `content/questions/*.yml` by
anyone who opens it. The situation moved from *served by the application* to
*readable in the source*, which is an improvement in the deployed artefact and
no improvement at all in confidentiality.

The underlying constraint is unchanged and follows from ADR-0001: on GitHub
Pages nothing can withhold data from a client that asks for it, and a public
repository withholds nothing either.

## Measured state of the pool (2026-09-03)

| | |
|---|---|
| HOLDOUT questions | 27 |
| Official items covered | 27 of 163 |
| Items carrying more than one | none |
| Deployed in any payload | no |
| Readable in the public source | yes |

The pool was never built to a quota. It grew where a lot's reasoning warranted a
third question, and CONTEXT.md records several lots that deliberately added none
because the existing pool already exercised the same reasoning.

## Consequence to state plainly

The project can produce courses, flashcards and practice questions exactly as
planned. What it **cannot** currently claim is Master Plan §22's *"protected
unseen holdout assessment"*. A learner who has read the question files has not
sat an unseen exam, and no amount of UI discipline changes that.

This does not block any content lot. It blocks Lot 27.

## Options

1. **Accept and relabel.** Drop the "unseen" claim; call the mocks a self-scored
   simulation under a stated learner protocol. Costs nothing, weakens §22.
2. **Separate private distribution.** Ship holdout questions outside the public
   repository — a download the learner opens once, or a separate private
   repository. Preserves "unseen" for a learner who cooperates; still not
   enforcement.
3. **Server-side scoring.** A small API holds the answer key and returns only a
   score. The only option that genuinely enforces it, and it contradicts
   ADR-0001's no-server model, so it would require amending that ADR.
4. **Obfuscation.** Rejected on sight: encoding the payload deters nobody and
   would let the project claim a protection it does not have.

## Proposed decision — option 1, with a written protocol

**Adopt option 1.** Drop the word *unseen* from every claim about the mocks, and
replace it with what is true and testable: the mocks draw on a pool that **no
mode of the application has ever served**, so a learner who has used only the
site meets those questions for the first time in the mock.

Add one learner protocol sentence to the mock instructions: *do not open
`content/questions/*.yml` before sitting the mocks.*

### Why this rather than the others

Option 3 is the only one that enforces anything, and it buys enforcement by
contradicting ADR-0001 — a server, a deployment, a secret to hold. For a corpus
whose learner is also its owner, enforcement against oneself buys nothing: the
owner holds the repository either way.

Option 2 looks attractive because ADR-0006 already did the payload half. It is
rejected **now** for a concrete reason: the 27 holdout questions are referenced
by `question_refs` in the canonical matrix and are checked by `POOL-002`,
`HoldoutIsolationRule` and `REF-001`. Moving them out of `content/questions/`
would either break those rules or require a second, unvalidated content root —
real architectural cost, paid for a guarantee that still rests on cooperation.
It remains the documented upgrade path if the owner wants the stronger claim.

Option 1 is the minimum change that leaves every statement the project makes
true. It is consistent with the rule this project has followed throughout:
never claim a protection that is not there.

### What this decision does *not* do

It does not weaken the functional isolation, which stays tested. It does not
change any existing question. It does not change the size or shape of the pool,
and it sets no quota: the distribution stays an outcome of what each item needed.

## Impact

- **Questions.** None change. `QST-0jd9nbbaqczb` was rewritten under audit item
  P2.2 for a separate reason — it was 0.88 similar to its VALIDATION counterpart
  — and that fix stands regardless of which option is approved.
- **Metrics.** Coverage is unaffected: §3.5 counts EXAM_READY atomic official
  items, not questions.
- **Pedagogy.** The mocks stay useful. What changes is the label on the claim.
- **Audits.** Every report keeps saying *functional isolation, not
  confidentiality*. Under option 1 that sentence becomes the permanent wording
  rather than an interim caveat.
- **Lot 27.** Unblocked once approved: the mocks are built from the existing
  27-question pool with no redistribution.

## Deadline

A decision is required **before Lot 27 begins**. Building five mock exams on an
undecided distribution model would mean authoring holdout content whose value
depends on an answer nobody has given yet.

Met on 2026-09-03. This ADR is `ACCEPTED`; Lot 27 is no longer blocked **by
this ADR**. Every report continues to say *functional isolation, not
confidentiality* — under option 1 that is the permanent wording, not an interim
caveat.

## Mock 4 architecture — Option A, decided by the owner 2026-09-03

The 2026-09-03 Mock 4 decision report put one question to the owner: must Mock 4
be confidential, or is *never served by the application* enough? The owner chose
**Option A**. What follows is settled, not proposed.

**"Unseen" means: a question never served by Practice Mode, Exam Mode or any
other learning mode of this application.** Absolute confidentiality at the level
of the public repository is **not** required.

The project therefore states, permanently and without softening:

| | |
|---|---|
| Functional isolation | **YES** — tested at build time and against the deployed bytes |
| Application-level unseen | **YES** |
| Repository confidentiality | **NO** |
| Answers readable by someone deliberately inspecting the public source | **YES** |

Consequences, all in force:

- the **27** existing holdout questions stay `HOLDOUT`; none is moved,
  duplicated or reclassified;
- **48 new** holdout questions are required to reach the 75 that §10 specifies;
- none of the 75 is ever served in Practice or Exam Mode;
- all 75 remain governed by the existing canonical rules — no second content
  root, no private repository, no backend, no secret store, no parallel
  validation system;
- a learner who deliberately opens `content/questions/*.yml` forfeits the
  *unseen* property **for themselves**. That is a property of the reader, not a
  failure of the system, and it is the honest limit of a static public site;
- **no official topic weighting is known.** Any distribution the project builds
  is labelled `TRAINING_DISTRIBUTION` and is never presented as official
  (§7.4, §10).

This architecture must never be described as offering real confidentiality.

## Operational definition now in force

`HOLDOUT` designates a pool of questions that **no planned application mode of
this repository has ever served**. That is the whole claim. It is tested — the
build assembles `practice.json` from `LEARNING` and `exam.json` from
`VALIDATION`, and `HoldoutIsolationRule` fails the build if a holdout question
reaches either payload.

It is *not* a claim of confidentiality: the questions and their correct answers
are readable in `content/questions/*.yml` in a public repository. The mock
instructions therefore carry one learner protocol sentence — *do not open
`content/questions/*.yml` before sitting the mocks* — and the project never
writes "unseen" without it.

### Effect on Master Plan §22

§22 requires a *"protected unseen holdout assessment"*. Read absolutely, a
public repository can never satisfy it and no option short of option 3 would.
Read operationally — unseen **by the application** — the requirement is met and
tested. The owner supplied the operational reading on 2026-09-03; this ADR
records it as the reading in force, and §22 is assessed against it.
