# Official Symfony 8 Certification syllabus (verbatim import)

## Status: COMPLETE

**Source:** <https://certification.symfony.com/exams/symfony.html>
**Artifact:** official exam page PDF, supplied by the project owner 2026-09-01.
**Import fidelity:** verbatim. Every item below was verified to appear
character-for-character in the source PDF text (163 of 163).

### Normalisation applied

Two mechanical transformations, neither of which changes wording:

1. **Line wraps rejoined.** The PDF lays topics out in narrow columns, so items
   such as *"Release management and roadmap schedule"* and *"HttpKernel
   component and FrameworkBundle"* are split across two rendered lines.
2. **F-ligatures normalised.** The PDF fonts encode `fi`, `fl`, `ffi` as single
   ligature glyphs, which extract as `ﬁ`, `ﬂ`, `ﬃ`. These are a rendering
   artefact, not the official spelling, and are restored to plain letters —
   `Conﬁguration` → `Configuration`.

Nothing else was altered: no item reworded, merged, split, renamed or reordered.

### A recorded discrepancy: 15 topics or 14?

The exam page states **"15 topics"** while enumerating **14** topic headings.

The 2026-09-03 independent audit settled which lines are headings, by
measurement rather than by plausibility. Extracted text cannot tell a heading
from an item, and two readings of the missing topic are both internally
consistent — either `Object Oriented Programming` or `Components:` could be the
fifteenth, and each closes the arithmetic. Font size and left edge decide it:

| | Font size | Left edge |
|---|---|---|
| Topic heading | **17.0** | 19.2 (left column) / 382.0 (right) |
| Item | 16.0 | 51.2 / 414.0 |
| Sub-item | 16.0 | **83.2 / 446.0** |

Exactly **14** runs in the document measure 17.0. `Object Oriented Programming`
measures 16.0 at x=51.2 — an **item** of *PHP*, as imported here. `Components:`
also measures 16.0 at x=51.2 and is the only item-level line in the document
with children: its twelve components are the only lines at the deeper
83.2/446.0 indent, which is why they are imported as twelve atomic items and
the label itself is not one.

### Three facts, kept together

| | |
|---|---|
| **Published constraint** | **15 topics** — stated by the exam page, and binding |
| **Measured presentation** | **14** first-level headings at font size 17.0 |
| **Item mapping** | **163 / 163** official items mapped, 0 missing, 0 unexpected |

The typography measures how the page is *rendered*. It does not amend what the
page *states*. **The official constraint remains 15 topics**, and every
downstream use — the exam blueprint, any per-topic distribution for the mocks —
takes 15 from the published constraint, never 14 from the layout.

**Interpretation, not a demonstrated fact.** `Components:` is the likeliest
candidate for the fifteenth topic: it is the only item-level line with children
of its own, which sets it apart from every other item even though it does not
carry the 17.0 heading presentation. That is an inference about the exam page's
own bookkeeping. The document states 15 and renders 14, and names no fifteenth
heading; nothing here resolves that, and nothing needs to.

The discrepancy is in the source and stays recorded rather than reconciled,
per §2.5.

It affects no coverage figure: coverage is computed from atomic items (§3.5),
never from topic counts. **Master Plan §22 clause 2 is unaffected** — it turns
on whether every official item is represented, and all 163 are, with faithful
wording, no omission and no addition. A recorded discrepancy in how the source
counts its own headings is not a syllabus gap.

Evidence and method: [`docs/audit/lot-27-syllabus-gate/README.md`](../audit/lot-27-syllabus-gate/README.md),
with the full mapping in `coverage-mapping.csv` alongside it.

### Exam constraints

```text
75 questions
90 minutes
15 topics
English
Symfony 8.0 only
```

`OFFICIAL_FORMAT` (§7.4). The page additionally states:

> NOTE: The Symfony 8 Certification exam only includes questions about
> Symfony 8.0 and not about Symfony 8.1, 8.2, 8.3 and 8.4 versions.

No per-topic weighting is published, so any internal distribution is
`TRAINING_DISTRIBUTION` (§10).

---

## Topics


### 1. PHP

*9 atomic items — delivered in lot-01*

- PHP API up to PHP 8.4 version
- Object Oriented Programming
- Attributes
- Interfaces
- Anonymous functions and closures
- Abstract classes
- Exception and error handling
- Traits
- Enums

> Note: PHP Polyfills are not included.

### 2. HTTP

*10 atomic items — delivered in lot-02*

- HTTP Specification (RFC 9110)
- Status codes
- HTTP request
- HTTP response
- HTTP methods
- Cookies
- Caching
- Content negotiation
- Language detection
- Symfony HttpClient component

> Note: ESI (Edge Side Includes) is not included.

### 3. Symfony Architecture

*15 atomic items — delivered in lot-03*

- HttpFoundation component
- Symfony Flex
- License
- Components and Bridges
- Code organization
- Request handling
- Exception handling
- Event dispatcher and kernel events
- Official best practices
- Backward compatibility promise
- Deprecations best practices
- Framework overloading
- Release management and roadmap schedule
- Framework interoperability and PSRs
- Naming conventions

> Note: Any Symfony component not explicitly named by the syllabus is out of scope, as is any bridge to a third-party service.

### 4. Controllers

*14 atomic items — delivered in lot-04*

- HttpKernel component and FrameworkBundle
- Naming conventions
- The base AbstractController class
- The request
- The response
- The cookies
- The session
- The flash messages
- HTTP redirects
- Internal redirects
- Generate 404 pages
- File upload
- Built-in internal controllers
- Argument value resolvers

### 5. Routing

*12 atomic items — delivered in lot-05*

- Routing component and FrameworkBundle
- Configuration (YAML and PHP attributes)
- Restrict URL parameters
- Set default values to URL parameters
- URLs generation
- Trigger redirects
- Special internal routing attributes
- Domain name matching
- Conditional request matching
- HTTP methods matching
- User's locale guessing
- Router debugging

### 6. Templating with Twig

*14 atomic items — delivered in lot-06*

- TwigBundle
- Twig syntax up to 3.22 version
- Auto escaping
- Template inheritance
- Global variables
- Filters and functions
- Template includes
- Loops and conditions
- URLs generation
- Controller rendering
- Translations and pluralization
- String interpolation
- Assets management
- Debugging variables

> Note: Twig features introduced after 3.22 are out of scope.

### 7. Forms

*13 atomic items — delivered in lot-07*

- Form component
- Forms creation
- Forms handling
- Form types (built-in and custom)
- Forms rendering with Twig
- Forms theming
- CSRF protection
- Handling file upload
- Built-in form types
- Data transformers
- Form events
- Form type extensions
- Form options (OptionsResolver component)

### 8. Data Validation

*8 atomic items — delivered in lot-08*

- Validator component
- PHP object validation
- Built-in validation constraints
- Validation scopes
- Validation groups
- Group sequence
- Custom callback validators
- Violations builder

### 9. Dependency Injection

*12 atomic items — delivered in lot-09*

- Dependency Injection component
- Service container
- Built-in services
- Configuration parameters
- Services registration (YAML and PHP attributes)
- Service decoration
- Tags
- Semantic configuration
- Factories
- Compiler passes
- Services autowiring
- Service locators

### 10. Security

*12 atomic items — delivered in lot-10*

- Security Core, CSRF and PasswordHasher components
- Authentication
- Authorization
- Configuration
- Providers
- Firewalls
- Users
- Password hashers
- Roles
- Access Control Rules
- Authenticators, Passports and Badges
- Voters and voting strategies

### 11. Messenger

*7 atomic items — delivered in lot-11*

- Messenger component
- Transports
- Messages and handlers
- Workers
- Retries and failures
- Middleware
- Events

> Note: Third-party transports (Doctrine, Redis, Amazon SQS, etc.) and their usage/configuration are not included.
> Note: Third-party transports and their usage/configuration are not included.

### 12. Console

*9 atomic items — delivered in lot-12*

- Console component
- Built-in commands
- Custom commands
- Configuration
- Options and arguments (using PHP attributes)
- Input and Output objects
- Built-in helpers
- Console events
- Verbosity levels

### 13. Automated Tests

*9 atomic items — delivered in lot-13*

- Unit tests with PHPUnit
- Functional tests with PHPUnit
- Client object
- Crawler object (CssSelector and DomCrawler components)
- Profiler object (WebProfiler bundle)
- Framework objects access
- Client configuration
- Request and response objects introspection
- Handling legacy deprecated code

> Note: PHPUnit Bridge is not included.

### 14. Miscellaneous

*19 atomic items — delivered in lot-14, lot-15, lot-16, lot-17, lot-18, lot-19, lot-20, lot-21, lot-22, lot-23, lot-24, lot-25, lot-26*

- Configuration (including DotEnv and ExpressionLanguage components)
- Error handling
- Code debugging
- Deployment best practices
- Web Profiler, Web Debug Toolbar and Data collectors
- Internationalization and localization (Note: Intl component utilities to access ICU data are not included)
- HTTP Caching (reverse proxies, expiration, validation) Note: ESI (Edge Side Includes) is not included
- Cache
- Clock
- EventDispatcher
- Filesystem
- Finder
- Event
- Mailer
- Mime
- Process
- PropertyAccess
- Runtime
- Serializer

> Note: Bridges to third-party services are not included.
> Note: ESI (Edge Side Includes) is not included.
> Note: Intl component utilities to access ICU data are not included.

---

**Total: 163 atomic official items across 14 enumerated topics.**

---

## Topics not included in the exam

Reproduced verbatim; enforced by [`exclusions.yml`](exclusions.yml) and CI rule
`SCOPE-001`.

> The following is a list of the topics not included in the exam:
>
> - Symfony UX
> - Symfony AI
> - Doctrine and database-related topics
> - Monolog
> - Third-party bundles and projects
> - AssetMapper and Webpack Encore
> - PHP Polyfills
> - Any Symfony component not explicitly mentioned in the above list (e.g. String, Uid, TypeInfo, Lock, etc.)
> - Any bridge to third-party services in any component (Mailer, Messenger, Translation, etc.)

### The strictest exclusion is an allow-list

> Any Symfony component not explicitly mentioned in the above list

A component is out of scope **unless the syllabus names it**. Now that the
import is complete this can finally be enforced, because the set of named
components is closed. Components named by the syllabus, in full:

HttpFoundation, HttpKernel, FrameworkBundle, HttpClient, Routing, TwigBundle,
Form, OptionsResolver, Validator, DependencyInjection, Security Core, CSRF,
PasswordHasher, Messenger, Console, CssSelector, DomCrawler, WebProfiler
bundle, DotEnv, ExpressionLanguage, Cache, Clock, EventDispatcher, Filesystem,
Finder, Event, Mailer, Mime, Process, PropertyAccess, Runtime, Serializer.

Anything outside that list — `String`, `Uid`, `TypeInfo`, `Lock` and every
other component — is out of scope and may never be required to earn a point.

## Settled by this import

**B-1 — syllabus access.** Closed. All 14 enumerated topics and 163 atomic
items imported and verified against the source.

**B-5 — Twig version.** Closed. The syllabus states *"Twig syntax up to 3.22
version"*, settling the examinable version at **3.22** despite Symfony 8.0
allowing `^3.21|^4.0` and the Twig 3.x branch having reached 3.29.

**Lot 01–04.** The truncated first import began at `resolvers`; the complete
PDF confirms this is the tail of *"Argument value resolvers"*, the last item of
**Controllers**. See [ADR-0004](../adr/0004-lot-numbering.md).

