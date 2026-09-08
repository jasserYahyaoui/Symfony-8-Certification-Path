# Question archetypes

**Rule:** `ARC-001` · **Vocabulary:** `src/Domain/QuestionArchetype.php` ·
**Decision:** [ADR-0007](../adr/0007-refinement-framework-v2.md)

## What this axis is, and what it is not

A question bank carries three independent axes. Two of them existed before this
policy:

| Axis | Question it answers | Values |
|---|---|---|
| `cognitive_level` | How deeply must the material be processed? | `KNOW`, `UNDERSTAND`, `APPLY` |
| `exam_skill` | What is the candidate asked to do? | `RECOGNIZE`, `DISTINGUISH`, `DIAGNOSE` |
| `question_archetype` | What **shape** does the question take? | the nine below |

The axis was added because the bank's only structural field, `type`, holds the
single value `mcq` across all 550 questions. An axis with one value
distinguishes nothing, so nothing prevented an item's entire assessment being
four questions built from one mould.

**What this is not.** These are not the official exam's question types. This
project has no evidence of how the Symfony certification composes its paper,
and §19 forbids inventing one. Every archetype below is a form that can be
verified by reading the question in this repository — nothing more is claimed.

## The nine archetypes

| Value | The stem shows… | …and asks |
|---|---|---|
| `CODE_OUTPUT` | code | what it produces, returns or prints |
| `CODE_DIAGNOSIS` | code that misbehaves | why |
| `CONFIG_BEHAVIOR` | configuration or attributes | the behaviour that results |
| `API_SIGNATURE` | a class, interface, method or option | whether it exists, or its shape |
| `VERSION_ATTRIBUTION` | a feature and versions | when it appeared, changed or went |
| `CONCEPT_DISTINCTION` | two mechanisms easily confused | which is which |
| `SEQUENCE_ORDER` | a lifecycle or pipeline | the order of its steps |
| `SCENARIO_CHOICE` | a situation | which approach is correct for it |
| `DEFINITION_RECALL` | nothing but the question | a stated fact or definition |

## Why the rule has two halves

Requiring a field is cheap, and a lazy author satisfies it by writing the same
value on every question. What stops that is the **consistency** half: the
declared archetype must agree with properties the question already carries.
These checks compare two different fields, so none of them can pass by
comparing a value with itself — the failure mode that made five earlier checks
in this project vacuous.

| Declared | Rejected when |
|---|---|
| `CODE_OUTPUT`, `CODE_DIAGNOSIS` | `code_language` is not declared — there is no code in front of the candidate |
| `CODE_DIAGNOSIS` | `exam_skill` is not `DIAGNOSE` |
| `CONCEPT_DISTINCTION` | `exam_skill` is `RECOGNIZE` — recognising one thing is not separating two |
| `DEFINITION_RECALL` | `cognitive_level` is `APPLY` — one of the two fields is lying (§4.1) |
| `VERSION_ATTRIBUTION` | the stem names no version at all |

The consistency half applies to **every** question that declares an archetype,
refined lot or not. Only the *requirement* to declare one is staged.

## Staging

`question_archetype` is required for every question belonging to a lot recorded
in `docs/progress/refinement-log.yml` at framework version 2 or later. The 550
questions written before the axis existed carry none; assigning archetypes to
them by guesswork would fabricate data. Each lot's refinement pass brings its
own in.

## How readiness uses it

Two criteria, and the second is concept-aware:

- `R12_archetypes_declared` — every question of the item declares one. All
  levels: an undeclared archetype is an unmeasurable question.
- `R13_archetype_variety` — the item's questions use **at least two distinct**
  archetypes. `STANDARD` and `DEEP` only. A `MINIMAL` item asked for
  recognition and nothing more is not penalised for assessing it twice the
  same way; a `STANDARD` item declared itself to need distinction *and*
  application, so one mould repeated cannot be evidence for it.
