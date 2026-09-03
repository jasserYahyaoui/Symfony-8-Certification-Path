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

## Measured state (2026-09-03, 163 courses)

| | Courses |
|---|---|
| `## Pièges d'examen` verbatim | 93 |
| A trap section under a descriptive heading | 8 |
| No trap section | 62 |

## P2.1 — resolution

P2.1 read: *"standard `Pièges d'examen` section wherever a trap exists in
prose (~50 courses)"*. It was recorded as `FAIL — not executed`, pending a
decision between two treatments.

**No treatment is required.** The audit item assumed §4.3 mandated the standard
section; the restored text says the opposite in the same sentence that
introduces the template. Against §4.3:

- the **62** courses without a trap section are compliant — §4.3 forbids the
  empty template that adding one would create;
- the **8** courses with a descriptively-titled trap section are compliant —
  the section exists and its content is the trap;
- the **93** verbatim courses are compliant.

P2.1 is closed as **NOT_REQUIRED**, on the plan text rather than on a judgement
call. Renaming the 8 headings would be a cosmetic change to compliant courses,
and §1.4's net-value gate rejects it: it does not improve any learner's
probability of answering an official-scope question correctly.

What §4.3 *does* mandate is the last line — **course pages must not reveal
interactive exam answers** — and that is enforced by rule `CRS-001`, not by a
heading convention.
