---
id: CRS-306ppg4454wj
official_item: OIT-sqekx9pkbe5v
title: "Filters and functions"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/reference/twig_reference.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/reference/twig_reference.rst"
    anchor: "functions"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/templates.rst"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/doc/templates.rst"
    anchor: "filters"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    commit_sha: "5079583d7313b0f0866ca32108036afcc072127d"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/filters/index.rst"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/doc/filters/index.rst"
    symbol_or_lines: "Filters"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/filters/u.rst"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/doc/filters/u.rst"
    symbol_or_lines: "u.truncate, twig/string-extra"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/advanced.rst"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/doc/advanced.rst"
    symbol_or_lines: "AsTwigFilter (3.21)"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/filters/slice.rst"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/doc/filters/slice.rst"
    symbol_or_lines: "slice"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    verified_at: "2026-09-24"
---

## Objectif

Distinguer un filter d'une function, savoir enchaîner et paramétrer, et
reconnaître les extensions que Symfony ajoute.

## La différence

Un **filter** transforme une valeur qui existe déjà, et s'applique par le tube :

```html
{{ name|upper }}
{{ price|number_format(2, ',', ' ') }}
```

Une **function** produit une valeur à partir de ses arguments :

```html
{{ path('blog_show', {slug: post.slug}) }}
{{ random(1, 10) }}
```

La règle pratique : si l'on part d'une valeur, c'est un filter ; si l'on part de
rien, c'est une function. `date` existe sous les deux formes — `now|date(...)`
filter une date, `date()` en construit une — ce qui en fait un bon révélateur de
la distinction. Troisième famille, à ne pas confondre : les **tests**, appliqués
par `is` — `is defined`, `is empty`, `is iterable`.

## Enchaîner

Les filters se composent de gauche à droite, chacun recevant le résultat du
précédent :

```html
{{ text|trim|lower|slice(0, 50) }}
```

L'ordre compte quand deux filters ne commutent pas. Sur `'  abcdef'`,
`|slice(0, 5)|trim` donne `abc` — la coupe garde les deux espaces — alors que
`|trim|slice(0, 5)` donne `abcde`. À l'inverse, `trim` et `lower` commutent :
l'un ne touche que les espaces, l'autre que la casse.

Les arguments peuvent être nommés : `{{ price|number_format(decimal: 2) }}`.
Plusieurs filters prennent une **function fléchée** — `filter`, `map`,
`reduce`, `sort`, `find` :

```html
{{ users|filter(u => u.active)|map(u => u.name)|join(', ') }}
```

`{% apply %}` applique un filter à tout un block :

```html
{% apply upper %}
    Ce paragraphe entier sera en majuscules.
{% endapply %}
```

## Le cœur de Twig et les packages « extra »

La liste des filters de la documentation Twig mélange le cœur et des extensions
**à installer**. `truncate`, par exemple, n'est **pas** un filter du cœur de
Twig 3.22 : on tronque avec `slice`, ou avec `|u.truncate(50)`, que fournit
l'extension `StringExtension` du package `twig/string-extra` — dans Symfony,
activée par `twig/extra-bundle`. Les filters `format_*`, `markdown_to_html` ou
`inline_css` viennent de même d'extensions séparées.

## Ce que Symfony ajoute

Le bridge Twig apporte les extensions qui exposent les composants :

| Ajout | Origine |
|---|---|
| `path()`, `url()` | Routing |
| `asset()`, `asset_version()` | Asset |
| `trans` (filter), `t()` | Translation |
| `form_start()`, `form_row()`, `form_widget()` | Form |
| `is_granted()` | Security |
| `dump()` | VarDumper |

`php bin/console debug:twig` liste ce qui est réellement disponible dans
l'application, filters, functions, tests et globales — c'est la Response fiable,
plutôt qu'une liste apprise par cœur.

## En ajouter

Une extension déclare ses filters et functions en étendant `AbstractExtension`.
Depuis Twig 3.21, plus court : les attributes `#[AsTwigFilter]`,
`#[AsTwigFunction]` et `#[AsTwigTest]` (namespace `Twig\Attribute`) sur
les methods publiques d'une class ; TwigBundle 8.0 les enregistre par
autoconfiguration.

## Pièges d'examen

**Filter ou function se décide à la source, pas au nom.** On part d'une valeur
existante : c'est un filter. On part de rien : c'est une function. Le même mot
peut exister sous les deux formes.

**L'ordre des filters enchaînés compte quand ils ne commutent pas.** `slice`
puis `trim` n'égale pas `trim` puis `slice` ; `trim` et `lower`, si.

**Un filter de la documentation peut exiger un package.** `u`, `format_*`,
`markdown_to_html` ne sont pas dans le cœur ; `truncate` n'existe pas du tout.

**Ce que Symfony ajoute vient du pont, pas du moteur.** Les functions de route,
d'asset, de translation et de form n'existent pas dans un Twig installé
seul.

## Points clés

- Filter = transforme une valeur, par `|` ; function = produit une valeur ;
  test = `is`.
- Les filters s'enchaînent de gauche à droite ; l'ordre compte s'ils ne
  commutent pas.
- Arguments nommés ; functions fléchées pour `filter`, `map`, `reduce`, `sort`.
- Packages `twig/*-extra` pour `u`, `format_*`… ; pas de `truncate` dans le cœur.
- `#[AsTwigFilter]` et consorts depuis Twig 3.21 ; `debug:twig` donne
  l'inventaire réel.

## Sources officielles

- [Symfony Twig Reference](https://github.com/symfony/symfony-docs/blob/8.0/reference/twig_reference.rst)
- [Twig 3.22, filters et functions](https://github.com/twigphp/Twig/blob/v3.22.0/doc/templates.rst)
- [Twig 3.22, index des filters](https://github.com/twigphp/Twig/blob/v3.22.0/doc/filters/index.rst) et [filter `u`](https://github.com/twigphp/Twig/blob/v3.22.0/doc/filters/u.rst)
- [Twig 3.22, attributes `AsTwigFilter`](https://github.com/twigphp/Twig/blob/v3.22.0/doc/advanced.rst)
