# CONTEXT.md — Session continuity (Master Plan §23)

**Last updated:** 2026-08-31

---

## Current lot

**Lot 0 — Infrastructure, governance and initial audit.**
Sub-step: §20 initial audit — **complete and reported**.
Lot 0 implementation: **not started** (blocked, see below).

## Current branch

`master` — created by this session. The repository previously had **no
commits and no branches at all**. Direct commits to `master` per plan §0
("Primary branch: master") and explicit user instruction.

## Completed work

- Full §20 initial audit (17 sections) executed.
- Repository inspected: confirmed fully empty — 0 commits, 0 branches,
  no `CLAUDE.md` / `AGENTS.md` / `CONTEXT.md` / `README.md` / `.github/`.
- Installed Claude Code skills inspected; plan §18 pipeline confirmed absent.
- Toolchain verified: PHP 8.4.19, Composer 2.8.12, Node 22.22.2, Python 3.11.15, Git 2.43.0.
- All §2.3 mandatory sources probed for reachability (results in the report).
- Commit SHAs resolved for evidence anchoring (§2.4):
  - `symfony/symfony-docs@8.0` → `eea05cbfe063b9cf99afaf303b8cad76757f43bb`
  - `symfony/symfony@8.0` → `6f841c00f41e5c037d40e1d739e2dc602c8f289d`
- Version facts verified against primary sources: Symfony 8.0 requires PHP `>=8.4`;
  `Kernel::VERSION` = `8.0.17-DEV`; latest release `v8.0.9`; Twig constraint `^3.21|^4.0`.
- Report written: `docs/reports/lot-00-audit-report.md`.
- **Decision issued: `CONDITIONAL_GO`.**

## Remaining work

Everything after the audit. Immediate next steps once unblocked:

1. Import the official syllabus verbatim → `docs/syllabus/official-syllabus.md`.
2. Build `docs/syllabus/syllabus-matrix.yml` with all §3.3 fields.
3. Author `docs/syllabus/exclusions.yml` from §1.5 + official exclusions.
4. Create `docs/syllabus/source-map.yml` and `coverage-report.md`.
5. Define the 15 canonical entities (§11), schema versions, migrations and
   the persistent-ID policy (IDs must not derive from file names or slugs).
6. Implement the coverage engine and the 20 CI checks of §12.
7. Specify and test the flashcard review algorithm (§6, required in Lot 0).
8. Define minimum `EXAM_READY` evidence (§9.3) — after question-bank design, not before.
9. Establish the GitHub Pages pipeline and accessibility baseline (§13).
10. Then Lot 0.5 Golden Slice.

## Atomic items affected

**None yet.** No atomic official item has been imported, specified or
implemented. Coverage is `0 / UNKNOWN`. The denominator is unavailable —
see Blocker B-1.

## Known issues

| ID | Issue | Severity | Status |
|---|---|---|---|
| **B-1** | `certification.symfony.com` is egress-blocked. Official syllabus + FAQ unreachable → coverage denominator undefined; verbatim import (§3.1) impossible. | CRITICAL | **Open — needs human** |
| **B-2** | Plan contradiction: §14 Lot 0.5 requires a PHP end-to-end slice; §13 requires static GitHub Pages (no PHP runtime). Irreversible architecture decision. | CRITICAL | **Open — needs human** |
| B-3 | GitHub Pages not enablable on an empty repo. | Minor | Resolved by the first push to `master` |
| B-4 | Plan §18 skills (`/research`, `/to-spec`, `/to-tickets`, `/implement`, `/tdd`) are not installed in this environment. | Minor | Accepted — native workflow used |
| B-5 | Plan pins Twig 3.22; Symfony 8.0 allows `^3.21\|^4.0`, Twig 3.x branch is at 3.29. Unverifiable without the syllabus. | Medium | Open — resolves with B-1 |
| V-3 | Branch `8.0` HEAD is `8.0.17-DEV`, ahead of released `v8.0.9`. Risk of documenting unreleased behaviour. | Medium | Mitigation: anchor to pinned SHA; prefer release tags for version-sensitive claims |

## Tests executed and actual results

**No tests were executed — no test suite exists.**
Only read-only inspection and network reachability probes were performed.
No functional code change was made (§20: "No functional code change is
allowed before this audit").

## Next action

Await the human decision on **B-1** and **B-2**.

- On B-1 resolved: begin the verbatim syllabus import and build the atomic matrix.
- On B-2 resolved: scaffold Lot 0 infrastructure against the approved architecture.
- Recommended B-2 resolution (pending approval): **PHP as build-time toolchain
  (canonical data, validation, coverage engine, static site generation, PHPUnit)
  + static HTML/CSS/JS runtime with `localStorage`**, deployed to GitHub Pages.

Work that is **not** blocked and may start the moment B-2 is approved:
schema definitions, ID policy, CI harness skeleton, Pages workflow,
accessibility baseline. These do not depend on B-1.

## Blocked decisions

1. **B-1 — Syllabus access.** Allow-list `certification.symfony.com` for this
   environment, or supply the official syllabus and FAQ text verbatim for
   import. Paraphrase is not acceptable (§3.1). The plan's §14 lot
   descriptions must **not** be used as a substitute — §3.1 forbids treating
   lot descriptions as the coverage denominator.
2. **B-2 — Target architecture.** Approve or amend the recommended
   build-time-PHP / static-runtime split described above.
