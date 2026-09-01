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
    anchor: "functions"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/templates.rst"
    anchor: "filters"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    commit_sha: "5079583d7313b0f0866ca32108036afcc072127d"
    verified_at: "2026-09-01"
---

## Objectif

Distinguer un filtre d'une fonction, savoir enchaîner et paramétrer, et
reconnaître les extensions que Symfony ajoute.

## La différence

Un **filtre** transforme une valeur qui existe déjà, et s'applique par le tube :

```html
{{ name|upper }}
{{ price|number_format(2, ',', ' ') }}
```

Une **fonction** produit une valeur à partir de ses arguments :

```html
{{ path('blog_show', {slug: post.slug}) }}
{{ random(1, 10) }}
```

La règle pratique : si l'on part d'une valeur, c'est un filtre ; si l'on part de
rien, c'est une fonction. `date` existe sous les deux formes — `now|date(...)`
filtre une date, `date()` en construit une — ce qui en fait un bon révélateur de
la distinction.

## Enchaîner

Les filtres se composent de gauche à droite :

```html
{{ text|trim|lower|truncate(50) }}
```

L'ordre compte : `|trim|lower` et `|lower|trim` diffèrent dès que la chaîne
contient des espaces significatifs.

`{% apply %}` applique un filtre à tout un bloc :

```html
{% apply upper %}
    Ce paragraphe entier sera en majuscules.
{% endapply %}
```

## Ce que Symfony ajoute

Le bridge Twig apporte les extensions qui exposent les composants :

| Ajout | Origine |
|---|---|
| `path()`, `url()` | Routing |
| `asset()`, `asset_version()` | Asset |
| `trans` (filtre), `t()` | Translation |
| `form_start()`, `form_row()`, `form_widget()` | Form |
| `is_granted()`, `app.user` | Security |
| `dump()` | VarDumper |

`php bin/console debug:twig` liste ce qui est réellement disponible dans
l'application, filtres, fonctions, tests et globales — c'est la réponse fiable,
plutôt qu'une liste apprise par cœur.

## En ajouter

Une extension déclare ses filtres et fonctions en étendant `AbstractExtension`,
ou, plus court, en marquant une méthode d'un service avec les attributs
`#[AsTwigFilter]` et `#[AsTwigFunction]`.

## Points clés

- Filtre = transforme une valeur, par `|` ; fonction = produit une valeur.
- Les filtres s'enchaînent de gauche à droite ; l'ordre change le résultat.
- `{% apply %}` filtre un bloc entier.
- Les extensions Symfony viennent du bridge Twig, pas du moteur.
- `debug:twig` donne l'inventaire réel.

## Sources officielles

- [Symfony Twig Reference](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/reference/twig_reference.rst)
- [Twig 3.22, filtres et fonctions](https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/templates.rst)
