---
id: CRS-vkgr06y3x72g
official_item: OIT-ff0kghjbzvpm
title: "Set default values to URL parameters"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-26"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/routing.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/routing.rst"
    anchor: "optional-parameters"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Routing/RouteCompiler.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/RouteCompiler.php"
    symbol_or_lines: "firstOptional, important"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Routing/Route.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Route.php"
    symbol_or_lines: "extractInlineDefaultsAndRequirements"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Routing/Loader/AttributeClassLoader.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Loader/AttributeClassLoader.php"
    symbol_or_lines: "addRoute"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
outcome_sections:
  OUT-w99f0fjydtp0: "La règle de base"
  OUT-fhjj5c6d002g: "Tout ce qui suit un parameter facultatif doit l'être"
  OUT-qva279gdyyra: "Le point d'exclamation"
  OUT-33d0j3zrrwmz: "Défaut et requirement"
---

## Objectif

Rendre un parameter facultatif, et connaître les deux règles qui en découlent.

## L'essentiel

Un parameter de path est obligatoire tant qu'il n'a pas de default value. En
attributes, cette valeur est **l'argument du controller**. Deux règles en
découlent : tout ce qui suit un parameter facultatif doit l'être aussi, et une
default value en fin de path disparaît de l'URL générée — sauf avec `{!page}`,
qui rend aussi le parameter obligatoire à l'appariement.

## Le schéma

```text
où écrire     attribute → list(int $page = 1) · YAML/PHP → defaults · inline → {page?1}, {page?} = null
loader        copie le défaut d'argument si : parameter homonyme dans le path, pas déjà dans defaults,
              valeur scalaire, null ou cas d'enum adossée (sa value)
facultatif    a une default value ET rien d'obligatoire ne le suit ET pas de !
génération    défaut en fin de path → omis · au milieu → gardé · {!page} → toujours écrit
requirement   la default value n'a pas à le vérifier
```

## L'exemple

```php
#[Route('/blog/{page}', name: 'blog_list', requirements: ['page' => '\d+'])]
public function list(int $page = 1): Response {}   // /blog → page = 1 ; génération → /blog
```

## La règle de base

Dès qu'un parameter apparaît dans le path, il **doit** avoir une valeur :
`/blog/{page}` ne correspond pas à `/blog`. Une default value le rend
facultatif.

| Format | Où |
|---|---|
| attribute PHP | **argument du controller** : `list(int $page = 1)` |
| YAML / PHP | option `defaults` |
| inline | `{page?1}`, combinable : `/blog/{page<[0-9]+>?1}` |

Un `?` sans valeur, `{page?}`, donne la default value `null` : l'argument du
controller doit alors être nullable.

### D'où vient le défaut en attributes

`AttributeClassLoader` copie dans la route la default value d'un argument qui
**porte le nom d'un parameter du path** et n'a pas déjà de défaut dans
`defaults` — qui l'emporte donc. Seules les valeurs scalaires, `null` et les
cas d'enum adossée, dont il copie la `value`, sont reprises. Un argument par
défaut sans parameter homonyme ne rend rien facultatif.

## Tout ce qui suit un parameter facultatif doit l'être

`/blog/{slug}/{page}` admet plusieurs parameters facultatifs, mais tout ce qui
suit un parameter facultatif doit l'être aussi. `/{page}/blog` est valide,
mais `page` y reste **toujours obligatoire**, default value ou non : `/blog`
ne correspond pas.

## Le point d'exclamation

Une default value **en fin de path** disparaît de l'URL générée : `blog_list`
avec `page = 1` produit `/blog`. Au milieu du path, elle reste. Pour forcer
l'inclusion, on écrit `/blog/{!page}`.

La documentation n'en décrit que cet effet. `RouteCompiler` ne rend facultative
qu'une variable « not important » ayant une default value : avec `{!page}`,
`page` redevient **obligatoire à l'appariement**, et `/blog` ne correspond
plus. Le `!` agit des deux côtés.

## Défaut et requirement

La default value n'a pas à satisfaire le requirement : elle ne vient pas de
l'URL, donc elle n'est pas filtrée.

## Pièges d'examen

**Tout ce qui suit un parameter facultatif doit l'être aussi.** La conséquence
surprend : un path qui place le parameter avant un segment fixe rend ce
parameter **toujours obligatoire**, default value ou non.

**Une default value en fin de path disparaît de l'URL générée.** L'inclure
de force s'écrit `{!page}`.

**`{!page}` rend aussi le parameter obligatoire à l'appariement.** Une variable
importante n'est jamais facultative pour le compiler.

**Un défaut d'argument ne compte que si le path porte un parameter du même
nom.**

**La default value n'a pas à satisfaire le requirement du parameter.** Le
requirement porte sur ce qui vient de l'URL, pas sur ce que la route fournit
elle-même.

## Application

- Une pagination où `/blog` vaut la page 1 → `list(int $page = 1)`.
- Des URLs toujours explicites, `/blog/1` compris → `{!page}`.

## QCM d'entraînement

**Question 1.** La route `/blog/{page}/{sort}` a les défauts `page = 1` et
`sort = 'date'`. Que produit sa génération avec `page = 1` et `sort = 'title'` ?

- **A.** `/blog/title`
- **B.** `/blog/1/title`
- **C.** `/blog?sort=title`
- **D.** Une exception : `page` manque

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Seules les default values en fin de path sont omises ;
`page` précède une valeur écrite, il reste donc dans l'URL.

</details>

**Question 2.** La route porte `#[Route('/blog/{page}', defaults: ['page' => 5])]`
et l'action `list(int $page = 1)`. Le client appelle `/blog`. Que vaut `page` ?

- **A.** `1`, celui de l'argument
- **B.** Une exception : deux défauts
- **C.** `5`
- **D.** `null`

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Le loader ne copie le défaut de l'argument que si
l'option `defaults` n'en déclare pas déjà un pour ce parameter.

</details>

**Question 3.** Même route `/blog/{page}/{sort}` qu'à la question 1. Que
produit sa génération sans aucun parameter ?

- **A.** `/blog/1/date`
- **B.** `/blog/1`
- **C.** Une exception : parameters manquants
- **D.** `/blog`

<details>
<summary>Correction</summary>

**Bonne réponse : D.** `sort` est en fin de path avec sa default value, donc
omis ; `page` devient alors final à son tour, et part aussi.

</details>

**Question 4.** L'action `list(Status $status = Status::Published)` sert la
route `/posts/{status}`, et `Status` est une enum adossée à des strings. Quelle
default value le loader copie-t-il dans la route ?

- **A.** `'published'`, la value du cas
- **B.** `Status::Published`, l'object
- **C.** Rien : une enum n'est pas scalaire
- **D.** `'Published'`, le nom du cas

<details>
<summary>Correction</summary>

**Bonne réponse : A.** Pour un cas d'enum adossée, le loader reprend sa
`value`, ce qui rend `/posts` valide avec `status` à `published`.

</details>

## Fiche express

**Si tu dois retenir seulement 6 choses :**

- Sans default value, un parameter est **obligatoire**.
- Attributes : défaut = **argument du controller**, si le path a le parameter.
- `defaults` **l'emporte** sur l'argument ; enum adossée → sa **value**.
- Après un facultatif, **tout** doit être facultatif.
- Défaut en **fin** de path → omis ; `{!page}` → écrit **et** obligatoire.
- La default value peut **violer** le requirement.

Moyen mnémotechnique : **« Le point d'exclamation crie : je suis obligatoire »**.

## Sources officielles

- [Routing, section « Optional Parameters »](https://github.com/symfony/symfony-docs/blob/8.0/routing.rst)
- [`RouteCompiler`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/RouteCompiler.php)
- [`AttributeClassLoader`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Loader/AttributeClassLoader.php)

