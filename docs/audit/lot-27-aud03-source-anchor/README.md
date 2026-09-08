# AUD-03 — Source and anchor audit

**Master Plan §14, §2.2–§2.4 · bears on §22 clause 5**

| | |
|---|---|
| State | `NOT RUN` → `RUNNING` → `FAIL` (2026-09-07) → **`PASS`** (2026-09-08) |
| Commit audited | SRC-5 + SRC-6 complete |
| Script | [`tools/audit/aud03_source_anchor.py`](../../../tools/audit/aud03_source_anchor.py) |
| Raw output | [`output.txt`](output.txt) — exit 0 |
| Checks proved to fire | [`fail-proof.txt`](fail-proof.txt) — 9 of 9 |
| Ledger | [`../lot-27-src5-citation-verification/ledger.csv`](../lot-27-src5-citation-verification/ledger.csv) |

## Result — `PASS`

| Criterion | Result |
|---|---|
| Citations inspected | **918** (550 question, 225 course, 143 flashcard) |
| Distinct URLs | **167**, every one fetched live, **all 167 at HTTP 200** |
| Missing anchors | **0** |
| Invalid URLs | **0** |
| Unsupported claims | **0** — 15 were found and repaired; see the ledger |
| Unresolved conflicts | **0** — one was raised, decided by the owner, and resolved against PHP 8.4 primaries |
| Invisible or truncated findings | **0** — output is grouped per check |
| `SRC-001` coverage | the whole learner-facing corpus, at **`Error`** severity |
| Regression and fail-proof tests | 18 SRC-6 tests; 9 of 9 audit checks proved to fire |

`PASS` is claimed on the conjunction, not on the anchor count. Zero missing
anchors was necessary and never sufficient: the 15 records whose source did not
prove its claim all had, or would have had, an anchor.

## History — this audit failed first, and that was the point

The 2026-09-07 run reported **105 unanchored citations** and stood at `FAIL`.
Verifying them one at a time found something larger than an annotation gap:
**15 of 105 cited a source that did not prove the claim assigned to it** — 6
replaced outright, 9 completed with the source that carried the missing part.
That was invisible precisely because no anchor was required. With nothing to
point at, nobody had to check there was anything to point to.

Two dead citations (HTTP 404) were also found, in the first run's `ANCHOR-8`
check — the first time this project had ever fetched a source URL.

## Question asked

AUD-02 asks whether a source points at the right *version*. This audit asks the
three questions that come before it: does every taught or scored record cite a
source at all, does every citation carry an anchor a reader can follow, and
does every cited URL actually resolve?

§2.4 is the standard: *"For documentation, the exact page, section anchor,
target version and verification date are required. A documentation homepage is
not evidence for a precise technical claim."*

## Checks

| id | Check | Result |
|---|---|---|
| `ANCHOR-1` | every question, course and flashcard cites at least one source | **PASS** — 0 |
| `ANCHOR-2` | every citation carries a URL | **PASS** — 0 |
| `ANCHOR-3` | every source URL is https | **PASS** — 0 |
| `ANCHOR-4` | every citation carries `symbol_or_lines` **or** `anchor` | **FAIL — 105** |
| `ANCHOR-5` | every citation carries `verified_at` | **PASS** — 0 |
| `ANCHOR-6` | `verified_at` parses as a date | **PASS** — 0 |
| `ANCHOR-7` | `verified_at` is not in the future | **PASS** — 0 |
| `ANCHOR-8` | every distinct source URL resolves, checked live | **PASS after repair** — see below |

## Findings, and where they went

| id | Finding | State |
|---|---|---|
| `SRC-4` | two citations returned HTTP 404 | **Resolved** in the audit unit |
| `SRC-5` | 105 citations with no anchor | **Resolved** — 105 of 105 verified; 90 `ANCHOR_ADDED`, 6 `SOURCE_REPLACED`, 9 `SOURCE_COMPLETED` |
| `SRC-6` | `SRC-001` never inspected learner-facing citations | **Resolved** — see below |
| conflict | `QST-fhrga35d77wa`: answer key vs a narrower manual page | **Resolved** by owner decision, against PHP 8.4 engine source and a controlled reproduction |

## Finding SRC-6 — the systemic cause: `SRC-001` never looks here

The anchor requirement is not unenforced by oversight in one lot. It is
structurally unreachable for this content.

`SourceRef::hasAnchor()` exists and is correct — it accepts either
`symbol_or_lines` or `anchor`. Rule `SRC-001` calls it. But `SRC-001` iterates
`$content->matrix->officialItems()` and inspects **the matrix items'** own
sources. The citations on courses, questions and flashcards — 907 of them, the
ones a learner actually follows — are never passed to it. And where it does
run, `hasAnchor()` raises `Severity::Warning`, which does not fail a build.

So the invariant has been unchecked for the life of the project, and every gate
passed throughout. This is the fourth instance of one pattern already recorded
here as `SPLICE-1`, `SPLICE-2` and `COG-1`: **an invariant nothing checks is
not an invariant.**

## Superseded — why SRC-5 was not repaired in the audit unit

Repairing 105 citations means, for each, reading the record's claim, fetching
the cited source, locating the passage that supports it, and quoting that
passage. The two `SRC-4` repairs above show the shape and the cost: one of them
also required rejecting the *plausible* source (the method reference page) in
favour of the one that actually carries the claim.

Doing that 105 times inside this audit unit would mean writing anchors faster
than they can be verified, which produces exactly the artefact this audit
exists to detect — a citation that looks precise and is not. The repair is
`SRC-5`, its own unit, followed by strengthening `SRC-001` so the class cannot
recur.

That was the position on 2026-09-07. `SRC-5` is now done, and each of the
105 carries a verdict in the ledger.
