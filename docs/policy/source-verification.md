# Source-verification policy

Implements Master Plan §2.

## Authority order

**Scope** — what is examinable (§2.1):

1. the official Symfony 8 Certification syllabus;
2. the official certification FAQ and published exam constraints;
3. explicit official exclusions.

**Technical behaviour** — how Symfony 8.0 actually works (§2.2):

1. Symfony 8.0 documentation;
2. Symfony 8.0 source code and tests;
3. official PHP documentation up to PHP 8.4;
4. RFC 9110 for HTTP;
5. Twig official documentation;
6. official component documentation applicable to Symfony 8.0.

The syllabus defines scope. It is not API documentation and must not be used
as such.

## Reachability in this environment

The Lot 0 audit measured every mandatory source. Two authorities are blocked by
the network egress policy and one is version-ambiguous. The current routes are
recorded in [`docs/syllabus/source-map.yml`](../syllabus/source-map.yml).

Where a rendered site is blocked, its **upstream repository is used instead**:

| Blocked | Used instead | Why it is equal or better |
|---|---|---|
| `symfony.com/doc/8.0` | `symfony/symfony-docs@8.0` (raw) | The rendered pages are generated from this RST; the repository additionally yields a commit SHA |
| `www.php.net/manual` | `php/doc-en@master` (raw) | Upstream of the rendered manual |
| `www.rfc-editor.org/rfc/rfc9110` | `httpwg/httpwg.github.io` mirror | Published by the HTTP working group itself |
| `certification.symfony.com` | **nothing** | There is no upstream. This is blocker B-1 and no substitute is acceptable |

## Evidence requirements

Every technical claim records, per §2.4:

```yaml
repository:
branch: "8.0"
commit_sha:
file:
symbol_or_lines:
verified_at:
verified_by:
```

For documentation, the exact page, section anchor, target version and
verification date are required. **A documentation homepage is not evidence for
a precise technical claim.**

## Enforced automatically

| Rule | Enforces |
|---|---|
| `SRC-001` | A `SOURCE_VERIFIED` item has sources; each is version-anchored; `/current/` is rejected outright (§2.3) |
| `VER-001` | No source points at a Symfony version other than 8.0 |
| `RDY-001` | `EXAM_READY` requires sources, content, an assessment and a verification date |

## Contradictions

Per §2.5: verify the version, verify the context, compare the 8.0 documentation
with the 8.0 source and tests, document the resolution, **quarantine affected
questions until resolved**, and raise an ADR only if the resolution changes
architecture or policy.

Unresolved knowledge is `UNKNOWN_NEEDS_VERIFICATION` and may never appear in
scored content — enforced by `QST-001`.

## Open contradiction

**B-5 — Twig version.** The Master Plan pins Twig 3.22. Symfony 8.0's
`composer.json` requires `twig/twig: ^3.21|^4.0`, and the Twig 3.x branch has
reached 3.29. "3.22" is therefore not derivable from Symfony 8.0 and is
presumably a syllabus statement. Status: `UNKNOWN_NEEDS_VERIFICATION`. No Lot 6
content may be scored against a specific Twig minor version until the official
syllabus is readable.
