# ADR-0001 — PHP at build time, static site at runtime

- **Status:** Accepted
- **Date:** 2026-08-31
- **Deciders:** Project owner (architecture decision reserved to a human by Master Plan §15)
- **Supersedes:** nothing

## Context

The Master Plan contains a contradiction that had to be resolved before any
code was written, and which the Lot 0 audit recorded as blocker **B-2**:

- **§13** mandates *"a static GitHub Pages implementation"*, with progress in
  *"local browser storage"*, *"no required personal account"* and *"no secrets
  in client code"*.
- **§14, Lot 0.5** mandates *"a small end-to-end **PHP** slice"* containing
  *"Practice Mode; Exam Mode … tests and deployment"*.

GitHub Pages serves static files. It executes no PHP at request time. A
PHP-rendered Practice/Exam runtime and a GitHub Pages deployment target cannot
both be satisfied literally.

Three options were considered:

1. **PHP at build time, static site at runtime.**
2. A full Symfony application on PHP hosting, abandoning GitHub Pages.
3. A pure JavaScript project with no PHP at all.

## Decision

**Option 1.** PHP 8.4 owns everything that happens before deployment; the
deployed artefact is static HTML, CSS, JS and JSON.

Concretely:

| Layer | Technology | Responsibility |
|---|---|---|
| Canonical data | YAML under `docs/` and `content/` | The single source for every entity of §11 |
| Toolchain | PHP 8.4 + Composer, `bin/cert` | Schema validation, migrations, the §12 rule set, the coverage engine, site generation |
| Tests | PHPUnit | The technical gate of §17 |
| Runtime | Static HTML/CSS/JS + `localStorage` | Practice Mode, Exam Mode, progress, export/reset |
| Delivery | GitHub Actions → `actions/deploy-pages` | Build, deploy, production smoke test |

## Rationale

- It satisfies **§13 without amendment**. No server, no account, no secret in
  client code, progress in local storage.
- The **PHP slice of Lot 0.5 remains genuinely PHP** and genuinely tested: the
  domain model, the coverage engine and the validation rules are PHP classes
  under PHPUnit, not a thin wrapper around a JavaScript app.
- **Holdout isolation becomes structural rather than behavioural.** §7.3 and
  §17 treat holdout leakage as a critical blocker. Because the Practice payload
  is assembled at build time from the learning pool only, a holdout question is
  not hidden by the UI — it is absent from the file the Practice page fetches.
  A front-end bug cannot leak what was never shipped.
- Deployment from GitHub Actions needs no `gh-pages` branch and no `docs/`
  folder convention, so the repository layout stays driven by the plan rather
  than by Pages' constraints.
- Option 2 would have required paid hosting, deployment secrets and an
  amendment to §13. Option 3 would have contradicted §14 Lot 0.5 outright.

## Consequences

**Positive**

- Every §12 rule runs before anything is published; the Pages workflow re-runs
  the content gate rather than trusting CI.
- The production artefact is auditable: CI asserts that no PHP reaches `build/`.
- Zero hosting cost and zero runtime attack surface.

**Negative, and accepted**

- **Question data is inspectable by a determined learner.** Any static site
  ships its data to the browser, so a learner who opens the network tab can
  read `data/exam.json` — including the holdout pool used by the final mocks.
  This is inherent to static hosting, not to this design. It is accepted
  because the defeat is entirely self-inflicted: a learner who reads the answers
  invalidates only their own readiness evidence. It is documented here rather
  than silently ignored, and §9.3 mastery evidence remains the real signal.
  If holdout integrity ever needs to be enforced against the learner, that
  requires a server and a revision of this ADR.
- No server-side personalisation: every learner-specific behaviour must be
  computable in the browser from local storage.
- Content changes require a rebuild and redeploy; there is no CMS.

## Compliance notes

- §13 privacy: satisfied — local storage only, explicit reset, versioned
  JSON export.
- §16 deployment: satisfied — the Pages workflow ends in a real production
  smoke test against the live URL, so `DEPLOYED` is never claimed from
  intention alone.
