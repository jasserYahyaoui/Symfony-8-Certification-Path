# Question-bank audit — Lot 27 unit 2

Read-only. **No question, choice, mapping, pool or rule was changed by this
audit**; the corrections it proposes are listed at the end and are not applied.

Scope: the 496 questions on `4e17719`. The 18 mandatory rules already run on
every push, so this looked for what those rules **cannot** see rather than
re-running them.

## What is clean

Measured across all 496 questions, not sampled:

| Check | Result |
|---|---|
| Declared `required_answer_count` vs actual keys | **0** mismatches |
| `answer_mode: single` with exactly one key | **0** violations (487 single, 9 multiple) |
| `answer_mode: multiple` with ≥ 2 keys | **0** violations |
| Every distractor carries an explanation (§7.2) | **0** unexplained |
| Question-level explanation present | **0** missing |
| Duplicate choice text within a question | **0** |
| Fewer than 3 choices | **0** |
| `estimated_time_seconds` outside 20–180 s | **0** |
| `official_item` resolves to a matrix row | **496 / 496** |
| Items carrying questions | **163 / 163**, 2 to 5 each, mean 3.0 |

A first pass reported one duplicate choice text, in `QST-whsz8qfwgcby`. That was
the detector's fault: it lowercased before comparing, and the question's whole
point is that `getLanguages()` normalises case — `fr_FR` and `FR_fr` are
different answers there. Re-run case-sensitively, the corpus has none.

## Language policy (`docs/policy/language-policy.md`)

| §5 requirement | Measured | Verdict |
|---|---|---|
| ≥ 50% of advanced (`hard`) questions in English | 201 / 202 = **99.5%** | PASS |
| Mock 3 primarily English (`VALIDATION`) | 135 / 135 | PASS |
| Mock 4 100% English (`HOLDOUT`) | 27 / 27 | PASS |
| Beginner practice may be French | 21 French, all `LEARNING` (13 medium, 7 easy, 1 hard) | PASS — permitted |

## Finding 1 — `DUP-001` does not detect near-duplicates

`DuplicateQuestionRule` compares **normalised prompts for exact equality**:
lowercase, strip punctuation, collapse whitespace. Two prompts differing by a
single word are invisible to it.

That is narrower than §12, which requires *"duplicate or near-duplicate
questions and flashcards"*, and narrower than the rule's own docblock, which
says *"Near-duplication is detected on a normalized prompt"*. Normalising
punctuation is not near-duplicate detection.

Measured on the current bank:

- exact normalised duplicates — what the rule can catch: **0**
- prompt pairs at similarity ≥ 0.75 — what it cannot: **6**

This is how audit item **P2.2** survived: a `HOLDOUT` question measured 0.88
similar to its `VALIDATION` counterpart, and no rule could see it. It was found
by a manual audit and fixed, but the gap that hid it was never closed.

**No content defect follows from this.** All six pairs were read; every one is
legitimate:

| Similarity | Pair | Why it is not duplication |
|---|---|---|
| 0.922 | `QST-1rm2gsd2rpg3` / `QST-sy8yv5y6kfbe` | Same deliberate stem — *"provided by FrameworkBundle rather than by X"* — over **different components**, different items, different answers |
| 0.881 | `QST-cs5czk986ceh` / `QST-35tar5pn92ca` | *"Which statement about X is correct"* for HttpFoundation and HttpKernel: different components, different facts |
| 0.761 | `QST-0hcwz5ma0vpr` / `QST-dxbhgyewd2ma` | Same item, LEARNING vs HOLDOUT, but **opposite mechanics**: a nested `GroupSequenceProvider` array validates the parallel group, a flat one stops. Testing the flat case after practising the nested one is discrimination, not recall |

Parallel phrasing across different subjects is a strength, not a defect. A
similarity rule must therefore not fire on phrasing alone — see the proposed
correction.

## Finding 2 — unaccented French in the question banks (**FR-3**)

**8 questions** carry French prose written without accents (`en-tete`,
`requete`, `methode`, `deja`, `etat`, `memoire`, `necessaire`, `meme`), all of
them `language: fr`, all in **lots 01 and 02**.

This is the same generator shortcut as FR-1 and FR-2, in a third artefact.
FR-1 covered the flashcards, FR-2 the syllabus matrix; **the question banks were
never measured**, so the defect was recorded as bounded when it was not.

A first probe reported 114 hits across 20 banks. That probe was wrong: it
included words that are ordinary English — `different`, `reference`, `element`,
`schema`, `execution` — which are correct inside the 475 English questions.
Restricted to tokens with no English homograph, the real figure is **8
questions**. The larger number is withdrawn.

## Time budget — input for the Mock 4 decision

| Pool | n | mean | median | total |
|---|---|---|---|---|
| LEARNING | 334 | 52.7 s | 50 s | 293 min |
| VALIDATION | 135 | 56.7 s | 60 s | 128 min |
| HOLDOUT | 27 | 65.6 s | 60 s | 30 min |

The official format is 75 questions in 90 minutes — **72 s per question**. At
the holdout pool's current mean of 65.6 s, 75 such questions come to **82
minutes against a 90-minute limit**: it fits, with roughly 8 minutes of margin.

This is evidence for the Mock 4 decision, not the decision itself. It says
questions of this weight are feasible at that length; it says nothing about
where the missing 48 come from.

## Proposed corrections — none applied

| # | Change | Reason | Size |
|---|---|---|---|
| Q-1 | Give `DUP-001` a real similarity check over normalised prompts, with a threshold and a regression test built from the P2.2 pair. It must compare **answers as well as prompts**, so parallel stems over different subjects do not fire | §12 requires near-duplicate detection; the rule performs exact matching and its docblock overstates it | S |
| Q-2 | Re-accent the 8 French questions of lots 01–02 (**FR-3**) | Same defect class as FR-1 and FR-2, in an artefact never measured | XS |
| Q-3 | Record FR-3 in `CONTEXT.md` beside FR-1 and FR-2, and correct the standing claim that the accent defect is confined to flashcards and the matrix | The recorded scope was wrong, and a wrong scope is what let this go unmeasured | XS |

Q-1 and Q-2 are independent and can ship separately. Neither is a §22 blocker:
§22 clause 3 asks for *0 known incorrect scored answer*, and this audit found
none.
