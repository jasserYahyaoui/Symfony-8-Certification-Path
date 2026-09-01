# Lot 03 Report — Symfony Architecture

**Date:** 2026-09-01
**Branch:** `lot-03-symfony-architecture` → PR #1 → `master`
**Plan reference:** Master Plan §14 (Lot 3), §4, §6, §7, §15, §16, §19

---

## Status

`PASS`

## Atomic official items

- **Assigned:** 15 (topic 3, *Symfony Architecture*, in full)
- **Previously EXAM_READY:** 0
- **Newly EXAM_READY:** 15
- **Blocked:** 0

```text
new this lot:  +15
cumulative:    22.09%  (36 / 163 EXAM_READY)
```

| # | Item | Level |
|---:|---|---|
| 1 | HttpFoundation component | `MINIMAL` |
| 2 | Symfony Flex | `STANDARD` |
| 3 | License | `MINIMAL` |
| 4 | Components and Bridges | `STANDARD` |
| 5 | Code organization | `STANDARD` |
| 6 | Request handling | `DEEP` |
| 7 | Exception handling | `STANDARD` |
| 8 | Event dispatcher and kernel events | `DEEP` |
| 9 | Official best practices | `STANDARD` |
| 10 | Backward compatibility promise | `STANDARD` |
| 11 | Deprecations best practices | `STANDARD` |
| 12 | Framework overloading | `STANDARD` |
| 13 | Release management and roadmap schedule | `STANDARD` |
| 14 | Framework interoperability and PSRs | `STANDARD` |
| 15 | Naming conventions | `MINIMAL` |

## Evidence

Every behavioural claim was read from the pinned sources, never from memory.
`symfony/symfony-docs` at `eea05cbfe063`, `symfony/symfony` at `6f841c00f41e`
— both resolved with `git ls-remote` against the `8.0` branch on the day, so
the fetched files are exactly those commits.

The findings that shaped the content:

- **Exception status code**, read from `HttpKernel.php:255-262`. The cascade is
  literal: unless `isAllowingCustomResponseCode()`, a response that is not
  4xx/5xx/3xx has its code overwritten — by `$e->getStatusCode()` when the
  exception implements `HttpExceptionInterface`, otherwise by `500`. A listener
  that returns a `204` therefore ships a `500`.
- **Bridges on the 8.0 branch: five.** `composer.json`'s `replace` key lists
  only three (Doctrine, Monolog, Twig) because the PhpUnit and PsrHttpMessage
  bridges are not replaced by the monorepo. Probing
  `src/Symfony/Bridge/<name>/composer.json` returns 200 for those five and 404
  for ProxyManager, which no longer exists on this branch. The count is stated
  from the probe, not from `replace`.
- **PSRs**, read from `composer.json`'s `provide` key: `psr/cache` 2.0|3.0,
  `psr/clock` 1.0, `psr/container` 1.1|2.0, `psr/event-dispatcher` 1.0,
  `psr/http-client` 1.0, `psr/link` 1.0|2.0, `psr/log` 1.0|2.0|3.0,
  `psr/simple-cache` 1.0|2.0|3.0. PSR-7 and PSR-17 are absent from that list;
  they reach Symfony through `symfony/psr-http-message-bridge` plus a
  third-party implementation.
- **`AsEventListener`** takes `event`, `method`, `priority`, `dispatcher`, and
  is `IS_REPEATABLE` — read from the attribute class, not from prose.
- **`KernelEvents`** constants confirmed against the class: eight events, whose
  values are the `kernel.*` strings.
- **Named arguments and the BC promise**: note `[10]` of `bc.rst` restricts the
  guarantee on parameter names to *constructors of attribute classes*. Elsewhere
  a named-argument call may break on a minor upgrade.
- **Release schedule**, from `releases.rst`: minor every six months (May,
  November), major every two years (November of odd years), 4 months of
  development + 2 of stabilisation, five minors per branch with X.4 as LTS,
  standard 8/8 months, LTS 3 years bugs / 4 years security.
- **Runtime**, from `runtime.rst`: `autoload_runtime.php` runs
  `$kernel->handle(Request::createFromGlobals())->send()`. The front controller
  returns a callable; it does not itself create the request.

### A duplication caught before it shipped

The first draft of item 1 (*HttpFoundation component*) reproduced Lot 02's
material: the seven-bag table, the `InputBag` / `ParameterBag` distinction and
an `InputBag::get()` question. Two things were wrong with that.

First it was **duplication** — Lot 02's *HTTP request* item already owns that
table, that distinction, a question and a flashcard on it. Second, the draft was
**factually wrong**: it followed the documentation page, which says `get()`
"doesn't support returning arrays", and concluded that it returns `null`.
`InputBag.php` is unambiguous — a non-scalar value raises `BadRequestException`.
Lot 02 had already read the source and recorded the correct behaviour.

Both the wrong fact and the duplication came from the same shortcut: trusting a
documentation page for a behavioural claim. The item was rescoped to what is
genuinely architectural — standalone component, replaces superglobals,
`Request::create()` makes the stack testable, `RequestStack` — and dropped from
`STANDARD` to `MINIMAL`, because that is what honestly remains once Lot 02's
territory is excluded. Its flashcard was deleted rather than rewritten: Lot 02
already carries the card.

### Content produced

|  | New this lot | Cumulative |
|---|---:|---:|
| Courses | 15 | 36 |
| Course body words | 5 959 | 13 993 |
| Flashcards | 10 | 27 |
| Questions — LEARNING | 30 | 72 |
| Questions — VALIDATION | 0 | 0 |
| Questions — HOLDOUT | 2 | 7 |
| Questions — English / French | 25 / 7 | 63 / 16 |

Word counts are **body words**, excluding YAML front matter. Ten flashcards for
fifteen items: a card exists only where the fact is arbitrary (release dates,
maintenance durations) or where the intuition is actively wrong (priority order,
`kernel.view` being conditional, a `204` becoming a `500`). Five items carry no
card, and that is the intended outcome of §6, not a gap.

### Tests actually executed

```text
php bin/cert validate     16 rules, 163 official items, 79 questions — no violations
php bin/cert coverage     22.09% (36/163)
vendor/bin/phpunit        74 tests, 601 assertions — OK
npm --prefix website run build     SUCCESS
npm --prefix website run a11y      6/6 surfaces PASS, TOTAL VIOLATIONS: 0
```

### Delivery evidence

| | |
|---|---|
| Lot commit | `90b963b` on `lot-03-symfony-architecture` |
| Pull Request | [#1](https://github.com/jasserYahyaoui/Symfony-8-Certification-Path/pull/1) — *Lot 03 — Symfony Architecture* |
| CI on the PR head | check run `99834847070`, **Technical gate: success** |
| Merge commit | `6a31ff5` on `master` (merge commit, branch history preserved) |
| CI on `master` | run `33501414293` — **success** |
| Deploy | run `33501414261`, job `99835397240` build + job `99835650551` deploy — **success** |
| Production smoke test | job `99835718227`, "Check every published page and payload" — **success** |
| Site | https://jasseryahyaoui.github.io/Symfony-8-Certification-Path/ |

The smoke test runs on GitHub's runners rather than from the build container,
because `jasseryahyaoui.github.io` is egress-blocked here (issue ENV-2). It is a
real request against the deployed site, not a local check.

## A build defect this lot exposed

`php bin/cert build` produced MDX that Docusaurus refused to parse:

```text
Expected a closing tag for `<env>` (25:40-25:45) before the end of `paragraph`
```

The cause was a learning outcome in the matrix reading
`Expliquer le role de config/packages/<env>/`. MDX reads a bare `<` as the start
of a JSX tag and a bare `{` as the start of an expression, so a legitimate
canonical prose field failed the site build with an error pointing at a
generated file rather than at the YAML that caused it.

The fix is in the **generator**, not in the content: `DocsGenerator::mdxText()`
escapes `<` and `{` in every canonical field it inlines — official wording,
version constraints, learning outcomes, level justification, exclusion
boundaries, source line references and flashcard explanations. Authored course
bodies are deliberately exempt: they are Markdown written for this pipeline and
use `<details>` on purpose.

Two regression tests pin both halves: the generated learning-outcome section
must contain no bare `<`, and an authored `<details>` block must survive
unescaped. This is the same class of defect as the `<https://…>` autolink that
broke the build earlier; the tests now cover both.

Only `<` is escaped. A bare `>` is harmless to MDX, and escaping it too would
make the canonical prose harder to read in a diff.

## Anti-duplication check against neighbouring lots

Lot ≠ syllabus topic (ADR-0004), and three Lot 03 items sit next to items that
another lot owns. The boundaries actually held:

| Lot 03 item | Neighbour | Boundary drawn |
|---|---|---|
| HttpFoundation component | Lot 02 *HTTP request*, Lot 04 *The request* | Lot 03 keeps the component's place in the architecture; the bag model and the API stay where they were |
| Request handling | Lot 04 *HttpKernel component*, *Argument value resolvers*, *Internal redirects* | Lot 03 keeps the flow and the `handle()` contract; resolvers and sub-request rendering are named and deferred |
| Naming conventions | Lot 04 *Naming conventions* (same official wording, different item) | Lot 03 keeps framework-wide casing, prefixes and suffixes; controller naming is Lot 04's |
| Request handling | Lot 03 *Event dispatcher and kernel events* | The flow versus the mechanism: one narrates the trajectory and its actors, the other the dispatcher and the per-event powers |

`DUP-001` passes, but it only compares normalized prompts — it would not have
caught the HttpFoundation overlap described above. The boundary table is the
control that did.

## Gates

| Gate | Result |
|---|---|
| **Technical** | **PASS** — validate, phpunit, site build and CI all green |
| **Pedagogical** | **PASS** — every item has outcomes, a justified level, teaching content and ≥2 questions |
| **Accessibility** | **PASS** — `npm --prefix website run a11y`, 6/6 surfaces, 0 violations, run against this lot's build |
| **Content Budget** | **PASS** — 397 body words per item on average (MINIMAL 188–306, STANDARD 334–488, DEEP 568–613) |

## Level distribution — observation only

New this lot: `MINIMAL` 3, `STANDARD` 10, `DEEP` 2.
Cumulative across the 36 EXAM_READY items: `MINIMAL` 7, `STANDARD` 26, `DEEP` 3.

This is a **result, not a target**. No ratio was aimed at and none is admissible.
Two items reached `DEEP` because each is a procedure with a scored order —
the request flow, and the dispatcher plus the eight-event catalogue — and both
are load-bearing for Lots 04 and 10. Three reached `MINIMAL` because two phrases
exhaust them. The eleven `STANDARD` items are not a default: each was written
down with a reason, and one of them (*HttpFoundation component*) was demoted to
`MINIMAL` mid-lot when its honest scope shrank.

## Process

Lot 03 is the **first lot delivered through the §15 workflow**: dedicated
branch, Pull Request, CI, then a controlled merge into `master`. Lots 0–02 were
committed directly to `master` on the owner's instruction and are recorded in
their own reports as a `DOCUMENTED_DEVIATION`; that history is not rewritten.

## Remaining risks

- **HOLDOUT confidentiality.** The 7 holdout questions are absent from
  `practice.json` (verified against the built payload) and never rendered by the
  UI, but `exam.json` is published with their correct answers. This is
  **functional isolation, not confidentiality**. It does not block content lots;
  a decision is required before Lot 27 — [ADR-0005](../adr/0005-holdout-distribution-deferred.md).
- **VALIDATION pool still empty.** Every scored question is `LEARNING` or
  `HOLDOUT`. §7.3 expects three pools; the third has no items yet.
- **Cross-lot boundaries held by prose.** The four boundaries above are enforced
  by the table in this report and by author discipline, not by a rule. The
  HttpFoundation near-miss shows the failure mode is real.
- **`php/doc-en` has no version branch.** Unchanged from Lot 01: PHP manual
  references are anchored to `master`, which moves. Symfony references are
  anchored to a commit SHA and do not.

## Recommendation

Proceed to **Lot 04 — Controllers** (14 items). Carry the boundary table
forward: Lot 04 owns the request, the response, the session, the cookies, file
upload, argument value resolvers, internal redirects and controller naming, all
of which Lot 03 deliberately left untouched.
