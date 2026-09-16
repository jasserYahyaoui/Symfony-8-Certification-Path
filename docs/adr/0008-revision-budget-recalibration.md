# ADR-0008 — Recalibrating the revision budgets

**Status:** Accepted
**Date:** 2026-09-16
**Supersedes:** nothing. **Amends:** [ADR-0007](0007-refinement-framework-v2.md), whose
`REV-001` budgets this changes. **Relates to:** `docs/policy/revision-budget.md`

## Context

Four independent reviews of Lot 02 returned `NON VALIDÉ` — 64, 82.5, 76.5 and 77
out of 100 against a threshold of 95. Across all four, the *coverage* line
scored at most 16 of 20, always for the same stated reason: notions the reviews
judged missing.

The reviews also recorded why those notions were not simply added. Four Lot 02
items sat within 15 body words of their `REV-001` ceiling, `REV-001` blocked
five separate correction attempts during the audit, and promoting an item's
level to buy room is forbidden — level distribution is an outcome, never a
target. The audit closed on an unresolved question rather than a sixth
iteration: **is 95/100 reachable at all, or does the rule set exclude it by
construction?**

The owner was given three options — raise the budgets, lower the threshold, or
leave the lot as it stands — and chose to raise the budgets.

## The measurement that shaped the decision

Raising a budget is the one move CLAUDE.md §12 most resembles what it forbids:
weakening a control to obtain a green status. So the change was measured first,
and the measurement moved the decision twice.

**First: the squeeze was not general, it was one level.** Measured on the
corpus on 2026-09-16, where each level's median sat inside its budget:

| Level | n | Median | Budget | Median as % of budget |
|---|---:|---:|---:|---:|
| `STANDARD` | 125 | 432 | 900 | 48% |
| `DEEP` | 11 | 584 | 1200 | 49% |
| `MINIMAL` | 27 | 339 | 400 | **85%** |

The corpus as a whole used 73 321 of the 136 500 body words its budgets allowed
— **54%**. The aggregate ceiling was never binding. `MINIMAL` was starved while
`DEEP`, whose largest item measured 755 against 1200, was not.

**Second: the rule was blocking two notions, not five.** The five notions the
Lot 02 reviews left open, against the margin actually available on their items:

| Notion | Item | Margin | Blocked by `REV-001`? |
|---|---|---:|---|
| `415` | Status codes (`MINIMAL`) | 2 | **yes** |
| `setTrustedHosts()` | HTTP request (`STANDARD`) | 6 | **yes** |
| `Partitioned` / CHIPS | Cookies (`STANDARD`) | 255 | no |
| `send()` / `sendHeaders()` | HTTP response (`STANDARD`) | 416 | no |
| HttpClient exception hierarchy | HttpClient (`STANDARD`) | 371 | no |

This is recorded because the decision was taken on a premise — "the rule is what
stops coverage improving" — that the data only partly supports. Three of the
five notions had hundreds of words of room and were simply never written.

## Decision

**1. `MINIMAL` rises from 400 to 700 body words.** `STANDARD` (900) and `DEEP`
(1200) are unchanged.

The derivation deliberately does **not** reuse the method of ADR-0007. That
method took each level's observed p90; by 2026-09-16 the `MINIMAL` p90 was 395
against a ceiling of 400, with a maximum of 398. A distribution pressed flat
against its own cap measures the rule, not the content, and re-deriving from it
would have written the constraint into its own replacement.

The basis used instead is the calibration the other two levels already exhibit:
a median at roughly 48% of budget. 700 puts `MINIMAL` at 48%. It is the figure
that makes the three levels alike, not the figure that makes a particular course
fit.

**2. `HTTP request` is promoted `STANDARD` → `DEEP`.** Its justification in the
matrix names three mechanisms — the seven bags and their types, `InputBag`'s
defensive contract, and the `X-Forwarded-For` trust chain — and argues that the
third is an ordered procedure that cannot be applied without being recalled
whole, with a security consequence when it is not. The test applied was whether
that justification still reads as true with every number removed from it. It
does; the item was arguably mis-levelled before the budget question arose.

This is recorded in the same ADR as the raise precisely because the two acts
look alike from the outside and must not be conflated.

## Consequences

**The corpus ceiling moves from 136 500 to 144 900 body words**, and the
"revisable in roughly one long day" claim from ≈9.1 h to ≈9.7 h at the stated
250 words/minute. The promise is about 35 minutes weaker. That is the price of
this decision and it is stated rather than absorbed.

**`REV-001` now reports nothing on the current corpus.** Before the raise it
flagged one item of 163; after it, none. A rule with nothing to say is the exact
vacuous shape this project has already found five times, so the raise ships with
the two things that keep it from being merely quiet:
`RefinementFrameworkRuleTest::testTheRaisedMinimalBudgetIsStillACeiling` asserts
that a `MINIMAL` item at 701 words still raises an `ERROR`, and
`tools/audit/prove_framework_rules_fail.py` grows a real course past its ceiling
in canonical data and asserts the rule fires. **A clean run is not evidence that
this rule works; those two are.**

**Lot 02 is not thereby validated.** No review has seen the corrected lot; the
last one to return a verdict gave 77. This ADR removes one obstacle to a higher
coverage score. It does not raise the score, and nothing in this decision
licenses describing the lot as validated.

**Three of the five open notions were never blocked.** Writing them remains
outstanding work, unaffected by this ADR.

## What was rejected

**Lowering the 95/100 threshold.** Offered to the owner and declined. It would
have resolved the tension by changing the answer rather than the constraint.

**Funding the raise by cutting `DEEP` to 900.** Arithmetically attractive — the
largest `DEEP` item measures 894 — and it would have held the ceiling near
136 500. Rejected because tightening a level against its observed maximum
invites exactly the pressure this ADR exists to resist, on a level whose
refinement passes are not finished.

**Re-deriving `MINIMAL` from its 2026-09-16 p90.** Rejected for the reason given
under *Decision*: that p90 is an artefact of the ceiling being replaced.
