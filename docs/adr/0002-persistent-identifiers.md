# ADR-0002 — Minted persistent identifiers

- **Status:** Accepted
- **Date:** 2026-08-31

## Context

Master Plan §11 states: *"Persistent IDs must not depend on file names or
slugs."*

Every entity of §11 is cross-referenced from somewhere else: the matrix
references courses, flashcards, questions, exercises and prerequisites; a
question references its official item; the browser records attempts against
question ids. If an identifier were derived from a title, a slug, a file path
or a content hash, then rewording an official item, renaming a chapter or
reorganising directories would silently break every reference pointing at it —
and the breakage would look like missing content rather than like a rename.

## Decision

Identifiers are **minted from randomness once and never regenerated**.

Format: `<PREFIX>-<12 characters>` — for example `OIT-3k9m2xq7bv4t`.

- The prefix names the entity type (`OIT` official item, `QST` question,
  `CHO` choice, `CRS` course, …), so a malformed reference is caught by shape
  alone, before any lookup.
- The suffix is 12 characters of Crockford base32 **minus `i`, `l`, `o` and
  `u`**, which keeps ids unambiguous when read aloud or transcribed from a
  review comment.
- Minting is `random_int()`, never a hash of the content. Two entities with
  identical text receive different ids, and rewriting an entity's text leaves
  its id untouched — which is exactly the property §11 asks for.

Mint with:

```bash
bin/cert id:mint OfficialItem 20
bin/cert id:mint Question 50
```

## Consequences

- Ids are opaque. Reading `OIT-3k9m2xq7bv4t` tells you nothing about the item,
  so the matrix must stay the lookup table — which it already is.
- Ids must be written into the YAML by hand (or by the mint command) rather
  than derived on load. The loader validates shape and the CI rules `SYL-001`
  and `REF-001` guard uniqueness and resolvability.
- Collision probability is negligible: 32^12 ≈ 1.15 × 10^18 values per type.
  `SYL-001` still checks, because "negligible" is not "impossible".
- Runtime entities (`Attempt`, `Session`, `MasteryRecord`, `Weakness`) share the
  same scheme so that browser-side records reference build-time content
  unambiguously across a rebuild.
