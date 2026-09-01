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
    anchor: "translation-locale-url"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/routing.rst"
    anchor: "localized-routes-i18n"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
---

## Objectif

Savoir comment le routage fixe la locale de la requête. La négociation
`Accept-Language` appartient au lot HTTP ; la traduction elle-même au lot
Internationalisation.

## Par le paramètre `_locale`

Quand une route contient le paramètre réservé `_locale`, la valeur appariée est
**automatiquement posée sur la requête**. Une visite de `/fr/contact` fixe la
locale `fr`, et `$request->getLocale()` la retourne — sans écrire une ligne.

```yaml
contact:
    path: '/{_locale}/contact'
    controller: App\Controller\ContactController::contact
    requirements:
        _locale: en|fr|de
```

La contrainte gagne à être écrite comme paramètre de configuration, pour éviter
de répéter la liste des locales dans chaque route.

## Par des chemins localisés

Une route peut définir **un chemin par locale**, ce qui évite de dupliquer la
route :

```php
#[Route(path: [
    'en' => '/about-us',
    'nl' => '/over-ons',
    '/about-us',        // repli pour toute autre locale
], name: 'about_us')]
```

Quand une route localisée est appariée, Symfony utilise la même locale pendant
**toute** la requête. En attributs, il faut passer le tableau par le paramètre
nommé `path`.

## Le repli

Si rien n'a déterminé la locale, `framework.default_locale` la fixe. C'est la
garantie qu'une locale existe toujours sur la requête.

## Le piège de l'instant

Appeler `$request->setLocale()` **depuis un contrôleur est trop tard** pour le
traducteur : il a déjà été configuré. La locale se fixe par l'URL, par un
écouteur, ou en appelant `setLocale()` sur le service `translator`.

Un écouteur maison doit s'exécuter **avant** `LocaleListener`, donc porter une
priorité plus élevée — que `debug:event kernel.request` permet de lire.

## Points clés

- `_locale` dans le chemin pose la locale automatiquement.
- Une route peut définir un chemin par locale, avec un repli sans locale.
- `framework.default_locale` est le repli global.
- `setLocale()` dans un contrôleur arrive trop tard ; passer par un écouteur de
  priorité supérieure à `LocaleListener`.

## Sources officielles

- [Translation, « Translating the Locale from the URL »](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/translation.rst)
- [Routing, section « Localized Routes (i18n) »](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/routing.rst)
