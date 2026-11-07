# ADR-0013 — A beginner's summary of each lot, « pour les nuls »

**Status:** Accepted
**Date:** 2026-11-07
**Relates to:** [ADR-0010](0010-comprehension-checks.md) (the other per-lot
page), [ADR-0012](0012-pedagogical-course-pages.md) (the pedagogical course
format), [ADR-0009](0009-mermaid-diagrams-outside-the-revision-budget.md)
(diagrams), §4.3 (no answer revealed by teaching content)

## Context

The owner asked, verbatim:

> « Par lot, ajoute une page de résumé de tout le lot pour les nuls avec des
> exemples de vie réelle, ça doit être pédagogique »

Each lot already has an index (a table of its items) and, last in its sidebar,
a « Contrôle de compréhension ». Nothing gives a beginner the whole lot at once,
in plain words, before the courses. The standing instruction « Continue sur tt
sans me demander » applies: the choices below are this project's defaults,
recorded so that the owner can overturn any of them.

## Decision

**1. One Markdown file per lot, `content/summaries/lot-XX.md`.** Its front
matter names the lot (`lot`, `verification_status`, `reviewed_at`); the file is
named after the lot. It is loaded by its own loader into its own field of the
content set (`ContentSet::$summaries`). Nothing that computes coverage,
readiness, the revision plan, a mock or a payload reads it.

**2. Published first in the lot's sidebar category**, as « Résumé du lot (pour
débuter) », at `/docs/courses/lot-XX/resume-du-lot`. The lot index points to it.
The generator adds the reading time and, under each notion, the link to the
course of that item; the summary itself carries no link and no heading id.

**3. A fixed shape a beginner can rely on — rule `SUM-001`.**

| Section | Content |
|---|---|
| « En une phrase » | the lot in one paragraph, at most 60 words |
| « L'image de la vie réelle » | one everyday picture for the whole lot |
| « Le schéma » | optional; a Mermaid diagram under `DIA-001` |
| « Notion par notion » | one `### ` subsection per atomic item of the lot, named verbatim, each with three labelled paragraphs: **C'est quoi ?**, **Dans la vraie vie**, **Pour l'examen** |
| « Les erreurs de débutant » | at least two points |
| « La fiche du lot » | five to ten points |

« Tout le lot » is checked: an item without its notion, or a notion naming no
item of the lot, fails. « Exemples de vie réelle » is checked the same way: a
notion without its **Dans la vraie vie** paragraph fails.

**4. No fact of its own.** A summary restates what the lot's courses teach,
already executed or read in the 8.0 source; the everyday example is a picture
of that fact, never a new claim about Symfony. Writing one means reading the
lot's courses — their « L'essentiel », « Fiche express » and pitfalls — and
nothing else; an analogy that would bend a rule is dropped, not kept.

**5. No answer revealed — rule `SUM-002`.** The same test as `CRS-001`: a
correct option of 25 characters or more, found verbatim. A summary may show, in
a fenced block, code its own lot teaches; another lot's answer is a leak
anywhere. The comprehension questions are matched too, since the summary is
read before the lot's check. A holdout match withholds the question's id.

**6. French, technical terms in English**, like the courses: the terminology
tool covers `content/summaries/`.

**7. Every lot, at once.** The 26 summaries ship together, so `SUM-001` requires
one for every lot carrying atomic items from the first day: no lot is ever
tolerated without its summary, and there is no staging to remove later (lot 27
carries no item, so it has none).

## Consequences

- The pilot was lot 02, *HTTP Fundamentals*: ten notions, 1 346 body words,
  about 7 minutes; the 25 others followed in the same delivery. All 26 come to
  19 700 body words, about 99 minutes of reading, from 1 minute (a one-item
  lot) to 8 (lot 03, fifteen notions). The terminology pass turned « commande » (a restaurant
  order) into `command` and « formulaire » (a delivery slip) into `form`; the
  sentences were rephrased rather than the list changed, since in course prose
  those rewrites are right.
- Production smoke: each summary is served with its heading, one everyday
  example and one course link per notion. Accessibility: the pilot is audited
  in both themes. The Mermaid smoke map and its test include summaries.
- The journal of corrections treats a summary as a page (`derive.py`, path
  `content/summaries/lot-XX.md`), linked to its published URL.
- The summaries are extra reading, outside the revision plan: the plan's
  minutes still count the courses only. Each delivery reports the reading time
  it adds.
- `prove_framework_rules_fail.py` breaks the real pilot four ways: a notion
  renamed, an everyday example removed, a lot-02 correct answer planted, a
  diagram title removed.
