# Remediation of the 2026-10-13 audit findings (F1–F10)

This file is the tracker for the remediation mission received on 2026-10-13. It
answers the ten findings the mission states (F1–F10) with the evidence produced
here. It is written so the work can be resumed from the repository alone.

The findings came with the mission as historical observations on a copy examined
on 2026-10-13. Each one was **reproduced in this repository before being
acted on**; the figures below are this repository's, not the audit's.

Statuses: `À FAIRE`, `EN COURS`, `CORRIGÉ NON VÉRIFIÉ`, `VÉRIFIÉ`, `BLOQUÉ`.
`VÉRIFIÉ` means: canonical files corrected, an executed check passes, and the
local gates pass. It never means deployed; delivery is recorded separately.

## Baseline (clean `master` at `45dad23`, 2026-10-13)

Measured in a separate worktree of `origin/master`, before any change.

| Measure | Value |
|---|---|
| `php bin/cert validate` | exit 0 — 1 violation, 0 blocking (PED-003 warning) |
| `vendor/bin/phpunit` (after `bin/cert build`) | exit 0 — 343 tests, 23 546 assertions |
| Official items / courses | 163 / 163 |
| Learning outcomes (`OUT-*`) | 612 |
| Flashcards | 1 909 |
| Questions by pool | LEARNING 516, VALIDATION 137, HOLDOUT 75, COMPREHENSION 1 117 |
| Multiple-answer questions | LEARNING 10, HOLDOUT 8, VALIDATION 0, COMPREHENSION 0 |
| Bank files | 29 in `content/questions/`, 26 in `content/comprehension/` |

Versions used for reproductions: PHP 8.4.19; Symfony 8.0.15 (`tools/behavior`,
`composer.lock`; 8.0.16 was not offered by the resolver here); Symfony Contracts
3.7.1. Twig 3.22.2 is in a separate sandbox and not yet used by any check.

## Mechanism added for P0 (and P1.8, P3.3)

| Piece | Role |
|---|---|
| `tools/behavior/` | Separate Composer project pinned to `symfony/*: 8.0.*`. `php tools/behavior/run.php` executes each fact; exit 1 on any mismatch. Runs in CI (`Taught behaviour holds on Symfony 8.0`). |
| `docs/facts/central-facts.yml` | Registry: one entry per corrected fact — statement, sources, the check that executes it, the content ids that teach it, and regexes of the corrected wording. |
| `tests/Integration/CentralFactsTest.php` | In the main PHPUnit suite: every fact names an existing check, every linked id exists, and no canonical file (`content/**`, the matrix) states a corrected claim again. A HOLDOUT match is reported without its id. |

The four kinds of test the mission distinguishes are kept apart: the generator's
own tests (PHPUnit), the Symfony behaviour (`tools/behavior`), the agreement of
the content with that behaviour (`CentralFactsTest`, by wording), and the UI
(`website/tools/verify-*.mjs`). A forbidden pattern catches a **wording**, not
an idea: it stops the old sentence returning, not every way of being wrong.

## Findings

| # | Finding | Status | Evidence |
|---|---|---|---|
| F1 | Serializer « toujours un tableau » | **VÉRIFIÉ** (local) | see P0.1 |
| F2 | Cache callback « only on a miss » | **VÉRIFIÉ** (local) | see P0.2 |
| F3 | `kernel.view` shown as unconditional | **VÉRIFIÉ** (local) | see P0.3 |
| F4 | HOLDOUT length bias not measured on its own | **BLOQUÉ** (rewrite) — control ready | see P0.4 |
| F5 | Content metrics read as candidate mastery | **VÉRIFIÉ** (local) | see P1.7 |
| F6 | Roadmap / mock texts out of sync | **VÉRIFIÉ** (local) — mock texts derived; comprehension load budgeted, exam moved to 29 Dec by owner decision | see P2.5 |
| F7 | Too few multiple-answer questions | **EN COURS** — +1 LEARNING, +1 COMPREHENSION (P1.5); VALIDATION still 0 | P1.6 |
| F8 | Unsourced "not examinable" restrictions | **EN COURS** — Serializer extended (P1.5, VÉRIFIÉ local); P1.1–P1.4, P2.7 À FAIRE | see P1.5 |
| F9 | PHP source 404 and mutable branch URLs | **BLOQUÉ** (the last 404 is in a HOLDOUT question) — pinning À FAIRE | see P2.6 |
| F10 | No per-card recall record; comprehension stores nothing | **EN COURS** — comprehension recorded (P2.4, VÉRIFIÉ local); per-card recall À FAIRE (P2.3) | see P2.4 |

### P0.1 — Serializer (F1)

Reproduced on Serializer 8.0.15 (`tools/behavior/checks/serializer.php`):
`DateTimeNormalizer::normalize()` returns `string`; `BackedEnumNormalizer`
returns the backing `string`; `serialize(42, 'json')` gives `42`; an ordinary
object normalizes to `array`. `NormalizerInterface::normalize()` declares
`array|string|int|float|bool|\ArrayObject|null` (fetched from the 8.0 branch).

Corrected: course `CRS-3z96cp2s1dka` (section rewritten, contrast table, pitfall
and key point), matrix item `OIT-fr58jzaj6jtb` (level justification; outcome
`OUT-g6j22cqpx1ga` reworded, id kept), flashcard `FLC-50daktpqmkv9`, LEARNING
`QST-xeygxgdd5586` (now a contrast question: which value normalizes to an
array), COMPREHENSION `QST-et0jfkbyacjy` (hypothesis made explicit: a JSON
object), plus distractor explanations in `QST-76tz3feab409` and
`QST-kg5astsr8fz8`. The groups/metadata explanation was kept. The single
HOLDOUT question on this item was searched by script for the wording, without
display: no match. That search is by wording only.

### P0.2 — Cache (F2)

Reproduced (`checks/cache.php`): with `beta = INF` on a stored key the callback
runs, `isHit()` is true inside it and `v2` is saved; `beta = 0` serves the stored
value and does not disable caching; an item without expiry is never elected
early with the default beta (200 draws); `$save = false` returns the value
without storing it; two `FilesystemAdapter` with the same namespace and
directory share a key, a different namespace does not. Source: `CacheTrait::doGet()`
(`$recompute = !$item->isHit() || \INF === $beta`, then the probabilistic
election) and `cache.rst` « keys from different pools never collide … prefixing
keys with a namespace ».

Corrected: course `CRS-p6yhxmpxrzbs` (callback, beta, pool isolation, pitfalls,
key points — it contradicted itself), flashcard `FLC-5ph49r9vszd1`, LEARNING
`QST-ch9mc2vedk60` (« Only when the key is missing » is now a distractor with its
reason) and `QST-w6g2sgj7apm1`, COMPREHENSION `QST-1rc72pvqjry1` (framework
pools named as the hypothesis) and `QST-4312r4ah4gn2` (hypothesis: no default
lifetime). `FLC-ajfvjtabnqet`, `FLC-q4e27600aa61` and the beta questions were
already right and were left alone.

### P0.3 — HttpKernel (F3)

Reproduced on HttpKernel 8.0.15 (`checks/http-kernel.php`), with a recorder
listening at the highest priority:

| Path | Events |
|---|---|
| controller returns a `Response`, then `terminate()` called | request → controller → controller_arguments → response → finish_request → terminate |
| controller returns an array, a view listener converts it | request → controller → controller_arguments → **view** → response → finish_request |
| nothing converts it | `ControllerDoesNotReturnResponseException` |
| response set on `kernel.request` | request → response → finish_request |

Corrected: flashcard `FLC-jjsdae8j3b56` (view bracketed and conditional;
terminate tied to the separate `terminate()` call) and the key-points line of
course `CRS-mpwjc4g3vmj7`, whose body and diagram already showed view as
conditional. The course was 1 word over its DEEP budget after the edit; a
redundant word was removed, the budget was not touched.

### P0.4 — HOLDOUT length bias (F4)

Measured on this repository, single-answer questions, whitespace normalized,
ties for the longest choice excluded from the denominator:

| Pool | strictly-longest choice is correct |
|---|---|
| LEARNING | 87 / 444 = 19.6 % |
| VALIDATION | 23 / 124 = 18.5 % |
| HOLDOUT | **34 / 64 = 53.1 %** |

Chance is 25 % for four choices. This is not a predicted exam score and not an
official threshold. Shuffling removes a position cue, not a length cue.

The control is written and proved, but **not committed yet**: `aud10` gains a
`pool/LEARNING`, `pool/VALIDATION`, `pool/HOLDOUT` group, gated with the same
exact Poisson-binomial tail and alpha 0.01 (its denominator counts ties, so it
reads 34/67, P = 5.2e-06). `--prove` asserts that a bias injected into the
holdout alone fires on `pool/HOLDOUT`, and that a 75-question group at chance
(19/75) stays silent. It fails on the current holdout — correctly — so landing
it before the rewrite would turn CI red.

**Blocked by owner decision.** Rewriting the options requires reading the
holdout, which this project has so far kept unread so that the owner, who has
not taken Mock 4, can take it unseen. The owner chose to leave it untouched for
now (« Owner decisions », 1).

### P1.5 — Serializer extended (F8, part)

Executed first, on Serializer 8.0.15 (`tools/behavior/checks/serializer.php`,
four new checks, 14 facts in all):

| Behaviour | Result |
|---|---|
| `[ObjectNormalizer, MoneyNormalizer]` vs the reverse | the first that accepts wins: `{"cents":1250,…}` vs `"12.50 EUR"`; ObjectNormalizer first also takes a date and exposes its getters |
| `{"qty":"7"}` into `int $qty` | `7` without a type extractor (PHP coercion), `NotNormalizableValueException` with one |
| `COLLECT_DENORMALIZATION_ERRORS` | one `PartialDenormalizationException`, paths `qty`, `note` |
| `OBJECT_TO_POPULATE` | same instance returned, absent keys untouched |
| `skip_null_values`, `datetime_format` | `{"qty":1}`, `"2026-01-02"` |

Framework wiring read in `FrameworkBundle/Resources/config/serializer.php`
(8.0): ObjectNormalizer at priority -1000 with `property_info`; autoconfigured
normalizers are tagged without priority (`FrameworkExtension`).

**Level: STANDARD → DEEP**, on the item: four independent rules each change the
output alone (form by elected normalizer, election order, metadata wiring,
extractor-dependent types). Recorded in the matrix justification and in
`docs/policy/revision-budget.md`; the corpus ceiling becomes 27/123/13 =
145 200, and a new test holds the published counts equal to the matrix.

Content: three course sections (« Quel normaliseur agit ? », « Dénormaliser »,
« Le contexte »), three outcomes (`OUT-hkvn3vxj4fyb`, `OUT-m8wfdy7569p8`,
`OUT-cf0sd5w3fzhe`), three flashcards, three LEARNING questions (one
multiple-answer, two `hard`), three COMPREHENSION questions (one
multiple-answer). A first draft of the code question repeated the course's own
Money example and CRS-001 refused it; it was rewritten as a transfer case (a
date taken by ObjectNormalizer), not fenced to pass. Published figures moved
with the bank: language policy 731 questions / 710 English / 274 of 275 hard in
English; Mock 5 universe 656.

### P1.7 — Content metrics are not the learner (F5)

Already in place before this work, and kept: the introduction separates
coverage from readiness, and PRÊT-CANDIDAT opens with « `EXAM_READY` ne parle
pas de vous ». What was missing was the learner's side: « Ma progression »
showed no answer at all.

Added: a table « Ce que vos réponses montrent », per official topic, in four
columns that are never added together — Practice and Compréhension (corrected
after each answer: learning), Exam and Mocks 1, 2, 3, 5 (VALIDATION, possibly
seen before), Mock 4 (the holdout). Each cell gives the **first** answer and,
when a question was retried, the **latest**. Under 5 distinct questions it
reads « données insuffisantes ». No pass probability is computed: the pass mark
and topic weights are not published. Logic in `website/src/lib/observed.ts`;
`website/tools/verify-observed.mjs` (CI step) checks no history, partial,
repeated, contradictory and mixed-channel histories, and was shown to fail on
two injected defects (a retry counted as a first answer; Mock 4 merged into
training), each file restored byte-identically.

### P2.4 — Comprehension recorded and retaken (F10, part)

Answers go to a new slice `comprehension` (storage schema 4; the migration adds
an empty slice and touches nothing else), never to `attempts`, so Practice
weak points and Mock 5 never see them. The check offers « Reprendre seulement
celles-ci » for the questions last missed in the lot, and the summary offers a
retake of the ones just missed. ADR-0010 has an addendum. Two new a11y
surfaces: « progression — historique » (seeded) and « comprehension — reprise ».
`verify-practice-ui` read the storage version as a literal 3; it now reads it
from `storage.ts`, as `verify-agenda-ui` already did — the assertion is
unchanged.

### P2.5 — Roadmap and mocks (F6)

Reproduced: `build_roadmap.py` wrote « 61 / 84 / 68 questions » and « 519
questions éligibles » — bank sizes presented as sitting sizes. The mock lines
are now read from the blueprints the payloads are built from: Mock 1 41 q /
42 min from 62, Mock 2 52 / 60 from 84, Mock 3 45 / 54 from 68, Mock 5 10 to 40
from 653, Mock 4 75 / 90. `MocksBlueprintTest` already holds the blueprint equal
to the bank.

The hand-written `study-roadmap.md` and `exam-readiness.md` still carry the
11 September snapshot (71 578 words, 519 questions, 174 cards); each now opens
with a dated notice giving the 13 October measurement (111 276 words, 653
questions, 1 909 cards, first pass 59.9 h, spaced reviews 57.5 h).

**Comprehension load: blocked on the owner.** The 1 117 comprehension
questions (15.7 h raw, 25.1 h at the plan's ×1.6) are budgeted nowhere. Counted
in each item's first pass (applied since, see « Owner decisions », 3),
the published parameters no longer fit: `build_roadmap.py` stops with « Pas de
place » for exam 2026-12-15 at 160/220 min. Measured alternatives:

| Parameters | Lots done | Reviews lost after the exam |
|---|---|---|
| exam 15 Dec, 210 min weekday / 270 weekend | 27 Nov | 59 (3.1 h) |
| exam 29 Dec, 160 / 220 (unchanged) | 9 Dec | 30 (1.6 h) |
| exam 15 Dec, 160 / 220, or 180 / 240, or 200 / 240 | — | infeasible |

### P2.6 — Sources (F9)

The one citation still answering 404 (`language/exceptions.xml`) is in a
HOLDOUT question. By the owner's decision the holdout is not touched now, and
the right replacement page cannot be chosen without reading the question.
Version pinning of branch URLs is not started: `AUD-02` reads the ref of every
citation, so a format change has to be designed with it, not applied in bulk.

## Owner decisions (2026-10-13)

1. **Holdout (F4): control only, later.** The holdout is not read or rewritten
   now. The per-pool control stays out of the audit, as
   [`aud10-per-pool.patch`](aud10-per-pool.patch) (`git apply` it), until the
   rewrite can follow; F4 stays `BLOQUÉ`. Landing the control alone would turn
   CI red on a known defect, and disabling the group to land it would be a
   weakened check.
2. **Delivery: PR, merge, deploy**, as for the previous lots: branch, gates,
   PR, green CI, squash merge, deployment, production smoke test.

3. **Comprehension load: option B** (owner, 2026-10-13, « B »): the exam moves
   to **2026-12-29**, the daily time is unchanged (160 / 220 min). The patch is
   applied: each item's first pass counts its comprehension questions; CI and
   the published plan use `--exam 2026-12-29`. Measured: first pass 86.3 h
   (comprehension 26.4 h), all items introduced 9 Dec, Mock 4 on 26 Dec, 30
   reviews (1.6 h) fall after the exam. The hand-written roadmap and readiness
   pages keep their 11 September analysis, under a notice that says so.

## Delivery log

| Lot | PR | Merge | Deployment |
|---|---|---|---|
| P0.1–P0.3 | #394, CI green | squash `fdf4035` | Pages run 38450923116 — Deploy success; job « Production smoke test » 115482864576 success, log read (1117 comprehension questions served). Direct fetch of the site from this session is refused by the egress proxy. |
| P1.7, P2.4, P2.5 (part) | #395, CI green | squash `6c1448c` | job « Production smoke test » 115498368380 success, log read. It checks served pages and payloads, not the client-side progression table, which the CI a11y audit renders. |

## Next action

P1 in the mission's order: P1.5 (Serializer) and P1.4 (Cache) extend the two
items corrected in P0, but both courses sit within ~10 words of the STANDARD
budget of 900, so the extension needs the level question settled on the item's
own merits (matrix field guide) before any paragraph is written. Then P1.6
(multiple-answer questions in VALIDATION and COMPREHENSION, baseline 0 and 0).
