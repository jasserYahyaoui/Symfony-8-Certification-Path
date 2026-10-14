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
---

## Objectif

Séparer le **composant** Routing de ce que **FrameworkBundle** en fait dans une
application, et savoir quel morceau vient d'où. La déclaration d'une route est
traitée par l'item *Configuration (YAML and PHP attributes)*.

## Le composant

Sa description officielle tient en une phrase : *« Maps an HTTP request to a set
of configuration variables »*. Une route ne désigne pas un controller au sens du
composant ; elle produit un **jeu de variables**, dont `_controller` n'est
qu'une parmi d'autres.

Sa liste de dependencies tient en deux entrées : PHP 8.4 et un package de
contrats. **Aucun autre composant Symfony.** C'est ce qui
permet de l'installer seul dans un projet qui n'utilise pas le framework.

Ses classes centrales :

| Class | Role |
|---|---|
| `Route` | une route : path, defaults, requirements, host, methods… |
| `RouteCollection` | l'ensemble ordonné des routes |
| `UrlMatcher` | de l'URL vers les variables — le **matching** |
| `UrlGenerator` | des variables vers l'URL — la **génération** |
| `RequestContext` | hôte, schéma, method, port courants |
| `Router` | assemble un loader, un matcher et un générateur |

Les deux sens — apparier et générer — sont deux classes distinctes. C'est ce qui
explique qu'une option puisse compter pour l'un et pas pour l'autre.

## Trois morceaux, trois origines

Le découpage « composant / bundle » ne suffit pas : un troisième acteur tient
le listener.

| Morceau | Class fournie par | Ce que fait FrameworkBundle |
|---|---|---|
| `Router`, matcher, générateur | **Routing** | déclare `router.default` avec le matcher `RedirectableCompiledUrlMatcher` et le générateur `CompiledUrlGenerator`, compilés dans le cache |
| `RouterListener` | **HttpKernel** | l'enregistre comme service `router_listener` |
| loader `routing.controllers` | **Routing** (`AttributeServicesLoader`) | tag `routing.controller` toute class portant `#[Route]` |

Le `RouterListener` n'est donc **pas** une class du bundle. Son namespace
est `Symfony\Component\HttpKernel\EventListener` ; le bundle ne fait que le
câbler.

### Ce que fait le listener

Il écoute `kernel.request` à la priorité **32**, et :

- **saute** le routage si la Request porte déjà `_controller` — c'est ce qui
  évite de router à nouveau une sub-Request de `forward()` ;
- dépose les variables trouvées dans `request->attributes`, et recopie dans
  `_route_params` toutes celles qui ne sont ni `_route` ni `_controller` ;
- traduit « aucune route » en **404** et « mauvaise method » en **405**, avec
  les methods admises dans le header `Allow`.

### D'où viennent les routes d'attributes

La ressource `routing.controllers` ne parcourt pas un directory. Elle charge
les classes **étiquetées** : toute class de service qui porte `#[Route]` reçoit,
par autoconfiguration, les tags `routing.controller` et
`controller.service_arguments`. C'est l'enregistrement comme service qui compte,
pas l'emplacement dans `src/Controller/`.

### Le matcher du bundle redirige

`RedirectableCompiledUrlMatcher` sait répondre par un redirect plutôt que
par un 404 : sa method `redirect()` renvoie vers
`RedirectController::urlRedirectAction`, en **permanent**.

## Ce que FrameworkBundle ajoute encore

- le chargement de `config/routes/` et de `config/routes.yaml` par
  `MicroKernelTrait`, qui importe aussi les attributes du kernel lui-même ;
- les commands `debug:router` et `router:match` ;
- les controllers `RedirectController` et `TemplateController`.

## Pièges d'examen

**`RouterListener` appartient à HttpKernel, pas à FrameworkBundle.** Le bundle
l'enregistre ; il ne le définit pas.

**Apparier et générer sont deux classes distinctes.** Le générateur ne voit pas
la Request ; il ne peut pas évaluer ce qui en dépend.

**Le composant ne dépend d'aucun autre composant Symfony.**

**Une route produit des variables, pas un controller.**

**Une Request qui porte déjà `_controller` n'est pas routée.**

**Les routes d'attributes viennent des services étiquetés**, pas d'un parcours
de directory.

## Points clés

- Le composant fait correspondre une Request à des **variables**.
- Il est autonome : PHP et un package de contrats, rien d'autre.
- `UrlMatcher` et `UrlGenerator` sont deux classes séparées.
- `RouterListener` : HttpKernel, `kernel.request` à 32 ; 404 et 405.
- FrameworkBundle câble le router compilé, le listener et le loader.

## Sources officielles

- [composer.json du composant Routing](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/composer.json)
- [UrlGeneratorInterface, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Generator/UrlGeneratorInterface.php)
- [`RouterListener`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/EventListener/RouterListener.php)
- [Services de routage de FrameworkBundle](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Resources/config/routing.php)
- [Autoconfiguration de `#[Route]`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/FrameworkExtension.php)
