# AUD-06 — Holdout integrity audit

**Master Plan §14, §7.3, ADR-0005/0006 · bears on §22 clause 7**

| | |
|---|---|
| State | `NOT RUN` → `RUNNING` → **`PASS`** |
| Run at | 2026-09-08 |
| Commit audited | `1573b88` |
| Script | [`tools/audit/aud06_holdout_integrity.py`](../../../tools/audit/aud06_holdout_integrity.py) |
| Raw output | [`output.txt`](output.txt) — exit 0 |
| Checks proved to fire | [`fail-proof.txt`](fail-proof.txt) — 18 of 18 |

## What this audit does not claim

**Confidentiality.** The repository is public and holdout answers are readable
in `content/questions/*.yml`. ADR-0005 Option A settled that *unseen* means
never served by Practice Mode, Exam Mode or any other learning mode, and every
statement below is bounded by that. Nothing here should be read as evidence
that the questions are secret.

## Question asked

Parts of clause 7 are already enforced — `assertNoHoldoutLeak()` at build time,
`assertMockMatchesBlueprint()` on the mock payload, and the production smoke
test in both directions. This audit is not a fourth copy of those. It asks what
nothing else asks, of the **canonical data** rather than of a payload.

| id | Check | Result |
|---|---|---|
| `HOLD-1` | the holdout is the size the Mock 4 blueprint states | **PASS** — 75 = `official_constraints.questions` |
| `HOLD-2` | no atomic item carries two holdout questions | **PASS** — 75 questions, 75 distinct items |
| `HOLD-3` | the whole holdout is English, as §10 requires | **PASS** — 75/75 |
| `HOLD-4` | no holdout question is referenced as learning material | **PASS** — 0 over 450 matrix references |
| `HOLD-5` | no holdout **answer text** appears outside the holdout | **PASS** — 0 over 932 haystacks |
| `HOLD-6` | the build-time guards still exist | **PASS** — 3 of 3 declared |

`HOLD-5` is the check a pool label cannot make: a correct answer is the thing
worth protecting, so its exact text is searched across every course body, every
flashcard face, and every non-holdout question. Answers under 25 characters are
excluded and the floor is stated, not hidden — 8 of the 75 fall below it, where
a collision would be coincidence rather than a leak.

## `CRS-001`'s exemption, applied unchanged

The first run reported one `HOLD-5` finding: `QST-32aywq9v8vg4`'s answer
`#[Autowire(param: 'kernel.debug')]` inside `CRS-w6yfxm6ad4zy`.

It is **not** a leak. Both belong to the same atomic item
(`OIT-wm3qdqemtap9`, *Services autowiring*), and the occurrence is inside a
fenced code block in the course that teaches exactly that. `CRS-001`'s recorded
rule permits a course to show the code **its own item** teaches, even when a
question on that item tests it — and under Option A the learner is *meant* to
learn it from the course; Mock 4 tests whether they did.

The audit was broader than the project's own policy. It now applies the same
exemption: fenced code in the course of the same item is exempt and **counted**
(1 occurrence, visible in the output), while the same text in another item's
course, or outside a fence, is still a finding. The fail-proof plants a real
holdout answer in a different item's course and requires `HOLD-5` to fire.

## Two vacuous checks, both caught by the fail-proof

Neither could have failed, and both looked correct.

**`HOLD-1` compared the holdout against a hardcoded literal.** It read
`blueprint.get('question_count') or blueprint.get('questions') or 75`. Neither
key exists — the count lives at `official_constraints.questions` — so it fell
through to the `or 75` default and compared 75 against 75 forever. A missing
key is now a finding, never a default.

**`HOLD-6` used a substring test.** `assertNoHoldoutLeak` is a *prefix* of
`assertNoHoldoutLeakRENAMED`, so renaming the guard away left the substring
present and the check reported it healthy. It now matches a declaration
(`function <name>(`). This is the same trap already recorded in this project
for `esi` matching inside `SameSite`.

Together with AUD-04's `VOL-3`, that is **three vacuous checks found in two
audits** — every one of them by injecting a real defect and demanding the
matching check fire.

## Observation, not a finding

`docs/mocks/mock-4-blueprint.yml` still carries
`status: "FILLED — the 48 questions exist (Unit B); Mock 4 itself is not
implemented yet (Unit C)"`. Mock 4 has been implemented and deployed since
Unit C. This is documentation drift, not a holdout-integrity defect, and it
belongs to the reconciliation unit rather than to this audit's diff.
