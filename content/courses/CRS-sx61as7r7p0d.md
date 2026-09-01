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
    symbol_or_lines: "ABSOLUTE_URL, ABSOLUTE_PATH, RELATIVE_PATH, NETWORK_PATH, generate()"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/routing.rst"
    anchor: "generating-urls"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
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

| Constante | Résultat |
|---|---|
| `ABSOLUTE_PATH` | `/blog/2` — **la valeur par défaut** |
| `ABSOLUTE_URL` | `https://example.com/blog/2` |
| `RELATIVE_PATH` | `../blog/2` |
| `NETWORK_PATH` | `//example.com/blog/2` |

En Twig, `path()` correspond au chemin absolu et `url()` à l'URL absolue.

## Les paramètres en trop

Un paramètre passé au générateur mais **absent de la définition de la route**
n'est pas ignoré : il est ajouté en **chaîne de requête**.

```php
$this->generateUrl('blog', ['page' => 2, 'category' => 'Symfony']);
// la route blog ne définit que page → /blog/2?category=Symfony
```

Un objet utilisé comme *placeholder* est converti en chaîne ; utilisé comme
paramètre supplémentaire, il ne l'est **pas**. Il faut le convertir soi-même :
`['uuid' => (string) $entity->getUuid()]`.

## Route absente

Ne pas appeler `getRouteCollection()` pour vérifier qu'une route existe : cela
régénère le cache de routage et ralentit l'application. Il faut tenter la
génération et rattraper `RouteNotFoundException`.

## Points clés

- `generateUrl()`, `generate()`, `path()`, `url()` — jamais d'URL écrite à la
  main.
- Quatre types de référence ; `ABSOLUTE_PATH` par défaut.
- Un paramètre hors route devient une chaîne de requête.
- Un objet en paramètre supplémentaire n'est pas converti en chaîne.
- Tester l'existence d'une route par `RouteNotFoundException`.

## Sources officielles

- [UrlGeneratorInterface, branche 8.0](https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Routing/Generator/UrlGeneratorInterface.php)
- [Routing, section « Generating URLs »](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/routing.rst)
