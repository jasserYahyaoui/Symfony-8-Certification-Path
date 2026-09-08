# CONFLICT_STOP — `QST-fhrga35d77wa`

**Raised by SRC-5 on 2026-09-07. SRC-5 stopped at this record.**

This is reported, not resolved. §15 lists *"an unresolved source contradiction"*
among the categories requiring human approval, and §2.5 requires the resolution
to be documented rather than chosen silently.

## The question

`QST-fhrga35d77wa` — pool `LEARNING`, difficulty `hard`, topic PHP.

> **How must an application define its own throwable type in PHP?**
>
> - ✅ **By extending Exception or Error**
> - ❌ By implementing Throwable directly
> - ❌ By implementing Stringable and Throwable together
> - ❌ By extending any class and adding a `getMessage()` method
>
> *Explanation:* PHP classes cannot implement Throwable directly. A custom
> throwable must extend Exception **or Error**, optionally also implementing an
> application marker interface.

## The cited source

`php/doc-en@master`, `language/predefined/throwable.xml`, the note at lines
17–22, verbatim:

> PHP classes cannot implement the `Throwable` interface directly, and must
> instead extend `Exception`.

## The contradiction

The source and the answer key agree on the first half — a userland class cannot
implement `Throwable` directly — which is what the three distractors turn on.

They disagree on the second half. The answer key permits `Error`; the manual's
note permits only `Exception`. Read strictly, the cited normative source
**excludes** the very option the answer key marks correct.

This is not a silent source. Silence would be `SOURCE_REPLACED` or
`SOURCE_COMPLETED` and SRC-5 would have handled it without stopping. Here the
source speaks and says something narrower.

## What was checked before raising it

- `language/predefined/error.xml` — describes `Error` and its `Throwable`
  implementation; says nothing about whether userland classes may extend it.
- `language/errors/php7.xml` — gives the `Error` hierarchy under `Throwable`
  and lists PHP's own subclasses (`ArithmeticError`, `AssertionError`,
  `CompileError`, …). Those are engine classes; the page makes no statement
  about userland extension.
- `language/oop5/inheritance.xml`, `language/oop5/basic.xml` — no statement
  either way.

So no official page found states that an application may define its own
throwable by extending `Error`, and one official page states the opposite.

## Why this is not mine to settle

Three resolutions exist and they are not equivalent:

1. **The manual's note is an imprecision.** `Error` is not `final` and
   extending it produces a throwable class, so the answer key is right about
   the language and the documentation is loose. Resolving this way means
   recording that an official source is wrong — which needs evidence stronger
   than a rendered page, and a decision that this project may say so.
2. **The answer key is wrong** and should read *"By extending Exception"*.
   That changes a scored question's correct answer.
3. **The question is out of scope for its own evidence** and belongs in
   quarantine until a source settles it.

Each is a content or scope decision on a scored question, and §15 reserves an
unresolved source contradiction to the owner. Picking one to keep SRC-5 moving
is exactly the substitution of convenience for evidence that this audit exists
to prevent.

## State

The record is **untouched**. Its citation still carries no anchor, so AUD-03
continues to count it, and `SRC-001` will continue to report it once `SRC-6`
lands. Nothing was written that would make the contradiction harder to see.

**SRC-5 is stopped at 31 of 105 records pending this decision.**
