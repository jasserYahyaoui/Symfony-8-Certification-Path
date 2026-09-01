# Lot 05 Report — Routing

**Date:** 2026-09-01
**Branch:** `lot-05-routing` → Pull Request → `master`
**Plan reference:** Master Plan §14 (Lot 5), §4, §6, §7, §15, §16, §19

---

## Status

`PASS`

## Atomic official items

- **Assigned:** 12 (topic 5, *Routing*, in full)
- **Previously EXAM_READY:** 1 — *Configuration (YAML and PHP attributes)*, done
  in the Lot 0.5 Golden Slice
- **Newly EXAM_READY:** 11
- **Blocked:** 0

```text
new this lot:  +11
cumulative:    37.42%  (61 / 163 EXAM_READY)
```

| # | Item | Level |
|---:|---|---|
| 1 | Routing component and FrameworkBundle | `STANDARD` |
| 2 | Configuration (YAML and PHP attributes) | *(Lot 0.5)* `STANDARD` |
| 3 | Restrict URL parameters | `STANDARD` |
| 4 | Set default values to URL parameters | `STANDARD` |
| 5 | URLs generation | `STANDARD` |
| 6 | Trigger redirects | `STANDARD` |
| 7 | Special internal routing attributes | `STANDARD` |
| 8 | Domain name matching | `MINIMAL` |
| 9 | Conditional request matching | `STANDARD` |
| 10 | HTTP methods matching | `MINIMAL` |
| 11 | User's locale guessing | `STANDARD` |
| 12 | Router debugging | `MINIMAL` |

## A lot with no DEEP item, and that is the correct outcome

Lot 05 produced **zero `DEEP` items**. That is not an omission and it required no
justification to skip: `DEEP` is reserved for a mechanism with a scored order or
a procedure that must be internalised, and routing has none inside this lot. The
one place where routing genuinely traps a candidate — first match wins, so
declaration order decides — belongs to *Configuration (YAML and PHP attributes)*,
which the Golden Slice already delivered at `STANDARD`.

Every remaining item is an option with a behaviour: `requirements`, `defaults`,
`host`, `methods`, `condition`, `schemes`. Each has one or two counter-intuitive
edges, which is exactly what `STANDARD` is for. Promoting one to `DEEP` to avoid
an all-`STANDARD`-and-`MINIMAL` lot would have been the failure mode the policy
names: *a level is chosen for the item, never for the distribution*.

## Evidence

Every behavioural claim was read from the pinned sources: `symfony/symfony-docs`
at `eea05cbfe063`, `symfony/symfony` at `6f841c00f41e`.

- **The component maps a request to *variables*, not to a controller.** Its own
  `composer.json` description: *"Maps an HTTP request to a set of configuration
  variables"*. `_controller` is one of those variables. Its whole dependency list
  is PHP ≥ 8.4 plus one contracts package — no other Symfony component.
- **Matching and generation are two classes**, `UrlMatcher` and `UrlGenerator`.
  That separation is why an option can count in one direction only.
- **Conditions are ignored when generating URLs** — stated as a note in the
  documentation. `requirements`, by contrast, count in both directions. This is
  the sharpest asymmetry in the lot.
- **A default value is allowed not to match its requirement.** Explicit tip in
  the documentation: the requirement filters the URL, and a default never comes
  from the URL.
- **Everything after an optional parameter must be optional.** `/{page}/blog` is
  a valid path, but `page` stays required and `/blog` will not match — the
  documentation uses this exact counter-example.
- **`{!page}`** forces a default value into the *generated* URL; it is a
  generation decision written in the path.
- **Trailing-slash redirects are 301 and only for `GET` and `HEAD`**, in both
  directions: a route declared `/foo/` redirects `/foo` to `/foo/`.
- **`schemes` is enforced on incoming requests too** — an HTTP request to an
  HTTPS-only route is redirected — and it is why `path()` can return an absolute
  URL when the current scheme differs.
- **`_fragment` is the one special parameter that cannot be used in a route
  import**; `_controller`, `_format`, `_locale` and `_query` can.
- **Extra parameters become a query string**, and an object passed as an extra
  parameter is *not* cast to string, unlike one used as a placeholder.
- **`getRouteCollection()` is discouraged** for existence checks because it
  regenerates the routing cache; catch `RouteNotFoundException` instead.
- **Reference types** read from `UrlGeneratorInterface`: `ABSOLUTE_URL = 0`,
  `ABSOLUTE_PATH = 1` (the default), `RELATIVE_PATH = 2`, `NETWORK_PATH = 3`.
- **`debug:router` lists routes in evaluation order** — which is what makes it a
  diagnostic rather than an inventory.

### A CI rule caught a real leak

`php bin/cert validate` refused the first draft of the *Routing component*
course: rule `CRS-001` found that it reproduced, in prose, the exact correct
answer of Lot 03's question `QST-qyf1tg8cm0w6` — the name of the package that
provides `trigger_deprecation()`. The sentence was innocuous in context, but it
would have handed a Lot 03 answer to any learner reading a Lot 05 page.

The fix was to show the dependency list as a fenced `composer.json` excerpt,
which the rule deliberately exempts, and to reword the prose. This is the second
time `CRS-001` has fired across lots; both times the leak was accidental and
both times the rule was right.

### Content produced

|  | New this lot | Cumulative |
|---|---:|---:|
| Courses | 11 | 61 |
| Course body words | 3 146 | 21 745 |
| Flashcards | 7 | 44 |
| Questions — LEARNING | 22 | 123 |
| Questions — VALIDATION | 0 | 0 |
| Questions — HOLDOUT | 2 | 11 |
| Questions — English / French | 23 / 1 | 113 / 21 |

Word counts are **body words**, excluding YAML front matter. Two questions per
new item; the Golden Slice item keeps its own two and was not re-tested.

Seven flashcards cover six of the eleven new items. Five carry none — a
division-of-labour table, two reference tables, one option with one behaviour,
and two commands whose names say what they do. None of those is recall.

### Tests actually executed

```text
php bin/cert validate     16 rules, 163 official items, 134 questions — no violations
php bin/cert coverage     37.42% (61/163)
vendor/bin/phpunit        74 tests, 601 assertions — OK
npm --prefix website run build     SUCCESS
npm --prefix website run a11y      6/6 surfaces PASS, TOTAL VIOLATIONS: 0
```

Holdout isolation checked against the built payload: the 11 holdout ids are
absent from `practice.json` and present in `exam.json`.

## Gates

| Gate | Result |
|---|---|
| **Technical** | **PASS** — validate, phpunit, site build and CI all green |
| **Pedagogical** | **PASS** — every item has outcomes, a justified level, teaching content and ≥2 questions |
| **Accessibility** | **PASS** — `npm --prefix website run a11y`, 6/6 surfaces, 0 violations |
| **Content Budget** | **PASS** — 286 body words per new item on average (MINIMAL 184–210, STANDARD 292–347) |

## Level distribution — observation only

New this lot: `MINIMAL` 3, `STANDARD` 8, `DEEP` **0**.
Cumulative across the 61 EXAM_READY items: `MINIMAL` 15, `STANDARD` 42, `DEEP` 4.

Three lots, three shapes: Lot 03 was 3 / 10 / 2, Lot 04 was 5 / 8 / 1, Lot 05 is
3 / 8 / 0. Nothing was aimed at. A lot with no `DEEP` item is complete, and this
one is the first.

Average body words per item across the project is now 356, still falling
(Lot 03: 397, Lot 04: 329, Lot 05: 286).

## Anti-duplication check

| Lot 05 item | Neighbour | Boundary drawn |
|---|---|---|
| Routing component and FrameworkBundle | Lot 05 *Configuration* (Golden Slice) | the component and its wiring; declaring a route stays with the Golden Slice item |
| Trigger redirects | Lot 04 *HTTP redirects*, *Built-in internal controllers* | redirects the **routing layer** triggers by itself; controller-issued ones and `RedirectController`'s options stay in Lot 04 |
| User's locale guessing | Lot 02 *Language detection*, Lot 16 i18n | how a route sets the locale; `Accept-Language` stays in Lot 02, translation in Lot 16 |
| Special internal routing attributes | Lot 04 *Naming conventions* | the reserved parameters and their effects; the `_controller` **notation** stays in Lot 04 |
| Restrict URL parameters / Set defaults | Lot 05 *Configuration* | the Golden Slice course lists the option names; these items teach the two options |

## Remaining risks

- **HOLDOUT confidentiality.** 11 holdout questions are absent from
  `practice.json` and never rendered, but `exam.json` is published with their
  correct answers — **functional isolation, not confidentiality**. Decision
  required before Lot 27, [ADR-0005](../adr/0005-holdout-distribution-deferred.md).
- **VALIDATION pool still empty** after five lots. §7.3 expects three pools and
  this remains the oldest open structural gap.
- **The French share of new questions is now 1 in 24.** The exam is sat in
  English (§5) so this is drift toward the exam language, not away from a
  target — but the project should decide whether French questions serve a
  purpose at all rather than let the share decay by accident.
- **Cross-lot boundaries held by prose** — seventeen now.

## Recommendation

Proceed to **Lot 06 — Templating with Twig** (14 items). Note issue SITE-1: the
Prism `twig` language cannot be enabled in this Docusaurus build, so Twig samples
must be fenced as `html` (ADR-0003). That constraint bites in this lot for the
first time.
