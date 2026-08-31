# Official Symfony 8 Certification syllabus (verbatim import)

## Status: NOT IMPORTED — BLOCKED

This file must contain the official syllabus **verbatim**, imported from the
scope authority defined in Master Plan §2.1:

- <https://certification.symfony.com/exams/symfony.html>
- <https://certification.symfony.com/faq.html>

At the time of the Lot 0 audit (2026-08-31) both URLs are unreachable from the
build environment: the egress proxy blocks `certification.symfony.com`. The
import therefore could not be performed. See
[the Lot 0 audit report](../reports/lot-00-audit-report.md) §10 for the
measured reachability of every mandatory source.

## Why this file is empty rather than reconstructed

Master Plan §3.1 states:

> Import every official topic and official item **verbatim** from the Symfony 8
> Certification syllabus. Lot descriptions are operational groupings only. They
> are never the coverage denominator and may not replace, merge, abbreviate or
> rename official items.

The lot list in Master Plan §14 enumerates topic areas in considerable detail,
and it would be technically easy to synthesise a plausible syllabus from it.
Doing so would substitute an unofficial denominator for the official one, and
every coverage percentage computed afterwards would be measuring the wrong
thing while looking entirely credible. That is the specific failure this
project is built to avoid, so the file stays empty until the real source is
readable.

Consequently:

- `docs/syllabus/syllabus-matrix.yml` contains no items;
- coverage is reported as **UNDEFINED**, not as `0%` — an undefined denominator
  and a zero numerator are different claims (§19);
- no content lot (Lot 1 and beyond) may begin.

## What is publicly confirmed

These constraints are corroborated outside the blocked domain and may be
labelled `OFFICIAL_FORMAT` (§7.4):

```text
75 questions
90 minutes
15 topics
English
Symfony 8.0 only
```

Everything else — the 15 topic titles, their atomic items, their wording and
any weighting — is **not** confirmed and must not be guessed. Any internal
distribution used for training must be labelled `TRAINING_DISTRIBUTION` (§10).

## How to unblock

Either:

1. allow-list `certification.symfony.com` for the execution environment, and
   re-run the import; or
2. paste the official syllabus text into this file verbatim, then build the
   matrix from it.

After import, regenerate the wording lock so that later edits to official
wording are caught by CI rule `SYL-002`:

```bash
bin/cert coverage
```
