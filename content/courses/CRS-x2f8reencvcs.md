---
id: CRS-x2f8reencvcs
official_item: OIT-vhd83fn6w9wy
title: "Twig syntax up to 3.22 version"
content_level: DEEP
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-08"
official_sources:
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/templates.rst"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/doc/templates.rst"
    anchor: "synopsis"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    commit_sha: "5079583d7313b0f0866ca32108036afcc072127d"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/templates.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/templates.rst"
    anchor: "template-variables"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/src/Extension/CoreExtension.php"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/src/Extension/CoreExtension.php"
    anchor: "getAttribute"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    commit_sha: "5079583d7313b0f0866ca32108036afcc072127d"
    verified_at: "2026-09-11"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/filters/spaceless.rst"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/doc/filters/spaceless.rst"
    symbol_or_lines: "deprecated as of Twig 3.12"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/functions/attribute.rst"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/doc/functions/attribute.rst"
    symbol_or_lines: "deprecated as of Twig 3.15"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/php/php-src/PHP-8.4/Zend/tests/add_006.phpt"
    readable_url: "https://github.com/php/php-src/blob/PHP-8.4/Zend/tests/add_006.phpt"
    symbol_or_lines: "Unsupported operand types"
    repository: "php/php-src"
    branch: "PHP-8.4"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/CHANGELOG"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/CHANGELOG"
    symbol_or_lines: "3.7.0: Add support for the ...spread operator on arrays and hashes"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    verified_at: "2026-10-08"
outcome_sections:
  OUT-491pkbwc6e8x: "Les trois délimiteurs"
  OUT-4wpk7x3epxym: "`foo.bar` : l'ordre de résolution"
  OUT-ap22qz4fkjra: "`foo.bar` : l'ordre de résolution"
  OUT-93svekvx55t0: "Les opérateurs"
  OUT-xpcwsqhycgnr: "`foo.bar` : l'ordre de résolution"
  OUT-je4qjh1s6yw0: "Affectation et espaces"
---

## Objectif

Maîtriser la syntaxe de base de Twig 3.22 : les trois délimiteurs, la façon dont
`foo.bar` est résolu, et le jeu d'opérateurs. C'est le socle des treize autres
items du lot.

La version compte : le syllabus dit « Twig syntax **up to 3.22** version ». Ce
qu'une version ultérieure a ajouté est hors périmètre.

## Prérequis

Aucun.

## Les trois délimiteurs

| Délimiteur | Role |
|---|---|
| `{{ … }}` | **affiche** le résultat d'une expression |
| `{% … %}` | **exécute** une instruction : `if`, `for`, `extends`, `set` |
| `{# … #}` | **commentaire**, jamais rendu |

La confusion classique est d'écrire `{% user.name %}` pour afficher, ou
`{{ if … }}` pour tester. Un délimiteur dit *ce qu'on fait*, pas *ce qu'on
manipule*.

## `foo.bar` : l'ordre de résolution

C'est le mécanisme central, et il est ordonné. Pour `{{ foo.bar }}`, Twig essaie
dans cet ordre :

1. `$foo['bar']` — array, `ArrayObject` ou `ArrayAccess`, et key existante ;
2. `$foo->bar` — object et property publique ;
3. `Foo::bar` — object et **constante de class** ;
4. `$foo->bar()` — object et method publique ;
5. `$foo->getBar()` — *getter* ;
6. `$foo->isBar()` — *isser* ;
7. `$foo->hasBar()` — *hasser* ;
8. `$foo->__call('bar')`, si la class déclare `__call()` ;
9. sinon `null` — **ou** une `Twig\Error\RuntimeError` si l'option
   `strict_variables` est active.

La recherche de method ignore la casse, et un *hasser* est écarté quand un
*isser* du même nom existe.

Trois conséquences pratiques. D'abord, le template ne sait pas s'il manipule un
array ou un object, ce qui permet de commencer avec des arrays puis de passer
à des objects sans toucher aux templates. Ensuite, un array est essayé **avant**
un object : un object qui implémente `ArrayAccess` verra `$foo['bar']` gagner.
Enfin, la constante de class passe **avant** toute method — si son nom
correspond à la casse près, alors que les methods sont cherchées sans tenir
compte de la casse. Exécuté : avec `const STATUS` et `getStatus()`,
`{{ order.status }}` appelle le *getter* ; seule une constante nommée `status`
l'aurait masqué.

> **Deux sources divergent sur l'étape 3.** La documentation Symfony 8.0
> énumère sept étapes et ne mentionne pas la constante de class. La
> documentation Twig v3.22.0 l'énumère, et le moteur l'implémente :
> `CoreExtension::getAttribute()` teste `\defined($object::class.'::'.$item)`
> entre la property et les methods. L'item du syllabus porte sur *Twig syntax
> up to 3.22 version*, donc le moteur fait foi ; la liste Symfony est une
> restitution abrégée, pas une contradiction. Retenir l'ordre du moteur, et
> savoir que l'énoncé d'examen peut n'en citer que sept.

Pour forcer l'appel d'une method, il existe la syntaxe explicite
`{{ foo.bar() }}`. Lorsque le nom est dynamique ou contient un tiret, Twig 3.15
a ajouté l'expression entre parenthèses : `{{ user.(name) }}`,
`{{ user.('first-name') }}`. La function `attribute()`, qui jouait ce role,
est **dépréciée depuis 3.15**.

## Les opérateurs

| Famille | Opérateurs |
|---|---|
| Arithmétique | `+ - * / % // **` |
| Comparaison | `== != < > <= >= <=>` |
| Logique | `and or not xor` — pas `&& \|\| !` ; bit à bit : `b-and b-or b-xor` |
| Strings | `~` pour **concaténer** ; `starts with`, `ends with`, `matches` |
| Appartenance | `in`, `not in` ; `..` construit une plage |
| Itérables | `has some`, `has every`, avec une function fléchée |
| Test | `is` : `is defined`, `is empty`, `is null`, `is iterable`, `is same as` |
| Confort | `?:` (Elvis), `??` (coalescence) |
| Filter | `\|` |
| Functions | `=>` crée une function fléchée ; `...` étend une séquence ou un array (3.7), les arguments d'un appel (3.15) |

Trois pièges reviennent :

- la concaténation est `~`, pas `+`. `+` est compilé tel quel en PHP : sur deux
  strings non numériques, PHP 8.4 lève une `TypeError` (« Unsupported operand
  types »), donc `{{ 'a' + 'b' }}` casse le rendu au lieu de concaténer ;
- `//` est la division **entière**, `/` la division ordinaire ;
- `?:` retourne l'opérande de gauche s'il est *vrai*, `??` s'il est *défini*.
  Sur une variable absente, `?:` déclenche `strict_variables`, `??` non ;
- sur un itérable vide, `has every` vaut `true` et `has some` vaut `false`.

## Affectation et espaces

`{% set total = 0 %}` déclare une variable ; `{% set body %}…{% endset %}`
capture un block entier.

Twig retire d'office le premier saut de ligne qui suit un tag. Au-delà, deux
modificateurs, d'un côté ou des deux :

- `-` — `{{-`, `-}}`, `{%-`, `-%}` — supprime **tout** le blanc de ce côté,
  sauts de ligne compris ;
- `~` supprime le blanc **sauf** les sauts de ligne ; placé à droite, il
  désactive aussi le retrait automatique du premier saut de ligne.

Le filter `spaceless`, souvent employé avec `{% apply %}`, est **déprécié
depuis Twig 3.12** : la documentation renvoie aux modificateurs ci-dessus.

## Pièges d'examen

- `{{ }}` affiche, `{% %}` exécute — un `{% %}` n'affiche jamais rien.
- L'ordre de résolution commence par le **array**, pas par la property.
- Une **constante de class** est lue avant le *getter* si son nom est
  identique, casse comprise : `const STATUS` ne masque pas `getStatus()`.
- `and` / `or` / `not`, jamais `&&` / `||` / `!`.
- `~` concatène ; `+` additionne.
- Sans `strict_variables`, une variable inconnue vaut `null` silencieusement.
- `attribute()` (3.15) et `spaceless` (3.12) sont dépréciés en 3.22.

## Points clés

- Trois délimiteurs, un role chacun.
- Neuf étapes de résolution pour `foo.bar`, array d'abord, constante de
  class avant les methods, `__call()` en dernier recours, `null` ou erreur au
  bout selon `strict_variables`.
- Opérateurs logiques en mots ; `~` pour concaténer ; `//` pour la division
  entière.
- `-` et `~` contrôlent les espaces ; `spaceless` est déprécié.

## Sources officielles

- [Twig 3.22, *Twig for Template Designers*](https://github.com/twigphp/Twig/blob/v3.22.0/doc/templates.rst)
- [Symfony Templates, « Template Variables »](https://github.com/symfony/symfony-docs/blob/8.0/templates.rst)
- [Twig 3.22, `CoreExtension::getAttribute()`](https://github.com/twigphp/Twig/blob/v3.22.0/src/Extension/CoreExtension.php)
- [Twig 3.22, filter `spaceless`](https://github.com/twigphp/Twig/blob/v3.22.0/doc/filters/spaceless.rst) et [function `attribute`](https://github.com/twigphp/Twig/blob/v3.22.0/doc/functions/attribute.rst)
- [php-src PHP-8.4, `add_006.phpt`](https://github.com/php/php-src/blob/PHP-8.4/Zend/tests/add_006.phpt)
