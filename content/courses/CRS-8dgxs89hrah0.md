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
    anchor: "debugging-routes"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
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
```

Passer un nom, même partiel, affiche le détail d'une route : chemin, hôte,
schéma, méthodes, defaults, requirements, options.

## `router:match` — quelle route répondrait

Elle prend une URL et dit laquelle des routes correspondrait.

```bash
php bin/console router:match /lucky/number/8
```

C'est la commande à utiliser quand une URL n'exécute pas le contrôleur attendu :
elle répond directement à la question, là où `debug:router` demande de la
déduire d'une liste.

## Points clés

- `debug:router` liste les routes **dans l'ordre d'évaluation**.
- `--show-aliases`, `--show-controllers` et `--method` affinent la sortie.
- Un nom en argument affiche le détail d'une seule route.
- `router:match <url>` dit quelle route correspondrait à une URL donnée.

## Sources officielles

- [Routing, section « Debugging Routes »](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/routing.rst)
