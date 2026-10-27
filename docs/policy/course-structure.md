# Course structure policy

This records how Master Plan **§4.3** is applied, and closes audit item
**P2.1**, which was written before §4.3 could be read.

## What §4.3 says

> ### 4.3 Course structure
>
> Use only relevant sections, not a mandatory empty template:
>
> ```markdown
> # Concept
> ## Objective
> ## Prerequisites
> ## Beginner explanation
> ## Technical explanation
> ## Focused example
> ## Similar concepts and distinctions
> ## Common mistakes
> ## Exam traps
> ## Key points
> ## Official sources
> ## Next recommended content
> ```
>
> Course pages must not reveal interactive exam answers.

## How this project applies it

**The list is a menu, not a checklist.** "Use only relevant sections, not a
mandatory empty template" is explicit: a course omits any section it has nothing
to put in. A course with no exam trap carries no `Pièges d'examen` section, and
that is compliance, not a gap.

**The headings are French.** §5 permits French explanations, so the section
names are given in French (`Pièges d'examen` for `Exam traps`). The English
strings in §4.3 name the *sections*, not the literal heading text.

**A descriptive heading is a section.** Where a course has exactly one trap and
names it — `Le piège de l'instant`, `Deux pièges` — that is the Exam traps
section with a more useful title. §4.3 constrains which sections exist, not how
they are worded.

## Measured state (2026-09-09, 163 courses)

| | Courses |
|---|---|
| `## Pièges d'examen` verbatim | **163** |
| A trap section under a descriptive heading | 0 |
| No trap section | **0** |

The previous reading, on 2026-09-03, was 93 verbatim, 8 under a descriptive
heading and 62 with none. It is kept below because the resolution written
against it is still the correct reading of §4.3 — what changed is the content,
not the rule.

## P2.1 — resolution, and what superseded it

P2.1 read: *"standard `Pièges d'examen` section wherever a trap exists in prose
(~50 courses)"*. It was closed **NOT_REQUIRED** on 2026-09-03, on the plan text
rather than on a judgement call: §4.3 says "use only relevant sections, not a
mandatory empty template", so a course with nothing to put in a trap section
correctly has none, and adding one would create the empty template §4.3 forbids.

**That reasoning stands. It was not overturned — it was made moot.**

On 2026-09-09 the owner asked for the 69 missing sections to be written. They
were written as *content*, not as headings: each names a specific confusion,
against a rule the course's own version-anchored source already establishes.
The measured sizes are 27 to 140 body words, median 64 — no section is an empty
template, and `REV-001` bounded the whole campaign inside the revision budget.

So the two states are both compliant with §4.3, for different reasons:

- **before** — 62 courses had no trap section because nothing was there to say,
  and §4.3 forbids inventing a heading to fill;
- **after** — 163 courses have one because something was found to say in each,
  and §4.3 permits any section that carries content.

What would violate §4.3 is the third state neither of these is: a heading
present with nothing under it. `tools/audit/aud09_course_sections.py` measures
that directly, so the distinction is checkable rather than asserted.

What §4.3 *does* mandate is its last line — **course pages must not reveal
interactive exam answers** — and that is enforced by rule `CRS-001`, not by a
heading convention. It fired twice during the 2026-09-09 campaign, on two
sections that repeated in prose an answer key their own item's question uses;
both were rewritten to drop the literal string rather than moved into a fence.

### Why this section exists at all

A governance document that records a measured state must be re-measured when
the content changes, or it becomes a false statement that reads as authority.
Between 2026-09-03 and 2026-09-09 this file claimed 62 courses deliberately had
no trap section while all 163 had one. Nothing failed, because no gate compares
a policy's prose with the corpus — which is exactly why the figure has to carry
its date and be refreshed by hand when a campaign moves it.

## Diagrams (ADR-0009)

A course may carry a Mermaid diagram where the syllabus examines an **ordered
flow with branches** — a step that short-circuits the rest, a failure, an
exception. A plain list does not need one, and a plain-text diagram in a
```` ```text ```` block is often enough.

- The diagram is fenced as ```` ```mermaid ````, opens with its type, and
  carries `accTitle` and `accDescr` (rule `DIA-001`). A visible title goes in
  the block's own `---` front matter.
- It is outside the revision budget, not outside verification: each arrow is
  checked against the Symfony 8.0 code like a sentence, and `CRS-001` reads
  inside the block.
- It is drawn for a phone first. A diagram is scaled to the screen's width, and
  its text with it: keep it vertical (`flowchart TD`), and split a flow that
  spreads over three columns into two diagrams.


## The objective (ADR-0011)

`## Objectif` opens every course: what the learner will be able to do, in at
most 60 words of prose — no code, table, list or sub-heading (rule `OBJ-001`).
A scope note may follow as a `>` blockquote. The section is outside the revision
budget, like the preamble the generator writes above it, by the owner's
decision of 2026-10-24; what teaches belongs in the body, where the budget
counts it.

## Pedagogical pages (ADR-0012)

By the owner's decision of 2026-10-27, a page may be rewritten as a full
revision course, in this order — each part only where it serves the item:

1. **Understand** — the problem the notion solves, why Symfony works this way,
   where a developer meets it, before any rule.
2. **Visualise** — the whole mechanism at once; a ```` ```text ```` diagram for
   a linear cycle, Mermaid for a branching flow (ADR-0009).
3. **Example** — one realistic case, real wherever the 8.0 sources offer one,
   reused through the page. An invented one is labelled as such.
4. **Official rule** — quoted or paraphrased from the source, then explained:
   each important rule with its *why*. Where the source states a rule without
   its reason, say so, and mark a reconstructed reason as reasoning; where
   execution shows the reason, say it was executed.
5. **Line by line** — every API, attribute or argument that appears, with the
   arguments an exam can turn into a trap named as such.
6. **Distinctions** — a comparison wherever two mechanisms can be confused.
7. **« Concrètement dans ton application »** — upgrades, Composer, tests, logs,
   the profiler, third-party code, only where relevant; context outside the
   item's scope is labelled « contexte complémentaire ».
8. **« À comprendre », « À mémoriser », « Pièges d'examen »** — the three levels
   kept apart. Mnemonics must stay technically exact.
9. **Application** — scenarios with the expected reasoning.
10. **« QCM d'entraînement »** — at least four questions mixing recall,
    understanding, code, scenarios and close choices, each with a correction
    that says why the answer is right and why the others are not.
11. **« Fiche express »** — « Si tu dois retenir seulement X choses », 5 to 10
    points, then one sentence to rebuild the mechanism.

The page carries `format: pedagogical` in its front matter. It then has no word
budget, and rule `FMT-001` checks the parts a script can read: the traps, a
5-to-10-point digest, and a quiz whose every question is well-formed:

```markdown
**Question 1.** The prompt, which may hold a fenced code block.

- **A.** first choice
- **B.** second choice
- **C.** third choice

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Why B, and why A and C are not.

</details>
```

« **Bonnes réponses : A et C.** » announces several correct choices. Rule
`QCM-001` refuses a prompt 60 % similar to any question of the exam banks,
holdout included, or of the comprehension checks: the quiz is written for the
page, never taken from a bank. `CRS-001` is unchanged — the item's own correct
answers appear in fenced code only, so a quiz tests them through the reasoning
around them rather than as a choice to pick.

The pilot is *Deprecations best practices* (lot 03): read it before rewriting
another page.

