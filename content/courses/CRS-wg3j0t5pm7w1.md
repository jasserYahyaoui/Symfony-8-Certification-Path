---
id: CRS-wg3j0t5pm7w1
official_item: OIT-1cj08dhtp9hj
title: "Restrict URL parameters"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-26"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/routing.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/routing.rst"
    anchor: "routing-requirements"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Routing/Generator/UrlGenerator.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Generator/UrlGenerator.php"
    symbol_or_lines: "strictRequirements, doGenerate"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/Configuration.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/Configuration.php"
    symbol_or_lines: "strict_requirements"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Routing/Requirement/Requirement.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Requirement/Requirement.php"
    symbol_or_lines: "DIGITS, POSITIVE_INT"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Routing/RouteCompiler.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/RouteCompiler.php"
    symbol_or_lines: "compilePattern lines 182-183 — a UTF-8 requirement without the \"utf8\" option throws a LogicException"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-10-24"
outcome_sections:
  OUT-gxaj5vfr68nt: "Trois façons de l'écrire"
  OUT-t3ajatgs8hh2: "Le problème qu'elle résout"
  OUT-7hyaem7hsh5h: "Trois façons de l'écrire"
  OUT-6m9a09sd49jg: "Deux pièges"
---

## Objectif

Contraindre la valeur d'un parameter de route, et savoir quand ce requirement
s'applique.

## L'essentiel

Un requirement est une **expression régulière PHP** que la valeur d'un
parameter doit vérifier pour que la route corresponde. Il départage
`/blog/{page}` et `/blog/{slug}` sans dépendre de l'ordre de déclaration. Il
s'écrit en option, inline ou par l'enum `Requirement`, et il vaut **aussi à la
génération** : par défaut, une valeur non conforme y lève une exception.

## Le schéma

```text
écrire       requirements: ['page' => '[0-9]+'] · /blog/{page<[0-9]+>} · Requirement::DIGITS
Requirement  enum sans cas, des constantes string : DIGITS [0-9]+ (0, 007 admis) · POSITIVE_INT [1-9][0-9]*
match        requirement non vérifié → route suivante · \p{…} exige utf8: true, sinon LogicException
default      n'a pas à vérifier le requirement (elle n'est pas dans l'URL)
génération   strict_requirements  true (défaut) → InvalidParameterException
                                  false → string vide + log · null → aucune vérification
```

## L'exemple

```php
#[Route('/blog/{page}', name: 'blog_list', requirements: ['page' => Requirement::DIGITS])]
public function list(int $page = 1): Response {}

#[Route('/blog/{slug}', name: 'blog_show')]
public function show(string $slug): Response {}
```

## Le problème qu'elle résout

`/blog/{page}` et `/blog/{slug}` sont indiscernables : sans requirement,
`/blog/mon-article` correspond aux deux, et la **première route déclarée**
gagne. Avec `[0-9]+` sur `page`, `/blog/2` va à `blog_list` et
`/blog/mon-article` à `blog_show`, quel que soit l'ordre.

## Trois façons de l'écrire

| Forme | Exemple |
|---|---|
| option `requirements` | `requirements: ['page' => '[0-9]+']` |
| inline dans le path | `/blog/{page<[0-9]+>}` |
| constante de l'enum | `requirements: ['page' => Requirement::DIGITS]` |

L'enum `Symfony\Component\Routing\Requirement\Requirement` n'a **aucun cas** :
elle porte des constants string — chiffres, dates, UUID. En YAML, on l'utilise
avec `!php/const`. `DIGITS` vaut `[0-9]+` et laisse passer `/blog/0` ;
`POSITIVE_INT` vaut `[1-9][0-9]*` et exclut `0`.

## Ce qui est autorisé

Un requirement peut contenir un **parameter de configuration**, pour définir une
expression compliquée une fois. Il accepte les **properties Unicode PCRE** avec
l'option `utf8: true` de la route : `\p{Lu}` correspond alors à toute
majuscule.

## Deux pièges

Une default value **n'est pas tenue** de satisfaire le requirement : celui-ci
filtre l'URL entrante, et la default value n'y est pas.

Le generator relit chaque requirement. Selon `framework.router.strict_requirements` :
`true`, le défaut, lève une `InvalidParameterException` ; `false` journalise
l'erreur et rend une **string vide** — l'aide de l'option annonce `null`, le code
8.0 rend `''` ; `null` ne vérifie rien. L'aide suggère `true` en développement,
`false` ou `null` en production.

## Pièges d'examen

**Sans requirement, deux routes de même forme sont départagées par l'ordre de
déclaration**, pas par ce qui « ressemble » le plus. Contraindre le parameter
rend l'ordre indifférent — c'est le vrai service rendu.

**Un requirement est une expression régulière PHP**, avec ce que cela implique :
une expression fragile passe sans que rien ne prévienne. Les properties Unicode
(`\p{Lu}`) y exigent l'option `utf8: true` ; sans elle, la compilation de la
route lève une `LogicException` — exécuté avec Routing 8.0.15.

**Le requirement vaut aussi à la génération.** Générer `blog_list` avec
`page: 'abc'` lève une exception par défaut.

## Application

- Une pagination qui ne doit pas accepter `/blog/0` → `Requirement::POSITIVE_INT`.
- Un slug accentué en majuscule initiale → `\p{Lu}\p{Ll}+` et `utf8: true`.

## QCM d'entraînement

**Question 1.** La route `/p/{n}` porte `Requirement::POSITIVE_INT`. Le client
appelle `/p/0`. Que se passe-t-il ?

- **A.** Elle correspond, `n` vaut `0`
- **B.** Elle ne correspond pas
- **C.** Elle correspond, `n` vaut `null`
- **D.** Une `InvalidParameterException`

<details>
<summary>Correction</summary>

**Bonne réponse : B.** L'expression `[1-9][0-9]*` exclut un zéro initial :
l'URL ne vérifie pas le requirement, et le matcher passe à la route suivante.

</details>

**Question 2.** `strict_requirements` vaut `false`. Le code génère `blog_list`
avec `page: 'abc'`. Que rend le generator ?

- **A.** Une string vide
- **B.** `null`
- **C.** `/blog/abc`
- **D.** Une `InvalidParameterException`

<details>
<summary>Correction</summary>

**Bonne réponse : A.** En mode non strict, l'erreur est journalisée et le code
8.0 rend `''`, malgré l'aide de l'option qui annonce `null`.

</details>

**Question 3.** Même génération, mais `strict_requirements` vaut `null`. Que
rend le generator ?

- **A.** Une string vide
- **B.** `null`
- **C.** `/blog/abc`
- **D.** Une `InvalidParameterException`

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Avec `null`, le requirement n'est plus du tout vérifié :
la valeur est insérée telle quelle dans l'URL produite.

</details>

**Question 4.** La route `/blog/{page}` a le requirement `[0-9]+` et la default
value `first`. Que produit `generate('blog_list')` sans parameter ?

- **A.** `/blog/first`
- **B.** Une `InvalidParameterException`
- **C.** `/blog/0`
- **D.** `/blog`

<details>
<summary>Correction</summary>

**Bonne réponse : D.** La default value n'a pas à vérifier le requirement, et
un parameter final égal à son défaut est omis de l'URL générée.

</details>

## Fiche express

**Si tu dois retenir seulement 6 choses :**

- Requirement = **regex PHP** ; sans lui, l'**ordre** de déclaration départage.
- Option, inline **`{page<[0-9]+>}`**, ou enum **`Requirement`** (sans cas).
- `DIGITS` accepte **0** ; `POSITIVE_INT` non.
- `\p{…}` exige **`utf8: true`**.
- Default value : **pas tenue** de vérifier le requirement.
- Génération : `true` → **exception**, `false` → **`''`**, `null` → rien.

Moyen mnémotechnique : **« Le requirement vaut à l'arrivée comme à la génération »**.

## Sources officielles

- [Routing, section « Parameters Validation »](https://github.com/symfony/symfony-docs/blob/8.0/routing.rst)

