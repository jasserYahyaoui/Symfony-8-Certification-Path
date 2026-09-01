# Lot 07 Report — Forms

**Date:** 2026-09-01
**Branch:** `lot-07-forms` → Pull Request → `master`
**Plan reference:** Master Plan §14 (Lot 7), §4, §6, §7, §15, §16, §19

---

## Status

`PASS`

## Atomic official items

- **Assigned:** 13 (topic 7, *Forms*, in full)
- **Previously EXAM_READY:** 0 · **Newly EXAM_READY:** 13 · **Blocked:** 0

```text
new this lot:  +13
cumulative:    53.99%  (88 / 163 EXAM_READY)
```

| # | Item | Level | | # | Item | Level |
|---:|---|---|---|---:|---|---|
| 1 | Form component | `STANDARD` | | 8 | Handling file upload | `MINIMAL` |
| 2 | Forms creation | `STANDARD` | | 9 | Built-in form types | `MINIMAL` |
| 3 | Forms handling | `STANDARD` | | 10 | Data transformers | `STANDARD` |
| 4 | Form types (built-in and custom) | `STANDARD` | | 11 | Form events | `DEEP` |
| 5 | Forms rendering with Twig | `STANDARD` | | 12 | Form type extensions | `MINIMAL` |
| 6 | Forms theming | `STANDARD` | | 13 | Form options (OptionsResolver) | `STANDARD` |
| 7 | CSRF protection | `STANDARD` | | | | |

## Evidence

Read from `symfony/symfony-docs` at `eea05cbfe063`.

- **Three data layers**, model / normalized / view, with the documented
  `DateType` example separating them: a `DateTime`, then integers in an array,
  then the same array with string values. Rendering descends, submission climbs.
- **`submit($data, $clearMissing)`** defaults `$clearMissing` to `true`, setting
  missing fields to `null`. With `false` — the `PATCH` case — **validation
  applies only to the submitted fields**, and a field that must still be
  validated has to be added explicitly with a `null` value.
- **After a valid submission the original object is already updated**; the
  documentation says so plainly, which is why `getData()` and the variable
  passed to `createForm()` are the same object.
- **`form_end()` renders the fields not yet rendered**, which is what carries
  the hidden CSRF field. `render_rest: false` removes it.
- **Theme block lookup runs most-specific first**:
  `_user_contact_widget` → `email_widget` → `text_widget`. The five parts are
  `_row`, `_label`, `_widget`, `_help`, `_errors`.
- **`twig.form_themes` is consulted from the end of the list**: the last entry
  is checked first and the first entry is the last fallback — the inverse of
  reading order, and silent when got wrong.
- **Form events**, six of them in a fixed order. Structure is locked **from
  `SUBMIT` onward**, so fields are added at `PRE_SET_DATA` or `PRE_SUBMIT` only;
  **validation runs through a listener on `POST_SUBMIT`**; a dependent field is
  added to the **parent** form, because the child's own structure is fixed.
- **`transform()` goes model → view, `reverseTransform()` view → model.**
  `addModelTransformer()` sits between model and normalized,
  `addViewTransformer()` between normalized and view.
- **`getExtendedTypes()` is static** and the only method a type extension must
  implement; extending `FormType::class` reaches every field.
- **`setDefined()` declares an option without a value**, where `setDefaults()`
  gives one; an option that is neither raises an exception.

### `CRS-001` fired once, and was right

The first draft leaked `TransformationFailedException` — a 30-character correct
answer that the *Data transformers* course must name in prose. Fixed by
rewriting the **question**: it now asks what the form *does* with that
exception rather than which class to throw, which tests the more useful fact
anyway. No teaching was cut and nothing was moved into a fence.

### Boundaries and exclusions

| Lot 07 item | Neighbour | Boundary drawn |
|---|---|---|
| Handling file upload | Lot 04 *File upload* | the form wiring — `FileType`, `mapped: false`, `$form->get()->getData()`. `UploadedFile`, `move()` and the untrusted client metadata stay in Lot 04 |
| CSRF protection | Lot 04 `isCsrfTokenValid()` | what the form does automatically; manual verification stays in Lot 04 |
| Forms rendering with Twig | Lot 06 *Filters and functions* | the form helpers themselves; where Twig extensions come from stays in Lot 06 |
| Data transformers | Lot 07 *Form component* | writing a transformer; the three layers are defined once, in the component item |
| Built-in form types | §1.5 exclusions | `EntityType` and Symfony UX fields are **out of scope**, named as such in the course and never tested |

### Content produced

|  | New this lot | Cumulative |
|---|---:|---:|
| Courses | 13 | 88 |
| Course body words | 4 434 | 30 795 |
| Flashcards | 9 | 62 |
| Questions — LEARNING | 27 | 179 |
| Questions — VALIDATION | 10 | 66 |
| Questions — HOLDOUT | 2 | 15 |

Word counts are **body words**. All ten `STANDARD`/`DEEP` items carry a
VALIDATION question, per `POOL-002`. Nine flashcards cover six items; seven
carry none.

### Tests actually executed

Each command run with `set -o pipefail` and its exit code checked (PROC-1).

```text
php bin/cert validate     17 rules, 163 official items, 260 questions — no violations   (exit 0)
php bin/cert coverage     53.99% (88/163)                                                (exit 0)
php bin/cert build        docs tree + coverage.json, exam.json, practice.json            (exit 0)
vendor/bin/phpunit        82 tests, 805 assertions — OK                                  (exit 0)
npm --prefix website run build     SUCCESS                                               (exit 0)
npm --prefix website run a11y      6/6 surfaces PASS, TOTAL VIOLATIONS: 0                (exit 0)
```

Pools verified against the built payloads: `practice.json` = LEARNING (179),
`exam.json` = **VALIDATION** (66), no holdout question in either.

## Gates

| Gate | Result |
|---|---|
| **Technical** | **PASS** — validate, phpunit, site build and CI all green |
| **Pedagogical** | **PASS** — outcomes, justified level, teaching content, ≥2 questions, and a VALIDATION question wherever `POOL-002` requires one |
| **Accessibility** | **PASS** — 6/6 surfaces, 0 violations, on this lot's build |
| **Content Budget** | **PASS** — 341 body words per item (MINIMAL 220–291, STANDARD 321–411, DEEP 549) |

## Level distribution — observation only

New: `MINIMAL` 3, `STANDARD` 9, `DEEP` 1. Cumulative over 88 items:
`MINIMAL` 22, `STANDARD` 60, `DEEP` 6.

A result, not a target. *Form events* is the single `DEEP` item: six events in a
fixed order, two of which alone may change the form's structure, a lock that
falls at `SUBMIT`, validation carried by `POST_SUBMIT`, and a dependent field
that must be added to the parent. Every one of those changes an answer, and the
order itself is examined. Nothing else in the lot is a mechanism with an order —
theming's lookup chain is three lines, and handling is a short call sequence.

Five lots, five shapes: 3/10/2, 5/8/1, 3/8/0, 4/9/1, 3/9/1.

## Remaining risks

- **HOLDOUT confidentiality.** 15 holdout questions sit in no published payload
  since ADR-0006, but the repository is **public**, so their answers stay
  readable in `content/questions/`. Decision required before Lot 27 —
  [ADR-0005](../adr/0005-holdout-distribution-deferred.md).
- **Cross-lot boundaries held by prose** — twenty-seven now.
- **French share** of the bank is 21/260 and this lot added none. Still a
  decision the owner has not taken.

## Recommendation

Proceed to **Lot 08 — Data Validation** (8 items). It sits directly against this
lot: constraints are declared on the object, and the form reports them —
*Forms handling*'s `isValid()` is the seam. Expect several `MINIMAL` items once
that boundary is drawn.
