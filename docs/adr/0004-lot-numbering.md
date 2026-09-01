# ADR-0004 — Lot numbering, and why content began at lot 05

- **Status:** Accepted
- **Date:** 2026-09-01

## Context

The project owner observed that pedagogical content appeared to start at
**lot 05**, with no lots 01–04, and asked whether the numbering was coherent.

It was a fair question, and the answer has two halves: one part was correct and
merely looked wrong, the other was a genuine defect.

## What was actually true

Lots 01–04 were **never missing from the plan**. Master Plan §14 defines them:

| Lot | Scope |
|---|---|
| 00 | Infrastructure, governance and initial audit |
| 00.5 | Golden Slice |
| 01 | PHP 8.4 foundations |
| 02 | HTTP fundamentals |
| 03 | Symfony Architecture |
| 04 | Controllers |
| 05 | Routing |

They were absent from the **matrix**, not from the plan, for one reason: the
first syllabus text supplied on 2026-08-31 was truncated. It began mid-item
with the fragment `resolvers` — which the complete PDF now confirms is the tail
of *"Argument value resolvers"*, the last item of **Controllers**. The four
topics preceding Routing were therefore never imported, so lots 01–04 had
nothing to carry.

The reservation of `official_topic_order` 1–4 was made for exactly this, and it
paid off: completing the import assigned PHP=1, HTTP=2, Symfony Architecture=3,
Controllers=4 with **zero renumbering** of the 115 items already present, and
all 115 identifiers were reused unchanged.

## What was genuinely wrong

All 19 items of the **Miscellaneous** topic were assigned to a single
`lot-14`. Master Plan §14 splits that topic across thirteen delivery lots
(14 through 26), and lumping them together would have produced one
implausibly large lot while lots 15–26 sat empty — hiding the real shape of
the remaining work.

Corrected in this change: Miscellaneous now distributes across lots 14–26 as
the plan specifies.

## Decision

**Lot and syllabus topic are independent dimensions**, and the matrix records
both.

- **`official_topic_order`** is the syllabus dimension. It is set by the
  official source and may never be invented, reordered or renumbered.
- **`lot`** is the delivery dimension. It is set by Master Plan §14 and is a
  project-management grouping.

In this plan the two happen to coincide for topics 1–13 (topic *n* → lot *n*),
which makes it tempting to treat them as the same axis. They are not, and
Miscellaneous proves it: one official topic, thirteen delivery lots.

| Lot | Scope | Items |
|---|---|---:|
| 00 | Infrastructure, governance, initial audit | 0 |
| 00.5 | Golden Slice | 0 |
| 01 | PHP | 9 |
| 02 | HTTP | 10 |
| 03 | Symfony Architecture | 15 |
| 04 | Controllers | 14 |
| 05 | Routing | 12 |
| 06 | Templating with Twig | 14 |
| 07 | Forms | 13 |
| 08 | Data Validation | 8 |
| 09 | Dependency Injection | 12 |
| 10 | Security | 12 |
| 11 | Messenger | 7 |
| 12 | Console | 9 |
| 13 | Automated Tests | 9 |
| 14 | Configuration and error handling | 3 |
| 15 | Profiler and deployment | 2 |
| 16 | Internationalization and localization | 1 |
| 17 | HTTP Caching | 1 |
| 18–26 | Cache, Clock, EventDispatcher/Event, Filesystem/Finder, Mailer/Mime, Process, PropertyAccess, Runtime, Serializer | 12 |
| 27 | Final review and mock exams | 0 |

Lots 00 and 00.5 carry no atomic official items by design: they are
infrastructure and architecture validation, not teaching.

## Consequences

- Changing `lot` was safe and cost nothing. Identifiers are minted and
  independent of wording, position and grouping (ADR-0002), so no
  cross-reference broke. Only the generated documentation paths moved, and
  those are regenerated on every build (ADR-0003).
- The coverage `by_lot` breakdown now reflects the real distribution of
  remaining work, instead of a single oversized lot 14.
- Lots 18–26 each carry one or two items. Master Plan §14 anticipates this and
  permits them to share a lighter delivery report or be released together;
  coverage and source verification stay atomic regardless.
