---
id: CRS-evzcj63fgdb7
official_item: OIT-xxcpx1qssp93
title: "User's locale guessing"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
format: pedagogical
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
outcome_sections:
  OUT-q1mwf424caew: "Par le parameter `_locale`"
  OUT-7vtxk6rxbjq2: "Par des paths localisés"
  OUT-r3cc9h4e6h3x: "Quand l'URL ne dit rien"
  OUT-qtd7wg0gtvex: "Le piège de l'instant"
---

## Objectif

Savoir comment le framework fixe la locale de la Request à partir du routage,
et ce que `LocaleListener` fait quand l'URL ne dit rien. La négociation HTTP
détaillée appartient au lot HTTP ; la translation elle-même au lot
Internationalisation.

## L'essentiel

La locale de la Request vient de l'URL : un parameter `_locale` dans le path,
ou une route localisée, que `LocaleListener` applique sur `kernel.request` à la
priority **16**. Quand l'URL ne dit rien, il consulte `Accept-Language` —
seulement si on l'a activé — puis retombe sur `framework.default_locale`.
Appeler `setLocale()` depuis un controller arrive **trop tard**.

## Le schéma

```text
kernel.request   RouterListener (32) pose _locale → LocaleListener (16) l'applique + contexte du router
ordre            1. attribute _locale de la Request
                 2. Accept-Language parmi enabled_locales, si set_locale_from_accept_language: true
                    (+ Vary: Accept-Language sur la Response)
                 3. framework.default_locale (en si rien n'est configuré)
paths localisés  ['en' => '/about-us', 'nl' => '/over-ons', '/about-us'] → about_us.en, about_us.nl, about_us
génération       about_us.<locale courante> → sinon la langue seule (fr_CA → fr) → sinon about_us
listener maison  priority > 16 ; setLocale() dans un controller = trop tard pour le translator
```

## L'exemple

```yaml
contact:
    path: '/{_locale}/contact'
    controller: App\Controller\ContactController::contact
    requirements:
        _locale: en|fr|de
```

Une visite de `/fr/contact` fixe la locale `fr` sans une ligne de code.

## Par le parameter `_locale`

La valeur appariée est **automatiquement posée sur la Request** :
`$request->getLocale()` la retourne. Le requirement gagne à être un parameter de
configuration, pour ne pas répéter la liste des locales. `LocaleListener`
écoute `kernel.request` à la priority **16**, après `RouterListener` (32) dont
il lit l'attribute, et recopie la locale dans le contexte du router.

## Par des paths localisés

Une route peut définir **un path par locale** :

```php
#[Route(path: [
    'en' => '/about-us',
    'nl' => '/over-ons',
    '/about-us',        // repli pour toute autre locale
], name: 'about_us')]
```

`AttributeClassLoader` crée `about_us.en` et `about_us.nl`, chacune avec la
default value `_locale`, un requirement et `_canonical_route` ; l'entrée sans
key devient `about_us`, sans locale. En attributes, l'array passe par le
parameter nommé `path`. Une route localisée appariée impose sa locale pour
**toute** la Request. À la génération, `generate('about_us')` vise la route de
la locale courante ; pour `fr_CA`, il essaie `fr`, puis la route sans suffixe.

## Quand l'URL ne dit rien

`LocaleListener` décide dans cet ordre : un attribute `_locale` l'emporte ;
sinon, si `framework.set_locale_from_accept_language` vaut `true`, la langue
préférée du header `Accept-Language` parmi `framework.enabled_locales`, et la
Response reçoit `Vary: Accept-Language` ; sinon `framework.default_locale`,
`en` par défaut. L'option vaut `false` par défaut : sans elle, le header ne
compte pas.

## Le piège de l'instant

`$request->setLocale()` dans un controller arrive **trop tard** : le translator
est déjà configuré. La locale se fixe par l'URL, par un listener, ou par
`setLocale()` sur le service `translator`. Un listener maison doit passer avant
`LocaleListener`, donc porter une priority supérieure à 16 — lisible par
`debug:event kernel.request`.

## Pièges d'examen

**Fixer la locale depuis un controller arrive trop tard.** Le translator est
déjà configuré : la locale se fixe par l'URL, par un listener, ou sur le service
de translation lui-même.

**Un listener maison doit passer avant celui du framework**, donc porter une
priority plus élevée — priority plus grande veut dire plus tôt.

**`Accept-Language` n'agit pas par défaut.** Il faut
`set_locale_from_accept_language: true`, et un `_locale` venu de l'URL passe
toujours devant.

**Une route localisée impose sa locale pour toute la Request**, pas seulement
pour le rendu du template.

## Application

- Une préférence de langue gardée en session → listener `kernel.request` de
  priority supérieure à 16.
- Proposer la langue du navigateur → `set_locale_from_accept_language: true` et
  `enabled_locales`.

## QCM d'entraînement

**Question 1.** `set_locale_from_accept_language` vaut `true`. L'URL porte
`_locale` à `fr`, et le navigateur préfère `de`. Quelle locale obtient la
Request ?

- **A.** `de`, la préférence du navigateur
- **B.** `fr`
- **C.** `en`, le défaut
- **D.** Une exception : deux sources

<details>
<summary>Correction</summary>

**Bonne réponse : B.** L'attribute venu de l'URL est examiné en premier ; le
header n'est consulté que lorsque cet attribute est absent.

</details>

**Question 2.** La route `about_us` a les paths `en`, `fr` (`/a-propos`) et un
repli `/about-us`. La locale courante est `fr_CA`. Que rend
`generate('about_us')` ?

- **A.** `/about-us`, le repli sans key
- **B.** Une `RouteNotFoundException`
- **C.** `/a-propos`
- **D.** `/fr_CA/about-us`

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Faute de route pour `fr_CA`, le generator essaie la
langue seule, `fr`, avant de se rabattre sur la route sans suffixe.

</details>

**Question 3.** La locale est choisie d'après `Accept-Language`. Quel effet
cela a-t-il sur la Response ?

- **A.** Aucun
- **B.** Un header `Content-Language`
- **C.** Un cookie qui mémorise la locale choisie
- **D.** Un header `Vary: Accept-Language`

<details>
<summary>Correction</summary>

**Bonne réponse : D.** `LocaleListener` marque la Request, et le listener de
Response ajoute ce `Vary` pour qu'un cache distingue les langues.

</details>

**Question 4.** Un listener de `kernel.request`, de priority 20, appelle
`$request->setLocale('de')`. L'URL n'a pas de `_locale` et l'option
`Accept-Language` est désactivée. Quelle locale finale ?

- **A.** `de`
- **B.** `en`, le défaut l'écrase
- **C.** `null`
- **D.** Une exception

<details>
<summary>Correction</summary>

**Bonne réponse : A.** `LocaleListener`, à 16, ne touche à la locale que s'il
trouve `_locale` ou le header activé ; la valeur posée plus tôt reste.

</details>

## Fiche express

**Si tu dois retenir seulement 6 choses :**

- `_locale` dans le path → locale posée par **`LocaleListener` (16)**.
- Après **`RouterListener` (32)**, dont il lit l'attribute.
- Ordre : **`_locale`** → `Accept-Language` **si activé** → **`default_locale`**.
- Paths localisés : **`nom.locale`** + `nom` pour l'entrée sans key.
- Génération : locale → **langue seule** → route sans suffixe.
- `setLocale()` en controller : **trop tard** ; listener de priority **> 16**.

Moyen mnémotechnique : **« L'URL parle d'abord, le navigateur s'il est invité »**.

## Sources officielles

- [Translation, « Translating the Locale from the URL »](https://github.com/symfony/symfony-docs/blob/8.0/translation.rst)
- [Routing, section « Localized Routes (i18n) »](https://github.com/symfony/symfony-docs/blob/8.0/routing.rst)
- [`LocaleListener`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/EventListener/LocaleListener.php)
- [`AttributeClassLoader::addRoute()`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Loader/AttributeClassLoader.php) et [`UrlGenerator::generate()`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Generator/UrlGenerator.php)
- [FrameworkBundle, `Configuration`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/Configuration.php)

