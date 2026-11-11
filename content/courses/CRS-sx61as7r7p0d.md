---
id: CRS-sx61as7r7p0d
official_item: OIT-81b2c0jmv2j3
title: "URLs generation"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Routing/Generator/UrlGeneratorInterface.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Generator/UrlGeneratorInterface.php"
    symbol_or_lines: "ABSOLUTE_URL, ABSOLUTE_PATH, RELATIVE_PATH, NETWORK_PATH, generate()"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/routing.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/routing.rst"
    anchor: "generating-urls"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Routing/Generator/UrlGenerator.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Generator/UrlGenerator.php"
    symbol_or_lines: "doGenerate"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Uid/AbstractUid.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Uid/AbstractUid.php"
    symbol_or_lines: "AbstractUid"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
outcome_sections:
  OUT-t5wbk2v6vn3y: "Où et comment"
  OUT-5t7q7sa1385e: "Les quatre types de référence"
  OUT-dqnskk31jve5: "Les parameters en trop"
  OUT-hz0pwe66gz3a: "Route absente"
---

## Objectif

Générer une URL depuis une route, et maîtriser ce qui arrive aux parameters qui
ne font pas partie de la route.

## L'essentiel

Une URL se génère toujours depuis une route : `generateUrl()` dans un
controller, `generate()` d'`UrlGeneratorInterface` dans un service, `path()` et
`url()` en Twig. Le troisième argument choisit la forme — **path absolu par
défaut**. Un parameter absent de la route part en **query string**, sans
erreur ; un object y est converti, contrairement à ce que dit la documentation.

## Le schéma

```mermaid
---
title: Générer une URL
---
flowchart LR
  accTitle: Les trois façons de générer une URL et ce que fait generate
  accDescr: Dans un controller, $this->generateUrl('blog', ['page' => 2]) ; dans un service, UrlGeneratorInterface::generate() ; en Twig, path() pour ABSOLUTE_PATH et url() pour ABSOLUTE_URL. Les types : ABSOLUTE_URL, 0, https://example.com/blog/2 ; ABSOLUTE_PATH, 1, /blog/2, le défaut ; RELATIVE_PATH, 2, ../blog/2 ; NETWORK_PATH, 3, //example.com/blog/2. Un parameter en trop va dans la query string ; un parameter égal au défaut de même nom est retiré ; _query, un array, est prioritaire. Un object devient un array de ses properties publiques, sinon une string s'il est Stringable ; une référence circulaire lève. Une route absente lève RouteNotFoundException ; il ne faut pas appeler getRouteCollection().
  C["controller<br/>$this->generateUrl('blog',<br/>['page' => 2])"] --> G(["generate()"])
  S["service<br/>UrlGeneratorInterface<br/>::generate()"] --> G
  T["Twig<br/>path() = ABSOLUTE_PATH<br/>url() = ABSOLUTE_URL"] --> G
  G --> TY["types<br/>ABSOLUTE_URL 0<br/>https://example.com/blog/2<br/>ABSOLUTE_PATH 1 /blog/2 (défaut)<br/>RELATIVE_PATH 2 ../blog/2<br/>NETWORK_PATH 3<br/>//example.com/blog/2"]
  G --> X["en trop → query string<br/>égal au défaut de même nom<br/>→ retiré<br/>_query (array) prioritaire"]
  G --> O["object<br/>properties publiques → array<br/>sinon Stringable → string<br/>référence circulaire → exception"]
  G --> NF["route absente<br/>→ RouteNotFoundException<br/>(ne pas appeler<br/>getRouteCollection())"]
```

## L'exemple

```php
$this->generateUrl('blog', ['page' => 2, 'category' => 'Symfony']);
// la route blog ne définit que page → /blog/2?category=Symfony

$this->generateUrl('blog', ['page' => 2], UrlGeneratorInterface::ABSOLUTE_URL);
// lien pour un email → https://example.com/blog/2
```

## Où et comment

| Contexte | Appel |
|---|---|
| controller (`AbstractController`) | `$this->generateUrl('nom', [...])` |
| service | injecter `UrlGeneratorInterface`, appeler `generate()` |
| template Twig | `path('nom', {...})` et `url('nom', {...})` |

On ne construit **jamais** une URL à la main : changer le path d'une route met
alors à jour tous les liens.

## Les quatre types de référence

Les constants sont sur `UrlGeneratorInterface` : `ABSOLUTE_URL` vaut `0`,
`ABSOLUTE_PATH` vaut `1` et c'est **le défaut**, `RELATIVE_PATH` vaut `2`,
`NETWORK_PATH` vaut `3`. La constant qui vaut `0` n'est donc pas le défaut.
`NETWORK_PATH` reprend le schéma courant ; `RELATIVE_PATH` est relatif au path
courant.

## Les parameters en trop

Un parameter absent de la definition de la route part en **query string**.
Deux nuances : un parameter en trop **égal à la default value** de même nom
est retiré ; la key réservée `_query` fournit un array de parameters qui
**l'emportent** sur les parameters en trop de même nom — une valeur qui n'est
pas un array lève une `InvalidParameterException`.

### Les objects en parameter supplémentaire

La documentation dit qu'un object — un Uuid — n'est pas converti, et conseille
`(string) $entity->getUuid()`. Le code 8.0 le convertit : `doGenerate()` prend
ses **properties publiques** en array s'il en a, sinon le convertit en chaîne
s'il est `Stringable`, et lève sur une référence circulaire. `AbstractUid` n'a
qu'une property `protected` et implémente `Stringable` : un Uuid est converti.

## Route absente

Ne pas appeler `getRouteCollection()` pour tester l'existence d'une route : cela
régénère le cache de routage. Tenter la génération et rattraper
`RouteNotFoundException`.

## Pièges d'examen

**Un parameter absent de la route n'est pas ignoré : il part en query
string.** Une faute de frappe dans un nom de parameter ne produit donc aucune
erreur — juste une URL avec un parameter en trop.

**Un object en parameter supplémentaire est converti, selon le code 8.0.** Les
properties publiques d'abord, en array ; `__toString()` seulement s'il n'en a
pas. La documentation dit encore le contraire.

**Un parameter en trop égal au défaut disparaît de la query string.**

**Le type de référence par défaut est le path absolu, pas l'URL absolue.**
Générer un lien pour un email demande de le dire explicitement.

## Application

- Un lien dans un email → `ABSOLUTE_URL`, ou `url()` en Twig.
- Un lien affiché seulement si la route existe → `try` sur `generate()`.

## QCM d'entraînement

**Question 1.** Quelle valeur entière porte `UrlGeneratorInterface::ABSOLUTE_URL` ?

- **A.** `0`
- **B.** `1`, la default value
- **C.** `2`
- **D.** `3`

<details>
<summary>Correction</summary>

**Bonne réponse : A.** L'URL absolue vaut `0` ; le défaut des methods de
génération est le path absolu, qui vaut `1`.

</details>

**Question 2.** Le code appelle `generate('blog', ['_query' => 'page=2'])`. Que
se passe-t-il ?

- **A.** `/blog?page=2`
- **B.** Une `InvalidParameterException`
- **C.** `/blog?_query=page%3D2`
- **D.** `/blog`, la key réservée étant simplement ignorée

<details>
<summary>Correction</summary>

**Bonne réponse : B.** La key réservée attend un array de parameters ; une
string n'est pas acceptée, et le generator le signale par une exception.

</details>

**Question 3.** Un object sans property publique, dont `__toString()` rend
`abc`, est passé en parameter supplémentaire `ref`. Que produit la génération
de `/blog` ?

- **A.** `/blog?ref=abc`
- **B.** Une `InvalidParameterException`
- **C.** `/blog?ref=`, sans valeur
- **D.** `/blog` : l'object est ignoré

<details>
<summary>Correction</summary>

**Bonne réponse : A.** Sans property publique à reprendre en array, le
generator convertit en chaîne un object `Stringable`, selon le code 8.0.

</details>

**Question 4.** La Request courante est `/en/blog/1`. Que produit la génération
de la route `/en/about` en `RELATIVE_PATH` ?

- **A.** `/en/about`
- **B.** `about`
- **C.** `../about`
- **D.** `../../en/about`

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Le path relatif remonte d'un niveau depuis le
directory courant `/en/blog/`, puis descend vers `about`.

</details>

## Fiche express

**Si tu dois retenir seulement 6 choses :**

- Jamais d'URL à la main : `generateUrl()`, `generate()`, **`path()`**, **`url()`**.
- Défaut : **`ABSOLUTE_PATH` (1)** ; `ABSOLUTE_URL` vaut **0**.
- Parameter hors route → **query string**, sans erreur.
- Égal au défaut → **retiré** ; `_query` : un **array**, prioritaire.
- Object en trop : properties publiques → **array**, sinon **`Stringable`** → string.
- Route absente : rattraper **`RouteNotFoundException`**.

Moyen mnémotechnique : **« Zéro, c'est tout : l'URL complète »**.

## Sources officielles

- [UrlGeneratorInterface, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Generator/UrlGeneratorInterface.php)
- [Routing, section « Generating URLs »](https://github.com/symfony/symfony-docs/blob/8.0/routing.rst)
- [`UrlGenerator::doGenerate()`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Generator/UrlGenerator.php)

