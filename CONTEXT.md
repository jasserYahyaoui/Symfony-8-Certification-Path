# CONTEXT.md — Session continuity (Master Plan §23)

**Last updated:** 2026-09-11

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

`master`, at `7062476` — **le lot 04 est raffiné sous le cadre version 2 et
vérifié en production ; l'étalonnage du coût des lots a rendu son verdict.**

| | |
|---|---|
| Pull requests | #99 (lot 04) · #100 (entrée du journal) · #101 (roadmap de révision candidat) |
| CI | #99 run 34575813303 `success` · #100 run 34576979376 `success` |
| Pages | #99 run 34576314839 `success`, smoke job 103189822542 · #100 run 34577807704 `success`, smoke job 103194562428 |
| Couverture officielle | **100 % (163/163)** — inchangée |
| Certification Readiness | **14,7 % (24/163) → 23,3 % (38/163)** |
| Lots raffinés | **2/27 → 3/27** |
| Corpus | 575 → **594 questions** |

Ligne relevée dans le smoke test de `7062476`, non reconstituée :

```text
ok  readiness  deployed 23.3% (38/163), 3 of 27 lots refined — matches the repository dashboard
ok  practice   383 questions, all LEARNING, no holdout id or choice
```

**Lot 04 (#99, #100).** 56 outcomes identifiés et évalués, 63 archétypes posés,
19 questions ajoutées, indice de longueur du lot 52,4 % → **23,8 %**, sous son
seuil de hasard, **avant** que l'entrée du journal ne le rende gaté. Un item
promettait de situer `AbstractController` du côté du bundle sans citer la
déclaration de namespace qui le prouve ; la source a été ajoutée.

**Aucune contradiction de cours trouvée dans le lot 04 — mais la recherche
était plus étroite qu'au lot 03, et le rapport le dit.** Les 14 cours n'ont pas
été relus intégralement : seuls les passages de source que les questions engagent
l'ont été. « Rien trouvé » n'est pas « rien à trouver ».

**Roadmap de révision candidat (#101).** Quatre documents sous `docs/revision/`
et leur générateur sous `tools/revision/`. Plan jour par jour du 1er octobre 2026
au 24 janvier 2027, 110,9 h, calculé depuis les fichiers canoniques. Trois
constats du corpus y sont enregistrés parce qu'ils contraignent le plan :
`exercise_refs` est **vide sur les 163 items** (aucun exercice n'existe) ; **37
items n'ont aucune flashcard** ; et sans plafond, le générateur plaçait **sept
notions PHP le premier jour**, ce qui a imposé un plafond à trois nouveautés
quotidiennes au prix de deux semaines de calendrier.

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
| ~~B-1~~ | Syllabus import. | — | **`PASS`, closed 2026-09-08** — complete PDF supplied 2026-09-01 (sha256 `4ee8b962…`, 468,963 bytes, 5 pages); 163/163 items verified verbatim in both directions; AUD-01 ran to a verdict and its two divergences (`SYL-1`, `SYL-2`) were repaired in PR #68. All **26** of the owner's completion conditions are met and evidenced in the audit report. **The ceiling is unchanged and closing B-1 does not lift it**: the PDF is byte-identical to the artefact the import was made from, so this is transcription fidelity and never independent corroboration |
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
| MRG-1 | Résoudre un conflit de fusion avec `git checkout --ours` reprend le fichier **entier** du côté branche, pas seulement les segments en conflit. La branche du lot 03 précédait la fusion du Mock 4 et les deux unités touchent `mock-04-holdout.yml` : 36 réparations du Mock 4 ont été jetées, et cinq questions ont perdu leur `question_archetype`, sans qu'aucun marqueur de conflit ne le signale. | Medium | **Résolu** — fusion refaite depuis le fichier de `master` avec ré-application des six éditions du lot 03, les cinq annotations rétablies. C'est le **KPI mesuré**, pas la relecture du diff, qui a rattrapé la perte : indice perceptible du Mock 4 remonté de 0 % à 43 %. Leçon : après toute fusion touchant du contenu déjà mesuré, re-mesurer avant de commiter — `--ours` et `--theirs` résolvent au fichier, jamais au segment |
| ENV-3 | Dans le conteneur de session, une attente en `sleep` ne consomme pas de temps réel : une boucle de 300 s rend la main en quelques secondes. Les horodatages de l'API GitHub, eux, sont exacts. Lire l'un à travers l'autre fait paraître « bloqué » un job qui progresse normalement. | Medium | **Ouvert, contourné** — deux exécutions saines (la CI de #95 et le déploiement de #96) ont été annulées et relancées sur ce malentendu, sans dommage sur le contenu mais pour rien. Contournement : cadencer les reprises avec `send_later` (réveil planifié, temps réel garanti) et **jamais** avec `sleep` ; comparer `date -u` à l'horodatage de l'API avant de déclarer un job bloqué |
| PR-1 | The first version of the Lot 08 pull-request description claimed all eight new courses carried a dedicated `Pièges d'examen` section. Six did. | Minor | **Resolved** in `0cde1d5` — the two remaining courses had their existing inline traps promoted into the standard section, which is also the P2.1 objective. Lesson: verify a claim about the artefact against the artefact before writing it down |
| DRAFT-1 | Two of my own Lot 09 drafts were wrong and only the source caught them: `decoration_priority` was described as putting the highest priority outermost (the documentation's own example compiles to `new Baz(new Bar(new Foo()))`, so the highest is **innermost**), and the env var processors were counted as twenty-two (`EnvVarProcessor::getProvidedTypes()` returns **twenty-one**). | Medium | **Resolved before commit** — both corrected against source. Third and fourth count/order error of the session: any stated count or ordering is verified against the code before it is written down, never against the narrative around it |
| CRS-3 | `CRS-001` fired three times in Lot 09, each because a correct answer reproduced a snippet also present in the course (an env expression, a decorator nesting, a factory line). | — | **Resolved** — all three **questions** were rewritten to test the mechanism rather than recall the literal string; no course was touched and nothing was moved into a fence |
| CRS-4 | `CRS-001` fired once in Lot 10: the correct answer of `QST-qzmh0dtgpccg` named `PasswordAuthenticatedUserInterface`, which the *Users* course also names. | — | **Resolved** — the **question** was rewritten to ask which method Symfony 8.0 removed from `UserInterface`, which tests the version-sensitive fact instead of an interface name. No course touched, nothing fenced |
| SCOPE-1 | Two Lot 11 questions were rejected by `SCOPE-001`: one used `doctrine://default` as a distractor and one asked why a **Doctrine entity** should not travel in a message. Doctrine is an excluded topic (§1.5), so neither may be the subject of a scored question. | Medium | **Resolved** — the distractor was replaced by a generic queue-backed transport, and the question was rewritten to ask why a message carries an **identifier rather than the loaded object**, which teaches the same serialisation fact without leaving the exam scope. The rule caught a scope drift I had not noticed while writing |
| CNT-2 | The Lot 11 *Events* course first claimed **seven** Messenger events. The documented list has **ten** — my own grep pattern had missed `MessageSentToTransportsEvent`, `WorkerMessageRetriedEvent` and `WorkerRateLimitedEvent`. | Medium | **Resolved before commit** — corrected by reading the source list rather than trusting my filter, and the course now teaches the *shape* of the catalogue rather than the bare count. Fifth count error of the session; the DRAFT-1 rule held, but only because the check was actually performed |
| FR-1 | French accents are missing throughout the flashcard banks of lots **07 to 11** (`traite` for `traité`, `resultat` for `résultat`, `facon` for `façon`) and throughout the matrix `content_level_justification` and `learning_outcomes` written over the same period. A line count confirms it: lots 01–06 carry 36 to 58 accented lines each, lots 07–11 carry 1 to 5. The cause is my own generator scripts, which were written in unaccented French to sidestep encoding trouble. No automated rule detects it, and no gate fails. | Medium | **Flashcard half resolved 2026-09-03; matrix half re-scoped as FR-2.** The five banks of lots 07–11 are repaired: 157 prose fields across 47 cards, 0 non-prose fields changed, accented-character counts now 51–109 against 58–130 for the always-clean banks; 18 rules and 85 tests green afterwards. Method: a context-free accent table applied by script (no judgement, backticked code spans excluded), then 97 context-sensitive occurrences (`a`/`à`, `ou`/`où`, participles) corrected one at a time by reading them, each guarded by an assertion before and after. That pass also caught **one over-correction the table itself introduced** — `oriente` is the present-tense verb and carries no accent — which is the reason the table is deliberately restricted to forms that are accented in every French sentence. Lesson: a generator script is content, and shortcuts taken inside it reach the learner |
| FR-2 | The matrix half of FR-1 was **mis-scoped**. `content_level_justification` and `learning_outcomes` carry **zero** accented characters for **lots 01 to 11** — not lots 07–11 — and lot 12 onward is clean (59, 55, 23, 14, 5, 10, 7, 7, 17, 27, 34, 24, 23, 23, 26). Measured scope: **126 items, 608 strings**; a probe of 46 common words finds 206 occurrences in 88 items, and the true count is higher because the probe is not exhaustive. Both fields are rendered onto every item page by `DocsGenerator` (lines 394 and 399), so this reaches the learner on 126 of 163 pages. | Medium | **Resolved 2026-09-08 (PR #78, merge `b5df678`), verified in production.** Delivered whole: **126 of 126 items, 619 of 734 strings, 1,537 word occurrences, 456 distinct corrections**. **The scope recorded in this very row was short by a field** — `minimum_evidence` carries the identical signature (zero accents through lot 11, fully accented from lot 12) and was in nobody's scope, so the real scope was 734 strings over **three** fields, not 608 over two. FR-3's lesson applied to FR-2 itself. Five passes, each with a different warrant: lots-12+ evidence (719), ambiguous function words and verbs read individually (~210), the repository's own French as witness (433), a witness re-run after **two witness defects** — one metadata tag poisoning `securite`, and 523 English questions testifying about French (49) — and finally words no witness can judge, read one by one (119). **Four proposals were rejected after reading** (`lie`, `apparie`, `resume`, `experimental`), each the present tense or an identifier where the evidence suggested a participle. The decisive proof is structural: every modified string reduces to the **same ASCII text**, so no word was added, removed, reordered or re-spelled and no identifier was touched. AUD-01 still finds 163/163 verbatim. Every audit re-earned its `PASS`; fail-proof 41/41. The **"uniformly zero accents" property was the detection tool, never the definition of done** — 115 strings needed no change. The production smoke test now greps the published pages for the pre-FR-2 spellings on every deploy (PR #79), so a regression fails the build instead of going unnoticed as the original defect did. |
| FR-3 | The same unaccented French, in a **third artefact nobody had measured**: the question banks. FR-1 covered the flashcards and FR-2 the syllabus matrix, so the defect was recorded as bounded when it was not. Measured: **8** of the 21 French questions, all in lots 01–02, carrying `en-tete`, `requete`, `methode`, `deja`, `etat`, `memoire`, `necessaire`, `meme` and the rest. | Medium | **Resolved 2026-09-03** — all 8 repaired: 39 prose fields by a context-free table (backticked code spans excluded), then 15 context-sensitive occurrences read one at a time, each guarded by an assertion before and after. Answer keys, choice ids, pools and every non-prose field verified byte-identical; 0 out-of-scope diffs. All **21** French questions in every bank now scan clean. Two traps were hit and recorded rather than papered over: the context-free table wrote `implémente` where the participle `implémenté` was meant, and the table held the plural `requetes` but not the singular `requete`, which left two explanations unfixed until the re-audit caught them. Lesson, after FR-1 and FR-2: **a defect's recorded scope is a claim, not a measurement** — the question banks were never scanned, so nobody knew. |
| CRS-5 | `CRS-001` fired twice on Lot 13, and both were content faults rather than rule noise. (a) The *Handling legacy deprecated code* course restated nearly all of *Deprecations best practices* (lot-03) — the two markers, the mineure/majeure calendar, the CHANGELOG and UPGRADE trace — and so reproduced that item's correct answer. (b) The *Request and response objects introspection* course used `$response->getStatusCode()`, which is the correct answer to a lot-02 HttpClient question. | Medium | **Resolved** — (a) the course was rewritten around what its item actually owns (a silenced `E_USER_DEPRECATED` notice: nothing fails, nothing prints, it exists only if an error handler collects it), with the lot-03 boundary stated on the page; its flashcard and its two LEARNING questions were realigned so nothing is asked that the course no longer teaches. (b) the example now shows headers and content. Neither was fixed by rewriting a validated question or by fencing. The rule caught duplication that the §1.4 value gate should have caught first |
| SPLICE-1 | The matrix splicer hard-coded the default `exclusion_boundaries` line. Three Lot 13 items carry `"PHPUnit Bridge is not included."` instead, so the splice would have silently replaced the syllabus's own scope note with the default text. | High | **Resolved before any data was lost** — the script's own guard refused to run rather than writing a near-match. It now matches that line with a regex and writes it back unchanged. Lesson: a splicer that assumes a constant template will corrupt the first record that differs, and only an assertion makes that visible |
| SPLICE-2 | The Lot 22 matrix splice wrote the level and outcomes but not the tails, and my fallback positional patch — whose **own guard was faulty** — then wrote **Mailer's** references into the **Serializer** item and **Mime's** into the **Runtime** item, in addition to the correct ones. Ten references ended up claimed by two items each. | High | **Resolved in Lot 25.** No gate caught it: `REF-001` checked that references *resolve*, never that they *belong*, and both wrongly credited items were `NOT_STARTED`, which no readiness rule inspects. The corruption reached `master` in Lot 22 and survived lots 23 and 24; it surfaced only because Lot 25 tried to splice Runtime and found the slot occupied. A full audit of every course, flashcard and question reference found exactly those ten duplicates and no others. `REF-001` now reports a reference claimed by two items **and** a reference whose content declares a different owning item — the sharper invariant, since content records its own item — pinned by two regression tests confirmed to fail against the previous rule. Lesson, and the second of its kind after SPLICE-1: **an assertion that is itself wrong protects nothing**, and a positional patch must be verified against the block it claims to have edited, not against its own success message. |
| COG-1 | `cognitive_level` was declared as a bare `string` that **no rule inspected**, so 39 questions written before the taxonomy settled carried an *exam skill* value in the *level* field — `DIAGNOSE` (30), `DISTINGUISH` (8), `RECOGNIZE` (1). In 38 of the 39 the two fields held the same word. Every gate passed for the whole life of the project. | Medium | **Resolved 2026-09-03** — rule `COG-001` constrains the field to `KNOW`, `UNDERSTAND`, `APPLY`; it reported exactly 39 before the fix, matching the audit count independently. All 39 reassigned under a stated mapping (symptom scenario → `APPLY`; contrast or multi-statement → `UNDERSTAND`; single recalled fact → `KNOW`), and a regression test pins the historical shape. Third instance of the same class after SPLICE-1 and SPLICE-2: **an invariant nothing checks is not an invariant.** |
| DOC-2 | The Master Plan (`SYMFONY-8-CERTIFICATION-MASTER-PLAN-V2.md`) is **not in the repository** — a filesystem-wide search finds no copy. §15 and §22 are therefore cited throughout but cannot be read verbatim from any artefact here. §15's triggers survive as an enumeration in `CLAUDE.md`; §22 survives only as the seven-word fragment *"protected unseen holdout assessment"* quoted inside ADR-0005. | Medium | **Resolved 2026-09-03** — the owner supplied the Master Plan and §15, §22, §5 and §4.3 were read verbatim. The consequences were not cosmetic: §22 is now quoted in full in [`docs/policy/final-readiness.md`](docs/policy/final-readiness.md) and assessed clause by clause; §15's approval list turns out to be **exhaustive** (*"Human approval is required **only** for…"*), which is narrower than the reading ADR-0005 had been held under; §4.3 and §5 respectively closed audit items P2.1 and P2.5 without a line of content changing. Lesson: three findings were held pending a human decision that the plan text had already made — an unreadable specification manufactures blockers. The plan is still not committed to the repository; the four sections that govern day-to-day work are now quoted verbatim inside `docs/policy/`. |
| API-1 | The GitHub Actions view **lags by several minutes**, and no endpoint avoids it. Lot 25 added a new shape: the *filtered* run listings (by `status`, or by `status` plus `actor`) reported no deploy at all for `130f7e8` while the **unfiltered** listing already showed it completed and successful, so a filter can hide a run that exists rather than merely delay its status. On Lot 14 the PR check-run endpoint reported `in_progress` for six minutes after the job had finished; on Lot 16, four; on Lot 17 the job completed at 06:15:35 and `list_workflow_jobs` with `filter: latest` still showed step 17 running at 06:19 and again at 06:21. | Low | **Open, mitigated by discipline — not by a parameter.** An earlier version of this row claimed `filter: latest` resolved it; that claim was wrong and is withdrawn. The rule is: one lagging read proves nothing, so never conclude from a single check that a job is stuck, and never report a lot `PASS` or `BLOCKED` on one read — re-check at the next check-in. The real risk is not waiting for nothing; it is announcing a state the build does not have |

| SRC-4 | **Two source citations returned 404.** `php/doc-en/master/reference/language/oop5/property-hooks.xml` carried a stray `reference/` prefix, and `php/doc-en/master/language/attributes/reflection.xml` has never existed. Both were on VALIDATION questions. A citation that 404s is worse than a vague one: the claim cannot be checked at all, and nothing in the project fetched a source URL until AUD-03 did. | Medium | **Resolved 2026-09-07** by AUD-03, in the same unit that found them. Both repaired against the real sources and re-fetched at 200. The `attributes` one is instructive: the *plausible* replacement was the method reference page `reference/reflection/reflectionattribute/newinstance.xml`, which exists — and says nothing about deferred argument validation, which is what the question tests. The correct source is `language/attributes.xml`, which states it verbatim. The anchors were rewritten to quote the sentence carrying the claim rather than name a symbol. |
| SRC-5 | **105 of 907 citations carry no anchor** — neither `symbol_or_lines` nor `anchor` — across 103 records and 57 distinct URLs (flashcards 68, courses 21, questions 16). §2.4 requires the exact section for a documentation citation and says in as many words that a homepage is not evidence for a precise technical claim. A bare `security.rst` is a 1,000-line document, not an anchor. | Medium | **Resolved 2026-09-08 (PR #69, merge `2c6018b`).** All **105 of 105** repaired in their own unit, each by reading the record's claim, fetching the source and quoting the passage that supports it — never by writing anchors faster than they could be verified. Seven classifications were used and the ledger is citation-keyed, reconciled from `content/**` by `tools/audit/src5_reconcile.py` rather than from an earlier report. One was re-classified after review (`FLC-paer6jdxzr95`, SOURCE_REPLACED → SOURCE_COMPLETED) and one anchor carrying evaluative prose was reduced to a pure locator (`QST-fhrga35d77wa`). **AUD-03 now `PASS`** — 918 citations, 167 distinct URLs, all 200. |
| SRC-6 | **`SRC-001` never inspects the citations a learner follows.** `SourceRef::hasAnchor()` is correct and rule `SRC-001` calls it — but `SRC-001` iterates `matrix->officialItems()` and checks *the matrix items'* sources. The 907 citations on courses, questions and flashcards are never passed to it, and where it does run the anchor failure is `Severity::Warning`, which does not fail a build. The invariant has been unchecked for the life of the project and every gate passed throughout. | Medium | **Resolved 2026-09-08 (PR #69, merge `2c6018b`).** `SRC-001` was extended to the learner-facing citations via `learnerFacingCitations()` and `checkCitation()`, and every anchor failure is now `Severity::Error` — **zero `Warning` remains in the rule**. The rule was strengthened, never weakened, and it was turned on only after the 105 were repaired so that the red build it would otherwise have produced carried real information. 18 tests cover it (`LearnerFacingCitationRuleTest`). Fourth instance of one pattern after `SPLICE-1`, `SPLICE-2` and `COG-1`: **an invariant nothing checks is not an invariant.** |

## Tests executed and actual results

Locally, on PHP 8.4.19, on `refine/framework-archetypes`, every command run as
its own command with its exit code read (PROC-1):

```text
php bin/cert validate                             → 21 rules, 163 items, 594 questions,
                                                    2 violations, 0 blocking                  (exit 0)
php bin/cert coverage                             → Coverage: 100% (163/163 EXAM_READY)       (exit 0)
php bin/cert readiness                            → Readiness: 23.3% (38/163), lots refined 3/27 (exit 0)
php bin/cert build                                → docs tree + payloads + readiness.json      (exit 0)
vendor/bin/phpunit                                → OK (236 tests, 8886 assertions)            (exit 0)
composer gate-full                                → all of the above, then site + a11y         (exit 0)
npm --prefix website run a11y                     → 15/15 surfaces PASS, TOTAL VIOLATIONS: 0   (exit 0)
python3 tools/audit/prove_audits_fail.py          → PROOF OK — 44 checks fired, restored       (exit 0)
python3 tools/audit/prove_framework_rules_fail.py → PROOF OK — 7 cases fired, restored         (exit 0)
python3 .github/scripts/readiness-smoke.py        → matches the dashboard; and against a
                                                    payload edited to the pre-ADR-0007
                                                    figures, 3 errors                         (exit 1)
AUD-01..AUD-08, lot01_second_audit, fr2_second    → FINDINGS: 0 each                           (exit 0)
python3 tools/audit/aud10_answer_length_bias.py   → 263/576 = 45.7%; les trois lots gatés
                                                    sous leur hasard de 25.0% : lot-01 20.6%,
                                                    lot-03 23.2%, lot-04 23.8%                (exit 0)
python3 tools/audit/aud11_length_cue_triage.py    → triage seul, ne fait échouer rien          (exit 0)
```

Smoke test de production, lignes relevées dans le job 103194562428 (`7062476`) :

```text
ok  readiness  deployed 23.3% (38/163), 3 of 27 lots refined — matches the repository dashboard
ok  practice   383 questions, all LEARNING, no holdout id or choice
ok  exam       136 questions, all VALIDATION, no holdout id or choice
ok  mock-4      75 questions, the whole holdout and nothing else, all English
```

The two non-blocking violations are the framework's own findings, not defects
left unaddressed: `PED-003` warns that 54 of 163 items carry fewer questions
than declared outcomes, and `REV-001` warns that one MINIMAL item (lot-13,
`Handling legacy deprecated code`, 450 body words) exceeds its budget of 400.
Both are recorded here because a warning nobody writes down becomes a warning
nobody reads.

**Earlier**, on `lot-26-serializer`, every command run as its own
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

**Le lot 02 (HTTP) sous le cadre de raffinement version 2.**

### Le facteur d'extrapolation est réfuté — ne pas le réintroduire

Le lot 04 avait une raison d'être précise : tester si l'écart du lot 03 entre le
plancher arithmétique et le déficit réel se reproduit. **Il ne se reproduit pas.**

| | Plancher arithmétique | Déficit réel (par lecture) | Écart |
| --- | ---: | ---: | ---: |
| Lot 03 | 17 | 20 | +17,6 % |
| Lot 04 | 18 | 19 | **+5,6 %** |

Trois items du lot 03 portaient deux questions sur le même outcome ; un seul le
fait dans le lot 04. **Ce qui tient sur les deux lots : le compte arithmétique
est un plancher. Ce qui ne tient pas : un multiplicateur.**

L'estimation de 132 questions pour les lots restants **ne doit pas être
multipliée par un facteur**. Elle se confirme lot par lot, par lecture. Sur les
deux seuls lots du cœur vérifiés ainsi, le dépassement va de **+1 à +3 questions
par lot**. La PR #96 avait présenté les +18 % comme un ordre de grandeur
transposable ; la PR #99 corrige cette formulation, et cette note existe pour
qu'une session future ne la réintroduise pas.

### Les deux constats de l'audit continuent de se traiter lot par lot

- **le biais de longueur** (`aud10`) — les lots 01, 03 et 04 sont passés sous
  leur hasard par le raffinement ; réécrire à l'aveugle les questions des lots
  non raffinés serait une édition de masse de contenu vérifié pour déplacer un
  nombre ;
- **le déficit objectifs/questions** (`PED-003`) — **54** items sur 163 portent
  encore moins de questions que d'objectifs déclarés, contre 63 avant le lot 04
  et 73 avant le lot 03.

### Reste réalisable sans mock, par ordre de valeur

Le raffinement des lots 02, 05 à 26 ; la suppression des branches locales déjà
fusionnées (cosmétique) ; et une décision sur `prove_audits_fail.py`, aujourd'hui
hors CI parce qu'il dure ~5 min et mute des fichiers canoniques.

### La roadmap candidat est livrée et ne dépend pas du raffinement

`docs/revision/` planifie l'apprentissage du candidat sur le corpus **tel qu'il
est** : les 163 items sont `EXAM_READY`, donc étudiables, que leur lot ait passé
l'audit de raffinement ou non. Le raffinement améliore la qualité des questions,
il ne conditionne pas le démarrage des révisions au 1er octobre 2026.

---

**§10 is discharged. Do not start anything below without the owner's
instruction.**

All five mocks exist, are merged and are deployed — see *Current branch* for
the per-unit evidence. What remains of Lot 27 is **not** mock work:

1. The **final audits** of §14: independent syllabus, version contamination,
   sources and anchors, content volume and duplication, holdout integrity,
   English readiness, technical/accessibility/production, and the final
   rationality and readiness assessment. The independent syllabus audit needs
   a **human-supplied copy of the official syllabus** to be worth anything
   (blocker B-1). **Superseded 2026-09-08**: the copy was supplied, AUD-01 ran,
   and B-1 is `PASS`. The instruction to ask for the copy first is spent; the
   audits themselves are all delivered.
2. The **human-supplied timed 75-question English simulation result**. No work
   in this repository substitutes for it, and no §22 clause may be claimed
   from a practice-mock score.
3. **FR-2**, which is a distinct atomic job and must not be folded into an
   audit or into any mock work. Still `REQUIRED_BEFORE_FINAL_READINESS`, still
   done completely or not at all.

Mocks 1, 2, 3 and 5 must never be recreated or renumbered to make a report
tidier, and Mock 4's 75 questions must not be modified without a demonstrated
anomaly.

**Mock 5 is delivered** (PR #64, merged `592039f`) and it is not
a TrainingMock clone. Its payload is the *candidate universe* — the 469
non-holdout questions — and the sitting is selected in the browser from this
learner's own recorded failures, ranked by a score that weighs repetition and
recency (a 30-day half-life) and is reduced by later successes. One question
per weak item, never one already served, 10 minimum and 40 maximum, duration
computed at generation.

Below 10 weak items the named `INSUFFICIENT_EVIDENCE_FALLBACK` runs: it
produces **no sitting**, states in as many words that it is not weakness-based,
and points at Practice, Exam Mode and Mocks 1–3 to gather evidence first.

**One blueprint signal is NOT_IMPLEMENTED and says so**: "answers that took
markedly longer than their estimated time". A stored attempt carries no
per-question elapsed time, so it cannot be computed from the history that
exists. It was removed rather than shipped as an always-empty field, the
blueprint carries the annotation, and a test fails if the annotation
disappears while the signal is still uncomputed.

**Mock 3 is delivered** (PR #63, merged `e7dd639`): 44
questions per sitting from a 67-question eligible pool, 52 minutes, VALIDATION
filtered to `difficulty: hard`, 14/14 topics. §5 requires Mock 3 to be
*primarily* English and the language policy binds that to VALIDATION; the test
asserts the floor §5 actually sets rather than the 100% the pool happens to
reach, because that 100% is an outcome and not a target.

**Mock 2 is delivered** (PR #62, merged `a4202c0`): 52
questions per sitting from an 83-question eligible pool, 60 minutes,
VALIDATION filtered to `DIAGNOSE|APPLY`, 14/14 topics. The blueprint's claim
that the pool already serves §10's Application role is now measured rather
than trusted — 66 of the 83 carry code or a scenario — so the LEARNING reuse
§10 permits stays declined.

**Mock 1 is delivered** (PR #61, merged `9fbef93`): 40 questions
drawn per sitting from a 61-question eligible pool, 41 minutes, VALIDATION
filtered to `RECOGNIZE|DISTINGUISH`, every one of the 14 topics represented.
The payload ships the *pool*, not the sitting, so two sittings differ; the page
draws using the blueprint's spread. Reporting is §10's full list, including
what the result does **not** establish. `TrainingMock` is shared, so the Mock 2
and Mock 3 units are thin.

**Mocks 1, 2, 3 and 5 — UNBLOCKED 2026-09-04.** The owner supplied §10's
normative text, now recorded verbatim in
[`docs/policy/mock-blueprint-policy.md`](docs/policy/mock-blueprint-policy.md)
with its provenance. The decisive reading is the owner's own: §10 fixes a
question count and a duration **for Mock 4 only**; for Mocks 1, 2, 3 and 5 it
defines a role and nothing else — no count, no duration, no topic weighting, no
pool, no pass threshold. Those values are never derived from Mock 4 and never
presented as official; every one this project decides is labelled
`INTERNAL_TRAINING_FORMAT`, every spread `TRAINING_DISTRIBUTION`.

`docs/mocks/mocks-1-2-3-5-blueprint.yml` carries the decisions, each derived by
a rule rather than chosen: count = min(⅔ of the eligible pool, what fits in an
hour), duration = ceil(count × mean estimated time × 1.15). Mock 1 40q/41min
(RECOGNIZE|DISTINGUISH, 61 eligible), Mock 2 52q/60min (DIAGNOSE|APPLY, 83),
Mock 3 44q/52min (hard, 67), all VALIDATION, all 14 topics represented. Mock 5
is generated per learner with a named `INSUFFICIENT_EVIDENCE_FALLBACK`.

One §10 discrepancy is recorded and deliberately unresolved: §10 says **15
topics**, the imported syllabus yields **14**. Nothing in this project derives
from the number 15, and the denominator remains the 163 atomic items.

Superseded: **Mocks 1, 2, 3 and 5 — BLOCKED on the owner.** Mock 4 is delivered, so what
remains of §10 is the other four, and they cannot be started:
`SYMFONY-8-CERTIFICATION-MASTER-PLAN-V2.md` is **not checked into this
repository**, and §10 was only ever quoted into it for Mock 4. For mocks 1, 2
and 5 the repository records no question count, no duration, no difficulty
profile, no language and no bank; Mock 3 has one recorded property, "primarily
in English", and nothing else.

Inferring the rest from Mock 4 would be inventing a specification and then
executing it. See
[`docs/mocks/mocks-1-2-3-5-open-questions.md`](docs/mocks/mocks-1-2-3-5-open-questions.md),
which records the measured bank capacity, the four sourcing options, and the
one thing needed: **§10's text for mocks 1, 2, 3 and 5**, quoted the way §22,
§15, §5 and §4.3 were quoted into `docs/policy/`.

One consequence is settled and must be reported that way whatever §10 says:
**no practice mock can be unseen.** `LEARNING` is served by Practice Mode and
`VALIDATION` by Exam Mode, so a mock built from either is drawn from questions
the learner has already been able to meet. Only Mock 4 is unseen, and only
because the holdout was reserved for it. No §22 clause may be claimed from a
score on a practice mock.

Then the final audits, and the human-supplied timed 75-question English
simulation result, which no amount of work here can substitute for.

**FR-2** is still open and still `REQUIRED_BEFORE_FINAL_READINESS`, as one
atomic job.

Superseded: **Implement Mock 4 — Unit C.** Built on 2026-09-04. Nothing in it
weakened what Unit B established: 75 questions, 90 minutes, 100% English, answers hidden
until submission and analysis only after it, results reported by topic and by
learning outcome, accessible, and persisted. The holdout pool stays out of
`practice.json` and `exam.json` — Mock 4 is served from its own payload, which
is Unit C's first design decision and the one the smoke test will have to
police.

Superseded: **Write the 48 new HOLDOUT questions — Mock 4 Unit B.** Delivered
on 2026-09-03 in eleven topic batches, starting with Miscellaneous (9) — the
largest topic at 19 of 163 items and the only one with no holdout question at
all — and ending with Data Validation and Messenger.

Superseded: **Decide the Mock 4 architecture.** Units 1 and 2 and the three corrections
Q-1 to Q-3 are delivered and reconciled with real ids. The blocker is
arithmetic and unresolved by any amount of work here: §10 requires 75 holdout
questions and the pool holds **27**, with none in *Miscellaneous* — the largest
topic at 19 of 163 items — and all 27 `hard` and single-answer. The decision
report evaluates four architectures and asks the owner one question.

Superseded: **Run the question-bank audit — Lot 27 unit 2.** Unit 1 (the §5 glossary) is
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
2. **Clause 2 still cannot be self-certified, and B-1 closing does not change
   that.** Coverage is 100% against the *imported* syllabus. B-1 is now `PASS`
   — the copy was supplied, AUD-01 ran, and all 26 completion conditions are
   met — but the copy is byte-identical to the artefact the import was made
   from, so **nothing here corroborates the scope against a second witness**.
   `certification.symfony.com` is unreachable and has no upstream. The
   operational instruction that stood here (*ask for the copy before running
   the audit*) is **spent**; the limit it guarded is **not**.
3. **The §5 glossary exists** since 2026-09-03 (Lot 27 unit 1): 81 entries,
   rendered and smoke-tested. The English readiness audit now checks it rather
   than writing it.

**FR-2** — the matrix accents for lots 01–11 — is open,
`REQUIRED_BEFORE_FINAL_READINESS`, is not a §22 clause,
and is not Lot 27's business. Schedule it as its own job, and do it completely
or not at all: see the issue row for why a partial pass is worse than none.

Mock 4 is 75 questions, 90 minutes, 100% English, drawn from the holdout pool
(§10). That design question is **answered and executed**: the owner chose
Option A on 2026-09-03, Unit A drew the blueprint, and Unit B wrote the 48 new
holdout questions it asked for. The pool now holds **75** questions across 75
items, still none with more than one, and ADR-0005's prohibition on
redistributing the original 27 was never approached — they are assigned where
they already sat.

What Option A means, and what it does not, is recorded in ADR-0005 and must
keep being reported that way: functional isolation **yes**, application-level
unseen **yes**, repository confidentiality **no**, answers readable by anyone
who deliberately opens the public source **yes**.

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
to settle.

**Superseded 2026-09-03/09-04.** The owner answered: ADR-0005 option 1 is
`ACCEPTED`, and §10's text was supplied and recorded verbatim in
[`docs/policy/mock-blueprint-policy.md`](docs/policy/mock-blueprint-policy.md).
Lot 27 began, and its mock exams are delivered. The sentence this replaces —
*"Lot 27 does not begin until the owner answers"* — was true when written and
is no longer. The exposure statement above it still holds unchanged: the
repository is still public and holdout answers are still readable in
`content/questions/*.yml`, so §22's *protected unseen holdout assessment* is
still not claimable in the confidentiality sense.

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
