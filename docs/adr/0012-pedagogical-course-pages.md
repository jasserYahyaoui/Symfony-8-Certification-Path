# ADR-0012 — Pedagogical course pages, and the word budget they leave

**Status:** Accepted
**Date:** 2026-10-27
**Amends:** [ADR-0007](0007-refinement-framework-v2.md),
[ADR-0008](0008-revision-budget-recalibration.md),
[ADR-0009](0009-mermaid-diagrams-outside-the-revision-budget.md) and
[ADR-0011](0011-page-preamble-outside-the-revision-budget.md), whose `REV-001`
measure no longer applies to a page in this format.
**Relates to:** `docs/policy/course-structure.md`, `docs/policy/revision-budget.md`,
Master Plan §4.3

## Context

On 2026-10-27 the owner asked for the course pages to change in kind, not in
wording. The request, abridged to its operative lines, verbatim:

> « je ne veux pas simplement une reformulation du contenu existant. Je veux
> que tu transformes chaque page en un véritable cours pédagogique de révision,
> facile à comprendre, progressif, précis et orienté certification. »
>
> « Le cours doit suivre cette logique : COMPRENDRE → VISUALISER → EXEMPLE →
> RÈGLE OFFICIELLE → MÉMORISER → PIÈGES → APPLICATION → QCM »
>
> « Ne tiens PAS compte d'un budget de mots. La priorité est la compréhension
> et la qualité pédagogique. Ne raccourcis pas artificiellement une
> explication lorsqu'un développement supplémentaire permet de mieux
> comprendre. »

The request also asks for a quiz at the end of each page, with a correction
that explains why each wrong choice is wrong, and a closing sheet: « Si tu dois
retenir seulement X choses », 5 to 10 points.

Three things in the project stood against that as written:

| Constraint | Where | Effect on a page of this kind |
|---|---|---|
| the revision budget, 700 / 900 / 1200 budget words by level | `REV-001`, mandatory | fails the build |
| the same ceiling as a readiness criterion | `R14_revision_budget` | the item stops counting as refined |
| a course four times the corpus median | `AUD-04` `VOL-1`, in CI | fails the audit run |

And one rule bears on the quiz: Master Plan §4.3 ends « Course pages must not
reveal interactive exam answers », enforced by `CRS-001`.

## Decision

**1. A page opts into the format.** Its front matter carries
`format: pedagogical`. `CourseLoader` refuses any other value. A page without
the flag is unchanged in every respect.

**2. Such a page has no word budget** — the owner's decision. `REV-001` skips
it, criterion `R14` holds for it, and `AUD-04` leaves it out of `VOL-1`, while
still printing how many pages are in the format and how many words they hold,
so the size of the exemption stays visible. `VOL-1` judges the other pages
against *their* median, so the rewritten pages cannot raise the threshold for
the rest. Pages not yet rewritten keep their budget: they are not growing, and
the ceiling still describes them.

**3. The flag is earned, not declared — rule `FMT-001`.** A flag anyone can set
would be a way out of the budget for any page, so the rule holds it to the
parts of the request a script can check:

- a « Pièges d'examen » section;
- a « Fiche express » of 5 to 10 top-level points;
- a « QCM d'entraînement » of at least four questions, each well-formed: three
  to five choices lettered from A, a `<details>` correction that opens with
  « **Bonne réponse : X.** » (or « **Bonnes réponses : X et Y.** »), names only
  letters that are choices, and explains in at least twelve more words.

A malformed quiz question is an error on any page, flagged or not. What no
rule can check is whether the explanation is good: like every claim in a
course, it is read against the Symfony 8.0 sources and executed where it can
be.

**4. The quiz is written apart from the banks — rule `QCM-001`.** A correction
one click away is compatible with §4.3 only if the question is not one the
exam modes ask. The rule compares each quiz prompt with every question of the
exam banks — LEARNING, VALIDATION and HOLDOUT — and with the comprehension
checks, using `CMP-002`'s comparison (same normalisation, same 40 % word gate,
same 60 % `similar_text()` threshold). A match against the holdout is
reported without its id. The quiz is in French and the banks mostly in
English, so a translated copy would pass; what the rule catches is the
realistic slip, a prompt or a snippet pasted from a bank. It fired once while
the pilot was written: a comprehension question drafted for the same lot
reused the quiz's code, at 66 %, and was replaced.

**5. `CRS-001` is unchanged.** A page may show its own item's code in a fenced
block; the item's own correct answers stay out of the prose. The pilot names
the package that provides `trigger_deprecation()` only in its
`composer require` blocks, as the page before it did.

**6. The rest of the structure is guidance, not a rule.** The order the owner
gave — understand, visualise, example, official rule, memorise, traps,
application, quiz — is written into `docs/policy/course-structure.md` with the
pilot as its example. Plain-text diagrams in a ```` ```text ```` block were
already allowed and suit linear cycles; Mermaid stays for branching flows
(ADR-0009). `## Points clés` gives way to the « Fiche express »; « À
comprendre », « À mémoriser », « QCM d'entraînement » and « Fiche express »
join the headings `SEC-001` refuses as an outcome's section, because they
summarise or test rather than explain.

## The pilot

*Deprecations best practices* (lot 03, `CRS-rkq1d437dw5d`), the page the
request's own examples describe. Every rule it states was re-read in the 8.0
sources, and the running example is real rather than invented:
`Request::get()`, deprecated in 7.4 (`@deprecated` and `trigger_deprecation()`
read at the 7.4 parent of the 8.0 reference commit) and removed in 8.0 (both
sections of HttpFoundation's `CHANGELOG.md`, `UPGRADE-8.0.md`, and the
`Error` executed on HttpFoundation 8.0.15). Executions on PHP 8.4.19 added two
facts the documentation states without explaining: a `trigger_deprecation()`
placed before the `use` declarations names the replacement in the wrong
namespace, and a file-level call reports once, when the class is autoloaded.

## Consequences

- **Reading time grows, and the plan says by how much.** The pilot went from
  836 to about 6 040 body words: at the roadmap's 110 words a minute, from
  8 to about 56 minutes of first reading for one STANDARD item. The revision
  plan is regenerated from the canonical files, so every rewritten page moves
  it. Extrapolated naively to 163 pages, the courses alone would need several
  times their current reading time — a scope question the owner decides,
  page by page or lot by lot; this ADR does not decide it.
- Two mandatory rules, both proved to fire on real data by
  `tools/audit/prove_framework_rules_fail.py` (three `FMT-001` cases, one
  `QCM-001` case); unit tests pin the parser, both rules, and the `REV-001`
  and `R14` exemptions in both directions.
- When every page carrying an atomic item is in the format, `REV-001` and `R14`
  cover nobody and are removed rather than left dormant — the exit act ADR-0007
  set the precedent for.
- Nothing changes for coverage, the syllabus matrix, levels, the exam banks,
  or the HOLDOUT's isolation.

## Addendum — the scope decision (owner, 2026-10-28)

The scope question left open above was answered after five pages had been
rewritten (*Deprecations best practices*, *Dependency Injection component*,
*Service container*, *Router debugging*, *Built-in services*). The fifth made
`build_roadmap.py` fail at the owner's pace — 160 minutes on weekdays, 220 at
weekends, four new items a day, exam on 29 December 2026: « Pas de place : 4
mocks à caser ». Asked to choose between more daily time, fewer rewrites and a
later exam, the owner chose the later exam: **every page is rewritten, the
pace is unchanged, the exam moves.**

The new date, **Tuesday 2 March 2027**, is a projection, not a measurement.
The roadmap generator was run with each page still to rewrite given the
length measured on the first five, by level (MINIMAL about 3 100 body words,
STANDARD about 5 500, DEEP estimated at 7 200 for want of a sample), and three
more cards per page. It gives a first pass of about 210 hours against 93, every
item introduced by 12 February 2027, and 2 March as the first exam date at which
the mocks fit; 23 February fails. The published plan is still computed from the
pages as they are, and fills up as they are rewritten; if the measured lengths
drift from the projection, the date is re-examined, not the pace.

Pages are rewritten in the calendar's order from the current day, so each one
is ready before it is first read.
