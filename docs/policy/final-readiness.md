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

## Measured state — 2026-09-03, commit `1098d75` + this change

| # | §22 clause | What it requires here | State |
|---|---|---|---|
| 1 | 100% atomic official syllabus coverage | EXAM_READY atomic official items ÷ total, per §3.5 | **PASS** — `bin/cert coverage` exit 0: **100% (163/163)**, no report diff |
| 2 | 0 critical syllabus gap | no official item without the content its level requires | **PASS as measured**, pending Lot 27's *independent syllabus audit*. Blocker **B-1** stands: the syllabus has no machine-readable upstream, so the import cannot be diffed against a source — that audit is precisely the check |
| 3 | 0 known incorrect scored answer | no scored question with a wrong key | **PASS as known** — 18 rules, 0 violations over 496 questions; P2.2/P2.3/P2.4 corrected. Systematic re-check is Lot 27's *question-bank audit* |
| 4 | 0 scored OUT_OF_SCOPE dependency | no scored question depending on non-official material | **PASS** — all **496** questions are `classification: OFFICIAL`; zero non-official scored questions |
| 5 | verified Symfony 8.0 sources | every source version-anchored to 8.0 | **PASS** — **496 of 496** `verification_status: VERIFIED`; **0** occurrences of `symfony.com/doc/current` in `content/` or `docs/syllabus/`; CI rejects `/current/` |
| 6 | functioning English timed simulation | a working timed exam mode, in English | **PASS** — `website/src/pages/exam.tsx`, 90-minute official duration, serving `exam.json`: **135 of 135 questions English** |
| 7 | protected unseen holdout assessment | see [ADR-0005](../adr/0005-holdout-distribution-deferred.md) | **PASS under the operational definition in force** — 27 holdout questions, served by **no** application mode; `practice.json` (334) and `exam.json` (135) contain **0**. *Functional isolation, not confidentiality*: the questions are readable in the public source |
| 8 | manageable revision burden | a corpus a candidate can actually revise | **PASS as measured** — 163 courses, **65,151 body words** (median 381, mean 400, range 184–880), 137 flashcards, 496 questions. Roughly 4–5 hours of reading. Lot 27's *content-volume and duplication audit* confirms |
| 9 | technical, pedagogical, accessibility and production gates | §17's gates, each green | **PASS** — validate 18 rules / 0 violations; phpunit **85 tests, 889 assertions**; `bin/cert build` exit 0; site build exit 0; a11y **6 pages, 0 violations**; production: master deployed and smoke-tested each lot through Lot 26 (run `33689975817`, smoke `100446567044`) |

## Divergences to carry into Lot 27

1. **Clause 2 rests on B-1.** Coverage is 100% against the *imported* syllabus.
   Nothing in this repository can prove the import matches the official source,
   because `certification.symfony.com` is unreachable and has no upstream
   repository. Lot 27's independent syllabus audit is the only remaining check,
   and it needs a human-supplied copy of the official syllabus to be worth
   anything. **This is the one clause the project cannot self-certify.**
2. **Clause 7 is met operationally, never absolutely.** ADR-0005 fixes the
   wording. No report may write "unseen" without the qualifier.
3. **Clauses 2, 3 and 8 are marked "as measured".** Each has a Lot 27 audit
   whose job is to test it independently rather than re-read this table.
