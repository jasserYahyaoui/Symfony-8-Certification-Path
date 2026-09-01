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
    anchor: "optional-parameters"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
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
`/blog/{page<[0-9]+>?1}`.

## Tout ce qui suit un paramètre facultatif doit l'être

C'est la contrainte structurelle de l'item. `/blog/{slug}/{page}` accepte
plusieurs paramètres facultatifs, mais **tout ce qui vient après un paramètre
facultatif doit être facultatif aussi**.

La conséquence surprend : `/{page}/blog` est un chemin valide, mais `page` y
sera **toujours obligatoire**, même avec une valeur par défaut — car quelque
chose de non facultatif (`/blog`) le suit. `/blog` ne correspondra pas.

## Le point d'exclamation

Une valeur par défaut disparaît de l'URL générée : la route `blog_list` avec
`page = 1` produit `/blog`, pas `/blog/1`. Pour **forcer** l'inclusion de la
valeur par défaut dans l'URL générée, on préfixe le nom du paramètre par `!` :

```text
/blog/{!page}
```

C'est une décision de génération d'URL, pas d'appariement.

## Défaut et contrainte

La valeur par défaut n'a pas à satisfaire la contrainte du paramètre. Elle n'est
pas issue de l'URL, donc elle n'est pas filtrée.

## Points clés

- Un paramètre sans valeur par défaut est obligatoire.
- En attributs, la valeur par défaut est **l'argument du contrôleur**.
- Tout ce qui suit un paramètre facultatif doit être facultatif : `/{page}/blog`
  garde `page` obligatoire.
- `{!page}` force la valeur par défaut dans l'URL générée.
- La valeur par défaut peut violer la contrainte.

## Sources officielles

- [Routing, section « Optional Parameters »](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/routing.rst)
