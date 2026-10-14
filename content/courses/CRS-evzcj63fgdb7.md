---
id: CRS-evzcj63fgdb7
official_item: OIT-xxcpx1qssp93
title: "User's locale guessing"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/translation.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/translation.rst"
    anchor: "translation-locale-url"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/routing.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/routing.rst"
    anchor: "localized-routes-i18n"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpKernel/EventListener/LocaleListener.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/EventListener/LocaleListener.php"
    symbol_or_lines: "getSubscribedEvents, setLocale"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpKernel/EventListener/ResponseListener.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/EventListener/ResponseListener.php"
    symbol_or_lines: "_vary_by_language"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Routing/Loader/AttributeClassLoader.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Loader/AttributeClassLoader.php"
    symbol_or_lines: "addRoute"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Routing/Generator/UrlGenerator.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Generator/UrlGenerator.php"
    symbol_or_lines: "generate"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/Configuration.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/Configuration.php"
    symbol_or_lines: "default_locale, set_locale_from_accept_language, enabled_locales"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
---

## Objectif

Savoir comment le framework fixe la locale de la Request à partir du routage,
et ce que `LocaleListener` fait quand l'URL ne dit rien. La négociation HTTP
détaillée appartient au lot HTTP ; la translation elle-même au lot
Internationalisation.

## Par le parameter `_locale`

Quand une route contient le parameter réservé `_locale`, la valeur appariée est
**automatiquement posée sur la Request**. Une visite de `/fr/contact` fixe la
locale `fr`, et `$request->getLocale()` la retourne — sans écrire une ligne.

```yaml
contact:
    path: '/{_locale}/contact'
    controller: App\Controller\ContactController::contact
    requirements:
        _locale: en|fr|de
```

La constraint gagne à être écrite comme parameter de configuration, pour éviter
de répéter la liste des locales dans chaque route.

Le travail est fait par `LocaleListener`, abonné à `kernel.request` avec la
priorité **16** — après `RouterListener` (32), puisqu'il lit l'attribute
`_locale` que le routage vient de poser. Il recopie aussi cette locale dans le
contexte du router.

## Par des paths localisés

Une route peut définir **un path par locale**, ce qui évite de dupliquer la
route :

```php
#[Route(path: [
    'en' => '/about-us',
    'nl' => '/over-ons',
    '/about-us',        // repli pour toute autre locale
], name: 'about_us')]
```

`AttributeClassLoader` crée une route par locale — `about_us.en`,
`about_us.nl` — avec la default value `_locale`, une constraint sur
`_locale` et une valeur `_canonical_route`. L'entrée sans key devient la route
`about_us` elle-même, sans locale. En attributes, il faut passer l'array par
le parameter nommé `path`.

Quand une route localisée est appariée, Symfony utilise la même locale pendant
**toute** la Request. À la génération, `generate('about_us')` choisit
`about_us.` suivi de la locale courante ; faute de route pour `fr_CA`, il essaie
`fr`, puis la route sans suffixe.

## Quand l'URL ne dit rien

`LocaleListener` décide dans cet ordre :

1. un attribute `_locale` sur la Request l'emporte ;
2. sinon, si `framework.set_locale_from_accept_language` vaut `true`, il
   choisit parmi `framework.enabled_locales` la langue préférée du header
   `Accept-Language`, et marque la Response comme variant selon la langue ;
3. sinon, la locale reste celle par défaut, `framework.default_locale` — `en`
   si rien n'est configuré.

`set_locale_from_accept_language` vaut `false` par défaut : sans lui, le header
`Accept-Language` n'influence pas la locale.

## Le piège de l'instant

Appeler `$request->setLocale()` **depuis un controller est trop tard** pour le
translator : il a déjà été configuré. La locale se fixe par l'URL, par un
listener, ou en appelant `setLocale()` sur le service `translator`.

Un listener maison doit s'exécuter **avant** `LocaleListener`, donc porter une
priorité supérieure à 16 — que `debug:event kernel.request` permet de lire.

## Pièges d'examen

**Fixer la locale depuis un controller arrive trop tard.** Le translator est
déjà configuré : la locale se fixe par l'URL, par un listener, ou sur le service
de translation lui-même.

**Un listener maison doit passer avant celui du framework**, donc porter une
priorité plus élevée — priorité plus grande veut dire plus tôt.

**`Accept-Language` n'agit pas par défaut.** Il faut
`set_locale_from_accept_language: true`, et un `_locale` venu de l'URL passe
toujours devant.

**Une route localisée impose sa locale pour toute la Request**, pas seulement
pour le rendu du template.

## Points clés

- `_locale` dans le path pose la locale, via `LocaleListener` (priorité 16).
- Paths localisés : une route `nom.locale` par locale, et `nom` pour
  l'entrée sans key.
- Ordre : `_locale`, puis `Accept-Language` si activé, puis
  `framework.default_locale`.
- `setLocale()` dans un controller arrive trop tard ; passer par un listener de
  priorité supérieure à 16.

## Sources officielles

- [Translation, « Translating the Locale from the URL »](https://github.com/symfony/symfony-docs/blob/8.0/translation.rst)
- [Routing, section « Localized Routes (i18n) »](https://github.com/symfony/symfony-docs/blob/8.0/routing.rst)
- [`LocaleListener`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/EventListener/LocaleListener.php)
- [`AttributeClassLoader::addRoute()`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Loader/AttributeClassLoader.php) et [`UrlGenerator::generate()`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Generator/UrlGenerator.php)
- [FrameworkBundle, `Configuration`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/Configuration.php)
