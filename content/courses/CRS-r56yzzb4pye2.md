---
id: CRS-r56yzzb4pye2
official_item: OIT-dp7w7s85wxjg
title: "Global variables"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/templates.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/templates.rst"
    anchor: "the-app-global-variable"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bridge/Twig/AppVariable.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/AppVariable.php"
    symbol_or_lines: "getUser, getSession, getFlashes, getCurrent_route"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/TwigBundle/Resources/config/twig.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/TwigBundle/Resources/config/twig.php"
    symbol_or_lines: "twig.app_variable"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/templates.rst"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/doc/templates.rst"
    symbol_or_lines: "Global Variables"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/advanced.rst"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/doc/advanced.rst"
    symbol_or_lines: "Globals"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    verified_at: "2026-09-24"
outcome_sections:
  OUT-r9kjw1fha5yx: "La variable `app`"
  OUT-5z9qxkyjy0yc: "La variable `app`"
  OUT-thnmr95cr5f4: "Déclarer ses propres globales"
  OUT-zn59vbr2pzhs: "Le piège"
---

## Objectif

Connaître les variables disponibles dans **tous** les templates sans qu'un
controller les passe, et savoir en déclarer.

## Les trois globales de Twig lui-même

Twig 3.22 fournit toujours trois variables, avant même Symfony :

| Variable | Contenu |
|---|---|
| `_self` | le nom du template courant |
| `_context` | le contexte courant, c'est-à-dire toutes les variables visibles |
| `_charset` | le jeu de caractères courant |

## La variable `app`

TwigBundle expose une variable `app` dans chaque template — un object
`AppVariable` du bridge Twig. C'est la voie normale d'accès à l'état de la
Request depuis un template.

| Property | Contenu |
|---|---|
| `app.user` | le user courant, ou `null` s'il n'est pas authentifié |
| `app.request` | l'object `Request` courant |
| `app.session` | la session, ou `null` si la Request n'en a pas |
| `app.flashes` | les flash messages ; `app.flashes('notice')` filter par type |
| `app.environment` | `dev`, `prod`… |
| `app.debug` | le mode debug |
| `app.token` | le token de sécurité |
| `app.current_route` | le nom de la route courante |
| `app.current_route_parameters` | ses parameters |
| `app.locale` | la locale courante |
| `app.enabled_locales` | les locales activées |

Trois détails lus dans `AppVariable` (8.0) :

- `app.current_route` lit l'attribute `_route` de la Request courante, et
  `app.current_route_parameters` l'attribute `_route_params` — un array vide
  s'il manque ;
- `app.flashes` accepte aussi une liste de types et rend alors un array
  `type => messages` ; sans session compatible, il rend un array vide ;
- une property dont le service manque **lève une `RuntimeException`** :
  `app.user` et `app.token` sans le component Security installé, par exemple —
  « The "app.user" variable is not available. » Seul `app.flashes` retombe sur
  un array vide.

`app.session` peut valoir `null`. Et lire son contenu — `app.flashes`, par
exemple — démarre la session, avec les conséquences que cela a sur la
cachabilité de la page.

## Déclarer ses propres globales

```yaml
# config/packages/twig.yaml
twig:
    globals:
        ga_tracking: 'UA-xxxxx-x'
        mailer: '@app.mailer'
```

La valeur devient `{{ ga_tracking }}` partout, sans qu'aucun controller ne la
passe ; une valeur qui commence par `@` désigne un service. Hors configuration,
une extension Twig peut en déclarer par `GlobalsInterface::getGlobals()`, et
Twig seul par `Environment::addGlobal()`.

La documentation de Twig le précise : les globales sont disponibles dans tous
les templates **et dans les macros**, qui ne voient pourtant pas les variables du
template qui les appelle. C'est adapté à une constant d'application ; ce n'est
pas un substitut au passage de variables, qui reste la voie normale pour les
données d'une page.

## Le piège

`app.flashes` **consomme** les messages, comme toute lecture du sac de flashs.
Les afficher deux fois dans une page en fait disparaître la moitié ; la lecture
non consommatrice est `app.session.flashbag.peekAll()`.

## Pièges d'examen

**La session vue depuis un template peut être `null`** — et lire son contenu la
démarre, avec l'effet que cela a sur la cachabilité de la page.

**Le user courant vaut `null` quand personne n'est authentifié.** Lire
`app.user.email` lève alors une `RuntimeError` — « Impossible to access an
attribute ("email") on a null variable » — en mode strict, actif en `dev` par
défaut, et rend `null` sinon. Et `app.user` lève une exception si le component
Security n'est pas installé.

**`_self`, `_context`, `_charset` existent sans Symfony.** Ce sont les globales
de Twig ; `app` est celle de Symfony.

**La forme courte de la route courante existe.** Traverser les attributes de la
Request pour l'obtenir fonctionne, mais c'est le path long de la même chose.

## Points clés

- Twig : `_self`, `_context`, `_charset` ; Symfony ajoute `app`, onze
  properties.
- `app.current_route` lit l'attribute `_route` ; `app.flashes` consomme.
- Service absent : `RuntimeException`, sauf pour `app.flashes`.
- `twig.globals` déclare une globale, `@id` pour un service ; les globales sont
  visibles dans les macros.

## Sources officielles

- [Symfony Templates, « The App Global Variable »](https://github.com/symfony/symfony-docs/blob/8.0/templates.rst)
- [Twig Bridge, `AppVariable`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/AppVariable.php)
- [Twig 3.22, *Global Variables*](https://github.com/twigphp/Twig/blob/v3.22.0/doc/templates.rst) et [*Globals*](https://github.com/twigphp/Twig/blob/v3.22.0/doc/advanced.rst)
