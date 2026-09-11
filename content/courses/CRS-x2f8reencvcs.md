---
id: CRS-x2f8reencvcs
official_item: OIT-vhd83fn6w9wy
title: "Twig syntax up to 3.22 version"
content_level: DEEP
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/templates.rst"
    anchor: "synopsis"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    commit_sha: "5079583d7313b0f0866ca32108036afcc072127d"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/templates.rst"
    anchor: "template-variables"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/src/Extension/CoreExtension.php"
    anchor: "getAttribute"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    commit_sha: "5079583d7313b0f0866ca32108036afcc072127d"
    verified_at: "2026-09-11"
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

| Délimiteur | Rôle |
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

1. `$foo['bar']` — tableau, `ArrayObject` ou `ArrayAccess`, et clé existante ;
2. `$foo->bar` — objet et propriété publique ;
3. `Foo::bar` — objet et **constante de classe** ;
4. `$foo->bar()` — objet et méthode publique ;
5. `$foo->getBar()` — *getter* ;
6. `$foo->isBar()` — *isser* ;
7. `$foo->hasBar()` — *hasser* ;
8. sinon `null` — **ou** une `Twig\Error\RuntimeError` si l'option
   `strict_variables` est active.

Trois conséquences pratiques. D'abord, le gabarit ne sait pas s'il manipule un
tableau ou un objet, ce qui permet de commencer avec des tableaux puis de passer
à des objets sans toucher aux gabarits. Ensuite, un tableau est essayé **avant**
un objet : un objet qui implémente `ArrayAccess` verra `$foo['bar']` gagner.
Enfin, la constante de classe passe **avant** toute méthode : une classe qui
déclare à la fois `const STATUS` et `getStatus()` verra `{{ order.status }}`
lire la constante, jamais le *getter*.

> **Deux sources divergent sur l'étape 3.** La documentation Symfony 8.0
> énumère sept étapes et ne mentionne pas la constante de classe. La
> documentation Twig v3.22.0 l'énumère, et le moteur l'implémente :
> `CoreExtension::getAttribute()` teste `\defined($object::class.'::'.$item)`
> entre la propriété et les méthodes. L'item du syllabus porte sur *Twig syntax
> up to 3.22 version*, donc le moteur fait foi ; la liste Symfony est une
> restitution abrégée, pas une contradiction. Retenir les huit étapes, et savoir
> que l'énoncé d'examen peut n'en citer que sept.

Pour forcer l'appel d'une méthode, il existe la syntaxe explicite
`{{ foo.bar() }}`, et `attribute(foo, 'bar')` lorsque le nom est dynamique.

## Les opérateurs

| Famille | Opérateurs |
|---|---|
| Arithmétique | `+ - * / % // **` |
| Comparaison | `== != < > <= >= <=>` |
| Logique | `and or not` — pas `&& \|\| !` |
| Chaînes | `~` pour **concaténer** |
| Test | `is` : `is defined`, `is empty`, `is null`, `is iterable`, `is same as` |
| Confort | `?:` (Elvis), `??` (coalescence) |
| Filtre | `\|` |

Trois pièges reviennent :

- la concaténation est `~`, pas `+`. `+` est l'addition : sur deux chaînes non
  numériques, PHP 8.4 lève une `TypeError`, donc `{{ 'a' + 'b' }}` casse le
  rendu au lieu de concaténer ;
- `//` est la division **entière**, `/` la division ordinaire ;
- `?:` retourne l'opérande de gauche s'il est *vrai*, `??` s'il est *défini*.
  Sur une variable absente, `?:` déclenche `strict_variables`, `??` non.

## Affectation et espaces

`{% set total = 0 %}` déclare une variable ; `{% set body %}…{% endset %}`
capture un bloc entier.

Les délimiteurs peuvent absorber les espaces alentour : `{{-` et `-}}` suppriment
le blanc avant et après. `{% apply spaceless %}` retire le blanc entre balises
HTML.

## Pièges d'examen

- `{{ }}` affiche, `{% %}` exécute — un `{% %}` n'affiche jamais rien.
- L'ordre de résolution commence par le **tableau**, pas par la propriété.
- Une **constante de classe** est lue avant le *getter* du même nom.
- `and` / `or` / `not`, jamais `&&` / `||` / `!`.
- `~` concatène ; `+` additionne.
- Sans `strict_variables`, une variable inconnue vaut `null` silencieusement.

## Points clés

- Trois délimiteurs, un rôle chacun.
- Huit étapes de résolution pour `foo.bar`, tableau d'abord, constante de classe
  avant les méthodes, `null` ou erreur au bout selon `strict_variables`.
- Opérateurs logiques en mots ; `~` pour concaténer ; `//` pour la division
  entière.
- `{{- -}}` contrôle les espaces.

## Sources officielles

- [Twig 3.22, *Twig for Template Designers*](https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/templates.rst)
- [Symfony Templates, « Template Variables »](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/templates.rst)
- [Twig 3.22, `CoreExtension::getAttribute()`](https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/src/Extension/CoreExtension.php)
