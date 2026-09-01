---
id: CRS-dfyab4kpcapw
official_item: OIT-30gnb617ksex
title: "String interpolation"
content_level: MINIMAL
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/templates.rst"
    anchor: "string-interpolation"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    commit_sha: "5079583d7313b0f0866ca32108036afcc072127d"
    verified_at: "2026-09-01"
---

## Objectif

Insérer une expression dans une chaîne, et connaître la contrainte qui l'entoure.

## La syntaxe

```html
{{ "premier #{middle} dernier" }}
{{ "résultat : #{1 + 2}" }}
```

`#{expression}` accepte **n'importe quelle expression valide**, pas seulement un
nom de variable.

## La contrainte

L'interpolation ne fonctionne que dans une chaîne à **guillemets doubles**. Dans
une chaîne à guillemets simples, `#{…}` est du texte littéral.

Le choix des guillemets n'a aucun effet sur les performances ; il ne décide que
de cela.

## Échapper

Une barre oblique inverse neutralise l'interpolation :

```html
{# affiche : premier #{1 + 2} dernier #}
{{ "premier \#{1 + 2} dernier" }}
```

## L'alternative

L'opérateur `~` concatène, et reste souvent plus lisible sur deux fragments :

```html
{{ "Bonjour #{user.name} !" }}
{{ "Bonjour " ~ user.name ~ " !" }}
```

Rappel utile : `+` additionne, il ne concatène pas.

## Points clés

- `#{expression}`, toute expression admise.
- **Guillemets doubles uniquement** ; en guillemets simples, c'est du texte.
- `\#{…}` échappe l'interpolation.
- `~` est l'alternative ; `+` n'est pas une concaténation.

## Sources officielles

- [Twig 3.22, « String Interpolation »](https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/templates.rst)
