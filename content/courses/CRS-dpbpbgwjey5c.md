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
---

## Objectif

Générer une URL depuis un gabarit. Le générateur lui-même et ses quatre types de
référence sont traités dans le lot Routing.

## Deux fonctions

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

Les deux premiers arguments sont communs : le nom de la route, puis un tableau
de paramètres. Le **troisième diffère** : `relative` pour `path()`,
`schemeRelative` pour `url()`. `RoutingExtension` (Twig Bridge 8.0) se borne à
traduire ce booléen en type de référence avant d'appeler le générateur.

## Ce que le générateur décide seul

Le comportement du générateur ne change pas parce qu'on l'appelle depuis un
gabarit. `UrlGenerator` peut **élargir** le type demandé :

- une route dont le schéma exigé diffère de celui de la requête courante rend
  une URL absolue, même par `path()` ;
- une route liée à un autre hôte rend une référence `//hôte/chemin`.

Un paramètre absent de la route devient une chaîne de requête, et une route
inconnue lève `RouteNotFoundException`.

## Deux fonctions voisines, sans route

`HttpFoundationExtension` ajoute deux fonctions qui prennent un **chemin**, pas
un nom de route :

- `absolute_url('/images/logo.png')` rend l'URL absolue du chemin, sur l'hôte
  courant — la documentation la combine avec `asset()` ;
- `relative_path('http://example.com/human.txt')` rend le chemin relatif à la
  page courante, ici `../human.txt` depuis `/products/hover-board`.

Sans requête disponible, `absolute_url()` se replie sur le contexte du routeur,
et rend le chemin inchangé si aucun hôte n'y est défini.

## L'échappement

`path()` et `url()` déclarent un `is_safe_callback` : leur sortie n'est
dispensée d'échappement HTML que si l'appel n'a pas de paramètres, ou un seul
paramètre à valeur littérale. Sinon elle est échappée comme toute valeur — le
`&` d'une chaîne de requête devient `&amp;`, ce qui est correct dans un
attribut HTML.

Une URL ne s'écrit jamais à la main dans un gabarit : changer le chemin d'une
route mettrait alors les liens en défaut sans qu'aucun test ne le signale.

## Pièges d'examen

**Le troisième argument n'a pas le même sens dans les deux fonctions** : chemin
relatif pour l'une, URL sans schéma pour l'autre.

**La fonction de chemin peut retourner une URL absolue**, quand la route exige
un autre schéma — ou une référence `//hôte/…` quand elle est liée à un autre
hôte.

**Un paramètre absent de la route part en chaîne de requête** — une faute de
frappe ne produit aucune erreur.

**`absolute_url()` prend un chemin, pas un nom de route.**

## Points clés

- `path()` = chemin absolu, `url()` = URL absolue ; même nom et mêmes
  paramètres.
- Troisième argument `true` : chemin relatif (`path`), URL sans schéma (`url`).
- Le générateur peut élargir la référence : schéma ou hôte différents.
- `absolute_url()` et `relative_path()` travaillent sur un chemin.
- Jamais d'URL écrite à la main.

## Sources officielles

- [Symfony Twig Reference, `path`, `url`, `absolute_url`, `relative_path`](https://github.com/symfony/symfony-docs/blob/8.0/reference/twig_reference.rst)
- [Twig Bridge 8.0, `RoutingExtension`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/Extension/RoutingExtension.php) et [`HttpFoundationExtension`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/Extension/HttpFoundationExtension.php)
- [Routing 8.0, `UrlGenerator`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Generator/UrlGenerator.php)
