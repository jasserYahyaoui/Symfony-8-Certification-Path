# AUD-04 — Content-volume and duplication audit

**Master Plan §14 · bears on §22 clause 8 (manageable revision burden)**

| | |
|---|---|
| State | `NOT RUN` → `RUNNING` → **`PASS`** |
| Run at | 2026-09-08 |
| Commit audited | `8dd3259` |
| Script | [`tools/audit/aud04_content_volume.py`](../../../tools/audit/aud04_content_volume.py) |
| Raw output | [`output.txt`](output.txt) — exit 0 |
| Checks proved to fire | [`fail-proof.txt`](fail-proof.txt) — 13 of 13 |

## Question asked

Clause 8 asks whether the corpus is *"a corpus a candidate can actually
revise"*. That is two questions, and only the first is arithmetic:

1. is the **volume** manageable, measured the way CLAUDE.md requires — body
   words, front matter excluded;
2. is any of that volume **duplicated**, so a learner reads the same thing
   twice under two headings?

The second is the one that matters. Volume can look reasonable while a third of
it repeats, and `CRS-5` (Lot 13) is this project's own precedent: a course that
restated another almost entirely, caught by `CRS-001` rather than by a size
check.

## Checks

| id | Check | Result |
|---|---|---|
| `VOL-1` | no course runs far past the corpus median | **PASS** — 0 |
| `VOL-2` | no identical prose line (≥ 60 chars) in two courses | **PASS** — 0 |
| `VOL-3` | no two courses are substantially the same text | **PASS** — 0 over 13,203 pairs |
| `VOL-4` | no two flashcards ask the same question | **PASS** — 0 |

The Master Plan sets no numeric threshold for clause 8, and this audit does not
invent one as a pass line. It **measures** volume and reports it; it **fails**
only on duplication, which is a defect, and on a course judged an outlier
against this corpus's own distribution (more than four times the median) rather
than against a guess.

## Measured

| | |
|---|---|
| Courses | 163 |
| Body words | **65,151** — median 381, mean 400, range 184–880 |
| Flashcards | 137 |
| Course pairs compared | 13,203 (every pair, exactly) |
| Estimated reading time | ~326 minutes at 200 wpm |

The 200 wpm rate is a reading-rate convention, **not** a Master Plan figure,
and is labelled as such wherever it appears. The 65,151 matches the figure
clause 8 already carried, recomputed here rather than copied.

## A vacuous check, caught by the fail-proof

`VOL-3` compared whole courses with
`difflib.SequenceMatcher(None, a, b).ratio()` over character strings. That
looked right and could never have worked.

`SequenceMatcher`'s **autojunk** heuristic treats any element occurring in more
than 1% of a sequence longer than 200 as junk. On character strings that is
every common letter and every space. A course pasted verbatim into another
scored **0.02** — far under the 0.60 threshold — so the check would have
reported nothing however duplicated the corpus became.

It was found only because the fail-proof injects a real defect and demands the
matching check fire. Comparing **word sequences** with `autojunk=False` gives
0.75 for that same pasted duplicate and 0.12 for an unrelated pair.

This is the audit's own lesson, and the fourth of its kind here after
`SPLICE-1`, `SPLICE-2` and `COG-1`: **zero findings is not a result until the
check is shown capable of producing one.**

## Two fail-proof cases that were themselves wrong

Recorded because both produced a passing-looking harness that proved nothing:

1. `VOL-2`'s injected line was first planted by an external wrapper into
   `CRS-2s6e4qgkcqza.md` — the same file `VOL-3` targets. The two cases
   interfered and `VOL-3` appeared not to fire when it did. `VOL-2` now lifts a
   real line from a donor course and needs no seeding.
2. The donor line was then picked from the course's **YAML front matter**,
   which the audit never scans — so the case silently no-opped. The picker now
   strips front matter and applies the audit's own fence filter, so it can only
   choose a line the check would actually collect.
