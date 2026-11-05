---
id: CRS-9g4qrgfs7nm4
official_item: OIT-c9pjp03cv4bq
title: "Naming conventions"
content_level: MINIMAL
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-26"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/contributing/code/standards.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/contributing/code/standards.rst"
    anchor: "naming-conventions"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/contributing/code/conventions.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/contributing/code/conventions.rst"
    anchor: "naming-a-method"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    verified_at: "2026-09-21"
outcome_sections:
  OUT-qy3807tsb7qw: "La casse"
  OUT-2njr3rsw6g05: "Préfixes et suffixes"
  OUT-3r39kw6erehf: "Préfixes et suffixes"
---

## Objectif

Reconnaître les conventions de nommage officielles de Symfony. La convention de
nommage propre aux controllers est traitée dans le lot Controllers.

## L'essentiel

Les standards de Symfony fixent la **casse** de chaque élément, quelques
**préfixes et suffixes**, deux préfixes d'attributes, et douze noms de
methods pour une collection. Rien n'est à déduire : tout se retient, et
l'examen vise les exceptions — un cas d'enum, l'`Abstract` en préfixe,
`getXXXs()` pour `all()`.

## Le schéma

```text
camelCase              variables, functions, methods, arguments      hasSession()
snake_case             config, routes, variables et templates Twig  framework.csrf_protection
SCREAMING_SNAKE_CASE   constantes                                    InputArgument::IS_ARRAY
UpperCamelCase         classes, interfaces, traits, enums, cas d'enum, files PHP
Abstract…  (préfixe, sauf *TestCase)   …Interface  …Trait  …Exception   (suffixes)
#[As…] configure un service      #[Map…] remplit un argument de controller
```

## L'exemple : une collection principale

```php
$jar->all();            // relation principale : un CookieJar a des Cookie
$jar->getOptions();     // relation secondaire : getXXXs(), pas allOptions()
```

## La casse

| Élément | Casse | Exemple |
|---|---|---|
| variables, functions, methods, arguments | `camelCase` | `hasSession()` |
| parameters de configuration, route names, variables Twig | `snake_case` | `framework.csrf_protection` |
| constantes | `SCREAMING_SNAKE_CASE` | `InputArgument::IS_ARRAY` |
| cas d'enum | `UpperCamelCase` | `InputArgumentMode::IsArray` |
| classes, interfaces, traits, enums, files PHP | `UpperCamelCase` | `EnvVarProcessor.php` |
| templates Twig et ressources web | `snake_case` | `section_layout.html.twig` |

Dans les PHPDoc et les transtypages : `bool`, `int`, `float` — jamais `boolean`,
`integer`, `double` ou `real`.

## Préfixes et suffixes

- `Abstract` en **préfixe** pour les abstract classes — sauf les `*TestCase`
  de PHPUnit.
- `Interface`, `Trait`, `Exception` en **suffixe** ; aucun suffixe pour une
  class ordinaire ou une enum.
- `As...` quand l'attribute configure un **service** (`#[AsCommand]`) ;
  `Map...` quand il remplit un **argument de controller** (`#[MapEntity]`).

## Nommer une method de collection

`conventions.rst` normalise douze noms quand un object a une relation
**principale** : `get()`, `set()`, `has()`, `all()`, `replace()`, `remove()`,
`clear()`, `isEmpty()`, `add()`, `register()`, `count()`, `keys()`. Un
`CookieJar` a beaucoup de `Cookie` : la convention s'applique ; un `Input` de
console a des arguments *et* des options : elle ne s'applique pas.

| Relation principale | Autre relation |
|---|---|
| `all()` | **`getXXXs()`** — pas `allXXX()` |
| `replace()` | **`setXXXs()`** — pas `replaceXXXs()` |
| `keys()` | **aucune** |
| *aucune* | **`replaceXXX()`** |

`setXXX()` peut **ajouter** un élément ; `replaceXXX()` ne le peut pas et doit
lever une exception sur une key inconnue.

## Pièges d'examen

**Un cas d'enum s'écrit `UpperCamelCase`, pas en majuscules.** Les standards
l'illustrent par `InputArgumentMode::IsArray` face à la constant
`InputArgument::IS_ARRAY` ; seule la constant existe dans Console 8.0 —
l'enum n'est qu'un exemple de nommage.

**`Abstract` est un préfixe ; `Interface`, `Trait` et `Exception` sont des
suffixes.** Il n'y a en revanche **aucun** suffixe pour une class ordinaire ou
une enum : ni `...Class`, ni `...Enum`.

**`As...` et `Map...` ne sont pas interchangeables.** `As...` configure un
service (`#[AsCommand]`), `Map...` concerne un argument de controller
(`#[MapEntity]`).

**`all()` devient `getXXXs()`, pas `allXXX()`.** De même `replace()` devient
`setXXXs()`. Les deux lignes les moins régulières de la table.

**`setXXX()` et `replaceXXX()` diffèrent par un droit.** Seul le premier peut
ajouter ; le second doit lever une exception sur une key inconnue.

## Application

- Un attribute qui déclare un subscriber → `#[As...]`.
- Une class `Order` qui expose toutes ses lignes, relation secondaire →
  `getLines()`.

## QCM d'entraînement

**Question 1.** Quelle est la forme suffixée de `keys()` pour une relation
secondaire ?

- **A.** `keysXXX()`
- **B.** `getXXXKeys()`, sur le modèle de `getXXXs()`
- **C.** Il n'y en a pas
- **D.** `XXXKeys()`, sans préfixe

<details>
<summary>Correction</summary>

**Bonne réponse : C.** La table de `conventions.rst` ne donne aucune variante
à `keys()` hors de la relation principale ; c'est une case vide à retenir.

</details>

**Question 2.** Quel type écrire dans un PHPDoc selon les standards ?

- **A.** `boolean`, la forme longue
- **B.** `bool`
- **C.** `Boolean`, comme une class
- **D.** Indifféremment `bool` ou `boolean`

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Les standards imposent les formes courtes — `bool`,
`int`, `float` — et écartent `boolean`, `integer`, `double` et `real`.

</details>

**Question 3.** Quelles abstract classes échappent au préfixe `Abstract` ?

- **A.** Les `*TestCase` de PHPUnit
- **B.** Aucune : la règle est absolue
- **C.** Celles qui implémentent une interface
- **D.** Les classes de base des bundles tiers

<details>
<summary>Correction</summary>

**Bonne réponse : A.** La règle prévoit cette seule exception, pour garder le
nom attendu par PHPUnit ; toutes les autres abstract classes sont préfixées.

</details>

**Question 4.** `replaceItem('inconnu', $v)` reçoit une key absente de la
collection. Que doit faire la method ?

- **A.** Ajouter l'élément, comme `setItem()`
- **B.** Ne rien faire, en silence
- **C.** Rendre `false`
- **D.** Lever une exception

<details>
<summary>Correction</summary>

**Bonne réponse : D.** `replaceXXX()` ne peut pas ajouter d'élément : une key
inconnue doit lever, là où `setXXX()` aurait ajouté.

</details>

## Fiche express

**Si tu dois retenir seulement 6 choses :**

- **`camelCase`** le code, **`snake_case`** config, routes et templates.
- Cas d'enum en **`UpperCamelCase`**, constantes en `SCREAMING_SNAKE_CASE`.
- **`Abstract`** en préfixe ; `Interface`, `Trait`, `Exception` en suffixe.
- **`As...`** = service ; **`Map...`** = argument de controller.
- `all()` → **`getXXXs()`**, `replace()` → **`setXXXs()`**, `keys()` → rien.
- `replaceXXX()` ne peut pas ajouter ; `setXXX()` le peut.

Moyen mnémotechnique : **« Abstract devant, le reste derrière »**.

## Sources officielles

- [Coding Standards, section « Naming Conventions »](https://github.com/symfony/symfony-docs/blob/8.0/contributing/code/standards.rst)
- [Conventions, section « Naming a Method »](https://github.com/symfony/symfony-docs/blob/8.0/contributing/code/conventions.rst)

