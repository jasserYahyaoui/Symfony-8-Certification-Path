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

## Measured state — 2026-09-15, `master` at `ef52ab3` + this change

Every figure below was re-derived from the canonical files (`content/**`,
`docs/syllabus/syllabus-matrix.yml`) **by script**, never from an earlier report
and never from this table's previous revision. The previous revision, dated
2026-09-07, understated the corpus by **172 questions, 92 tests, 16
accessibility surfaces and 5 rules** — a stale state document reading like a
current one, which is finding **A-5** of
[AUD-09](../audit/lot-27-aud09-final-readiness/README.md).

| # | §22 clause | What it requires here | State |
|---|---|---|---|
| 1 | 100% atomic official syllabus coverage | EXAM_READY atomic official items ÷ total, per §3.5 | **PASS** — `bin/cert coverage` exit 0: **100% (163/163)**, no report diff. Level distribution, stated as an **observation and never a target**: 125 STANDARD, 27 MINIMAL, 11 DEEP |
| 2 | 0 critical syllabus gap | no official item without the content its level requires | **PASS as measured, with a standing limit.** 163/163 items over 14 official topics, **603 identified learning outcomes**, `AUD-01` `PASS` and blocker `B-1` closed. The limit does not lift: the import was made from the same PDF that certifies it, so nothing corroborates the scope against a second witness, and `certification.symfony.com` is unreachable. The published constraint says *15 topics* where the PDF renders **14** headings — recorded by the syllabus gate, not re-opened here |
| 3 | 0 known incorrect scored answer | no scored question with a wrong key | **PASS as known** — **23 rules**, 0 blocking violations over **716 questions** and **2,872 choices**; `AUD-05` 0 findings. *Known* remains the operative word: no human reviewer has read the 695 English questions, and no script can decide whether a key is correct |
| 4 | 0 scored OUT_OF_SCOPE dependency | no scored question depending on non-official material | **PASS** — **716/716** `classification: OFFICIAL` |
| 5 | verified Symfony 8.0 sources | every source version-anchored to 8.0 | **PASS** — **1,138 citations**, **177 distinct URLs**, **177/177 returning `200`** when actually fetched on 2026-09-15; **0** occurrences of `/current/`; `AUD-02` reads all 1,138 sources with 0 findings and 0 non-raw; **163/163** courses `VERIFIED`. Each citation now also carries `readable_url`, held to the raw one by rule `SRC-002` |
| 6 | functioning English timed simulation | a working timed exam mode, in English | **PASS for the artefact — `PENDING_HUMAN_VALIDATION` for the sitting.** `/exam` serves `exam.json`, **136 of 136 English**; `/mock-4` runs 75 questions at 90 minutes, **75 of 75 English**. Both deployed and smoke-tested. **Mock 4 has not been sat**, and that half is the project's only open blocker |
| 7 | protected unseen holdout assessment | see [ADR-0005](../adr/0005-holdout-distribution-deferred.md) | **PASS on Option A, with the permanent qualifier.** **75 questions across 75 distinct atomic items, 308 choices, 75/75 English.** Proved against the built bytes: `practice.json` (505 LEARNING), `exam.json` (136 VALIDATION) and the four training mocks carry none of the 75 ids nor their 308 choice ids, while `mock-4.json` carries the whole holdout and nothing else — both directions asserted at build time and re-proved by the production smoke test. Functional isolation **yes**; **repository confidentiality NO** — the repository is public and the answers are readable by anyone deliberately opening the source |
| 8 | manageable revision burden | a corpus a candidate can actually revise | **PASS as measured** — 163 courses, **71,680 body words** (median 421, mean 440, range 242–880, front matter excluded), **174 flashcards**, 716 questions. `AUD-04` 0 findings |
| 9 | technical, pedagogical, accessibility and production gates | §17's gates, each green | **PASS, the pedagogical one still the least instrumented.** Measured 2026-09-15, each command run on its own with its exit code read (PROC-1): validate **23 rules** / 0 blocking; coverage 100% with no diff; phpunit **287 tests, 15,576 assertions**; 12 content audits **0 findings**; 4 non-vacuity proofs green; `bin/cert build` exit 0; site build `SUCCESS` (210 pages); typecheck exit 0; navigation **209 reachable of 210**; a11y **28 surfaces, 0 violations**; three browser verifiers green; production deployed and smoke-tested. `AUD-08` `PASS`. The **pedagogical** word is the one no command measures |

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
Lot 27 §14 deliverable. **All nine have now been executed**; the section keeps
its name and its history rather than being retitled, because what each audit
found is the point, not that the list is complete:

| # | Audit | Bears on | State |
|---|---|---|---|
| AUD-01 | [Independent syllabus audit](../audit/lot-27-aud01-independent-syllabus/README.md) | clause 2 | **`PASS`, and blocker `B-1` closed with it** — 2026-09-08, after failing on 2026-09-07. Ran against the owner-supplied PDF (sha256 `4ee8b962…`, 468,963 bytes, 5 pages). Transcription 163/163 verbatim, the reverse direction clean, every constraint reconciled, and the exclusion record now holds 13 entries against the PDF's 13. **Blocker `B-1` is `PASS`, closed 2026-09-08** — the owner supplied all 26 completion conditions and the audit report assesses every one: **26 met and evidenced**, condition 24 by PR #74 (`852b650`, smoke `102067762513`) and PR #76 (`ee6fd36`, smoke `102078807138`), both read line by line. **Caveat that does not go away:** the PDF is byte-identical to the one the import was made from, so this is transcription fidelity, not independent corroboration of scope |
| AUD-02 | [Version-contamination audit](../audit/lot-27-aud02-version-contamination/README.md) | clause 5 | **`PASS`** — 2026-09-07, 0 findings over 907 citations; one real gap found and fixed (`php/php-src` undeclared in the source map) |
| AUD-03 | [Source and anchor audit](../audit/lot-27-aud03-source-anchor/README.md) | clause 5 | **`PASS`** — 2026-09-08, after failing on 2026-09-07. 918 citations inspected, 167 distinct URLs all at HTTP 200, 0 missing anchors, 0 unsupported claims, 0 unresolved conflicts, no truncated findings. `SRC-4` (two 404s), `SRC-5` (105 unanchored, of which **15 cited a source that did not prove the claim**) and `SRC-6` (`SRC-001` blind to learner-facing citations) are all resolved |
| AUD-04 | [Content-volume and duplication audit](../audit/lot-27-aud04-content-volume/README.md) | clause 8 | **`PASS`** — 2026-09-08. 163 courses, 65,151 body words (median 381, range 184–880), 137 flashcards; every one of the 13,203 course pairs compared exactly. 0 duplicated prose lines, 0 substantially-similar courses, 0 duplicate flashcard questions, 0 outsized courses. `VOL-3` was found **vacuous** by the fail-proof — `SequenceMatcher` autojunk made a verbatim duplicate score 0.02 — and fixed to compare word sequences with autojunk disabled |
| AUD-05 | [Question-bank audit](../audit/lot-27-aud05-question-bank/README.md) | clause 3 | **`PASS` on structure, and structure is not correctness** — 2026-09-08. 544 questions, 2,184 choices all with distinct ids, 163/163 items carrying 2–5 questions each; 0 findings over 9 checks, each proved to fire. `QB-1` covers what `QST-001` does not: a declared answer count that disagrees with the real keys. **No script can decide whether a key is correct**, so clause 3 rests on the `AUD-1` professor audit and the human sitting, not on this file |
| AUD-06 | [Holdout integrity audit](../audit/lot-27-aud06-holdout-integrity/README.md) | clause 7 | **`PASS`** — 2026-09-08. 75 holdout questions across 75 distinct items, all English, matching the blueprint's `official_constraints.questions`; 0 referenced as learning material over 450 matrix references; 0 holdout answers found across 932 haystacks, with `CRS-001`'s own-item fenced exemption applied and its single occurrence counted. Two of its checks were found **vacuous** by the fail-proof — `HOLD-1` compared against a hardcoded literal, `HOLD-6` used a substring that a rename left intact — and both are fixed. **Confidentiality is not claimed**: the repository is public and holdout answers are readable in `content/questions/*.yml` |
| AUD-07 | [English readiness audit](../audit/lot-27-aud07-english-readiness/README.md) | clause 6 | **`PASS` for the corpus** — 2026-09-08. §5's three measurable requirements all met and recomputed independently: advanced questions 204/205 = 99.5% English, `VALIDATION` 135/135, `HOLDOUT` 75/75, 0 non-English questions in a bound bank, all 523 English questions readable inside their own time budget, glossary 81 entries. **§5's fourth requirement — acceptable timed performance in English — is the human sitting and no script closes it** |
| AUD-08 | [Technical, accessibility and production audit](../audit/lot-27-aud08-technical-production/README.md) | clause 9 | **`FAIL` on first run, `PASS` after the gap was closed** — 2026-09-08. It audits the **coverage of §17's gates**, never their verdicts: 9/9 application routes, 3/3 generated syllabus pages and 8/8 payloads smoke-tested; 18 rule classes on disk all registered; 0 tests skipped; 0 generated files tracked; 5/5 `gate-full` gates also run by CI. The first run **failed**: `/docs/syllabus/coverage` and `/docs/syllabus/exclusions` were served to learners and **never accessibility-audited**, while the audit's own comments already excused other screens by citing *"the audited coverage page"*. Closed by **adding both pages to the audit** — 14 surfaces, 0 violations, passing on their first audited run. `TECH-7` was found **vacuous before it ever ran** (it compared the rule array against a count of itself) and rewritten to compare rule classes on disk against those registered |
| AUD-09 | [Final rationality and readiness assessment](../audit/lot-27-aud09-final-readiness/README.md) | all | **`PASS_WITH_BLOCKER`** — 2026-09-15. Every clause re-measured from the canonical files by script; **five findings, four of them fixed in the same pass**, three being gaps between what the project claimed and what it did. The eight instrumentable clauses hold on measured evidence; clause 6's sitting half remains `PENDING_HUMAN_VALIDATION`, and §22's last line forbids the other eight from compensating for it |

Every audit moves `NOT RUN` → `RUNNING` → `PASS` / `FAIL` / `BLOCKED`, and no
row may reach `PASS` without persisted evidence under `docs/audit/`. A run that
finds defects is recorded as `FAIL`, never softened.

**Clause 5 is `PASS`, and this paragraph used to deny it.** It read *"Clause 5
is therefore not `PASS` … AUD-03 fails the anchor half"* — written on
2026-09-07, when that was true, and left standing after AUD-03 passed on its
second run the next day. The AUD-03 row three lines above it said `PASS` the
whole time. A governance document contradicting itself is worse than a stale
figure, because each half looks authoritative; AUD-09 records it as finding
**A-6**. Both halves of clause 5 now hold: AUD-02 clears the version anchoring,
AUD-03 clears the passage anchoring and the reachability.

`NOT RUN` is not `PASS`, and it is not `FAIL`. It is the absence of evidence,
written down so that the passes above cannot be mistaken for a finished
assessment. No row carries it today.

## Divergences to carry into Lot 27

1. **Clause 2 rests on B-1, and B-1 has changed shape.** The owner supplied the
   syllabus copy on 2026-09-07 and AUD-01 ran against it: 163/163 items appear
   verbatim, nothing in the PDF is missing from the matrix, every constraint
   reconciles, and **two exclusion divergences** were found (`SYL-1`, `SYL-2`).
   `SYL-1` and `SYL-2` were fixed in PR #68 (merge `8dd3259`), AUD-01's `FAIL`
   is lifted, and **B-1 is now `PASS`** — all 26 of the owner's completion
   conditions met and evidenced in the audit report.
   What has **not** changed, and what closing B-1 does not touch, is the
   ceiling: the PDF is byte-identical
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
4. **FR-2 is `DONE`, verified in production on 2026-09-08.** It was
   `REQUIRED_BEFORE_FINAL_READINESS` and is not a §22 clause. 126 of 126 items,
   **619 of 734 strings, 1,537 word occurrences, 456 distinct corrections**,
   delivered whole in PR #78 (merge `b5df678`), smoke `102126174046`.
   [Specification](fr-2-specification.md) ·
   [report](../audit/lot-27-fr-2/README.md).
   Its **recorded scope was short by a field**: `minimum_evidence` carried the
   identical signature and was in nobody's scope, so the real scope was 734
   strings over three fields, not 608 over two — FR-3's lesson that a recorded
   scope is a claim, not a measurement, applied to FR-2 itself.
   The "uniformly zero accented characters" property was the **detection tool**,
   never the definition of done: 115 of the 734 strings needed no change.
   Every audit re-earned its `PASS` on the changed corpus rather than carrying
   an earlier result forward, and the production smoke test now **greps the
   published pages for the pre-FR-2 spellings on every deploy**, so a
   regression fails the build instead of going unnoticed as the original defect
   did.
