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
    anchor: "naming-conventions"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
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

## Points clés

- `camelCase` pour le code, `snake_case` pour la configuration et les gabarits.
- `Abstract` en préfixe ; `Interface`, `Trait`, `Exception` en suffixe.
- `As...` = service ; `Map...` = argument de contrôleur.
- Cas d'énumération en `UpperCamelCase`, constantes en `SCREAMING_SNAKE_CASE`.

## Sources officielles

- [Coding Standards, section « Naming Conventions »](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/contributing/code/standards.rst)
