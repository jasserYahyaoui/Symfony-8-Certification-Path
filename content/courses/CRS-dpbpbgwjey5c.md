---
id: CRS-dpbpbgwjey5c
official_item: OIT-yh6zx9shv9vs
title: "URLs generation"
content_level: MINIMAL
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-24"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/reference/twig_reference.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/reference/twig_reference.rst"
    anchor: "path"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bridge/Twig/Extension/RoutingExtension.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/Extension/RoutingExtension.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "RoutingExtension::getPath(), getUrl(), isUrlGenerationSafe()"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bridge/Twig/Extension/HttpFoundationExtension.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/Extension/HttpFoundationExtension.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "absolute_url, relative_path"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpFoundation/UrlHelper.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/UrlHelper.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "UrlHelper::getAbsoluteUrl()"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Routing/Generator/UrlGenerator.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Generator/UrlGenerator.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "UrlGenerator::doGenerate(), scheme and host widening"
    verified_at: "2026-09-24"
outcome_sections:
  OUT-eqkt5vg15zqf: "Deux functions"
  OUT-5g7zrabez2b4: "Deux functions"
  OUT-b6ffjb05rkmr: "L'escaping"
---

## Objectif

Générer une URL depuis un template. Le generator lui-même et ses quatre types de
référence sont traités dans le lot Routing.

## Deux functions

```html
<a href="{{ path('blog_show', {slug: post.slug}) }}">Lire</a>
<a href="{{ url('blog_show', {slug: post.slug}) }}">Lien absolu</a>
```

| Appel | Type de référence | Résultat |
|---|---|---|
| `path(nom, params)` | `ABSOLUTE_PATH` | `/blog/mon-article` |
| `path(nom, params, true)` | `RELATIVE_PATH` | `blog/mon-article`, relatif à la page courante |
| `url(nom, params)` | `ABSOLUTE_URL` | `https://example.com/blog/mon-article` |
| `url(nom, params, true)` | `NETWORK_PATH` | `//example.com/blog/mon-article` |

Les deux premiers arguments sont communs : le nom de la route, puis un array
de parameters. Le **troisième diffère** : `relative` pour `path()`,
`schemeRelative` pour `url()`. `RoutingExtension` (Twig Bridge 8.0) se borne à
traduire ce bool en type de référence avant d'appeler le generator.

## Ce que le generator décide seul

Le comportement du generator ne change pas parce qu'on l'appelle depuis un
template. `UrlGenerator` peut **élargir** le type demandé :

- une route dont le schéma exigé diffère de celui de la Request courante rend
  une URL absolue, même par `path()` ;
- une route liée à un autre host rend une référence `//hôte/chemin`.

Un parameter absent de la route devient une chaîne de Request, et une route
inconnue lève `RouteNotFoundException`.

## Deux functions voisines, sans route

`HttpFoundationExtension` ajoute deux functions qui prennent un **path**, pas
un route name :

- `absolute_url('/images/logo.png')` rend l'URL absolue du path, sur le host
  courant — la documentation la combine avec `asset()` ;
- `relative_path('http://example.com/human.txt')` rend le path relatif à la
  page courante, ici `../human.txt` depuis `/products/hover-board`.

Sans Request disponible, `absolute_url()` se replie sur le contexte du router,
et rend le path inchangé si aucun host n'y est défini.

## L'escaping

`path()` et `url()` déclarent un `is_safe_callback` : leur output n'est
dispensée d'escaping HTML que si l'appel n'a pas de parameters, ou un seul
parameter à valeur littérale. Sinon elle est échappée comme toute valeur — le
`&` d'une chaîne de Request devient `&amp;`, ce qui est correct dans un
attribute HTML.

Une URL ne s'écrit jamais à la main dans un template : changer le path d'une
route mettrait alors les liens en défaut sans qu'aucun test ne le signale.

## Pièges d'examen

**Le troisième argument n'a pas le même sens dans les deux functions** : path
relatif pour l'une, URL sans schéma pour l'autre.

**La function de path peut retourner une URL absolue**, quand la route exige
un autre schéma — ou une référence `//hôte/…` quand elle est liée à un autre
host.

**Un parameter absent de la route part en chaîne de Request** — une faute de
frappe ne produit aucune erreur.

**`absolute_url()` prend un path, pas un route name.**

## Points clés

- `path()` = path absolu, `url()` = URL absolue ; même nom et mêmes
  parameters.
- Troisième argument `true` : path relatif (`path`), URL sans schéma (`url`).
- Le generator peut élargir la référence : schéma ou host différents.
- `absolute_url()` et `relative_path()` travaillent sur un path.
- Jamais d'URL écrite à la main.

## Sources officielles

- [Symfony Twig Reference, `path`, `url`, `absolute_url`, `relative_path`](https://github.com/symfony/symfony-docs/blob/8.0/reference/twig_reference.rst)
- [Twig Bridge 8.0, `RoutingExtension`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/Extension/RoutingExtension.php) et [`HttpFoundationExtension`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/Extension/HttpFoundationExtension.php)
- [Routing 8.0, `UrlGenerator`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Generator/UrlGenerator.php)
