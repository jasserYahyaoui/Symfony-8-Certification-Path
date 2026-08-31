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

PHP at build time, static site at runtime ([ADR-0001](docs/adr/0001-build-time-php-static-runtime.md)).

```text
YAML canonical data  ──►  PHP toolchain (bin/cert)  ──►  build/  ──►  GitHub Pages
docs/, content/           validate · coverage · build      static HTML/CSS/JS/JSON
```

Nothing executes on the server. Learner progress lives in the browser's
`localStorage`: no account, no network call, no secret in client code.

## Requirements

- PHP 8.4 or later — the same floor Symfony 8.0 itself requires
- Composer 2

## Usage

```bash
composer install

php bin/cert validate    # the 14 mandatory content rules of Master Plan §12
php bin/cert coverage    # recompute docs/syllabus/coverage-report.md
php bin/cert build       # generate the static site into build/
php bin/cert id:mint OfficialItem 20

vendor/bin/phpunit       # unit and integration tests
composer gate            # validate + coverage + tests
```

To preview the built site locally:

```bash
php bin/cert build && php -S localhost:8000 -t build
```

## Layout

| Path | Contents |
|---|---|
| `docs/syllabus/` | Canonical syllabus data — matrix, exclusions, source map, coverage report |
| `docs/adr/` | Architecture decision records |
| `docs/policy/` | Review algorithm, source verification, matrix field guide, accessibility baseline |
| `docs/reports/` | One report per lot, with real evidence |
| `content/questions/` | Question bank, one file per official topic |
| `src/` | Domain model, schemas, coverage engine, validation rules, site builder |
| `assets/` | Templates, stylesheet and the Practice/Exam runtime |
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
