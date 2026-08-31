# Official Symfony 8 Certification syllabus (verbatim import)

## Status: PARTIALLY IMPORTED — head of the syllabus is missing

**Source:** <https://certification.symfony.com/exams/symfony.html>
**Imported:** 2026-08-31, supplied by the project owner as text.
**Import fidelity:** verbatim. Wording, ordering and notes are reproduced
exactly as received; nothing has been reworded, merged, split or renamed (§3.1).

> ### ⚠️ This import is incomplete
>
> The supplied text begins mid-item, with the fragment **`resolvers`** — the
> tail of an item belonging to a topic that precedes `Routing`. Everything
> before that fragment is missing.
>
> The published exam covers **15 topics**. Ten are present below. The missing
> topics are those preceding `Routing` in the official ordering, plus the item
> whose tail is the `resolvers` fragment.
>
> **Consequence:** the coverage denominator is *known to be incomplete*. The
> matrix therefore carries `syllabus_complete: false`, and the coverage engine
> refuses to publish a percentage while that flag is false. A percentage
> computed against a partial denominator would overstate readiness precisely
> because it would look correct.
>
> The missing head must be supplied verbatim before any coverage figure is
> meaningful. It is deliberately **not** reconstructed from the execution
> plan's lot descriptions (§3.1 forbids treating them as the denominator).

---

## Topics

> Ordering below reflects the supplied text. `official_topic_order` values in
> the matrix start at 5, leaving 1–4 free for the missing head, so that adding
> it later does not renumber what is already imported.

### (truncated) — trailing fragment

```text
resolvers
```

Recorded, not interpreted. It is the end of an official item whose full wording
and parent topic are unknown, so no matrix entry is created for it.

### Routing

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

### Templating with Twig

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

### Forms

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

### Data Validation

- Validator component
- PHP object validation
- Built-in validation constraints
- Validation scopes
- Validation groups
- Group sequence
- Custom callback validators
- Violations builder

### Dependency Injection

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

### Security

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

### Messenger

- Messenger component
- Transports
- Messages and handlers
- Workers
- Retries and failures
- Middleware
- Events

> Note: third-party transports (Doctrine, Redis, Amazon SQS, etc.) and their
> usage/configuration is not included

### Console

- Console component
- Built-in commands
- Custom commands
- Configuration
- Options and arguments (using PHP attributes)
- Input and Output objects
- Built-in helpers
- Console events
- Verbosity levels

### Automated Tests

- Unit tests with PHPUnit
- Functional tests with PHPUnit
- Client object
- Crawler object (CssSelector and DomCrawler components)
- Profiler object (WebProfiler bundle)
- Framework objects access
- Client configuration
- Request and response objects introspection
- Handling legacy deprecated code

> Note: PHPUnit Bridge is not included

### Miscellaneous

- Configuration (including DotEnv and ExpressionLanguage components)
- Error handling
- Code debugging
- Deployment best practices
- Web Profiler, Web Debug Toolbar and Data collectors
- Internationalization and localization (Note: Intl component utilities to access ICU data are not included)
- HTTP Caching (reverse proxies, expiration, validation) Note: ESI (Edge Side Includes) is not included
- Components:
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

---

## Topics not included in the exam

Reproduced verbatim. These are enforced by
[`exclusions.yml`](exclusions.yml) and CI rule `SCOPE-001`.

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

### The strictest exclusion

> Any Symfony component not explicitly mentioned in the above list

This is an **allow-list, not a deny-list**: a component is out of scope unless
the syllabus names it. The named components are, in full:

Routing, FrameworkBundle, TwigBundle, Form, Validator, DependencyInjection,
Security Core, CSRF, PasswordHasher, Messenger, Console, PHPUnit (as a testing
tool), CssSelector, DomCrawler, WebProfiler bundle, DotEnv, ExpressionLanguage,
Cache, Clock, EventDispatcher, Filesystem, Finder, Event, Mailer, Mime,
Process, PropertyAccess, Runtime, Serializer — plus whatever the missing head
of the syllabus names.

Because that list is incomplete, the allow-list cannot yet be enforced
mechanically: a component named only in the missing head would be wrongly
rejected. `exclusions.yml` therefore records this boundary as review-only
until the full syllabus is present.

## Resolved by this import

**B-5 — Twig version.** The syllabus states *"Twig syntax up to 3.22 version"*
verbatim. The examinable version is settled at **3.22**, notwithstanding that
Symfony 8.0's `composer.json` allows `^3.21|^4.0` and the Twig 3.x branch has
advanced to 3.29. Lot 6 content is scored against 3.22, and any Twig feature
introduced after 3.22 is out of scope.

## Publicly confirmed exam constraints

```text
75 questions
90 minutes
15 topics
English
Symfony 8.0 only
```

Labelled `OFFICIAL_FORMAT` (§7.4). No per-topic weighting is published, so any
internal distribution is `TRAINING_DISTRIBUTION` (§10).
