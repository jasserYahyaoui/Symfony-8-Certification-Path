---
id: CRS-yw6c5hq2mbax
official_item: OIT-c34hn4px3czj
title: "Controller rendering"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-24"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/templates.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/templates.rst"
    anchor: "embedding-controllers"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bridge/Twig/Extension/HttpKernelExtension.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/Extension/HttpKernelExtension.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "render, render_*, fragment_uri, controller"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bridge/Twig/Extension/HttpKernelRuntime.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/Extension/HttpKernelRuntime.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "HttpKernelRuntime::renderFragment()"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpKernel/Fragment/FragmentHandler.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/Fragment/FragmentHandler.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "FragmentHandler::render(), deliver()"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpKernel/Fragment/InlineFragmentRenderer.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/Fragment/InlineFragmentRenderer.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "InlineFragmentRenderer::render(), createSubRequest()"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/Configuration.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/Configuration.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "addFragmentsSection()"
    verified_at: "2026-09-24"
---

## Objectif

Exécuter un contrôleur depuis un gabarit, et savoir ce que cela coûte et ce qui
peut échouer. Le `forward()` d'un contrôleur vers un autre appartient au lot
Controllers.

## Le besoin

Un fragment a besoin de données que la page courante n'a pas — les derniers
articles dans une barre latérale présente sur tout le site. Passer ces données
depuis chaque contrôleur serait absurde. La fonction `render()` exécute un
contrôleur et insère sa réponse :

```html
{{ render(path('latest_articles', {max: 3})) }}
{{ render(url('latest_articles', {max: 3})) }}
```

La documentation Symfony 8.0 nuance d'emblée : pour une simple unité d'interface
réutilisable, elle recommande les Twig Components ; intégrer un contrôleur se
justifie quand il faut vraiment une sous-requête, par exemple pour mettre le
fragment en cache séparément avec ESI.

## Avec ou sans route

`render()` prend une URL ou une référence de contrôleur. Pour un contrôleur
**sans** route, on l'enveloppe dans `controller()` :

```html
{{ render(controller(
    'App\\Controller\\BlogController::recentArticles',
    {max: 3}
)) }}
```

`controller(nom, attributs, query)` construit un simple `ControllerReference` —
il n'exécute rien ; c'est `render()` qui le fait. Les attributs deviennent des
attributs de la sous-requête (des objets y sont admis en mode `inline`), le
troisième argument des paramètres de chaîne de requête.

## Ce que cela déclenche

Chaque appel est une **sous-requête** `GET` traitée par le noyau
(`HttpKernelInterface::SUB_REQUEST`) : le noyau retraverse son cycle pour le
fragment. `InlineFragmentRenderer` la construit avec les cookies, les variables
serveur et la session de la requête courante, sans les en-têtes
`If-Modified-Since` et `If-None-Match`, et transmet `_format` et la locale.

Ce n'est pas gratuit ; la documentation avertit qu'intégrer beaucoup de
contrôleurs peut peser nettement sur les performances.

## Les stratégies

`render()` utilise la stratégie `inline` par défaut ; l'option `strategy` ou les
fonctions `render_esi()`, `render_ssi()` et `render_hinclude()` en choisissent
une autre. `fragment_uri()` rend l'URI d'un fragment sans l'exécuter.

## L'URL des fragments

Avec `controller()`, la sous-requête vise une URL interne, `/_fragment` par
défaut (`framework.fragments.path`). L'option `framework.fragments` est
**désactivée par défaut** ; l'activer charge le `FragmentListener`, qui sert
cette URL aux requêtes venues de l'extérieur — ESI, hinclude — et y vérifie la
signature. En `inline`, la sous-requête porte déjà son `_controller`, et le
`RouterListener` ne route pas une requête qui en a un.

## Quand le fragment échoue

- **Exception** dans le contrôleur du fragment : relancée en `dev`, avalée en
  `prod`. `FragmentHandler` fixe `ignore_errors` à `!kernel.debug` si l'appel ne
  le précise pas ; ignorée, l'exception passe par l'événement `kernel.exception`
  — pour la journalisation — et le fragment est vide.
  L'option `alt` désigne un fragment de repli.
- **Réponse non 2xx** — une redirection comprise : `FragmentHandler::deliver()`
  lève une `RuntimeException`, quel que soit `ignore_errors`. Un contrôleur de
  fragment ne redirige pas.

## Pièges d'examen

**Rendre un contrôleur depuis un gabarit déclenche une sous-requête**, pas un
appel de fonction.

**`controller()` seul n'affiche rien** : il construit une référence.

**Une erreur de fragment ne se comporte pas pareil en `dev` et en `prod`.**

**Une redirection dans un fragment est une erreur.**

**`framework.fragments` n'est pas actif par défaut** ; il sert à exposer
`/_fragment` à l'extérieur, pas à faire fonctionner `inline`.

## Points clés

- `render(path(...))` / `render(url(...))` pour un contrôleur routé ;
  `render(controller(...))` sans route.
- Sous-requête `GET`, cookies et session partagés ; stratégie `inline` par
  défaut, `render_esi()` et consorts sinon.
- `ignore_errors` vaut `!kernel.debug` ; réponse non 2xx : exception.
- `/_fragment` par défaut ; `framework.fragments` désactivé tant qu'on ne
  l'active pas.

## Sources officielles

- [Symfony Templates, « Embedding Controllers »](https://github.com/symfony/symfony-docs/blob/8.0/templates.rst)
- [Twig Bridge 8.0, `HttpKernelExtension`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/Extension/HttpKernelExtension.php) et [`HttpKernelRuntime`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/Extension/HttpKernelRuntime.php)
- [HttpKernel 8.0, `FragmentHandler`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/Fragment/FragmentHandler.php) et [`InlineFragmentRenderer`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/Fragment/InlineFragmentRenderer.php)
- [FrameworkBundle 8.0, `Configuration`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/Configuration.php)
