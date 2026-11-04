---
id: CRS-x2f8reencvcs
official_item: OIT-vhd83fn6w9wy
title: "Twig syntax up to 3.22 version"
content_level: DEEP
language: fr
verification_status: VERIFIED
reviewed_at: "2026-11-03"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/templates.rst"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/doc/templates.rst"
    anchor: "synopsis"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    commit_sha: "5079583d7313b0f0866ca32108036afcc072127d"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/templates.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/templates.rst"
    anchor: "template-variables"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/src/Extension/CoreExtension.php"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/src/Extension/CoreExtension.php"
    anchor: "getAttribute"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    commit_sha: "5079583d7313b0f0866ca32108036afcc072127d"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/filters/spaceless.rst"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/doc/filters/spaceless.rst"
    symbol_or_lines: "deprecated as of Twig 3.12"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/functions/attribute.rst"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/doc/functions/attribute.rst"
    symbol_or_lines: "deprecated as of Twig 3.15"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/php/php-src/PHP-8.4/Zend/tests/add_006.phpt"
    readable_url: "https://github.com/php/php-src/blob/PHP-8.4/Zend/tests/add_006.phpt"
    symbol_or_lines: "Unsupported operand types"
    repository: "php/php-src"
    branch: "PHP-8.4"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/CHANGELOG"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/CHANGELOG"
    symbol_or_lines: "3.7.0: Add support for the ...spread operator on arrays and hashes"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    verified_at: "2026-11-03"
outcome_sections:
  OUT-491pkbwc6e8x: "Les trois délimiteurs"
  OUT-4wpk7x3epxym: "`foo.bar` : l'ordre de résolution"
  OUT-ap22qz4fkjra: "`foo.bar` : l'ordre de résolution"
  OUT-93svekvx55t0: "Les operators"
  OUT-xpcwsqhycgnr: "`foo.bar` : l'ordre de résolution"
  OUT-je4qjh1s6yw0: "Affectation et espaces"
---

## Objectif

Maîtriser la syntaxe de base de Twig 3.22 : les trois délimiteurs, la façon dont
`foo.bar` est résolu, et le jeu d'operators. C'est le socle des treize autres
items du lot.

La version compte : le syllabus dit « Twig syntax **up to 3.22** version ». Ce
qu'une version ultérieure a ajouté est hors périmètre.

## L'essentiel

Trois choses à savoir : quel **délimiteur** fait quoi, dans quel **ordre** Twig
cherche `foo.bar`, et quels **operators** il comprend — proches de ceux de PHP,
mais pas identiques.

## Le schéma

L'ordre que suit `CoreExtension::getAttribute()` pour `{{ foo.bar }}` :

```mermaid
---
title: Comment Twig résout foo.bar
---
flowchart TD
  accTitle: L'ordre de résolution de foo.bar dans Twig 3.22
  accDescr: Twig essaie d'abord foo comme array, ArrayObject ou ArrayAccess avec la key bar. Puis une property publique bar. Puis une constant de class nommée bar, à la casse près. Puis les methods bar, getBar, isBar et hasBar, cherchées sans tenir compte de la casse, un hasser étant écarté quand un isser du même nom existe. Puis __call. Si rien ne correspond, null, ou une RuntimeError quand strict_variables est active.
  A{"array ou ArrayAccess, key bar ?"} -->|"oui"| R1(["foo['bar']"])
  A -->|"non"| P{"property publique bar ?"}
  P -->|"oui"| R2(["foo->bar"])
  P -->|"non"| C{"constante bar, casse exacte ?"}
  C -->|"oui"| R3(["Foo::bar"])
  C -->|"non"| M{"bar(), getBar(), isBar(), hasBar() ?"}
  M -->|"oui"| R4(["la method, casse ignorée"])
  M -->|"non"| CALL{"__call() ?"}
  CALL -->|"oui"| R5(["__call('bar')"])
  CALL -->|"non"| END(["null, ou RuntimeError si strict_variables"])
```

## L'exemple

```twig
{% set total = order.amount * 1.2 %}
<h1>Commande {{ order.reference ~ ' — ' ~ order.status }}</h1>
{% if order.paid and order.items has some item => item.qty > 1 %}
    <p>{{ order.customer.nickname ?? 'client' }}, total {{ total }} €</p>
{% endif %}
```

## Les trois délimiteurs

| Délimiteur | Role |
|---|---|
| `{{ … }}` | **affiche** le résultat d'une expression |
| `{% … %}` | **exécute** une instruction : `if`, `for`, `extends`, `set` |
| `{# … #}` | **commentaire**, jamais rendu |

## `foo.bar` : l'ordre de résolution

Pour `{{ foo.bar }}`, Twig essaie dans cet ordre :

1. `$foo['bar']` — array, `ArrayObject` ou `ArrayAccess` ;
2. `$foo->bar` — property publique ;
3. `Foo::bar` — **constante de class**, dont le nom correspond à la casse près ;
4. `bar()`, `getBar()`, `isBar()`, `hasBar()` — methods, casse ignorée ; un
   *hasser* est écarté quand un *isser* du même nom existe ;
5. `__call('bar')`, si la class le déclare ;
6. sinon `null` — ou une `RuntimeError` avec `strict_variables`.

Conséquences : l'array passe **avant** l'object (un `ArrayAccess` gagne sur la
property) ; `const STATUS` ne masque pas `getStatus()`, mais `const status` le
masque. La documentation Symfony énumère sept étapes sans la constant ;
l'engine 3.22 l'implémente — un énoncé d'examen peut n'en citer que sept.

- `{{ foo.bar() }}` force l'appel d'une method.
- Nom dynamique ou avec un tiret : `{{ user.(name) }}`, `{{ user.('first-name') }}`
  (3.15).
- `attribute()` est **dépréciée depuis 3.15**, mais en 3.22 elle fonctionne
  **sans notice** de deprecation.

## Les operators

| Famille | Operators |
|---|---|
| Arithmétique | `+ - * / % // **` |
| Comparaison | `== != < > <= >= <=>` |
| Logique | `and or not xor` — pas `&& \|\| !` ; bit à bit : `b-and b-or b-xor` |
| Strings | `~` **concatène** ; `starts with`, `ends with`, `matches` |
| Appartenance | `in`, `not in` (aussi dans une string) ; `..` construit une plage |
| Itérables | `has some`, `has every`, avec une function fléchée |
| Test | `is` : `defined`, `empty`, `null`, `iterable`, `same as` |
| Confort | `?:` (Elvis), `??` (coalescence) |
| Functions | `=>` function fléchée ; `...` étend une séquence (3.7) ou des arguments (3.15) |

Ce qui tombe à l'examen :

- `{{ 'a' + 'b' }}` casse le rendu : `+` additionne, PHP 8.4 refuse deux strings
  non numériques, et Twig remonte la `TypeError` dans une `RuntimeError` ;
- `//` est la division entière et arrondit vers le bas : `-7 // 2` vaut `-4` ;
- `**` s'associe à droite : `2 ** 3 ** 2` vaut `512` ;
- `?:` remplace une valeur **fausse** (`0`, `''`), `??` une valeur **absente**
  ou `null` ; sur une variable absente, `?:` déclenche `strict_variables`, `??`
  non ;
- sur un itérable vide, `has every` vaut `true`, `has some` vaut `false` ;
- `true && false` ne compile pas.

## Affectation et espaces

`{% set total = 0 %}` déclare une variable ; `{% set body %}…{% endset %}`
capture un block.

Twig retire d'office le premier saut de ligne qui suit un tag. Ensuite :

- `-` (`{{-`, `-}}`, `{%-`, `-%}`) supprime **tout** le blanc de ce côté ;
- `~` le supprime **sauf** les sauts de ligne.

Le filter `spaceless` est **déprécié depuis 3.12**, et lui émet une notice.

## Pièges d'examen

- `{{ }}` affiche, `{% %}` exécute — un `{% %}` n'affiche jamais rien.
- L'ordre de résolution commence par l'**array**, pas par la property.
- Une **constante de class** est lue avant le *getter* si son nom est
  identique, casse comprise : `const STATUS` ne masque pas `getStatus()`.
- `and` / `or` / `not`, jamais `&&` / `||` / `!`.
- `~` concatène ; `+` additionne.
- Sans `strict_variables`, une variable inconnue vaut `null` silencieusement.
- `attribute()` (3.15) et `spaceless` (3.12) sont dépréciés en 3.22.

## Application

- La page affiche toujours `draft` malgré `getStatus()` → la class déclare
  `const status` : la constant, à la casse exacte, passe avant le getter.
- `{{ count ?: 'aucun' }}` affiche « aucun » pour `0` → écrire `??`.

## QCM d'entraînement

**Question 1.** Que rend `{{ -7 // 2 }}` ?

- **A.** `-3`
- **B.** `-3.5`
- **C.** `-4`
- **D.** Une erreur : `//` est un commentaire

<details>
<summary>Correction</summary>

**Bonne réponse : C.** `//` arrondit vers le bas, pas vers zéro. B est le
résultat de `/`.

</details>

**Question 2.** Une class déclare `const status = 'CONST'` et `getStatus()` qui
rend `'GETTER'`. Que rend `{{ o.status }}` ?

- **A.** `GETTER` : les methods passent avant les constants
- **B.** `CONST`
- **C.** Une erreur : deux candidats
- **D.** `null` : le nom est ambigu

<details>
<summary>Correction</summary>

**Bonne réponse : B.** La constant est testée avant les methods, et son nom
correspond ici à la casse près. Avec `const STATUS`, ce serait le getter.

</details>

**Question 3.** Un template utilise `{{ attribute(user, field) }}` sous Twig
3.22. Que se passe-t-il ?

- **A.** La valeur est rendue, sans notice de deprecation
- **B.** Une `SyntaxError` : la function a été retirée en 3.15
- **C.** La valeur est rendue, avec une notice de deprecation
- **D.** Une `RuntimeError` : la function n'existe plus

<details>
<summary>Correction</summary>

**Bonne réponse : A.** Dépréciée depuis 3.15, mais sans notice en 3.22. C serait
vrai pour `spaceless`.

</details>

**Question 4.** `zero` vaut `0`. Que rend
`{{ zero ?: 'E' }}|{{ zero ?? 'C' }}` ?

- **A.** `E|C` : les deux remplacent `0`
- **B.** `0|0` : aucun ne remplace `0`
- **C.** `0|C` : seul `??` remplace `0`
- **D.** `E|0`

<details>
<summary>Correction</summary>

**Bonne réponse : D.** `?:` remplace une valeur fausse, `??` seulement une
valeur absente ou `null`.

</details>

## Fiche express

**Si tu dois retenir seulement 8 choses :**

- **`{{ }}`** affiche, **`{% %}`** exécute, **`{# #}`** commente.
- `foo.bar` : **array**, property, **constante à la casse exacte**, methods
  sans casse, **`__call`**.
- Rien trouvé : **`null`**, ou **`RuntimeError`** avec `strict_variables`.
- **`~`** concatène ; **`+`** additionne et casse sur des strings.
- **`//`** arrondit vers le bas ; **`**`** s'associe à droite.
- **`?:`** teste la vérité, **`??`** l'existence.
- **`has every`** sur vide : vrai ; **`has some`** : faux.
- **`attribute()`** dépréciée sans notice ; **`spaceless`** dépréciée avec.

Moyen mnémotechnique : **« array d'abord, constante exacte, method sans
casse »**.

```text
{{ }} affiche, {% %} exécute ; foo.bar = [key] > prop > CONST exacte > méthode/get/is/has (sans casse) > __call > null|erreur ;
~ concatène, // plancher, ** à droite, ?: vérité, ?? existence, and/or/not ; - et ~ pour les blancs
```

## Sources officielles

- [Twig 3.22, *Twig for Template Designers*](https://github.com/twigphp/Twig/blob/v3.22.0/doc/templates.rst)
- [Symfony Templates, « Template Variables »](https://github.com/symfony/symfony-docs/blob/8.0/templates.rst)
- [Twig 3.22, `CoreExtension::getAttribute()`](https://github.com/twigphp/Twig/blob/v3.22.0/src/Extension/CoreExtension.php)
- [Twig 3.22, filter `spaceless`](https://github.com/twigphp/Twig/blob/v3.22.0/doc/filters/spaceless.rst) et [function `attribute`](https://github.com/twigphp/Twig/blob/v3.22.0/doc/functions/attribute.rst)
- [php-src PHP-8.4, `add_006.phpt`](https://github.com/php/php-src/blob/PHP-8.4/Zend/tests/add_006.phpt)
