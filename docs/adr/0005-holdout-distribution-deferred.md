# ADR-0005 — Holdout distribution: decision deferred

- **Status:** Deferred — decision required **before Lot 27** (final mocks)
- **Date:** 2026-09-01

## Context

Holdout questions are functionally isolated: the build assembles
`practice.json` from the learning pool alone, so Practice Mode cannot serve one.
That guarantee is real and tested.

They are not confidential. `exam.json` is published at `/data/exam.json` with
each question's `correct` flags and explanation. Anyone who fetches the URL can
read the answers, and the production smoke test fetches it deliberately. This
follows from ADR-0001: on GitHub Pages nothing can withhold data from a client
that asks for it.

## Consequence to state plainly

The project can produce courses, flashcards and practice questions exactly as
planned. What it **cannot** currently claim is Master Plan §22's *"protected
unseen holdout assessment"*. A learner who has read `exam.json` has not sat an
unseen exam, and no amount of UI discipline changes that.

This does not block Lot 03 or any content lot.

## Options, not yet chosen

1. **Accept and relabel.** Drop the "unseen" claim; call the mocks a
   self-scored simulation. Costs nothing, weakens §22.
2. **Separate private distribution.** Ship holdout questions outside the public
   site — a download the learner opens once, or a separate private repository.
   Preserves "unseen" for a learner who cooperates; still not enforcement.
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
