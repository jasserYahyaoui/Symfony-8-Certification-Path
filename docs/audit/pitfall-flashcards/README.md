# Pitfalls rehearsed by flashcards — audit of 2026-10-24

Each course closes its teaching with « Pièges d'examen », the traps a question
can be built on. The question this audit asks is whether each trap is
**rehearsed**, not only read: whether a flashcard asks for it. A trap met once,
in prose, is the kind of fact §6 says a card exists for.

The registry is `reviewed.yml`; the check is `tools/audit/pitfall_flashcards.py`,
which runs in CI with `--max-gaps 0`, and `--prove` beside it.

## Method

- **Unit.** A pitfall is a paragraph of the section, or a dash bullet with its
  continuation lines: **788 pitfalls in 163 courses**. Its key is the course
  and the SHA-1 of its text with whitespace collapsed, so rewording a pitfall
  voids its verdict.
- **Ranking, not judging.** A TF-IDF cosine between each pitfall and each card
  of its item ordered the reading. It was calibrated, then set aside: above 0.5
  the best-ranked card covered the pitfall in the 32 cases sampled; below it,
  638 pitfalls needed a reader.
- **Reading.** Every one of the 788 pitfalls was read against **all** the cards
  of its item — not the top three — and, where the trap belongs to another
  item, against that item's cards (`grep` over all 1 940 cards). A pitfall is
  COVERED when a card's question and answer rehearse its point; a card whose
  explanation alone mentions it does not count.
- **What the score missed.** 26 covering cards of the same item ranked below
  the top three, and 27 pitfalls were covered by a card of another item. Both
  are why the verdicts are read, not computed.

## Findings

| | Count |
|---|---:|
| Pitfalls | 788 |
| Covered by an existing card, same item | 697 |
| Covered by an existing card of another item | 27 |
| **No card rehearsed them (GAP)** | **63** |
| No card warranted (NO_CARD) | 1 |

After this audit: 786 covered (759 by a card of the same item, 27 of another),
2 NO_CARD, 0 GAP — the figures `pitfall_flashcards.py` prints, reconciled from
the courses and the decks, not from this page.

Of the 63 GAPs, 23 were in lot 01, where eight of the nine items carried a
single card. The rest were spread over fourteen lots, no more than eight per lot.

**A card was wrong.** `FLC-h2e5dj32pzgg` asked « Un listener pose une
`Response` à 204. Quel statut sort ? » and answered 500. That holds only when
the exception carries no HTTP status: a `NotFoundHttpException` gives 404
(`HttpKernel.php` 255-263, executed). The condition sat in the explanation,
which a learner flips past. The front now names a `\RuntimeException`, the back
gives the 404 case, and the fact is a central fact with a behavior check and a
forbidden pattern for the old wording.

**A course sentence was wrong.** The routing course (`CRS-wg3j0t5pm7w1`) said
of requirements « les properties Unicode fonctionnent ». Without the `utf8`
option, a `\p{Lu}` requirement makes the route fail to compile with a
`LogicException` (`RouteCompiler.php` 182-183, executed). Corrected, with the
same central-fact guard.

**A course sentence contradicted another lot.** The HTTP Caching course said
« L'invalidation ne fait pas partie de la spécification HTTP », after the
Symfony documentation; lot 02 teaches, after RFC 9111 §4.4, that a cache MUST
invalidate the target URI after an unsafe method. Both are right about
different invalidations. The course now says *on-demand* invalidation, cites
RFC 9111, and was trimmed elsewhere to stay within its STANDARD budget (898
words of 900).

**One pitfall has no card, on purpose.** « `getUser()` sur un passport sans
`UserBadge` lève une `LogicException` » is true of the code
(`Passport.php` 52-53) but not reachable in 8.0: both `Passport` and
`SelfValidatingPassport` require a `UserBadge` in their constructors. A card
would rehearse a situation an exam cannot set. The other NO_CARD is a scope
note in the Assets course, « Les outils de construction d'assets sont hors
périmètre », which states no fact a question could test.

Observed, not changed: the CSRF course's pitfall « l'identifier par défaut est
le nom du form, pas une valeur commune » holds for FrameworkBundle's defaults,
while the same course says the Flex recipe gives `submit` to every
autoconfigured form type.

## What was added

- **62 flashcards**, one per GAP but the passport one: 23 in lot 01, 39 in
  lots 02 to 21. Each fact was executed — PHP 8.4.19 for lot 01, Symfony 8.0.15
  (`tools/behavior` vendor) for the rest — or read in the source it cites; 54
  distinct URLs, every one fetched. Level distribution, as an observation:
  22 TRAP, 18 RECALL, 11 UNDERSTANDING, 11 APPLICATION.
- **Two central facts** (`docs/facts/central-facts.yml`):
  `http-kernel.exception-response-takes-exception-status` and
  `routing.unicode-requirement-needs-utf8`, each with a check in
  `tools/behavior/checks/` and a pattern that fails `CentralFactsTest` if the
  old wording returns. Both patterns were shown to match the old text.
- **The registry and the audit.** `--prove` injects, in memory, a pitfall
  without a verdict, a verdict whose pitfall is gone, a reworded pitfall, a
  COVERED card that does not exist, a NO_CARD without a reason, an unknown
  verdict, a duplicate, a course without the section, and a GAP over the
  ceiling; each is refused.
- **HOLDOUT.** The new cards were checked against the 75 HOLDOUT correct
  answers of 25 characters or more, by count only; none matched. This is the
  functional isolation AUD-06 checks, not confidentiality: the repository is
  public.

## Cost to the revision plan

Cards are priced at 45 s each in the first pass. With the owner's pace (start
2026-10-01, exam 2026-12-29, four new items a weekday, 160/220 minutes), the
plan still introduces every item by 2026-12-11 and keeps the mocks on 12, 13,
19, 20 and 26 December. The generated load grows from 147.8 h to 148.6 h, the
scheduled load from 145.5 h to 146.4 h.

## Not covered by this audit

Whether a card is *well written* — one idea, a back that cannot be guessed from
the front — is §6's question and the reviewer's. This audit asks only whether a
trap has a card. A new pitfall now fails CI until a reader gives it a verdict.
