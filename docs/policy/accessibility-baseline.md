# Accessibility baseline

Implements the accessibility gate of Master Plan §13. Every changed UI must
pass this list before a lot is done (§16).

| §13 requirement | Implementation | Where |
|---|---|---|
| Complete keyboard navigation | Native controls only — `button`, `input`, `select`, `label`, `fieldset`. No custom widget intercepts keys. A skip link precedes the header. | `assets/templates/*.html` |
| Visible focus | `:focus-visible` draws a 3px outline in a hue distinct from every state colour, with 2px offset. Never removed. | `assets/css/app.css` |
| Semantic headings | One `h1` per page; sections use `aria-labelledby` pointing at a real heading. No level is skipped. | templates |
| Accessible form controls and labels | Every input has a `label[for]`. Choice groups are a `fieldset` with a `legend` naming the question number. | `renderQuestion()` in `assets/js/app.js` |
| Sufficient contrast | Palette documented below; all pairs ≥ 4.5:1, most ≥ 6.7:1. | `assets/css/app.css` |
| Responsive mobile layout | Fluid `max-width` container, `grid-template-columns: repeat(auto-fit, …)`, wrapping nav, `overflow-x: auto` on `pre`. | `assets/css/app.css` |
| Results not by colour alone | Every verdict carries a text label ("Réponse correcte" / "Réponse incorrecte") and a `✓`/`✗` glyph in addition to colour. | `.verdict` in `assets/css/app.css` |
| Readable timer, non-destructive timeout | `font-variant-numeric: tabular-nums`, 1.5rem, bold. On expiry the exam **submits the answers already entered** — it never discards work. | `assets/js/app.js`, `finish(true)` |
| Reduced-motion support | `@media (prefers-reduced-motion: reduce)` neutralises animation, transition and smooth scrolling. | `assets/css/app.css` |

## Contrast ratios

Measured against the light-theme background `#ffffff`:

| Token | Value | Ratio | Use |
|---|---|---|---|
| `--text` | `#16191d` | 15.8:1 | Body text |
| `--muted` | `#4a5058` | 8.0:1 | Secondary text |
| `--accent` | `#0b4f9c` | 8.1:1 | Links, buttons |
| `--correct` | `#14622f` | 6.7:1 | Correct verdict |
| `--incorrect` | `#97180f` | 7.4:1 | Incorrect verdict |

A dark palette is defined under `prefers-color-scheme: dark` with the same
role structure.

## Screen-reader announcements

The exam timer is deliberately **not** a continuously live region: announcing
every second would flood a screen reader and make the page unusable. It sets
`aria-live="assertive"` only at the five-minute and one-minute marks, and stays
`off` otherwise. Status messages and feedback blocks use `role="status"`
(polite), so a verdict is announced without interrupting.

## Not yet verified

No automated accessibility audit (axe, Lighthouse) has been run — there is no
deployed page to run it against yet. This baseline is a design commitment
verified by inspection; the first real audit belongs to Lot 0.5, when Practice
Mode carries actual questions. This distinction is deliberate: §16 forbids
reporting a gate as passed from intention alone.
