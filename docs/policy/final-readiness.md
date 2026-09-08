# Final readiness against Master Plan §22

§22 is the project's exit rule. This file states it verbatim, names what each
clause requires of *this* repository, and records the measured state. It is a
standing document: Lot 27 closes against it.

## §22 verbatim

> ## 22. Final readiness rule
>
> The project is ready only when it demonstrates:
>
> ```text
> 100% atomic official syllabus coverage
> + 0 critical syllabus gap
> + 0 known incorrect scored answer
> + 0 scored OUT_OF_SCOPE dependency
> + verified Symfony 8.0 sources
> + functioning English timed simulation
> + protected unseen holdout assessment
> + manageable revision burden
> + successful technical, pedagogical, accessibility and production gates
> ```
>
> A numerical score may summarize quality but must never compensate for a
> critical blocker.

## Purpose

§22 is a **conjunction**, not a score. Every clause must hold; a strong figure
in one clause buys nothing for a weak one. Its last line exists to forbid
exactly that trade, and it is why this project reports `MISSING`, `BLOCKED` or
`NOT_APPLICABLE` rather than a percentage that averages a blocker away.

§22 is assessed **after Lot 27**, not before it: three clauses depend on audits
and mock exams that are Lot 27's deliverables (§14).

## Measured state — 2026-09-07, commit `ff09d45` + this change

Every figure below was reconciled from the canonical files
(`content/**`, `docs/syllabus/syllabus-matrix.yml`) by script, never from an
earlier report and never from this table's previous revision.

| # | §22 clause | What it requires here | State |
|---|---|---|---|
| 1 | 100% atomic official syllabus coverage | EXAM_READY atomic official items ÷ total, per §3.5 | **PASS** — `bin/cert coverage` exit 0: **100% (163/163)**, no report diff |
| 2 | 0 critical syllabus gap | no official item without the content its level requires | **PASS as measured, with a standing limit.** AUD-01 ran and its two divergences are repaired: the Messenger third-party-transport exclusion is recorded and enforced (`SYL-1`), and the seven Messenger items state the boundary the syllabus states instead of denying one exists (`SYL-2`). Import fidelity is clean in both directions, 163/163. The limit stands regardless: the PDF is the same artefact the import was made from, so nothing here corroborates the scope against a second witness, and `certification.symfony.com` is still unreachable |
| 3 | 0 known incorrect scored answer | no scored question with a wrong key | **PASS as known** — 18 rules, 0 violations over **544** questions; P2.2/P2.3/P2.4 corrected. Systematic re-check is Lot 27's *question-bank audit*, which has not run |
| 4 | 0 scored OUT_OF_SCOPE dependency | no scored question depending on non-official material | **PASS** — all **544** questions are `classification: OFFICIAL`; zero non-official scored questions |
| 5 | verified Symfony 8.0 sources | every source version-anchored to 8.0 | **PASS — AUD-02 and AUD-03 both `PASS`.** Version anchoring is clean (AUD-02 `PASS`, 0 findings over 918 citations) and every one of the 167 distinct source URLs returns 200, and every one of those 918 citations now carries a precise anchor to a passage that proves its claim — 15 of them needed a different or an additional source, found by verifying each one rather than by counting anchors. `SRC-001` enforces this over the whole learner-facing corpus at `Error` severity. **544 of 544** `verification_status: VERIFIED`; **0** occurrences of `symfony.com/doc/current` across every file in `content/` and `docs/syllabus/`; CI rejects `/current/` |
| 6 | functioning English timed simulation | a working timed exam mode, in English | **PASS for the artefact** — `website/src/pages/exam.tsx`, 90-minute official duration, serving `exam.json`: **135 of 135 questions English**; and `/mock-4`, 75 questions, 90 minutes, **75 of 75 English**. The software exists and is deployed. **It has not been sat.** The owner's human validation gate below is separate from this clause and blocks final readiness on its own |
| 7 | protected unseen holdout assessment | see [ADR-0005](../adr/0005-holdout-distribution-deferred.md) | **PASS on the definition the owner settled 2026-09-03 (Option A)**: *unseen* means never served by Practice Mode, Exam Mode or any other learning mode. The holdout is complete — **75 questions across 75 distinct atomic items, 308 choices**, all English — and Mock 4 is built. Proved against the deployed bytes, not any payload's own label: `practice.json` (334, all LEARNING), `exam.json` (135, all VALIDATION) and the three training-mock payloads (61, 83 and 67 eligible) carry none of the 75 holdout ids nor their 308 choice ids, while `mock-4.json` carries the whole holdout and nothing else. `PayloadBuilder::assertNoHoldoutLeak()` and `assertMockMatchesBlueprint()` assert both directions at build time and the production smoke test re-proves them on the deployed site. **Repository confidentiality: NO** — the questions and answers are readable by anyone deliberately inspecting the public source, and the project says so permanently. Lot 27's *holdout integrity audit* has not run |
| 8 | manageable revision burden | a corpus a candidate can actually revise | **PASS as measured** — 163 courses, **65,151 body words** (median 381, mean 400, range 184–880), 137 flashcards, **544** questions. Roughly 4–5 hours of reading. Lot 27's *content-volume and duplication audit* has not run |
| 9 | technical, pedagogical, accessibility and production gates | §17's gates, each green | **PASS as measured, one gate unrun** — validate 18 rules / 0 violations over 544 questions; phpunit **163 tests, 8652 assertions**; `bin/cert build` exit 0; site build exit 0; a11y **12 surfaces, 0 violations** (landing, docs index, item page, glossary, practice, exam, mocks 1–5, progression); production: `master` deployed and smoke-tested every lot, most recently deploy run `34159913803` on `ff09d45` with smoke `101859608690`, 21 URLs at 200 and the holdout check green in both directions. Each command was run on its own with its exit code read (PROC-1). The **pedagogical** gate is the one word in this clause no command measures: Lot 27's audits are its evidence and they have not run |

## The owner's Mock 4 human validation gate

**State: `PENDING_HUMAN_VALIDATION`.**

The owner set this gate on 2026-09-07, on top of §22. The project is **not
final-ready** while it stands, whatever every clause above says — §22's own last
line is the precedent: a table of passes must never compensate for a blocker.

The gate is a real sitting of Mock 4 by the candidate, under these conditions:

| Condition | Required |
|---|---|
| Questions | 75 |
| Duration | 90 minutes |
| Language | 100% English |
| Conditions | certification-style |
| External help | none |

and it must record: **score, time taken, incorrect answers, unanswered
questions, and weak topics**.

**This result may never be fabricated, inferred, estimated, or substituted.**
Not from a Mock 1, 2, 3 or 5 score — those draw on `LEARNING` and `VALIDATION`,
which the learner meets during study, so no score on them is evidence about
unseen material. Not from `exam.json` results. Not from a partial or untimed
sitting. Not from the mere existence of `/mock-4`. Until the owner supplies the
result, this row reads `PENDING_HUMAN_VALIDATION` and nothing else, and no
report may describe the project as ready, done, or validated.

What the repository *can* evidence, and what it has, is the instrument: Mock 4
exists, carries the whole holdout and nothing else, runs 75 questions at 90
minutes in English, and is deployed. That is the artefact half of clause 6. The
sitting is the other half and it belongs to the candidate.

## Audits not yet run

Recorded here so no clause above is read as more settled than it is. Each is a
Lot 27 §14 deliverable, and none has been executed:

| # | Audit | Bears on | State |
|---|---|---|---|
| AUD-01 | [Independent syllabus audit](../audit/lot-27-aud01-independent-syllabus/README.md) | clause 2 | **`PASS` on every condition this side can test, `SYL-1` and `SYL-2` resolved** — 2026-09-08, after failing on 2026-09-07. Ran against the owner-supplied PDF (sha256 `4ee8b962…`, 468,963 bytes, 5 pages). Transcription 163/163 verbatim, the reverse direction clean, every constraint reconciled, and the exclusion record now holds 13 entries against the PDF's 13. **B-1 is not marked `PASS`**: the owner's completion gate was truncated in transmission, so the full condition set is unknown and a partially known gate is not one this project claims to have met. **Caveat that does not go away:** the PDF is byte-identical to the one the import was made from, so this is transcription fidelity, not independent corroboration of scope |
| AUD-02 | [Version-contamination audit](../audit/lot-27-aud02-version-contamination/README.md) | clause 5 | **`PASS`** — 2026-09-07, 0 findings over 907 citations; one real gap found and fixed (`php/php-src` undeclared in the source map) |
| AUD-03 | [Source and anchor audit](../audit/lot-27-aud03-source-anchor/README.md) | clause 5 | **`PASS`** — 2026-09-08, after failing on 2026-09-07. 918 citations inspected, 167 distinct URLs all at HTTP 200, 0 missing anchors, 0 unsupported claims, 0 unresolved conflicts, no truncated findings. `SRC-4` (two 404s), `SRC-5` (105 unanchored, of which **15 cited a source that did not prove the claim**) and `SRC-6` (`SRC-001` blind to learner-facing citations) are all resolved |
| AUD-04 | [Content-volume and duplication audit](../audit/lot-27-aud04-content-volume/README.md) | clause 8 | **`PASS`** — 2026-09-08. 163 courses, 65,151 body words (median 381, range 184–880), 137 flashcards; every one of the 13,203 course pairs compared exactly. 0 duplicated prose lines, 0 substantially-similar courses, 0 duplicate flashcard questions, 0 outsized courses. `VOL-3` was found **vacuous** by the fail-proof — `SequenceMatcher` autojunk made a verbatim duplicate score 0.02 — and fixed to compare word sequences with autojunk disabled |
| AUD-05 | Question-bank audit | clause 3 | `NOT RUN` |
| AUD-06 | Holdout integrity audit | clause 7 | `NOT RUN` |
| AUD-07 | English readiness audit | clause 6 | `NOT RUN` |
| AUD-08 | Technical, accessibility and production audit | clause 9 | `NOT RUN` |
| AUD-09 | Final rationality and readiness assessment | all | `NOT RUN` — depends on every row above, on the Mock 4 human gate, and on FR-2; it cannot be started while any is unsatisfied |

Every audit moves `NOT RUN` → `RUNNING` → `PASS` / `FAIL` / `BLOCKED`, and no
row may reach `PASS` without persisted evidence under `docs/audit/`. A run that
finds defects is recorded as `FAIL`, never softened.

**Clause 5 is therefore not `PASS`.** AUD-02 clears the version half of it;
AUD-03 fails the anchor half. The clause-5 row above records what its two
audits actually found.

`NOT RUN` is not `PASS`, and it is not `FAIL`. It is the absence of evidence,
written down so that the passes above cannot be mistaken for a finished
assessment.

## Divergences to carry into Lot 27

1. **Clause 2 rests on B-1, and B-1 has changed shape.** The owner supplied the
   syllabus copy on 2026-09-07 and AUD-01 ran against it: 163/163 items appear
   verbatim, nothing in the PDF is missing from the matrix, every constraint
   reconciles, and **two exclusion divergences** were found (`SYL-1`, `SYL-2`).
   B-1 is therefore no longer `BLOCKED` — it carries AUD-01's `FAIL` until those
   are fixed. What has **not** changed is the ceiling: the PDF is byte-identical
   to the artefact the import was made from, so this audit proves transcription
   fidelity, never that the scope matches what Symfony publishes today.
   `certification.symfony.com` is still unreachable and still has no upstream.
   **This remains the one clause the project cannot fully self-certify.**
2. **Clause 7 is met operationally, never absolutely.** ADR-0005 fixes the
   wording, and the owner settled the definition on 2026-09-03 (Option A). No
   report may write "unseen" without the qualifier. The arithmetic half of this
   divergence is **closed**: Mock 4 holds its full 75. The qualifier half never
   closes — functional isolation **yes**, application-level unseen **yes**,
   repository confidentiality **no**, answers readable by anyone who
   deliberately opens the public source **yes**.
3. **Clauses 2, 3 and 8 are marked "as measured".** Each has a Lot 27 audit
   whose job is to test it independently rather than re-read this table.
4. **FR-2 is `REQUIRED_BEFORE_FINAL_READINESS` and is not a §22 clause.** The
   missing French accents in `content_level_justification` and
   `learning_outcomes` for lots 01–11 reach 126 of 163 rendered item pages. No
   gate detects it and none fails. It is one atomic job, done completely or not
   at all: a partial pass destroys the uniformly-zero accented-character signal
   that locates the remaining strings. It is not part of any audit above and
   must not be folded into one.
