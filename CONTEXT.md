# CONTEXT.md — Session continuity (Master Plan §23)

**Last updated:** 2026-09-01

---

## Current lot

**Lot 10 — Security: PASS** (11 items written; the topic's twelfth was already
EXAM_READY). Lots 0 through 09 are complete. Next: **Lot 11 — Messenger**
(7 items).

The early-delivery notice on `CRS-6gc45etcssfb` has been **removed**: its three
declared prerequisites — Firewalls, Users, Providers — now exist, which is what
the Lot 10 definition of done required.

Before Lot 08, the six **Priority-1 corrections** of the mid-path professor
audit were applied and merged (PR #15, merge `fc9b84f`): the SPL hierarchy
diagram, the Twig `+` claim on strings (four occurrences, not three), the
`getPayload()` return type, the `kernel.request` short-circuit wording, three
cache directives that a VALIDATION question tested without teaching, and an
early-delivery notice on the orphan Security item. Priority 2 of that audit
remains **open and is due before the mock exams** — see *Remaining work*.

## Current branch

`master`, at merge commit `505eb58` (Lot 09), CI run `33553008230` and deploy
run `33553221047` both green, **production smoke-test job `100008013813`
success**. Lot 09 shipped through
[PR #18](https://github.com/jasserYahyaoui/Symfony-8-Certification-Path/pull/18).
Previously `ff25863` (Lot 08), CI run `33549262471` and deploy
run `33549471335` both green, **production smoke-test job `99995551340`
success**. Lot 08 shipped through
[PR #16](https://github.com/jasserYahyaoui/Symfony-8-Certification-Path/pull/16);
the audit's Priority-1 corrections through
[PR #15](https://github.com/jasserYahyaoui/Symfony-8-Certification-Path/pull/15)
(merge `fc9b84f`); the Form-events count fix through
[PR #14](https://github.com/jasserYahyaoui/Symfony-8-Certification-Path/pull/14)
(merge `2682415`). Earlier history: merge commit `0502775`. Lot 05 shipped through
[PR #5](https://github.com/jasserYahyaoui/Symfony-8-Certification-Path/pull/5),
whose first push failed CI on the site build (issue SITE-3) and was fixed in
`a06a14b`. Lot 04 shipped through
[PR #3](https://github.com/jasserYahyaoui/Symfony-8-Certification-Path/pull/3)
(merge `5dd75d7`) and its evidence through PR #4. Lot 03 shipped through
[PR #1](https://github.com/jasserYahyaoui/Symfony-8-Certification-Path/pull/1)
(merge `6a31ff5`) and its evidence through PR #2 (merge `4802990`).
Every lot from 03 onward uses branch → Pull Request → CI → controlled merge.

Lot 03 is the first lot shipped through the §15 workflow: dedicated branch →
Pull Request → CI → controlled merge into `master`. The direct-to-`master`
commits of Lots 0.5–02 remain a **DOCUMENTED_DEVIATION** recorded in their
reports; that history is not rewritten (CLAUDE.md, "Every lot ships through a
branch and a Pull Request").

## Completed work

### Audit (§20, 17 sections)

Full report: [`docs/reports/lot-00-audit-report.md`](docs/reports/lot-00-audit-report.md).
Repository confirmed greenfield; all §2.3 sources probed; commit SHAs resolved
for evidence anchoring; toolchain verified.

### Architecture decisions

- **[ADR-0001](docs/adr/0001-build-time-php-static-runtime.md)** — resolves the
  plan's PHP-vs-GitHub-Pages contradiction (B-2). PHP 8.4 at build time; the
  deployed artefact is static with `localStorage`. Decided by the project owner.
- **[ADR-0002](docs/adr/0002-persistent-identifiers.md)** — minted persistent
  ids, never derived from a file name or slug (§11).
- **[ADR-0003](docs/adr/0003-docusaurus-presentation-layer.md)** — Docusaurus
  3.10 renders the site, in `website/`. Amends ADR-0001's presentation layer
  only; the deployment model is unchanged.

### Implementation

- **Domain model:** 16 canonical entities of §11, typed enums for
  classification, content level, lifecycle status, pool, language, answer mode
  and verification status.
- **Schemas:** versioned registry + migration runner; a gap in the migration
  chain fails loudly.
- **Loaders:** matrix and question bank; every §3.3 / §7.1 field mandatory —
  a missing field is an error, never a default.
- **Coverage engine (§3.5):** counts an item only when `exam_ready`, lifecycle
  status *and* verification status agree. Reports UNDEFINED, not `0%`, when the
  denominator does not exist.
- **Validation (§12):** 17 mandatory rules, listed in `src/Validation/RuleSet.php`.
- **Review scheduler (§6):** deterministic, specified in
  [`docs/policy/review-algorithm.md`](docs/policy/review-algorithm.md), pinned
  by tests, with no efficacy claim attached.
- **Runtime (§9, §13):** Practice Mode (filters, weakness replay, answer hidden
  until submission) and Exam Mode (configurable timer, nothing revealed before
  final submission, timeout submits rather than discards). Holdout isolation is
  structural — the Practice payload is built from the learning pool alone.
- **Accessibility baseline (§13):** documented in
  [`docs/policy/accessibility-baseline.md`](docs/policy/accessibility-baseline.md).
- **CI/CD:** `ci.yml` (PHP gate + Node stage: `npm ci`, typecheck, site build,
  and assertions that no PHP and no holdout question reach the artefact) and
  `pages.yml` (build → deploy → production smoke test).
- **Site (ADR-0003):** Docusaurus 3.10 in `website/`. `website/docs/` and
  `website/static/data/` are generated by `php bin/cert build` and gitignored.
  Practice, Exam and progress management are React pages.
- **Canonical data:** `exclusions.yml` populated from §1.5; `source-map.yml`
  records every authority with its reachable route and commit SHA;
  `syllabus-matrix.yml` deliberately empty.

## Remaining work

1. ~~Import the official syllabus verbatim~~ — done for 10 of 15 topics;
   the head is still missing.
2. ~~Populate `syllabus-matrix.yml`~~ — 115 items imported at `NOT_STARTED`.
3. Generate `docs/syllabus/wording.lock.yml` so rule `SYL-002` becomes active.
4. Define minimum `EXAM_READY` evidence (§9.3) — after question-bank design, not before.
5. Lot 0.5 Golden Slice: one MINIMAL, one STANDARD, one justified DEEP item,
   end to end, then approve or correct the architecture before scaling.
6. ~~Lots 1–3~~ — done. ~~Lots 04–10~~ — done. Lots 11–27.
7. **Audit Priority 2, due before the mock exams.** P2.1 restore a standard
   `Pièges d'examen` section wherever a trap already exists in prose (~50
   courses, reorganisation only, growth < 2%); P2.2 rewrite the
   backward-compatibility HOLDOUT stem, 0.88 similar to its VALIDATION
   counterpart; P2.3 the `dump()`-in-prod error kind; P2.4 the positive
   constant-expression list in *Attributes*; P2.5 the question-language policy
   plus small FR→EN terminology blocks; P2.6 de-duplicate the upgrade paragraph
   between *Release management* and *Deprecations*; P2.7 normalise 14
   `cognitive_level` values; P2.8 restore accents on the Lot 07 flashcards.

## Atomic items affected

**163 imported, 119 EXAM_READY.**

```text
coverage = 88 / 163 = 53.99%
```

| Lot | Topic | Items |
|---|---|---:|
| 0.5 | Golden Slice (HTTP, Routing, Security) | 3 |
| 01 | PHP | 9 |
| 02 | HTTP (minus Status codes) | 9 |
| 03 | Symfony Architecture | 15 |
| 04 | Controllers | 14 |
| 05 | Routing (minus Configuration) | 11 |
| 06 | Templating with Twig | 14 |
| 07 | Forms | 13 |

Content: 88 courses (30 795 body words), 62 flashcards, 179 LEARNING, 66
VALIDATION and 15 HOLDOUT questions, 0 exercises. English share: 239/260.

Level distribution so far — **observation, not a target**: 60 `STANDARD`,
22 `MINIMAL`, 6 `DEEP`. Five lots, five shapes: 10/3/2, 8/5/1, 8/3/0, 9/4/1,
9/3/1.

Reports: [`lot-005`](docs/reports/lot-005-golden-slice-report.md),
[`lot-01`](docs/reports/lot-01-report.md),
[`lot-02`](docs/reports/lot-02-report.md),
[`lot-03`](docs/reports/lot-03-report.md),
[`lot-04`](docs/reports/lot-04-report.md),
[`lot-05`](docs/reports/lot-05-report.md),
[`lot-06`](docs/reports/lot-06-report.md),
[`lot-07`](docs/reports/lot-07-report.md).

### Cross-lot boundaries now load-bearing

Stated in prose only; no CI rule enforces them. Later lots must honour them:

- *Caching* (HTTP) = protocol headers → *HTTP Caching* (lot-17) = Symfony reverse proxy.
- *Language detection* (HTTP) = `Accept-Language` → *User's locale guessing*
  (lot-05) = route locale → *i18n* (lot-16) = translation.
- *Status codes* (Lot 0.5) → *HTTP response* owns the `isRedirect()` trap.
- *HttpFoundation component* (lot-03) = the component's place in the
  architecture → *HTTP request* (lot-02) owns the bag model and the
  `InputBag::get()` restriction → *The request* (lot-04) owns controller usage.
- *Request handling* (lot-03) = the `handle()` flow → *HttpKernel component*,
  *Argument value resolvers* and *Internal redirects* (lot-04).
- *Request handling* (lot-03) = the trajectory → *Event dispatcher and kernel
  events* (lot-03) = the dispatcher mechanics and the per-event powers.
- *Naming conventions* (lot-03) = framework-wide casing, prefixes and suffixes
  → *Naming conventions* (lot-04, same official wording, distinct item) =
  controller naming.

- *The response* (lot-04) = ce qu'un contrôleur retourne et avec quel raccourci
  → *HTTP response* (lot-02) owns the subclass catalogue and `isRedirect()`.
- *The cookies* (lot-04) = the request/response asymmetry → *Cookies* (lot-02)
  owns `SameSite`, `Secure`, `HttpOnly` and the deletion constraint.
- *HttpKernel component and FrameworkBundle* (lot-04) = the division of labour
  → *Request handling* (lot-03) owns the `handle()` flow.
- *Argument value resolvers* (lot-04) owns the resolver chain and the `Map…`
  attributes; lot-03's *Request handling* names them and defers.

The lot-03 report records a near-miss on the first of these: a draft reproduced
Lot 02's bag table **and** got `InputBag::get()` wrong by trusting the
documentation page over the source. Read the source for behavioural claims.

- *Trigger redirects* (lot-05) = redirects the routing layer triggers itself
  → *HTTP redirects* and *Built-in internal controllers* (lot-04).
- *User's locale guessing* (lot-05) = how a route sets the locale
  → *Language detection* (lot-02) owns `Accept-Language`; lot-16 owns
  translation.
- *Special internal routing attributes* (lot-05) = the reserved parameters
  → *Naming conventions* (lot-04) owns the `_controller` notation.
- *Restrict URL parameters* and *Set default values* (lot-05) teach the two
  options → *Configuration* (Golden Slice) only lists the option names.

Lot 04 is where the table paid off: three of its items landed at `MINIMAL`
because the boundary excluded most of their surface, and its average course
dropped to 329 body words from Lot 03's 397. Lot 05 fell further, to 286.

## Known issues

| ID | Issue | Severity | Status |
|---|---|---|---|
| ~~B-1~~ | Syllabus import. | — | **Closed** — complete PDF supplied 2026-09-01; 163/163 items imported and verified verbatim against the source |
| B-2 | PHP-vs-static-Pages contradiction. | — | **Resolved** by ADR-0001 |
| B-6 | GitHub Pages was not enabled on the repository. | — | **Resolved** — enabled by the owner; deploy and production smoke test both green |
| B-4 | Master Plan §18 skill pipeline (`/research`, `/to-spec`, `/to-tickets`, `/implement`, `/tdd`) is not installed here. | Minor | Accepted — native workflow used |
| B-5 | Examinable Twig version. | — | **Resolved** — the syllabus states "Twig syntax up to 3.22 version" verbatim. Lot 6 is scored against 3.22; later Twig features are out of scope. |
| V-3 | Branch `8.0` HEAD is `8.0.17-DEV`, ahead of released `v8.0.9`. | Medium | Mitigated — anchor to the pinned SHA; prefer release tags for version-sensitive claims |
| ENV-1 | `api.github.com` is blocked, so Composer cannot fetch dist archives in this container. Use `composer install --prefer-source`. CI is unaffected. | Minor | Workaround documented in `CLAUDE.md` |
| ENV-2 | `jasseryahyaoui.github.io` is egress-blocked from this container, so production cannot be verified from here. The smoke test therefore runs as a CI job on GitHub's runners. | Minor | By design |
| SITE-1 | Prism's `twig` language cannot be enabled: it assumes a global `Prism` the Docusaurus 3.10 SSR bundle does not provide. `php`, `yaml` and `bash` work. | Minor | Open — fence Twig samples as `html` in Lot 6 (ADR-0003) |
| SITE-2 | A bare `<` or `{` in a canonical matrix field broke the MDX build (`config/packages/<env>/`). | — | **Resolved** in Lot 03 — `DocsGenerator::mdxText()` escapes them; two regression tests pin the behaviour, authored course bodies stay exempt |
| SITE-3 | A bare `{` in a flashcard front or back broke the MDX build: `<details>`/`<summary>` is a JSX context and `htmlspecialchars` does not escape braces. Two Lot 05 cards quote route paths (`/{page}/blog`). | — | **Resolved** in Lot 05 — both escapings composed for that context, with a regression test asserting no generated `<summary>` carries a bare `{` |
| PROC-1 | A gate command piped through `tail` and chained with `&&` hides its failure: `&&` reads the exit status of the pipeline's last command. A failed site build was reported as SUCCESS in Lot 05, and the accessibility audit then ran against the previous lot's build directory. | Medium | **Resolved** — `npm run a11y` refuses a build older than its inputs, `composer gate-full` builds before auditing, and CLAUDE.md carries the rule |
| CRS-1 | `CRS-001` exempted every fenced code block, so moving a leaked answer into a fence silenced the rule while leaving the answer fully visible on the published page. Lot 05 did exactly that. | — | **Resolved** — the exemption is now scoped to the course's own item; another item's answer is a leak fence or not, with tests both ways |
| DOC-1 | The Symfony 8.0 documentation page for HttpFoundation describes `InputBag::get()` on an array parameter loosely; the source throws `BadRequestException`. A Lot 03 draft followed the page and was wrong. | Medium | Open — for any behavioural claim, read `symfony/symfony` at the pinned SHA, not the docs prose |
| CNT-1 | The Lot 07 *Form events* course claimed "six événements" in two places, and the matrix learning outcome and level justification repeated it. `FormEvents` declares five constants and five aliases; the course's own list enumerated five. The count came from the eight numbered steps, which interleaved the three transformations with the events. Caught by the owner reading the page. | Medium | **Resolved** — corrected in all four places against `symfony/symfony` 8.0 `FormEvents.php`, and the list now numbers the events only, with the transformations as indented lines. Lesson: a stated count is a factual claim and must be read off the source, never off the narrative around it |
| AUD-1 | Mid-path professor audit of all 88 EXAM_READY items: 2 BLOCKER and 5 MAJOR findings. Two courses taught a wrong fact (SPL hierarchy; Twig `'a' + 'b'` = 0, also drilled by a flashcard), one DEEP course contradicted its own VALIDATION question, one VALIDATION question tested three untaught cache directives, and a published Security item declared prerequisites that do not exist. | High | **Priority 1 resolved** in PR #15 (`fc9b84f`); Priority 2 open, due before the mock exams |
| AUD-2 | Grep during the P1 fix found a **fourth** `'a' + 'b'` occurrence the audit had missed (a distractor explanation), and the flashcard's `explanation` field described the logical operators rather than its own front and back. | Medium | **Resolved** — a textual sweep now accompanies every content correction; a green test suite does not prove an old wording is gone |
| AUD-3 | `QST-5pybfq9ra7ff` cited RFC 9110 for `Cache-Control`, which is RFC 9111, and for `stale-while-revalidate`, which is RFC 5861 and appears in neither. Verified: RFC 9111 contains `must-revalidate` 23 times and the other two zero times. | Medium | **Resolved** — citation corrected on both the course and the question |
| CRS-2 | `CRS-001` fired in Lot 08: the correct answer of `QST-mhm4eqjg10s2` reproduced the course's callback signature line verbatim. | — | **Resolved** — the **question** was rewritten to ask why the static form shifts its arguments; the course was untouched and nothing was moved into a fence |
| PR-1 | The first version of the Lot 08 pull-request description claimed all eight new courses carried a dedicated `Pièges d'examen` section. Six did. | Minor | **Resolved** in `0cde1d5` — the two remaining courses had their existing inline traps promoted into the standard section, which is also the P2.1 objective. Lesson: verify a claim about the artefact against the artefact before writing it down |
| DRAFT-1 | Two of my own Lot 09 drafts were wrong and only the source caught them: `decoration_priority` was described as putting the highest priority outermost (the documentation's own example compiles to `new Baz(new Bar(new Foo()))`, so the highest is **innermost**), and the env var processors were counted as twenty-two (`EnvVarProcessor::getProvidedTypes()` returns **twenty-one**). | Medium | **Resolved before commit** — both corrected against source. Third and fourth count/order error of the session: any stated count or ordering is verified against the code before it is written down, never against the narrative around it |
| CRS-3 | `CRS-001` fired three times in Lot 09, each because a correct answer reproduced a snippet also present in the course (an env expression, a decorator nesting, a factory line). | — | **Resolved** — all three **questions** were rewritten to test the mechanism rather than recall the literal string; no course was touched and nothing was moved into a fence |
| CRS-4 | `CRS-001` fired once in Lot 10: the correct answer of `QST-qzmh0dtgpccg` named `PasswordAuthenticatedUserInterface`, which the *Users* course also names. | — | **Resolved** — the **question** was rewritten to ask which method Symfony 8.0 removed from `UserInterface`, which tests the version-sensitive fact instead of an interface name. No course touched, nothing fenced |

## Tests executed and actual results

Locally, on PHP 8.4.19, on `lot-10-security`, every command run with
`set -o pipefail` and its exit code checked:

```text
composer validate --strict                                                      (exit 0)
php -l on src bin tests                                                         (exit 0)
vendor/bin/yaml-lint docs/syllabus content --parse-tags                         (exit 0)
php bin/cert validate           → 17 rules, 163 official items, 361 questions, no violations   (exit 0)
php bin/cert coverage           → Coverage: 73.01% (119/163 EXAM_READY)                        (exit 0)
vendor/bin/phpunit              → OK (82 tests, 836 assertions)                                (exit 0)
php bin/cert build              → docs tree + coverage.json, exam.json, practice.json          (exit 0)
npm --prefix website run typecheck → tsc --noEmit clean                                        (exit 0)
npm --prefix website run build  → [SUCCESS] Generated static files, onBrokenLinks: 'throw'     (exit 0)
npm --prefix website run a11y   → 6/6 surfaces PASS, TOTAL VIOLATIONS: 0                       (exit 0)
artefact                        → no server-side code; practice=LEARNING(245), exam=VALIDATION(95);
                                  21 holdout questions, none in either payload
```

`CRS-001` blocked the first draft once, on `TransformationFailedException` — a
correct answer the *Data transformers* course must name. Fixed by rewriting the
**question** to ask what the form does with that exception, which tests the more
useful fact. Nothing was moved into a fence.

Pools verified against the built payloads: `practice.json` = LEARNING (179),
`exam.json` = **VALIDATION** (66), no holdout question in either.

In CI on the merge commit `e3e091c`: run `33532924201` (CI) **success**, and run
`33532924208` (Deploy) **success** across build (`99940220219`), deploy
(`99940558830`) and the **production smoke test** (`99940629755`).
Production: https://jasseryahyaoui.github.io/Symfony-8-Certification-Path/

## Next action

**Lot 11 — Messenger** (7 items), in Master Plan §14 order. Dedicated branch,
Pull Request, CI, controlled merge, deploy, production smoke test, per §15.

Remaining after that: lot-12 Console (9), lot-13 Automated Tests (9), lot-14
Miscellaneous (3), then the fifteen single- and double-item lots 15–26, and
Lot 27 (final review and mock exams). Forty-four items remain.

**Working rule adopted at the owner's instruction:** no sleep, no polling loop,
no background process waiting on a resource. CI and deploy are checked **once**,
explicitly; if a job is still running it is reported as such rather than waited
out.

`POOL-002` stays in force: a lot is not finished until every `STANDARD` or
`DEEP` item carries a VALIDATION question.

The Lot 08 boundary held as planned and is now load-bearing: Lot 07's *Form
events* owns **when** validation runs (`POST_SUBMIT`); Lot 03's *Framework
overloading* owns the **bundle-level** merge; Lot 08 owns constraints, scopes,
groups, sequences, callbacks and the violations builder — and *PHP object
validation* names the Lot 03 boundary explicitly so the two merges are not
conflated. The forecast of "several MINIMAL items" was wrong: the observed
distribution is 7 STANDARD and 1 DEEP, because every item turned out to need a
distinction or an application rather than recognition alone.

Watch — the figure the learner pays:

- **revision burden**: 43 159 body words for 119 items, ~363 per item. Per-lot:
  376, 407, 397, 329, 286, 329, 341, 420, 415, **349**. Projection to 163 items
  ≈ 59 000 words, stable across three lots. Lot 10 came in lighter than 08 and
  09 because the Security topic splits into more, smaller items.
- **French questions**: 21 of 287, and the last four lots added none. The
  policy is now **decided** rather than drifting — P2.5 of the audit keeps
  English as the question language, since the exam is sat in English (§5), and
  replaces the idea of a French quota with small FR→EN terminology blocks in
  the most terminology-dense courses. Not yet applied.

## Blocked decisions

**Deferred, with a deadline:** holdout distribution — see
[ADR-0005](docs/adr/0005-holdout-distribution-deferred.md), amended by
[ADR-0006](docs/adr/0006-exam-mode-serves-the-validation-pool.md). The holdout
is **no longer deployed in any payload**: Exam Mode serves the `VALIDATION`
pool. But this repository is **public**, so holdout answers stay readable in
`content/questions/*.yml`. The exposure narrowed from *served by the
application* to *readable in the source*; §22's "protected unseen holdout
assessment" still cannot be claimed. Does not block content lots. **A decision
is required before Lot 27 begins.**

**Process:** Lots 0–02 were committed directly to `master` on the owner's
instruction — a documented deviation from §15, not an inapplicable step. Lot 03
was the first lot shipped as branch → Pull Request → CI → controlled merge, and
every later lot follows it.
