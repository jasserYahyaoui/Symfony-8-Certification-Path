# Post-campaign audit — 2026-09-09

**Base**: `3a70ade` · **Scope**: the whole project, without any mock result.

Every finding below is a measurement or a file, never an impression. Three of my
own probes turned out to be false positives and are recorded as such, because a
proxy that misfires is itself a finding about the audit.

## What was checked and found sound

| Area | Method | Result |
|---|---|---|
| Declared assessment modes vs content | script over the matrix, banks and decks | **0 mismatches** — no item declares `FLASHCARD`, `EXAM` or `QUESTION` it cannot satisfy |
| Exercises | `required_assessment_modes` | **0 items declare `EXERCISE`** — zero exercises is compliance with the project's model, not a gap |
| Prose reuse between courses | every sentence ≥ 12 words, across 163 courses | **0 sentences appear in more than one course** |
| Source liveness | `aud03_source_anchor.py`, online | **167 URLs, all 200, 0 findings** |
| Source form | 1 005 references, 162 distinct URLs | 100% `raw.githubusercontent.com`, **0 `/current/`**, all branch-anchored, all with an anchor or a symbol |
| Rules on disk vs registered | `RuleSet::mandatory()` vs `src/Validation/Rule/` | 21 = 21, every rule referenced by at least one test file |
| Site UX | Playwright/Chromium over the real build, 9 surfaces incl. 390 px | all 200, one `h1` each, search present, prev/next present, **no horizontal overflow, no overflowing table** |
| Answer position | choice order in the banks | correct answer always first in the file — and `QuestionCard.tsx:34` shuffles at run time, so the file order is deterministic by design, not a leak |
| Duplicate questions | normalized stems and answer keys | 0 identical stems; the single same-item/same-key pair (`500`) is two different mechanisms, not a duplicate |

## Findings acted on

### F-1 — a governance document asserted a false measured state (BLOQUANT)

`docs/policy/course-structure.md` carried *"Measured state (2026-09-03): 93
verbatim / 8 descriptive / **62 no trap section**"* and closed audit item P2.1
as `NOT_REQUIRED` on that basis. After the 2026-09-09 campaign every one of the
163 courses has the section, and the file still said 62 did not.

Nothing failed, because **no gate compares a policy's prose with the corpus**.
A reader consulting governance would have concluded that 62 courses
deliberately have no trap section.

Fixed: the state is re-measured and dated, and the P2.1 resolution is recorded
as *made moot*, not overturned — its reading of §4.3 was and remains correct.
§4.3 forbids a mandatory **empty** template; the 69 sections carry 27 to 140
words each, median 64.

### F-2 — no audit script ran in CI (HAUTE VALEUR)

All fourteen scripts under `tools/audit/` were one-shot investigations. Nothing
re-ran them on a push, including:

- `prove_framework_rules_fail.py`, the guard against a rule becoming vacuous —
  **this project has already found five vacuous checks**;
- `aud02_version_contamination.py`, which catches a 7.x reference leaking into
  an 8.0 corpus, an exam-correctness defect no rule sees.

Fixed: twelve deterministic audits plus the vacuity proofs now run on every
push, measured at ~75 s total. `prove_audits_fail.py` is deliberately excluded:
it takes about five minutes and mutates canonical files, which belongs in a
release check rather than on every push.

### F-3 — the correct answer is the longest choice far above chance (HAUTE VALEUR)

Measured over the 537 single-answer questions:

```
correct answer strictly the longest   271/537 = 50.5%
expected by chance (4 choices)                  25.0%
mean length   correct 55 chars, distractor 42
median length correct 49 chars, distractor 41
```

**A candidate who knows nothing and always picks the longest option scores
about 50% on this bank.** That inflates every practice score and trains a
heuristic the real exam will not reward.

The distribution says something sharper still:

| | rate |
|---|---|
| lot-01 — the only lot refined under framework v2 | **20.6%**, below its own chance baseline |
| lot-12 | 79.3% |
| lot-14 | 90.9% |
| lots 15, 17, 18, 19 | **100%** |

So the refinement process already fixes this where it has run, and the bias sits
in the bulk that has not been refined.

Acted on by **measurement, not by rewriting 271 questions**:
`tools/audit/aud10_answer_length_bias.py` reports the figure per lot and fails
only where a lot is recorded as refined — the same staging as `ARC-001`,
`PED-003` and `REV-001`. The threshold is not invented: a refined lot must not
exceed the chance baseline, and lot-01 already sits below it.

Rewriting the 266 questions in unrefined lots is **not** done here. It is
per-lot refinement work, and doing it blind would be a mass edit of verified
content to move a number.

### F-4 — AUD-01 cannot run outside this session (found BY wiring the CI)

Wiring the audits made CI fail on the first run, on `aud01_syllabus_transcription`:

```
ModuleNotFoundError: No module named 'pypdf'
```

That error is the symptom. The cause is line 12 of the script:

```python
PDF = '/root/.claude/uploads/<session-uuid>/…-Symfony_Certification.PDF'
```

An **absolute path into a session upload directory**, whose name carries the id
of the session the file was uploaded to. `git ls-files` shows **no PDF tracked**,
and correctly so — it is the owner's copy of the official syllabus. So AUD-01
runs where that upload lives and nowhere else: not in CI, not in a fresh clone,
not in a later session with a different id.

**Its recorded result stands**: on 2026-09-08 it matched 163 of 163 atomic items
verbatim and closed blocker B-1. That evidence is persisted, and nothing here
re-opens it. What is not reproducible is the *script*, and that distinction was
invisible until something tried to run it somewhere else.

Acted on: AUD-01 is out of the CI set, and the script now says in its own
docstring that it is one-shot and session-bound, so a future
`ModuleNotFoundError` reads as that absence rather than as a corpus regression.

**This is the finding that justifies F-2 on its own.** A script nobody re-runs
decays silently; the only reason this was discoverable is that something finally
tried to run it in a clean environment.

## False positives from my own probes, recorded

| Probe | Why it misfired |
|---|---|
| "6 courses have an objective under 12 words" | All six are complete, well-formed sentences. Word count is not a quality measure for a one-line objective. |
| "26 courses carry no fenced code block" | Nearly all are conceptual (License, RFC 9110, naming conventions). The strongest suspect — `Twig syntax up to 3.22`, `DEEP` — teaches syntax densely through tables and inline code; a fenced block would add nothing. |
| "133 `Sources officielles` sections under 12 words" | They are link lists. The threshold was wrong for that section, which is why `aud09_course_sections.py` exempts it by name. |

The heading-count proxy that drove the trap campaign had already been wrong
once (`Status codes` had the content under another heading).
`aud09_course_sections.py` now reads what is under each heading instead.

## Both new audits are proved non-vacuous

`aud09` was made to fire on an empty heading and on a two-word section, with the
canonical file restored byte-identically under SHA-256. `aud10` carries a
`--prove` flag that lengthens every refined-lot answer **in memory** and asserts
`LEN-1` fires — no file is opened for writing, so the proof runs in CI on every
push.
