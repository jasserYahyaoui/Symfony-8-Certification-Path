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

**Never weaken a test or a CI rule to get a green build (§12).** Removing a
rule from `RuleSet::mandatory()` is a governance decision, not maintenance.

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
