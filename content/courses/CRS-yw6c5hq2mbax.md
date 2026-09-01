---
id: CRS-yw6c5hq2mbax
official_item: OIT-c34hn4px3czj
title: "Controller rendering"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/templates.rst"
    anchor: "embedding-controllers"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
---

## Objectif

Exécuter un contrôleur depuis un gabarit, et savoir ce que cela coûte. Le
`forward()` d'un contrôleur vers un autre appartient au lot Controllers.

## Le besoin

Un fragment a besoin de données que la page courante n'a pas — les derniers
articles dans une barre latérale présente sur tout le site. Passer ces données
depuis chaque contrôleur serait absurde. La fonction `render()` exécute un
contrôleur et insère sa réponse :

```html
{{ render(path('latest_articles', {max: 3})) }}
{{ render(url('latest_articles', {max: 3})) }}
```

## Avec ou sans route

`render()` prend une URL. Pour appeler un contrôleur qui n'a **pas** de route,
on l'enveloppe dans `controller()` :

```html
{{ render(controller(
    'App\\Controller\\BlogController::recentArticles',
    {max: 3}
)) }}
```

C'est la différence à retenir : `path()` et `url()` exigent une route,
`controller()` non.

## Ce que cela déclenche

Chaque appel est une **sous-requête** : le noyau retraverse son cycle pour le
fragment. Ce n'est pas gratuit, et c'est la raison de ne pas en semer partout.

Les contrôleurs appelés par `controller()` ne passent pas par une route
ordinaire mais par une URL interne réservée aux fragments, configurée par
`framework.fragments.path`, dont la valeur usuelle est `/_fragment`.

## Points clés

- `render(path(...))` ou `render(url(...))` exécute un contrôleur routé.
- `render(controller(...))` en exécute un **sans route**.
- Chaque appel est une sous-requête, avec son coût.
- Les fragments transitent par `framework.fragments.path`, par défaut
  `/_fragment`.

## Sources officielles

- [Symfony Templates, « Embedding Controllers »](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/templates.rst)
