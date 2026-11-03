---
id: CRS-zbnzvqkhjknh
official_item: OIT-84j0qwkbcgq6
title: "Loops and conditions"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-11-03"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/tags/for.rst"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/doc/tags/for.rst"
    anchor: "for"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    commit_sha: "5079583d7313b0f0866ca32108036afcc072127d"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/tags/if.rst"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/doc/tags/if.rst"
    anchor: "if"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    commit_sha: "5079583d7313b0f0866ca32108036afcc072127d"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/src/Node/ForNode.php"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/src/Node/ForNode.php"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    symbol_or_lines: "ForNode::compile(), loop keys and scope"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/tags/set.rst"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/doc/tags/set.rst"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    symbol_or_lines: "loops are scoped"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/src/Extension/CoreExtension.php"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/src/Extension/CoreExtension.php"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    symbol_or_lines: "CoreExtension::testEmpty(), getTests()"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/tests/empty.rst"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/doc/tests/empty.rst"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    symbol_or_lines: "empty test"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/src/Node/IfNode.php"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/src/Node/IfNode.php"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    symbol_or_lines: "IfNode::__construct(), TrueTest"
    verified_at: "2026-11-03"
outcome_sections:
  OUT-n2sny821kd8p: "La loop"
  OUT-16dbq9y7rz76: "La variable `loop`"
  OUT-rjj1wzpn63ph: "La variable `loop`"
  OUT-d2d8w5y3z51h: "Filtrer et découper"
  OUT-j1qtw6ccaxqg: "Les conditions"
---

## Objectif

Parcourir une séquence, connaître la variable `loop` et sa limite, le scope
d'une loop, et écrire une condition sans se tromper sur ce qui est « vide ».

## L'essentiel

Twig parcourt une séquence avec `for`, propose une clause `else` pour le cas
vide et une variable `loop` qui connaît sa position — mais pas toujours sa
taille. Pas de `break` ni de `continue` : on filtre la séquence avant. Et dans
une condition, « faux » et « vide » sont deux questions différentes.

## Le schéma

```mermaid
---
title: Ce que Twig fait d'une loop for
---
flowchart TD
  accTitle: Le déroulement d'une loop for dans Twig 3.22
  accDescr: La séquence passe par ensureTraversable, qui remplace toute valeur non itérable, comme null ou une string, par un array vide. La variable loop reçoit toujours index, index0, first et parent ; length, revindex, revindex0 et last seulement si la séquence est un array ou un object Countable. Le body s'exécute pour chaque élément ; si aucune itération n'a eu lieu, la clause else s'exécute. Après endfor, les variables créées dans la loop disparaissent, la variable de loop retrouve sa valeur d'avant, et une variable déclarée avant la loop garde la dernière valeur reçue dedans.
  SEQ["la séquence"] --> TRAV{"itérable ?"}
  TRAV -->|"non : null, string, nombre"| EMPTY["traitée comme un array vide"]
  TRAV -->|"oui"| CNT{"array ou Countable ?"}
  CNT -->|"oui"| FULL["loop : index, first, last, length, revindex"]
  CNT -->|"non : generator, iterator"| HALF["loop : index, index0, first, parent seulement"]
  FULL --> BODY["body, pour chaque élément"]
  HALF --> BODY
  EMPTY --> ELSE
  BODY --> ANY{"au moins une itération ?"}
  ANY -->|"non"| ELSE(["clause else"])
  ANY -->|"oui"| AFTER(["après endfor : variables créées dedans perdues, déclarées avant gardées"])
  ELSE --> AFTER
```
## L'exemple

```twig
{% set total = 0 %}
{% for member in team.members|filter(m => m.active) %}
    {{ loop.index }}. {{ member.name }}{{ not loop.last ? ' —' }}
    {% set total = total + member.points %}
{% else %}
    Aucun membre actif.
{% endfor %}
Total : {{ total }}
```

## La loop

- `{% for user in users %}…{% else %}…{% endfor %}` : `else` s'exécute quand
  **aucune itération n'a eu lieu**. Une valeur non itérable — `null`, une
  string — est traitée comme une séquence vide : `else` aussi.
- `{% for key, user in users %}` donne la key et la valeur ; `users|keys` les
  clés seules.
- `0..10` va de 0 à 10 inclus, `'a'..'z'` marche aussi, `3..0` descend ;
  `range(0, 10, 5)` change le pas.

## La variable `loop`

| Variable | Contenu |
|---|---|
| `loop.index` / `loop.index0` | itération courante, **à partir de 1** / de 0 |
| `loop.revindex` / `loop.revindex0` | itérations restantes, à partir de 1 / de 0 |
| `loop.first` / `loop.last` | vrai à la première / à la dernière |
| `loop.length` | taille de la séquence |
| `loop.parent` | le contexte englobant |

**La limite :** `length`, `revindex`, `revindex0` et `last` n'existent que pour
un **array** ou un object **`Countable`** — un `ArrayIterator` les a. Sur un
generator ou un iterator non `Countable`, ils manquent : `RuntimeError` avec
`strict_variables`, `null` sans. `loop.index` et `loop.first` fonctionnent
toujours.

## Le scope d'une loop

- Une variable **créée** par `set` dans la loop n'existe plus après `endfor`.
- Une variable **déclarée avant** garde la dernière valeur reçue dedans : c'est
  ainsi qu'on cumule un total.
- La variable de loop elle-même retrouve sa valeur d'avant : après
  `{% set u = 'outer' %}{% for u in ['a', 'b'] %}{% endfor %}`, `u` vaut `outer`.

## Filtrer et découper

Pas de `break` ni de `continue`, et `ForTokenParser` refuse une condition sur le
tag `for` lui-même. On restreint la séquence :

```html
{% for user in users|filter(u => u.isActive) %}
{% for user in users|slice(0, 10) %}
```

Avec un `if` dans le body, `loop.index` compte les éléments sautés et
`loop.last` peut tomber sur un élément non affiché ; avec `filter`, la
numérotation suit ce qui est affiché.

## Les conditions

`{% if %}`, `{% elseif %}`, `{% else %}`. Les tests s'écrivent avec `is` et se
nient par `is not` : `constant`, `defined`, `divisible by`, `empty`, `even`,
`iterable`, `mapping`, `null` (alias `none`), `odd`, `same as`, `sequence`.
Pas d'operator `===` : la comparaison stricte est le **test** `same as`, et
`===` ne compile pas.

## Vrai, faux, vide : trois questions différentes

| Valeur | Dans un `if` | `is empty` |
|---|---|---|
| `''`, `[]`, `null` | faux | vide |
| `0`, `'0'` | faux | **pas vide** |
| `' '` | vrai | pas vide |
| collection `Countable` vide | **vrai** (tout object est vrai) | vide |

`is defined` porte sur l'existence, pas sur la valeur. Sur un generator,
`is empty` le parcourt pour compter : la loop qui suit lève « Cannot traverse an
already closed generator ».

## Pièges d'examen

**La moitié des properties de loop n'existent pas sur un generator ni sur un
iterator non `Countable`** ; l'index et le test de première itération, si. Un
`ArrayIterator`, qui est `Countable`, les a toutes.

**L'index commence à 1.**

**La clause `else` d'une loop se déclenche sur une séquence vide.**

**Une variable créée dans une loop n'en sort pas** ; déclarée avant, elle
garde sa dernière valeur.

**`0` est faux mais n'est pas vide ; une collection vide est vraie mais vide.**

## Application

- Un séparateur `not loop.last` casse quand le repository renvoie un generator
  → convertir en array, ou poser le séparateur avant l'élément avec
  `not loop.first`.
- Une liste numérotée affiche 1, 3, 4 → remplacer le `if` du body par
  `|filter`.

## QCM d'entraînement

**Question 1.** Que rend ce template ?

```twig
{% set u = 'outer' %}{% for u in ['a', 'b'] %}{{ u }}{% endfor %}/{{ u }}
```

- **A.** `ab/b`
- **B.** `ab/outer`
- **C.** `ab/`
- **D.** Une erreur : `u` est déjà définie

<details>
<summary>Correction</summary>

**Bonne réponse : B.** À `endfor`, la variable de loop est retirée et le
contexte d'avant est recomposé ; `u` y valait `outer`.

</details>

**Question 2.** Avec `strict_variables`, une loop parcourt
`new ArrayIterator(['a', 'b'])` et affiche `{{ loop.last ? 'L' : '-' }}`. Que
rend-elle ?

- **A.** `-L`
- **B.** Une `RuntimeError` : la key `last` n'existe pas sur un iterator
- **C.** `--`
- **D.** `LL`

<details>
<summary>Correction</summary>

**Bonne réponse : A.** `ArrayIterator` est `Countable` ; B est ce qu'on obtient
avec un generator.

</details>

**Question 3.** Que rend `{% for c in 'abc' %}[{{ c }}]{% else %}E{% endfor %}` ?

- **A.** `[a][b][c]`
- **B.** `[abc]`
- **C.** Une `RuntimeError`
- **D.** `E`

<details>
<summary>Correction</summary>

**Bonne réponse : D.** Une valeur non itérable devient une séquence vide : la
clause `else` s'exécute.

</details>

**Question 4.** Que rend
`{% for n in [1, 2, 3, 4]|filter(n => n is even) %}{{ loop.index }}:{{ n }} {% endfor %}` ?

- **A.** `2:2 4:4`
- **B.** `0:2 1:4`
- **C.** `1:2 2:4`
- **D.** `1:1 2:2 3:3 4:4`

<details>
<summary>Correction</summary>

**Bonne réponse : C.** La loop ne voit que la séquence filtrée. A est ce que
donne un `if` dans le body.

</details>

## Fiche express

**Si tu dois retenir seulement 8 choses :**

- **`else`** de loop : séquence vide ou non itérable.
- **`loop.index`** part de **1**.
- **`last`, `length`, `revindex`, `revindex0`** : array ou **`Countable`**.
- Generator : **`RuntimeError`** sous `strict_variables`, `null` sans.
- Variable **créée dedans perdue**, **déclarée avant gardée**.
- **Ni `break` ni `continue`** : `filter`, `slice`, `sort`.
- **`same as`** remplace `===`, qui ne compile pas.
- **`0` faux mais pas vide** ; collection vide **vraie mais vide**.

Moyen mnémotechnique : **« else pour le vide, Countable pour la fin, avant
pour garder »**.

```text
for…else (vide ou non itérable) ; loop.index dès 1 ; last/length/revindex = array ou Countable ;
scope : créé perdu, déclaré avant gardé ; filter au lieu de break ; same as pour === ; if ≠ empty
```

## Sources officielles

- [Twig 3.22, tag `for`](https://github.com/twigphp/Twig/blob/v3.22.0/doc/tags/for.rst) et [`ForNode`](https://github.com/twigphp/Twig/blob/v3.22.0/src/Node/ForNode.php)
- [Twig 3.22, tag `if`](https://github.com/twigphp/Twig/blob/v3.22.0/doc/tags/if.rst), [`IfNode` et `TrueTest`](https://github.com/twigphp/Twig/blob/v3.22.0/src/Node/IfNode.php) et [tag `set`, portée des loops](https://github.com/twigphp/Twig/blob/v3.22.0/doc/tags/set.rst)
- [Twig 3.22, tests](https://github.com/twigphp/Twig/blob/v3.22.0/doc/tests/index.rst), [`empty`](https://github.com/twigphp/Twig/blob/v3.22.0/doc/tests/empty.rst) et [`CoreExtension::testEmpty()`](https://github.com/twigphp/Twig/blob/v3.22.0/src/Extension/CoreExtension.php)
