# ADR-0010 — Comprehension checks at the end of each lot

**Status:** Accepted
**Date:** 2026-10-09
**Supersedes:** nothing. **Relates to:** [ADR-0006](0006-exam-mode-serves-the-validation-pool.md)
(the three pools), [ADR-0007](0007-refinement-framework-v2.md) (learning
outcomes), `docs/revision/mastery-checkpoints.md`

## Context

The owner asked for a new kind of exercise, verbatim:

> « Je veux des ameliorarions sur le cours , exemple faire des qcm de
> comprehension apres chausz lot qui englobe tout lenlots , les questions
> doivent etre independants des examens et qcm deja en place »

and then settled four choices:

| Question | Owner's answer, verbatim |
|---|---|
| Language | « Anglais » |
| How many per lot | « Pas un nombre fixe, vraiment tous ce qu'il faut pour garantir que le lot est révisé en succès, pas de mimite, les questions doivent etre au emme endroirs que lélot » |
| When to correct | « Après chaque question (Recommandé) » |
| How to start | « Pilote sur un lot, puis la suite (Recommandé) » |

The project already has an end-of-lot exercise — the mastery checkpoints of
`docs/revision/mastery-checkpoints.md` — but it **re-uses** the lot's existing
questions in Exam Mode. This one must not: its questions are new, and must stay
apart from every question the learner meets elsewhere.

## Decision

**1. A fourth pool, `COMPREHENSION`, kept in its own files.** Questions live in
`content/comprehension/lot-XX.yml` (schema `comprehension-bank`), loaded by
their own loader into their own field of the content set. Nothing that reads
`content/questions/` — Practice Mode, Exam Mode, the five mocks, readiness,
coverage, the revision roadmap, the audits — can see them, by construction
rather than by filter. They count towards no readiness criterion and no
coverage figure: a comprehension check is learning, not evidence.

**2. « Tout ce qu'il faut », made checkable.** No fixed number. A lot is
complete when **every learning outcome (`OUT` id) of every item of the lot is
assessed by at least one comprehension question** — rule `CMP-001`. Questions
that tie several items of the lot together carry `related_items`, so that a
synthesis question may assess outcomes of more than one item. A lot without a
comprehension file is not yet started and is not checked; once every lot has
one, the rule tightens to require all of them (the same exit act ADR-0007 used).

**3. Independence, enforced.** Rule `CMP-002` rejects a comprehension question
whose normalized prompt is identical to, or at least 60 % similar to, any
question in `content/questions/` — every pool, the holdout included. The
threshold is stricter than `DUP-001`'s, which also requires the answers to
match: a comprehension question may test the same fact as an exam question,
since both come from the same course, but it may not be the same question
reworded. When the match is a holdout question, the message withholds its id:
the comparison is automatic and nothing about the holdout is shown.

**4. Same quality bar as the rest.** The question-level rules already applied
to the banks — integrity, archetype, cognitive level, source anchoring,
readable URLs, version contamination, out-of-scope contamination, duplicates —
run on the comprehension set too (`CMP-003` wraps them). Every correct answer is
verified against the Symfony 8.0 code or documentation, by execution where it
can be. Questions are in English.

**5. Where they live: in the lot.** Each lot that has comprehension questions
gets a page « Contrôle de compréhension » as the **last entry of its own
sidebar category**, after its courses. The page serves the questions in order
with the correction **after each question** — the explanation, why each
distractor is wrong, and a link back to the course of the item tested.

**6. Payload isolation.** The build writes `comprehension.json` carrying the
`COMPREHENSION` pool and nothing else, and asserts it carries no question of
another pool and no holdout id.

## Consequences

- 612 learning outcomes across 26 lots: a lot needs at least as many
  questions as it has outcomes, unless a synthesis question assesses several;
  delivered lot by lot. The pilot is lot 12, *Console* (9 items, 25 outcomes):
  34 questions, 29 on one item and 5 synthesis questions relating two items,
  every answer verified by execution against Console 8.0.15 or read in the 8.0
  documentation. `CMP-002` rejected one draft prompt, at 63 % similarity to a
  LEARNING question it shared only a sentence shape with; it was rewritten, the
  threshold was not moved.
- `aud10` measures each comprehension bank as its own group,
  `comprehension/lot-XX`, and the rule proofs break the real pilot bank six
  ways (`prove_framework_rules_fail.py`).
- `mastery-checkpoints.md` keeps its own protocol; the comprehension check is
  an additional, earlier step — understanding before testing.
- A learner's answers are not stored: the page keeps the score for the session
  only. Practice Mode's weak-point history therefore stays exactly what it was.
