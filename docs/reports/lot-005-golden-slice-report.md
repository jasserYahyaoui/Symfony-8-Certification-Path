# Lot 0.5 Report — Golden Slice

**Date:** 2026-09-01
**Branch:** `master`
**Plan reference:** Master Plan §14 (Lot 0.5), §4, §6, §7, §16, §19

---

## Status

`PASS`

The Golden Slice validates the pipeline end to end on three real official
items, at three different content levels, with sources anchored to Symfony 8.0.

## Atomic official items

- **Assigned:** 3
- **EXAM_READY:** 3
- **Blocked:** 0

```text
coverage: 1.84%  (3 / 163 EXAM_READY)
```

The first coverage figure this project has earned. It counts only because each
item's `exam_ready` flag, lifecycle status and verification status agree, and
because `RDY-001` independently confirms sources, teaching content, assessment,
declared minimum evidence and a verification date all exist.

| Item | Topic | Level | Lot |
|---|---|---|---|
| Status codes | HTTP | `MINIMAL` | lot-02 |
| Configuration (YAML and PHP attributes) | Routing | `STANDARD` | lot-05 |
| Authenticators, Passports and Badges | Security | `DEEP` | lot-10 |

## Content decisions

### Why each level

**MINIMAL — Status codes.** The class is readable from the first digit, so the
item is recognition-oriented. One concise explanation, a table of the codes that
actually appear in exam scenarios, and the two distinctions that are genuinely
confusable. Anything more would add revision cost without improving an answer.

**STANDARD — Routing configuration.** The two formats are equivalent in
capability but diverge on control of evaluation order, which is a real source of
error. Requires DISTINGUISH and APPLY, hence a comparison and a focused
diagnostic example. Not `DEEP`: the router's internal matching flow is not
examinable under this item.

**DEEP — Authenticators, Passports and Badges.** Three abstractions whose roles
are routinely swapped, a five-method interface contract, a binary
`Passport`/`SelfValidatingPassport` choice forced by a constructor signature,
and badges whose effect depends on firewall configuration. DIAGNOSE is required:
recognising the definitions does not let a learner fix a broken authentication
flow. This is the hardest case the architecture will face, which is why it was
chosen first.

### What was deliberately not created

The content budget is a constraint, not a target, so the omissions matter as
much as the output:

- **No exercises, labs or Source Tours.** §8 permits an exercise only when it
  tests application better than a focused question. The routing diagnostic
  question already forces the learner to identify a collision and choose the
  minimal fix; an exercise would repeat it at higher revision cost.
- **No flashcards for the five status-code classes.** Derivable from the first
  digit. §6 forbids a card for something not worth memorising.
- **No flashcards for the YAML or attribute syntax.** Retained by writing it,
  not by recall.
- **No flashcard listing the badges.** That is a lookup, not something recalled
  under time pressure.
- **The `isRedirect()` / `isRedirection()` trap was excluded.** It is a genuinely
  good trap — `Response::isRedirect()` covers `[201, 301, 302, 303, 307, 308]`,
  including 201 and excluding 300 and 304 — but it is a fact about the `Response`
  API, so it belongs to the *HTTP response* item, not *Status codes*. Putting it
  here would have inflated a MINIMAL item and duplicated content that another
  item must own (§4.5).

### Volume

| Resource | Count | Size |
|---|---:|---|
| Courses | 3 | 1 635 words total (408 / 514 / 713) |
| Flashcards | 4 | — |
| Questions (LEARNING) | 6 | 4 English, 2 French |
| Questions (HOLDOUT) | 1 | English |
| Exercises / Labs / Source Tours | 0 | — |

Estimated revision time for all three items: **10–12 minutes**. Extrapolated
naively to 163 items that is roughly 9–11 hours of revision — a plausible
budget for a certification, and the number to watch as content grows.

## Evidence

### Sources verified

Every claim is anchored to a pinned commit, and each was read rather than
recalled:

```yaml
symfony/symfony-docs:
  branch: "8.0"
  commit_sha: eea05cbfe063b9cf99afaf303b8cad76757f43bb
symfony/symfony:
  branch: "8.0"
  commit_sha: 6f841c00f41e5c037d40e1d739e2dc602c8f289d
```

Facts checked against source, not memory:

- `Symfony\Component\Routing\Attribute\Route` exists; `Routing\Annotation\Route`
  returns **404** on branch 8.0 — the namespace is gone, and the question that
  tests it is therefore fair.
- `Route::__construct` accepts `priority` (default `null`, behaving as 0).
- `AuthenticatorInterface` declares exactly five methods, and `supports()`
  returns `?bool` — the three-state contract the DEEP question turns on.
- `Passport::__construct(UserBadge, CredentialsInterface, array $badges = [])`
  makes credentials mandatory, which is precisely why `SelfValidatingPassport`
  exists.
- `Response::HTTP_*` constants confirmed against lines 28–160.

### Tests

```text
vendor/bin/phpunit  → OK (69 tests, 399 assertions)
php bin/cert validate → 16 rules, 163 items, 7 questions, 0 violations
php bin/cert coverage → 1.84% (3/163 EXAM_READY)
```

### Holdout isolation, proven against real data

Until now the holdout guarantee was tested against an empty set. It is now
exercised with a real holdout question:

```text
practice.json  pool=LEARNING  6 questions
exam.json      pool=HOLDOUT   1 question
QST-eb3sm6ytxwb2 present in practice payload: False
```

The holdout question is not hidden by the UI — it is absent from the file the
Practice page fetches.

### New CI rules

Two rules were added with the new content types, taking the mandatory set to 16:

- `CRS-001` — courses map to a real item, cite sources, and do not reproduce a
  question's correct answer in prose.
- `FLC-001` — flashcards map to a real item, cite sources, are verified, and are
  not near-duplicates.

`REF-001` now also resolves `course_refs` and `flashcard_refs`.

## Two defects the slice exposed

Both are recorded because the slice existed to find exactly this kind of thing.

**1. `CRS-001` over-fired.** It flagged the routing course for containing
`use Symfony\Component\Routing\Attribute\Route;`, the correct answer to one
question. That is a false positive: a course must be able to show correct code,
and a question must be able to test what was taught. The rule now matches prose
only, ignoring fenced code blocks.

This is a heuristic correction, not a test weakened for a green build (§12).
Three tests pin the distinction: a leak in prose is still rejected; the same
text inside a fence is allowed; and a fence elsewhere on the page does not
exempt the prose around it.

**2. The generator emitted Markdown autolinks.** `- <https://…>` is valid
Markdown but MDX parses it as JSX and fails on the first slash, breaking the
site build the moment real sources appeared. Fixed to use a fenced Markdown link, with a
regression test asserting no generated page contains `<http`.

Neither would have been found by writing more content. They were found by
taking three items all the way to production, which is what a Golden Slice is
for.

## Gates

| Gate | Result |
|---|---|
| **Technical** | **PASS** — build, 69 tests, 16 content rules, lint, typecheck, pool isolation, site build |
| **Pedagogical** | **PASS** — see below |
| **Content Budget** | **PASS** — see below |

### Pedagogical Gate detail

| Criterion | Assessment |
|---|---|
| Atomic official coverage | 3 items fully covered; each maps to verbatim official wording |
| Technical accuracy | Every claim read from pinned Symfony 8.0 source or docs |
| Version compatibility | Symfony 8.0 only; no `/current/`; `VER-001` clean |
| Source relevance | Anchored to files and line ranges, not homepages |
| Minimum sufficiency | 408–713 words per item; no empty template sections |
| Prerequisite coherence | Declared with required level (attributes, firewalls, users) |
| Learning-outcome coverage | 3–5 outcomes per item, each assessed |
| Question clarity, answer uniqueness | `QST-001` clean; every distractor explained |
| English readiness | 5 of 7 questions in English, including all DEEP questions |
| Absence of excessive revision burden | 10–12 minutes for three items |

### Content Budget Gate detail

Each resource was admitted only against §1.2 and §1.4:

| Question | Answer |
|---|---|
| Syllabus-relevant? | Yes — each maps to a verbatim official item |
| Exam-relevant? | Yes — every trap taught is one the exam can test |
| Necessary? | Yes — five candidate resources were rejected, listed above |
| Already covered? | No — `DUP-001` and `FLC-001` confirm no near-duplicates |
| Unique value? | Yes — course explains, flashcard memorises, question discriminates |
| Revision cost justified? | Yes — 1 635 words for 3 items of 163 |

**`DO NOT SCALE` was not triggered.** The slice is lean enough to extrapolate.

## Remaining risks

- **The revision-burden extrapolation is linear and therefore optimistic.**
  Nine to eleven hours assumes every item behaves like these three. `DEEP` items
  cost roughly twice a `MINIMAL` one, so the real figure depends on the level
  distribution across the remaining 160 items — which is not yet decided.
- **`CRS-001` remains a heuristic.** It catches verbatim prose leaks, not
  paraphrase. A course that explains an answer in different words still weakens
  its question, and only review will catch that.
- **No accessibility audit has been run** against the deployed slice. The
  `<details>` flashcards use a native, keyboard-operable control, but that is
  inspection, not measurement.
- **One holdout question is not a holdout pool.** Isolation is proven
  mechanically; statistical integrity needs Lot 27.

## Recommendation

`PROCEED`

The architecture holds under its worst load, the content model produces lean
and sourced material, and the two defects found were in the tooling rather than
in the model. Scale to the content lots in Master Plan §14 order, starting with
**Lot 01 — PHP** (9 items).

Watch as volume grows: the level distribution (`DEEP` must never be the
default), the running revision-time estimate, and `DUP-001` pressure as related
items accumulate.
