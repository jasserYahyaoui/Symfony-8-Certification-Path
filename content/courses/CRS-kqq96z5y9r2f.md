---
id: CRS-kqq96z5y9r2f
official_item: OIT-egy2wn3z7gb7
title: "Configuration (YAML and PHP attributes)"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/routing.rst"
    anchor: "creating-routes"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Routing/Attribute/Route.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "Route::__construct"
    verified_at: "2026-09-01"
---

## Objectif

Déclarer une route en YAML et en attribut PHP, savoir choisir entre les deux,
et maîtriser la conséquence la plus piégeuse de ce choix : **l'ordre
d'évaluation**.

## Prérequis

- Attributs PHP 8 — `must-know` (topic PHP, item *Attributes*).

## Les deux formes

Les deux déclarations ci-dessous sont **strictement équivalentes**.

```php
// src/Controller/BlogController.php
use Symfony\Component\Routing\Attribute\Route;

class BlogController
{
    #[Route('/blog', name: 'blog_list')]
    public function list(): Response
    {
        // ...
    }
}
```

```yaml
# config/routes.yaml
blog_list:
    path: /blog
    controller: App\Controller\BlogController::list
```

Le nom de la route (`blog_list`) est la clé YAML, ou l'argument `name:` de
l'attribut. Le contrôleur est explicite en YAML ; en attribut il est déduit de
la méthode portant l'attribut.

## Le namespace de l'attribut

```php
use Symfony\Component\Routing\Attribute\Route;   // correct
```

`Symfony\Component\Routing\Annotation\Route` **n'existe plus** : ce namespace,
hérité de l'époque des annotations Doctrine, a été supprimé. Un import
`Annotation\Route` échoue en Symfony 8.0.

## Options communes

L'attribut `#[Route]` et la clé YAML acceptent le même jeu d'options :
`path`, `name`, `requirements`, `defaults`, `options`, `host`, `methods`,
`schemes`, `condition`, `priority`, `locale`, `format`, `utf8`, `stateless`,
`env`, `alias`.

```php
#[Route(
    '/blog/{page}',
    name: 'blog_list',
    requirements: ['page' => '[0-9]+'],
    defaults: ['page' => 1],
    methods: ['GET', 'HEAD'],
)]
```

```yaml
blog_list:
    path: /blog/{page}
    controller: App\Controller\BlogController::list
    requirements:
        page: '[0-9]+'
    defaults:
        page: 1
    methods: [GET, HEAD]
```

## Le piège : l'ordre d'évaluation

**Symfony évalue les routes dans l'ordre où elles sont définies, et la première
qui correspond gagne.** Les suivantes ne sont jamais essayées.

En YAML, cet ordre est celui du fichier : il suffit de déplacer une route plus
haut.

```yaml
# /blog/list doit précéder /blog/{slug}, sinon slug="list" capture tout
blog_list:
    path: /blog/list
    controller: App\Controller\BlogController::list

blog_show:
    path: /blog/{slug}
    controller: App\Controller\BlogController::show
```

En attributs, l'ordre dépend de l'ordre de découverte des classes et des
méthodes — que vous ne contrôlez pas de façon fiable. C'est précisément la
raison d'être de l'option `priority` :

```php
#[Route('/blog/list', name: 'blog_list', priority: 2)]
public function list(): Response { /* ... */ }

#[Route('/blog/{slug}', name: 'blog_show')]   // priority 0 par défaut
public function show(string $slug): Response { /* ... */ }
```

Une `priority` plus élevée est évaluée en premier. La valeur par défaut est `0`.

## Erreurs fréquentes

- Croire que la route la **plus spécifique** l'emporte. Non : c'est la
  **première déclarée** qui correspond.
- Utiliser `priority` en YAML pour réordonner alors qu'il suffit de déplacer la
  route dans le fichier.
- Importer `Annotation\Route` au lieu de `Attribute\Route`.

## Points clés

- Les deux formats sont équivalents en capacité ; ils diffèrent par la
  localisation de la configuration et par la maîtrise de l'ordre.
- Première correspondance gagnante, jamais la plus spécifique.
- `priority` existe pour les attributs, dont l'ordre n'est pas contrôlable.
- L'attribut vit dans `Routing\Attribute`, plus dans `Routing\Annotation`.

## Sources officielles

- `routing.rst` (symfony-docs, branche 8.0, `eea05cb`)
- `Symfony\Component\Routing\Attribute\Route` (symfony, branche 8.0, `6f841c0`)
