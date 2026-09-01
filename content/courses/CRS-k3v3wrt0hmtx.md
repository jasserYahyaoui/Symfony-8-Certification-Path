---
id: CRS-k3v3wrt0hmtx
official_item: OIT-g3p8wdtww344
title: "Template inheritance"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/tags/extends.rst"
    anchor: "extends"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    commit_sha: "5079583d7313b0f0866ca32108036afcc072127d"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/tags/use.rst"
    anchor: "use"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    commit_sha: "5079583d7313b0f0866ca32108036afcc072127d"
    verified_at: "2026-09-01"
---

## Objectif

Construire une hiérarchie de gabarits, savoir ce que l'héritage autorise, et
distinguer l'héritage de la réutilisation horizontale.

## Le mécanisme

Un gabarit parent déclare des **blocs** ; un gabarit enfant `extends` le parent
et redéfinit ceux qui l'intéressent.

```html
{% extends 'base.html.twig' %}

{% block title %}Articles{% endblock %}

{% block body %}
    <h1>Derniers articles</h1>
{% endblock %}
```

Un bloc non redéfini garde le contenu du parent. Un enfant n'affiche **que** ses
blocs : tout ce qu'il écrit hors d'un bloc est ignoré, ce qui surprend au
premier essai.

## Les deux règles

**Un seul héritage.** Un gabarit ne peut étendre qu'un seul parent. C'est
délibéré : la limitation rend la hiérarchie lisible et débogable.

**`extends` doit être la première balise** du gabarit.

Rien n'empêche en revanche d'empiler les niveaux — `base` → `layout de section`
→ `page` — et c'est le motif habituel.

## Reprendre le contenu du parent

`{{ parent() }}` insère le contenu que le bloc avait dans le parent, ce qui
permet d'ajouter au lieu de remplacer :

```html
{% block stylesheets %}
    {{ parent() }}
    <link rel="stylesheet" href="page.css">
{% endblock %}
```

## Réutilisation horizontale

`{% use 'blocks.html.twig' %}` **importe les blocs** d'un autre gabarit sans
l'étendre. C'est la réponse de Twig au besoin d'héritage multiple, sans sa
complexité : on peut `use` plusieurs gabarits tout en n'en `extends` qu'un.

La documentation la présente comme une fonctionnalité avancée, rarement
nécessaire dans un gabarit ordinaire — mais c'est exactement pour cela qu'elle
est interrogeable.

## Points clés

- `extends` + `block` ; un bloc non redéfini garde le contenu du parent.
- Héritage **simple** ; `extends` doit être la première balise.
- Hors d'un bloc, le contenu d'un enfant n'est pas affiché.
- `{{ parent() }}` ajoute au lieu de remplacer.
- `{% use %}` importe des blocs — réutilisation horizontale, pas héritage.

## Sources officielles

- [Twig 3.22, balise `extends`](https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/tags/extends.rst)
- [Twig 3.22, balise `use`](https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/tags/use.rst)
