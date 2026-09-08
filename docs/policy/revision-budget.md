# Revision budget

**Rule:** `REV-001` · **Criterion:** `R14_revision_budget` ·
**Decision:** [ADR-0007](../adr/0007-refinement-framework-v2.md)

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

| Content level | Budget | Observed on 2026-09-08 (n, median, p90, max) |
|---|---|---|
| `MINIMAL` | **400** | 27 · 246 · 319 · 450 |
| `STANDARD` | **900** | 125 · 395 · 543 · 880 |
| `DEEP` | **1200** | 11 · 584 · 646 · 677 |

### Where the numbers come from

Each budget is roughly the observed p90 of its level plus headroom for the
refinement work to come, rounded to a figure a person can hold in mind. The
constraint that bounds them from above is the **full-corpus revision pass**:

```
27 × 400  +  125 × 900  +  11 × 1200  =  136 500 body words
136 500 / 250 words per minute        ≈  9.1 hours
```

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

The corpus measured **65 477 body words** on 2026-09-08 — about 4.4 hours.

## Honest statement of what this rule catches today

**One item of 163.** `Handling legacy deprecated code` (lot-13, `MINIMAL`, 450
body words) exceeds its budget and is reported as a warning.

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
