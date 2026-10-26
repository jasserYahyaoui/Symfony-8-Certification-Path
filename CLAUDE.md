# Working in this repository

This project executes `SYMFONY-8-CERTIFICATION-MASTER-PLAN-V2.md`. That plan is
the specification; this file records how it is applied here.

## The rules that are easy to break by accident

**Coverage has exactly one formula (§3.5).**

```text
EXAM_READY atomic official items / total atomic official items * 100
```

Never compute it from lots, pages, chapters, flashcards, questions or files.
When the syllabus is not imported, report coverage as **UNDEFINED**, never as
`0%` — an undefined denominator and a zero numerator are different claims.

**The syllabus is imported verbatim (§3.1).** The lot descriptions in Master
Plan §14 are operational groupings. They are *not* the syllabus and must never
be used to populate `docs/syllabus/syllabus-matrix.yml`, however convenient
that would be. Doing so substitutes an unofficial denominator that then looks
entirely credible.

**Never report `DONE`, `COMPLETE`, `VALIDATED` or `DEPLOYED` from intention
alone (§16).** A deployment claim requires a real production smoke test; a test
claim requires real output. Do not invent commits, URLs, percentages or scores
(§19).

**Level distribution is an outcome, never a target.** There is no target ratio
of `MINIMAL` / `STANDARD` / `DEEP`, and no minimum number of `DEEP` items for
the project or for any lot. A lot with zero `DEEP` items is complete. Never
promote an item because a percentage looks low; the only admissible reason for
a level is the item itself. See
[the field guide](docs/policy/matrix-field-guide.md#level-distribution-is-an-outcome-never-a-target).

**Never weaken a test or a CI rule to get a green build (§12).** Removing a
rule from `RuleSet::mandatory()` is a governance decision, not maintenance.

**Reporting a lot.** Every lot report and hand-off must:

- separate **new** figures from **cumulative** ones — never present a running
  total as this lot's output;
- reconcile from the canonical files (`syllabus-matrix.yml`, `content/**`) with
  a script, never from an earlier report or from memory;
- compute coverage only as EXAM_READY atomic official items over total atomic
  official items, and count course size as **body words**, excluding YAML front
  matter and ```` ```mermaid ```` blocks (ADR-0009);
- state level distribution as an observation (see the rule above);
- distinguish HOLDOUT **functional isolation** (absent from `practice.json`)
  from **confidentiality** — the published payloads carry correct answers, so
  never write "no leak" without that qualifier;
- claim `PASS` only with the applicable evidence present (tests, CI, commit,
  merge, deployment URL, production smoke test, the three gates); write
  `MISSING`, `NOT_APPLICABLE` or `BLOCKED` rather than inventing a value;
- end with a compact, self-contained summary that a reader without repository
  access can audit.

**Every lot ships through a branch and a Pull Request (§15).** The plan's
per-lot workflow is `… > PULL REQUEST > MERGE TO MASTER > DEPLOY`. Committing
straight to `master` is **never** compliant by default and must never be
reported as `NOT_APPLICABLE`: it is a **documented process deviation**, recorded
as such in the lot report with the instruction that authorised it. Lots 0, 0.5,
01 and 02 were delivered this way on the owner's explicit instruction and are
recorded accordingly; history is not rewritten. From Lot 03 onward: branch,
push, open a PR, let CI run, then merge.

**Accessibility is a gate, not a note (§13, §17).** Run
`npm --prefix website run a11y` (axe-core, WCAG 2.1 AA, plus single-h1,
heading-order and focus-affordance checks) whenever a lot changes anything that
reaches a rendered page — including the generator, the CSS and the React pages.
`MISSING` is not an acceptable gate value: it is `PASS`, or `NOT_APPLICABLE`
justified by a diff showing no UI-reaching change. CI runs the audit on every
push.

**Run a gate command so its failure can be seen (PROC-1).** Piping a gate
through `tail` and chaining with `&&` reads the exit status of the *pipeline's
last command*, not of the gate. A failed site build was reported as a success
that way, and the accessibility audit then ran against a stale build directory.
Run each gate command with `set -o pipefail`, check `$?`, and prefer
`composer gate-full`, which builds before it audits. `npm --prefix website run
a11y` now refuses to run against a build older than its inputs.

**`composer gate-full` does not run the audits.** It builds, validates, tests
and audits accessibility — and stops there. The twelve scripts under
`tools/audit/` run only in CI, so a green `gate-full` says nothing about them.
Adding a citation to a repository absent from `docs/syllabus/source-map.yml`
passes every local gate and fails `AUD-02` on the push, which is what happened
on 2026-09-16 with `httpwg/http-extensions`. When a change touches citations,
levels, question pools or course sections, run the audit set before pushing.

**The three pools have distinct jobs (§7.3, [ADR-0006](docs/adr/0006-exam-mode-serves-the-validation-pool.md)).**
`LEARNING` is Practice Mode. `VALIDATION` is the exam-mode bank used during
study, and it is what `exam.json` contains. `HOLDOUT` is reserved for the final
mocks and reaches **no learning payload**: `practice.json` and `exam.json`
carry none of it, which `PayloadBuilder::assertNoHoldoutLeak()` asserts at
build time. Since Mock 4 (Unit C) it is deployed in exactly one payload,
`mock-4.json`, which must carry the whole holdout and nothing else —
`assertMockMatchesBlueprint()` — so a missing question fails the build as
loudly as a leaked one. Note that `POOL-001` guards the *data*, not the
payloads: it forbids a holdout question being referenced as learning material. Every `STANDARD` or `DEEP` item
that is `EXAM_READY` needs at least one `VALIDATION` question, because its
stated evidence requires a success in exam mode — rule `POOL-002` enforces this,
so a lot is not finished until its VALIDATION questions exist.

**A fourth pool closes each lot: `COMPREHENSION` ([ADR-0010](docs/adr/0010-comprehension-checks.md)).**
By the owner's decision of 2026-10-09, each lot ends with a « Contrôle de
compréhension » page, last in its sidebar category, correcting after each
question. Its questions live in `content/comprehension/lot-XX.yml`, are loaded
by `QuestionLoader::comprehension()` into `ContentSet::$comprehension`, and are
seen by nothing that reads `content/questions/` — no mock, readiness, coverage
or roadmap figure counts them. They are in **English** and their number is not
fixed: a lot is complete when every `OUT` id of every item is assessed (rule
`CMP-001`, synthesis questions counting through `related_items`). `CMP-002`
refuses a prompt 60 % similar to any exam question, holdout included, without
printing a holdout id; `CMP-003` runs the exam-bank question rules on them.
Writing one means verifying its answer like any other — by execution where it
can be — and reading only the LEARNING and VALIDATION prompts of the lot, never
the holdout. If `CMP-002` fires, rewrite the question; never move the threshold.

**Refinement is versioned, and the bar is allowed to rise (ADR-0007).**
`docs/progress/refinement-log.yml` records the `framework_version` each lot was
audited against. Version 2 requires identified learning outcomes (`OUT` ids),
the `assesses_outcomes` link from a question to what it assesses,
`question_archetype`, and the revision budget. A lot audited under an older
version keeps its record — the audit really happened — but is **never**
re-labelled as refined; it is re-refined. Raising `RefinementFramework::CURRENT`
lowers Certification Readiness, and that is the intended behaviour: a metric
that only ever rises measures effort, not readiness.

**The staging is gone — the exit act was executed on 2026-09-15.** The three
rules those structures gate (`ARC-001`, `PED-003`, `REV-001`) once raised errors
only inside refined lots and warned elsewhere, so that 550 questions written
before the axis existed did not force either a fabricated archetype on each or
the rule's removal. Every lot carrying atomic official items reached version 2,
so the tolerance covered nobody and was removed rather than left dormant: a
`question_archetype` is required on every question, an unidentified outcome is an
error anywhere, the revision budget is a ceiling everywhere, and `aud10` gates
all twenty-six lots. `MatrixLoader` no longer accepts a bare-string outcome and
`syllabus-matrix` is at **schema version 2**.

ADR-0007's exit condition named « all 27 lots », which was **unsatisfiable**:
lot 27 carries no matrix item and no question, so no refinement audit can ever
record it. The addendum to ADR-0007 documents that drafting defect and the
reading retained — the twenty-six lots the framework can grip. Do not restore the
staging to make a new lot easier to land: mint the ids and write the archetypes.

**A rule that has only ever been silent is not passing (§16).** All three new
rules report nothing on the current corpus — the shape all five vacuous checks
found in this project had. `python3 tools/audit/prove_framework_rules_fail.py`
injects one defect per rule into real canonical data, asserts the rule fires
with `[ERROR]`, and restores every file byte-identically under SHA-256. Run it
whenever a rule is added or changed, and never report a rule as working on the
strength of a clean run alone.

**The audits run in CI, and two of them prove themselves (2026-09-09).** Twelve
scripts under `tools/audit/` run on every push (~75 s), together with
`prove_framework_rules_fail.py` and `aud10 --prove`. Before that, none of them
ran on a push — including the proof that guards against a rule becoming vacuous,
in a project that has already found five vacuous checks. `prove_audits_fail.py`
stays out: it takes ~5 minutes and mutates canonical files.

`aud09_course_sections.py` reads what is **under** each heading, because a
heading count is a proxy and this project has already been misled by one.
`aud10_answer_length_bias.py` measures whether the correct answer is the longest
choice — 50.5% across the corpus when it was written, against a 25% chance
baseline; 21.8% today. Since 2026-09-15 it gates **every** lot (the staging went
with ADR-0007's exit act) and it fails a lot on an **exact one-sided
Poisson-binomial tail** below alpha 0.01, not on a comparison of two
percentages. The old comparison had no notion of sampling variability and failed
a four-question lot on a result chance produces 26% of the time — which is why
lot 19 was once edited over a one-character gap. Alpha is 0.01 rather than 0.05
because twenty-six lots are tested per push. A lot of **fewer than four**
questions can never fail, even at total bias; the run prints that rather than
leaving it to be derived, and `--prove` names the lot it cannot act on instead of
implying full coverage.

**A fence is not a hiding place (§4.3, rule `CRS-001`).** A course may show the
code its **own** item teaches inside a fenced block, even when a question on
that item tests it. A correct answer belonging to **another** item is a leak
wherever it appears — the learner reading the page sees it either way, and the
fence only hides it from the rule. If `CRS-001` fires, fix the content; moving
the string into a fence is gaming the check, not passing it.

**A diagram is outside the budget, not outside the rules (ADR-0009).** By the
owner's decision of 2026-10-09, a ```` ```mermaid ```` block is not counted as
body words — in `REV-001`, `R14`, `AUD-04` and the revision roadmap, through one
pattern, `Course::MERMAID_BLOCK`, mirrored in `tools/audit/collect.py` and
`build_roadmap.py`. Rule `DIA-001` makes each block a real diagram with an
`accTitle` and an `accDescr`; never put prose in a fence to fit a page under its
budget. Each arrow is a claim, verified against the 8.0 code like a sentence.
Mermaid draws in the browser: the accessibility audit waits for the SVGs, and a
curl-based smoke test can only see the section around them.

**The page preamble is outside the budget too (owner, 2026-10-24, ADR-0011).**
The table, « Objectifs d'apprentissage », « Justification du niveau » and
« Limites de périmètre » are written by the generator from the matrix and were
never counted; the course's own `## Objectif` now is not either. `REV-001` and
`R14` read `Course::budgetWordCount()` — body words without that section,
through one pattern, `Course::OBJECTIVE_SECTION`, mirrored in
`tools/audit/collect.py` — while `wordCount()`, course size and the revision
roadmap still count it, because it is read. Rule `OBJ-001` keeps the objective
to 60 words of prose: no code, table, list or sub-heading. Never move teaching
into it to fit a page under its budget.

**A course maps each outcome to the section teaching it (owner, 2026-10-14, rule `SEC-001`).**
A mock's correction links a missed question to the `## ` heading named for its
outcome in the course's `outcome_sections`. Renaming or removing a heading, or
adding an outcome to the matrix, means updating that map in the same change —
by reading the section, never by pointing at « Points clés » to make the rule
pass. The generator writes the anchor (`CourseUrl::sectionAnchor`); do not
hand-write `{#…}` ids in a course.

**Every exam pitfall is rehearsed by a flashcard, or says why not (2026-10-24).**
`tools/audit/pitfall_flashcards.py` (CI, `--max-gaps 0`) reads each paragraph
of each « Pièges d'examen » section and requires a verdict in
`docs/audit/pitfall-flashcards/reviewed.yml`: COVERED by a named card, or
NO_CARD with a reason. A verdict is keyed by the paragraph's hash, so rewording
a pitfall voids it. Give the verdict by reading the pitfall against all the
cards of its item — `--suggest` only ranks them, and on 2026-10-24 it missed 53
covering cards. A card counts when its question and answer rehearse the trap,
not when its explanation mentions it. A new card's fact is executed or read in
the source it cites, like any other.

**Technical terms stay in English in French prose (owner, 2026-10-14).**
Write `listener`, `compiler pass`, `service container`, `Request`, `Response`,
`attribute`, never écouteur, passe de compilation, conteneur de services,
requête, réponse, attribut. The list is `docs/policy/terminology.yml`; CI runs
`python3 tools/terminology/apply.py --check`, and running it without `--check`
rewrites the prose. A word missing from the list is added to the list, not
fixed by hand in one page.

**A correction is journaled, and a rewritten question gets a new version (P3.3).**
`docs/corrections/changelog.yml` records each correction or extension of what
the site teaches, with the courses, cards and questions carrying it, and is
published as « Journal des corrections ». An entry cites the squash commit, so
it is written in the PR after the one that ships the change, like a delivery
row. Never fill its lists from memory: `python3 tools/corrections/derive.py
<commit>` reports what the commit changed, and `--check` (CI) fails on an id the
commit did not change. When a question's prompt or correct answer changes, raise
its `version` in the same change — `derive.py --versions` fails the push
otherwise — because « Ma progression » and Mock 5 stop counting answers given to
an earlier version, and nothing else tells them the question is no longer the
same.

**Human approval is required (§15)** for: irreversible architecture change,
official-scope change, major deletion, anything touching authentication,
permissions or secrets, an unresolved source contradiction, a disabled test or
CI rule, and a deployment blocker needing human access.

## Commands

```bash
php bin/cert validate     # mandatory content rules (§12)
php bin/cert coverage     # regenerate docs/syllabus/coverage-report.md
php bin/cert build        # generate the Docusaurus content tree
php bin/cert id:mint <EntityType> [count]
vendor/bin/phpunit
composer gate             # validate + coverage + tests
composer gate-full        # the above, then bin/cert build and the site build + a11y

npm --prefix website run build   # render the site (needs `bin/cert build` first)
npm --prefix website start       # dev server
```

CI regenerates the coverage report and fails on any diff, so run
`php bin/cert coverage` before committing a matrix change.

**`website/docs/` and `website/static/data/` are generated and gitignored**
(ADR-0003). Editing them by hand is always wrong — the next `bin/cert build`
overwrites the change, and the canonical YAML is the single source of truth.
To change a page, change the generator in `src/Build/DocsGenerator.php` or the
canonical data it reads.

## Adding content

Before adding anything, apply the admission test of §1.2 and the value gate of
§1.4. If the answer to *"does this materially improve the learner's probability
of answering an official-scope question correctly?"* is not demonstrably yes,
stop (§1.3).

Identifiers are minted, never derived from a slug or file name
([ADR-0002](docs/adr/0002-persistent-identifiers.md)):

```bash
php bin/cert id:mint OfficialItem 20
```

Sources must be version-anchored to Symfony 8.0. `/current/` is rejected by CI.
Prefer the raw upstream repositories listed in `docs/syllabus/source-map.yml` —
they carry a commit SHA, which a rendered documentation page does not.

**A citation carries two URLs, and they cannot drift.** `url` is the raw file
this project fetches to verify the claim; `readable_url` is the same object as a
rendered GitHub page, and it is what the site links to. `readable_url` must be
*exactly* `SourceUrl::readable(url)` — rule `SRC-002` — so a learner always opens
the file the claim was checked against. Omit it and it is derived; write it
wrong and the build fails. Never convert `url` itself to `github.com/blob`:
`AUD-02` extracts each citation's (repository, ref) from the raw form, and a
blanket conversion would leave `CONTAM-1`, `CONTAM-2` and `CONTAM-8` skipping
every source while reporting zero findings. See
[the verification](docs/audit/source-url-readability/verification.md).

## Environment notes

- `certification.symfony.com`, `symfony.com`, `www.php.net` and
  `www.rfc-editor.org` are blocked by the egress proxy. Their upstream
  repositories on `raw.githubusercontent.com` are reachable and are used
  instead — except for the syllabus, which has no upstream (blocker B-1).
- `api.github.com` is blocked, so Composer cannot fetch dist archives locally.
  Use `composer install --prefer-source`. CI is unaffected.
- `tools/behavior` requires `symfony/runtime`, whose Composer plugin writes
  `vendor/autoload_runtime.php`. Composer refuses plugins as root unless
  `COMPOSER_ALLOW_SUPERUSER=1` is set; without it the `runtime.*` checks fail
  on the missing file, by design.

## Session continuity

Update `CONTEXT.md` at the end of any incomplete session, per §23: current lot,
branch, completed work, remaining work, atomic items affected, known issues,
tests actually executed with their real results, next action, blocked decisions.
