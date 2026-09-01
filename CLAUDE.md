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
  matter;
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

**The three pools have distinct jobs (§7.3, [ADR-0006](docs/adr/0006-exam-mode-serves-the-validation-pool.md)).**
`LEARNING` is Practice Mode. `VALIDATION` is the exam-mode bank used during
study, and it is what `exam.json` contains. `HOLDOUT` is reserved for the final
mocks and is **never deployed** in any payload. Every `STANDARD` or `DEEP` item
that is `EXAM_READY` needs at least one `VALIDATION` question, because its
stated evidence requires a success in exam mode — rule `POOL-002` enforces this,
so a lot is not finished until its VALIDATION questions exist.

**A fence is not a hiding place (§4.3, rule `CRS-001`).** A course may show the
code its **own** item teaches inside a fenced block, even when a question on
that item tests it. A correct answer belonging to **another** item is a leak
wherever it appears — the learner reading the page sees it either way, and the
fence only hides it from the rule. If `CRS-001` fires, fix the content; moving
the string into a fence is gaming the check, not passing it.

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

## Environment notes

- `certification.symfony.com`, `symfony.com`, `www.php.net` and
  `www.rfc-editor.org` are blocked by the egress proxy. Their upstream
  repositories on `raw.githubusercontent.com` are reachable and are used
  instead — except for the syllabus, which has no upstream (blocker B-1).
- `api.github.com` is blocked, so Composer cannot fetch dist archives locally.
  Use `composer install --prefer-source`. CI is unaffected.

## Session continuity

Update `CONTEXT.md` at the end of any incomplete session, per §23: current lot,
branch, completed work, remaining work, atomic items affected, known issues,
tests actually executed with their real results, next action, blocked decisions.
