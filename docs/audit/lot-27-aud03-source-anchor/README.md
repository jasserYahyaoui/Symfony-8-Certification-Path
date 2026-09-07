# AUD-03 — Source and anchor audit

**Master Plan §14, §2.2–§2.4 · bears on §22 clause 5**

| | |
|---|---|
| State | `NOT RUN` → `RUNNING` → **`FAIL`** |
| Run at | 2026-09-07 |
| Commit audited | `2ece943` + this change |
| Script | [`tools/audit/aud03_source_anchor.py`](../../../tools/audit/aud03_source_anchor.py) |
| Raw output | [`output.txt`](output.txt) — exit 1 |
| Checks proved to fire | [`fail-proof.txt`](fail-proof.txt) |

**`FAIL` is the honest result and it is recorded as `FAIL`.** 105 citations
carry no anchor. Nothing was relaxed to make this read `PASS`.

## Question asked

AUD-02 asks whether a source points at the right *version*. This audit asks the
three questions that come before it: does every taught or scored record cite a
source at all, does every citation carry an anchor a reader can follow, and
does every cited URL actually resolve?

§2.4 is the standard: *"For documentation, the exact page, section anchor,
target version and verification date are required. A documentation homepage is
not evidence for a precise technical claim."*

## Checks

| id | Check | Result |
|---|---|---|
| `ANCHOR-1` | every question, course and flashcard cites at least one source | **PASS** — 0 |
| `ANCHOR-2` | every citation carries a URL | **PASS** — 0 |
| `ANCHOR-3` | every source URL is https | **PASS** — 0 |
| `ANCHOR-4` | every citation carries `symbol_or_lines` **or** `anchor` | **FAIL — 105** |
| `ANCHOR-5` | every citation carries `verified_at` | **PASS** — 0 |
| `ANCHOR-6` | `verified_at` parses as a date | **PASS** — 0 |
| `ANCHOR-7` | `verified_at` is not in the future | **PASS** — 0 |
| `ANCHOR-8` | every distinct source URL resolves, checked live | **PASS after repair** — see below |

## Corpus measured

| | |
|---|---|
| questions / citations | 544 / 547 |
| courses / citations | 163 / 223 |
| flashcards / citations | 137 / 137 |
| distinct URLs | 160, **all 160 returning HTTP 200** |

## Finding SRC-4 — two dead citations, repaired in this unit

`ANCHOR-8` fetched all 161 distinct URLs live and two returned **404**. A
citation that 404s is worse than a vague one: the claim cannot be checked at
all.

| Question | Cited (404) | Corrected to | Why that source |
|---|---|---|---|
| `QST-psqn0fe95khc`-adjacent property-hooks question | `php/doc-en/master/reference/language/oop5/property-hooks.xml` | `php/doc-en/master/language/oop5/property-hooks.xml` | a stray `reference/` prefix; the real file is titled *Property Hooks* and states *"Property hooks were introduced in PHP 8.4"*, which is what the question tests |
| the `ReflectionAttribute::newInstance` question | `php/doc-en/master/language/attributes/reflection.xml` | `php/doc-en/master/language/attributes.xml` | that path has never existed. The claim — that argument validation is deferred — is stated verbatim at `language/attributes.xml`: *"Objects of the attribute class are instantiated only after calling ReflectionAttribute::newInstance, ensuring that argument validation occurs at that point."* The method reference page `reference/reflection/reflectionattribute/newinstance.xml` exists but says nothing about deferred validation, so it would have been the worse anchor |

Both corrected URLs were re-fetched and return 200. The anchors were rewritten
to quote the sentence that carries the claim rather than name a symbol.

## Finding SRC-5 — 105 citations with no anchor at all

`ANCHOR-4`. **This is not repaired in this unit** and is tracked as issue
**SRC-5**; see *Why not repaired here*.

| | |
|---|---|
| Unanchored citations | **105** of 907 (11.6%) |
| Distinct records affected | **103** |
| Distinct URLs affected | **57** |
| By kind | flashcards 68, courses 21, questions 16 |

By file:

| File | Unanchored |
|---|---|
| `lot-01-php.yml` | 19 |
| `lot-09-dependency-injection.yml` | 12 |
| `lot-10-security.yml` | 11 |
| `lot-12-console.yml` | 9 |
| `lot-13-automated-tests.yml` | 9 |
| `lot-11-messenger.yml` | 7 |
| `lot-08-data-validation.yml` | 3 |
| `lot-14-miscellaneous.yml` | 3 |
| `CRS-3424z948caan.md` | 2 |
| `CRS-tqcp2kn9r3b5.md` | 2 |
| `lot-15-miscellaneous.yml` | 2 |
| `lot-20-miscellaneous.yml` | 2 |
| `lot-21-miscellaneous.yml` | 2 |
| `CRS-0jtjh77tabt1.md` | 1 |
| `CRS-2s6e4qgkcqza.md` | 1 |
| `CRS-3p9jdkw1nd3g.md` | 1 |
| `CRS-3sggmeyd01x6.md` | 1 |
| `CRS-722pscs6e2m0.md` | 1 |
| `CRS-96w05v20b8w1.md` | 1 |
| `CRS-awb7sd999c5j.md` | 1 |
| `CRS-ax2v94pgbk0g.md` | 1 |
| `CRS-bthv5xmh5wea.md` | 1 |
| `CRS-e4y7gtn5k4f0.md` | 1 |
| `CRS-exs5dvtqa1as.md` | 1 |
| `CRS-p1694d5f7r8c.md` | 1 |
| `CRS-rdzx4ka72saj.md` | 1 |
| `CRS-rk0fkn1q5byc.md` | 1 |
| `CRS-vskyr5zdwr2t.md` | 1 |
| `CRS-x5frtpg07mmd.md` | 1 |
| `CRS-yc9fry0vz4gh.md` | 1 |
| `golden-slice.yml` | 1 |
| `lot-16-miscellaneous.yml` | 1 |
| `lot-17-miscellaneous.yml` | 1 |
| `lot-18-miscellaneous.yml` | 1 |
| `lot-19-miscellaneous.yml` | 1 |
## Finding SRC-6 — the systemic cause: `SRC-001` never looks here

The anchor requirement is not unenforced by oversight in one lot. It is
structurally unreachable for this content.

`SourceRef::hasAnchor()` exists and is correct — it accepts either
`symbol_or_lines` or `anchor`. Rule `SRC-001` calls it. But `SRC-001` iterates
`$content->matrix->officialItems()` and inspects **the matrix items'** own
sources. The citations on courses, questions and flashcards — 907 of them, the
ones a learner actually follows — are never passed to it. And where it does
run, `hasAnchor()` raises `Severity::Warning`, which does not fail a build.

So the invariant has been unchecked for the life of the project, and every gate
passed throughout. This is the fourth instance of one pattern already recorded
here as `SPLICE-1`, `SPLICE-2` and `COG-1`: **an invariant nothing checks is
not an invariant.**

## Why not repaired here

Repairing 105 citations means, for each, reading the record's claim, fetching
the cited source, locating the passage that supports it, and quoting that
passage. The two `SRC-4` repairs above show the shape and the cost: one of them
also required rejecting the *plausible* source (the method reference page) in
favour of the one that actually carries the claim.

Doing that 105 times inside this audit unit would mean writing anchors faster
than they can be verified, which produces exactly the artefact this audit
exists to detect — a citation that looks precise and is not. The repair is
`SRC-5`, its own unit, followed by strengthening `SRC-001` so the class cannot
recur.

**The audit's verdict stands at `FAIL` until `SRC-5` is done.**
