---
id: CRS-2nwv1v57j7ef
official_item: OIT-ceaw3ewfsw85
title: "Routing component and FrameworkBundle"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Routing/composer.json"
    symbol_or_lines: "description and require keys"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Routing/Generator/UrlGeneratorInterface.php"
    symbol_or_lines: "reference type constants and generate()"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    verified_at: "2026-09-01"
---

## Objectif

Séparer le **composant** Routing de ce que **FrameworkBundle** en fait dans une
application. La déclaration d'une route est traitée par l'item *Configuration
(YAML and PHP attributes)*.

## Le composant

Sa description officielle tient en une phrase : *« Maps an HTTP request to a set
of configuration variables »*. Une route ne désigne pas un contrôleur au sens du
composant ; elle produit un **jeu de variables**, dont `_controller` n'est
qu'une parmi d'autres.

Sa liste de dépendances tient en deux entrées : PHP 8.4 et un paquet de
contrats minuscule. **Aucun autre composant Symfony.** C'est ce qui permet de
l'installer seul dans un projet qui n'utilise pas le framework.

Ses classes centrales :

| Classe | Rôle |
|---|---|
| `Route` | une route : chemin, defaults, requirements, host, methods… |
| `RouteCollection` | l'ensemble ordonné des routes |
| `UrlMatcher` | de l'URL vers les variables — le **matching** |
| `UrlGenerator` | des variables vers l'URL — la **génération** |
| `RequestContext` | hôte, schéma, méthode, port courants |
| `Router` | assemble un chargeur, un matcher et un générateur |

Les deux sens — apparier et générer — sont deux classes distinctes. C'est ce qui
explique qu'une option puisse compter pour l'un et pas pour l'autre.

## Ce que FrameworkBundle ajoute

- le `RouterListener`, écouteur de `kernel.request` qui exécute le matching et
  dépose le résultat dans `request->attributes` ;
- le chargement de `config/routes.yaml` et `config/routes/`, et le chargeur
  d'attributs qui lit `#[Route]` dans `src/Controller/` ;
- la **compilation** : matcher et générateur sont générés en PHP dans
  `var/cache/`, si bien qu'aucune expression régulière n'est reconstruite à
  chaque requête ;
- les commandes `debug:router` et `router:match` ;
- les contrôleurs `RedirectController` et `TemplateController`, référencables
  depuis une route.

## Points clés

- Le composant fait correspondre une requête à des **variables**, pas à un
  contrôleur.
- Il est autonome : PHP et un paquet de contrats, rien d'autre.
- `UrlMatcher` et `UrlGenerator` sont deux classes séparées, un sens chacune.
- FrameworkBundle apporte le `RouterListener`, le chargement, la compilation en
  cache et les commandes de débogage.

## Sources officielles

- [composer.json du composant Routing](https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Routing/composer.json)
- [UrlGeneratorInterface, branche 8.0](https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Routing/Generator/UrlGeneratorInterface.php)
