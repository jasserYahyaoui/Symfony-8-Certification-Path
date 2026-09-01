# Lot 01 Report — PHP

**Date:** 2026-09-01
**Branch:** `master`
**Plan reference:** Master Plan §14 (Lot 1), §4, §6, §7, §16, §19

---

## Status

`PASS`

## Atomic official items

- **Assigned:** 9 (all of official topic 1, *PHP*)
- **EXAM_READY:** 9
- **Blocked:** 0

```text
coverage: 7.36%  (12 / 163 EXAM_READY)
```

| # | Item | Level |
|---:|---|---|
| 1 | PHP API up to PHP 8.4 version | `STANDARD` |
| 2 | Object Oriented Programming | `STANDARD` |
| 3 | Attributes | `STANDARD` |
| 4 | Interfaces | **`MINIMAL`** |
| 5 | Anonymous functions and closures | `STANDARD` |
| 6 | Abstract classes | `STANDARD` |
| 7 | Exception and error handling | `STANDARD` |
| 8 | Traits | `STANDARD` |
| 9 | Enums | `STANDARD` |

## Content decisions

**No `DEEP` item in this lot.** §4.1 says `DEEP` must never be the default, and
nothing here earns it: PHP's object model is intricate in places but not
structurally deep in the way Security's authentication flow is. Claiming `DEEP`
for Traits or Enums would have inflated the level to look thorough.

**One `MINIMAL`: Interfaces.** Its rules fit in a single example plus a list of
prohibitions. Crucially, the interface-versus-abstract-class comparison is
*not* here — it belongs to *Abstract classes*, which owns it. Teaching it twice
would double the revision cost for one idea (§4.5).

**Object Oriented Programming was scoped by subtraction.** It is a catch-all
item sitting beside eight siblings that are themselves OOP topics. Rather than
restate attributes, interfaces, abstract classes, traits and enums, the course
covers only what no other item owns: visibility semantics, `self` versus
`static`, `readonly`, and constructor promotion. Without that discipline this
one item would have duplicated the whole lot.

### Deliberate omissions

- **No flashcard for Interfaces** — a MINIMAL item whose rules are derivable
  from its single example.
- **No flashcard for Abstract classes** — its value is a comparison table,
  which is reference material, not recall. The `self`/`static` card already
  tests the resolution question that matters.
- **No exercises, labs or Source Tours** — every learning outcome here is
  testable by a focused question. §8 permits an exercise only when it tests
  application *better* than a question can, and none of these do.

### Volume

| Resource | Lot 01 | Project total |
|---|---:|---:|
| Courses | 9 (3 162 body words) | 12 (4 685 body words) |
| Flashcards | 7 | 11 |
| Questions (LEARNING) | 18 | 24 |
| Questions (HOLDOUT) | 2 | 3 |
| Exercises / Labs / Source Tours | 0 | 0 |

Average 351 body words per item — below the Golden Slice's 545, because this lot has
no `DEEP` item. Estimated revision time for Lot 01: **22–26 minutes**.

## Evidence

### Sources verified

Every version-sensitive claim was read from source, not recalled:

- **PHP 8.4 core features** taken from `php-src` branch `PHP-8.4`, file
  `UPGRADING`, section *New Features > Core*: property hooks, asymmetric
  property visibility, lazy objects, `#[\Deprecated]`, dereferencable `new`.
  This matters because the exam is PHP 8.4 and misattributing a feature by one
  minor version is a plausible wrong answer.
- **Asymmetric visibility syntax** confirmed as `public private(set) string $title`,
  including in constructor promotion and on static properties.
- **Trait precedence** confirmed verbatim: *"methods from the current class
  override Trait methods, which in turn override methods from the base class."*
  The intuition that a trait beats the class is wrong.
- **Throwable** confirmed: `Error` and `Exception` are the two branches, and
  *"PHP classes cannot implement the Throwable interface directly"* — so a
  custom throwable must extend one of them.
- **Enums** confirmed: backed enums implement the internal `BackedEnum`
  interface; `from()` throws, `tryFrom()` returns null; redefining either is a
  fatal error; `name` and `value` are readonly.
- **Arrow functions** confirmed: *"it will be implicitly captured by-value"*,
  with no `use` clause and therefore no by-reference capture.

### Tests

```text
vendor/bin/phpunit    → OK (69 tests, 399 assertions)
php bin/cert validate → 16 rules, 163 items, 27 questions, 0 violations
php bin/cert coverage → 7.36% (12/163 EXAM_READY)
npm run build         → SUCCESS
```

### Pool isolation

```text
practice.json  pool=LEARNING  24 questions
exam.json      pool=HOLDOUT    3 questions
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


### A source-quality finding

Promoting the nine items initially raised **10 non-blocking `SRC-001`
warnings**: PHP manual references pointed at whole page files with no anchor.

The rule was right. §2.4 requires evidence precise enough to re-verify, and
"the enumerations page" is weaker than "the *Backed Enumerations* section". The
warnings were resolved by adding real `symbol_or_lines` anchors to all ten
sources — not by relaxing the rule. A warning that gets silenced is a rule that
stops working.

## Gates

| Gate | Result |
|---|---|
| **Technical** | **PASS** |
| **Pedagogical** | **PASS** |
| **Content Budget** | **PASS** |

### Content Budget detail

| Question (§1.2, §1.4) | Answer |
|---|---|
| Syllabus-relevant? | Yes — 9 items, each verbatim from topic 1 |
| Exam-relevant? | Yes — every trap taught is one the exam can pose |
| Necessary? | Yes — three resource classes rejected, listed above |
| Already covered? | No — `DUP-001` and `FLC-001` clean; OOP scoped by subtraction |
| Unique value? | Yes — 7 flashcards for 9 items, not 9 by reflex |
| Revision cost justified? | Yes — 376 words per item, ~24 minutes for the lot |

`DO NOT SCALE` not triggered.

## Remaining risks

- **The revision-burden projection is firming up but still incomplete.** Two
  lots give 376 (no DEEP) and 545 (one DEEP in three) words per item. The
  project total will hinge on how many of the remaining 151 items justify
  `DEEP` — a number that should be watched, not assumed.
- **`SRC-001` only checks that an anchor exists, not that it is accurate.** A
  wrong `symbol_or_lines` passes CI. Only review catches that.
- **The PHP manual is tracked on `master`, not a version branch.** Unlike
  `symfony-docs@8.0`, `php/doc-en` has no per-version branch to pin, so its
  content can drift under a fixed URL. Anchors reduce but do not remove the
  risk; a claim about 8.4 specifics should be re-checked against `php-src`
  `PHP-8.4`, which *is* version-pinned.
- **No accessibility audit** has been run against the deployed pages.

## Recommendation

`PROCEED` to **Lot 02 — HTTP** (10 items, one already `EXAM_READY` from the
Golden Slice, so 9 remain).
