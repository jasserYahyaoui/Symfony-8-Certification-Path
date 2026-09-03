# Question and course language policy

This policy does not invent a rule. It writes down Master Plan **§5**, which
already governs language, and records what the corpus currently measures
against it. Audit item **P2.5** asked for exactly this: a written policy. It did
*not* establish that the French questions are defects — §5 permits them.

## What §5 requires

Quoted from the Master Plan:

> The principal explanations may be in French. Official API names, keywords,
> classes, interfaces, configuration keys and technical terms remain in English.
>
> Because the official exam is in English:
>
> - beginner practice may be in French;
> - advanced Practice Mode must progressively introduce English wording;
> - at least 50% of advanced questions must be in English;
> - Mock 3 must be primarily in English;
> - Mock 4, the official-format simulation, must be 100% in English;
> - final `EXAM_READY` status requires acceptable timed performance in English;
> - maintain a French-to-English certification glossary;
> - include common English question formulations and negations without
>   artificial trickery.

## How this project reads it

- **`difficulty: hard` is the operational meaning of "advanced."** The corpus
  has three difficulties; `hard` is the only one that denotes advanced practice.
  The 50% threshold is therefore measured over `hard` questions.
- **The pools carry the mock requirements.** `VALIDATION` is the exam-mode bank
  ([ADR-0006](../adr/0006-exam-mode-serves-the-validation-pool.md)) and
  `HOLDOUT` is the final-mock reserve, so the Mock 3 and Mock 4 language
  requirements bind those two pools.
- **A French question is not a defect.** §5 permits French; only the thresholds
  above bind. Translating a compliant French question buys nothing and is
  forbidden by §1.4's net-value gate.

## Measured state (2026-09-03, 496 questions)

| §5 requirement | Measured | Verdict |
|---|---|---|
| ≥ 50% of advanced (`hard`) questions in English | 201 of 202 = **99.5%** | **PASS** |
| Mock 3 primarily English (`VALIDATION`) | 135 of 135 = **100%** | **PASS** |
| Mock 4 100% English (`HOLDOUT`) | 27 of 27 = **100%** | **PASS** |
| Beginner practice may be French (`LEARNING`) | 21 French, all `LEARNING` (7 `easy`, 13 `medium`, 1 `hard`) | **PASS** — permitted |
| French-to-English certification glossary | **absent** | **MISSING** |
| Acceptable timed performance in English | not yet exercised | **NOT_APPLICABLE** until the mocks are sat |

Whole corpus: **475 English, 21 French**.

## The one open obligation

The **glossary does not exist**. It is a standing §5 requirement, not a lot
deliverable, and it is the only part of §5 this corpus does not satisfy. It
belongs with Lot 27's *English readiness audit* (§14), which is the first point
at which the terms that actually need glossing are known.

## What this policy forbids

- Translating the 21 French `LEARNING` questions to raise an English
  percentage. The thresholds are already met with room to spare, and §5 permits
  French beginner practice explicitly.
- Adding English questions to move a ratio. Level and language distribution are
  outcomes of what each item needs, never targets — the same rule that governs
  `MINIMAL`/`STANDARD`/`DEEP`.
- Translating an API name, keyword, class, interface or configuration key.
  §5 keeps those in English inside otherwise-French prose.
