# Mocks 1, 2, 3 and 5 — what is settled, what is missing

**Status: BLOCKED on the owner. No mock is authored from this note.**

Mock 4 is delivered (Units A, B, C: PRs #52, #54, #56). What remains of §10 is
the other four mocks. This note records what the repository can establish about
them, what it cannot, and the one thing that has to come from outside it.

## The blocker: §10's text for these four is not in the repository

`SYMFONY-8-CERTIFICATION-MASTER-PLAN-V2.md` is the specification this project
executes, and it is **not checked in**. Everything the repository knows about
§10 arrived by being quoted into it, and only Mock 4 was ever quoted:

| Recorded here | Source in the repo |
|---|---|
| Mock 4 — 75 questions, 90 minutes, English, Symfony 8.0, holdout | `docs/mocks/mock-4-blueprint.yml`, `official_constraints` |
| Mock 3 — "primarily in English" | `docs/policy/language-policy.md`, quoting §5 |
| Mock 4 — "100% in English" | same |

For mocks **1, 2 and 5** the repository records **nothing**: not their question
count, not their duration, not their difficulty profile, not their language,
not their bank. Mock 3 has one property recorded — a language requirement —
and nothing else.

Inferring the rest from Mock 4 would be inventing a specification and then
executing it, which is the failure this project's §3.1 rule exists to prevent:
a plausible number becomes an unofficial constraint that then looks entirely
credible. So no blueprint is written from this note.

**What is needed:** §10's text for mocks 1, 2, 3 and 5, quoted the way §22, §15,
§5 and §4.3 were quoted into `docs/policy/`.

## What is settled

**Mock 3's bank is decided.** `docs/policy/language-policy.md` binds the mock
language requirements to pools: `VALIDATION` is the exam-mode bank and
`HOLDOUT` is the final-mock reserve, so Mock 3 draws on `VALIDATION`. It
measures **135 of 135 English — PASS**. This was settled before Lot 27 and is
not reopened here.

**The holdout is spent, by design.** All 75 holdout questions are Mock 4's
(ADR-0005 Option A). Mocks 1, 2 and 5 have no reserved bank, and creating one
would mean writing new questions.

## Bank capacity, measured 2026-09-04 over 544 questions

| Pool | Questions | Distinct items | Difficulty | Language | Answer mode | Mean time | Already served by |
|---|---|---|---|---|---|---|---|
| `LEARNING` | 334 | 163 | 30 easy / 196 medium / 108 hard | 313 en, 21 fr | 325 single, 9 multiple | 53s | Practice Mode |
| `VALIDATION` | 135 | 135 | 68 medium / 67 hard | 135 en | 135 single | 57s | Exam Mode, and Mock 3 |
| `HOLDOUT` | 75 | 75 | 5 easy / 40 medium / 30 hard | 75 en | 67 single, 8 multiple | 66s | Mock 4 only |

Both served pools cover all 14 official topics, with at least 7 distinct atomic
items in every topic — so either could supply a 75-question mock on 75 distinct
items, if 75 turns out to be the size §10 asks for.

## The consequence to state plainly

**No practice mock can be unseen.** `LEARNING` is served by Practice Mode and
`VALIDATION` by Exam Mode, so any mock built from them is drawn from questions
the learner has already been able to meet. Only Mock 4 is unseen, and only
because the holdout was reserved for it and served by nothing else.

That is not a defect to engineer around — it is what a practice mock is. But it
must never be reported as if these mocks were unseen assessments, and no
§22 readiness clause may be claimed from a score on one.

## The options, once §10 is known

1. **Reuse `VALIDATION`** — as Mock 3 already does. No new content; the mock is
   a re-arrangement of the exam-mode bank. Cheapest, and least informative for
   a learner who has already worked through Exam Mode.
2. **Reuse `LEARNING`** — the largest pool and the only one with easy questions
   and French ones. Suits an early, gentler mock; the French questions would
   have to be excluded or accepted, depending on what §10 says about language.
3. **Draw across both served pools** — more room to hit a difficulty or topic
   profile, at the cost of a mock whose questions come from two different
   purposes.
4. **Write new questions** — the only way to make a practice mock unseen, and
   the only option with a real content cost. It would need the §1.2 admission
   test and the §1.4 net-value gate applied per question, exactly as Unit B did.

A recommendation is deliberately withheld: which option is right depends
entirely on what §10 says these mocks are *for*, and that text is the thing
this note is missing.
