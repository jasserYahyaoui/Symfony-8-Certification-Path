# Lot 02 Report — HTTP

**Date:** 2026-09-01
**Branch:** `master`
**Plan reference:** Master Plan §14 (Lot 2), §4, §6, §7, §16, §19

---

## Status

`PASS`

## Atomic official items

- **Assigned:** 9 (topic 2 *HTTP*, minus *Status codes* already done in Lot 0.5)
- **EXAM_READY:** 9
- **Blocked:** 0

```text
coverage: 12.88%  (21 / 163 EXAM_READY)
```

| # | Item | Level |
|---:|---|---|
| 1 | HTTP Specification (RFC 9110) | `MINIMAL` |
| 2 | Status codes | *(Lot 0.5)* |
| 3 | HTTP request | `STANDARD` |
| 4 | HTTP response | `STANDARD` |
| 5 | HTTP methods | `STANDARD` |
| 6 | Cookies | `STANDARD` |
| 7 | Caching | `STANDARD` |
| 8 | Content negotiation | `STANDARD` |
| 9 | Language detection | `MINIMAL` |
| 10 | Symfony HttpClient component | `STANDARD` |

## Content decisions

**Two `MINIMAL`, seven `STANDARD`, no `DEEP`.** Recorded as an observation, not
as a shape aimed for. `MINIMAL` fits *RFC 9110* (situating a document among its
siblings) and *Language detection* (two methods and a normalisation rule).
Nothing in this topic demands `DIAGNOSE` across a multi-class flow, so nothing
is `DEEP`.

### Scope boundaries drawn to prevent duplication

Three items in this lot overlap with items owned elsewhere. Each boundary is
stated in the course itself, so a later lot cannot quietly re-teach it:

- **Caching** covers **protocol headers only** — expiration versus validation,
  `max-age`/`s-maxage`, `ETag`, `Vary`. Symfony's reverse proxy and its
  expiration/validation strategies belong to *HTTP Caching (reverse proxies,
  expiration, validation)* in Miscellaneous (lot-17).
- **Language detection** covers `Accept-Language` parsing only. Route-based
  locale resolution belongs to *User's locale guessing* (Routing), and
  translation to *Internationalization and localization* (lot-16).
- **HTTP response** picks up the `isRedirect()` / `isRedirection()` trap that
  Lot 0.5 deliberately deferred from *Status codes*, because it is a fact about
  the `Response` API rather than about status codes.

### Deliberate omissions

- **No flashcard for RFC 9110, Language detection or Content negotiation.** The
  first two are `MINIMAL` and derivable from one example each. For negotiation,
  the `q` rules are *applied*, and the questions exercise them — a card saying
  "`q` defaults to 1.0" is exactly the reflex over-production §6 forbids.
- **No exercises anywhere.** Every outcome is testable by a focused question.

### Volume

| Resource | Lot 02 | Project total |
|---|---:|---:|
| Courses | 9 (3 349 body words) | 21 (8 034 body words) |
| Flashcards | 6 | 17 |
| Questions (LEARNING) | 18 | 42 |
| Questions (HOLDOUT) | 2 | 5 |
| Exercises / Labs / Source Tours | 0 | 0 |

372 body words per item. Estimated revision time for Lot 02: **24–28 minutes**.

## Evidence

Every claim read from source. The findings that shaped the content:

- **Method classification**, read from `Request::isMethodSafe/Idempotent/Cacheable`:
  safe = `GET, HEAD, OPTIONS, TRACE, QUERY`; idempotent adds `PUT, DELETE, PURGE`;
  cacheable = `GET, HEAD, QUERY` only. This confirms the exam-relevant shape —
  `PUT`/`DELETE` idempotent but not safe, `OPTIONS`/`TRACE` safe but not cacheable.
- **`isRedirect()`** is `[201, 301, 302, 303, 307, 308]` — includes 201 (a 2xx)
  and excludes 300 and 304 (both 3xx). It neither equals nor contains
  `isRedirection()`, which is literally `300 <= code < 400`.
- **`InputBag::get()`** throws `BadRequestException` on a non-scalar value.
  `$query`, `$request` and `$cookies` are `InputBag`; `$attributes` is a
  `ParameterBag` without the restriction, because its content is
  application-supplied rather than client-supplied.
- **`Cookie::SAMESITE_NONE`** confirmed alongside `LAX` and `STRICT`.
- **HttpClient is asynchronous by default** — `request()` returns before the
  response arrives, and the wait happens on the first read.

### Tests

```text
vendor/bin/phpunit    → OK (72 tests, 406 assertions)
php bin/cert validate → 16 rules, 163 items, 47 questions, 0 violations
php bin/cert coverage → 12.88% (21/163 EXAM_READY)
npm run build         → SUCCESS
```

### Pool isolation

```text
practice.json  pool=LEARNING  42 questions
exam.json      pool=HOLDOUT    5 questions
```

Holdout questions are **absent from the Practice payload**: the build assembles
`practice.json` from the learning pool alone, so Practice Mode cannot serve one
even if the UI were wrong.

They are **not confidential**. `exam.json` is published at
`/data/exam.json` and carries each holdout question with its `correct` flags and
explanation, so anyone who fetches that URL can read the answers. The same is
true of `practice.json`. This is inherent to static hosting (ADR-0001), not a
defect in the build: nothing on GitHub Pages can withhold data from a client
that asks for it. Holdout integrity is therefore a **convention protecting a
learner from themselves**, not an access control.


## A rule defect this lot exposed

Promoting the cookie questions failed `SCOPE-001` twice:

```text
[ERROR] SCOPE-001: Scored question references excluded topic "esi" (QST-kc7stf86x9jh)
[ERROR] SCOPE-001: Scored question references excluded topic "esi" (QST-hmpny1fkf8mv)
```

Neither question mentions ESI. The exclusion term `esi` was matching inside
**`SameSite`** — `sam·esi·te`. A three-letter substring search is unusable
against prose.

Fixed by making exclusion matching **word-boundary aware**: `ESI` as a word
still trips the rule, `SameSite` no longer does, and multi-word phrases such as
`symfony ux` still match as phrases. Three tests pin all three behaviours.

This is a heuristic correction, not a rule relaxed to get a green build (§12).
The distinction matters: had it been "solved" by tagging the two questions
`exclusion-note`, the rule would have kept firing on every future mention of
SameSite until someone disabled it — and a rule everyone routes around has
stopped protecting anything.

## Gates

| Gate | Result |
|---|---|
| **Technical** | **PASS** |
| **Pedagogical** | **PASS** |
| **Content Budget** | **PASS** |

`DO NOT SCALE` not triggered.

## Level distribution — observation only

Across the 21 levelled items so far: **16 `STANDARD`, 4 `MINIMAL`, 1 `DEEP`**.

Recorded because it is measurable, not because it is a target. There is no
intended ratio and no minimum number of `DEEP` items; a lot with none is
complete, and the figure that actually matters is the revision burden below.
This is now written into
[the field guide](../policy/matrix-field-guide.md#level-distribution-is-an-outcome-never-a-target)
and `CLAUDE.md`, so it binds future lots rather than depending on memory.

## Remaining risks

- **Revision burden is tracking at roughly 370 body words per item** (351 for
  Lot 01, 372 for Lot 02, 508 for the DEEP-containing slice). Extrapolated
  across 163 items that is on the order of 60 000 words — several hours of revision. Still
  plausible for a certification, but it is the number to watch, and it should be
  re-estimated rather than assumed linear.
- **Three cross-lot boundaries are now load-bearing** (Caching ↔ lot-17,
  Language detection ↔ Routing and lot-16, Status codes ↔ HTTP response). They
  are stated in prose, which no CI rule enforces. Lots 05, 16 and 17 must honour
  them or the content will duplicate.
- **`SCOPE-001` still matches case-insensitively across whole prose.** A
  legitimate comparison mentioning an excluded technology by name will trip it
  and need the `exclusion-note` tag — which is the intended escape hatch, but
  it depends on authors reaching for it rather than removing the rule.

## Recommendation

`PROCEED` to **Lot 03 — Symfony Architecture** (15 items, the largest topic in
the syllabus).
