---
id: CRS-2nwv1v57j7ef
official_item: OIT-ceaw3ewfsw85
title: "Routing component and FrameworkBundle"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Routing/composer.json"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/composer.json"
    symbol_or_lines: "description and require keys"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Routing/Generator/UrlGeneratorInterface.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Generator/UrlGeneratorInterface.php"
    symbol_or_lines: "reference type constants and generate()"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpKernel/EventListener/RouterListener.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/EventListener/RouterListener.php"
    symbol_or_lines: "onKernelRequest, getSubscribedEvents"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/Resources/config/routing.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Resources/config/routing.php"
    symbol_or_lines: "router.default, router_listener, routing.loader.attribute.services"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/FrameworkExtension.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/FrameworkExtension.php"
    symbol_or_lines: "registerAttributeForAutoconfiguration(Route::class"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
outcome_sections:
  OUT-jsv2v128z17j: "Le component"
  OUT-25y9qjcp8wgy: "Le component"
  OUT-qpav59c60vb3: "Le component"
  OUT-bn50g0may31c: "Ce que FrameworkBundle ajoute encore"
---

## Objectif

Séparer le **component** Routing de ce que **FrameworkBundle** en fait dans une
application, et savoir quel morceau vient d'où. La déclaration d'une route est
traitée par l'item *Configuration (YAML and PHP attributes)*.

## L'essentiel

Le **component** Routing fait correspondre une Request à des **variables** —
`_controller` n'en est qu'une — et ne dépend d'aucun autre component Symfony.
Apparier et générer sont deux classes distinctes. **FrameworkBundle** câble le
tout : router compilé, matcher qui sait rediriger, loader des attributes, et
`RouterListener` — une class de **HttpKernel**, pas du bundle.

## Le schéma

```text
component Routing   « Maps an HTTP request to a set of configuration variables » · requiert PHP + deprecation-contracts
  Route · RouteCollection · UrlMatcher (URL → variables) · UrlGenerator (variables → URL) · RequestContext · Router
HttpKernel          RouterListener : kernel.request, priority 32
                      _controller déjà présent → rien · variables → attributes + _route_params
                      aucune route → 404 · mauvaise method → 405 + Allow
FrameworkBundle     router.default : RedirectableCompiledUrlMatcher + CompiledUrlGenerator (cache)
                    service router_listener · routing.controllers (AttributeServicesLoader)
                    #[Route] sur un service → tags routing.controller + controller.service_arguments
```

## L'exemple : ce que l'appariement dépose

```php
// route post : /post/{id}, defaults _controller et page = 1
// GET /post/7 → attributes de la Request :
['_controller' => 'App\C::show', 'page' => 1, 'id' => '7',
 '_route' => 'post', '_route_params' => ['page' => 1, 'id' => '7']]
```

## Le component

Sa description officielle : *« Maps an HTTP request to a set of configuration
variables »*. Une route produit un **jeu de variables**. Ses dependencies : PHP
8.4 et un package de contrats, **aucun autre component Symfony** — il s'installe
seul.

| Class | Role |
|---|---|
| `Route`, `RouteCollection` | une route ; l'ensemble ordonné des routes |
| `UrlMatcher` | de l'URL vers les variables — le **matching** |
| `UrlGenerator` | des variables vers l'URL — la **génération** |
| `RequestContext` | host, schéma, method, port courants |
| `Router` | assemble un loader, un matcher et un generator |

Apparier et générer étant deux classes, une option peut compter pour l'un et
pas pour l'autre.

## Trois morceaux, trois origines

| Morceau | Class fournie par | Ce que fait FrameworkBundle |
|---|---|---|
| `Router`, matcher, generator | **Routing** | déclare `router.default` avec `RedirectableCompiledUrlMatcher` et `CompiledUrlGenerator`, compilés dans le cache |
| `RouterListener` | **HttpKernel** | l'enregistre comme service `router_listener` |
| loader `routing.controllers` | **Routing** (`AttributeServicesLoader`) | tague `routing.controller` toute class portant `#[Route]` |

`RouterListener`, de namespace `Symfony\Component\HttpKernel\EventListener`,
écoute `kernel.request` à la priority **32**. Il **saute** le routage si la
Request porte déjà `_controller` — une sub-Request de `forward()` n'est pas
routée à nouveau ; il dépose les variables dans les attributes et recopie dans
`_route_params` celles qui ne sont ni `_route` ni `_controller` ; il traduit
« aucune route » en **404** et « mauvaise method » en **405**, header `Allow`
compris.

Les routes d'attributes viennent des **services étiquetés** : par
autoconfiguration, une class qui porte `#[Route]` reçoit `routing.controller` et
`controller.service_arguments`. Le matcher du bundle répond par un redirect
plutôt qu'un 404, vers `RedirectController::urlRedirectAction`, en permanent.

## Ce que FrameworkBundle ajoute encore

- le chargement de `config/routes/` et `config/routes.yaml` par
  `MicroKernelTrait`, qui importe aussi les attributes du kernel ;
- les commands `debug:router` et `router:match` ;
- les controllers `RedirectController` et `TemplateController`.

## Pièges d'examen

**`RouterListener` appartient à HttpKernel, pas à FrameworkBundle.** Le bundle
l'enregistre ; il ne le définit pas.

**Apparier et générer sont deux classes distinctes.** Le generator ne voit pas
la Request ; il ne peut pas évaluer ce qui en dépend.

**Le component ne dépend d'aucun autre component Symfony.**

**Une route produit des variables, pas un controller.**

**Une Request qui porte déjà `_controller` n'est pas routée.**

**Les routes d'attributes viennent des services étiquetés**, pas d'un parcours
de directory.

## Application

- Un routage minimal hors framework → `symfony/routing` seul, `UrlMatcher` et
  `RequestContext`.
- Un controller rangé hors de `src/Controller/` → il suffit qu'il soit un
  service autoconfiguré.

## QCM d'entraînement

**Question 1.** À quelle priority `RouterListener` écoute-t-il `kernel.request` ?

- **A.** 32
- **B.** 16, comme `LocaleListener`
- **C.** 0
- **D.** 256

<details>
<summary>Correction</summary>

**Bonne réponse : A.** Il passe avant `LocaleListener`, à 16, qui lit
justement l'attribute `_locale` que le routage vient de déposer.

</details>

**Question 2.** La route `/post/{id}` n'accepte que `GET` et `PUT`. Un `POST`
arrive sur `/post/7`. Que produit `RouterListener` ?

- **A.** Un 404
- **B.** Un 405, header `Allow: GET, PUT`
- **C.** Un 400
- **D.** Un 302 qui renvoie le client vers la même URL en GET

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Le listener traduit l'exception de method refusée en
erreur HTTP 405, et liste les methods admises dans le header.

</details>

**Question 3.** La route `/post/{id}` a les defaults `_controller` et
`page = 1`. Après `GET /post/7`, que contient `_route_params` ?

- **A.** `id` seul
- **B.** `_controller`, `page` et `id`
- **C.** `page` et `id`
- **D.** `_route` et `id`

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Toutes les variables trouvées y sont recopiées, sauf
`_route` et `_controller`, qui restent seulement dans les attributes.

</details>

**Question 4.** Une Request arrive déjà munie de l'attribute `_controller`. Que
fait `RouterListener` ?

- **A.** Il route quand même et écrase `_controller`
- **B.** Une exception
- **C.** Il ajoute seulement `_route`
- **D.** Rien : le routage est sauté

<details>
<summary>Correction</summary>

**Bonne réponse : D.** Le listener considère le routage comme déjà fait ; c'est
ce qui évite de router à nouveau une sub-Request produite par `forward()`.

</details>

## Fiche express

**Si tu dois retenir seulement 6 choses :**

- Routing : Request → **variables** ; autonome, sans autre component Symfony.
- **`UrlMatcher`** apparie, **`UrlGenerator`** génère : deux classes.
- `RouterListener` : **HttpKernel**, `kernel.request` à **32**.
- Aucune route → **404** ; mauvaise method → **405** + `Allow`.
- `_controller` déjà présent → routage **sauté**.
- Bundle : matcher **redirigeant** compilé, `router_listener`, routes des **services étiquetés**.

Moyen mnémotechnique : **« Le component trouve, le kernel écoute, le bundle branche »**.

## Sources officielles

- [composer.json du component Routing](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/composer.json)
- [UrlGeneratorInterface, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Generator/UrlGeneratorInterface.php)
- [`RouterListener`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/EventListener/RouterListener.php)
- [Services de routage de FrameworkBundle](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Resources/config/routing.php)
- [Autoconfiguration de `#[Route]`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/FrameworkExtension.php)

