# Symfony 8 Certification Path

A certification-focused learning and assessment system for the
**Symfony 8.0 Certification** — not a Symfony encyclopedia.

Published exam constraints (`OFFICIAL_FORMAT`): 75 questions, 90 minutes,
15 topics, English, Symfony 8.0 only.

> **Current status: Lot 0 infrastructure complete, content blocked.**
> The official syllabus is not reachable from the build environment, so no
> atomic official item has been imported and coverage is reported as
> **UNDEFINED** rather than `0%`. See
> [`docs/syllabus/official-syllabus.md`](docs/syllabus/official-syllabus.md)
> and [`CONTEXT.md`](CONTEXT.md).

## Architecture

PHP owns the data and the rules at build time; Docusaurus renders the site;
the deployed artefact is static ([ADR-0001](docs/adr/0001-build-time-php-static-runtime.md),
[ADR-0003](docs/adr/0003-docusaurus-presentation-layer.md)).

```text
docs/syllabus/*.yml, content/questions/*.yml      canonical data
        │
        ▼  php bin/cert validate · coverage · build
website/docs/**.md          generated pages
website/static/data/*.json  generated Practice and Exam payloads
        │
        ▼  npm --prefix website run build
website/build/              static site  ──►  GitHub Pages
```

Nothing executes on the server. Learner progress lives in the browser's
`localStorage`: no account, no network call, no secret in client code.

`website/docs/` and `website/static/data/` are **generated and gitignored** —
the YAML is the single source of truth. Never edit them by hand.

## Requirements

- PHP 8.4 or later — the same floor Symfony 8.0 itself requires
- Composer 2
- Node 20 or later

## Usage

```bash
composer install
npm --prefix website ci

php bin/cert validate    # the 14 mandatory content rules of Master Plan §12
php bin/cert coverage    # recompute docs/syllabus/coverage-report.md
php bin/cert build       # generate the Docusaurus content tree
php bin/cert id:mint OfficialItem 20

vendor/bin/phpunit       # unit and integration tests
composer gate            # validate + coverage + tests
```

To preview the site locally, generate the tree first, then serve it:

```bash
php bin/cert build
npm --prefix website start     # dev server with hot reload
npm --prefix website run build # production build into website/build/
```

## Layout

| Path | Contents |
|---|---|
| `docs/syllabus/` | Canonical syllabus data — matrix, exclusions, source map, coverage report |
| `docs/adr/` | Architecture decision records |
| `docs/policy/` | Review algorithm, source verification, matrix field guide, accessibility baseline |
| `docs/reports/` | One report per lot, with real evidence |
| `content/questions/` | Question bank, one file per official topic |
| `src/` | Domain model, schemas, coverage engine, validation rules, docs generator |
| `website/` | Docusaurus site — config, React pages for Practice and Exam, styles |
| `tests/` | PHPUnit suites |

## Governing rules

Coverage is calculated **only** as:

```text
EXAM_READY atomic official items / total atomic official items * 100
```

Never from lots, pages, chapters, flashcards, questions or files.

An item is `EXAM_READY` only when its lifecycle status, its verification status
and its evidence all agree — a hand-edited boolean cannot inflate the figure.

Holdout questions never reach Practice Mode: the Practice payload is assembled
at build time from the learning pool alone, so a holdout question is absent
from the file the Practice page fetches rather than merely hidden by the UI.

CI fails on any mandatory-rule violation. Per Master Plan §12, tests are never
weakened to obtain a green build.
