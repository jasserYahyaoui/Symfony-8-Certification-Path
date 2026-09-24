---
id: CRS-vkgr06y3x72g
official_item: OIT-ff0kghjbzvpm
title: "Set default values to URL parameters"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/routing.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/routing.rst"
    anchor: "optional-parameters"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Routing/RouteCompiler.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/RouteCompiler.php"
    symbol_or_lines: "firstOptional, important"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Routing/Route.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Route.php"
    symbol_or_lines: "extractInlineDefaultsAndRequirements"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Routing/Loader/AttributeClassLoader.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Loader/AttributeClassLoader.php"
    symbol_or_lines: "addRoute"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
---

## Objectif

Rendre un paramètre facultatif, et connaître les deux règles qui en découlent.

## La règle de base

Dès qu'un paramètre apparaît dans le chemin, il **doit** avoir une valeur :
`/blog/{page}` ne correspond pas à `/blog`. Lui donner une valeur par défaut le
rend facultatif.

L'endroit où on l'écrit dépend du format :

| Format | Où |
|---|---|
| attribut PHP | **argument du contrôleur** : `list(int $page = 1)` |
| YAML / PHP | option `defaults` |
| inline | `{page?1}` dans le chemin |

La forme inline se combine avec une contrainte inline :
`/blog/{page<[0-9]+>?1}`. Un `?` sans valeur, `{page?}`, donne la valeur par
défaut `null` : l'argument du contrôleur doit alors être nullable.

### D'où vient le défaut en attributs

`AttributeClassLoader` parcourt les paramètres de la méthode. Pour chacun qui a
une valeur par défaut, **porte le nom d'un paramètre du chemin** et n'a pas déjà
de défaut dans l'option `defaults`, il copie la valeur dans la route. Seules les
valeurs scalaires, `null` et les cas d'énumération adossée — dont il copie la
`value` — sont reprises. Un argument par défaut sans paramètre homonyme dans le
chemin ne rend rien facultatif.

## Tout ce qui suit un paramètre facultatif doit l'être

C'est la contrainte structurelle de l'item. `/blog/{slug}/{page}` accepte
plusieurs paramètres facultatifs, mais **tout ce qui vient après un paramètre
facultatif doit être facultatif aussi**.

La conséquence surprend : `/{page}/blog` est un chemin valide, mais `page` y
sera **toujours obligatoire**, même avec une valeur par défaut — car quelque
chose de non facultatif (`/blog`) le suit. `/blog` ne correspondra pas.

## Le point d'exclamation

Une valeur par défaut placée **en fin de chemin** disparaît de l'URL générée : la
route `blog_list` avec `page = 1` produit `/blog`, pas `/blog/1`. Pour **forcer**
l'inclusion de la valeur par défaut, on préfixe le nom du paramètre par `!` :

```text
/blog/{!page}
```

La documentation n'en décrit que cet effet. Le compilateur de routes en montre
un second : un paramètre marqué `!` est dit *important*, et
`RouteCompiler` ne rend facultative qu'une variable « not important » qui a une
valeur par défaut. Avec `{!page}`, `page` redevient **obligatoire à
l'appariement** : `/blog` ne correspond plus, seul `/blog/1` correspond.

Le `!` agit donc des deux côtés — génération **et** appariement.

## Défaut et contrainte

La valeur par défaut n'a pas à satisfaire la contrainte du paramètre. Elle n'est
pas issue de l'URL, donc elle n'est pas filtrée.

## Pièges d'examen

**Tout ce qui suit un paramètre facultatif doit l'être aussi.** La conséquence
surprend : un chemin qui place le paramètre avant un segment fixe rend ce
paramètre **toujours obligatoire**, valeur par défaut ou non.

**Une valeur par défaut en fin de chemin disparaît de l'URL générée.** L'inclure
de force s'écrit `{!page}`.

**`{!page}` rend aussi le paramètre obligatoire à l'appariement.** Une variable
importante n'est jamais facultative pour le compilateur.

**Un défaut d'argument ne compte que si le chemin porte un paramètre du même
nom.**

**La valeur par défaut n'a pas à satisfaire la contrainte du paramètre.** La
contrainte porte sur ce qui vient de l'URL, pas sur ce que la route fournit
elle-même.

## Points clés

- Un paramètre sans valeur par défaut est obligatoire.
- En attributs, la valeur par défaut est **l'argument du contrôleur**.
- Tout ce qui suit un paramètre facultatif doit être facultatif : `/{page}/blog`
  garde `page` obligatoire.
- `{!page}` force la valeur par défaut dans l'URL générée **et** rend `page`
  obligatoire à l'appariement.
- `{page?}` : défaut `null`, argument nullable.
- La valeur par défaut peut violer la contrainte.

## Sources officielles

- [Routing, section « Optional Parameters »](https://github.com/symfony/symfony-docs/blob/8.0/routing.rst)
- [`RouteCompiler`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/RouteCompiler.php)
- [`AttributeClassLoader`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Loader/AttributeClassLoader.php)
