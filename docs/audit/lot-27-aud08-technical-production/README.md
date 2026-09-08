# AUD-08 — technical, accessibility and production audit

**Master Plan §14, §13, §17 · bears on §22 clause 9**

| | |
|---|---|
| State | `NOT RUN` → `RUNNING` → **`FAIL` on first run, `PASS` after the gap was closed** |
| Run at | 2026-09-08 |
| Commit audited | `852b650` + this unit |
| Script | [`tools/audit/aud08_technical_production.py`](../../../tools/audit/aud08_technical_production.py) |
| Raw output | [`output.txt`](output.txt) — exit 0 |
| Checks proved to fire | [`fail-proof.txt`](fail-proof.txt) — 41 of 41, plus [`tech8-manual-proof.txt`](tech8-manual-proof.txt) |

## Question asked

Clause 9 asks for *"§17's gates, each green"*. Running those gates again would
only reproduce what `composer gate-full` and CI already print, and this project
has learned what a clean run is worth on its own. So this audit asks the
question a green gate cannot answer about itself:

> **Does each gate actually cover the thing it is supposed to be a gate on?**

A gate that runs cleanly over half the site is green and useless, and nothing
in the build says so. The subject here is therefore the **coverage of the
gates**, never their verdicts.

| id | Check | Result |
|---|---|---|
| `TECH-1` | every application route is smoke-tested | **PASS** — 9/9 |
| `TECH-2` | every generated syllabus page is smoke-tested | **PASS** — 3/3 |
| `TECH-3` | every generated payload is smoke-tested | **PASS** — 8/8 |
| `TECH-4` | every published page is accessibility-audited | **FAIL → PASS** — 2 gaps, closed below |
| `TECH-5` | no accessibility justification cites an unaudited page | **FAIL → PASS** — 1, closed below |
| `TECH-6` | no test is skipped or incomplete at runtime | **PASS** — 0 |
| `TECH-7` | every rule that exists is registered, so it runs | **PASS** — 18 on disk, 18 registered |
| `TECH-8` | the generated trees are untracked (ADR-0003) | **PASS** — 0 tracked |
| `TECH-9` | every gate `composer gate-full` runs is also run by CI | **PASS** — 5/5 |

## What the first run found

**Two pages were served to learners and never accessibility-audited.**
`/docs/syllabus/coverage` and `/docs/syllabus/exclusions` are generated,
published, and checked by the production smoke test — and both were absent from
the audit's `PAGES` list. The audit reported `PASS 12 surfaces, 0 violations`
every time, and 12 was never the number of pages.

CLAUDE.md states that `MISSING` is not an acceptable value for the
accessibility gate. This was exactly that `MISSING`, hidden behind a green run
over the *other* pages.

**And the audit was already citing one of them as audited.** Its own comments
excuse the un-visited mock sitting and results screens on the grounds that they
*"mirror the audited coverage page"* and reuse *"the same table and list
primitives as the coverage page above"*. There was no coverage page above. Two
justifications rested on an audit that did not happen — `TECH-5` is the check
that reads a justification and tests the claim inside it.

## How it was closed — by strengthening the gate, never the audit

`/docs/syllabus/coverage` and `/docs/syllabus/exclusions` were **added to the
accessibility audit**. Nothing was excused, no exemption list was invented, and
the finding was not narrowed to make it pass.

Both pages then **passed on their first audited run**: 14 surfaces, axe 0,
structural 0. That is the honest result and it is worth stating plainly — the
defect was never a broken page, it was a gate that had never looked. Had they
failed, the fix would have been the pages.

## `TECH-7` was rewritten because the obvious version was vacuous

The first version compared the rules parsed from `RuleSet::mandatory()` against
the count `bin/cert validate` prints. That count is
`count($validator->rules())` — **the same array**, so the two could never
disagree, whatever was added or removed. It would have reported `PASS` forever.

It now compares the rule classes **on disk** against the set **registered** in
`RuleSet::mandatory()`, which is how an invariant stops being enforced while
every gate stays green — the pattern already recorded here as `SRC-6`,
`SPLICE-1`, `SPLICE-2` and `COG-1`. The count validate prints is still reported,
as a count and not as a check.

This is the **fourth vacuous check** caught in this audit series, after `VOL-3`,
`HOLD-1` and `HOLD-6`. Unlike those three it was caught by reading the check
rather than by the fail-proof, because a tautology passes its own injection test
as happily as a real check would fail it.

## Every check proved to fire

The harness injects a targeted defect per check and requires the matching check
to report it, restoring each file and verifying the restoration by SHA-256.
Every AUD-08 case **removes coverage** rather than breaking a gate — that is the
failure this audit exists to catch.

**41 of 41** across AUD-02 through AUD-08, every file restored byte-identically.

`TECH-8` is the exception and is recorded as one: it reads the **git index**,
which the harness restores no part of, so it is proved by a recorded manual
injection in [`tech8-manual-proof.txt`](tech8-manual-proof.txt) — a generated
payload force-added to the index, `TECH-8` firing, then the index restored to
zero tracked generated files.

`TECH-7`'s case unregisters a rule from `RuleSet::mandatory()`. That is an
injected defect restored byte-identically within one process, never a
governance change to the rule set.

## What this audit does not claim

**It does not re-verify the gates' verdicts.** `validate`, `phpunit`, the site
build and the accessibility audit report their own results, and this file takes
them as given rather than re-deriving them.

**Course pages are covered as a class, not individually.** The smoke test
fetches the docs index and one representative item page, not all 163 course
pages, and `TECH-1` and `TECH-2` do not require it to. A single broken course
page would not be caught in production by this or any other check here.

**Clause 9's pedagogical word is still not measured by any command.** AUD-08 is
the clause's own audit and it measures the technical, accessibility and
production thirds. The pedagogical third rests on `AUD-1`'s professor audit and
the human sitting.
