# AUD-02 — Version-contamination audit

**Master Plan §14 · bears on §22 clause 5 (verified Symfony 8.0 sources)**

| | |
|---|---|
| State | `NOT RUN` → `RUNNING` → **`PASS`** |
| Run at | 2026-09-07 |
| Commit audited | `2ece943` + this change |
| Script | [`tools/audit/aud02_version_contamination.py`](../../../tools/audit/aud02_version_contamination.py) |
| Raw output | [`output.txt`](output.txt) — exit 0 |
| Checks proved to fire | [`../lot-27-aud03-source-anchor/fail-proof.txt`](../lot-27-aud03-source-anchor/fail-proof.txt) |

## Question asked

Does anything in the corpus anchor to a Symfony version other than 8.0, to a
Twig other than the examinable 3.22, or to a moving reference that cannot be
pinned?

## Checks

| id | Check |
|---|---|
| `CONTAM-1` | every cited repository is declared in `docs/syllabus/source-map.yml` |
| `CONTAM-2` | the ref in each URL matches the ref the source map authorises |
| `CONTAM-3` | a record's declared `branch:` agrees with the ref in its own URL |
| `CONTAM-4` | no `symfony.com/doc/current` anywhere in `content/` or `docs/syllabus/` |
| `CONTAM-5` | no `symfony-docs/master/` |
| `CONTAM-6` | no `symfony/symfony/main/` |
| `CONTAM-7` | no prose naming a non-8.0 Symfony without historical context |

`CONTAM-7` deliberately permits historical statements. *"@deprecated since
Symfony 5.1"* in a deprecation course is correct content, not contamination;
the check fires only where a version is named as the one being taught.

## Result

**0 findings over 907 citations.**

| Authority | Ref | Citations |
|---|---|---|
| `symfony/symfony-docs` | `8.0` | 648 |
| `symfony/symfony` | `8.0` | 149 |
| `php/doc-en` | `master` | 45 |
| `twigphp/Twig` | `v3.22.0` | 39 |
| `httpwg/httpwg.github.io` | `master` | 23 |
| `php/php-src` | `PHP-8.4` | 3 |

## What the first run found, and what changed

The audit did not pass on its first execution. It reported 27 findings, and
separating them mattered more than clearing them:

**Two were defects in the audit itself, not the corpus.**

1. `CONTAM-1` fired on 23 legitimate RFC 9110 and `php/php-src` citations
   because the script read only source-map entries declared as
   `repository:` + `branch:`. `SRC-RFC9110` is declared as a bare `url:`.
   The script now derives the repository and ref from either shape.
2. `CONTAM-7` fired on *"Symfony 8 propose aussi des jetons sans état"*. Bare
   `Symfony 8` names the target version exactly as `Symfony 8.0` does. The
   pattern now excludes it.

**One was a real gap in the repository.** Three records — a course, a
flashcard and a question — cited
`php/php-src/PHP-8.4/UPGRADING` while `source-map.yml` authorised no such
repository. The citations were verified correct (HTTP 200, and the file is
*"PHP 8.4 UPGRADE NOTES"*), so the **map** was incomplete, not the content.
`SRC-PHP-SRC` was added to `source-map.yml` recording the authority, its ref
and why it is a branch rather than a tag.

That distinction is the audit's real output. Clearing a finding by relaxing the
check and clearing it by fixing the repository look identical in a summary line
and are opposite acts.
