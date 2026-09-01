---
id: CRS-w83y6pfa5edn
official_item: OIT-ds2p5d4eg0pq
title: "Template includes"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/tags/include.rst"
    anchor: "include"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    commit_sha: "5079583d7313b0f0866ca32108036afcc072127d"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/tags/embed.rst"
    anchor: "embed"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    commit_sha: "5079583d7313b0f0866ca32108036afcc072127d"
    verified_at: "2026-09-01"
---

## Objectif

Insérer un gabarit dans un autre, contrôler ce qu'il voit, et choisir entre
`include` et `embed`.

## Les deux écritures

La **fonction** `include()` est la forme recommandée, parce qu'elle s'utilise
dans une expression :

```html
{{ include('article/_card.html.twig', {article: post}) }}
```

La **balise** `{% include %}` fait la même chose avec une autre syntaxe :

```html
{% include 'article/_card.html.twig' with {article: post} %}
```

Par convention, un fragment destiné à être inclus est préfixé d'un souligné —
`_card.html.twig` — ce qui le distingue d'un gabarit de page.

## Le contexte

Par défaut, le gabarit inclus **hérite de tout le contexte** du gabarit
appelant, plus les variables qu'on lui passe. Deux mots changent cela :

| Écriture | Effet |
|---|---|
| `{% include 'x.html.twig' only %}` | aucune variable du contexte |
| `{% include 'x.html.twig' with {a: 1} only %}` | seulement `a` |
| `{{ include('x.html.twig', {a: 1}, with_context: false) }}` | idem, en fonction |

`only` et `with_context: false` sont la même idée dans les deux écritures. Elles
rendent un fragment réutilisable en le forçant à déclarer ses entrées.

## Gabarit absent

`{% include 'sidebar.html.twig' ignore missing %}` n'échoue pas si le fichier
n'existe pas. Les mots se combinent, dans cet ordre :
`ignore missing with {…} only`.

## `include` ou `embed`

`{% embed %}` combine `include` et `extends` : il insère un gabarit **et**
permet de redéfinir ses blocs au point d'insertion.

```html
{% embed 'teaser_skeleton.html.twig' %}
    {% block left %}Contenu de gauche{% endblock %}
{% endembed %}
```

La documentation le décrit comme un « micro squelette de mise en page ». Le
critère de choix : si le fragment est identique à chaque appel, `include` ; s'il
faut en changer une partie, `embed`.

## Points clés

- `include()` en fonction, `{% include %}` en balise ; même mécanisme.
- Le contexte est hérité par défaut ; `only` ou `with_context: false` le coupe.
- `ignore missing` tolère un gabarit absent.
- `embed` = `include` + redéfinition de blocs.

## Sources officielles

- [Twig 3.22, balise `include`](https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/tags/include.rst)
- [Twig 3.22, balise `embed`](https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/tags/embed.rst)
