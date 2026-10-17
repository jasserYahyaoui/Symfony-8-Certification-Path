# Semantic repetition between exam-bank questions (remediation P3.1)

**Date:** 2026-10-17 · **Script:** `tools/audit/semantic_repetition.py` (CI, with `--prove`)
· **Registry:** [`reviewed.yml`](reviewed.yml)

DUP-001 refuses a repeated prompt, or a prompt and an answer both close
character by character. It cannot see the same question asked in other words:
« Which events does ConsoleEvents declare? » and the same with « (Choose 2) »
share a situation and a fact but not an answer string, so both were in the
Practice bank. This audit compares meaning as far as a lexical model can.

## The model

Every LEARNING and VALIDATION question becomes a TF-IDF vector of its prompt
and its correct answers — identifiers counted whole and by their parts
(`setSharedMaxAge` also says *shared*, *max*, *age*), the answer weighted
twice. Every pair at cosine ≥ 0.45 is flagged. The threshold was set by reading
every pair above it on 2026-10-17, not tuned to a count. A pair sharing no
word is not seen: the model is lexical, and says so.

HOLDOUT questions are compared like the others and reported **by count
alone** — pool pair and band, never an id or a word.

## The verdicts

A flag is not a verdict. Each visible pair is read once:

| Verdict | Meaning |
|---|---|
| `DISTINCT` | the pair shares words, not knowledge |
| `PARALLEL` | same knowledge, another situation: memorizing one answer does not answer the other without reasoning — a parallel form, which assessment wants |
| `REPEAT` | the same situation and the same conclusion, reworded |

The audit fails on a flagged pair that is unreviewed, reviewed against an
older version of either question, or still `REPEAT`.

## What it found

82 visible pairs were flagged on the bank as it stood. **Twelve were
`REPEAT`.** Each was resolved by rewriting the LEARNING side under a new
version, its new answer executed on Symfony 8.0 / PHP 8.4 where it can be
(`tools/behavior/questions/semantic-p31.php`, each check proved by a flipped
key):

| Rewritten | Was a repeat of | Now asks |
|---|---|---|
| `QST-m8byv8zcsa8m` | `QST-w2tv19e0bh82` (setSharedMaxAge alone) | setSharedMaxAge() then setPrivate(): may a reverse proxy store it |
| `QST-enxn0ad65n0w` | `QST-b7n04h0xsdrb` (307/308 keep the POST) | 303 See Other: the redirect that turns a POST into a GET |
| `QST-ycpqw0ygx9cd` | `QST-6j988v4fwa7n` (the ConsoleEvents constants) | the name ConsoleAlarmEvent is dispatched under |
| `QST-k1qgrrevaw40` | `QST-1qy9s052f4x7` VALIDATION — its prompt stated the answer | strict_variables left unset: its kernel.debug default in dev and in prod |
| `QST-nqeq65rj5m97` | `QST-q9vmvxwtk6n4` VALIDATION (the extensions come from a bridge) | what `composer require symfony/twig-bundle` installs, and why |
| `QST-gbccz7ynvbd2` | `QST-mmz7113w0hqb` VALIDATION (`_route`) | `_controller` after routing, and in what form |
| `QST-c8gpyvw5shmy` | `QST-2yyvqea9bx5j` VALIDATION (all abstain: denied) | the priority strategy on abstain, deny, grant |
| `QST-bftzbt0t3q8y` | `QST-9d4z5adb9sez` (.env.local skipped in tests) | `.env.prod` against `.env.local` in prod |
| `QST-gfqb0xrb7tbx` | `QST-r73ye7dqg80n` (_defaults and when@test) | _defaults and an imported file |
| `QST-gpz2ww98k670` | `QST-zxsw2qb185qq` VALIDATION (a collision is fatal) | `insteadof` with `as`: which name runs which trait |
| `QST-af1e821vzx6r` | `QST-fvhwatsy529v` VALIDATION (_format sets the format) | `_format` against an explicit Content-Type |
| `QST-3bbtms3dk9ca` | `QST-w6dwd7mt2gh4` VALIDATION (a RuntimeException gives 500) | framework.exceptions against HttpExceptionInterface |

Seven of the twelve were cross-pool: practising the LEARNING question gave
the answer of a VALIDATION question, whose success is the evidence an item is
ready. A first rewrite of `QST-3bbtms3dk9ca` moved it from 0.21 to 0.54 of a
HOLDOUT question on the same item — measured as a number, nothing read — and
was replaced by another angle (0.29), and a first rewrite of
`QST-m8byv8zcsa8m` that rose from 0.38 to 0.44 likewise (0.24): a rewrite must
not bring a Practice question closer to the final mock. Two first rewrites, on `_format` and on
`strict_variables`, were refused by CMP-002 — 71 % and 74 % similar to
comprehension questions that already ask them — and were given other angles;
the threshold was not touched.

After the rewrites, 70 visible pairs are flagged: **46 `PARALLEL`, 24
`DISTINCT`, 0 `REPEAT`**.

## What it does not settle

- **HOLDOUT pairs** — 25 above the threshold before the rewrites and after,
  none involving a rewritten question, one at 0.8+ with a LEARNING question on
  the same item — are counted and not read; deciding them means reading the
  holdout.
- **The lexical model** misses a paraphrase that shares no word. The reading
  of each flagged pair is one reviewer's (this project's); a second reader
  is `MISSING`.
- **Difficulty and discrimination** stay `MISSING` until real learner answers
  exist; the site says « difficulté estimée » wherever it shows a difficulty.
