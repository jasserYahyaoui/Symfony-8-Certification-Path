# ADR-0014 — Every schema is drawn

**Status:** Accepted
**Date:** 2026-11-10
**Amends:** [ADR-0009](0009-mermaid-diagrams-outside-the-revision-budget.md) and
[ADR-0012](0012-pedagogical-course-pages.md), which let a ```` ```text ````
block stand for a diagram.
**Relates to:** `docs/policy/course-structure.md`, rule `DIA-001`

## Context

The owner, on 2026-11-08, verbatim:

> « Refais voir tous les schémas, ils doivent être visibles en tant qu'image
> comme mermaid, pas un code ni texte »

The course policy allowed « a ```` ```text ```` diagram for a linear cycle,
Mermaid for a branching flow ». The pedagogical rewrite followed it. On
2026-11-08, of the 175 « Le schéma » sections of the 163 courses:

| What the section held | Sections |
|---|---|
| a Mermaid diagram | 70 |
| a ```` ```text ```` block, shown to the learner as code | 104 |
| prose, no drawing | 1 |

A browser check of the built site confirmed what the reader saw. Every one of
the 74 Mermaid blocks then on the site was drawn as an image. Each of the
105 other sections showed code or no drawing at all. Six more ```` ```text ````
blocks outside « Le schéma » drew a flow with arrows (a fallback order, a
middleware stack, an event order, a block lookup, a transformer order, a
redirect summary).

## Decision

**1. « Le schéma » is a Mermaid diagram — rule `DIA-002`.** In a course, and in
a lot summary that has the section, « Le schéma » holds at least one
```` ```mermaid ```` block and no other fenced block. Prose may surround the
drawing. `DIA-001` already holds each block to a declared type, an accessible
title and an accessible description.

**2. The 111 sections are redrawn, not rewritten.** Each diagram restates the
text block it replaces: the same facts, the same names, the same values, laid
out as boxes and arrows. An arrow is a claim like a sentence (ADR-0009). Where
the text block set facts side by side, the diagram does not invent an order:
an arrow carries a label, or the boxes hang from a common root. Facts were not
re-executed for this change. None was added or removed, and each is the one
the text block already carried, verified when its course was written.

**3. Drawn for a phone.** A diagram is scaled to the screen's width, and its
text with it. Each new diagram was rendered with the site's Mermaid and options
at a 358-pixel content width before it was written. Two sizes were considered:

| Scale | Font size on a phone | Verdict |
|---|---|---|
| 0.5 | 8 px | legible: the limit kept |
| 0.62 | 10 px | first target, dropped |

Any wider diagram was split into two or three diagrams of one section. That is
why 123 diagrams fill 111 sections. The 74 diagrams that existed before were
not redrawn, and 35 of them scale below 0.62 on a phone.

**4. A browser check of every page — `website/tools/verify-diagrams-drawn.mjs`.**
It serves the built site and opens every generated page that has a diagram or a
« Le schéma » section. Each ```` ```mermaid ```` block must be drawn as an SVG
with its accessible title and description, and none as a Mermaid error image.
« Le schéma » must show a drawing and no code block. Before this change, the
accessibility audit checked two pages this way. The production smoke test
cannot check this at all, because Mermaid draws in the browser. The verifier
proves it can fail on a broken copy of a real page, and CI runs it after the
site build.

**5. What stays text.** The « Fiche express » memo lines and the outputs shown
in « L'exemple » sections are not schemas. They stay in ```` ```text ````
blocks.

## Consequences

- The Mermaid smoke map in `pages.yml` lists every page that draws.
  `MermaidSmokeMapTest` holds it to the content: 176 pages and 197 diagrams.
- A Mermaid block is outside the revision budget (ADR-0009). Every redrawn page
  therefore has fewer body words. The revision roadmap is regenerated with the
  change.
- The terminology pass rewrites a diagram's labels like any prose. It turned
  « chaîne » into `string`, « dépôt » into `repository` and « Redirection » (an
  exception name) into `Redirect`. Those labels were rephrased; the list was
  not changed.
- `prove_framework_rules_fail.py` breaks the real pilot page two ways: its
  schema written back as a text fence, and a text fence left beside the
  drawing. `SchemaDrawnRuleTest` adds eleven unit cases, a lot summary among
  them.
