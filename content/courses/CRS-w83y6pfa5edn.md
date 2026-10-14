---
id: CRS-w83y6pfa5edn
official_item: OIT-ds2p5d4eg0pq
title: "Template includes"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-24"
official_sources:
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/tags/include.rst"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/doc/tags/include.rst"
    anchor: "include"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    commit_sha: "5079583d7313b0f0866ca32108036afcc072127d"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/tags/embed.rst"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/doc/tags/embed.rst"
    anchor: "embed"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    commit_sha: "5079583d7313b0f0866ca32108036afcc072127d"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/functions/include.rst"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/doc/functions/include.rst"
    anchor: "include function"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    symbol_or_lines: "with_context, ignore_missing, sandboxed"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/src/Extension/CoreExtension.php"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/src/Extension/CoreExtension.php"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    symbol_or_lines: "CoreExtension::include()"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/src/TokenParser/IncludeTokenParser.php"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/src/TokenParser/IncludeTokenParser.php"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    symbol_or_lines: "IncludeTokenParser::parseArguments()"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/templates.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/templates.rst"
    anchor: "Including Templates"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    symbol_or_lines: "underscore prefix is optional"
    verified_at: "2026-09-24"
outcome_sections:
  OUT-2dk5sjxn9j7s: "Les deux écritures"
  OUT-ng5q8rr0804g: "Le contexte"
  OUT-k04wxdqz7vn1: "Quel template, et s'il manque"
  OUT-kb1neg7efnfp: "`include` ou `embed`"
---

## Objectif

Insérer un template dans un autre, contrôler ce qu'il voit, et choisir entre
`include` et `embed`.

## Les deux écritures

La **function** `include()` est la forme recommandée :

```html
{{ include('article/_card.html.twig', {article: post}) }}
```

Le **tag** `{% include %}` fait la même chose avec une autre syntaxe :

```html
{% include 'article/_card.html.twig' with {article: post} %}
```

La documentation de Twig 3.22 donne trois raisons de préférer la function :
elle est sémantiquement plus juste — un tag ne devrait rien afficher — ;
elle se compose, `{% set content = include('x.html.twig') %}` ou
`{{ include('x.html.twig')|upper }}` ; et ses arguments nommés n'imposent aucun
ordre.

Par convention, un fragment destiné à être inclus est préfixé d'un souligné —
`_card.html.twig` — ce qui le distingue d'un template de page. La documentation
Symfony précise que ce préfixe est facultatif.

## Le contexte

Par défaut, le template inclus **hérite de tout le contexte** du template
appelant, plus les variables qu'on lui passe. Deux mots changent cela :

| Écriture | Effet |
|---|---|
| `{% include 'x.html.twig' only %}` | aucune variable du contexte |
| `{% include 'x.html.twig' with {a: 1} only %}` | seulement `a` |
| `{{ include('x.html.twig', {a: 1}, with_context: false) }}` | idem, en function |

`only` et `with_context: false` sont la même idée dans les deux écritures. Elles
rendent un fragment réutilisable en le forçant à déclarer ses entrées.

## Quel template, et s'il manque

Le nom est une **expression** : une variable, un ternaire
(`ajax ? 'ajax.html.twig' : 'page.html.twig'`), ou une **liste** dont le premier
template existant est inclus.

Un template absent fait échouer l'inclusion, sauf :

- en tag, `ignore missing`, **juste après le nom** — les mots se combinent
  dans cet ordre : `ignore missing with {…} only` ;
- en function, l'argument `ignore_missing: true`, qui rend une string vide.

Avec une liste et `ignore missing`, rien n'est rendu si aucun template n'existe ;
sans, une exception est levée.

Pour un template écrit par un user, la documentation recommande le bac à
sable : `include('page.html.twig', sandboxed: true)` — effectif seulement si
l'extension `SandboxExtension` est enregistrée.

## `include` ou `embed`

`{% embed %}` combine `include` et `extends` : il insère un template **et**
permet de redéfinir ses blocks au point d'insertion.

```html
{% embed 'teaser_skeleton.html.twig' %}
    {% block left %}Contenu de gauche{% endblock %}
{% endembed %}
```

La documentation le décrit comme un « micro squelette de mise en page ». Il
accepte exactement les mêmes arguments que le tag `include` : `with`, `only`,
`ignore missing`. Le critère de choix : si le fragment est identique à chaque
appel, `include` ; s'il faut en changer une partie, `embed`.

Un avertissement de la documentation relie `embed` à l'échappement : un template
embarqué n'a pas de nom, donc la stratégie d'échappement déduite de l'extension
ne s'applique pas comme attendu. Pour embarquer du CSS ou du JavaScript dans du
HTML, on fixe la stratégie avec le tag `autoescape`.

## Pièges d'examen

**Par défaut, le template inclus voit tout le contexte de l'appelant.** Le
fragment marche par accident tant que l'appelant possède la variable, puis
casse ailleurs.

**Restreindre le contexte s'écrit différemment selon la forme employée** — un
mot-clé dans le tag, un argument nommé dans la function.

**Tolérer l'absence aussi** : `ignore missing` en tag, `ignore_missing: true`
en function.

**Une liste de noms n'inclut qu'un template** : le premier qui existe.

## Points clés

- `include()` en function, recommandée ; `{% include %}` en tag.
- Contexte hérité par défaut ; `only` ou `with_context: false` le coupe.
- Nom dynamique ou liste ; `ignore missing` / `ignore_missing: true`.
- `embed` = `include` + redéfinition de blocks, mêmes arguments ; stratégie
  d'échappement à fixer explicitement.

## Sources officielles

- [Twig 3.22, tag `include`](https://github.com/twigphp/Twig/blob/v3.22.0/doc/tags/include.rst) et [function `include`](https://github.com/twigphp/Twig/blob/v3.22.0/doc/functions/include.rst)
- [Twig 3.22, tag `embed`](https://github.com/twigphp/Twig/blob/v3.22.0/doc/tags/embed.rst)
- [Symfony Templates, inclusion de templates](https://github.com/symfony/symfony-docs/blob/8.0/templates.rst)
