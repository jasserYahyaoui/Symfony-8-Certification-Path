# FR-2 — French accents in the syllabus matrix

**Master Plan §5 · `REQUIRED_BEFORE_FINAL_READINESS` · specification:
[`docs/policy/fr-2-specification.md`](../../policy/fr-2-specification.md)**

| | |
|---|---|
| State | `NOT_STARTED` → **`DONE` pending production verification** |
| Run at | 2026-09-08 |
| Base commit | `d83edd6` |
| Specification | read-only, produced before any file was modified |
| Structural check | [`structure-check.txt`](structure-check.txt) |
| Second independent audit | [`second-audit.txt`](second-audit.txt) — 0 findings |
| Fail-proof | [`fail-proof.txt`](fail-proof.txt) — 41 of 41 |

## What was delivered

| | |
|---|---|
| Items | **126 of 126** (lots 01–11) |
| Strings | **619 of 734** changed; the other 115 were already correct |
| Word occurrences corrected | **1,537** |
| Distinct word corrections | **456** |
| Fields | `content_level_justification`, `learning_outcomes`, `minimum_evidence` |
| Files touched | **one** — `docs/syllabus/syllabus-matrix.yml` |

Accent profile after the repair: **84% of strings in lots 01–11 carry an
accented character, against 84% in the never-affected lots 12–26.** Before, it
was 0.1% against 84%.

## The scope was re-measured, and the recorded scope was short

FR-3's recorded lesson is that *a defect's recorded scope is a claim, not a
measurement*. Applying it to FR-2 itself found that **the recorded scope of
"608 strings over two fields" was missing a third field**: `minimum_evidence`
carries the identical signature — zero accents through lot 11, fully accented
from lot 12 — and was in nobody's scope. The real scope is **734 strings over
three fields**.

`minimum_evidence` is **not** rendered to the learner (verified: its text
appears on 0 generated pages), so on a strict reading of FR-2's learner-impact
justification it falls outside. It was repaired anyway, because leaving one
field would reproduce exactly the mis-scoping that turned FR-1 into FR-2 and
then into FR-3, and because repairing invisible metadata carries no pedagogical
risk. This is a **documented extension**, recorded in the specification §5.

## Five passes, each with a different warrant

| Pass | Method | Occurrences |
|---|---|---|
| 1 | word types lots 12+ **always** accent | 719 |
| 2 | ambiguous function words and verbs, **read individually** | ~210 |
| 3 | the repository's own French as witness | 433 |
| 4 | witness re-run after two witness defects were fixed | 49 |
| 5 | words no witness can judge, **read one by one** | 119 |

**No pass was allowed to guess.** Where evidence existed it was used; where it
did not, the sentence was read.

## What the witness got wrong, and how it was caught

The widened witness was itself defective twice, and both faults would have left
real errors standing or introduced new ones:

1. **A single metadata tag poisoned it.** `content/questions/lot-04-controllers.yml`
   carries `tags: - securite`. That one unaccented slug made the witness call
   `securite` ambiguous, which would have left **11 real defects** unrepaired.
   Bare slug lines are now excluded — a tag is metadata, and its slug is
   correct as a slug.
2. **English was testifying about French.** 523 of the 544 questions are
   English, and `reference`, `declaration`, `generation`, `representation`,
   `complete`, `presence` are all correct **English** words. Feeding them in
   made the witness call the French `référence` ambiguous. The witness is now
   restricted to records with `language: fr`.

## Traps read and refused

Four proposals the evidence supported were **rejected after reading**, each the
present tense where the witness happened to use a participle — the trap FR-1 hit
with `oriente` and FR-3 with `implémente`:

| Form | Evidence proposed | Correct here | Why |
|---|---|---|---|
| `lie` | `lié` | **`lie`** | verb — *"L'omission de Vary lie l'item au cache"* |
| `apparie` | `apparié` | **`apparie`** | verb — *"ce qui apparie handler et message"* |
| `resume` | `résumé` | **`résume`** | present — *"Flex se résume à quatre faits"* |
| `experimental` | `expérimental` | **`experimental`** | the Symfony annotation `@experimental`, listed beside `@internal` — an identifier, not French (§10) |

Seven further forms were read and **deliberately left unaccented** (`caches`,
`croit`, `devine`, `relie`, `consulte`, `modifie`, `recommande` in one of its
two uses), and are recorded in `tools/fr2/apply_readings2.py` so a later pass
cannot "helpfully" change them.

`deroule` and `recommande` are each correct **both ways** in this corpus and are
decided per sentence, not per word.

## `a` / `à` — 170 occurrences, 12 of them the verb

The largest ambiguous class. All 170 were printed with their sentences and
read: **155 are the preposition `à`**, **12 are the verb *avoir*** and keep
their bare `a`, and 3 are `qu'a` → `qu'à`.

The applier **asserts that exactly 12 match**. On the first attempt it found 9,
which was a real defect in the tool and not in the reading: a folded YAML
scalar wraps one sentence over several lines, so per-line context misread every
occurrence at a line boundary. Field content is now substituted as one block.
A second miss followed — `str.split()` yields the token `n'y`, not `y`, so the
three *"il n'y a"* cases escaped the pattern. **Both were caught by the
assertion rather than by inspection**, which is why it is written as an
assertion.

## What was proved, not asserted

**The structural check is the decisive one.** Every modified string must reduce
to the *same ASCII text* as before. That single assertion proves no word was
added, removed, reordered or re-spelled, and that no technical identifier was
touched — any of those would change the folded form.

```text
strings changed : 619
items touched   : 126
STRUCTURE OK — diacritics-only, in-scope fields of lots 01-11 only,
word counts and string counts unchanged, every other field identical
```

**No answer key, no official wording, no holdout, no question, no source.**
AUD-01 re-ran and still finds 163/163 items verbatim in the PDF, which is the
independent proof that the official text was not touched.

## Every audit re-earned its PASS

The corpus changed, so no earlier result was carried forward:

```text
AUD-01  exit 0   163/163 verbatim, 0 not found
AUD-02  exit 0   FINDINGS: 0        AUD-03 --offline  exit 0   FINDINGS: 0
AUD-04  exit 0   FINDINGS: 0        AUD-05            exit 0   FINDINGS: 0
AUD-06  exit 0   FINDINGS: 0        AUD-07            exit 0   FINDINGS: 0
AUD-08  exit 0   FINDINGS: 0        FR-2 second audit exit 0   FINDINGS: 0
fail-proof       41 of 41 proved, every file restored byte-identically
```

## Gates, each run as its own command with its exit status read (PROC-1)

```text
php bin/cert validate   exit 0  — 18 rules, no violations, 544 questions
php bin/cert coverage   exit 0  — 100% (163/163 EXAM_READY), no diff
vendor/bin/phpunit      exit 0  — 194 tests, 8685 assertions
composer gate-full      exit 0  — site build + a11y 14 surfaces, 0 violations
```

The corrected prose reaches the learner and was verified in the generated tree:

```text
Concept important exigeant distinction et application : cet item est
propriétaire de la comparaison interface / classe abstraite, qui est un choix
de conception réellement testé. Les règles propres … sont courtes mais
piégeuses.
```

## What FR-2 is not

It is **not** "zero unaccented characters". That property was the *detection
tool* that located the defect, never the definition of done — a correct French
sentence can carry no accent at all, and 6 of 43 strings in lot-12 do. The
criterion applied here is **per word**, and 115 of the 734 strings needed no
change because they were already right.
