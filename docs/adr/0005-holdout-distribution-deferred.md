# ADR-0005 — Holdout distribution: decision deferred

- **Status:** Deferred — decision required **before Lot 27** (final mocks)
- **Date:** 2026-09-01

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

The exposure is narrowed, not removed. This repository is **public**, so holdout
questions and their answers remain readable in `content/questions/*.yml` by
anyone who opens it. The situation moved from *served by the application* to
*readable in the source*, which is an improvement in the deployed artefact and
no improvement at all in confidentiality.

The underlying constraint is unchanged and follows from ADR-0001: on GitHub
Pages nothing can withhold data from a client that asks for it, and a public
repository withholds nothing either.

## Consequence to state plainly

The project can produce courses, flashcards and practice questions exactly as
planned. What it **cannot** currently claim is Master Plan §22's *"protected
unseen holdout assessment"*. A learner who has read the question
files has not sat an unseen exam, and no amount of UI discipline changes that.

This does not block any content lot.

## Options, not yet chosen

1. **Accept and relabel.** Drop the "unseen" claim; call the mocks a
   self-scored simulation. Costs nothing, weakens §22.
2. **Separate private distribution.** Ship holdout questions outside the public
   repository — a download the learner opens once, or a separate private
   repository. Preserves "unseen" for a learner who cooperates; still not
   enforcement. ADR-0006 makes this the smallest remaining step: the payload
   side is already done, and only the source-visibility side is left.
3. **Server-side scoring.** A small API holds the answer key and returns only a
   score. The only option that genuinely enforces it, and it contradicts
   ADR-0001's no-server model, so it would require amending that ADR.
4. **Obfuscation.** Rejected on sight: encoding the payload deters nobody and
   would let the project claim a protection it does not have.

## Deadline

A decision is required **before Lot 27 begins**. Building five mock exams on an
undecided distribution model would mean authoring holdout content whose value
depends on an answer nobody has given yet.

Until then, every report says *functional isolation, not confidentiality*.
