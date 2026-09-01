---
id: CRS-zbnzvqkhjknh
official_item: OIT-84j0qwkbcgq6
title: "Loops and conditions"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/tags/for.rst"
    anchor: "for"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    commit_sha: "5079583d7313b0f0866ca32108036afcc072127d"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/tags/if.rst"
    anchor: "if"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    commit_sha: "5079583d7313b0f0866ca32108036afcc072127d"
    verified_at: "2026-09-01"
---

## Objectif

Parcourir une séquence, connaître la variable `loop` et sa limite, et écrire une
condition.

## La boucle

```html
{% for user in users %}
    <li>{{ user.username }}</li>
{% else %}
    <li>Aucun utilisateur.</li>
{% endfor %}
```

La clause `else` s'exécute quand la séquence est **vide**. C'est une
particularité de Twig : il n'y a pas d'équivalent en PHP, et elle évite un `if`
englobant.

`{% for key, user in users %}` donne la clé et la valeur ; `{% for key in
users|keys %}` les clés seules.

## La variable `loop`

Disponible dans le corps de la boucle :

| Variable | Contenu |
|---|---|
| `loop.index` | itération courante, **à partir de 1** |
| `loop.index0` | itération courante, à partir de 0 |
| `loop.revindex` | itérations restantes, à partir de 1 |
| `loop.revindex0` | itérations restantes, à partir de 0 |
| `loop.first` | vrai à la première |
| `loop.last` | vrai à la dernière |
| `loop.length` | taille de la séquence |
| `loop.parent` | le contexte englobant |

**La limite à connaître :** `loop.length`, `loop.revindex`, `loop.revindex0` et
`loop.last` ne sont disponibles que pour un **tableau PHP** ou un objet
implémentant `Countable`. Sur un itérateur ordinaire, elles n'existent pas —
Twig ne peut pas connaître la taille sans consommer la séquence.

`loop.index` et `loop.first`, eux, fonctionnent toujours.

## Filtrer et découper

Twig n'a pas de `break` ni de `continue`. On restreint la séquence en amont :

```html
{% for user in users|filter(u => u.isActive) %}
{% for user in users|slice(0, 10) %}
{% for user in users|sort %}
```

## Les conditions

```html
{% if users is empty %}
    …
{% elseif users|length == 1 %}
    …
{% else %}
    …
{% endif %}
```

Les tests s'écrivent avec `is` : `is defined`, `is empty`, `is null`,
`is even`, `is iterable`, `is same as`. La négation est `is not` :
`{% if x is not defined %}`.

Attention à la différence entre `is empty` — vrai pour `''`, `0`, `[]`, `null` —
et `is not defined`, qui porte sur l'existence de la variable, pas sur sa valeur.

## Points clés

- `{% for %}` accepte une clause `else` pour la séquence vide.
- `loop.index` commence à **1** ; `loop.index0` à 0.
- `loop.length`, `revindex`, `revindex0` et `last` exigent un tableau ou
  `Countable`.
- Pas de `break` ni de `continue` : filtrer la séquence avec `filter` ou `slice`.
- Les tests s'écrivent `is` / `is not`.

## Sources officielles

- [Twig 3.22, balise `for`](https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/tags/for.rst)
- [Twig 3.22, balise `if`](https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/tags/if.rst)
