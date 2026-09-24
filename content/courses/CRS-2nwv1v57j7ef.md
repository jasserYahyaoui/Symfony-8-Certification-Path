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
of configuration variables »*. Une route ne désigne pas un contrôleur au sens du
composant ; elle produit un **jeu de variables**, dont `_controller` n'est
qu'une parmi d'autres.

Sa liste de dépendances tient en deux entrées : PHP 8.4 et un paquet de
contrats. **Aucun autre composant Symfony.** C'est ce qui
permet de l'installer seul dans un projet qui n'utilise pas le framework.

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

## Trois morceaux, trois origines

Le découpage « composant / bundle » ne suffit pas : un troisième acteur tient
l'écouteur.

| Morceau | Classe fournie par | Ce que fait FrameworkBundle |
|---|---|---|
| `Router`, matcher, générateur | **Routing** | déclare `router.default` avec le matcher `RedirectableCompiledUrlMatcher` et le générateur `CompiledUrlGenerator`, compilés dans le cache |
| `RouterListener` | **HttpKernel** | l'enregistre comme service `router_listener` |
| chargeur `routing.controllers` | **Routing** (`AttributeServicesLoader`) | étiquette `routing.controller` toute classe portant `#[Route]` |

Le `RouterListener` n'est donc **pas** une classe du bundle. Son espace de noms
est `Symfony\Component\HttpKernel\EventListener` ; le bundle ne fait que le
câbler.

### Ce que fait l'écouteur

Il écoute `kernel.request` à la priorité **32**, et :

- **saute** le routage si la requête porte déjà `_controller` — c'est ce qui
  évite de router à nouveau une sous-requête de `forward()` ;
- dépose les variables trouvées dans `request->attributes`, et recopie dans
  `_route_params` toutes celles qui ne sont ni `_route` ni `_controller` ;
- traduit « aucune route » en **404** et « mauvaise méthode » en **405**, avec
  les méthodes admises dans l'en-tête `Allow`.

### D'où viennent les routes d'attributs

La ressource `routing.controllers` ne parcourt pas un répertoire. Elle charge
les classes **étiquetées** : toute classe de service qui porte `#[Route]` reçoit,
par autoconfiguration, les étiquettes `routing.controller` et
`controller.service_arguments`. C'est l'enregistrement comme service qui compte,
pas l'emplacement dans `src/Controller/`.

### Le matcher du bundle redirige

`RedirectableCompiledUrlMatcher` sait répondre par une redirection plutôt que
par un 404 : sa méthode `redirect()` renvoie vers
`RedirectController::urlRedirectAction`, en **permanent**.

## Ce que FrameworkBundle ajoute encore

- le chargement de `config/routes/` et de `config/routes.yaml` par
  `MicroKernelTrait`, qui importe aussi les attributs du noyau lui-même ;
- les commandes `debug:router` et `router:match` ;
- les contrôleurs `RedirectController` et `TemplateController`.

## Pièges d'examen

**`RouterListener` appartient à HttpKernel, pas à FrameworkBundle.** Le bundle
l'enregistre ; il ne le définit pas.

**Apparier et générer sont deux classes distinctes.** Le générateur ne voit pas
la requête ; il ne peut pas évaluer ce qui en dépend.

**Le composant ne dépend d'aucun autre composant Symfony.**

**Une route produit des variables, pas un contrôleur.**

**Une requête qui porte déjà `_controller` n'est pas routée.**

**Les routes d'attributs viennent des services étiquetés**, pas d'un parcours
de répertoire.

## Points clés

- Le composant fait correspondre une requête à des **variables**.
- Il est autonome : PHP et un paquet de contrats, rien d'autre.
- `UrlMatcher` et `UrlGenerator` sont deux classes séparées.
- `RouterListener` : HttpKernel, `kernel.request` à 32 ; 404 et 405.
- FrameworkBundle câble le routeur compilé, l'écouteur et le chargeur.

## Sources officielles

- [composer.json du composant Routing](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/composer.json)
- [UrlGeneratorInterface, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Generator/UrlGeneratorInterface.php)
- [`RouterListener`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/EventListener/RouterListener.php)
- [Services de routage de FrameworkBundle](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Resources/config/routing.php)
- [Autoconfiguration de `#[Route]`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/FrameworkExtension.php)
