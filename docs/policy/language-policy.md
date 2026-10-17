# Question and course language policy

This policy does not invent a rule. It writes down Master Plan **§5**, which
already governs language, and records what the corpus currently measures
against it. Audit item **P2.5** asked for exactly this: a written policy. It did
*not* establish that the French questions are defects — §5 permits them.

## What §5 requires

Quoted from the Master Plan:

> The principal explanations may be in French. Official API names, keywords,
> classes, interfaces, configuration keys and technical terms remain in English.
>
> Because the official exam is in English:
>
> - beginner practice may be in French;
> - advanced Practice Mode must progressively introduce English wording;
> - at least 50% of advanced questions must be in English;
> - Mock 3 must be primarily in English;
> - Mock 4, the official-format simulation, must be 100% in English;
> - final `EXAM_READY` status requires acceptable timed performance in English;
> - maintain a French-to-English certification glossary;
> - include common English question formulations and negations without
>   artificial trickery.

## How this project reads it

- **`difficulty: hard` is the operational meaning of "advanced."** The corpus
  has three difficulties; `hard` is the only one that denotes advanced practice.
  The 50% threshold is therefore measured over `hard` questions.
- **The pools carry the mock requirements.** `VALIDATION` is the exam-mode bank
  ([ADR-0006](../adr/0006-exam-mode-serves-the-validation-pool.md)) and
  `HOLDOUT` is the final-mock reserve, so the Mock 3 and Mock 4 language
  requirements bind those two pools.
- **A French question is not a defect.** §5 permits French; only the thresholds
  above bind. Translating a compliant French question buys nothing and is
  forbidden by §1.4's net-value gate.

## Technical terms stay in English (owner decision, 2026-10-14)

Verbatim: « utilise le jargon symfony toujours et les mots techniques dans toute
la doc et ne les traduisent pas en fr. Par exp j'attend compiler pass, ou service
container ». Asked to settle the edges, the owner chose `Request` / `Response`
for requête / réponse, and English for the generic words too (argument, option,
attribute, default value).

The list is `docs/policy/terminology.yml`. `tools/terminology/apply.py` applied
it on 2026-10-14 to the course bodies, the French flashcards, the French fields
of the matrix and the « what's new » pages, and its `--check` mode runs in CI.
Code, link targets and English fields are never touched. Words that are as often
ordinary French as a term (entrée, modèle, vue, rappel, lot) are not listed; the
file says why. The explanations stay in French — §5 is unchanged — only the
terms are not translated.

## Measured state (2026-10-17, 799 questions)

| §5 requirement | Measured | Verdict |
|---|---|---|
| ≥ 50% of advanced (`hard`) questions in English | 298 of 299 = **99.7%** | **PASS** |
| Mock 3 primarily English (`VALIDATION`) | 141 of 141 = **100%** | **PASS** |
| Mock 4 100% English (`HOLDOUT`) | 75 of 75 = **100%** | **PASS** |
| Beginner practice may be French (`LEARNING`) | 21 French, all `LEARNING` (7 `easy`, 13 `medium`, 1 `hard`) | **PASS** — permitted |
| French-to-English certification glossary | 81 entries, `docs/syllabus/glossary.yml`, rendered at `/docs/syllabus/glossary` | **PASS** |
| Acceptable timed performance in English | not yet exercised | **NOT_APPLICABLE** until the mocks are sat |

Whole corpus: **778 English, 21 French**.

The remediation of 2026-10-14 (P1.5, Serializer) added three English `LEARNING`
questions, two of them `hard`, taking the corpus to 731 and the advanced
denominator to 275. P1.4 (Cache) then added two more, one of them `hard`,
taking the corpus to 733 and the denominator to 276. P1.6 then added 33 English
multiple-answer questions, 3 `VALIDATION` and 30 `LEARNING`, 8 of them
`hard`: the corpus is 766 and the denominator 284. Its second batch
(2026-10-15, PHP and Automated Tests) added 10 more, 1 `VALIDATION` and 9
`LEARNING`, 2 `hard`: the corpus is 776 and the denominator 286. P1.3
(Validator) added three English `LEARNING` questions, two of them `hard`: the
corpus is 779 and the denominator 288. The deep audit of 8.0 then added three
English `LEARNING` questions, two of them `hard`: the corpus is 782 and the
denominator 290. P1.2 (Forms) added three English `LEARNING` questions, one of
them `hard`: the corpus is 785 and the denominator 291. P1.1 (PHP API) rewrote two
questions and added none. P2.7 (HttpClient) added three English `LEARNING`
questions, one of them `hard`: the corpus is 788 and the denominator 292. P2.7
(Console) added three more, one of them `hard`: the corpus is 791 and the
denominator 293. P2.7 (Profiler) added two, neither `hard`: the corpus is 793.
P2.7 (Translation) added two, one of them `hard`: the corpus is 795 and the
denominator 294. P2.7 (Filesystem, Finder) added two, one of them `hard`: the
corpus is 797 and the denominator 295. P3.1 rewrote twelve `LEARNING` questions
and labelled three of them `hard` (2026-10-17): the denominator is 298, a figure
this table did not follow until P3.2 — no test reads it. P3.2 added two English
`LEARNING` questions crossing components, one of them `hard`: the corpus is 799
and the denominator 299. Comprehension questions are not part of these figures: they
live in their own pool and banks (ADR-0010).

The corpus grew by eleven since the Lot 07 reading, across two units refined back to
back. Lot 12 added ten questions and **removed one**; Lot 13 added two. All are
English, so the French count is unchanged at 21.

The removal is the point worth recording: a question written in the Lot 12 unit
re-asked a holdout question and was dropped rather than rewritten, because the
outcome it claimed was already assessed — §1.4, not a style preference.

Four of the eleven net additions are `hard`, which moved the advanced denominator
from 259 to 263.

Lot 08 then added four more questions, one of them `hard`, taking the corpus to 693
and the advanced denominator to 264. Lot 11 added eight more, two of them `hard`,
taking it to 701 and the denominator to 266.

Every addition since the Lot 07 reading has been English, so the French count has
not moved from 21 across six refined lots. That is not a target being held: §5
permits French beginner practice, and none of these lots needed a beginner
question it did not already have.

Lots 14 to 17 added three more, none of them `hard`, taking the corpus to 704
while the advanced denominator stays at 266. Lots 18 to 21 added three more
again, also none `hard`, taking it to 707 with the denominator unmoved.

Lots 22 to 26 added nine, four of them `hard`, taking the corpus to 716 and the
advanced denominator from 266 to 270. All nine are English, so the French count
has not moved from 21 across the whole refinement campaign.

The five questions written on 2026-09-16 for the notions the Lot 02 reviews had
left open take the corpus to 721 and the advanced denominator from 270 to 272.
All five are English. One is `VALIDATION`, which is why that row moves to 137;
the other four are `LEARNING`, because their items already carried the single
`VALIDATION` question a mock sitting can draw per atomic item. The French count
is still 21 — it has not moved since the policy was written.

### The reading of 2026-09-11 after Lot 07, kept

678 questions: 258 of 259 advanced English (99.6%), `VALIDATION` 136 of 136,
Mock 4 75 of 75, whole corpus 657 English and 21 French. That reading followed
the refinement of Lot 07, which added twenty-one questions, all English.

### The reading of 2026-09-11 after Lot 06, kept

657 questions: 251 of 252 advanced English (99.6%), `VALIDATION` 136 of 136,
Mock 4 75 of 75, whole corpus 636 English and 21 French. That reading followed
the refinement of Lot 06, which added nineteen questions, all English.

### The reading of 2026-09-11 after Lot 05, kept

627 questions: 240 of 241 advanced English (99.6%), `VALIDATION` 136 of 136,
Mock 4 75 of 75, whole corpus 606 English and 21 French.

### The reading of 2026-09-11 after Lot 09, kept

615 questions: 235 of 236 advanced English (99.6%), `VALIDATION` 136 of 136,
Mock 4 75 of 75, whole corpus 594 English and 21 French. That reading followed
the refinement of Lot 09, which added ten questions, all English.

### The reading of 2026-09-11 after Lot 02, kept

605 questions: 230 of 231 advanced English (99.6%), `VALIDATION` 136 of 136,
Mock 4 75 of 75, whole corpus 584 English and 21 French. That reading followed
the refinement of Lot 02, which added eleven questions, all English.

### The reading of 2026-09-11 after Lot 04, kept

594 questions: 226 of 227 advanced English (99.6%), `VALIDATION` 136 of 136,
Mock 4 75 of 75, whole corpus 573 English and 21 French. That reading followed
the refinement of Lot 04 under framework version 2, which added nineteen
questions, all English.

### The reading of 2026-09-10, kept

575 questions: 219 of 220 advanced English (99.5%), `VALIDATION` 136 of 136,
Mock 4 75 of 75, whole corpus 554 English and 21 French. That reading followed
the refinement of Lot 03 under framework version 2, which added twenty
questions.

### The reading of 2026-09-09, kept

555 questions: 213 of 214 advanced English (99.5%), `VALIDATION` 136 of 136,
Mock 4 75 of 75, whole corpus 534 English and 21 French. That reading followed
the re-refinement of Lot 01 under framework version 2, which added five
questions.

### The reading of 2026-09-08, kept

Over 550 questions: 208 of 209 advanced English (99.5%), `VALIDATION` 136 of
136, Mock 4 75 of 75, whole corpus 529 English and 21 French. That reading
followed the first Lot 01 refinement, which added six questions.

### The previous measurements, kept

The reading of 2026-09-04 over 544 questions was: 204 of 205 advanced English
(99.5%), `VALIDATION` 135 of 135, Mock 4 75 of 75, whole corpus 523 English and
21 French. The Mock 4 row had moved from 27 to 75 because Lot 27 wrote the 48
questions the mock still needed; every one was English.

The reading of 2026-09-03 over 496 questions was: 201 of 202 advanced English
(99.5%), Mock 4 27 of 27, whole corpus 475 English and 21 French, and the
glossary **absent**. It is recorded here rather than overwritten, because a
policy that silently restates its numbers cannot be audited against the date it
claims to measure.

## The one open obligation

**Closed on 2026-09-03.** The glossary was the only part of §5 this corpus did
not satisfy. It now exists — 81 entries in `docs/syllabus/glossary.yml`,
rendered at `/docs/syllabus/glossary` and covered by the accessibility audit
and the production smoke test — delivered as Lot 27's first unit, which was
also the first point at which the terms that actually needed glossing were
known.

What remains open under §5 is not a corpus property at all: *acceptable timed
performance in English* can only be established by a human sitting a timed
75-question English simulation. Mock 4 exists to make that possible; sitting it
is not something this repository can do for itself.

## What this policy forbids

- Translating the 21 French `LEARNING` questions to raise an English
  percentage. The thresholds are already met with room to spare, and §5 permits
  French beginner practice explicitly.
- Adding English questions to move a ratio. Level and language distribution are
  outcomes of what each item needs, never targets — the same rule that governs
  `MINIMAL`/`STANDARD`/`DEEP`.
- Translating an API name, keyword, class, interface or configuration key.
  §5 keeps those in English inside otherwise-French prose.

Les **sept** questions écrites le 2026-09-17 pour le sous-topic *HTTP
Specification (RFC 9110)* — six, puis une septième comblant le trou que la
matrice de couverture avait nommé — portent le corpus à **728**, et le
dénominateur avancé de 272 à **274** : deux d'entre elles sont `hard`. Toutes sont anglaises et toutes
sont `LEARNING` — l'item est `MINIMAL`, donc `POOL-002` n'exige aucune question
`VALIDATION`, et n'en ajouter aucune évite de déplacer les effectifs des mocks.
Le compte français ne bouge pas : il est à 21 depuis l'écriture de cette
politique.
