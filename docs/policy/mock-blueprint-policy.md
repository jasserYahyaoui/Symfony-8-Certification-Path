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

## The numbered mock exams (/examens) — owner's request, 2026-10-14

Verbatim: « Refait des examens blancs, les examens doivent être format symfony
exam 8.0, je dois depuis menu choisir quel examen passer avec une
recommandation 1 2 3 … 1000 ». The bank was measured before anything was
promised: 637 English questions outside the holdout make at most **eight**
disjoint 75-question exams, and **six** once every official topic keeps its
share in every exam (Automated Tests carries 27 questions). The owner chose six
disjoint exams plus free draws beyond, and kept Mocks 1 to 5.

| | |
|---|---|
| Count, duration, language | 75 questions, 90 minutes, English — the `official_constraints` of the Mock 4 blueprint (§10), `OFFICIAL_FORMAT` |
| Topic spread | each topic's share of the atomic official items, largest remainder — `TRAINING_DISTRIBUTION`; no official weighting is published |
| Exams 1 to 6 | fixed at build time (`PayloadBuilder::examSeriesPayload`), sharing no question, never two questions on one item; dealt by difficulty within each topic, which makes them rise in estimated difficulty — the recommended order |
| Exams 7 and up | drawn in the browser from the same pool and spread, the questions met least often first; the page states how many were already met |
| Holdout | never used: Mock 4's alone |
| Recommendation | the first fixed exam not yet finished, then the next draw |

These questions are served by Practice and Exam Mode too, so no score here is
an independent measure; only Mock 4 is. `PayloadBuilder::assertExamSeries`
refuses a build whose exams share a question, miss the count, leave English or
break the spread (`ExamSeriesPayloadTest` shows it refusing two broken series);
`website/tools/verify-exams.mjs` checks the menu's recommendation and draws;
the production smoke test checks `exams.json` again.

### Answer format and correction — owner's request, 2026-10-14

Verbatim: « garantie que l'examen est comme symfony c'est-à-dire QCM et checkbox
multiple etc. Finalement le résultat en fin doit donner les bonnes réponses ;
pour les mauvaises réponses il doit pointer exactement sur la section de cours
en question ».

| | |
|---|---|
| Answer modes | single answer (radio buttons) and multiple answers (checkboxes, with the number expected). The official exam asks both; **the share of each is not published**, so the series guarantees only that every exam asks both, spread evenly (`dealExams` deals the multiple-answer questions first, `assertExamSeries` refuses an exam without one or a spread wider than one) |
| Measured on 2026-10-14 | **12** of the 637 eligible questions are multiple-answer: **2 per exam**. That is a floor, not a target: remediation item F7 / P1.6 owes more, written and verified like any question |
| Correction | after submission only: every question with its right answers beside the learner's, the canonical explanation, and for a question missed or left blank a link to the course section teaching each outcome it assesses — `ExamSitting`'s `Correction`, also Mocks 1 to 3 |
| Section links | each course maps every outcome of its item to a `## ` heading (`outcome_sections`, 617 outcomes, read section by section when written); the generator writes `CourseUrl::sectionAnchor` on the heading and the payload links to it. Rule **SEC-001** refuses a missing outcome, a heading that does not exist, an outcome of another item or a framing section (aim, key points, sources) as target |

Evidence: `OutcomeSectionTest` (the rule refusing four broken maps, every exam
question resolving), `ExamSeriesPayloadTest` (both kinds in every exam, and the
check refusing an exam without), three SEC-001 cases in
`prove_framework_rules_fail.py`, `website/tools/verify-exam-correction.mjs` (an
exam sat in a browser, the correction read back, every section anchor of
`exams.json` and `practice.json` found in the built pages, a link followed),
and the production smoke test (anchors on a deployed course, counts in
`exams.json`).
