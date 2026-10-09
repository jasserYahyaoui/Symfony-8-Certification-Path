# ADR-0009 — Mermaid diagrams in courses, outside the revision budget

**Status:** Accepted
**Date:** 2026-10-09
**Supersedes:** nothing. **Amends:** [ADR-0007](0007-refinement-framework-v2.md) and
[ADR-0008](0008-revision-budget-recalibration.md), whose `REV-001` measure this
changes. **Relates to:** `docs/policy/revision-budget.md`,
`docs/policy/course-structure.md`

## Context

At the close of the second-pass audit the owner was offered Mermaid diagrams on
the courses where the syllabus examines an **ordered flow with branches**: a
step that short-circuits the rest, a failure, an exception. Fourteen courses
carry a ```` ```text ```` block, and the five inspected that day are plain-text
diagrams; nothing on the site could render a real one.

Two constraints stood in the way, both measured on 2026-10-09:

- `REV-001` counted every whitespace token of a course body, fenced code
  included. The course that most needed a diagram, *Request handling* (lot 03,
  `DEEP`), stood at 1194 of its 1200 words; *HTTP Caching* (lot 17) at 897 of
  900. A diagram would have had to displace the explanation it illustrates.
- The site had no Mermaid support at all.

## Decision

**1. A ```` ```mermaid ```` block is not counted as body words.** The owner's
instruction, verbatim:

> « Vas y pour mermaid et ninclut pas memrmaid dans budget mots , faire
> enregistrer cette règle »

The exclusion applies everywhere body words are measured, so that no two
reports disagree about the size of a course: `REV-001` and readiness criterion
`R14` (through `Course::wordCount()`), the content-volume audit `AUD-04` and the
revision roadmap (`build_roadmap.py`). One pattern defines a block —
`Course::MERMAID_BLOCK` — mirrored verbatim in `tools/audit/collect.py` and in
the roadmap script. An unclosed fence matches nothing, so a malformed diagram
makes a page heavier, never lighter.

The rationale the exclusion rests on: a diagram's source is node identifiers,
arrows and labels, not prose read at 250 words a minute, and counting it made a
diagram compete with the explanation it replaces.

**2. A new mandatory rule, `DIA-001`.** Every Mermaid block in a course must:

- open with a known diagram type (`flowchart`, `sequenceDiagram`, …), after an
  optional `---` front matter carrying its visible `title`;
- carry a non-empty `accTitle` and a non-empty `accDescr`, which Mermaid renders
  as the SVG's `<title>` and `<desc>`;
- be closed, and fenced with backticks — a `~~~mermaid` block would escape both
  the exemption and the rule.

The rule has two jobs. A diagram is an image, and without a title and a
description a screen-reader user gets nothing from it. And an exemption that
accepted any text inside the fence would be a place to put the prose the budget
refuses: requiring a declared diagram type closes the easy form of that, and
`AUD-04` now reports the Mermaid token count separately, so the rest stays
visible. `tools/audit/prove_framework_rules_fail.py` injects three `DIA-001`
defects and one `REV-001` defect into the real diagram course, and asserts each
fires.

**3. A diagram is a claim.** Each arrow is verified against the Symfony 8.0
code, exactly as a sentence would be, and `CRS-001` reads inside the block like
anywhere else on the page: a fence is not a hiding place.

## Rendering, measured

- **Dependency.** `@docusaurus/theme-mermaid` 3.10.2, matching the core, which
  brings `mermaid` 12.1.0. The lockfile gained 117 packages and changed none.
  `npm audit` went from 58 to 61 advisories: the theme inherits the
  `@docusaurus/core` chain already reported, and `mermaid` adds a low-severity
  advisory through `katex` (GHSA-238p-pmpm-9mq7). CI has no `npm audit` gate.
- **Drawn in the browser, not at build time.** The built HTML holds no SVG. The
  accessibility audit therefore waits for every diagram to be drawn — the
  expected count read from the generated Markdown — and checks the rendered
  `<title>` and `<desc>`, not only the source. The production smoke test, which
  reads HTML with `curl`, can see the section but not the drawing.
- **Weight.** On the page with diagrams, the JavaScript loaded rose from
  614 602 to 2 161 587 bytes, uncompressed. A page without a diagram loads
  Mermaid not at all: 548 648 bytes before, 543 865 after.
- **Legibility on a phone.** One diagram covering both paths was 830 px wide,
  scaled to 358 px: its labels rendered at about 7 px. Split into the nominal
  path and the exception path, with tighter node spacing, they render at
  11.4 px and 11.6 px.
- **Accessibility.** The first audit of the diagram page, at phone width and in
  both themes, reported four violations of three defects. One was Mermaid's:
  dark-theme edge labels at 4.43:1, fixed in `custom.css`. The two others had
  been on the site all along — a Prism colour at 4.29:1, and course tables
  scrolling without being reachable by keyboard, now wrapped in a focusable
  region named after their column headers — invisible because course pages had
  only ever been audited at desktop width.

## Consequences

- A course may now carry a diagram without paying for it in the budget. The
  budget still binds the prose around it: `REV-001` counts every word outside the
  fence, and the proof shows ten words added beside the diagrams of *Request
  handling* fail it.
- The revision roadmap no longer counts diagram source as reading time. A
  diagram does take time to read; the source token count was never a good
  measure of it, and no better one is claimed here.
- Any later change to the pattern must change all three copies. Widening it to
  every fence would take every PHP snippet out of the budget; a unit test pins
  that it does not.

## Addendum — second batch (2026-10-09)

Two text diagrams were replaced by drawn ones: *Retries and failures* (lot 11),
checked against `SendFailedMessageForRetryListener::shouldRetry()` and
`SendFailedMessageToFailureTransportListener` (Messenger 8.0.15), and
*Authenticators, Passports and Badges* (lot 10), checked against
`AuthenticatorManager::executeAuthenticator()` and the listeners' subscribed
events (Security HTTP 8.0.14). The authenticator diagram now shows the failure
branch the text version left out.

`DIA-001` reads the source, so it cannot see a Mermaid **syntax error**, which
draws nothing. The accessibility audit now visits every course page carrying a
```` ```mermaid ```` block, found from the generated Markdown rather than listed
by hand, and fails when fewer diagrams are drawn than written. Proven by
breaking one block in the generated Markdown: the audit exited 1, naming the
page, 1 block and 0 drawn.

Visiting those pages found one more defect that was not Mermaid's: the light
code theme writes YAML keys (`atrule`, `attr-name`) in #00a4db, 2.69:1 against
the code background. All 25 courses with a YAML block were affected and none
had ever been audited. Fixed in `custom.css` at 5.5:1.

