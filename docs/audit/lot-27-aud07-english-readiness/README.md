# AUD-07 — English readiness audit

**Master Plan §14, §5 · bears on §22 clause 6**

| | |
|---|---|
| State | `NOT RUN` → `RUNNING` → **`PASS` for the corpus** |
| Run at | 2026-09-08 |
| Commit audited | `335fedf` |
| Script | [`tools/audit/aud07_english_readiness.py`](../../../tools/audit/aud07_english_readiness.py) |
| Raw output | [`output.txt`](output.txt) — exit 0 |
| Checks proved to fire | [`fail-proof.txt`](fail-proof.txt) — 33 of 33 across AUD-02..AUD-07 |

## What this audit does not claim

§5 states four requirements. This audit measures **three**. The fourth —
*"final EXAM_READY status requires acceptable timed performance in English"* —
is the human sitting under exam conditions, and no script closes it. The audit
prints that limit in its own output rather than leaving a reader to infer it.

**Clause 6 is therefore not closed by this row.** The artefact half is settled
(exam mode and Mock 4 exist and are deployed) and the corpus half is measured
here; the performance half is `PENDING_HUMAN_VALIDATION` behind Mock 4.

## Question asked

`RDY-001` and the language policy already record §5. This audit asks whether
the corpus actually meets the thresholds, recomputed from `content/questions/**`
rather than read back from any earlier report.

| id | Check | Result |
|---|---|---|
| `ENG-1` | at least 50% of advanced questions are English | **PASS** — 204/205 = 99.5% |
| `ENG-2` | Mock 3's bank is primarily English | **PASS** — `VALIDATION` 135/135 |
| `ENG-3` | Mock 4 is 100% English | **PASS** — `HOLDOUT` 75/75 |
| `ENG-4` | no non-English question sits in a bank §5 binds | **PASS** — 0 |
| `ENG-5` | the English is readable inside its own time budget | **PASS** — 523/523 |
| `ENG-6` | the §5 glossary exists and is populated | **PASS** — 81 entries |

## `ENG-4` is the check a ratio cannot make

`ENG-1` and `ENG-2` are percentages, and a percentage absorbs a single defect
without moving: one French question in `VALIDATION` would leave the ratio at
99.3% and still be a violation, because §5 permits French for beginner practice
and not in a bank its thresholds bind. `ENG-4` tests the membership rather than
the proportion, so it fires on the first such question regardless of the ratio.

All 21 French questions in the corpus are in `LEARNING`, which is exactly where
§5 permits them. The one advanced question that is not English —
`QST-xms6c8xjd03a` — is `LEARNING` / `fr`, permitted, and counted openly in
`ENG-1`'s denominator rather than excluded to improve the figure.

## `ENG-5` and the convention it uses

A question is not usable in English merely by being tagged `en`; it must be
readable at exam pace. The measure is the corpus's own
`estimated_time_seconds` against the words a candidate must actually read —
stem plus every choice — at **200 wpm**, the same reading convention AUD-04
uses and labelled as such here rather than buried.

No question exceeds its budget. The tightest is `QST-raqy4bsq9e5m` at 87 words
against 40 seconds, needing about 65% of its budget to read; the median
question needs 23%. This is a floor, not a comfort: it shows no question is
*mistimed*, and says nothing about whether a candidate can answer it in the
remainder. That is `ENG-5`'s stated limit.

## Every check proved to fire

The fail-proof injects a targeted defect per check and requires the matching
check to report it, restoring each fixture and verifying the restoration by
SHA-256. All six `ENG-*` checks are proved, bringing the harness to **33 of 33**
across AUD-02 through AUD-07, with every file restored byte-identically.

Zero findings is not a `PASS` in this project — three checks have already been
caught vacuous this way (`VOL-3`, `HOLD-1`, `HOLD-6`). No `ENG-*` check was
found vacuous.

## Documented process deviation — a commit pushed on red gates

The first delivery attempt of this audit pushed commit `335fedf` to
`origin/audit-07-english-readiness` **while `php bin/cert validate` and
`vendor/bin/phpunit` were both failing**, and reported neither.

*Cause.* The command chained the gates and the push with `;` instead of
checking `$?` between them, so the non-zero exits were printed and stepped
over. This is precisely the failure `PROC-1` records, repeated on the push
rather than on a pipeline.

*The defect the gates were reporting.* The register row in
`docs/policy/final-readiness.md` linked this README before the directory
existed, so `LNK-001` fired on a dead internal link and
`CanonicalDataTest::testCanonicalDataLoadsAndPassesTheMandatoryRules` failed on
the same violation. One root cause, both gates, correctly detected.

*Resolution.* The directory and this report now exist, the link resolves, and
the gates were re-run individually with their exit statuses read. **No check
was weakened and no test was skipped** — the rules did their job; the delivery
step ignored them. Recorded here rather than amended away.
