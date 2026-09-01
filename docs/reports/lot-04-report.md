# Lot 04 Report — Controllers

**Date:** 2026-09-01
**Branch:** `lot-04-controllers` → Pull Request → `master`
**Plan reference:** Master Plan §14 (Lot 4), §4, §6, §7, §15, §16, §19

---

## Status

`PASS`

## Atomic official items

- **Assigned:** 14 (topic 4, *Controllers*, in full)
- **Previously EXAM_READY:** 0
- **Newly EXAM_READY:** 14
- **Blocked:** 0

```text
new this lot:  +14
cumulative:    30.67%  (50 / 163 EXAM_READY)
```

| # | Item | Level |
|---:|---|---|
| 1 | HttpKernel component and FrameworkBundle | `STANDARD` |
| 2 | Naming conventions | `MINIMAL` |
| 3 | The base AbstractController class | `STANDARD` |
| 4 | The request | `MINIMAL` |
| 5 | The response | `STANDARD` |
| 6 | The cookies | `MINIMAL` |
| 7 | The session | `STANDARD` |
| 8 | The flash messages | `MINIMAL` |
| 9 | HTTP redirects | `MINIMAL` |
| 10 | Internal redirects | `STANDARD` |
| 11 | Generate 404 pages | `STANDARD` |
| 12 | File upload | `STANDARD` |
| 13 | Built-in internal controllers | `STANDARD` |
| 14 | Argument value resolvers | `DEEP` |

## The lot the boundaries were written for

Lot 03 closed with a table of cross-lot boundaries and a warning: they are held
by prose, and the HttpFoundation near-miss showed the failure mode is real. Lot
04 is where that table was cashed in. Five of its fourteen items sit directly on
top of territory another lot already owns.

| Lot 04 item | Already owned by | What Lot 04 kept |
|---|---|---|
| The request | Lot 02 *HTTP request* (the seven bags, `InputBag`), Lot 03 *HttpFoundation component* | how the object reaches a controller: type-hint, route parameters matched **by name**, `getPayload()` |
| The response | Lot 02 *HTTP response* (subclasses, `isRedirect()` vs `isRedirection()`) | what a controller returns and with which helper: `render()` vs `renderView()`, the automatic 422, `json()`, `file()`, `stream()` |
| The cookies | Lot 02 *Cookies* (`SameSite`, `Secure`, `HttpOnly`, deletion constraints) | the request/response asymmetry, and why `setcookie()` is the wrong tool |
| HttpKernel component and FrameworkBundle | Lot 03 *Request handling* (the `handle()` flow) | the division of labour: what the component ships, what the bundle wires |
| Naming conventions | Lot 03 *Naming conventions* (same official wording, distinct item) | controller-side conventions, and that none of them is enforced |

Three of these five landed at `MINIMAL` **because** of the boundary, not in
spite of it. *The cookies* is the clearest case: once Lot 02's attribute table
and deletion rule are excluded, what remains is one asymmetry and one warning —
231 body words. Writing more would have meant writing Lot 02 again.

## Evidence

Every behavioural claim was read from the pinned sources: `symfony/symfony-docs`
at `eea05cbfe063`, `symfony/symfony` at `6f841c00f41e`.

- **`AbstractController` is a service subscriber, not a container holder.** Read
  from the class: it `implements ServiceSubscriberInterface`, `setContainer()`
  carries `#[Required]`, and `getSubscribedServices()` returns eleven entries —
  `router`, `request_stack`, `http_kernel`, `serializer`,
  `security.authorization_checker`, `twig`, `form.factory`,
  `security.token_storage`, `security.csrf.token_manager`, `parameter_bag`,
  `web_link.http_header_serializer` — **each prefixed with `?`**, so each is
  optional. That prefix is why the class works in an application without Twig.
- **Every helper is `protected`.** Not one of the twenty-plus shortcuts is
  public, so none can be called on a controller from outside it.
- **`render()` returns 422 by itself** when an invalid form is among the
  parameters — documented on the method, and true of `renderBlock()` too. The
  first draft of the course claimed the third `Response` argument was the way to
  get a non-200 status from a rendered form; the source says the common case
  needs no argument at all.
- **`json()` uses the Serializer when the service exists** and falls back to
  `json_encode` otherwise; read from the method body, not from prose.
- **`migrate()` vs `invalidate()`**, from `SessionInterface`'s docblocks:
  migrate "migrates the current session to a new session id **while maintaining
  all session attributes**"; invalidate "**clears all session attributes and
  flashes** and regenerates the session".
- **Session start is lazy** — on read, write *or check*. That is what keeps a
  session-free page cacheable.
- **`Kernel implements KernelInterface, RebootableInterface, TerminableInterface`**,
  read from the class declaration. `Kernel` is in the component;
  `MicroKernelTrait` and `AbstractController` are in FrameworkBundle.
- **`resolve()` always returns an array** — empty means "I cannot handle this"
  and passes the chain on; that is not an error.
- **Resolver order is priority order, first value wins.** The documentation uses
  `SessionValueResolver` before `DefaultValueResolver` as the worked example:
  reversing them would make `SessionInterface $session = null` always null.
- **An invalid backed-enum value in a route gives 404**, not 400.
- **`keepRequestMethod: true`** on `RedirectController` swaps 302 → **307** and
  301 → **308**.
- **Security data is unavailable on 404 pages**, because of the order in which
  routing and security load. It works in tests and fails in production.

### Content produced

|  | New this lot | Cumulative |
|---|---:|---:|
| Courses | 14 | 50 |
| Course body words | 4 606 | 18 599 |
| Flashcards | 10 | 37 |
| Questions — LEARNING | 29 | 101 |
| Questions — VALIDATION | 0 | 0 |
| Questions — HOLDOUT | 2 | 9 |
| Questions — English / French | 27 / 4 | 90 / 20 |

Word counts are **body words**, excluding YAML front matter. Two questions per
item, three for *Argument value resolvers* — its three testable layers (the
return contract, the priority chain, the enum 404) do not fit in two.

Ten flashcards cover nine items; five items carry none. The French share of this
lot's bank is 4 of 31, lower than earlier lots. The exam is sat in English (§5),
so the drift is toward the exam language rather than away from a target — there
is no ratio to hold.

### Tests actually executed

```text
php bin/cert validate     16 rules, 163 official items, 110 questions — no violations
php bin/cert coverage     30.67% (50/163)
vendor/bin/phpunit        74 tests, 601 assertions — OK
npm --prefix website run build     SUCCESS
npm --prefix website run a11y      6/6 surfaces PASS, TOTAL VIOLATIONS: 0
```

Holdout isolation checked against the built payload, not assumed: the 9 holdout
question ids are absent from `practice.json` and present in `exam.json`.

## Gates

| Gate | Result |
|---|---|
| **Technical** | **PASS** — validate, phpunit, site build and CI all green |
| **Pedagogical** | **PASS** — every item has outcomes, a justified level, teaching content and ≥2 questions |
| **Accessibility** | **PASS** — `npm --prefix website run a11y`, 6/6 surfaces, 0 violations, run against this lot's build |
| **Content Budget** | **PASS** — 329 body words per item on average (MINIMAL 231–319, STANDARD 293–397, DEEP 632) |

## Level distribution — observation only

New this lot: `MINIMAL` 5, `STANDARD` 8, `DEEP` 1.
Cumulative across the 50 EXAM_READY items: `MINIMAL` 12, `STANDARD` 34, `DEEP` 4.

A **result, not a target**, and worth reading against Lot 03, which came out 3 /
10 / 2. Lot 04 has more `MINIMAL` items and fewer `DEEP` ones — not because
anything was balanced, but because five of its items had most of their surface
already covered elsewhere, and because only one item here is a mechanism with a
scored order. Had the boundaries been ignored, the same fourteen items would
have produced a much larger lot that taught nothing new.

Average body words per item fell from 397 (Lot 03) to 329. That is the boundary
table working, not a drop in quality.

## Remaining risks

- **HOLDOUT confidentiality.** 9 holdout questions are absent from
  `practice.json` and never rendered by the UI, but `exam.json` is published
  with their correct answers — **functional isolation, not confidentiality**.
  Decision required before Lot 27, [ADR-0005](../adr/0005-holdout-distribution-deferred.md).
- **VALIDATION pool still empty** after four lots. Every scored question is
  `LEARNING` or `HOLDOUT`; §7.3 expects three pools. This is now the oldest
  open structural gap in the project.
- **Cross-lot boundaries held by prose.** Twelve of them now. `DUP-001` compares
  normalized prompts only and would not catch a course that re-teaches another
  lot's material.
- **`php/doc-en` has no version branch** — unchanged.

## Recommendation

Proceed to **Lot 05 — Routing** (12 items), of which *Configuration (YAML and
PHP attributes)* is already `EXAM_READY` from the Golden Slice, so 11 remain.
Carry the boundary table forward: Lot 04 owns argument value resolvers and the
`_controller` notation; Lot 05 owns route definition, matching and generation.
