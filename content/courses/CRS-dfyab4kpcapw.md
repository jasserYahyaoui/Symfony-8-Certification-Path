---
id: CRS-dfyab4kpcapw
official_item: OIT-30gnb617ksex
title: "String interpolation"
content_level: MINIMAL
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-24"
official_sources:
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/templates.rst"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/doc/templates.rst"
    anchor: "string-interpolation"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    commit_sha: "5079583d7313b0f0866ca32108036afcc072127d"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/src/Lexer.php"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/src/Lexer.php"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    symbol_or_lines: "Lexer::stripcslashes(), REGEX_STRING"
    verified_at: "2026-09-24"
outcome_sections:
  OUT-rve93x3e2gjq: "La syntaxe"
  OUT-w25h6r5rdb9c: "La constraint"
  OUT-tf752s70s9sw: "Échapper"
---

## Objectif

Insérer une expression dans une string, connaître la constraint qui l'entoure,
et savoir ce que Twig fait des barres obliques inverses dans une string.

## La syntaxe

```html
{{ "premier #{middle} dernier" }}
{{ "résultat : #{1 + 2}" }}
{{ "Bonjour #{user.name|upper}" }}
```

`#{expression}` accepte **n'importe quelle expression valide** — variable,
calcul, filter —, pas seulement un nom de variable. Son résultat est inséré dans
la string.

## La constraint

L'interpolation ne fonctionne que dans une string à **guillemets doubles**. Dans
une string à guillemets simples, `#{…}` est du texte littéral. Un `#` qui n'est
pas suivi de `{` reste du texte, même entre guillemets doubles.

La documentation de Twig 3.22 le précise : le choix des guillemets n'a aucun
effet sur les performances ; il ne décide que de l'interpolation.

## Échapper

Une barre oblique inverse neutralise l'interpolation :

```html
{# affiche : premier #{1 + 2} dernier #}
{{ "premier \#{1 + 2} dernier" }}
```

## Les séquences d'escaping, dans les deux guillemets

Contrairement à PHP, Twig traite les séquences d'escaping **aussi entre
guillemets simples** : le `Lexer` passe toute string littérale par la même
function. `'a\nb'` contient donc un vrai saut de ligne. Sont reconnus `\n`,
`\t`, `\r`, `\v`, `\f`, `\\`, les formes hexadécimale `\x…` et octale `\0` à
`\377`, et le guillemet de la string : `\'` entre simples, `\"` entre doubles.

Tout autre caractère précédé d'une barre oblique inverse est **déprécié depuis
Twig 3.12** : la barre est ignorée en Twig 3, elle ne le sera plus en Twig 4.
Un path Windows s'écrit donc en doublant la barre : `'c:\\Program Files'`.

## L'alternative

L'operator `~` concatène, et reste souvent plus lisible sur deux fragments :

```html
{{ "Bonjour #{user.name} !" }}
{{ "Bonjour " ~ user.name ~ " !" }}
```

Rappel utile : `+` additionne des nombres ; il ne concatène pas.

## Pièges d'examen

**L'interpolation ne fonctionne qu'entre guillemets doubles.** En guillemets
simples, la même écriture est du texte littéral affiché tel quel — et rien ne
signale l'erreur.

**Le choix des guillemets ne coûte rien en performance.**

**`\n` est un saut de ligne même entre guillemets simples**, à l'inverse de
PHP.

**L'operator d'addition ne concatène pas.**

## Points clés

- `#{expression}`, toute expression admise.
- **Guillemets doubles uniquement** ; en guillemets simples, c'est du texte.
- `\#{…}` échappe l'interpolation.
- Séquences d'escaping traitées dans les deux guillemets ; escaping
  inconnu déprécié depuis 3.12.
- `~` est l'alternative ; `+` n'est pas une concaténation.

## Sources officielles

- [Twig 3.22, « String Interpolation » et « Literals »](https://github.com/twigphp/Twig/blob/v3.22.0/doc/templates.rst)
- [Twig 3.22, `Lexer`](https://github.com/twigphp/Twig/blob/v3.22.0/src/Lexer.php)
