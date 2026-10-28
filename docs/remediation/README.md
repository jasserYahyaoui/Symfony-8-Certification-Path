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
| F7 | Too few multiple-answer questions | **VÉRIFIÉ** — P1.6: +33, then +10 for PHP and Automated Tests (4 VALIDATION, 39 LEARNING in all), every choice executed or read in its source | see P1.6 |
| F8 | Unsourced "not examinable" restrictions | **VÉRIFIÉ** — its three symptoms are answered: the PHP API reduced to versions (P1.1), helpers said not to need their API details (P2.7 Console), the Validator catalogue sent to the reference (P1.3); Serializer (P1.5), Cache (P1.4) and Forms (P1.2) extended too. On 2026-10-18 a pattern search over the 163 courses (« hors périmètre », « pas au programme », « pas besoin », « référence »…) found 9 scope sentences, all read: each rests on `docs/syllabus/exclusions.yml` (UX, Doctrine, assets, PHPUnit Bridge, ESI, third-party transports) or on a note of `docs/syllabus/official-syllabus.md` (third-party bridges, « Twig syntax up to 3.22 »). A pattern search finds what it looks for; no other wording was hunted | see P1.5, P1.3, P1.2, P1.1, P2.7 |
| F9 | PHP source 404 and mutable branch URLs | **BLOQUÉ** (the last 404 is in a HOLDOUT question) — pinning À FAIRE | see P2.6 |
| F10 | No per-card recall record; comprehension stores nothing | **VÉRIFIÉ** (local) — comprehension recorded (P2.4); per-card recall and due day (P2.3) | see P2.4, P2.3 |

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

### P1.4 — Cache: PSR-6 and PSR-16 (F8, part)

Executed first, on Cache 8.0.15 (`tools/behavior/checks/cache.php`, four new
checks, 18 facts in all; `psr/simple-cache` added to `tools/behavior`):

| Behaviour | Result |
|---|---|
| PSR-6 miss / `set()` without `save()` / saved `null` | `isHit()` false · nothing stored · `isHit()` **true** with `get()` null |
| `saveDeferred()` (FilesystemAdapter) | invisible to another pool until `commit()`; a `getItem()` of the key on the deferring pool commits it |
| `saveDeferred()` (ArrayAdapter) | stored at once: it calls `save()` |
| PSR-16 | default on a miss; `has()` true for a stored null |
| Bridges | `Psr16Cache` = PSR-16 over a pool; `Psr16Adapter` = pool (and Contracts) over PSR-16 |

Not taught, and why: reserved key characters `{}()/\@:`. `ArrayAdapter` and
`Psr16Cache` check them only inside `assert()`, so the result depends on
`zend.assertions`; executed here, `a{b}` was accepted. A rule that holds only
in some runtimes is not stated as one.

**Level: STANDARD → DEEP**, on the item (three access contracts that diverge
where a question can bite). Recorded with the Serializer promotion in
`docs/policy/revision-budget.md`, which says plainly that two promotions in one
day is the pattern the policy warns about, and why these are not that. Corpus
ceiling 27/122/14 = 145 500; the test holds the counts equal to the matrix.

Content: two course sections (« PSR-6 : écrire soi-même », « PSR-16 et les
ponts »), two outcomes (`OUT-wq0bg1r133y3`, `OUT-8h04z2xxjp34`), two flashcards,
two LEARNING questions (one `hard` code question, one multiple-answer), two
COMPREHENSION questions (one multiple-answer). Figures: language policy 733 /
712 English / 275 of 276 hard; Mock 5 universe 658; plan regenerated (all items
in 10 Dec, Mock 4 on 26 Dec, 34 reviews after the exam).

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
| Exam date (owner option B) | #397 | squash `c4c5348` | smoke 115538068644 success, log read |
| P1.5 Serializer | #398 | squash `afe8926` | smoke 115735269277 success, log read (519 LEARNING, 615 outcomes, lot-26 comprehension 18) |
| P1.4 Cache | #399 | squash `effce50` | smoke 115749438772 success, log read (lot-18 comprehension 16, 1 122 in all) |
| P1.3 Validator | #412 | squash `1284271` | smoke 116625759008 success, log read (295 ok, 0 error; « ok remediation P1.3 lot-08 ») |
| Deep audit 8.0 (report, Cache checks, terminology senses) | #413 | squash `ebdefeb` | smoke 116661090571 success, log read (295 ok, 0 error) |
| Deep audit 8.0, gaps (Config, expose_security_errors, TranslatableMessage) | #414 | squash `e76910c` | smoke 116685357696 success, log read (295 ok, 0 error) |
| P1.2 Forms | #415 | squash `0318807` | smoke success, log read (« ok remediation P1.2 lot-07 ») |
| P1.1 PHP API | #416 | squash `a8bb7fd` | smoke 116895810622 success, log read (« ok remediation P1.1 lot-01 »; 297 ok and 0 error in the last 400 lines) |
| P2.7 HttpClient | #417 | squash `17253ae` | smoke 117170953200 success, log read (« ok remediation P2.7 lot-02 »; 298 ok and 0 error) |
| P2.7 Console | #418 | squash `baa8cec` | smoke 117187468251 success, log read (« ok remediation P2.7 lot-12 »; 299 ok and 0 error) |
| P2.7 Profiler | #419 | squash `bfd0c04` | smoke 117205386196 success, log read (« ok remediation P2.7 lot-15 »; 300 ok and 0 error) |
| P2.7 Translation | #420 | squash `abbcca0` | smoke 117226027552 success, log read (« ok remediation P2.7 lots 06 and 16 »; 301 ok and 0 error) |
| P2.7 Filesystem, Finder | #421 | squash `76ee0dd` | smoke 117235982393 success, log read (« ok remediation P2.7 lot-21 »; 302 ok and 0 error) |
| P2.7 Runtime (CI harness only, no site change) | #422 | squash `5882b0c` | smoke 117244563874 success, log read (302 ok and 0 error); the `runtime.*` checks ran green in its CI |
| P2.3 Flashcard recall | #423 | squash `1032fc2` | smoke 117400911384 success, log read (304 ok and 0 error; « ok remediation P2.3: flashcards.json (1940 cards, fronts only); card anchors and recall slot served ») |
| P3.3 Journal of corrections, version guard | #424 | squash `2a8f7da` | smoke 117423979751 success, log read (308 ok and 0 error in the last 400 lines; « ok remediation P3.3: journal des corrections served, entries anchored », « question-versions.json (59 rewritten questions) ») |
| P3.1 Semantic repetition, « difficulté estimée » | #425 | squash `9cebee6` | smoke 117493721315 success, log read (308 ok and 0 error in the last 400 lines; « question-versions.json (70 rewritten questions) ») |
| P3.2 (first part) Reasoning before the correction, delayed checks | #426 (first CI run red on `verify-navigation`, fixed in the PR) | squash `8b6c898` | smoke 117514123216 success, log read (309 ok and 0 FAIL in the last 400 lines; « ok 200 …/controle-differe ») |
| P3.2 (second part) First exposures, parallel forms, questions crossing components | #427 | squash `6c5b918` | smoke 117528038621 success, log read (309 ok and 0 FAIL in the last 400 lines; « practice 583 questions, all LEARNING », « mock-5 724 eligible ») |
| Journal entries for #427 (`CVR-a4813yxth40x`, `CVR-zzn9ysgne2mg`) | #428 | squash `bdc0de5` | smoke 117637182148 success, log read (309 ok and 0 FAIL in the last 400 lines; « P3.3: journal des corrections served, entries anchored ») |
| Tracker: #428 delivered, F8 VÉRIFIÉ | #429 | squash `f8c9ad2` | smoke 117643408266 success, log read (309 ok and 0 FAIL in the last 400 lines) |
| Independent evaluation: reviewer's brief, sample drawn from the reviewer's seed | #430 | squash `800278b` | smoke 117655872723 success, log read (309 ok and 0 FAIL in the last 400 lines) |
| Independent evaluation: attachable review packs, HOLDOUT in mixed bank files | #431 | squash `806a8a9` | smoke 120014740019 success, log read (309 ok and 0 FAIL in the last 400 lines) |
| Exam pitfalls rehearsed by flashcards: 62 cards, CI audit, three corrections | #432 | squash `c48317d` | smoke 120947746506 success, log read (309 ok and 0 FAIL in the last 400 lines; « P2.3: flashcards.json (2002 cards, fronts only) ») |
| Page preamble outside the revision budget (ADR-0011, OBJ-001), and the #432 journal | #433 | squash `e8ea073` | smoke 121453492993 success, log read (309 ok and 0 FAIL in the last 400 lines) |
| Third refinement pass: 21 defects corrected in 18 courses, three central facts | #434 | squash `0bf0ba6` | smoke 121470795931 success, log read (313 ok and 0 FAIL in the last 400 lines; the four « third-pass » lines) |
| Third pass, terminology: the tool's misrewrites undone in 71 courses, the tool fixed; the #434 journal | #435 | squash `dd3ea45` | smoke 121496667051 success, log read (313 ok and 0 FAIL in the last 400 lines; « P3.3: journal des corrections served, entries anchored ») |
| Journal entry for #435, delivery rows, third pass closed | #436 | squash `cdb00a6` | smoke 121517190085 success, log read (313 ok and 0 FAIL in the last 400 lines; « P3.3: journal des corrections served, entries anchored ») |
| Abstract classes: PHP 8.4 abstract properties (section, 3 cards, 3 comprehension questions), each rule executed | #437 | squash `ec289fd` | smoke 121823444013 success, log read (314 ok and 0 FAIL in the last 400 lines; « ok third-pass lot-01 abstract classes: PHP 8.4 abstract properties, each rule executed », « lot-01: 54 question(s) in the payload ») |
| Journal entry for #437 (`CVR-6y0s02nyfcv0`), delivery rows | #438 | squash `bd3c25f` | smoke 121851776449 success, log read (314 ok and 0 FAIL in the last 400 lines; « P3.3: journal des corrections served, entries anchored ») |
| Pedagogical course pages (ADR-0012, `FMT-001`, `QCM-001`), pilot: *Deprecations best practices* | #439 (first CI run red: the revision plan was not regenerated after three words were added, fixed in the PR) | squash `04f42b3` | smoke 122183663573 success, log read (315 ok and 0 FAIL in the last 400 lines; « ok pedagogical lot-03 deprecations: running example, executed facts, quiz corrections and digest served ») |
| Journal entry for #439 (`CVR-f4zwnrj6ngdr`), delivery rows for #438 and #439 | #440 | squash `e9a2abd` | smoke 122209369550 success, log read (315 ok and 0 FAIL in the last 400 lines; « P3.3: journal des corrections served, entries anchored ») |
| Pedagogical format, lot 09: *Dependency Injection component* and *Service container*; two corrections (four injection forms; `--show-hidden` shows only internal services) | #441 | squash `8148cd0` | smoke 122468005238 success, log read (317 ok and 0 FAIL in the last 400 lines; « ok pedagogical lot-09 dependency-injection-component », « ok pedagogical lot-09 service-container ») |
| P1.7, P2.4, P2.5 (part) | #395, CI green | squash `6c1448c` | job « Production smoke test » 115498368380 success, log read. It checks served pages and payloads, not the client-side progression table, which the CI a11y audit renders. |

### P3.3 — Maintenance: a journal of corrections, and answers that follow versions

The mission asks for a changelog of pedagogical corrections linking courses,
cards and questions, a registry of central facts (it exists, P0), regression
controls on links, versions, budgets, pools, generated content and executable
examples, and a new independent evaluation.

| Piece | What it does |
|---|---|
| `docs/corrections/changelog.yml` | 21 entries (8 `CORRECTION`, 2 `SCOPE`, 11 `EXTENSION`), #394 to #421: what was taught (`was`, quoted), what replaced it, the course sections, whats-new pages, flashcards and questions carrying it, the central facts executing it, and the questions `reversioned` |
| `tools/corrections/derive.py` | reads a commit and its parent from git and reports every changed entity — course sections by heading, cards, questions with pool and version, outcomes. HOLDOUT questions are counted, never named |
| `derive.py --check` (CI) | each entry's ids were changed by its commit; its PR and date are its commit's; `reversioned` equals the questions whose version the commit raised; and no cited commit changed a prompt or key without a new version |
| `derive.py --versions <base>` (CI, every push) | the regression guard: a question whose prompt or correct answer changes must get a new version |
| `derive.py --prove` (CI) | 13 injections in memory, plus the detector on a real commit (a rewritten key vs a reworded distractor): all reported |
| `CorrectionsChangelogTest` | every id still exists, every linked section is still a heading, no question is HOLDOUT (proved by injection, restored under SHA-256), the page anchors every entry and prints no question id |
| « Journal des corrections » (`/docs/corrections`) | newest first; the former wording, what is true, links to sections and card anchors, questions counted by pool |
| `question-versions.json` | the 59 non-HOLDOUT questions at version 2; « Ma progression » and Mock 5 stop counting an answer given to an earlier version, and « Ma progression » says how many |

The lists were derived, not recalled: two terminology corrections were split
from the content they shipped with by the word diff of each section (#412:
« chaîne → string » undone, 17 courses and 32 cards; #413: requirement,
filtrer and the answer sense of « réponse », 14 courses and 26 cards).
Re-deriving every remediation commit found **no question whose prompt or key
changed without a new version**: the seven questions whose prompt or key was
rewritten (five in #394, two in #416) all moved to version 2. Writing the page's own text, the terminology tool
turned « chaîne » into « string » and « réponse » into « Response » once more —
the very defect two entries record; the sentences were rephrased, the list was
not touched.

**Learner side.** An answer stored before a question was rewritten answered
another question — for the keys of #394, one whose expected answer was false.
Until now it kept counting in « Ma progression » (first answer) and in Mock
5's weak items. `verify-observed.mjs` (7 new cases) and `verify-weakness.mjs`
(new) pin it; removing either filter fails them (restored byte-identically).
Mock 4 answers are never discounted: the holdout has no published versions,
and no remediation commit changed a HOLDOUT question.

**Not done.** The independent evaluation after remediation needs a reviewer
who is not this project: `MISSING`, and nothing here claims READY.

### P3.1 — Question quality: semantic repetition, and « difficulté estimée »

The mission asks for detection of semantic repetition beyond textual
duplicates, explicit editorial criteria, and « difficulté estimée » rather
than a certified difficulty until real data exists.

**Semantic repetition.** `tools/audit/semantic_repetition.py` (CI, with
`--prove`) flags every LEARNING/VALIDATION pair at TF-IDF cosine ≥ 0.45 and
fails on one not reviewed in `docs/audit/semantic-repetition/reviewed.yml`,
reviewed against an older version, or still `REPEAT`. Of 82 visible pairs,
**twelve were `REPEAT`** — the same situation and conclusion reworded, seven of
them giving the answer of a VALIDATION question to whoever practised the
LEARNING one. Each LEARNING side was rewritten under a new version, on another
outcome angle of its item, its key executed where it can be
(`tools/behavior/single/semantic-p31.php`, eight single-answer checks;
`questions/semantic-p31.php`, the traits one; three were proved by flipping a
key). After: 70 visible pairs, 46 `PARALLEL`, 24 `DISTINCT`, 0 `REPEAT`. The
report is `docs/audit/semantic-repetition/README.md`.

Four first drafts were refused and redone, not forced: two by CMP-002 (71 %
and 74 % similar to comprehension questions that already asked them), and two
because a rewrite moved a Practice question towards a HOLDOUT question of the
same item (0.21 → 0.54 and 0.38 → 0.44, measured as numbers, nothing read).
HOLDOUT pairs are counted only (25, none involving a rewritten question).

`tools/behavior` gains `symfony/dotenv` and `symfony/twig-bundle` 8.0.15, to
execute the `.env` cascade and TwigBundle's `strict_variables` default; a
new `single/` directory holds single-answer checks, held to its shape by
`SingleAnswerExecutionTest` (questions/ stays multiple-answer, as
MultipleAnswerEvidenceTest requires).

**« Difficulté estimée ».** The Practice filter reads « Difficulté estimée »
(facile, moyenne, difficile); Mock 3 is « Highest estimated difficulty »
rather than « Certification difficulty », in its blueprint, page, footer and
the home page. The exam menu already said « difficulté estimée ».

**Readiness moved, and why.** Certification Readiness is unchanged (163
items count either way), but two items pass from `REFINED` to
`MASTERED_READY` — *TwigBundle* and *Error handling* — because their rewritten
question is labelled `hard`, the third criterion of that state. Both labels
follow the question (two facts combined: TwigBundle's `%kernel.debug%` default
and both behaviours; ErrorListener's order of precedence); a third rewrite,
on `_format`, was lowered to `medium` because it applies one rule. The
criterion rests on an estimated difficulty, and says no more than that.

**Not done, and why.** A second reviewer of the pairs, and item difficulty or
discrimination measured on real answers: both `MISSING`. The editorial
criteria (autonomous prompt, explicit hypotheses, one defensible key,
plausible choices, causal explanation, relevant source) are what the rewrites
were held to; they are not yet a rule a script can check.

### P3.2 — Transfer and retention (first part): reasoning before the correction, delayed checks

The mission asks for an explanation requested before the reveal and compared
with the expected one, and for checks delayed after the reading, not only
right after it.

| Piece | What it does |
|---|---|
| « Écrire mon raisonnement avant la correction » (Practice) | an option, remembered per browser. After the answer, a field asks why; the correction is absent from the DOM until a reason of 15 characters or more is given, or « Je ne sais pas l'expliquer ». The reasoning is then quoted beside the expected explanation, and the learner says whether it matches: oui, en partie, non |
| what is stored | only the rating, on the attempt (`reasoning`: matched, partial, missed, skipped); never the text |
| Mock 5 | a right answer rated `missed` — a right choice for another reason — counts as a failure; `partial` does not. The series report counts them |
| `src/lib/delayed.ts` | a lot's comprehension check comes back 7 days after the last answer on the lot, then 14, then 28; six questions: missed last time first, then never asked, then right longest ago. No efficacy claimed for the intervals |
| `/controle-differe` | the lots due and the next ones; a sitting reuses the comprehension check, corrected after each answer, its answers marked `delayed` |
| « Ma progression », footer | how many delayed checks are due, linked; the page is in the footer, so it is reachable before any is due (`verify-navigation` failed the first CI run on it) |

Verified: `verify-delayed.mjs` (9 cases), `verify-weakness.mjs` (+2),
`verify-transfer-ui.mjs` in a browser (6 checks: no correction in the DOM
before the reasoning, the field focused, the 15-character floor, the rating
stored and the text not, the option remembered; a lot due after 8 days, its
missed question served first and recorded `delayed`, the next one 14 days
later); five new accessibility surfaces, one of them the page a learner with nothing due sees.

### P3.2 — Transfer (second part): first exposures, parallel forms, series mixing lots

The mission asks for unseen series mixing several components without
announcing the trap, and for parallel forms of assessment with explicit
first-exposure rules. The rules are `docs/policy/first-exposure.md`.

| Piece | What it does |
|---|---|
| `src/lib/exposure.ts` | one definition of a first exposure: the first answer to a question as it stands, in any mode (a rewritten question is new) |
| « Série inédite » (`/practice?serie=inedite`) | ten LEARNING questions never answered, dealt lot by lot — the first round takes one from each lot, and two consecutive questions share a lot only when no other lot has one left. Filters are hidden; nothing outside the question names its lot, item or topic before the answer |
| « Forme parallèle de mes points faibles » (`/practice?serie=parallele`) | for each question whose latest answer was wrong, in any mode, one never-answered LEARNING question on the same learning outcome — or the same item when the bank has none — never the missed one; it says how many matched on the outcome, on the item, and how many weak points have nothing left |
| series report | both say every answer was a first exposure; the series is recorded (`series` on the practice session) |
| `PayloadBuilder::assertPoolsAllowed()` | at build, on Practice, Exam Mode, Mocks 1 to 5 and the exam series: each question belongs to a pool the payload may carry; a HOLDOUT id is never printed, a comprehension question is refused |

**A guarantee that was a label.** Practice Mode's « LEARNING only » rested on a
filter and on `PracticePayloadTest` asserting the payload's `pool` label.
Making `Pool::isExposedInPracticeMode()` accept VALIDATION left that test green;
`FirstExposureRulesTest` fails on it (proved, file restored under SHA-256). Its
four injections — VALIDATION in Practice, LEARNING in Exam Mode, HOLDOUT in the
exam series (refused without its id), a comprehension question in Mock 1 —
run on every `phpunit`.

**Verified.** `verify-exposure.mjs` (15 cases, 200 seeds where order is
random) and `verify-transfer-ui.mjs` (3 new browser checks: ten questions from
ten lots, none answered before, nothing outside the question naming its item
or topic; the series recorded and the next one skipping it; the parallel
replay serving another question on the missed outcome). Each was proved by
mutation, files restored under SHA-256: taking answered questions, dropping
the lot-by-lot dealing, preferring the item to the outcome, and counting every
answered question as weak each fail `verify-exposure`; printing the item in
the series banner fails `verify-transfer-ui`.

**An audit that measured a frame.** The full gate run of this PR failed once
on « flashcard — notée » (contrast, 2 nodes) and passed on the same build
rebuilt. Reproduced, not called a flake: auditing that surface right after the
click failed 2 runs in 20, always the « Bien » button sampled while its
colours faded to the hover state (3.12:1 and 3.69:1, intermediate colours of
no rest state); after the fade, 0 in 20. `a11y-audit.mjs` now waits for every
finite animation to end before axe runs — the hover state is still measured,
and an animation that never ends fails the surface on the timeout.

**Parallel forms, and what is not one.** A missed question and its parallel
replay, a `PARALLEL` pair of P3.1, a comprehension and an exam question are
built to measure the same thing with different questions. Exams 1 to 6 are
not parallel forms: they rise in estimated difficulty on purpose. No form is
equated — difficulty is the author's estimate and item statistics are MISSING.

**Questions crossing components.** Two LEARNING questions put the trap in
one component and the deciding rule in another, each anchored on the item
whose rule decides (the exam-bank schema takes one item) and tagged
`integration`; each is executed with its distractors' own cases
(`tools/behavior/single/integration-p32.php`):

- `#[MapRequestPayload]` receiving `{"quantity": "three"}` for an `int`: **422**,
  because the resolver deserializes with `collect_denormalization_errors` and
  turns the collected type error into a violation (Serializer item). Executed
  alongside: malformed JSON 400, `text/plain` 415, and
  `validationFailedStatusCode: 409` moving the 422 but not the 400;
- a `kernel.request` listener at priority 64 reads `_route` as `null`:
  `RouterListener` is a listener at 32 (EventDispatcher item); at 16 it reads
  the name;

A third — `{{ 'cart.total'|trans }}` at `fr_CA`, defined only in `en`, shows the
English text through `fr_CA`, `fr`, then `fallbacks` (Internationalization
item) — was written, executed and passed every rule, and is **held back**: its
60 seconds tip the revision plan, which then introduces the last items on
14 December instead of 11 and no longer fits the four mocks before Mock 4
(`build_roadmap.py`: « Pas de place »). The plan has no slack left at the
owner's pace (160/220 min, four new items a day, exam on 29 December), so any
new question on a late item does the same. Moving the pace or the date is the
owner's decision; the question waits in the session scratchpad until then.

Writing the first one showed a course statement too broad: « ce qui vient du
body [échoue] en 422 ». A body that cannot be decoded gives 400, a format
with no decoder 415, and `validationFailedStatusCode` changes only the 422 —
read in `RequestPayloadValueResolver` 8.0 and executed. The *Argument value
resolvers* course now says so (paragraph, trap, key point), and the
*Serializer* course says that `#[MapRequestPayload]` collects denormalization
errors. No threshold moved: none of the questions makes a pair at cosine 0.45 or
more with any question, HOLDOUT included (counted only), and CMP-002, ARC-001
(one archetype corrected from CODE_OUTPUT, as the template is inline) and the
length audit pass.

### After remediation — exam pitfalls rehearsed by flashcards (2026-10-24)

Not one of F1–F10: an extension proposed after them and accepted by the owner.
Report: `docs/audit/pitfall-flashcards/README.md`.

| | |
|---|---|
| **Reproduced** | Every « Pièges d'examen » paragraph — 788 in 163 courses — read against all the cards of its item: 63 traps had no card that rehearses them (23 in lot 01). |
| **Corrected** | 62 cards added, each fact executed (PHP 8.4.19, Symfony 8.0.15) or read in its source; one pitfall NO_CARD, unreachable in 8.0. `FLC-h2e5dj32pzgg` gave 500 where a `NotFoundHttpException` gives 404, now conditioned. The routing course said Unicode properties work in a requirement; without `utf8` the route does not compile. The HTTP Caching course now says *on-demand* invalidation is outside HTTP, as RFC 9111 §4.4 defines the other kind. |
| **Guarded** | `tools/audit/pitfall_flashcards.py --max-gaps 0` and `--prove` in CI; two central facts with behavior checks and forbidden patterns. |
| **Cost** | Plan load 147.8 h → 148.6 h; every item still introduced by 2026-12-11, mocks on the same dates. |
| **Journal** | Four entries citing squash `c48317d` (#432), written in the next PR: `CVR-r7b0wgkjebs5`, `CVR-7av4cwx5kw2d`, `CVR-86r6ry4qg2dn` (corrections), `CVR-2kf5ma2p370b` (the 62 cards). |

## Next action

P3.1 is delivered in #425, P3.2 in #426 and #427, and the journal entries for
#427 in #428. The
held-back third integration question waits for a decision on the revision
pace or date, which is the owner's. What remains of the mission is the
independent evaluation after remediation, which needs a reviewer outside this
project: MISSING. `docs/review/independent-evaluation-brief.md` prepares it —
who may review, what not to read (the HOLDOUT), the grid of the earlier
reviews, the commands that verify each claim, and a sample the reviewer draws
from their own seed (`tools/review/draw_sample.py`, `--prove` in CI).

A first attempt, on 2026-10-22, was **not evaluable**: the reviewer (M365
Copilot) could open none of its links and gave no score, rightly
(`docs/audit/independent-evaluation-2026-10-22/REVIEW.md`). It taught two
things. A reviewer may not browse, so `tools/review/build_pack.py` builds files
to attach — the project at a commit, and Symfony 8.0's code and documentation
at theirs. And the brief was wrong about the HOLDOUT: 27 of its questions sit in
the bank files of lots 01 to 13 and the golden slice, which the prompt allowed.
The pack's guard refused its own first build on them; the pack now re-serialises
those files without their HOLDOUT entries, strips the ids from the matrix, and
`--prove` (in CI) shows each leak refused. Nothing was exposed: the reviewer
opened nothing.

Item statistics (P3.1) now have a first local source — per-card recall —
but no shared data: they stay MISSING until learners' answers are collected
with consent. By the owner's decision of 2026-10-16, Mock 4 is
not sat now and is no longer raised in reports; F4 and F9 stay blocked.

### P2.3 — Flashcards: a recall rating and a due day per card (F10)

The cards existed, with their back hidden behind a native `<details>`; nothing
was recorded. The mission: a recall result and a due day per card, the planned
scheduler really wired to the page, migration, export/import, the card's
version, a reset — and never « back shown » taken for « answer known ».

| Piece | What it does |
|---|---|
| `src/lib/review.ts` | port of `src/Review/ReviewScheduler.php` (docs/policy/review-algorithm.md); `verify-review.mjs` replays 5 467 sequences, 31 288 states, through both — injected: dropping the 180-day ceiling fails it; two unreachable branches cannot (documented) |
| `FlashcardRecall` | inside each card's `<details>`, after the back: « Avant de lire le verso, vous saviez ? » and four buttons (À revoir, Difficile, Bien, Facile); the focus moves to the next due day; « Oublier cette carte » |
| storage schema 5 | slice `flashcards`, card id → schedule, `card_hash`, `due_on`, history; v4 migrates by adding an empty slice |
| card version | `DocsGenerator::cardVersion()`: a hash of front and back; a schedule for another version is discarded |
| `flashcards.json` | id, version, item, anchor URL, front — never the back (BuildTest, production smoke) |
| « Ma progression » | cards rated, due today (linked to the card's anchor), changed cards; JSON import beside export and reset |

`verify-flashcards-ui.mjs` drives it in a browser, by keyboard: opening a card
records nothing; « Bien » schedules J+1, then J+3; the schedule survives a
reload at schema 5; a changed card restarts; « Oublier » removes it; a v4 store
keeps its attempts and the due card is listed with its anchor; export → import
is identical and a JSON without `schema_version` is refused. Injected: reusing
a schedule across versions fails it. Accessibility: three new surfaces — a card
rated at phone width in light and dark, and the due list — axe 0.

No efficacy is claimed, on the page or here: the schedule is the plain one of
the policy, and the rating is the learner's own judgement.

### P2.7 — Runtime: the minimal entry point, run in CI

The mission asks for a minimal, executable entry point and a trace of its
cycle. The *Runtime* course already had both, executed by hand in a scratch
project; nothing ran them again. `tools/behavior` now requires
`symfony/runtime` 8.0.14, whose Composer plugin writes
`vendor/autoload_runtime.php` (allowed in `config.allow-plugins`; locally, as
root, `COMPOSER_ALLOW_SUPERUSER=1` is needed for the plugin to run, CI is not
root). `tools/behavior/checks/runtime.php` writes a front controller to a
temporary directory and runs it in a fresh PHP process, with `GenericRuntime`
(`SymfonyRuntime` needs a `.env`). Four checks, four mutations — three of them
on the generated `vendor/autoload_runtime.php` — all proved.

| Fact | Executed |
|---|---|
| cycle | above the `require_once` twice, below once; then runtime constructed, closure called, application run |
| return value | `return 42` → `TypeError` « callable object expected »; a closure returning a string → `TypeError` |
| `vendor/autoload.php` first | the script runs to its end, no application, exit 0 |
| arguments | `array $context` resolved; `array $ctx` → « Cannot resolve argument "array $ctx" » |

No course text changes: the four facts were already taught and are now
regression-tested, linked to the course by four central facts.

### P2.7 — Filesystem, Finder: a portable test environment

The mission asks for controlled, portable test environments. Executed first on
Filesystem and Finder 8.0 under Linux (`tools/behavior/checks/filesystem-finder-portable.php`,
three checks, four vendor mutations, all proved, restored byte-identically).

| Behaviour | Result |
|---|---|
| `Path::canonicalize('/var/www/../log/./x')`, `Path::join('a', '/b', 'c/')` | `/var/log/x`, `a/b/c` |
| `Path::canonicalize('a\\b/../c')` | `c`: under Unix a backslash is a filename character |
| `isAbsolutePath('c:\\Windows')` | **`false`** under Linux |
| `tempnam($dir, 'test_', '.txt')` | an empty file, unique name, prefix and suffix kept; `remove()` deletes the tree |
| Finder without sort | the filesystem order (`f4, f12, f9, f10, f1…` for files created f1 to f12) |
| `sortByName()` / `sortByName(true)` | `f1, f10, f2` / `f1, f2, f10` |

**A documentation/code divergence, kept visible.** `components/filesystem.rst`
(8.0) lists `isAbsolutePath('c:\\Windows')` as `true` and says backslashes are
converted. `Path` does both only when `DIRECTORY_SEPARATOR` is a backslash — the
class docblock says so (« On UNIX, backward slashes are preserved ») — so the
documented results are the Windows ones. Same on 7.4.21. The course gives both
and tells a portable test to write `/`. The check refuses to run where
`DIRECTORY_SEPARATOR` is not `/`, rather than passing there vacuously.

**Process: no change.** Its course already executes the array/string escaping,
environment inheritance, both timeouts and `stop()`; at 857 of 900 body words a
new section would push it towards DEEP on word count, which the level policy
forbids.

**Levels: unchanged (STANDARD)**; *Filesystem* 794, *Finder* 714 body words.
Content: two outcomes, two sections, two flashcards, two LEARNING questions (one
`hard`), two COMPREHENSION questions, three central facts.

### P2.7 — Translation: several catalogues, nested plural and select

The mission names several catalogues and nested plural/select. Executed first
on Translation 8.0.15 with the intl extension
(`tools/behavior/checks/translation-icu.php`, five checks, six vendor
mutations, all proved, restored byte-identically); same results on 7.4.21.
Plural categories are ICU data for each locale; the checks pin the Symfony
side — the locale a message is formatted in, the catalogue that wins, how
parameter keys are read.

| Behaviour | Result |
|---|---|
| `{n, plural, one {# fichier} other {# fichiers}}`, `fr` | `0 fichier`, `1,5 fichier`, `2 fichiers`; in English, `0 files` |
| `=0` before `one` | `=0` wins for 0 |
| `select` / `plural` without `other` | `InvalidArgumentException` (U_DEFAULT_KEYWORD_MISSING) on every `trans()`, even for a matched value |
| nested `select` → `plural`; `#` | they nest; `#` is the innermost plural's number; `selectordinal` 1st 2nd 3rd 4th 11th 21st |
| parameter keys | `name` and `%name%` both fill `{name}`; `%name%` in an ICU message stays text; a missing parameter leaves `{name}` |
| same key in `messages` and `messages+intl-icu` | the ICU version wins |
| two resources of one domain and locale | the last loaded wins key by key |
| key only in the fallback locale | formatted with that locale's rules (`0 files` for `fr` → `en`) |

**Levels: unchanged (STANDARD)**; *Translations and pluralization* 836 and
*Internationalization* 881 body words. Content: two outcomes, two sections, two
flashcards, two LEARNING questions (one `hard`), two COMPREHENSION questions,
five central facts.

### P2.7 — Profiler: a data collector end to end

The mission asks for a complete, tested data collector. `tools/behavior` gains
`symfony/framework-bundle` 8.0.15 (for `AbstractDataCollector` and
`KernelBrowser`; the lock changes no other version). Executed first
(`tools/behavior/checks/profiler.php`, three checks, six vendor mutations, all
proved, restored byte-identically); same results on 7.4.21. The functional-test
side was already taught and executed on *Profiler object* (lot 13); the new
check reads a custom, autoconfigured collector back through it.

| Behaviour | Result |
|---|---|
| `getName()` not overridden | the FQCN; `getTemplate()` is `null` |
| `Profiler::collect()` | calls `collect()`, stores a **clone** in the profile |
| `saveProfile()` | `lateCollect()` on that clone, then write; both values are loaded back |
| unknown collector name | `InvalidArgumentException`: Collector "…" does not exist. |
| a `Closure` in `$this->data` | `saveProfile()` throws « Serialization of 'Closure' is not allowed » |
| `reset()` / disabled profiler | data emptied / `collect()` returns `null` |
| functional test, `collect: false` | `getCollector('app.hit')` only after `enableProfiler()`, for one request |

**Level: unchanged (STANDARD)**, 885 body words (two traps that restated the
course were merged). Content: one outcome, one section, two flashcards, two
LEARNING questions, two COMPREHENSION questions, three central facts.

### P2.7 — Console: signals, the alarm event, testing a command

The mission names scenarios combining interaction, output, signals and tests,
and notes that the helpers were already taught. Executed first on Console
8.0.15 (`tools/behavior/checks/console-scenarios.php`, six checks, each proved
by a mutation of the vendor line carrying it, restored byte-identically). A
signal or an alarm ends the PHP process, so those checks run a script in a
child process and read its trace and exit status; they need `pcntl` and
`posix`, now named in the CI PHP setup, and fail rather than skip without them.
Same results on 7.4.21.

**A false claim corrected.** The *Console events* page said « Quatre events,
pas cinq ». `ConsoleEvents` declares four constants, but 8.0 dispatches a fifth
event, `ConsoleAlarmEvent`, under its class name (documented in
`components/console/events.rst`, 8.0). The heading, the trap and the key points
now say « quatre constantes, cinq events »; the flashcard that claimed four
events names the fifth; the smoke test fails if the old sentence returns, and a
central fact forbids it in the content. The deep audit of 8.0 had missed it: it
compared what was taught with the 7.4→8.0 changes, and the alarm event came in
7.2 (Console `CHANGELOG.md`).

| Behaviour | Result |
|---|---|
| `SIGINT` with a dispatcher, nothing changes the code | `console.signal`, `console.terminate` (signal 2), **exit 0** |
| `abortExit()` / `setExitCode(3)` on `console.signal` | the command carries on / exit 3 |
| `handleSignal()` returns 5 / `false` | receives 0, exits 5 / carries on — with or without a dispatcher |
| no dispatcher, no signalable command | PHP dies of the signal |
| `setAlarmInterval(1)` | `console.signal` then `ConsoleAlarmEvent`; without `abortExit()` the first alarm ends the command, code 0 |
| `CommandTester` vs `ApplicationTester` | no console event vs `console.command`, `console.terminate` |
| lifecycle | `initialize`, `interact` (interactive only), validation, `execute`; non-interactive, a missing required argument throws « Not enough arguments » |
| `capture_stderr_separately` | `getErrorStyle()` output and a question's prompt go to `getErrorOutput()` |

**Levels: unchanged (STANDARD)**; *Console events* 873 and *Input and Output
objects* 839 body words. Content: three outcomes, three course sections and a
corrected one, three flashcards and a corrected one, three LEARNING questions
(one `hard`), three COMPREHENSION questions, six central facts.

### P2.7 — HttpClient: retries, an unread response, a partly failed stream

The mission names retries, the response lifecycle and partially failed
streaming, and notes that `stream`, `getInfo`, `MockHttpClient` and timeouts
were already taught. Executed first on HttpClient 8.0.16 with `MockHttpClient`
(`tools/behavior/checks/http-client.php`, seven checks, each proved by a
mutation of the vendor line carrying it, restored byte-identically under
SHA-256 — `tools/audit/deep80/mutate.py`). The seven give the same result on
7.4.21: stable behaviours, not 8.0 changes, and the content says neither.

| Behaviour | Result |
|---|---|
| status never read | the destructor throws: an unassigned `POST` answered 500 throws a `ServerException` on its line; `getStatusCode()`, `getHeaders(false)`, `cancel()` opt out, `getInfo('http_code')` does not |
| `RetryableHttpClient` defaults | 3 retries (4 requests); then the last response is returned, its `getContent()` throws |
| what is retried | 423, 425, 429, 502, 503 for every method; 500, 504, 507, 510 and transport errors for idempotent methods; a failed DNS resolution always; 404 never |
| delay | 1 s doubled per retry, 10 % jitter; `Retry-After` replaces it |
| `stream()`, one transport error | thrown by that chunk's methods; uncaught it ends the loop for all; caught in the loop the others finish; `getError()` alone does not handle it |
| `stream()`, a 404 | an unread status at the first chunk is checked by the stream: `ClientException` as the loop resumes |
| `stream()` timeout | `isTimeout()` true, that stream ends; `isLast()` on it throws `TimeoutException`; a new `stream()` resumes |

**Level: unchanged (STANDARD).** The course was at 843 of 900 body words; the
three sections fit by removing repetition between « Pièges », « Tips » and
« Points clés » and a duplicated `try`/`catch` listing (895 words). Content:
three outcomes, three course sections, three flashcards, three LEARNING
questions (one multiple-answer, executed by `questions/http-client-p27.php`;
one `hard`), three COMPREHENSION questions, seven central facts.

### P1.1 — PHP API: the standard library a question makes you predict (F8, part)

The item's justification said the detail of each PHP API was « ni examinable ni
révisable », with no source. It is replaced by what a question can make a
candidate predict, executed first on PHP 8.4.19 (`tools/behavior/checks/php.php`,
five checks). These are engine behaviours: no vendor line carries them, so
`mutate.py` cannot prove them; each check was instead run against altered
expected values, failed, and was restored byte-identically under SHA-256.

| Behaviour | Result |
|---|---|
| `array_merge` vs `+` | `['a','b']` vs `['a']`; on a shared string key `array_merge` keeps the right, `+` the left |
| `array_filter([1, 0, 2, null, '0', 'a'])` | `[0 => 1, 2 => 2, 5 => 'a']`, not a list |
| `array_map` | keys kept with one array, reindexed with two |
| PHP 8 comparison | `0 == 'a'` false, `0 == ''` false, `'1' == '01'` true, `'10' == '1e1'` true; `in_array` uses `==` |
| `json_decode` | `stdClass` by default, array with `true`, `null` on invalid input, `JsonException` with `JSON_THROW_ON_ERROR` |
| PHP 8.4 `array_find` / `array_find_key` / `array_any` / `array_all` | first value or `null`; callback gets value then key; `array_all([])` true, `array_any([])` false |

Versions read in doc-en `versions.xml` and php-src `PHP-8.4/UPGRADING`:
`str_contains` / `str_starts_with` / `str_ends_with` 8.0, `array_is_list` 8.1,
`json_validate` 8.3, `array_find` family, `mb_trim`, `mb_ucfirst` 8.4.

**Level: unchanged (STANDARD).** Content: two outcomes (`OUT-e7z5wvfwcsxk`,
`OUT-a1r0b9k9vxsn`), two course sections, two flashcards, three COMPREHENSION
questions, five central facts. The item already held five exam questions, the
ceiling audit L1-5 sets for lot 01, so no question was added: two redundant
LEARNING questions were rewritten as version 2 — `QST-g3y2dqj5820q` (language
feature in 8.4, also assessed by the VALIDATION question) now attributes a
standard function to its version, and `QST-f2m90kz2ya28` (two of the five 8.4
additions, assessed twice elsewhere) now asks which comparisons are true,
executed by `questions/php-p11.php`. Question counts and published figures are
unchanged.

### P1.2 — Forms: collections and mapping (F8, part)

Executed first, on Form 8.0.15 (`tools/behavior/checks/form.php`, five checks,
each proved by a mutation of the Form source line that carries it, restored
byte-identically under SHA-256 — `tools/audit/deep80/mutate.py`). All five give
the same result on 7.4.21: these are stable behaviours, not 8.0 changes, and the
content does not present them as such.

| Behaviour | Result |
|---|---|
| `CollectionType` without `allow_add`, one entry too many | dropped; with the Validator extension the form is **invalid**, « This form should not contain extra fields. » |
| without `allow_delete`, one entry missing | `['a', null]`: the entry becomes `null`; `allow_delete` removes it; `''` is removed only with `delete_empty` |
| prototype | `vars.prototype` only with `allow_add`; name `__name__`, changed by `prototype_name` |
| `by_reference` on an **object** collection | `true` (default): modified in place, **no** setter, adder or remover called; `false`: `removeTag()` then `addTag()`, or `setTags()` with a new instance |
| `by_reference` on a PHP **array** | no difference: adders or setter either way |
| `property_path`, `inherit_data` | `title` with `property_path: 'name'` reads and writes `name`, `'profile.bio'` reaches the nested object; an `inherit_data` subform returns the parent object from `getData()` |

A finding while executing: without the Validator extension the extra entry is
dropped **silently** — the error is a Form-constraint violation, not a listener
exception. The check and the question name the full-stack setting.

**Level: unchanged (STANDARD)** on both items; both courses stay under 900 words.

Content: two outcomes (`OUT-q996f75mz363`, `OUT-7gbvq4n8vsgp`), two course
sections (« Une collection de types » on *Form types*, « Ailleurs que sous son
nom » on *Forms creation*), three flashcards, three LEARNING questions (one
multiple-answer, executed by `questions/forms-p12.php`; one `hard` code
question on `by_reference`) and two COMPREHENSION questions; five central facts.

### P1.3 — Validator: constraint behaviours and a custom constraint (F8, part)

Executed first, on Validator 8.0.15 (`tools/behavior/checks/validator.php`,
nine checks, each mutated once to prove it fails, then restored byte-identically
under SHA-256):

| Behaviour | Result |
|---|---|
| `null` | every value constraint passes it (`Length`, `Email`, `Regex`, `Choice`, `Positive`, `Count`, `Url`, `Type`); only `NotNull` and `NotBlank` refuse it |
| `''` | `Email`, `Regex`, `Url`, `Type('string')` return at once; `Length(min: 3)`, `Choice`, `Positive` report it |
| `new Choice(['a'])` | `InvalidArgumentException`: the first parameter is still `$options`, refused in 8.0 (7.4 deprecated the list form); the attribute throws at the first `validate()` |
| `new Url()` on `http://localhost` | a violation: 8.0 makes `requireTld` default to `true` |
| `Collection` | `[age]` « This field is missing. », `[zip]` « This field was not expected. »; `Required` holds the key, not a non-null value |
| list / `Sequentially` / `Compound` | 2 / 1 / 2 violations: only `Sequentially` stops |
| custom `Slug` | `validatedBy()` = `SlugValidator`; property target; on a class `ConstraintDefinitionException` |
| custom validator | called with `null`; `UnexpectedValueException` → « This value should be of type string. »; `UnexpectedTypeException` propagates |

Not taught: `When`, which needs `symfony/expression-language`, absent from
`tools/behavior`; a claim about it would not have been executed.

**Level: unchanged (STANDARD)** on both items. The additions are rules a
question can bite on, not a new mechanism to reason through; both courses stay
under the 900-word STANDARD budget.

Content: three outcomes (`OUT-kyt7yfamw0aq`, `OUT-bkrt4hz3td66`,
`OUT-7m4ym18awd8g`), three course sections (« null et la string vide »,
« Configurer une constraint en 8.0 » on *Built-in validation constraints*,
« Une constraint sur mesure » on *Violations builder*), three flashcards, three
LEARNING questions (two multiple-answer, executed by
`tools/behavior/questions/validator-p13.php`; one `hard` code question) and
three COMPREHENSION questions; nine central facts.

**Found on the way — a terminology defect.** The « chaîne → string » rule of
#401 had turned forty-odd *chains* into strings: the `buildViolation()` method
chain (« elle termine la string »), the Messenger middleware chain, the argument
resolver chain, the `X-Forwarded-For` proxy chain, form transformers and type
guessers. « Chaîne de requête » had become « chaîne de Request » instead of
*query string*, about thirty-five times. Each was restored by an exact, counted
replacement; the rule now protects « chaîne de … » and « chaîne d'… », and
« chaîne de requête » maps to *query string*. The glossary notes, which no rule
read, are now in the tool's scope.

### P1.6 — Multiple-answer questions (F7)

Owner, 2026-10-14: « continue avec les questions à cases à cocher ». 33 English
multiple-answer questions over 33 items and 12 of the 14 topics (none yet for
PHP or Automated Tests). Before writing them,
each choice — correct or not — was executed on Symfony 8.0.15, PHP 8.4 and
Twig 3.22 (`tools/behavior`, which gains the components it needs), or read in
the cited source for the four that rest on a document (RFC 9110 on 307/308,
the release process, the special routing parameters, AbstractController's
signatures).

`tools/behavior/questions/multiple-answer.php` returns, for 29 of them, one
boolean per choice in content order; `run.php` compares the list with the
`correct` flags of the YAML, so a flag flipped in the content fails CI
(shown: flipping one flag of `QST-bbxhkwvjjhph` gave `FAIL … the content marks
[true,true,true,false], Symfony 8.0 gives [true,true,false,false]`, then the
file was restored byte-identically under SHA-256). `MultipleAnswerEvidenceTest`
requires an execution or a stated reason for every multiple-answer VALIDATION
question and every question of the new file.

Pools: the bank holds **one VALIDATION question per item** (audit L1-4, the
Mock 1–3 payload tests). Only three of the 33 items had none, so 3 are
VALIDATION and 30 LEARNING (`content/questions/multiple-answer-learning.yml`);
the numbered mock exams draw on both. A first draft put 34 in VALIDATION; the
audit refused it, and a PHP question was dropped because lot 01 caps an item at
five questions (L1-5). Ten candidate questions were dropped beforehand for
duplicating an existing one.

Consequences, recomputed from the bank by the blueprint's own rule: Mock 1
42 questions / 43 min (was 41 / 42), Mock 2 and 3 unchanged in count, Mock 5's
universe 691; the six exams carry 7 or 8 multiple-answer questions each and
are numbered by estimated difficulty, now asserted at build time.

**Second batch (2026-10-15), PHP and Automated Tests.** Owner: « continuer ».
Ten more: four PHP (OOP, interfaces, two on enums) and six on tests (assertions, redirects, crawler, server parameters,
`trigger_deprecation()`, status code). 1 is VALIDATION (Handling legacy
deprecated code had none), 9 LEARNING. Two more, on attributes and closures,
were written and dropped: with the holdout questions of those items, which the
audit counts and this work does not read, they would have taken lot 01 items
past five questions (L1-5). Nine are executed in
`tools/behavior/questions/multiple-answer-php-tests.php`, compile-time errors
in a child process; the WebTestCase assertions rest on FrameworkBundle's
traits and are listed as documentary.

The execution caught a loose check, not a content error: reading `->value` on a
pure enum does not throw, it warns "Undefined property" and yields null. The
check now turns a warning into a failure, and the explanation says what PHP
does.

Incident, recorded rather than hidden: profiling the PHP and Automated Tests
items printed the first 110 characters of four HOLDOUT prompts, which live in
the lot files. The script filtered on the file name, not on the pool. The
prompts were not used, no new question is on their subject, and every script
since filters `pool != HOLDOUT`.

