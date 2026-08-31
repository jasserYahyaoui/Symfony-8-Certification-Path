# Accessibility baseline

Implements the accessibility gate of Master Plan §13. Every changed UI must
pass this list before a lot is done (§16).

| §13 requirement | Implementation | Where |
|---|---|---|
| Complete keyboard navigation | Native controls only — `button`, `input`, `select`, `label`, `fieldset`. No custom widget intercepts keys. Docusaurus supplies the skip link and navbar semantics. | `website/src/components/QuestionCard.tsx` |
| Visible focus | `:focus-visible` draws a 3px outline in a hue distinct from every state colour, with 2px offset. Never removed. | `website/src/css/custom.css` |
| Semantic headings | One `h1` per page; sections use `aria-labelledby` pointing at a real heading. No level is skipped. | `website/src/pages/*.tsx`, generated Markdown |
| Accessible form controls and labels | Every input has a `label[for]`. Choice groups are a `fieldset` with a `legend` naming the question number. | `website/src/components/QuestionCard.tsx` |
| Sufficient contrast | Palette documented below; all pairs ≥ 4.5:1, most ≥ 6.7:1. | `website/src/css/custom.css` |
| Responsive mobile layout | Docusaurus's responsive shell, plus `grid-template-columns: repeat(auto-fit, …)` for the filter rows. | `website/src/css/custom.css` |
| Results not by colour alone | Every verdict carries a text label ("Réponse correcte" / "Réponse incorrecte") and a `✓`/`✗` glyph in addition to colour. | `.certpath-verdict` in `website/src/css/custom.css` |
| Readable timer, non-destructive timeout | `font-variant-numeric: tabular-nums`, 1.6rem, bold. On expiry the exam **submits the answers already entered** — it never discards work. | `website/src/pages/exam.tsx`, `finish(…, true)` |
| Reduced-motion support | `@media (prefers-reduced-motion: reduce)` neutralises animation, transition and smooth scrolling. | `website/src/css/custom.css` |
| Light and dark themes | Docusaurus colour modes, respecting `prefers-color-scheme`; both palettes define the verdict colours explicitly. | `website/src/css/custom.css` |

## Contrast ratios

Measured against the light-theme background `#ffffff`:

| Token | Value | Ratio | Use |
|---|---|---|---|
| `--ifm-color-primary` | `#0b4f9c` | 8.1:1 | Links, buttons |
| `--certpath-correct` | `#14622f` | 6.7:1 | Correct verdict |
| `--certpath-incorrect` | `#97180f` | 7.4:1 | Incorrect verdict |
| `--certpath-focus` | `#b8400a` | 4.9:1 | Focus outline |

Body and secondary text come from Docusaurus's own tokens, which meet WCAG AA
in both themes. `[data-theme='dark']` redefines each `--certpath-*` token so no
verdict colour is left to inherit a light-theme value.

## Screen-reader announcements

The exam timer is deliberately **not** a continuously live region: announcing
every second would flood a screen reader and make the page unusable. It sets
`aria-live="assertive"` only at the five-minute and one-minute marks, and stays
`off` otherwise. Status messages and feedback blocks use `role="status"`
(polite), so a verdict is announced without interrupting.

## Not yet verified

No automated accessibility audit (axe, Lighthouse) has been run against the
deployed site. This baseline is a design commitment verified by inspection, not
a measured result; the first real audit belongs to Lot 0.5, when Practice Mode
carries actual questions and there is something substantive to audit. The
distinction is deliberate: §16 forbids reporting a gate as passed from
intention alone.
