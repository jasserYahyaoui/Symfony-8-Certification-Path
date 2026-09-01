# SYLLABUS + LOT STRUCTURE AUDIT

**Date:** 2026-09-01
**Source of truth:** official exam page PDF, supplied by the project owner,
sourced from <https://certification.symfony.com/exams/symfony.html>
**Commit:** see git history for this report

---

## Syllabus

| Metric | Value |
|---|---|
| Official topics (enumerated in the source) | **14** |
| Imported topics | **14** |
| Official atomic items | **163** |
| Imported items | **163** |
| **Missing items** | **0** |
| **Unexpected items** | **0** |
| **Modified formulations** | **0** |
| **Duplicate official items** | **0** |
| **Order mismatches** | **0** |

```text
syllabus_complete: true
coverage: 0% (0 / 163 EXAM_READY)
```

Coverage is now a **real figure for the first time**. It is 0% because no item
has yet been taught, assessed and verified — not because the denominator is
unknown. Those were different claims throughout Lot 0, and the engine
distinguished them deliberately.

### How fidelity was verified, not asserted

Two automated passes against the raw extracted PDF text:

1. **Matrix → source.** Every one of the 163 `official_wording` values was
   searched for in the PDF text. **163 of 163 found verbatim; 0 missing.**
2. **Source → matrix.** Every line of the syllabus body (198 lines, from the
   `PHP` heading to the exclusions list) was checked for coverage by some
   imported item. **4 lines unaccounted for**, all belonging to the two
   standalone scope Notes:
   - *"Note: third-party transports (Doctrine, Redis, Amazon SQS, etc.) and
     their usage/configuration is not included"*
   - *"Note: PHPUnit Bridge is not included"*

   These are scope notes, not items. They are modelled as
   `exclusion_boundaries` on the items they qualify, which is why they match no
   item wording. This is the correct outcome, not a gap.

### Normalisation applied

Two mechanical transformations, neither changing wording:

- **Line wraps rejoined.** The PDF lays topics out in narrow columns, splitting
  items such as *"Release management and roadmap schedule"* across two lines.
- **F-ligatures normalised.** PDF fonts encode `fi`/`fl`/`ffi` as single glyphs
  that extract as `ﬁ`/`ﬂ`/`ﬃ`. A rendering artefact, not the official spelling:
  `Conﬁguration` → `Configuration`.

### Per-topic breakdown

| # | Official topic | Items | Delivery lot(s) |
|---:|---|---:|---|
| 1 | PHP | 9 | lot-01 |
| 2 | HTTP | 10 | lot-02 |
| 3 | Symfony Architecture | 15 | lot-03 |
| 4 | Controllers | 14 | lot-04 |
| 5 | Routing | 12 | lot-05 |
| 6 | Templating with Twig | 14 | lot-06 |
| 7 | Forms | 13 | lot-07 |
| 8 | Data Validation | 8 | lot-08 |
| 9 | Dependency Injection | 12 | lot-09 |
| 10 | Security | 12 | lot-10 |
| 11 | Messenger | 7 | lot-11 |
| 12 | Console | 9 | lot-12 |
| 13 | Automated Tests | 9 | lot-13 |
| 14 | Miscellaneous | 19 | lot-14 … lot-26 |
| | **Total** | **163** | |

### Recorded discrepancy: "15 topics" vs 14 enumerated

The exam page advertises **15 topics** while enumerating **14** headings. The
most plausible reading is that `Components:`, nested under *Miscellaneous* with
its own list of twelve, is counted as a topic in its own right.

Not resolved by guessing (§2.5). It affects no figure here: coverage is
computed from atomic items, never from topic counts.

### ID stability

Completing the import reused **all 115 identifiers** minted during the partial
import and minted **48 new ones**. **Zero renumbering, zero broken references.**

This is what ADR-0002 was for. Because identifiers are random and independent
of wording, position and grouping, adding four topics at the *front* of the
syllabus cost nothing. The reservation of `official_topic_order` 1–4 during the
partial import paid off exactly as intended.

### Wording lock now armed

`docs/syllabus/wording.lock.yml` fingerprints all 163 official wordings, which
activates CI rule `SYL-002`. Verified live rather than assumed: rewording
`Enums` to `Enumerations` was rejected with

```text
[ERROR] SYL-002: Official wording changed without an approved syllabus refresh.
1 violation(s), 1 blocking.
```

An accidental reword can no longer pass as an ordinary content edit.

---

## Lot numbering

Full reasoning in [ADR-0004](../adr/0004-lot-numbering.md).

| Lot | Scope | Items |
|---|---|---:|
| 00 | Infrastructure, governance, initial audit | 0 |
| 00.5 | Golden Slice | 0 |
| 01 | PHP | 9 |
| 02 | HTTP | 10 |
| 03 | Symfony Architecture | 15 |
| 04 | Controllers | 14 |
| 05 | Routing | 12 |
| 06 | Templating with Twig | 14 |
| 07 | Forms | 13 |
| 08 | Data Validation | 8 |
| 09 | Dependency Injection | 12 |
| 10 | Security | 12 |
| 11 | Messenger | 7 |
| 12 | Console | 9 |
| 13 | Automated Tests | 9 |
| 14 | Configuration and error handling | 3 |
| 15 | Profiler and deployment | 2 |
| 16 | Internationalization and localization | 1 |
| 17 | HTTP Caching | 1 |
| 18 | Cache | 1 |
| 19 | Clock | 1 |
| 20 | EventDispatcher and Event | 2 |
| 21 | Filesystem and Finder | 2 |
| 22 | Mailer and Mime | 2 |
| 23 | Process | 1 |
| 24 | PropertyAccess | 1 |
| 25 | Runtime | 1 |
| 26 | Serializer | 1 |
| 27 | Final review and mock exams | 0 |

**Numbering: coherent** — after one correction.

### Why content appeared to start at lot 05

**Lots 01–04 were never missing from the plan.** Master Plan §14 defines them
as PHP, HTTP, Symfony Architecture and Controllers. They were absent from the
*matrix* because the first syllabus text supplied on 2026-08-31 was truncated:
it began mid-item with the fragment `resolvers`.

The complete PDF settles what that fragment was. **`Argument value resolvers`** —
the fourteenth and last item of **Controllers**. So the truncation cut cleanly
at the end of topic 4, which is exactly why the import started at topic 5. The
numbering was correct; the data was incomplete.

### The one genuine defect, now corrected

All 19 items of *Miscellaneous* had been assigned to a single `lot-14`. Master
Plan §14 splits that topic across thirteen delivery lots (14–26). Lumping them
together would have produced one implausibly large lot while lots 15–26 sat
empty, concealing the true shape of the remaining work.

**Impact of the correction: none beyond intent.** Identifiers are independent of
grouping (ADR-0002), so no cross-reference broke; only generated documentation
paths moved, and those are rebuilt on every run (ADR-0003). The `by_lot`
coverage breakdown now reflects reality.

### Principle recorded

**Lot ≠ syllabus topic.** `official_topic_order` is set by the official source
and may never be invented or renumbered. `lot` is a delivery grouping set by
Master Plan §14. They coincide for topics 1–13, which makes them easy to
conflate — and *Miscellaneous* proves they are not the same axis: one official
topic, thirteen delivery lots.

---

## Golden Slice

**Not started.** Gated behind this audit by the owner's own ordering; it is the
next action.

```text
MINIMAL:  not yet selected
STANDARD: not yet selected
DEEP:     not yet selected
```

Result: **N/A** — not attempted, therefore not claimed.

---

## Content Budget

**PASS**, trivially and by construction.

| Resource | Count |
|---|---:|
| Courses | 0 |
| Flashcards | 0 |
| Questions | 0 |
| Exercises / Labs / Source Tours | 0 |

No teaching content exists, so nothing has been over-produced. The budget rules
(§1.2 admission test, §1.4 net-value gate, §1.3 stop rule) are encoded as review
policy and, where mechanisable, as CI rules `SCOPE-002` (enrichment ≤ 10%) and
`DUP-001` (near-duplicate detection). Their first real test is the Golden Slice.

The 163 generated item pages are **not** content: each is a stub rendering the
official wording, scope boundary and status, generated from the matrix. They
carry no explanations, no examples and no revision burden.

---

## Technical Gate

**PASS.**

| Check | Result |
|---|---|
| Build | `php bin/cert build` → 192 pages generated |
| Automated tests | `vendor/bin/phpunit` → **59 tests, 383 assertions, OK** |
| Linting | PHP lint clean; `yaml-lint` → 4 files valid |
| Schema and type validation | 163 items load; `tsc --noEmit` clean |
| Content rules (§12) | 14 rules, **0 violations** |
| Question-pool isolation | Enforced at build; asserted in CI and in production |
| Persistence and migrations | Versioned `localStorage` with migration chain |
| CI/CD | Green |
| Deployment | Live |
| Production smoke test | Passing on the previous commit; re-run on this one |

---

## Pedagogical Gate

**NOT ASSESSABLE.**

There is no teaching content to assess. Atomic official coverage is now
measurable (163 items, 0 EXAM_READY) and version compatibility is settled
(Symfony 8.0; Twig 3.22), but minimum sufficiency, prerequisite coherence,
learning-outcome coverage, question clarity, English readiness and mastery
evidence all require content that does not yet exist.

Recording this as "PASS" would be a claim from intention (§16).

---

## Remaining blockers

**None.** Every blocker raised in Lot 0 is now closed:

| ID | Status |
|---|---|
| B-1 — syllabus access | **Closed.** 163 items imported and verified |
| B-2 — PHP vs static Pages contradiction | Closed by ADR-0001 |
| B-5 — examinable Twig version | **Closed.** Syllabus states 3.22 |
| B-6 — GitHub Pages not enabled | Closed; deploy and smoke test green |

Open non-blocking risks:

- **SITE-1.** Prism's `twig` language cannot be enabled (Docusaurus 3.10 SSR
  provides no global `Prism`). Fence Twig samples as `html` in Lot 6.
- **V-3.** `symfony/symfony@8.0` HEAD is ahead of the latest release; anchor
  evidence to pinned SHAs and prefer release tags for version-sensitive claims.
- **Static hosting exposes question data** (ADR-0001). Inherent to the target;
  holdout integrity is a convention, not a guarantee.
- **Accessibility is unaudited by tooling.** A design commitment verified by
  inspection; first real audit belongs to the Golden Slice.

---

## Recommended next action

**Start the Golden Slice (Lot 0.5).** The architecture is proven end to end and
the syllabus is complete, so the slice can finally validate the *pedagogical*
pipeline against real official items rather than invented ones.

Proposed selection, to be confirmed against the sources during the slice:

| Level | Candidate | Why this level |
|---|---|---|
| `MINIMAL` | **HTTP → Status codes** | Recognition-oriented; one concise explanation and one source suffice |
| `STANDARD` | **Routing → Configuration (YAML and PHP attributes)** | Requires distinction between two equivalent mechanisms, and application |
| `DEEP` | **Security → Authenticators, Passports and Badges** | Structural, multi-class flow, demonstrably confused; §18 itself flags Security as the hard case |

The `DEEP` choice is deliberate: validating the architecture against its worst
load first is more informative than validating it against its easiest.
