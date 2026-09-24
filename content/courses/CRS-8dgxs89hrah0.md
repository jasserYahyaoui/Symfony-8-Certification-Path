---
id: CRS-8dgxs89hrah0
official_item: OIT-8sr74a2wnb3r
title: "Router debugging"
content_level: MINIMAL
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/routing.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/routing.rst"
    anchor: "debugging-routes"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/Command/RouterDebugCommand.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Command/RouterDebugCommand.php"
    symbol_or_lines: "configure, execute, findRouteNameContaining"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/Command/RouterMatchCommand.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Command/RouterMatchCommand.php"
    symbol_or_lines: "configure, execute"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Routing/Matcher/TraceableUrlMatcher.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Matcher/TraceableUrlMatcher.php"
    symbol_or_lines: "ROUTE_ALMOST_MATCHES"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
---

## Objectif

Diagnostiquer un problème de routage avec les deux commandes prévues pour cela.

## `debug:router` — quelles routes existent

Elle liste toutes les routes **dans l'ordre où Symfony les évalue**. C'est ce
détail qui en fait un outil de diagnostic et non un simple inventaire : comme la
première correspondance gagne, l'ordre est la réponse à « pourquoi est-ce
l'autre route qui répond ? ».

```bash
php bin/console debug:router
php bin/console debug:router --show-aliases
php bin/console debug:router --show-controllers
php bin/console debug:router --method=GET
php bin/console debug:router --format=json
```

`--method` filtre la liste, mais garde les routes sans contrainte de méthode,
puisqu'elles acceptent tout verbe. `--format` vaut `txt` par défaut ; `xml`, `json` et `md` sont les autres.

Passer un nom affiche le détail d'une route : chemin, hôte, schéma, méthodes,
defaults, requirements, options. Le code de `RouterDebugCommand` (8.0) précise
comment ce nom est lu :

- un nom exact est affiché directement ;
- sinon, la commande cherche les routes dont le nom **contient** l'argument,
  sans tenir compte de la casse ;
- plusieurs résultats : elle demande de choisir en mode interactif, et les liste
  en mode non interactif ;
- aucun résultat : `The route "…" does not exist.`

## `router:match` — quelle route répondrait

Elle prend un **chemin** — le *path info* — et dit laquelle des routes
correspondrait :

```bash
php bin/console router:match /lucky/number/8
php bin/console router:match /api/posts/1 --method=PUT --host=api.example.com --scheme=https
```

L'argument n'est pas une URL complète : l'hôte, le schéma et la méthode se
passent par les options `--host`, `--scheme` et `--method`, qui modifient le
contexte du routeur avant l'appariement.

Ce que la commande affiche, d'après `RouterMatchCommand` :

- chaque route qui correspond **presque**, avec la raison : contrainte de
  paramètre, hôte, condition, schéma ou méthode (`TraceableUrlMatcher`) ;
- en mode verbeux (`-v`), aussi les routes qui ne correspondent pas ;
- en cas de succès, le détail de la route, en relançant `debug:router` sur son
  nom ;
- si aucune route ne correspond, une erreur et le code de sortie **1**.

C'est la commande à utiliser quand une URL n'exécute pas le contrôleur attendu :
elle répond directement à la question, là où `debug:router` demande de la
déduire d'une liste.

## Pièges d'examen

**La liste des routes est donnée dans l'ordre d'évaluation, pas alphabétique.**
La première correspondance gagne, donc l'ordre affiché *est* la réponse à
« pourquoi est-ce l'autre route qui répond ? ».

**Deux commandes, deux questions différentes.** L'une inventorie les routes,
l'autre prend un chemin et dit laquelle répondrait.

**`router:match` prend un chemin, pas une URL.** Hôte, schéma et méthode passent
par des options ; sans elles, c'est le contexte par défaut du routeur qui
s'applique.

**Un nom partiel suffit à `debug:router`**, et la recherche ignore la casse.

## Points clés

- `debug:router` liste les routes **dans l'ordre d'évaluation**.
- `--show-aliases`, `--show-controllers`, `--method` et `--format` affinent la
  sortie.
- Un nom exact ou partiel en argument affiche le détail d'une seule route.
- `router:match <chemin>` dit quelle route correspondrait ; `--method`,
  `--host`, `--scheme` fixent le contexte ; code de sortie 1 sans
  correspondance.

## Sources officielles

- [Routing, section « Debugging Routes »](https://github.com/symfony/symfony-docs/blob/8.0/routing.rst)
- [`RouterDebugCommand`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Command/RouterDebugCommand.php)
- [`RouterMatchCommand`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Command/RouterMatchCommand.php)
- [`TraceableUrlMatcher`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Matcher/TraceableUrlMatcher.php)
