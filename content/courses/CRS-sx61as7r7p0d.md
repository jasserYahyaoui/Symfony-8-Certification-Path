---
id: CRS-sx61as7r7p0d
official_item: OIT-81b2c0jmv2j3
title: "URLs generation"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
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
---

## Objectif

Générer une URL depuis une route, et maîtriser ce qui arrive aux paramètres qui
ne font pas partie de la route.

## Où et comment

| Contexte | Appel |
|---|---|
| contrôleur (`AbstractController`) | `$this->generateUrl('nom', [...])` |
| service | injecter `UrlGeneratorInterface`, appeler `generate()` |
| gabarit Twig | `path('nom', {...})` et `url('nom', {...})` |

Le principe est le même partout : on ne construit **jamais** une URL à la main.
Changer le chemin d'une route met alors à jour tous les liens.

## Les quatre types de référence

Le troisième argument de `generate()` — et de `generateUrl()` — choisit la forme
de l'URL. Les constantes sont sur `UrlGeneratorInterface` :

| Constante | Valeur | Résultat |
|---|---|---|
| `ABSOLUTE_URL` | `0` | `https://example.com/blog/2` |
| `ABSOLUTE_PATH` | `1` | `/blog/2` — **la valeur par défaut** |
| `RELATIVE_PATH` | `2` | `../blog/2`, relatif au chemin courant |
| `NETWORK_PATH` | `3` | `//example.com/blog/2`, qui reprend le schéma courant |

La constante qui vaut `0` n'est **pas** le défaut : c'est l'URL absolue.

En Twig, `path()` correspond au chemin absolu et `url()` à l'URL absolue.

## Les paramètres en trop

Un paramètre passé au générateur mais **absent de la définition de la route**
n'est pas ignoré : il est ajouté en **chaîne de requête**.

```php
$this->generateUrl('blog', ['page' => 2, 'category' => 'Symfony']);
// la route blog ne définit que page → /blog/2?category=Symfony
```

Deux nuances du code :

- un paramètre en trop **égal à la valeur par défaut** de même nom est
  **retiré** : il n'apparaît pas dans la chaîne de requête ;
- la clé réservée `_query` fournit un tableau de paramètres de requête, qui
  **l'emportent** sur les paramètres en trop de même nom. Une valeur qui n'est pas
  un tableau lève une `InvalidParameterException`.

### Les objets en paramètre supplémentaire

La documentation avertit : un objet — un Uuid par exemple — ne serait **pas**
converti quand il sert de paramètre supplémentaire, et il faudrait écrire
`(string) $entity->getUuid()`. Le code de la branche 8.0 fait autrement. Pour
chaque objet des paramètres en trop, `doGenerate()` :

1. prend ses **propriétés publiques**, s'il en a, et en fait un tableau ;
2. sinon, s'il est `Stringable`, le **convertit en chaîne** ;
3. lève une `InvalidParameterException` sur une référence circulaire.

`AbstractUid` n'a qu'une propriété `protected` et implémente `Stringable` : un
Uuid est donc bien converti. La conversion explicite reste sans danger, mais
elle n'est plus nécessaire — et un objet à propriétés publiques devient un
tableau, pas sa représentation textuelle.

## Route absente

Ne pas appeler `getRouteCollection()` pour vérifier qu'une route existe : cela
régénère le cache de routage et ralentit l'application. Il faut tenter la
génération et rattraper `RouteNotFoundException`.

## Pièges d'examen

**Un paramètre absent de la route n'est pas ignoré : il part en chaîne de
requête.** Une faute de frappe dans un nom de paramètre ne produit donc aucune
erreur — juste une URL avec un paramètre en trop.

**Un objet en paramètre supplémentaire est converti, selon le code 8.0.** Les
propriétés publiques d'abord, en tableau ; `__toString()` seulement s'il n'en a
pas. La documentation dit encore le contraire.

**Un paramètre en trop égal au défaut disparaît de la chaîne de requête.**

**Le type de référence par défaut est le chemin absolu, pas l'URL absolue.**
Générer un lien pour un courriel demande de le dire explicitement.

## Points clés

- `generateUrl()`, `generate()`, `path()`, `url()` — jamais d'URL écrite à la
  main.
- Quatre types de référence ; `ABSOLUTE_PATH` par défaut.
- Un paramètre hors route devient une chaîne de requête.
- Objet en trop : propriétés publiques en tableau, sinon `Stringable` en chaîne.
- `_query` : un tableau, prioritaire sur les paramètres en trop.
- Tester l'existence d'une route par `RouteNotFoundException`.

## Sources officielles

- [UrlGeneratorInterface, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Generator/UrlGeneratorInterface.php)
- [Routing, section « Generating URLs »](https://github.com/symfony/symfony-docs/blob/8.0/routing.rst)
- [`UrlGenerator::doGenerate()`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Generator/UrlGenerator.php)
