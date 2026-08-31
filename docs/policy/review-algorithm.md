# Flashcard review algorithm

Master Plan §6 requires that *"the exact deterministic algorithm must be
specified and tested in Lot 0"* and that the project *"not claim scientifically
optimized spaced repetition without evidence"*.

This document is the specification. The implementation is
`src/Review/ReviewScheduler.php`; the behaviour below is pinned by
`tests/Unit/ReviewSchedulerTest.php`.

## What this is, and what it is not

It is a plain, auditable interval schedule: predictable, deterministic and easy
to reason about. **No efficacy claim is attached to it.** It has not been
validated against learner outcomes, and the project must not describe it as
optimized, scientific or evidence-based until real attempt data exists (§7.5:
"Do not draw statistical conclusions from insufficient attempts").

## State

| Field | Meaning |
|---|---|
| `intervalDays` | Days until the next review |
| `ease` | Growth multiplier, clamped to `[1.3, 3.0]`, initial `2.5` |
| `repetitions` | Consecutive successful reviews |
| `lapses` | Number of `AGAIN` outcomes ever recorded |
| `dueInSession` | The card must be shown again before the session ends |

A new card starts at `intervalDays: 0`, `ease: 2.5`, `repetitions: 0`.

## Transitions

Outcomes are the four of §6.

### `AGAIN`

> §6: "repeat in current session, then J1".

- `dueInSession` → `true`
- `intervalDays` → `1`
- `repetitions` → `0`
- `lapses` → `+1`
- `ease` → `ease - 0.20`, clamped

### `HARD`, `GOOD`, `EASY`

`repetitions` is incremented, then:

| `repetitions` | New interval |
|---|---|
| 1 | `1` day (fixed) |
| 2 | `3` days (fixed) |
| ≥ 3 | `round(intervalDays × multiplier)`, and never less than `intervalDays + 1` |

with

| Outcome | Multiplier | Ease change |
|---|---|---|
| `HARD` | `1.2` | `−0.15` |
| `GOOD` | `ease` | `0` |
| `EASY` | `ease × 1.3` | `+0.15` |

The first two intervals are fixed so that a card's early schedule does not
depend on an ease value the learner has not yet influenced.

The `intervalDays + 1` floor guarantees that a successful review always moves
the card forward. Without it, a card at `ease = 1.3` reviewed as `HARD` could
have produced an interval shorter than the one it already had.

## Bounds

- `ease` ∈ `[1.3, 3.0]`, rounded to two decimals.
- `intervalDays` ≤ **180**. The exam is a fixed-date event, so scheduling a
  card past a six-month horizon would put it beyond the preparation period and
  effectively retire it.

## Determinism

`next(state, outcome)` is pure: no clock, no randomness, no I/O. The same input
always produces the same output, which is what makes the schedule testable and
portable to the browser without divergence.

## Not yet decided

Card selection *order* within a session (due-first, weakest-first, interleaved)
is not part of this algorithm and is deferred to the flashcard UI in Lot 0.5.
