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
---

## Objectif

Insérer un gabarit dans un autre, contrôler ce qu'il voit, et choisir entre
`include` et `embed`.

## Les deux écritures

La **fonction** `include()` est la forme recommandée :

```html
{{ include('article/_card.html.twig', {article: post}) }}
```

La **balise** `{% include %}` fait la même chose avec une autre syntaxe :

```html
{% include 'article/_card.html.twig' with {article: post} %}
```

La documentation de Twig 3.22 donne trois raisons de préférer la fonction :
elle est sémantiquement plus juste — une balise ne devrait rien afficher — ;
elle se compose, `{% set content = include('x.html.twig') %}` ou
`{{ include('x.html.twig')|upper }}` ; et ses arguments nommés n'imposent aucun
ordre.

Par convention, un fragment destiné à être inclus est préfixé d'un souligné —
`_card.html.twig` — ce qui le distingue d'un gabarit de page. La documentation
Symfony précise que ce préfixe est facultatif.

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

## Quel gabarit, et s'il manque

Le nom est une **expression** : une variable, un ternaire
(`ajax ? 'ajax.html.twig' : 'page.html.twig'`), ou une **liste** dont le premier
gabarit existant est inclus.

Un gabarit absent fait échouer l'inclusion, sauf :

- en balise, `ignore missing`, **juste après le nom** — les mots se combinent
  dans cet ordre : `ignore missing with {…} only` ;
- en fonction, l'argument `ignore_missing: true`, qui rend une chaîne vide.

Avec une liste et `ignore missing`, rien n'est rendu si aucun gabarit n'existe ;
sans, une exception est levée.

Pour un gabarit écrit par un utilisateur, la documentation recommande le bac à
sable : `include('page.html.twig', sandboxed: true)` — effectif seulement si
l'extension `SandboxExtension` est enregistrée.

## `include` ou `embed`

`{% embed %}` combine `include` et `extends` : il insère un gabarit **et**
permet de redéfinir ses blocs au point d'insertion.

```html
{% embed 'teaser_skeleton.html.twig' %}
    {% block left %}Contenu de gauche{% endblock %}
{% endembed %}
```

La documentation le décrit comme un « micro squelette de mise en page ». Il
accepte exactement les mêmes arguments que la balise `include` : `with`, `only`,
`ignore missing`. Le critère de choix : si le fragment est identique à chaque
appel, `include` ; s'il faut en changer une partie, `embed`.

Un avertissement de la documentation relie `embed` à l'échappement : un gabarit
embarqué n'a pas de nom, donc la stratégie d'échappement déduite de l'extension
ne s'applique pas comme attendu. Pour embarquer du CSS ou du JavaScript dans du
HTML, on fixe la stratégie avec la balise `autoescape`.

## Pièges d'examen

**Par défaut, le gabarit inclus voit tout le contexte de l'appelant.** Le
fragment marche par accident tant que l'appelant possède la variable, puis
casse ailleurs.

**Restreindre le contexte s'écrit différemment selon la forme employée** — un
mot-clé dans la balise, un argument nommé dans la fonction.

**Tolérer l'absence aussi** : `ignore missing` en balise, `ignore_missing: true`
en fonction.

**Une liste de noms n'inclut qu'un gabarit** : le premier qui existe.

## Points clés

- `include()` en fonction, recommandée ; `{% include %}` en balise.
- Contexte hérité par défaut ; `only` ou `with_context: false` le coupe.
- Nom dynamique ou liste ; `ignore missing` / `ignore_missing: true`.
- `embed` = `include` + redéfinition de blocs, mêmes arguments ; stratégie
  d'échappement à fixer explicitement.

## Sources officielles

- [Twig 3.22, balise `include`](https://github.com/twigphp/Twig/blob/v3.22.0/doc/tags/include.rst) et [fonction `include`](https://github.com/twigphp/Twig/blob/v3.22.0/doc/functions/include.rst)
- [Twig 3.22, balise `embed`](https://github.com/twigphp/Twig/blob/v3.22.0/doc/tags/embed.rst)
- [Symfony Templates, inclusion de gabarits](https://github.com/symfony/symfony-docs/blob/8.0/templates.rst)
