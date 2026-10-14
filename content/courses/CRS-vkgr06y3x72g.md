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
outcome_sections:
  OUT-w99f0fjydtp0: "La règle de base"
  OUT-fhjj5c6d002g: "Tout ce qui suit un parameter facultatif doit l'être"
  OUT-qva279gdyyra: "Le point d'exclamation"
  OUT-33d0j3zrrwmz: "Défaut et constraint"
---

## Objectif

Rendre un parameter facultatif, et connaître les deux règles qui en découlent.

## La règle de base

Dès qu'un parameter apparaît dans le path, il **doit** avoir une valeur :
`/blog/{page}` ne correspond pas à `/blog`. Lui donner une default value le
rend facultatif.

L'endroit où on l'écrit dépend du format :

| Format | Où |
|---|---|
| attribute PHP | **argument du controller** : `list(int $page = 1)` |
| YAML / PHP | option `defaults` |
| inline | `{page?1}` dans le path |

La forme inline se combine avec une constraint inline :
`/blog/{page<[0-9]+>?1}`. Un `?` sans valeur, `{page?}`, donne la valeur par
défaut `null` : l'argument du controller doit alors être nullable.

### D'où vient le défaut en attributes

`AttributeClassLoader` parcourt les parameters de la method. Pour chacun qui a
une default value, **porte le nom d'un parameter du path** et n'a pas déjà
de défaut dans l'option `defaults`, il copie la valeur dans la route. Seules les
valeurs scalaires, `null` et les cas d'enum adossée — dont il copie la
`value` — sont reprises. Un argument par défaut sans parameter homonyme dans le
path ne rend rien facultatif.

## Tout ce qui suit un parameter facultatif doit l'être

C'est la constraint structurelle de l'item. `/blog/{slug}/{page}` accepte
plusieurs parameters facultatifs, mais **tout ce qui vient après un parameter
facultatif doit être facultatif aussi**.

La conséquence surprend : `/{page}/blog` est un path valide, mais `page` y
sera **toujours obligatoire**, même avec une default value — car quelque
chose de non facultatif (`/blog`) le suit. `/blog` ne correspondra pas.

## Le point d'exclamation

Une default value placée **en fin de path** disparaît de l'URL générée : la
route `blog_list` avec `page = 1` produit `/blog`, pas `/blog/1`. Pour **forcer**
l'inclusion de la default value, on préfixe le nom du parameter par `!` :

```text
/blog/{!page}
```

La documentation n'en décrit que cet effet. Le compilateur de routes en montre
un second : un parameter marqué `!` est dit *important*, et
`RouteCompiler` ne rend facultative qu'une variable « not important » qui a une
default value. Avec `{!page}`, `page` redevient **obligatoire à
l'appariement** : `/blog` ne correspond plus, seul `/blog/1` correspond.

Le `!` agit donc des deux côtés — génération **et** appariement.

## Défaut et constraint

La default value n'a pas à satisfaire la constraint du parameter. Elle n'est
pas issue de l'URL, donc elle n'est pas filtrée.

## Pièges d'examen

**Tout ce qui suit un parameter facultatif doit l'être aussi.** La conséquence
surprend : un path qui place le parameter avant un segment fixe rend ce
parameter **toujours obligatoire**, default value ou non.

**Une default value en fin de path disparaît de l'URL générée.** L'inclure
de force s'écrit `{!page}`.

**`{!page}` rend aussi le parameter obligatoire à l'appariement.** Une variable
importante n'est jamais facultative pour le compilateur.

**Un défaut d'argument ne compte que si le path porte un parameter du même
nom.**

**La default value n'a pas à satisfaire la constraint du parameter.** La
constraint porte sur ce qui vient de l'URL, pas sur ce que la route fournit
elle-même.

## Points clés

- Un parameter sans default value est obligatoire.
- En attributes, la default value est **l'argument du controller**.
- Tout ce qui suit un parameter facultatif doit être facultatif : `/{page}/blog`
  garde `page` obligatoire.
- `{!page}` force la default value dans l'URL générée **et** rend `page`
  obligatoire à l'appariement.
- `{page?}` : défaut `null`, argument nullable.
- La default value peut violer la constraint.

## Sources officielles

- [Routing, section « Optional Parameters »](https://github.com/symfony/symfony-docs/blob/8.0/routing.rst)
- [`RouteCompiler`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/RouteCompiler.php)
- [`AttributeClassLoader`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Loader/AttributeClassLoader.php)
