# Source resolution — `QST-fhrga35d77wa`

```text
DECISION              = KEEP_ANSWER_AND_REPLACE_OR_COMPLETE_SOURCE
ANSWER_KEY            = UNCHANGED  ("By extending Exception or Error")
CURRENT_SOURCE        = INSUFFICIENT_FOR_COMPLETE_CLAIM
MANUAL_DISCREPANCY    = DOCUMENTED
FINAL_CLASSIFICATION  = SOURCE_COMPLETED
```

Decided by the project owner, 2026-09-08. Verified before applying.

## The discrepancy

The PHP manual's `Throwable` page states:

> PHP classes cannot implement the `Throwable` interface directly, and must
> instead extend `Exception`.

That wording is **accurate for the first half** — direct implementation is
rejected, which is what this question's three distractors turn on. It is
**incomplete for the capability under test**: PHP also permits a userland class
to derive from `Error`. The page is therefore insufficient as the *sole* source
for this question.

This is a narrow finding about one page's wording. It is **not** a statement
that the PHP manual is unreliable, and nothing else in this project treats it
that way.

## Primary evidence, in the order the decision required

### 1. PHP 8.4 engine source

`php/php-src`, branch `PHP-8.4`, commit
`0c2fc141fcf9edb13b57b34c3843ed75e24ddcf5`, file `Zend/zend_exceptions.c`,
function `zend_implement_throwable()`:

```c
zend_class_entry *root = class_type;
while (root->parent) {
    root = root->parent;
}
if (zend_string_equals_literal(root->name, "Exception")
        || zend_string_equals_literal(root->name, "Error")) {
    return SUCCESS;
}

bool can_extend = (class_type->ce_flags & ZEND_ACC_ENUM) == 0;

zend_error_noreturn(E_ERROR,
    can_extend
        ? "%s %s cannot implement interface %s, extend Exception or Error instead"
        : "%s %s cannot implement interface %s",
    ...);
```

The engine walks to the root of the inheritance chain and returns `SUCCESS`
only if that root is `Exception` **or** `Error`. Anything else is a fatal
error whose message names **both** bases. The implementation states the answer
key almost verbatim.

Excerpt saved as [`zend_implement_throwable.c`](zend_implement_throwable.c).

### 2. Controlled PHP 8.4 reproduction

[`repro.php`](repro.php), output in [`run-output.txt`](run-output.txt).

| | |
|---|---|
| PHP | 8.4.19 (zend 4.4.19) |
| Binary | `/usr/bin/php` |
| Command | `php -d zend.assertions=1 -d assert.exception=1 …/repro.php` |
| Exit status | **0** |

It asserts, with assertions active:

1. `class ApplicationThrowable extends Error {}` instantiates, is `instanceof
   Error` and `instanceof Throwable`, and can be thrown and caught as
   `Throwable` (identity preserved).
2. The same for a class extending `Exception`.
3. `catch (Exception)` does **not** catch the `Error`-derived one — the sibling
   branches the distractors depend on.
4. `class DirectThrowable implements Throwable {}` is rejected. Run in a child
   process, because this is a compile-time fatal and not catchable. The
   engine's message, captured verbatim:

   > `PHP Fatal error: Class DirectThrowable cannot implement interface
   > Throwable, extend Exception or Error instead`

The reproduction is evidence but **not** the permanent citation: the engine
source above is the stable anchor, exactly as the decision required.

## Stop condition

Not triggered. The engine source and the controlled reproduction both confirm
that a userland class may extend `Error` and be thrown and caught as
`Throwable`. Had they not, the answer key would have been left alone and the
question quarantined as `UNKNOWN_NEEDS_VERIFICATION`.

## What changed on the record

- **Answer key: unchanged.**
- **Explanation** now states the distinction the decision asked for: a
  user-defined class cannot implement `Throwable` directly; it becomes
  throwable by extending `Exception` or `Error`; extending `Exception` is the
  normal application-level approach; `Error` is the separate engine-error
  hierarchy and not the usual base for domain or application exceptions.
- **Sources: `SOURCE_COMPLETED`.** The manual page is **retained**, anchored
  strictly to the half it proves, with its incompleteness stated in the anchor
  itself rather than left for a reader to discover. The engine source is added,
  pinned to a commit and recorded as `commit_sha` and `file`.
- No missing proof was written into an anchor as prose. Every part of the
  answer corresponds to a cited passage or a named source symbol.

## A rule this produced

AUD-02 rejected the pinned citation, because it compared the URL's ref against
the branch named in `source-map.yml` and a commit SHA is not that branch. That
was backwards: V-3 exists precisely because a branch moves, so a commit pin is
the **stronger** anchor. AUD-02 now accepts a 40-hex ref for any declared
repository, and gained two checks so the pin cannot be cosmetic:

| id | Check |
|---|---|
| `CONTAM-8` | a URL pinned to a commit must record that commit in `commit_sha` |
| `CONTAM-9` | a pinned commit must still declare the branch the source map authorises |

Both are proved to fire against injected defects — 8 of 8 checks in
`prove_audits_fail.py`, every fixture file restored byte-identically.
