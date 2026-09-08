# AUD-01 — Independent syllabus audit

**Master Plan §14 · bears on §22 clause 2 (0 critical syllabus gap) · blocker B-1**

| | |
|---|---|
| State | `NOT RUN` → `BLOCKED` → `RUNNING` → `FAIL` (2026-09-07) → **`PASS` pending the owner's remaining gate conditions** (2026-09-08) |
| Run at | 2026-09-07 |
| Commit audited | `1db32f5` |
| Script | [`tools/audit/aud01_syllabus_transcription.py`](../../../tools/audit/aud01_syllabus_transcription.py) |
| Raw output | [`transcription-check.txt`](transcription-check.txt) — exit 0 |
| Extracted text | [`pdf-extracted-2026-09-07.txt`](pdf-extracted-2026-09-07.txt) |

## The artefact

| | |
|---|---|
| Filename as supplied | `Symfony_Certification.PDF` |
| SHA-256 | `4ee8b9620c683f89c1e4d860d7a017fb1570698b8f9c56d0c716e8884a0b3bd1` |
| Size | 468,963 bytes |
| Pages | 5 |
| Supplied | 2026-09-07 by the project owner |

## What this audit can and cannot establish

**It is the same artefact already on record.** The hash is byte-identical to
the PDF used for the 2026-09-03 syllabus gate
(`docs/audit/lot-27-syllabus-gate/README.md` records the same sha256 and the
same 468,963 bytes).

That has a consequence which must not be glossed: this audit checks the
repository against **the source the import was made from**, not against a
second, independent witness. It proves **transcription fidelity and internal
coherence**. It cannot prove that the PDF is the current published syllabus,
because `certification.symfony.com` remains egress-blocked and the syllabus has
no upstream repository. Any statement that the scope has been independently
corroborated would be false, and this project does not make it.

What the audit therefore reports is bidirectional fidelity plus a
line-by-line reconciliation of the constraints and exclusions.

## Result 1 — transcription, matrix → PDF: **PASS**

All **163 of 163** atomic official items in `syllabus-matrix.yml` appear in the
PDF text character-for-character, after the two mechanical normalisations the
import already documents (f-ligature glyphs, and column wraps rejoined).
Nothing in the matrix is invented.

## Result 2 — coverage, PDF → matrix: **PASS**

The reverse direction, which no earlier pass had performed. Every line of the
PDF was classified as page chrome, prose, a topic heading, or an atomic item,
and every content line resolved to a matrix entry. **Nothing in the PDF is
missing from the matrix.**

## Result 3 — constraints: **PASS**

Each verified present in the PDF text:

| Constraint | Verbatim in the PDF |
|---|---|
| Questions | `75 questions` |
| Topics | `15 topics` |
| Duration | `90 minutes` |
| Language | `In English` |
| Symfony version | `only includes questions about Symfony 8.0 and not about Symfony 8.1, 8.2, 8.3 and 8.4 versions` |
| PHP | `PHP API up to PHP 8.4 version` |
| Twig | `Twig syntax up to 3.22 version` |
| HTTP | `HTTP Specification (RFC 9110)` |

The **15 vs 14** discrepancy is confirmed and remains unresolved by design: the
page states *"15 topics"* and enumerates **14** headings. This audit adds
nothing to the 2026-09-03 font-size measurement that settled which lines are
headings, and no figure in this project derives from 15. The denominator stays
the 163 atomic items.

## Result 4 — exclusions: **FAIL, two divergences**

The PDF carries **13** exclusions: 9 in the general list and 4 as inline notes.
`exclusions.yml` records **12**.

| # | PDF exclusion | Kind | In `exclusions.yml` |
|---|---|---|---|
| 1 | Symfony UX | general | `EXC-UX` |
| 2 | Symfony AI | general | `EXC-AI` |
| 3 | Doctrine and database-related topics | general | `EXC-DOCTRINE` |
| 4 | Monolog | general | `EXC-MONOLOG` |
| 5 | Third-party bundles and projects | general | `EXC-THIRD-PARTY-BUNDLES` |
| 6 | AssetMapper and Webpack Encore | general | `EXC-ASSETS` |
| 7 | PHP Polyfills | general | `EXC-POLYFILL` |
| 8 | Any Symfony component not explicitly mentioned | general | `EXC-UNNAMED-COMPONENTS` |
| 9 | Any bridge to third-party services | general | `EXC-THIRD-PARTY-BRIDGES` |
| 10 | `Note: PHPUnit Bridge is not included` | particular | `EXC-PHPUNIT-BRIDGE` |
| 11 | `Note: ESI (Edge Side Includes) is not included` | particular | `EXC-ESI` |
| 12 | `Intl component utilities to access ICU data are not included` | particular | `EXC-INTL-ICU` |
| 13 | `third-party transports (Doctrine, Redis, Amazon SQS, etc.) and their usage/configuration is not included` | particular | **MISSING** |

### `SYL-1` — the Messenger transports exclusion is absent

The syllabus states it under **Messenger**, in the same form as the three
particular exclusions the repository does record. `exclusions.yml` contains no
entry for it: the strings `transport`, `redis` and `sqs` do not appear anywhere
in the file. The nearest entry, `EXC-THIRD-PARTY-BRIDGES`, is about **bridges**
and its `match_terms` are `third-party bridge, amazon ses, sendgrid, mailgun,
amqp, rabbitmq` — it names neither Redis nor Amazon SQS, and a *transport* is
not a *bridge*.

Consequence: rule `SCOPE-001` cannot catch a scored question that turns on the
usage or configuration of a Redis or Amazon SQS transport. Nothing detects it
today.

### `SYL-2` — a matrix item denies a boundary the syllabus states

`OIT-ckr67pq9npyb` (*Transports*, Messenger) records:

```yaml
exclusion_boundaries: None stated by the syllabus for this item.
```

That is false against the PDF, which states exactly such a boundary for this
topic. All seven Messenger items carry the same sentence.

## Not a finding, recorded so it is not re-litigated

`content/courses/CRS-se1jr6cxh2n7.md` names AMQP, Doctrine, Redis, Amazon SQS
and Beanstalkd when listing which transports exist. This audit does **not**
treat that as a scope violation: *Transports* is an in-scope atomic item, and
the syllabus excludes third-party transports *"and their usage/configuration"*,
not the fact that the catalogue has members. The course teaches the routing
rule, not how to configure SQS. Whether that line should change is a content
judgement for the question-bank and content audits, not for this one.

## Verdict

The 2026-09-07 run was **`FAIL`** — two verifiable divergences, `SYL-1` and
`SYL-2`. Transcription and coverage were clean in both directions and every
constraint reconciled; the exclusion record did not. The corrections were
deliberately not applied in the audit unit, so that a syllabus-scope change was
never mixed into the audit that found it.

Both are now resolved in this unit:

| | Resolution |
|---|---|
| `SYL-1` | `EXC-MESSENGER-THIRD-PARTY-TRANSPORTS` added to `exclusions.yml`, carrying the syllabus wording verbatim. `exclusions.yml` now holds **13** entries against the PDF's 13. `SCOPE-001` extended to detect it |
| `SYL-2` | all seven Messenger items now state the boundary the syllabus states, instead of denying one exists. 163 atomic items unchanged; no `official_item` or `official_wording` touched |

### Why the exclusion is contextual, not a term in the flat list

Adding `doctrine`, `redis` and `sqs` to `match_terms` is the obvious fix and is
wrong. `SCOPE-001` matches those terms against every scored question, so it
would reject:

- **Redis outside Messenger** — an in-scope `Cache` adapter, which the corpus
  legitimately teaches;
- **Doctrine named as the syllabus's own exclusion example**;
- **generic Messenger transport concepts**, which are an examinable item.

The syllabus excludes third-party transports *"and their usage/configuration"* —
not the words. So the entry declares the context it belongs to
(`official_topic: Messenger` plus the transport terms) and `SCOPE-001` fires
only when a scored question is on that topic **and** names such a transport.
Symfony Messenger itself, the transport concept, routing, `sync://` and
`in-memory://` all remain examinable, and the eight transport terms are
examples — the official note ends in *"etc."* and this list must never be read
as exhaustive.

Thirteen regression tests pin both directions: six positives (Doctrine, Redis,
Amazon SQS, another identifiable transport, usage stated in an explanation, and
a transport named only in a distractor) and seven negatives (each false
positive above, a term inside a longer word, a mention in another topic, and
the rule staying inert with no contextual exclusion configured). Against the
previous `SCOPE-001` all six positives fail and all seven negatives pass, which
is what a negative control should do.

## B-1

**No longer `BLOCKED`.** The copy exists, is registered by hash, and the audit
ran to a verdict; its two divergences are repaired.

**B-1 is not recorded `PASS` here.** The owner's completion gate for B-1 was
truncated in transmission — it ends mid-list after *"all official constraints
are represented correctly"* — so the full set of conditions is unknown. Three
are met and evidenced above; how many remain is not knowable from this side.
Marking `PASS` against a partially known gate is exactly the kind of claim this
project does not make, so B-1 stays open pending the rest of the conditions.
