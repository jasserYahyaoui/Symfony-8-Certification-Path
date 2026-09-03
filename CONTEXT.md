# CONTEXT.md — Session continuity (Master Plan §23)

**Last updated:** 2026-09-03

---

## Current lot

**Content coverage is complete.** Lots 0 through 26 are **complete, merged
and deployed**. All **163/163 atomic official items are `EXAM_READY`
(100%)**; none remains `NOT_STARTED`, and no content reference is claimed by
two items.

Level distribution across the 163 items, stated as an **observation and never
a target**: 124 STANDARD, 28 MINIMAL, 11 DEEP.

**What remains is not content.** The pre-Lot-27 decision gate of 2026-09-03
cleared every blocker that stood in front of Lot 27:

- **ADR-0005 is `ACCEPTED`** (option 1). The restored §15 requires approval
  *only* for seven enumerated categories and option 1 is none of them; the
  owner independently named it as the recommended course. Options 2 and 3 stay
  unapproved — both amend ADR-0001.
- **P2.1 closed `NOT_REQUIRED`** on §4.3; **P2.5** measured `PASS` against §5
  with the policy now written; **P2.8** executed for the flashcards.
- **DOC-2 resolved** — §22, §15, §5 and §4.3 read verbatim and quoted into
  `docs/policy/`.

**Audit Priority 2 is closed.** P2.2, P2.3, P2.4, P2.6, P2.7 were fixed on
2026-09-03; P2.1 requires no work; P2.5 passes with one obligation carried to
Lot 27 unit 1, now delivered; P2.8 is done for the flashcards, with its matrix half
re-scoped as issue **FR-2**.

**Lot 27 is UNBLOCKED.** Two items travel with it rather than blocking it: the
§5 glossary — **delivered 2026-09-03 as Lot 27 unit 1** — and **FR-2**, now
reclassified `REQUIRED_BEFORE_FINAL_READINESS` rather than optional, to be done
as one atomic job: a partial pass would destroy the uniform
zero-accented-character signal that locates the affected strings. Neither is a
§22 clause — see
[`docs/policy/final-readiness.md`](docs/policy/final-readiness.md), which
assesses all nine clauses against measured state.

## Current branch

`master`, at `5d8a4f8` — **Lot 27 unit 1 (the §5 glossary) delivered in full**:
PR #46, Technical gate `100587591300` success on `1a6716c`, merged, deploy run
`33737464109` with build `100591307991`, deploy `100591639720` and production
smoke test `100591698010`, all success.

The smoke log was read, not assumed. Eleven URLs at 200 including the new
`/docs/syllabus/glossary`, and the **new** holdout check ran in production for
the first time:

```text
ok  practice  334 questions, all LEARNING, no holdout id or choice
ok  exam      135 questions, all VALIDATION, no holdout id or choice
checked against 27 holdout questions and 108 holdout choices
```

That is the first time holdout isolation has been proved against the deployed
bytes rather than against the payload's own `pool` label.

Before it, `7456e20` — the squashed corrections PR #44, **delivered in full**:
Technical gate `100542490813` success on head `46b0df4`, merged, deploy run
`33722133013` with build `100543284846`, deploy `100543519869` and production
smoke test `100543569797`, all success. The smoke-test log was read rather than
assumed: ten production URLs returned 200, the landing page rendered its
expected content, and `practice.json` declared `pool: LEARNING`.

`c4cfebc` before it is the squashed pre-Lot-27 decision gate (PR #43, Technical
gate `100527192838` success on `eda6f50`, deploy `33719653086`). It carries the
restored Master Plan findings, the ADR-0005 acceptance, the §5 and §4.3
policies, the §22 assessment, the P2.8 flashcard repair and the versioned
syllabus-audit artifacts.

### Pre-Lot-27 corrections G-1 to G-4 (PR #44)

Applied from the accepted audit, and nothing else — five files, coverage
100% (163/163) before and after, pools untouched.

| | Correction | Evidence it closed |
|---|---|---|
| G-1 | `SCOPE-001` now reads each choice's own `explanation`, not only its text | The extended rule reported **exactly one** violation — `QST-psqn0fe95khc`, the occurrence the audit predicted from separate evidence. Regression test added and **confirmed to fail against the pre-fix rule** |
| G-2 | The Doctrine reference **removed** from `QST-psqn0fe95khc`, the explanation rewritten around the namespace segment, and the `exclusion-note` tag dropped | Tagging would have made the tag a general exemption for keeping an out-of-scope anecdote that earned no point and taught nothing. The question now passes on its content: **zero** excluded terms anywhere in it, no tag needed. Two guards added — a genuine tagged note must actually state a boundary, and this question must carry no excluded term |
| G-3 | `exclusions.yml` `review_only_exclusions` de-duplicated | Was 6 entries / 3 ids, and the two `EXC-UNLISTED-COMPONENTS` copies disagreed — the second omitted `HttpFoundation`, `HttpKernel`, `HttpClient`, `OptionsResolver`. Now 3 entries, 3 ids, one 32-entry list |
| G-4 | `official-syllabus.md` records **three facts together**: the published constraint (**15 topics**, binding), the measured presentation (**14** headings at 17.0) and the mapping (**163/163**) | The typography measures how the page renders, never what it states. `Components:` as the fifteenth topic stays an **interpretation** — it is the only item-level line with children, but carries no 17.0 heading. §22 clause 2 is unaffected: it turns on item representation, and all 163 are present |

**G-1 is the fourth instance of one class**, after SPLICE-1, SPLICE-2 and
COG-1: **a rule that does not read a field cannot protect it.** `Choice::$explanation`
existed, was required by §7.1, was rendered to the learner, and no rule had
ever looked at it.

The Technical gate that counts is run `33653840108` on head `610e320`:
**success**. Run `33653775788` on `efaf14c` shows `cancelled` — superseded by
the next push, concurrency cancellation, not a failure. Run `33622202938` on
`8bf0758` succeeded but predates the audit trim and does not stand for the
merged tree.

Deploy run `33656601994` on `7c89adc`: success. A CONTEXT.md commit then moved
master to `8029875` and deploy run `33656689995` succeeded on it — that is the
live deploy, and its jobs are the recorded evidence: build `100337182656`,
deploy `100337538061`, production smoke test `100337628436`, all success.

`master` is at merge commit `da87b7e` (Lot 20, PR #30).

Verified, with real identifiers:

| Lot | Merge | Technical gate | Deploy run | Production smoke test |
|---|---|---|---|---|
| 12 — Console | `0fc51e1` | `100130374274` | `33594048974` | `100133956362` |
| 21 — Filesystem, Finder | `7c89adc` | `33653840108` | `33656689995` | `100337628436` |
| 22 — Mailer, Mime | `25fe900` | `33659872155` | `33660554878` | `100350157086` |
| 23 — Process | `df88caa` | `33661598698` | `33685599410` | `100433156037` |
| 24 — PropertyAccess | `2a8f0e8` | `33686497465` | `33686874047` | `100437028877` |
| 25 — Runtime | `130f7e8` | `33687839072` | `33688747560` | `100442702932` |
| 26 — Serializer | `59b5756` | `33689350805` | `33689975817` | `100446567044` |
| Pre-Lot-27 gate (PR #43) | `c4cfebc` | `100527192838` | `33719653086` | — |
| Corrections G-1 to G-4 (PR #44) | `7456e20` | `100542490813` | `33722133013` | `100543569797` |
| CONTEXT delivery evidence (PR #45) | `90f990e` | `100580157989` | `33734587613` | `100582461633` |
| Lot 27 unit 1 — §5 glossary (PR #46) | `5d8a4f8` | `100587591300` | `33737464109` | `100591698010` |
| 13 — Automated Tests | `4b89c97` | `100136839343` | `33595866117` | `100139317182` |
| 14 — Config/Errors/Debug | `f3f6212` | `100140245517` | `33596413331` | `100140900164` |
| 15 — Deploy/Profiler | `5356666` | `100141336955` | `33596821306` | `100142110861` |
| 16 — i18n | `35b550c` | `100142369730` | `33597624000` | `100144456820` |
| 17 — HTTP Caching | `db40346` | `100145067830` | `33598597355` | `100147381182` |
| 18 — Cache | `301ab8b` | `100147813907` | `33599377684` | `100149755378` |
| 19 — Clock | `8f36d0b` | run `33618683916` | `33619375464` | `100212925165` |
| 20 — EventDispatcher, Event | `da87b7e` | `100216057848` | `33621145565` | `100218509654` |

Lot 20's deploy run also carries build `100218191780` and deploy
`100218447960`, both success.

The deploy and smoke ids for lots 14 to 17, long recorded as `MISSING` because
they were not captured at the time, were **recovered from the Actions history
during the pre-Lot-27 audit** and are now in the table above. They were read
from the API, not reconstructed: each smoke-test job is a real id whose run
carries the matching `head_sha`. Nothing was back-filled by inference.
Production: https://jasseryahyaoui.github.io/Symfony-8-Certification-Path/

Every lot from 03 onward ships through branch → Pull Request → CI → controlled
merge. The direct-to-`master` commits of Lots 0.5–02 remain a
**DOCUMENTED_DEVIATION** recorded in their reports.

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
6. ~~Lots 1–3~~ — done. ~~Lots 04–16~~ — done. ~~Lot 17~~ — delivered. Lots 18–27.
7. **Audit Priority 2 — audited 2026-09-03, four of eight closed.** Every
   criterion was re-read from this file rather than assumed, and two of the
   recorded estimates proved wrong when measured.

   | | Criterion as recorded | Audited result |
   |---|---|---|
   | P2.1 | standard `Pièges d'examen` section wherever a trap exists in prose (~50 courses) | **CLOSED — NOT_REQUIRED.** Re-measured against the restored §4.3: 93 verbatim, **8** under a descriptive heading, 62 with none. §4.3 says *"Use only relevant sections, **not a mandatory empty template**"*, so all three groups are compliant and the audit item's premise does not hold. Recorded in [`docs/policy/course-structure.md`](docs/policy/course-structure.md). |
   | P2.2 | rewrite the backward-compatibility HOLDOUT stem, 0.88 similar to its VALIDATION counterpart | **PASS — fixed.** Measured at exactly 0.88. `QST-0jd9nbbaqczb` rewritten around the security-fix tolerance, a facet the course teaches and nothing else assessed. Similarity now **0.22**. |
   | P2.3 | the `dump()`-in-prod error kind | **PASS — fixed.** The course called it "une erreur fatale"; its cited source, `components/var_dumper.rst`, says nothing about production. The sourced half (dev dependency, `composer require --dev`) is kept and the unsourced error-kind claim is replaced by what the source supports. |
   | P2.4 | the positive constant-expression list in *Attributes* | **PASS — fixed.** `php/doc-en` states it positively: *"Arguments can only be literal values or constant expressions."* The course stated only the negative; it now gives the positive list first. |
   | P2.5 | question-language policy plus small FR→EN terminology blocks | **PARTIAL — policy written, one gap open.** The restored §5 settles it: French is *permitted*. Measured against §5 — advanced (`hard`) questions **201/202 = 99.5%** English (threshold 50%), `VALIDATION` **135/135**, `HOLDOUT` **27/27**, and all 21 French questions are `LEARNING`, which §5 allows. No translation is required or permitted. The policy is now written: [`docs/policy/language-policy.md`](docs/policy/language-policy.md). **Closed 2026-09-03** — §5's *French-to-English certification glossary* now exists: 81 entries in `docs/syllabus/glossary.yml`, rendered at `/docs/syllabus/glossary`, delivered as Lot 27 unit 1 (PR #46). **P2.5 is fully closed.** |
   | P2.6 | de-duplicate the upgrade paragraph between *Release management* and *Deprecations* | **PASS — already resolved.** Verified: the two courses now share no prose line over 45 characters, and `UPGRADE`/`CHANGELOG` appears 4 times in *Deprecations* and 0 in *Release management*. Closed incidentally by the Lot 13 `CRS-001` rewrite. |
   | P2.7 | normalise 14 `cognitive_level` values | **PASS — fixed, and larger than recorded.** Measured **39**, not 14. In 38 of them the field simply duplicated `exam_skill`. Root cause: `cognitive_level` was an unconstrained `string` that no rule inspected. All 39 reassigned; rule **`COG-001`** added so it cannot recur. |
   | P2.8 | restore accents on the flashcards of lots 07–11 and the matrix justifications of that period (FR-1) | **PARTIAL — flashcards fixed; matrix half re-scoped and open.** *Flashcards:* the five banks of lots 07–11 are repaired — **157 prose fields** across 47 cards, 0 non-prose fields touched, accented-character counts now 51–109 against 58–130 for the always-clean banks. *Matrix:* the recorded scope was wrong. The defect is **lots 01–11**, not "that period": all eleven carry **zero** accented characters in `content_level_justification` and `learning_outcomes`, and lot 12 onward is clean (59, 55, 23, 14, …). See issue **FR-2**. |

   **What the restored Master Plan changed.** P2.1, P2.5 and P2.8 were held on
   2026-09-03 for want of a decision. Two of the three needed no decision at
   all — they needed the plan text, which was unreadable at the time:

   - **P2.1 — the premise was wrong.** §4.3 introduces the section list with
     *"Use only relevant sections, not a mandatory empty template"*. Adding a
     `Pièges d'examen` heading to a course with no trap is the empty template
     §4.3 forbids, and renaming the 8 descriptive headings is cosmetic churn
     that §1.4's net-value gate rejects. Closed, no content changed.
   - **P2.5 — §5 permits French.** The audit item assumed a policy would
     require converting the 21 French questions. §5 says the opposite:
     *"beginner practice may be in French"*, with thresholds that bind only
     advanced questions and Mocks 3–4 — all of which this corpus already
     exceeds. The policy is written; the only real §5 gap is the missing
     glossary.
   - **P2.8 — executed for the flashcards, re-scoped for the matrix.** The
     flashcard half was recorded accurately and is done. The matrix half was
     not: the defect covers lots 01–11, not lots 07–11, and it reaches rendered
     pages (`DocsGenerator` emits both fields onto every item page). Tracked as
     **FR-2** rather than folded into a decision gate.

## Atomic items affected

Reconciled from `docs/syllabus/syllabus-matrix.yml` and `content/**` with a
script, not from an earlier report.

**163 imported, 163 EXAM_READY.**

```text
coverage = EXAM_READY atomic official items / total atomic official items * 100
         = 157 / 163 = 96.32%
```

**Cumulative content:** 157 courses (62246 body words, YAML front matter
excluded), 131 flashcards, 478 questions — 322 LEARNING,
129 VALIDATION, 27 HOLDOUT.

**Level distribution — observation, not a target:** 118 `STANDARD`,
28 `MINIMAL`, 11 `DEEP`.

**Still to do — 6 items, all Miscellaneous:**

| Lot | Items |
|---|---|
| lot-22 | Mailer; Mime |
| lot-23 | Process |
| lot-24 | PropertyAccess |
| lot-25 | Runtime |
| lot-26 | Serializer |

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
| SCOPE-1 | Two Lot 11 questions were rejected by `SCOPE-001`: one used `doctrine://default` as a distractor and one asked why a **Doctrine entity** should not travel in a message. Doctrine is an excluded topic (§1.5), so neither may be the subject of a scored question. | Medium | **Resolved** — the distractor was replaced by a generic queue-backed transport, and the question was rewritten to ask why a message carries an **identifier rather than the loaded object**, which teaches the same serialisation fact without leaving the exam scope. The rule caught a scope drift I had not noticed while writing |
| CNT-2 | The Lot 11 *Events* course first claimed **seven** Messenger events. The documented list has **ten** — my own grep pattern had missed `MessageSentToTransportsEvent`, `WorkerMessageRetriedEvent` and `WorkerRateLimitedEvent`. | Medium | **Resolved before commit** — corrected by reading the source list rather than trusting my filter, and the course now teaches the *shape* of the catalogue rather than the bare count. Fifth count error of the session; the DRAFT-1 rule held, but only because the check was actually performed |
| FR-1 | French accents are missing throughout the flashcard banks of lots **07 to 11** (`traite` for `traité`, `resultat` for `résultat`, `facon` for `façon`) and throughout the matrix `content_level_justification` and `learning_outcomes` written over the same period. A line count confirms it: lots 01–06 carry 36 to 58 accented lines each, lots 07–11 carry 1 to 5. The cause is my own generator scripts, which were written in unaccented French to sidestep encoding trouble. No automated rule detects it, and no gate fails. | Medium | **Flashcard half resolved 2026-09-03; matrix half re-scoped as FR-2.** The five banks of lots 07–11 are repaired: 157 prose fields across 47 cards, 0 non-prose fields changed, accented-character counts now 51–109 against 58–130 for the always-clean banks; 18 rules and 85 tests green afterwards. Method: a context-free accent table applied by script (no judgement, backticked code spans excluded), then 97 context-sensitive occurrences (`a`/`à`, `ou`/`où`, participles) corrected one at a time by reading them, each guarded by an assertion before and after. That pass also caught **one over-correction the table itself introduced** — `oriente` is the present-tense verb and carries no accent — which is the reason the table is deliberately restricted to forms that are accented in every French sentence. Lesson: a generator script is content, and shortcuts taken inside it reach the learner |
| FR-2 | The matrix half of FR-1 was **mis-scoped**. `content_level_justification` and `learning_outcomes` carry **zero** accented characters for **lots 01 to 11** — not lots 07–11 — and lot 12 onward is clean (59, 55, 23, 14, 5, 10, 7, 7, 17, 27, 34, 24, 23, 23, 26). Measured scope: **126 items, 608 strings**; a probe of 46 common words finds 206 occurrences in 88 items, and the true count is higher because the probe is not exhaustive. Both fields are rendered onto every item page by `DocsGenerator` (lines 394 and 399), so this reaches the learner on 126 of 163 pages. | Medium | **Open, deliberately not executed in the decision gate.** Repairing it is the same job just done for the flashcards at roughly 13× the volume: ~386 occurrences fall to the context-free table, leaving ~280 candidate context-sensitive occurrences to read individually. It was **not** done as a partial pass, and that is the considered choice rather than the convenient one: the defect is detectable today only because the accented-character count is uniformly **zero** across lots 01–11, and a partial pass would destroy that signal while leaving errors behind, with no cheap way to find the remainder. It is done completely, as one scheduled job, or not at all. No gate detects it and none fails. |
| CRS-5 | `CRS-001` fired twice on Lot 13, and both were content faults rather than rule noise. (a) The *Handling legacy deprecated code* course restated nearly all of *Deprecations best practices* (lot-03) — the two markers, the mineure/majeure calendar, the CHANGELOG and UPGRADE trace — and so reproduced that item's correct answer. (b) The *Request and response objects introspection* course used `$response->getStatusCode()`, which is the correct answer to a lot-02 HttpClient question. | Medium | **Resolved** — (a) the course was rewritten around what its item actually owns (a silenced `E_USER_DEPRECATED` notice: nothing fails, nothing prints, it exists only if an error handler collects it), with the lot-03 boundary stated on the page; its flashcard and its two LEARNING questions were realigned so nothing is asked that the course no longer teaches. (b) the example now shows headers and content. Neither was fixed by rewriting a validated question or by fencing. The rule caught duplication that the §1.4 value gate should have caught first |
| SPLICE-1 | The matrix splicer hard-coded the default `exclusion_boundaries` line. Three Lot 13 items carry `"PHPUnit Bridge is not included."` instead, so the splice would have silently replaced the syllabus's own scope note with the default text. | High | **Resolved before any data was lost** — the script's own guard refused to run rather than writing a near-match. It now matches that line with a regex and writes it back unchanged. Lesson: a splicer that assumes a constant template will corrupt the first record that differs, and only an assertion makes that visible |
| SPLICE-2 | The Lot 22 matrix splice wrote the level and outcomes but not the tails, and my fallback positional patch — whose **own guard was faulty** — then wrote **Mailer's** references into the **Serializer** item and **Mime's** into the **Runtime** item, in addition to the correct ones. Ten references ended up claimed by two items each. | High | **Resolved in Lot 25.** No gate caught it: `REF-001` checked that references *resolve*, never that they *belong*, and both wrongly credited items were `NOT_STARTED`, which no readiness rule inspects. The corruption reached `master` in Lot 22 and survived lots 23 and 24; it surfaced only because Lot 25 tried to splice Runtime and found the slot occupied. A full audit of every course, flashcard and question reference found exactly those ten duplicates and no others. `REF-001` now reports a reference claimed by two items **and** a reference whose content declares a different owning item — the sharper invariant, since content records its own item — pinned by two regression tests confirmed to fail against the previous rule. Lesson, and the second of its kind after SPLICE-1: **an assertion that is itself wrong protects nothing**, and a positional patch must be verified against the block it claims to have edited, not against its own success message. |
| COG-1 | `cognitive_level` was declared as a bare `string` that **no rule inspected**, so 39 questions written before the taxonomy settled carried an *exam skill* value in the *level* field — `DIAGNOSE` (30), `DISTINGUISH` (8), `RECOGNIZE` (1). In 38 of the 39 the two fields held the same word. Every gate passed for the whole life of the project. | Medium | **Resolved 2026-09-03** — rule `COG-001` constrains the field to `KNOW`, `UNDERSTAND`, `APPLY`; it reported exactly 39 before the fix, matching the audit count independently. All 39 reassigned under a stated mapping (symptom scenario → `APPLY`; contrast or multi-statement → `UNDERSTAND`; single recalled fact → `KNOW`), and a regression test pins the historical shape. Third instance of the same class after SPLICE-1 and SPLICE-2: **an invariant nothing checks is not an invariant.** |
| DOC-2 | The Master Plan (`SYMFONY-8-CERTIFICATION-MASTER-PLAN-V2.md`) is **not in the repository** — a filesystem-wide search finds no copy. §15 and §22 are therefore cited throughout but cannot be read verbatim from any artefact here. §15's triggers survive as an enumeration in `CLAUDE.md`; §22 survives only as the seven-word fragment *"protected unseen holdout assessment"* quoted inside ADR-0005. | Medium | **Resolved 2026-09-03** — the owner supplied the Master Plan and §15, §22, §5 and §4.3 were read verbatim. The consequences were not cosmetic: §22 is now quoted in full in [`docs/policy/final-readiness.md`](docs/policy/final-readiness.md) and assessed clause by clause; §15's approval list turns out to be **exhaustive** (*"Human approval is required **only** for…"*), which is narrower than the reading ADR-0005 had been held under; §4.3 and §5 respectively closed audit items P2.1 and P2.5 without a line of content changing. Lesson: three findings were held pending a human decision that the plan text had already made — an unreadable specification manufactures blockers. The plan is still not committed to the repository; the four sections that govern day-to-day work are now quoted verbatim inside `docs/policy/`. |
| API-1 | The GitHub Actions view **lags by several minutes**, and no endpoint avoids it. Lot 25 added a new shape: the *filtered* run listings (by `status`, or by `status` plus `actor`) reported no deploy at all for `130f7e8` while the **unfiltered** listing already showed it completed and successful, so a filter can hide a run that exists rather than merely delay its status. On Lot 14 the PR check-run endpoint reported `in_progress` for six minutes after the job had finished; on Lot 16, four; on Lot 17 the job completed at 06:15:35 and `list_workflow_jobs` with `filter: latest` still showed step 17 running at 06:19 and again at 06:21. | Low | **Open, mitigated by discipline — not by a parameter.** An earlier version of this row claimed `filter: latest` resolved it; that claim was wrong and is withdrawn. The rule is: one lagging read proves nothing, so never conclude from a single check that a job is stuck, and never report a lot `PASS` or `BLOCKED` on one read — re-check at the next check-in. The real risk is not waiting for nothing; it is announcing a state the build does not have |

## Tests executed and actual results

Locally, on PHP 8.4.19, on `lot-26-serializer`, every command run as its own
command with `set -o pipefail` and its exit code read:

```text
php bin/cert validate           → 17 rules, 163 official items, 496 questions, no violations   (exit 0)
php bin/cert coverage           → Coverage: 100% (163/163 EXAM_READY)                          (exit 0)
vendor/bin/phpunit              → OK (84 tests, 886 assertions)                                (exit 0)
php bin/cert build              → docs tree + coverage.json, exam.json, practice.json          (exit 0)
npm --prefix website run build  → [SUCCESS] Generated static files                             (exit 0)
npm --prefix website run a11y   → 6/6 surfaces PASS, TOTAL VIOLATIONS: 0                       (exit 0)
```

Pools verified against the built payloads: the 2 LEARNING questions are in
`practice.json` (334) and the 1 VALIDATION question in `exam.json` (135), with
no wrong-pool leak and no HOLDOUT question published. That is **functional
isolation**, not confidentiality: both published payloads carry correct
answers.

`CRS-001` has not fired since Lot 13. Lot 24 is the clearest case of why: the
accessor-resolution conventions are the correct answer of the lot-07
VALIDATION question `QST-3pfgr2whbm74`, the collision was found by grepping
the banks **before** drafting, and it was resolved by scoping the course to
what PropertyAccess uniquely owns — never by fencing the string. The discipline that changed is writing the
boundary into the course before drafting rather than discovering it from the
rule afterwards.

**In CI:** Lot 26's Technical gate is run `33689350805` on head `4df9ced`
(success). Deploy run `33689975817` on `59b5756` succeeded in all three jobs,
and the production smoke-test log was read rather than assumed: ten production
URLs at 200, landing page rendered, `practice.json` declaring pool `LEARNING`.

The smoke test does not fetch individual course pages by name, so no lot's
course pages are separately evidenced in production; ENV-2 blocks fetching them
from this container.

**Pedagogical sufficiency audit (six named details).** Four are tied to a
learning outcome and carry an assessment: `dumpFile()` atomicity (Filesystem
outcome 2, flashcard + VALIDATION `QST-at67jvy54v5b`); Finder statefulness and
cloning (Finder outcome 2, flashcard + LEARNING `QST-9vdyrx07zz9j`); the default
without `files()`/`directories()` (Finder outcome 3, LEARNING
`QST-5a5z84vv3kvc`); `iterator_to_array(..., false)` (VALIDATION
`QST-y8xczgk8cn9z`, whose outcome was missing and has been added). Two carried
neither outcome nor assessment while being asserted as exam traps —
`followLinks()` and `sortByName()` ordering — and were shortened to a single
default-behaviour clause and a code comment respectively. Finder course
593 → 529 body words.

## Next action

**Run the question-bank audit — Lot 27 unit 2.** Unit 1 (the §5 glossary) is
delivered; the audit covers answer correctness, ambiguity, distractors,
duplicates and near-duplicates, mappings, languages, estimated times and pool
isolation across the 496 questions. Read-only first: propose corrections before
applying any.

Lot 27's full scope
fixed by §14: independent syllabus audit; version-contamination audit; source
and anchor audit; content-volume and duplication audit; question-bank audit;
holdout integrity audit; English readiness audit; mock exams; technical,
accessibility and production audit; final rationality and readiness assessment.

Three things must travel with it and must not be quietly dropped:

1. **The §22 assessment is a conjunction, not a score.** Close Lot 27 against
   [`docs/policy/final-readiness.md`](docs/policy/final-readiness.md) clause by
   clause. §22's own last line forbids a good figure compensating for a blocker.
2. **Clause 2 cannot be self-certified.** Coverage is 100% against the
   *imported* syllabus, and blocker **B-1** means nothing here can prove the
   import matches the official source. The independent syllabus audit needs a
   human-supplied copy of the official syllabus to be worth anything. **Ask for
   it before running that audit, not after.**
3. **The §5 glossary exists** since 2026-09-03 (Lot 27 unit 1): 81 entries,
   rendered and smoke-tested. The English readiness audit now checks it rather
   than writing it.

**FR-2** — the matrix accents for lots 01–11 — is open,
`REQUIRED_BEFORE_FINAL_READINESS`, is not a §22 clause,
and is not Lot 27's business. Schedule it as its own job, and do it completely
or not at all: see the issue row for why a partial pass is worse than none.

Mock 4 is 75 questions, 90 minutes, 100% English, drawn from the holdout pool
(§10). The pool holds exactly **27** questions across 27 items, none with more
than one, and ADR-0005 forbids redistributing it without a demonstrated need —
so how 75 slots are filled from 27 questions is Lot 27's first real design
question, and it must be answered before any mock is authored.

No content work remains.

**Boundaries in force:** lot-02 owns HTTP protocol headers, lot-17 the Symfony
reverse proxy, lot-18 the Cache component; lot-03 owns the HTTP kernel events
and lot-12 the console events, while lot-20 owns the dispatcher and the `Event`
object; lot-14 owns the `debug:*` commands.

**Working rules in force:** no sleep, no polling loop, no background process
waiting on a resource; CI and deploy are checked **once** per turn, and the
Actions view lags by minutes on every endpoint (issue API-1), so a job shown as
running is re-checked later rather than declared stuck or green.

`POOL-002` stays in force.

## Blocked decisions

**Deferred, with a deadline:** holdout distribution — see
[ADR-0005](docs/adr/0005-holdout-distribution-deferred.md), amended by
[ADR-0006](docs/adr/0006-exam-mode-serves-the-validation-pool.md). The holdout
is **no longer deployed in any payload**: Exam Mode serves the `VALIDATION`
pool. But this repository is **public**, so holdout answers stay readable in
`content/questions/*.yml`. The exposure narrowed from *served by the
application* to *readable in the source*; §22's "protected unseen holdout
assessment" still cannot be claimed. It never blocked a content lot — but
content is now finished, so **this is the decision the project is waiting on.**
Lot 27's mock exams draw on the holdout pool, and its distribution is precisely
what is undecided. §15 lists it as requiring human approval, so it is not mine
to settle. **Lot 27 does not begin until the owner answers.**

**Process:** Lots 0–02 were committed directly to `master` on the owner's
instruction — a documented deviation from §15, not an inapplicable step. Lot 03
was the first lot shipped as branch → Pull Request → CI → controlled merge, and
every later lot follows it.

**Second deviation, Lot 21, unauthorised.** Commit `8029875` (CONTEXT.md,
recording the merge state) was pushed straight to `master` without a branch or a
Pull Request. No instruction authorised it; it was my own lapse, not an owner
decision, and §15 makes it a documented process deviation rather than a
`NOT_APPLICABLE` step. Lot 21's *content* did go through PR #31 correctly. The
evidence commit that carries this paragraph goes through a branch and a Pull
Request, as lots 08 and 09 did with PRs #17 and #19. History is not rewritten.
