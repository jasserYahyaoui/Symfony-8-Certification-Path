# Lot 0 — Initial Audit Report (Master Plan §20)

**Date:** 2026-08-31
**Auditor:** Claude Code (Tech Lead role)
**Repository:** `jasserYahyaoui/Symfony-8-Certification-Path`
**Plan reference:** SYMFONY-8-CERTIFICATION-MASTER-PLAN-V2.md (v2.0)
**Decision:** `CONDITIONAL_GO`

> Scope of this document: audit only. Per §20, no functional code change was made.
> Only this report and session-continuity documentation are committed.

---

## 1. Repository state

| Check | Result | Evidence |
|---|---|---|
| Commits | **0** | `git log` → `does not have any commits yet` |
| Branches (local/remote) | **0** | `git ls-remote --heads origin` → empty; GitHub API `list_branches` → `[]` |
| Default branch | **Does not exist** | repository has never been initialised |
| Working tree | **Empty** (only `.git/`) | `ls -la` |
| `CLAUDE.md` | Absent | — |
| `AGENTS.md` | Absent | — |
| `CONTEXT.md` | Absent | — |
| `README.md` | Absent | — |
| `.github/` | Absent | — |

**Finding R-1:** The repository is fully greenfield. There is no legacy content, no
prior architecture and no migration burden. Nothing needs to be audited for
pre-existing debt because nothing exists.

### Installed Claude Code skills (§20.3, §18)

Skills present in this environment: `session-start-hook`, plus the synced
plugin set (`design`, `dataviz`, `artifact-*`, `code-review`, `simplify`,
`security-review`, `init`, `skill-creator`, `run`, `loop`, `pdf`, `pptx`,
`xlsx`, `claude-api`, `update-config`, `keybindings-help`,
`fewer-permission-prompts`, `import-memory`, `morning`).

**Finding R-2:** The skill sequence proposed in plan §18
(`/research > /to-spec > /to-tickets > /implement > /tdd > /code-review`)
is **not installable as written** — `/research`, `/to-spec`, `/to-tickets`,
`/implement` and `/tdd` are **not present** in this environment. Only
`/code-review`, `/simplify`, `/security-review` and `/init` map to real,
readable `SKILL.md` files. Per §18.3 ("do not invent behaviour"), the plan's
skill pipeline must be treated as **aspirational, not available**. Execution
will use the native workflow plus the four real skills above.

### Toolchain

| Tool | Version | Adequate for plan |
|---|---|---|
| PHP | 8.4.19 | Yes — Symfony 8.0 requires `>=8.4` (verified, see §10) |
| Composer | 2.8.12 | Yes |
| Node | 22.22.2 / npm 10.9.7 | Yes |
| Python | 3.11.15 | Yes (usable for the coverage engine / CI validators) |
| Git | 2.43.0 | Yes |

---

## 2. Architecture and canonical data

**Nothing exists.** None of the 15 canonical entities required by §11
(`OfficialTopic`, `OfficialItem`, `LearningOutcome`, `Course`, `Flashcard`,
`Question`, `Choice`, `Exercise`, `Source`, `Attempt`, `Session`,
`MasteryRecord`, `Weakness`, `MockExam`, `ExamBlueprint`, `ContentVersion`)
is defined. No schema, no versioning, no migration strategy, no
referential-integrity checking.

**Finding A-1 (blocking architecture contradiction).** The plan contains an
unresolved internal contradiction that must be settled before any code:

- §13 mandates *"a static GitHub Pages implementation"* with *"local browser
  storage for progress"* and *"no secrets in client code"*.
- §14 Lot 0.5 mandates *"a small end-to-end **PHP** slice"* including
  *"Practice Mode; Exam Mode … and deployment"*.

GitHub Pages serves **static files only** — it executes no PHP at request
time. A PHP-rendered Practice/Exam runtime and a GitHub Pages deployment
target are mutually exclusive as stated. This is an irreversible architecture
decision and therefore requires human approval under §15.

**Finding A-2.** §11 requires *"Persistent IDs must not depend on file names
or slugs."* No ID-minting policy exists yet; this must be specified in Lot 0
before the first syllabus item is written, because retrofitting IDs
invalidates every `*_refs` cross-reference in the matrix (§3.3).

---

## 3. Existing content

| Metric | Count |
|---|---|
| Courses | 0 |
| Flashcards | 0 |
| Questions (all pools) | 0 |
| Exercises / Labs / Source Tours | 0 |
| Mock exams | 0 |
| Syllabus files (§3.2, 5 required) | 0 of 5 |

None of the five mandatory syllabus files exist:
`docs/syllabus/official-syllabus.md`, `syllabus-matrix.yml`, `source-map.yml`,
`exclusions.yml`, `coverage-report.md`.

---

## 4. Existing tests and CI/CD

**Nothing exists.** No test suite, no CI workflow, no linting, no schema
validation. None of the 20 CI detections mandated by §12 are implemented.

**Finding T-1:** The §12 rule *"holdout question exposed in Practice Mode"*
and *"question depending on OUT_OF_SCOPE content"* are the two hardest
checks and both depend on a machine-readable syllabus + exclusion list. They
cannot be implemented until Blocker B-1 (below) is cleared.

---

## 5. Deployment

| Check | Result |
|---|---|
| GitHub Pages configured | **No** |
| Pages workflow present | **No** |
| Production URL | **None** |
| Can Pages be enabled today | **No** — Pages cannot be enabled on a repository with no branch and no content |

**Finding D-1:** The expected production URL will be
`https://jasseryahyaoui.github.io/Symfony-8-Certification-Path/`. It does not
exist yet. Per §16 and §19, no `DEPLOYED` claim may be made and no smoke test
can be reported until a real build publishes. This report makes no such claim.

---

## 6. Existing pedagogy

None. No content levels, no learning outcomes, no prerequisites graph, no
mastery model, no spaced-repetition algorithm. §6 requires the flashcard
review algorithm to be *"specified and tested in Lot 0"* — not started.
§9.3 requires Lot 0 to define minimum evidence for `EXAM_READY` — not started,
and explicitly must **not** be hard-coded before the question-bank design
(§9.3 final clause).

---

## 7. Atomic syllabus coverage

```text
EXAM_READY atomic official items / total atomic official items * 100
= 0 / UNKNOWN = UNDEFINED
```

**The denominator cannot currently be established.** See Blocker B-1.

Per §3.5, coverage may be computed **only** from atomic official items.
Per §3.1, *"Lot descriptions are operational groupings only. They are never
the coverage denominator and may not replace, merge, abbreviate or rename
official items."* Therefore the lot list in plan §14 — although it enumerates
topic areas in detail — **may not** be used to synthesise the syllabus. Doing
so would silently substitute an unofficial denominator and invalidate every
downstream coverage figure for the life of the project.

### Publicly confirmed exam constraints

`75 questions · 90 minutes · 15 topics · English · Symfony 8.0 only`

Corroborated by the Symfony blog announcement of the Symfony 8 certification
(via web search). These are consistent with plan §10 and may be labelled
`OFFICIAL_FORMAT`. **The 15 topic titles and their atomic items are not
confirmed** and must not be guessed.

---

## 8. Missing official items

**Cannot be enumerated.** Blocked by B-1. Formally: 100% of official items are
missing (0 implemented), but the itemised gap list is unavailable.

---

## 9. Out-of-scope contamination

**None — zero risk today.** The repository is empty, so no content violates
the §1.5 prohibited-expansion list (Symfony UX, Symfony AI, Doctrine, Monolog,
AssetMapper/Encore, PHP Polyfills, PHPUnit Bridge, ESI, Intl ICU utilities,
third-party bridges/transports, post-8.0 features).

**Finding C-1 (preventive):** `docs/syllabus/exclusions.yml` plus the
corresponding CI check must exist **before** the first content commit,
otherwise contamination is detected only retrospectively.

---

## 10. Version and source risks

### 10.1 Source reachability — measured, not assumed

Every mandatory source in §2.3 was probed from this environment.

| Mandatory source (§2.3) | Status | Note |
|---|---|---|
| `certification.symfony.com/exams/symfony.html` | ❌ **BLOCKED** (`EGRESS_BLOCKED`) | **Scope authority — critical** |
| `certification.symfony.com/faq.html` | ❌ **BLOCKED** | Scope authority |
| `symfony.com/doc/8.0/index.html` | ❌ blocked (whole domain) | Mitigated ↓ |
| `github.com/symfony/symfony-docs/tree/8.0` | ✅ **via raw + git clone** | HTML UI 403; raw/clone OK |
| `github.com/symfony/symfony/tree/8.0` | ✅ **via raw + git clone** | HTML UI 403; raw/clone OK |
| `www.php.net/manual/en/` | ❌ blocked | Mitigated ↓ |
| `www.rfc-editor.org/rfc/rfc9110` | ❌ blocked | Mitigated ↓ |
| Twig 3.x official documentation | ✅ **via raw** (`twigphp/Twig@3.x/doc`) | `twig.symfony.com` blocked |

**Available mitigations (all verified reachable, all SHA-anchorable):**

- Symfony 8.0 documentation → `raw.githubusercontent.com/symfony/symfony-docs/8.0/…`
  — this is the **upstream source of `symfony.com/doc/8.0`**, so it is equal or
  better evidence, and unlike the rendered site it carries a commit SHA.
- Symfony 8.0 source & tests → `raw.githubusercontent.com/symfony/symfony/8.0/…`
- PHP manual → `raw.githubusercontent.com/php/doc-en/master/…` (upstream of php.net)
- RFC 9110 → `raw.githubusercontent.com/httpwg/httpwg.github.io/master/specs/rfc9110.html`
- Twig docs → `raw.githubusercontent.com/twigphp/Twig/3.x/doc/…`

**§2.4 reproducible evidence is fully satisfiable** for all *technical*
authorities. Verified commit SHAs at audit time:

```yaml
symfony/symfony-docs:
  branch: "8.0"
  commit_sha: eea05cbfe063b9cf99afaf303b8cad76757f43bb
  verified_at: 2026-08-31
symfony/symfony:
  branch: "8.0"
  commit_sha: 6f841c00f41e5c037d40e1d739e2dc602c8f289d
  verified_at: 2026-08-31
```

### 10.2 Verified version facts

| Claim | Verified value | Evidence |
|---|---|---|
| Symfony 8.0 minimum PHP | `>=8.4` | `symfony/symfony@8.0/composer.json` |
| `Kernel::VERSION` on branch 8.0 | `8.0.17-DEV` | `symfony/symfony@8.0/src/Symfony/Component/HttpKernel/Kernel.php:75` |
| Latest released 8.0.x | `v8.0.9` | packagist `symfony/symfony` |
| Twig constraint in Symfony 8.0 | `^3.21\|^4.0` | `symfony/symfony@8.0/composer.json` |
| Twig 3.x branch current | `3.29.0` (dev) | `twigphp/Twig@3.x/CHANGELOG` |

**Finding V-1 (version risk).** The plan repeatedly fixes Twig at **3.22**
(§2.2, §2.3, §14 Lot 6). Symfony 8.0 actually allows `^3.21|^4.0`, and the
Twig 3.x line has advanced to 3.29. "Twig 3.22" is therefore **not** derivable
from Symfony 8.0 itself — it is presumably a syllabus statement. Until the
syllabus is readable this is `UNKNOWN_NEEDS_VERIFICATION` and **no Lot 6
content may be scored against a specific Twig minor version.**

**Finding V-2 (contamination risk).** Because `symfony.com/doc` is blocked,
there is no accidental route to `/current/` pages — the §2.3 prohibition
(*"Never use `/current/` as the primary source"*) is enforced by the network
itself. This is a risk that has been *reduced* by the environment.

**Finding V-3.** `Kernel::VERSION` on branch `8.0` reads `8.0.17-DEV`, i.e.
the branch is **ahead of the latest release (8.0.9)**. Content verified
against branch HEAD may reference behaviour not present in any released
8.0.x. Mitigation: anchor evidence to the pinned SHA above and prefer the
`v8.0.x` release tags when a behavioural claim is version-sensitive.

---

## 11. Technical and pedagogical debt

**Zero inherited debt** (empty repository). The entire cost is new
construction. The pertinent risk is not debt but **premature scaling**:
producing content before the schema, ID policy and coverage engine are frozen
would create rework proportional to the volume already written. Lot 0.5
(Golden Slice) exists precisely to prevent this and must not be skipped.

---

## 12. Revision burden and duplication

Not applicable — no content. Baseline is 0. The §1.4 net-value gate and the
§1.2 admission test must be encoded as review checklist + CI rules in Lot 0
so that burden is controlled from the first item rather than audited at
Lot 27.

---

## 13. Proposed Golden Slice (Lot 0.5)

The Golden Slice must exercise every layer end to end on the smallest possible
content surface. Proposal, pending B-1 (real official item wording) — the
*shape* is chosen now, the *items* are chosen once the syllabus is readable:

| Level | Candidate area | Why this level |
|---|---|---|
| `MINIMAL` | An HTTP fact (e.g. a status-code semantic) | Pure recognition; one source; one question |
| `STANDARD` | A Routing item (attribute vs YAML configuration) | Requires distinction + application; flashcard adds value |
| `DEEP` | A Security item (authenticator → passport → badge flow) | Structurally complex and demonstrably confused; justifies a Source Tour |

The `DEEP` choice is deliberate: Security is the area where the plan
(§18) itself flags "difficult Security or DI decisions", so exercising the
hardest case first validates the architecture against its worst load rather
than its easiest.

The slice must prove, with evidence: matrix → course → flashcard → single- and
multiple-answer questions → Practice Mode → Exam Mode → scoring → holdout
isolation → coverage report → CI green → Pages deploy → production smoke test.

---

## 14. Effort and dependencies per lot

Estimates are in agent-sessions and are **post-inspection but pre-syllabus**;
they carry the B-1 uncertainty and will be re-baselined once the atomic item
count is known.

| Lot | Scope | Est. | Hard dependency |
|---|---|---|---|
| 0 | Infra, schemas, coverage engine, CI, Pages, audit | 3–4 | **B-1, B-2** |
| 0.5 | Golden Slice (3 items, full vertical) | 2–3 | Lot 0 |
| 1 | PHP 8.4 foundations | 2–3 | Lot 0.5 approved |
| 2 | HTTP fundamentals | 2 | Lot 0.5 |
| 3 | Symfony Architecture | 3 | Lot 0.5 |
| 4 | Controllers | 2 | Lot 3 |
| 5 | Routing | 2 | Lot 3 |
| 6 | Twig | 2–3 | Lot 3, **V-1** |
| 7 | Forms | 3 | Lot 5, Lot 8 |
| 8 | Validation | 2 | Lot 3 |
| 9 | Dependency Injection | 3 | Lot 3 |
| 10 | Security | 3–4 | Lot 9 |
| 11 | Messenger | 2 | Lot 9 |
| 12 | Console | 2 | Lot 9 |
| 13 | Automated Tests | 2 | Lots 4–5 |
| 14 | Configuration & error handling | 2 | Lot 9 |
| 15 | Profiler & deployment | 1–2 | Lot 13 |
| 16 | i18n / l10n | 1–2 | Lot 6 |
| 17 | HTTP Caching | 1–2 | Lot 2 |
| 18–26 | 9 component lots (grouped delivery allowed, §14) | 4–6 total | Lot 3 |
| 27 | Final audit + 5 mock exams | 4–5 | All |

**Global effort (§15): ~52–66 agent-sessions.** Roughly 55% content
production, 25% question authoring and review, 20% infrastructure and audit.
Highest-variance items: Lot 10 (Security), Lot 27 (mock authoring), and the
whole plan if the atomic item count materially exceeds expectation.

---

## 15. Gates

| Gate | Status | Reason |
|---|---|---|
| Technical (§17) | **NOT ASSESSABLE** | No build, no tests, no deployment exists |
| Pedagogical (§17) | **NOT ASSESSABLE** | No content, and no syllabus to assess against |
| Accessibility (§13) | **NOT ASSESSABLE** | No UI exists |

No gate is claimed as passed. Per §16, no `DONE` / `COMPLETE` / `VALIDATED` /
`DEPLOYED` status is asserted anywhere in this report.

### Critical blockers (§17)

| ID | Blocker | Severity | Owner |
|---|---|---|---|
| **B-1** | Official syllabus + FAQ unreachable (`certification.symfony.com` egress-blocked). The **coverage denominator (§3.5) cannot be established**, the verbatim import (§3.1) cannot be performed, and the exclusions list (§3.2) cannot be authored. | **CRITICAL** | **Human** |
| **B-2** | Architecture contradiction: PHP runtime slice (§14 Lot 0.5) vs static GitHub Pages (§13). Irreversible decision → human approval required (§15). | **CRITICAL** | **Human** |
| B-3 | GitHub Pages cannot be enabled until a branch with content exists. | Minor | Resolves itself at first push |
| B-4 | Plan §18 skill pipeline (`/research`, `/to-spec`, `/to-tickets`, `/implement`, `/tdd`) is not installed. | Minor | Accepted — use native workflow |
| B-5 | Twig version pinned to 3.22 by the plan, unverifiable against Symfony 8.0 (`^3.21\|^4.0`). | Medium | Resolves with B-1 |

---

## 16. Compliance with §20 (audit preconditions)

| §20 step | Done |
|---|---|
| 1. Inspect the repository | ✅ |
| 2. Read `CLAUDE.md`, `AGENTS.md`, `CONTEXT.md` | ✅ (all absent — recorded) |
| 3. Inspect installed skills | ✅ (Finding R-2) |
| 4. Inspect architecture, content, tests, schemas, CI/CD | ✅ (all absent) |
| 5. Inspect GitHub Pages configuration | ✅ (none; §5) |
| 6. Fetch official syllabus and exclusions | ❌ **BLOCKED (B-1)** |
| 7. Build the atomic verbatim matrix | ❌ blocked by step 6 |
| 8. Compare existing content with atomic items | ⚠️ trivially empty on the content side |
| 9. Identify version/source/architecture/pedagogical risks | ✅ (§10, Findings A-1, A-2, V-1…V-3) |
| 10. Estimate each lot after inspection | ✅ (§14) |
| 11. Identify the Golden Slice | ✅ (§13, shape fixed / items pending B-1) |
| 12. Issue GO / CONDITIONAL_GO / NO_GO | ✅ (§17) |

Steps 6 and 7 are the only failures, and both stem from a single external
cause that is outside agent control.

---

## 17. Decision

```text
CONDITIONAL_GO
```

**Rationale.**

Not `GO`: the audit cannot complete steps 6–7 of §20. Proceeding to build
content without the official syllabus would mean inventing the coverage
denominator, which §3.1 explicitly forbids and which would corrupt every
coverage percentage for the entire project. That is precisely the failure
mode this plan is written to prevent.

Not `NO_GO`: the project is sound and the environment is capable. PHP 8.4,
Composer, Node and Git are present; every *technical* authority (Symfony 8.0
docs and source, PHP manual, RFC 9110, Twig docs) is reachable with
reproducible commit-SHA anchoring, which satisfies §2.4 in full. The
repository is greenfield with zero inherited debt and zero out-of-scope
contamination. Roughly 70% of Lot 0 — schemas, ID policy, coverage engine,
CI harness, Pages pipeline, accessibility baseline — is *structurally*
specifiable from the plan alone.

**Conditions to convert `CONDITIONAL_GO` → `GO`:**

1. **Clear B-1.** Provide the official Symfony 8 Certification syllabus and
   FAQ content verbatim — by allow-listing `certification.symfony.com` for
   this environment, or by supplying the text directly for import. Verbatim
   fidelity is mandatory (§3.1); paraphrase is not acceptable.
2. **Clear B-2.** Approve the target architecture (see recommendation below).

**Recommendation:** `PROCEED` — conditionally, on B-1 and B-2.

Recommended resolution for B-2: **PHP as build-time toolchain, static
front-end at runtime.** PHP 8.4 + Composer + PHPUnit own the canonical data,
schema validation, the coverage engine, the CI rules of §12 and the static
site generation; the deployed artefact is static HTML/CSS/JS with
`localStorage` persistence, satisfying §13 unchanged. This honours "an
end-to-end PHP slice" (the slice is genuinely PHP, and genuinely tested with
PHPUnit) while keeping GitHub Pages a valid deployment target, and it keeps
holdout-pool isolation enforceable at build time rather than trusting the
client.

---

## Remaining risks

- **B-1 dominates every downstream estimate.** The atomic item count is the
  project's true unit of work and is currently unknown; §14 estimates carry
  that uncertainty.
- Anchoring to branch `8.0` HEAD (`8.0.17-DEV`) rather than a release tag can
  introduce claims not present in any released 8.0.x (Finding V-3).
- The plan's §18 tooling assumptions do not match this environment (B-4);
  reported velocity should not assume that pipeline.
- No production URL exists; no deployment or smoke-test claim can be made
  until Lot 0 actually publishes (§16, §19).

---

## Evidence

- **Sources verified:** 8 reachability probes across all §2.3 mandatory
  sources; 2 commit SHAs resolved (`symfony-docs@8.0`, `symfony@8.0`);
  5 version facts verified against primary source files; shallow clone of
  `symfony-docs@8.0` performed and discarded.
- **Tests:** none executed — no test suite exists.
- **CI:** none — no workflow exists.
- **Commit:** this report.
- **Pull request:** none (direct-to-`master` per plan §0 and explicit user instruction).
- **Deployment URL:** none — GitHub Pages not enabled (B-3).
- **Production smoke test:** not performed — no production environment exists.

## Gates

- Technical: NOT ASSESSABLE
- Pedagogical: NOT ASSESSABLE
- Accessibility: NOT ASSESSABLE

## Status

`BLOCKED` on B-1 and B-2 — awaiting human decision, per §15.
