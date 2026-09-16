# Revision budget

**Rule:** `REV-001` · **Criterion:** `R14_revision_budget` ·
**Decision:** [ADR-0007](../adr/0007-refinement-framework-v2.md), budgets
recalibrated by [ADR-0008](../adr/0008-revision-budget-recalibration.md)

## The one measure where more is worse

CLAUDE.md forbids counting files or lines as progress. This rule counts words
for the opposite purpose: a course that costs more to revise is **worse**, not
further along. The Master Plan's lot-reporting rule already names the unit —
"course size as body words, excluding YAML front matter" — and this is that
unit turned into a ceiling.

## How body words are counted

Whitespace-separated tokens of the Markdown body, front matter excluded.

`Course::wordCount()` deliberately does **not** use PHP's `str_word_count()`:
its default character class excludes accented letters, so it splits `défaut`
into two words and over-counts a French corpus unevenly — by however many
accents a page happens to contain. On this project's first course it reported
387 where the body holds 354 tokens, a 9% inflation. A budget measured with a
biased ruler is not a budget.

## The budgets

| Content level | Budget | Observed 2026-09-08 (n · median · p90 · max) | Observed 2026-09-16 |
|---|---|---|---|
| `MINIMAL` | **700** *(was 400)* | 27 · 246 · 319 · 450 | 27 · 339 · 395 · 398 |
| `STANDARD` | **900** | 125 · 395 · 543 · 880 | 124 · 431 · 587 · 885 |
| `DEEP` | **1200** | 11 · 584 · 646 · 677 | 12 · 584 · 755 · 894 |

### Where the numbers come from

Each budget was originally roughly the observed p90 of its level plus headroom
for the refinement work to come. The constraint that bounds them from above is
the **full-corpus revision pass**:

```
27 × 700  +  124 × 900  +  12 × 1200  =  144 900 body words
144 900 / 250 words per minute        ≈  9.7 hours
```

### Why MINIMAL was raised on 2026-09-16 (ADR-0008)

**The p90 could not be used a second time.** By 2026-09-16 the MINIMAL p90 was
395 against a ceiling of 400, and its maximum 398. A distribution pressed flat
against its own cap says nothing about what the content needs; it says the rule
is censoring it. Re-deriving the budget from that number would have baked the
constraint into its own replacement.

**The basis used instead is the calibration the other two levels already show.**
Where a level's median sits inside its budget is a statement about how much room
that level was given:

| Level | Median | Budget | Median as % of budget |
|---|---:|---:|---:|
| `STANDARD` | 431 | 900 | **48%** |
| `DEEP` | 584 | 1200 | **49%** |
| `MINIMAL` *(before)* | 339 | 400 | **85%** |
| `MINIMAL` *(after)* | 339 | 700 | **48%** |

MINIMAL was the outlier, by a factor of nearly two. 700 is the figure that puts
it on the same footing as the two levels nobody had complained about — not a
figure chosen to make a particular course fit.

**What the raise cost.** The corpus ceiling moves from 136 500 to 144 900 body
words, and the "roughly one long day" claim from ≈9.1 h to ≈9.7 h. The promise
is weaker by about 35 minutes. That is the price and it is written here rather
than absorbed silently.

**What the raise did not do.** It unblocked **two** of the five notions the Lot
02 reviews named as missing — `415` on *Status codes* (2 words of margin) and
`setTrustedHosts()` on *HTTP request* (6). The other three — `Partitioned`/CHIPS,
`send()`/`sendHeaders()`, the HttpClient exception hierarchy — sat on items with
255, 416 and 371 words of margin. They were never blocked by this rule. Raising
a budget does not write a paragraph, and this document should not be read as
though it had.

**One promotion accompanied it, and it is not the same act.** *HTTP request*
moved `STANDARD` → `DEEP` the same day. The justification in the matrix names
three mechanisms and the ordered procedure the third one requires; it would read
the same had the budget never been touched. §4.1 and CLAUDE.md both forbid
promoting an item because a number looked tight, and the test of good faith is
whether the justification survives with the numbers removed. Record it here so a
later reader can check that for themselves rather than take it on trust.

The stated assumption is 250 words per minute for technical prose being
revised rather than read for the first time. **That assumption is not measured
here** — it is a planning figure, and it is written down so a later reader can
disagree with it explicitly instead of inheriting it silently. What the
derivation fixes is the shape of the claim: the whole corpus must stay
revisable in about one long day, and the per-level budgets are that total
distributed by how much each level was judged to need.

`RefinementFrameworkRuleTest::testThePublishedCorpusCeilingMatchesTheBudgets`
asserts the 136 500 figure against the rule's own constants, so this document
and the code cannot drift apart.

The corpus measured **65 477 body words** on 2026-09-08 — about 4.4 hours. On
2026-09-16 it measured **73 321** — about 4.9 hours, or 51% of the ceiling the
budgets allow. The aggregate was never the binding constraint; its *shape* was.

## Honest statement of what this rule catches today

**Nothing, as of 2026-09-16.** No item of the 163 exceeds its budget. The one
item the rule used to flag — `Handling legacy deprecated code` (lot-13,
`MINIMAL`) — now measures 396 body words against a budget of 700.

That is worse than it sounds and is stated plainly: a raise that leaves a rule
with nothing to say makes it, today, exactly the vacuous shape this project has
found five times. Two things keep it from being dormant rather than merely
quiet. `RefinementFrameworkRuleTest::testTheRaisedMinimalBudgetIsStillACeiling`
asserts that a MINIMAL item at 701 words still raises an `ERROR`, and
`tools/audit/prove_framework_rules_fail.py` grows a real course past its ceiling
in the canonical data and asserts the rule fires. **A clean run is not the
evidence; those two are.**

*Before the raise, for the record:* one item of 163 exceeded its budget —
`Handling legacy deprecated code`, then at 450 body words against 400.

A rule that flags 1 in 163 is close to vacuous *today*, and saying otherwise
would repeat the mistake this project has already made five times. Its value is
prospective: the refinement passes about to begin add scenarios, rationale and
targeted confusions to every course, and a budget that arrives after the
spending is not a budget. `tools/audit/prove_framework_rules_fail.py` grows a
real course past its ceiling and asserts the rule fires with `[ERROR]`, so the
rule is known to work rather than assumed to.

**A green `REV-001` is therefore not evidence that revision load has been
audited under pressure.** It becomes that only once the corpus has grown.

## Severity

`ERROR` for a lot recorded as refined under the current framework, `WARNING`
elsewhere. The ceiling is a constraint on refinement work; failing the build
over a course whose refinement pass has not happened yet would achieve nothing
except pressure to raise the ceiling.

## What to do when it fires

Two possibilities, and the rule states both because it cannot tell them apart:

1. **The content is doing more than the level claims.** Cut it, or move the
   surplus to the item that owns the material.
2. **The level is wrong.** Promote the item — with a justification that names
   the concept, per §4.1, never because a number looked low.

Deleting the check is not on the list (§12).
