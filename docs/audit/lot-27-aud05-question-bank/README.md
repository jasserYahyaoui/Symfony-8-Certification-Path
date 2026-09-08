# AUD-05 — Question-bank audit

**Master Plan §14, §7.1–§7.3, §9 · bears on §22 clause 3**

| | |
|---|---|
| State | `NOT RUN` → `RUNNING` → **`PASS`** |
| Run at | 2026-09-08 |
| Commit audited | `cdac2ce` |
| Script | [`tools/audit/aud05_question_bank.py`](../../../tools/audit/aud05_question_bank.py) |
| Raw output | [`output.txt`](output.txt) — exit 0 |
| Checks proved to fire | [`fail-proof.txt`](fail-proof.txt) — 27 of 27 |

## What this audit cannot do, said first

Clause 3 asks for **"0 known incorrect scored answer"**. No script can read a
question and know whether its key is right. This audit finds the *shapes* in
which a wrong answer hides. Key correctness rests on the `AUD-1` professor
audit and, ultimately, on the human sitting — **clause 3 is never closed by
this file alone**, and its register entry says so.

It also does not repeat the 18 mandatory rules. `QST-001` already requires a
resolvable item, at least one key, an explanation on every distractor and on
the question, a cited source, and no scored `UNKNOWN_NEEDS_VERIFICATION`;
`DUP-001` covers duplicates and near-duplicates, `COG-001` the cognitive level,
`POOL-001`/`002` the pools, `SCOPE-001` the exclusions. This asks what is left.

## Checks

| id | Check | Result |
|---|---|---|
| `QB-1` | `required_answer_count` equals the real key count | **PASS** — 0 |
| `QB-2` | `answer_mode` matches the key count | **PASS** — 0 |
| `QB-3` | no question a learner cannot get wrong | **PASS** — 0 |
| `QB-4` | no two choices with the same text | **PASS** — 0 |
| `QB-5` | no catch-all choice (*all of the above*, …) | **PASS** — 0 |
| `QB-6` | `estimated_time_seconds` within 20–180 | **PASS** — 0 |
| `QB-7` | `official_item` resolves | **PASS** — 0 |
| `QB-8` | question and choice ids unique bank-wide | **PASS** — 0 |
| `QB-9` | every atomic item carries at least one question | **PASS** — 0 |

`QB-1` is the one worth naming. `QST-001` requires *"at least one correct
answer"* and stops there — a question declaring two keys while carrying three
is scored wrongly for every learner and **no mandatory rule sees it**.

`QB-4` compares **case-sensitively on purpose**. The earlier Lot 27 unit-2 pass
lowercased first and flagged `QST-whsz8qfwgcby`, whose whole point is that
`getLanguages()` normalises case — `fr_FR` and `FR_fr` are different answers
there. That was the detector's fault and the lesson is kept.

## Measured

| | |
|---|---|
| Questions | **544** — LEARNING 334, VALIDATION 135, HOLDOUT 75 |
| Choices | **2,184**, all ids distinct |
| Answer modes | single 527, multiple 17 |
| Items carrying questions | **163 of 163**, 2 to 5 each |

The earlier Lot 27 unit-2 pass measured **496** questions. That figure is now
stale by 48 and is superseded by this run rather than reused.

## Fail-proof

All nine checks proved to fire against a targeted injected defect — a declared
count that no longer matches, a single-answer question given a second key, a
catch-all distractor, an implausible time, an unresolvable item, a two-choice
question, twin distractor texts, a reused choice id, and a matrix item with no
question. Every fixture restored byte-identically, sha256 verified.

The harness gained a fixture builder so a question can be constructed from real
field names rather than pasted, and the four `APPEND` cases carry valid YAML
the audit's own loader accepts.
