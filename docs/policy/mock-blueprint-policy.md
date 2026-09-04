# Mock policy — §10 recorded, and what it does not decide

Source: `SYMFONY-8-CERTIFICATION-MASTER-PLAN-V2.md — §10`. The plan is not
checked into this repository, so its text is recorded here verbatim and its
wording is not modified.

## 1. The normative text (§10, quoted)

> ### 10. Exam blueprint and mocks
>
> Public official constraints:
> 75 questions
> 90 minutes
> 15 topics
> English
> Symfony 8.0 only
>
> Never invent official topic weighting. Any internal distribution must be
> labelled TRAINING_DISTRIBUTION.
>
> Final mocks:
> - Mock 1 - Knowledge: direct mastery checks;
> - Mock 2 - Application: code, configuration and scenarios;
> - Mock 3 - Certification difficulty: close distractors, primarily English;
> - Mock 4 - Official-format simulation: 75 questions, 90 minutes, 100% English, holdout questions;
> - Mock 5 - Weakness-based: generated from demonstrated weak outcomes.
>
> After each mock, report:
> - score according to the declared internal policy;
> - time;
> - unanswered and incorrect questions;
> - weak official topics and items;
> - learning outcomes to revise;
> - targeted next actions;
> - readiness evidence.

## 2. Official constraints of the exam

These are properties of the certification, not of this project:

| Constraint | Value |
|---|---|
| Questions | 75 |
| Duration | 90 minutes |
| Topics | 15 |
| Language | English |
| Version | Symfony 8.0 only |

They bind **Mock 4**, which §10 defines as the official-format simulation. §10
states no question count and no duration for Mocks 1, 2, 3 and 5.

> **Note on "15 topics".** §10 says 15; the imported official syllabus yields
> **14** topics, and the repository's denominator is the 163 atomic official
> items, not a topic count. The discrepancy is recorded, not resolved, and
> nothing in this project is derived from the number 15. It is not used as a
> distribution basis anywhere.

## 3. Internal rules the Master Plan imposes

- Never invent official topic weighting.
- Any internal distribution must be labelled `TRAINING_DISTRIBUTION`.
- Any internal format decision must be labelled `INTERNAL_TRAINING_FORMAT`.
- Answers and analysis are shown only after submission.
- Mastery is never inferred from a single correct answer.

## 4. What §10 leaves undecided for Mocks 1, 2, 3 and 5

§10 fixes a question count and a duration **only for Mock 4**. For the other
four it defines a *role* and nothing else. It does not define:

- a mandatory question count;
- a mandatory duration;
- a topic weighting;
- a mandatory pool;
- an official pass threshold.

**These values must not be derived from Mock 4 and must never be presented as
official.** Where this project needs them, it decides them from measured data
and labels the decision `INTERNAL_TRAINING_FORMAT`; the topic spread is labelled
`TRAINING_DISTRIBUTION`. Both labels mean the same thing: *this project chose
it, the certification did not*.

The decisions themselves live in
[`docs/mocks/mocks-1-2-3-5-blueprint.yml`](../mocks/mocks-1-2-3-5-blueprint.yml),
with the measurement behind each one.

## 5. Mock 4 is delivered and is not reopened

75 questions, 90 minutes, 100% English, `HOLDOUT`, `TRAINING_DISTRIBUTION`,
functional isolation, **no confidentiality of the public repository**. Its 75
questions are not modified without a demonstrated anomaly.

## 6. The holdout is closed to the other mocks

`HOLDOUT` is Mock 4's, entirely and by design (ADR-0005 Option A). Mocks 1, 2,
3 and 5 **never** draw on it. Consequently none of them is unseen, and no §22
readiness clause may be claimed from a score on one.
