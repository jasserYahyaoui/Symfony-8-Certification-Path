# Lot 0 Report — Infrastructure, governance and initial audit

**Date:** 2026-08-31
**Branch:** `master`
**Plan reference:** SYMFONY-8-CERTIFICATION-MASTER-PLAN-V2.md §14 (Lot 0), §16, §19

---

## Status

`BLOCKED`

The infrastructure half of Lot 0 is complete, merged and green in CI. The
content half — the verbatim syllabus import, which §14 lists first among Lot 0's
deliverables — could not be performed, and the deployment could not be
completed. Two blockers remain, both requiring human access.

Per §16, no part of this lot is reported as `DONE`, `COMPLETE`, `VALIDATED` or
`DEPLOYED`.

## Atomic official items

- **Assigned:** 0
- **EXAM_READY:** 0
- **Blocked:** all of them — the denominator does not exist

Coverage is reported as **UNDEFINED**, not `0%`. §3.5 defines coverage as
`EXAM_READY atomic official items / total atomic official items * 100`; with no
imported syllabus there is no denominator, and an undefined denominator is a
different claim from a zero numerator (§19).

Lot 0 assigns no atomic official items by design: it is infrastructure and
governance. The syllabus *import* is its content deliverable, and that is what
is blocked.

## Content decisions

- **MINIMAL:** 0
- **STANDARD:** 0
- **DEEP:** 0
- **Removed or shortened:** nothing — the repository was greenfield

One decision worth recording is what was **not** built. The Master Plan §14 lot
list enumerates topic areas in enough detail that a plausible syllabus matrix
could have been synthesised from it, which would have made this lot look
complete. §3.1 forbids exactly that: *"Lot descriptions are operational
groupings only. They are never the coverage denominator."* Populating the matrix
from §14 would have substituted an unofficial denominator that then looked
entirely credible, and every coverage figure for the rest of the project would
have measured the wrong thing. The matrix was left empty instead.

## Delivered

### Governance and architecture

| Deliverable | Location |
|---|---|
| Initial audit, 17 sections (§20) | [`docs/reports/lot-00-audit-report.md`](lot-00-audit-report.md) |
| ADR-0001 — build-time PHP, static runtime (resolves B-2) | [`docs/adr/0001-build-time-php-static-runtime.md`](../adr/0001-build-time-php-static-runtime.md) |
| ADR-0002 — minted persistent identifiers (§11) | [`docs/adr/0002-persistent-identifiers.md`](../adr/0002-persistent-identifiers.md) |
| Source-verification policy (§2) | [`docs/policy/source-verification.md`](../policy/source-verification.md) |
| Review algorithm specification (§6) | [`docs/policy/review-algorithm.md`](../policy/review-algorithm.md) |
| Matrix field guide (§3.3) | [`docs/policy/matrix-field-guide.md`](../policy/matrix-field-guide.md) |
| Accessibility baseline (§13) | [`docs/policy/accessibility-baseline.md`](../policy/accessibility-baseline.md) |

### Canonical data (§3.2, §11)

All five mandatory syllabus files exist. `exclusions.yml` is fully populated
from §1.5 and is machine-enforced; `source-map.yml` records every authority
with its reachable route and pinned commit SHA; `syllabus-matrix.yml` is
deliberately empty and documents why.

The 16 canonical entities of §11 are modelled in `src/Domain/` and
`src/Support/EntityType.php`, split into build-time entities (YAML, referential
integrity enforced) and runtime entities — `Attempt`, `Session`,
`MasteryRecord`, `Weakness` — which live only in the learner's browser (§13).

Schemas are versioned through `SchemaRegistry` with a `MigrationRunner`; a gap
in the migration chain raises an error rather than passing a document through
un-migrated.

### Coverage engine (§3.5)

An item counts as covered only when its `exam_ready` flag, its lifecycle status
and its verification status **all** agree. Flipping the boolean alone does not
move the number — pinned by
`CoverageCalculatorTest::testExamReadyFlagAloneDoesNotCountAsCovered`.

### Validation (§12)

14 mandatory rules in `src/Validation/RuleSet.php`:

| Rule | Enforces |
|---|---|
| `SYL-001` | Unique syllabus ids |
| `SYL-002` | Official wording matches the approved fingerprint lock |
| `PED-001` | Learning outcomes present; content level justified; DEEP substantively argued |
| `PED-002` | Implemented items have teaching content and an assessment |
| `SRC-001` | Sources present, version-anchored, never `/current/` |
| `QST-001` | Question mapping, answer-count consistency, distractor explanations, no unverified content scored |
| `POOL-001` | Holdout questions never referenced as learning material |
| `SCOPE-001` | No scored dependency on out-of-scope knowledge (§1.5) |
| `SCOPE-002` | Enrichment below the 10% ceiling |
| `DUP-001` | No near-duplicate questions |
| `REF-001` | Prerequisites and references resolve; no orphans |
| `VER-001` | No source outside Symfony 8.0 |
| `RDY-001` | Every EXAM_READY claim is backed by evidence |
| `LNK-001` | No dead internal links |

### Runtime (§9, §13)

Practice Mode filters by topic, difficulty and language, replays recorded
weaknesses, and reveals nothing until an answer is submitted. Exam Mode hides
all feedback until final submission, and on timeout **submits the answers
already entered** rather than discarding them.

Holdout isolation is structural rather than behavioural: the Practice payload
is assembled at build time from the learning pool alone, so a holdout question
is absent from the file the Practice page fetches. A front-end bug cannot leak
what was never shipped. `SiteBuilder` asserts this on every build.

## Evidence

### Sources verified

All §2.3 mandatory sources probed. Reachable with commit-SHA anchoring:

```yaml
symfony/symfony-docs:
  branch: "8.0"
  commit_sha: eea05cbfe063b9cf99afaf303b8cad76757f43bb
symfony/symfony:
  branch: "8.0"
  commit_sha: 6f841c00f41e5c037d40e1d739e2dc602c8f289d
```

Verified version facts: Symfony 8.0 requires PHP `>=8.4`; `Kernel::VERSION` on
branch 8.0 is `8.0.17-DEV`; latest release `v8.0.9`; Twig constraint
`^3.21|^4.0`.

Unreachable: `certification.symfony.com` (no upstream substitute — blocker B-1).
`symfony.com/doc`, `www.php.net` and `www.rfc-editor.org` are blocked but each
has an upstream repository that is used instead, as recorded in
`docs/syllabus/source-map.yml`.

### Tests

Local, PHP 8.4.19:

```text
vendor/bin/phpunit     → OK (55 tests, 371 assertions)
php bin/cert validate  → 14 rules, 0 official items, 0 questions, no violations
php bin/cert coverage  → Coverage: UNDEFINED (the syllabus has not been imported)
php bin/cert build     → 9 files generated
```

### CI

Run [`33439599181`](https://github.com/jasserYahyaoui/Symfony-8-Certification-Path/actions/runs/33439599181),
commit `a41cdd8` — **success**, all 13 steps: composer validation, PHP lint,
YAML lint, content rules, test suite, coverage-freshness diff, site build, and
the assertion that no PHP reaches the deployed artefact.

### Commits

- `3e39c48` — initial audit report and session continuity
- `a41cdd8` — Lot 0 infrastructure, governance and Pages pipeline
- `a0f3518` — Pages enablement attempt (superseded)

### Pull request

None. Direct to `master`, per Master Plan §0 and explicit instruction.

### Deployment URL

`https://jasseryahyaoui.github.io/Symfony-8-Certification-Path/` — **not live.**

### Production smoke test

**Not performed.** There is no production environment to test.

Two deployment attempts ran and both failed at `Configure Pages`:

- Run [`33439599488`](https://github.com/jasserYahyaoui/Symfony-8-Certification-Path/actions/runs/33439599488):
  `Get Pages site failed … Not Found` — the repository has no Pages site.
- Run [`33439704885`](https://github.com/jasserYahyaoui/Symfony-8-Certification-Path/actions/runs/33439704885),
  after adding `enablement: true`:
  `Resource not accessible by integration` — the workflow `GITHUB_TOKEN` cannot
  call the create-Pages-site API, which requires repository admin rights.

The `enablement: true` attempt was reverted, because it replaces a clear
"Pages is not enabled" message with a confusing permissions error. Both jobs
built the site successfully and passed the content gate before failing; only
publication is blocked.

## Gates

| Gate | Result |
|---|---|
| **Technical** | **PARTIAL.** Build, tests, linting, schema validation, scoring logic, question-pool isolation, persistence, migrations and CI all pass. Deployment and the production HTTP smoke test **fail** — blocked by B-6. |
| **Pedagogical** | **NOT ASSESSABLE.** There is no content, and no syllabus to assess it against. |
| **Accessibility** | **NOT ASSESSABLE by tooling.** The baseline is implemented and documented, and verified by inspection; no automated audit has been run because there is no deployed page to run it against. Recording this as "passed" would be a claim from intention (§16). |

### Critical blockers (§17)

| ID | Blocker | Owner |
|---|---|---|
| **B-1** | `certification.symfony.com` is egress-blocked. The verbatim import of §3.1 is impossible, so the §3.5 denominator is undefined and no content lot may begin. Unlike the other blocked domains, this one has no upstream repository to substitute. | **Human** — allow-list the domain, or supply the syllabus text |
| **B-6** | GitHub Pages is not enabled on the repository, and the workflow token cannot enable it. | **Human** — Settings → Pages → Source: "GitHub Actions", once |

## Remaining risks

- **B-1 governs every downstream estimate.** The atomic item count is the
  project's real unit of work and remains unknown, so the §14 effort estimates
  in the audit report carry that uncertainty unchanged.
- **B-5 — Twig version.** The plan pins Twig 3.22; Symfony 8.0 allows
  `^3.21|^4.0` and the 3.x branch is at 3.29. Marked
  `UNKNOWN_NEEDS_VERIFICATION`; no Lot 6 content may be scored against a
  specific Twig minor version until the syllabus confirms it.
- **V-3 — branch drift.** `symfony/symfony@8.0` HEAD is `8.0.17-DEV`, ahead of
  released `v8.0.9`. Content verified against branch HEAD could describe
  behaviour absent from any released 8.0.x. Mitigation: anchor to the pinned
  SHA and prefer release tags for version-sensitive claims.
- **Static hosting exposes question data.** Any static site ships its data to
  the browser, so a determined learner can read `data/exam.json`, including the
  holdout pool. This is inherent to the deployment target, is documented in
  ADR-0001, and is accepted because the defeat is self-inflicted — but it means
  holdout integrity is a convention, not a guarantee.
- **The accessibility baseline is unaudited.** It is a careful design
  commitment, not a measured result. The first real audit belongs to Lot 0.5.

## Recommendation

`FIX`

Both blockers are single, well-defined human actions, and neither requires any
change to the work already delivered:

1. **B-6 (two minutes):** enable GitHub Pages with source "GitHub Actions".
   The next push then builds, deploys and runs the production smoke test
   automatically — the pipeline is already written and already passes its
   build and gate steps.
2. **B-1 (the real blocker):** allow-list `certification.symfony.com`, or paste
   the official syllabus and FAQ text for verbatim import.

Once B-1 clears, the sequence is: import → matrix → wording lock → Lot 0.5
Golden Slice → architecture approval → content lots.

Lot 0.5 must not start before B-1: a Golden Slice built on invented syllabus
items would validate the architecture against the wrong content and would have
to be redone.
