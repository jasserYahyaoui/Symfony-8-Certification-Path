# Filling the missing `Pièges d'examen` sections

**Started**: 2026-09-09 · **Base**: `5924a34`

## Why this, and why now

Measured on `5924a34`: **94 of 163 courses carried a `Pièges d'examen` section
and 69 did not.** Every course states what a mechanism *is*; two thirds also
state what an exam does with it. The remaining third teaches without ever
naming the confusion the question will exploit.

This is the one content gap worth closing **before** a mock sitting, because it
needs no attempt data to be worth doing: a trap is a property of the material,
not of the candidate.

## The rule this campaign works under

**A trap that is not verified is worse than no trap.** Every section added here
is anchored to wording read from a version-anchored source during the session
that wrote it — `symfony/symfony-docs` at `8.0`, `symfony/symfony` at `8.0`,
`php/doc-en`, `php/php-src` at `PHP-8.4`, or the RFC text itself. Nothing is
written from the model's memory of how Symfony behaves (§19).

The Symfony framework is **not installed** in this environment (`vendor/`
carries `console`, `string` and `yaml` only), so a Symfony behaviour cannot be
executed here the way a PHP one can. Where execution is impossible, the source
wording *is* the evidence, and the section is written to say no more than that
wording supports.

## Size and budget

Existing sections measure 27 to 107 body words, median 51. `REV-001` caps a
`MINIMAL` item at 400 body words and a `STANDARD` one at 900, so the budget —
installed before this campaign, not after — decides how much can be added.

## Batch 1 — six items outside lots 03 to 07

| Lot | Item | What was done | Source read |
|---|---|---|---|
| 02 | Status codes | **Renamed** `## Distinctions et pièges` → `## Pièges d'examen` | — |
| 02 | HTTP Specification (RFC 9110) | safe ≠ idempotent; `DELETE` idempotent despite a `404`; framing is RFC 9112 | RFC 9110 §9.2.1, §9.2.2 |
| 09 | Built-in services | `debug:container` ≠ `debug:autowiring`; the alias is on the interface | `service_container.rst` 8.0 |
| 10 | Roles | `in_array(…, getRoles())` ignores the hierarchy; `role_hierarchy` is static; `IS_AUTHENTICATED_*` is not a role | `security.rst` 8.0 |
| 12 | Verbosity levels | `--silent` still logs; `SHELL_VERBOSITY` is overridden by `-q`/`-v`; the third `writeln()` argument is a threshold | `console/verbosity.rst` 8.0 |
| 12 | Built-in commands | commands run in `APP_ENV`, `dev` by default; `debug:*` reads the compiled container | `console.rst` 8.0 |

### Status codes was not a missing section — it was a differently named one

The course already carried `## Distinctions et pièges`: 401 vs 403, 301 vs 302
vs 307/308, 204 vs 200, 4xx vs 5xx. That is an exam-traps section under another
title.

It also had the least room of all 69 items — 377 body words against a `MINIMAL`
budget of 400, **23 words of headroom**. Adding a second section would have
either broken the budget or duplicated what was already written. Renaming is
the whole fix, and the audit that counted 94/163 was counting headings, not
content.

Worth stating plainly, because it bears on the other 68: **a count of headings
is a proxy.** It was right 68 times out of 69 here, and wrong once.

### The two verbatim findings worth naming

**Roles.** `security.rst` at 8.0 marks the trap itself, in the source:

```php
// BAD - $user->getRoles() will not know about the role hierarchy
$hasAccess = in_array('ROLE_ADMIN', $user->getRoles());
// GOOD - use of the normal security methods
$hasAccess = $this->isGranted('ROLE_ADMIN');
```

**RFC 9110.** §9.2.1 — *"the GET, HEAD, OPTIONS, and TRACE methods are defined
to be safe"*; §9.2.2 — *"PUT, DELETE, and safe request methods are
idempotent"*. So every safe method is idempotent and the converse is false, and
`POST` is neither. The course taught the status-code classes and never this.

## Remaining

63 items, in lots 03 (13), 04 (13), 05 (12), 06 (13) and 07 (12), delivered lot
by lot.
