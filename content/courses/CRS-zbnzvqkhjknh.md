---
id: CRS-zbnzvqkhjknh
official_item: OIT-84j0qwkbcgq6
title: "Loops and conditions"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-24"
official_sources:
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/tags/for.rst"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/doc/tags/for.rst"
    anchor: "for"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    commit_sha: "5079583d7313b0f0866ca32108036afcc072127d"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/tags/if.rst"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/doc/tags/if.rst"
    anchor: "if"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    commit_sha: "5079583d7313b0f0866ca32108036afcc072127d"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/src/Node/ForNode.php"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/src/Node/ForNode.php"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    symbol_or_lines: "ForNode::compile(), loop keys and scope"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/tags/set.rst"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/doc/tags/set.rst"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    symbol_or_lines: "loops are scoped"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/src/Extension/CoreExtension.php"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/src/Extension/CoreExtension.php"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    symbol_or_lines: "CoreExtension::testEmpty(), getTests()"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/tests/empty.rst"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/doc/tests/empty.rst"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    symbol_or_lines: "empty test"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/src/Node/IfNode.php"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/src/Node/IfNode.php"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    symbol_or_lines: "IfNode::__construct(), TrueTest"
    verified_at: "2026-09-24"
---

## Objectif

Parcourir une séquence, connaître la variable `loop` et sa limite, la portée
d'une boucle, et écrire une condition sans se tromper sur ce qui est « vide ».

## La boucle

```html
{% for user in users %}
    <li>{{ user.username }}</li>
{% else %}
    <li>Aucun utilisateur.</li>
{% endfor %}
```

La clause `else` s'exécute quand **aucune itération n'a eu lieu** — la séquence
était vide. Sans équivalent en PHP, elle évite un `if` englobant.

`{% for key, user in users %}` donne la clé et la valeur ; `{% for key in
users|keys %}` les clés seules. L'opérateur `..` produit une suite —
`{% for i in 0..10 %}` va de 0 à 10 inclus, `'a'..'z'` marche aussi — et la
fonction `range()` accepte un pas différent de 1.

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
implémentant `Countable`. Le code compilé par `ForNode` ne les renseigne que
dans ce cas : sur un générateur ou un itérateur ordinaire, ces clés n'existent
pas — Twig ne peut pas connaître la taille sans consommer la séquence.

`loop.index` et `loop.first`, eux, fonctionnent toujours.

## La portée d'une boucle

Une boucle a sa propre portée. Une variable **créée** par `{% set %}` dans le
corps n'existe plus après `{% endfor %}` ; une variable **déclarée avant** la
boucle garde en revanche la dernière valeur qu'elle y a reçue. `ForNode` le
compile ainsi : à la sortie, seules les clés du contexte d'avant la boucle sont
conservées. Pour cumuler un total, on le déclare donc avant.

## Filtrer et découper

Twig n'a pas de `break` ni de `continue`, et `ForTokenParser` n'accepte aucune
condition sur la balise `for` elle-même. On restreint la séquence en amont :

```html
{% for user in users|filter(u => u.isActive) %}
{% for user in users|slice(0, 10) %}
{% for user in users|sort %}
```

Un `if` à l'intérieur du corps fonctionne aussi, mais `loop.index` compte alors
les éléments sautés, et `loop.last` peut tomber sur un élément non affiché.

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

Les tests s'écrivent avec `is` et se nient par `is not`. `CoreExtension`
(3.22) enregistre `constant`, `defined`, `divisible by`, `empty`, `even`,
`iterable`, `mapping`, `null` (alias `none`), `odd`, `same as` et `sequence` —
plus `true`, que le compilateur pose lui-même sur chaque condition d'un `if`.
Twig n'a pas d'opérateur `===` : la comparaison stricte s'écrit avec le
**test** `same as`.

## Vrai, faux, vide : trois questions différentes

Une condition nue suit les règles de PHP. La documentation de `if` en donne les
cas limites : la chaîne vide, zéro, la chaîne `'0'`, une séquence vide et `null`
sont faux ; une chaîne d'espaces, `NAN`, `INF` et **tout objet** sont vrais.
Une exception, lue dans `IfNode` et `TrueTest` : un objet `Markup` — une chaîne
déjà marquée sûre — est jugé sur son texte.

Le test `empty` n'est pas la négation de cette règle. `CoreExtension::testEmpty()`
rend vrai pour `''`, `[]`, `null` et `false`, pour un `Countable` de taille
zéro, pour un `Traversable` sans élément, et pour un objet dont la conversion en
chaîne est vide. **`0` et `'0'` ne sont pas vides**, alors qu'ils sont faux dans
un `if`. Et un objet `Countable` vide — une collection, par exemple — est vrai
dans un `if`, mais `empty`.

`is defined`, enfin, porte sur l'existence de la variable, pas sur sa valeur.

## Pièges d'examen

**La moitié des propriétés de boucle n'existent pas sur un itérateur** ; l'index
et le test de première itération, si.

**L'index commence à 1.**

**La clause `else` d'une boucle se déclenche sur une séquence vide.**

**Une variable créée dans une boucle n'en sort pas** ; déclarée avant, elle
garde sa dernière valeur.

**`0` est faux mais n'est pas vide ; une collection vide est vraie mais vide.**

## Points clés

- `{% for %}` accepte une clause `else` pour la séquence vide ; `..` et
  `range()` produisent des suites.
- `loop.index` commence à **1** ; `length`, `revindex`, `revindex0` et `last`
  exigent un tableau ou `Countable`.
- Portée propre à la boucle ; déclarer avant pour garder une valeur.
- Pas de `break` ni de `continue` : `filter`, `slice`, `sort`.
- Tests par `is` / `is not` ; `same as` pour `===`, absent comme opérateur.
- Condition nue = règles de PHP ; `empty` = règles de `testEmpty()`.

## Sources officielles

- [Twig 3.22, balise `for`](https://github.com/twigphp/Twig/blob/v3.22.0/doc/tags/for.rst) et [`ForNode`](https://github.com/twigphp/Twig/blob/v3.22.0/src/Node/ForNode.php)
- [Twig 3.22, balise `if`](https://github.com/twigphp/Twig/blob/v3.22.0/doc/tags/if.rst) et [balise `set`, portée des boucles](https://github.com/twigphp/Twig/blob/v3.22.0/doc/tags/set.rst)
- [Twig 3.22, tests](https://github.com/twigphp/Twig/blob/v3.22.0/doc/tests/index.rst), [`empty`](https://github.com/twigphp/Twig/blob/v3.22.0/doc/tests/empty.rst) et [`CoreExtension`](https://github.com/twigphp/Twig/blob/v3.22.0/src/Extension/CoreExtension.php)
