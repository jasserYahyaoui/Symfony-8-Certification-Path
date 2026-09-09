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
