# Lot 06 Report — Templating with Twig

**Date:** 2026-09-01
**Branch:** `lot-06-templating-twig` → Pull Request → `master`
**Plan reference:** Master Plan §14 (Lot 6), §4, §6, §7, §15, §16, §19

---

## Status

`PASS`

## Atomic official items

- **Assigned:** 14 (topic 6, *Templating with Twig*, in full)
- **Previously EXAM_READY:** 0
- **Newly EXAM_READY:** 14
- **Blocked:** 0

```text
new this lot:  +14
cumulative:    46.01%  (75 / 163 EXAM_READY)
```

| # | Item | Level |
|---:|---|---|
| 1 | TwigBundle | `STANDARD` |
| 2 | Twig syntax up to 3.22 version | `DEEP` |
| 3 | Auto escaping | `STANDARD` |
| 4 | Template inheritance | `STANDARD` |
| 5 | Global variables | `STANDARD` |
| 6 | Filters and functions | `STANDARD` |
| 7 | Template includes | `STANDARD` |
| 8 | Loops and conditions | `STANDARD` |
| 9 | URLs generation | `MINIMAL` |
| 10 | Controller rendering | `STANDARD` |
| 11 | Translations and pluralization | `STANDARD` |
| 12 | String interpolation | `MINIMAL` |
| 13 | Assets management | `MINIMAL` |
| 14 | Debugging variables | `MINIMAL` |

## The version constraint, resolved into a pinned source

The syllabus says *"Twig syntax **up to 3.22** version"* verbatim. Issue B-5 was
closed on that wording, but `source-map.yml` still pointed at the moving `3.x`
branch, which is at 3.29 — ahead of what is examinable.

`git ls-remote` resolves `v3.22.0` to `5079583d7313b0f0866ca32108036afcc072127d`.
Every Twig claim in this lot is anchored to that tag, and the source map now
records it instead of the branch. Anything a later Twig added is out of scope by
construction rather than by discipline.

## Evidence

Read from the pinned sources: `twigphp/Twig` at `v3.22.0` (`5079583d7313`),
`symfony/symfony-docs` at `eea05cbfe063`.

- **`foo.bar` resolution order**, seven steps: `$foo['bar']`, `$foo->bar`,
  `$foo->bar()`, `$foo->getBar()`, `$foo->isBar()`, `$foo->hasBar()`, then
  `null` — or `Twig\Error\RuntimeError` under `strict_variables`. The array is
  tried **first**, so an object implementing `ArrayAccess` sees the array access
  win.
- **`'a' + 'b'` is `0`.** `+` is arithmetic; `~` concatenates.
- **Five escaping strategies** — `html`, `js`, `css`, `url`, `html_attr` — with
  the documented boundary: `html` covers the HTML body *and attribute values
  inside quotes*; an unquoted attribute needs `html_attr`. `url` is for a
  subcomponent, never a whole URI. The `html` strategy uses `htmlspecialchars`.
- **A child template renders only its blocks.** Content written outside a block
  is discarded silently. Inheritance is single, and `{% extends %}` must be the
  first tag.
- **`loop.index` starts at 1**, and `loop.length`, `loop.revindex`,
  `loop.revindex0` and `loop.last` require a PHP array or a `Countable` — Twig
  cannot size a generator without consuming it. There is no `break` or
  `continue`; the documented approach is `filter`, `slice` or `sort`.
- **`{% for %}` has an `else` clause** for the empty sequence.
- **String interpolation `#{…}` works in double-quoted strings only**, accepts
  any valid expression, and is escaped with a backslash.
- **`app` exposes eleven properties**, with `app.current_route` documented as
  equivalent to `app.request.attributes.get('_route')`.
- **`trans(arguments, domain, locale)`** in that order; ICU MessageFormat is the
  recommended pluralization, requiring the `+intl-icu` filename suffix and
  `{name}` placeholders rather than `%name%`. The legacy pipe syntax still works
  and is no longer recommended.
- **`{% dump %}` goes to the debug toolbar, `{{ dump() }}` into the page**, and
  both exist in `dev` and `test` only — calling `dump()` in `prod` is a PHP
  error, deliberately.
- **`render(controller(...))`** reaches a controller with no route, through the
  internal fragment URL configured by `framework.fragments.path`.

### `CRS-001` fired three times, and was right each time

The strengthened rule — scoped last session so a fence cannot hide another
item's answer — blocked the first draft with five violations across four
courses. Three distinct leaks:

| Leaked string | Question's item | Where it appeared |
|---|---|---|
| `@AcmeBlog/user/profile.html.twig` | TwigBundle | its own course's prose |
| `php bin/console debug:twig` | Filters and functions | three courses, two of them other items |
| `{% include 'x.html.twig' with {a: 1} only %}` | Template includes | its own course's table |

None was fixable by moving text into a fence, and none was fixed that way. The
**questions** were rewritten so that the answer is no longer a string the
courses must print: the namespace question now asks for `@AcmeBlog` alone, the
command question for `debug:twig` alone, and the `only` question asks what the
keyword *does* rather than for the exact line. The teaching is unchanged.

That is the rule working as intended: it protected three questions'
discriminating power without costing a single sentence of content.

### Anti-duplication boundaries

| Lot 06 item | Neighbour | Boundary drawn |
|---|---|---|
| URLs generation | Lot 05 *URLs generation* | the Twig side, `path()` vs `url()`; the generator and its four reference types stay in Lot 05 |
| Controller rendering | Lot 04 *Internal redirects* | `render()` from a template; `forward()` between controllers stays in Lot 04 |
| Translations and pluralization | Lot 16 i18n, Lot 05 *User's locale guessing* | the Twig surface and ICU; catalogue organisation and locale detection stay out |
| TwigBundle | Lot 03 *Components and Bridges* | what the bundle configures; the component/bridge/bundle triad stays in Lot 03 |
| Assets management | §1.5 exclusions | `asset()` only — AssetMapper and Webpack Encore are **excluded scope** and are named as such in the course, never tested |

### Content produced

|  | New this lot | Cumulative |
|---|---:|---:|
| Courses | 14 | 75 |
| Course body words | 4 603 | 26 361 |
| Flashcards | 9 | 53 |
| Questions — LEARNING | 29 | 152 |
| Questions — VALIDATION | 10 | 56 |
| Questions — HOLDOUT | 2 | 13 |

Word counts are **body words**, excluding YAML front matter. This is the first
lot authored with rule `POOL-002` in force: its ten `STANDARD`/`DEEP` items each
carry a VALIDATION question, written with the lot rather than retrofitted.

Nine flashcards cover eight items. Six carry none — mechanisms reasoned from a
criterion, or a single line the course already states.

### Tests actually executed

Each command run with `set -o pipefail` and its exit code checked (PROC-1).

```text
php bin/cert validate     17 rules, 163 official items, 221 questions — no violations   (exit 0)
php bin/cert coverage     46.01% (75/163)                                                (exit 0)
php bin/cert build        docs tree + coverage.json, exam.json, practice.json            (exit 0)
vendor/bin/phpunit        82 tests, 796 assertions — OK                                  (exit 0)
npm --prefix website run build     SUCCESS                                               (exit 0)
npm --prefix website run a11y      6/6 surfaces PASS, TOTAL VIOLATIONS: 0                (exit 0)
```

The accessibility audit ran on this lot's artefact: the staleness guard added
last session refuses a build older than its inputs, so a stale PASS is no longer
possible.

Pools verified against the built payloads: `practice.json` = LEARNING,
`exam.json` = **VALIDATION**, and no holdout question appears in either.

### Delivery evidence

| | |
|---|---|
| Lot commit | `9498272` on `lot-06-templating-twig` |
| Pull Request | [#10](https://github.com/jasserYahyaoui/Symfony-8-Certification-Path/pull/10) |
| CI on the PR head | **Technical gate: success** |
| Merge commit | `dd806b7` on `master` |
| CI on `master` | run `33530315787` — **success** |
| Deploy | run `33530315781`, job `99931518573` build + job `99931882080` deploy — **success** |
| Production smoke test | job `99931965535` — **success** |
| Site | https://jasseryahyaoui.github.io/Symfony-8-Certification-Path/ |

## Gates

| Gate | Result |
|---|---|
| **Technical** | **PASS** — validate, phpunit, site build and CI all green |
| **Pedagogical** | **PASS** — every item has outcomes, a justified level, teaching content, ≥2 questions, and a VALIDATION question where `POOL-002` requires one |
| **Accessibility** | **PASS** — 6/6 surfaces, 0 violations, on this lot's build |
| **Content Budget** | **PASS** — 329 body words per item on average (MINIMAL 193–258, STANDARD 238–427, DEEP 584) |

## Level distribution — observation only

New this lot: `MINIMAL` 4, `STANDARD` 9, `DEEP` 1.
Cumulative across the 75 EXAM_READY items: `MINIMAL` 19, `STANDARD` 51, `DEEP` 5.

A result, not a target. The single `DEEP` item is *Twig syntax up to 3.22
version*: it carries a scored ordered procedure — the seven-step resolution of
`foo.bar` — and the other thirteen items of the lot rest on it. Nothing else
here is a mechanism with an order; the four `MINIMAL` items are one function or
one distinction each, three of them narrowed by a boundary or an exclusion.

Four lots, four shapes: 3/10/2, 5/8/1, 3/8/0, 4/9/1.

## Remaining risks

- **HOLDOUT confidentiality.** 13 holdout questions are in no published
  payload since ADR-0006, but this repository is **public**, so their answers
  remain readable in `content/questions/`. *Functional isolation and a narrowed
  exposure, not confidentiality.* A decision is required before Lot 27 —
  [ADR-0005](../adr/0005-holdout-distribution-deferred.md).
- **SITE-1 did not bite.** Twig samples are fenced as `html`, per ADR-0003, and
  the site build is green. The Prism `twig` language remains unavailable.
- **Cross-lot boundaries held by prose** — twenty-two now.
- **French share** of the question bank is 21/221 and this lot added none. The
  exam is sat in English (§5); this remains a decision the owner has not yet
  taken.

## Recommendation

Proceed to **Lot 07 — Forms** (13 items). *Handling file upload* and *CSRF
protection* have standing boundaries with Lot 04's *File upload* and
`isCsrfTokenValid()`; *Forms rendering with Twig* and *Forms theming* sit
against this lot's *Filters and functions*.
