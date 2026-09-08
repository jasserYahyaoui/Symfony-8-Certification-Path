# FR-2 — specification

**Read-only phase. Produced 2026-09-08 against `d83edd6`. No canonical file was
modified to produce this document.**

Every figure below was **re-measured from `docs/syllabus/syllabus-matrix.yml`**,
not read back from the FR-2 issue row. That is deliberate: FR-3's recorded
lesson is that *a defect's recorded scope is a claim, not a measurement*, and
this specification exists partly because FR-1's scope was a claim that turned
out to be wrong twice.

---

## 1. The exact definition of FR-2

> **FR-2 is French prose in the syllabus matrix that was written without its
> accents.**

It is *not* "strings containing zero accented characters". It is French text
whose orthography is wrong, in fields whose language is French by design.

The repair is complete when **every French word in scope carries the accents
French requires of it, and no others** — judged word by word, never by counting
accents per string.

## 2. Origin

FR-1 recorded missing accents in two artefacts: the flashcard banks and the
matrix justifications. The flashcard half was repaired on 2026-09-03. The
matrix half was then **re-measured and found mis-scoped** — the defect ran from
lot 01, not lot 07 — and was re-issued as **FR-2**.

The cause is recorded in FR-1 and is not in dispute: *"my own generator scripts,
which were written in unaccented French to sidestep encoding trouble"*. FR-1's
own lesson applies here — **a generator script is content, and shortcuts taken
inside it reach the learner.**

FR-3 was the same defect in a third artefact nobody had measured, the question
banks. It is **resolved**; all 21 French questions scan clean.

## 3. Why FR-2 is mandatory

It is classified `REQUIRED_BEFORE_FINAL_READINESS` — not a §22 clause, but a
gate the owner set in front of final readiness.

The substantive reason is that **it reaches the learner**. `DocsGenerator`
renders `content_level_justification` and `learning_outcomes` onto every item
page, so the defect is visible on **126 of 163 pages**. Verified in the
generated tree, not assumed:

```text
website/docs/courses/lot-01/abstract-classes.md:27
  Concept important exigeant distinction et application : cet item est
  proprietaire de la comparaison interface / classe abstraite …
```

**No gate detects it and none fails.** That is the whole difficulty: validate,
phpunit, the site build and the accessibility audit are all green over this
text today.

## 4. The 126 items

| | |
|---|---|
| Items in lots 01–11 | **126** |
| Items in lots 12–26 | 37 |
| Total | 163 |

The boundary is not approximate. Measured accented-string counts across the
three fields, per lot:

```text
lot-01   1 / 44      lot-12  37 / 43
lot-02   0 / 54      lot-13  34 / 43
lot-03   0 / 89      lot-14  12 / 15
lot-04   0 / 84      lot-15   8 / 10
lot-05   0 / 69      lot-16   3 / 5
lot-06   0 / 86      lot-17   4 / 5
lot-07   0 / 84      lot-18   4 / 5
lot-08   0 / 43      lot-19   3 / 5
lot-09   0 / 69      lot-20   9 / 10
lot-10   0 / 70      lot-21  11 / 11
lot-11   0 / 42      lot-22  10 / 13
                     lot-23   7 / 7
                     lot-24   6 / 7
                     lot-25   7 / 7
                     lot-26   7 / 7
```

**The single exception is `OIT-bvnvx2b6yt2y` (lot-01)**, whose justification
already contains `sœurs`. It is a later edit, and it is the reason the
"uniformly zero" description is *nearly* true rather than exactly true. This
specification does not rely on that uniformity as a correctness criterion —
see §9.

## 5. The strings — 734, not 608

**The recorded scope of 608 strings over two fields is short by one field.**

| Field | Strings, lots 01–11 | Accented, lots 01–11 | Accented, lots 12+ | Reaches the learner |
|---|---|---|---|---|
| `content_level_justification` | 126 | 1 | 37 | **yes** |
| `learning_outcomes` | 482 | 0 | 88 | **yes** |
| `minimum_evidence` | **126** | **0** | **37** | **no** |
| **Total** | **734** | 1 | 162 | |

`minimum_evidence` carries the **identical signature** — zero accents through
lot 11, fully accented from lot 12 — and was in nobody's scope.

Every other string-bearing field in the matrix was measured too, and all are
clean: `official_item`, `official_wording`, `official_topic`,
`exclusion_boundaries`, `version_constraints`, `notes`, every id and ref list,
every enum and date. **`notes` carries zero accents in lots 12+ as well**, so
its zero in lots 01–11 is not evidence of a defect.

### The scope decision, stated rather than buried

`minimum_evidence` is **not rendered to the learner** — confirmed by searching
the generated tree for its text, which appears on 0 pages. FR-2's issue row
justifies itself by learner impact, so on a strict reading `minimum_evidence`
falls outside it.

**It is included anyway**, and the reason is FR-3's lesson. Leaving one field
unrepaired would reproduce exactly the mis-scoping that turned FR-1 into FR-2
and then into FR-3, and would destroy the detection signal in that field while
leaving the errors behind. Repairing invisible metadata carries **no
pedagogical risk whatsoever** — that is precisely what "invisible to the
learner" means — so this is a data-hygiene decision, not a pedagogical one.

This is a **documented extension of FR-2's recorded scope**, made on measured
evidence and flagged here for the owner rather than folded in silently.

## 6. How the strings were identified

Three methods, in order of strength.

**a. Field-and-lot measurement.** Every string-bearing field of all 163 items,
partitioned by lot, counted for accented characters. This produced the table in
§5 and found `minimum_evidence`.

**b. Vocabulary differencing against lots 12+ — the primary evidence.** Lots
12–26 are correct French written by the same author on the same subject. A word
type is **proven defective** when every occurrence in lots 12+ is accented and
occurrences in lots 01–11 are not. This is evidence internal to the corpus, not
a table written from memory.

> **97 proven-defective word types, 749 occurrences.**

The largest: `reussies` 126, `Enoncer` 112, `reponse` 27, `mecanisme` 24,
`regle` 23, `methodes` 23, `defaut` 23, `Reconnaitre` 18, `role` 16,
`Ecrire` 15, `Declarer` 15.

**c. Ambiguity enumeration.** Words that are correct French *both* with and
without their accent can never be settled by (b) and are counted separately for
individual reading — see §9.

## 7. File types in scope

**One file: `docs/syllabus/syllabus-matrix.yml`.** Nothing else.

Not `content/**` (courses, flashcards, questions — FR-1 and FR-3 territory,
both closed). Not the generated tree, which is rebuilt from this file
(ADR-0003). Not the generator, whose French is already accented.

## 8. Corrections expected

Restoration of French diacritics on existing words: `é è ê ë à â ä î ï ô ö ù û
ü ç œ`. **No rewriting, no rephrasing, no re-ordering, no addition, no
deletion.** The word count of every string is unchanged; only the letters
change.

## 9. Linguistic rules

**The "zero accented characters" property is a detection tool, not the
definition of done, and this specification refuses to use it as an acceptance
criterion.** Two reasons, both measurable:

- A perfectly correct French sentence can legitimately contain no accented
  character (`Distinguer les mots-cles`→`Distinguer les mots-clés` has one, but
  `Nommer les quatre options` has none and is already correct). In lots 12+,
  **6 of 43 strings in lot-12 carry no accent at all** and are correct.
- Conversely, a string can contain one accent and still be wrong in five other
  words — which is exactly the state of `OIT-bvnvx2b6yt2y` today.

So the criterion is **per word**, and per word the rule is:

1. A word proven defective by §6b is corrected to the form lots 12+ use, with
   case preserved (`Enoncer`→`Énoncer`, `separer`→`séparer`).
2. A word whose accented and unaccented forms are **both** valid French is read
   in its sentence and decided individually. Never by table.
3. A word that is not French is left alone (§10).

### The ambiguous classes, counted

| Class | Occurrences, lots 01–11 | Why a table cannot decide it |
|---|---|---|
| `a` / `à` | **167** | verb *avoir* vs preposition |
| `la` / `là` | 330 | article vs adverb of place |
| `ou` / `où` | 18 | conjunction vs relative |
| `different` | 5 | adjective *différent* vs verb *diffèrent* |
| `decide`, `depasse`, `limite`, `separe` | 22 | present tense vs past participle |
| `apres` / `après` | 3 | always accented, but low count |

**`different` is the trap this project has already been bitten by twice.** The
vocabulary difference in §6b reports `different → diffèrent`, because lots 12+
happen to use the verb. In lots 01–11 the observed use is adjectival —
*"Deux methodes du contexte aux roles differents"* — where the correct form is
**`différents`**, not `diffèrent`. FR-1 hit the same shape with `oriente` and
FR-3 with `implémente`/`implémenté`. **Automatic application of §6b's table to
these classes would introduce new errors**, which is why they are enumerated
here and excluded from it.

## 10. Technical terms that stay English

**There are zero backticked code spans in these three fields** — measured, not
assumed. The protection FR-1 and FR-3 relied on (*"backticked code spans
excluded"*) **does not exist here**. 129 distinct technical identifier types sit
bare in the prose:

`STANDARD` 94, `MINIMAL` 42, `DEEP` 15, `PSR` 9, `URL` 9, `PHP` 7, `HTTP` 5,
`tryFrom`, `InputBag`, `FrameworkBundle`, `isValid`, `getAttributes`,
`newInstance`, `getClientIp`, `isRedirection`, `SameSite`, `RequestStack`,
`HttpFoundation`, `ServiceSubscriberInterface`, `getPayload`, `LTS`, `RFC`,
`DELETE`, …

None may be altered. The correction must therefore be **whole-word, case-aware,
and blind to camelCase, PascalCase and ALL-CAPS tokens**, since none of those
shapes is a French word.

### `role` — a risk that was checked, not assumed

`role → rôle` is one of the 97 proven types, with 16 occurrences. But **lot-10
is Security**, where *role* is also a Symfony concept (`ROLE_ADMIN`,
`IS_AUTHENTICATED_*`). Accenting a Symfony identifier would corrupt meaning.

All 24 `role`/`roles` occurrences in scope were read. **Every one is French
prose** — *"Citer le rôle de RequestStack"*, *"Enoncer la contrainte de nommage
d'un rôle"* — and none is a bare identifier; the Symfony identifiers appear in
their own ALL-CAPS form. `rôle` is therefore correct in all 24. **The risk was
real and was resolved by reading, not by a rule.**

## 11. French elements to correct

The 749 proven occurrences of §6b, plus whichever of the ambiguous occurrences
of §9 individual reading shows to need an accent.

## 12. What must never be modified

- `official_item`, `official_wording`, `official_topic` — the imported
  syllabus, verbatim and locked by `OfficialWordingLockRule` and AUD-01.
- `exclusion_boundaries`, `version_constraints` — official or version text.
- Every `id`, every `*_refs` list, `status`, `verification_status`,
  `exam_ready`, `content_level`, `classification`, `learning_domain`, `lot`,
  `last_verified_at`, `reviewed_by`, ordering keys.
- **Mock 4 and the holdout** — untouched by construction: the holdout lives in
  `content/questions/mock-04-holdout.yml`, which is not in scope.
- Answer keys, choices, distractors, explanations — all in `content/questions/`,
  not in scope.
- The 129 technical identifiers of §10.
- Word order, punctuation, sentence structure, and the number of
  `learning_outcomes` per item.

## 13. Impact on questions, answer keys, distractors, explanations

**None by construction.** FR-2 touches one file, and the question banks are not
it. No answer key can change because no answer key is in scope.

The *indirect* risk is different and is handled in §17: `AssessmentCoverageRule`,
`ExamReadyEvidenceRule` and `POOL-002` read matrix fields, so the fields must
stay semantically identical. `ExamReadyEvidenceRule` was inspected — it only
tests `minimum_evidence` for emptiness (`'' === trim(...)`) and never parses its
text — so an accent cannot change its verdict.

## 14. Impact on sources and anchors

**None.** `official_sources` is not in scope, and no correction changes a
technical claim — an accent restores orthography, it does not alter what is
asserted. `SRC-001` and AUD-03 therefore have nothing to re-verify.

This is checked rather than asserted: §17 re-runs AUD-03 offline and compares
citation counts before and after.

## 15. Risks of automatic correction

Recorded from this project's own history, not hypothesised:

1. **Over-correction.** FR-1's table wrote `orienté` where `oriente` (present
   tense) was correct. Mitigation: the table holds only forms accented in
   *every* French sentence; the ambiguous classes of §9 are excluded from it.
2. **Wrong inflection.** FR-3's table wrote `implémente` where the participle
   `implémenté` was meant. Mitigation: participle families are read
   individually.
3. **Incomplete table.** FR-3's table held plural `requetes` but not singular
   `requete`, leaving two explanations unfixed until a re-audit caught it.
   Mitigation: the table is *derived* from the corpus (§6b), and the completion
   criterion is a re-scan, not the table's own coverage.
4. **Identifier corruption.** New to FR-2, because there are no backticks here
   (§10). Mitigation: whole-word matching that never touches camelCase,
   PascalCase or ALL-CAPS.
5. **Destroying the detection signal.** A partial pass leaves errors behind and
   removes the only cheap way to find them. Mitigation: §16.

## 16. All-or-nothing criteria

FR-2 is one atomic job. It is **not** done in parts, and no partial state is
reported as progress.

The reason is mechanical, not stylistic: the defect is locatable today because
the accent count is uniformly zero across lots 01–11. A half pass would leave a
population of remaining errors with no signal marking them.

**Every one of the 734 strings is either verified correct or corrected, in one
delivery.**

## 17. Tests and gates

| Gate | Purpose |
|---|---|
| `php bin/cert validate` | 18 rules — `OfficialWordingLockRule` proves the official text was not touched |
| `php bin/cert coverage` | must stay 100% (163/163), no diff |
| `vendor/bin/phpunit` | 194 tests |
| `composer gate-full` | build + a11y 14 surfaces — the corrected prose is rendered |
| **structural diff assertion** | every non-prose field byte-identical; string *count* per item unchanged; word count per string unchanged |
| **AUD-03 offline** | citations unchanged, 918 over 167 URLs |
| **AUD-04, AUD-05, AUD-07, AUD-08 re-run** | the corpus changed, so their PASS is re-earned rather than assumed |
| **independent second audit** | a fresh scan asserting 0 remaining proven-defective forms |

## 18. Representative before / after — proposed, nothing yet modified

**1 · `OIT-46ry8d7dypmb` (lot-01), `learning_outcomes[0]`**
```diff
- Attribuer une fonctionnalite du langage a sa version d'introduction
+ Attribuer une fonctionnalité du langage à sa version d'introduction
```
`a → à` is preposition, decided by reading; `fonctionnalite` is proven by §6b.

**2 · `OIT-46ry8d7dypmb`, `minimum_evidence`** — the formulaic case
```diff
- Niveau STANDARD : 2 questions uniques minimum couvrant la distinction ET
- l'application, reussies sur 2 sessions distinctes.
+ Niveau STANDARD : 2 questions uniques minimum couvrant la distinction ET
+ l'application, réussies sur 2 sessions distinctes.
```
`STANDARD` and `ET` untouched. One word changes in 126 near-identical strings.

**3 · `OIT-0wnxbapegqhv` (lot-08), `content_level_justification`** — the trap
```diff
- Deux methodes du contexte aux roles differents, et une chaine de
- constructeur dont l'oubli du dernier maillon ne produit aucune erreur.
+ Deux méthodes du contexte aux rôles différents, et une chaîne de
+ constructeur dont l'oubli du dernier maillon ne produit aucune erreur.
```
**`differents → différents`**, the adjective — *not* `diffèrent`, which is what
§6b's raw table would have produced.

**4 · `OIT-3qgn13f7zvqx` (lot-10), `learning_outcomes[1]`** — the Symfony-role case
```diff
- Distinguer role, etat d'authentification et verbe metier
+ Distinguer rôle, état d'authentification et verbe métier
```
French prose about a Symfony concept; the identifier form `ROLE_*` never appears
here, so `rôle` is correct (§10).

**5 · `OIT-3qgn13f7zvqx`, `content_level_justification`** — what must *not* change
```diff
- Le passage obligatoire par les votants, y compris pour un role, trois
- familles d'attributs a distinguer, et un code de statut qui depend de
+ Le passage obligatoire par les votants, y compris pour un rôle, trois
+ familles d'attributs à distinguer, et un code de statut qui dépend de
```
`passage`, `obligatoire`, `votants`, `familles`, `attributs`, `statut` are all
already correct and are **left alone**. `passe` elsewhere in this item stays
`passe` — present tense, no accent.

## 19. Human decisions required

**None.**

- No pedagogical choice: no content is added, removed or rephrased.
- No source contradiction: no technical claim changes (§14).
- No answer key, no official wording, no holdout (§12).
- The one scope judgement — `minimum_evidence` — is documented in §5, carries no
  pedagogical risk because the field is invisible to the learner, and is
  reported to the owner rather than hidden.

FR-2 therefore proceeds without blocking.

## 20. FR-2 = DONE

All of the following, together:

1. All **734** strings across the **3** fields of the **126** items in lots
   01–11 verified or corrected — no partial delivery.
2. An independent re-scan reports **0** remaining occurrences of the 97
   proven-defective forms.
3. Every ambiguous occurrence of §9 individually read, with its decision
   recorded.
4. Structural assertion passes: non-prose fields byte-identical, string counts
   and word counts unchanged.
5. `validate`, `coverage`, `phpunit`, `gate-full` green, each run as its own
   command with its exit status read (PROC-1).
6. AUD-03 unchanged; AUD-04, AUD-05, AUD-07, AUD-08 re-run and still `PASS` on
   the new corpus.
7. Second independent audit and code review completed.
8. PR opened, CI green **on the exact merged head**, merged.
9. Deployment matches the merge commit and the production smoke log is **read**.
10. The corrected prose verified in the **published** pages.
11. `CONTEXT.md`, the audit register and `final-readiness.md` updated after
    production verification.

Anything short of all eleven is `NOT_DONE`.
