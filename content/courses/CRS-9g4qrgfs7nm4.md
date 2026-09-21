---
id: CRS-9g4qrgfs7nm4
official_item: OIT-c9pjp03cv4bq
title: "Naming conventions"
content_level: MINIMAL
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
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
---

## Objectif

Reconnaître les conventions de nommage officielles de Symfony. La convention de
nommage propre aux contrôleurs est traitée dans le lot Controllers.

## La casse

| Élément | Casse | Exemple |
|---|---|---|
| variables, fonctions, méthodes, arguments | `camelCase` | `hasSession()` |
| paramètres de configuration, noms de route, variables Twig | `snake_case` | `framework.csrf_protection` |
| constantes | `SCREAMING_SNAKE_CASE` | `InputArgument::IS_ARRAY` |
| cas d'énumération | `UpperCamelCase` | `InputArgumentMode::IsArray` |
| classes, interfaces, traits, énumérations | `UpperCamelCase` | `ConsoleLogger` |
| fichiers PHP | `UpperCamelCase` | `EnvVarProcessor.php` |
| gabarits Twig et ressources web | `snake_case` | `section_layout.html.twig` |

## Préfixes et suffixes

- Préfixe `Abstract` pour les classes abstraites — sauf les `*TestCase` de
  PHPUnit.
- Suffixe `Interface` pour les interfaces.
- Suffixe `Trait` pour les traits.
- Suffixe `Exception` pour les exceptions.
- **Aucun** suffixe dédié pour les classes ordinaires et les énumérations : pas
  de `...Class`, pas de `...Enum`.

Les attributs PHP suivent deux préfixes selon leur rôle :

- `As...` quand l'attribut configure un **service** : `#[AsCommand]`,
  `#[AsEventListener]` ;
- `Map...` quand il concerne un **argument de contrôleur** : `#[MapEntity]`,
  `#[MapCurrentUser]`.

## Nommer une méthode de collection

Un second document, `conventions.rst`, normalise **douze** noms de méthodes
quand un objet a une relation « principale » avec des choses apparentées :

`get()` · `set()` · `has()` · `all()` · `replace()` · `remove()` · `clear()` ·
`isEmpty()` · `add()` · `register()` · `count()` · `keys()`

La convention ne s'applique **que** si la relation principale est évidente. Un
`CookieJar` a beaucoup de `Cookie` — elle s'applique. Un `Input` de console a
des arguments *et* des options, sans relation principale — elle ne s'applique
pas.

Sinon, on suffixe par le nom de la chose. Quatre lignes ne se déduisent pas :

| Relation principale | Autre relation |
|---|---|
| `all()` | **`getXXXs()`** — pas `allXXX()` |
| `replace()` | **`setXXXs()`** — pas `replaceXXXs()` |
| `keys()` | **aucune** |
| *aucune* | **`replaceXXX()`** |

Et `setXXX()` n'est pas `replaceXXX()` : le premier peut **ajouter** un élément,
le second ne le peut pas et doit **lever une exception** sur une clé inconnue.

## Types

Dans les PHPDoc et les transtypages : `bool`, `int`, `float` — jamais `boolean`,
`integer`, `double` ou `real`.

## Pièges d'examen

**Un cas d'énumération s'écrit `UpperCamelCase`, pas en majuscules.**
`InputArgumentMode::IsArray`, alors que la constante de classe équivalente
s'écrit `IS_ARRAY`. Les deux cohabitent dans le même code.

**`Abstract` est un préfixe ; `Interface`, `Trait` et `Exception` sont des
suffixes.** Il n'y a en revanche **aucun** suffixe pour une classe ordinaire ou
une énumération : ni `...Class`, ni `...Enum`.

**`As...` et `Map...` ne sont pas interchangeables.** `As...` configure un
service (`#[AsCommand]`), `Map...` concerne un argument de contrôleur
(`#[MapEntity]`).

**`all()` devient `getXXXs()`, pas `allXXX()`.** De même `replace()` devient
`setXXXs()`. Les deux lignes les moins régulières du tableau.

**`setXXX()` et `replaceXXX()` diffèrent par un droit.** Seul le premier peut
ajouter ; le second doit lever une exception sur une clé inconnue.

## Points clés

- `camelCase` pour le code, `snake_case` pour la configuration et les gabarits.
- `Abstract` en préfixe ; `Interface`, `Trait`, `Exception` en suffixe.
- `As...` = service ; `Map...` = argument de contrôleur.
- Cas d'énumération en `UpperCamelCase`, constantes en `SCREAMING_SNAKE_CASE`.
- Douze noms normalisés pour une relation principale ; sinon suffixe `XXX`,
  avec `getXXXs()` pour `all()` et `setXXXs()` pour `replace()`.
- `replaceXXX()` ne peut pas ajouter ; `setXXX()` le peut.

## Sources officielles

- [Coding Standards, section « Naming Conventions »](https://github.com/symfony/symfony-docs/blob/8.0/contributing/code/standards.rst)
- [Conventions, section « Naming a Method »](https://github.com/symfony/symfony-docs/blob/8.0/contributing/code/conventions.rst)
