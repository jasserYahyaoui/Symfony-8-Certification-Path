---
id: CRS-tygmreqkkds3
official_item: OIT-qr7gnht5s847
title: "HttpKernel component and FrameworkBundle"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-07"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpKernel/Kernel.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/Kernel.php"
    symbol_or_lines: "abstract class Kernel implements KernelInterface, RebootableInterface, TerminableInterface"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/configuration/micro_kernel_trait.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/configuration/micro_kernel_trait.rst"
    anchor: "building-your-own-framework-with-the-microkerneltrait"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpKernel/KernelInterface.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/KernelInterface.php"
    symbol_or_lines: "interface KernelInterface extends HttpKernelInterface"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-22"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/Kernel/MicroKernelTrait.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Kernel/MicroKernelTrait.php"
    symbol_or_lines: "registerBundles, registerContainerConfiguration, configureContainer, configureRoutes"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-22"
outcome_sections:
  OUT-dvn4fe5a95kf: "Ce que FrameworkBundle ajoute"
  OUT-9n7kqntbxaap: "Trois interfaces, et ce que chacune apporte"
  OUT-wtvmvsdmdz41: "Ce que FrameworkBundle ajoute"
  OUT-8a4ygtt2rwe5: "Ce que le component fournit"
---

## Objectif

Séparer ce que le **component** HttpKernel fournit de ce que **FrameworkBundle**
y ajoute. Le déroulé d'une Request et le catalogue des events sont traités
dans le lot Symfony Architecture ; on regarde ici le partage des roles.

## Ce que le component fournit

HttpKernel est autonome. Il contient :

- `HttpKernelInterface` et son unique contrat `handle(Request): Response` ;
- l'abstract class `Kernel`, qui implémente `KernelInterface`,
  `RebootableInterface` et `TerminableInterface` — c'est elle qui enregistre les
  bundles, construit le container et expose `getEnvironment()`, `isDebug()`,
  `getProjectDir()`, `getCacheDir()`, `getLogDir()` ;
- les huit events, les interfaces de résolution (`ControllerResolverInterface`,
  `ValueResolverInterface`) et leurs implémentations par défaut ;
- les exceptions HTTP, dont `NotFoundHttpException`.

Avec cela seul, on peut écrire son propre framework : c'est le sens de la
phrase « le component est le cœur de n'importe quelle application ».

## Trois interfaces, et ce que chacune apporte

`Kernel` est déclarée ainsi :

```php
abstract class Kernel implements KernelInterface, RebootableInterface, TerminableInterface
```

Le détail qui structure tout : **`KernelInterface` étend `HttpKernelInterface`**.
Un `Kernel` **est** donc un `HttpKernelInterface` — il porte `handle()` sans
qu'on ait à le déclarer. Les deux autres interfaces n'apportent **qu'une method
chacune** :

| Interface | Ce qu'elle ajoute |
|---|---|
| `KernelInterface` | dix-sept methods — `boot()`, `shutdown()`, `registerBundles()`, `getBundle()`, `locateResource()`, les accessors de directories… **et `handle()` par inheritance** |
| `RebootableInterface` | `reboot(?string $warmupDir): void` |
| `TerminableInterface` | `terminate(Request, Response): void` |

## Ce que FrameworkBundle ajoute

FrameworkBundle est le **câblage**. Il ne réécrit rien du component ; il
l'installe dans un service container et lui donne des collaborateurs :

| Apport | Effet |
|---|---|
| Listeners enregistrés | `RouterListener` sur `kernel.request`, `ErrorListener` sur `kernel.exception`, et les autres |
| Resolvers déclarés comme services | tagués `controller.argument_value_resolver`, donc extensibles |
| Arbre de configuration `framework:` | ce que l'on écrit dans `config/packages/framework.yaml` |
| Commands `bin/console` | `debug:router`, `debug:event-dispatcher`, `cache:clear`… |
| Routes internes | dont `_error/{statusCode}`, la prévisualisation des pages d'erreur |
| `AbstractController` | la class de base optionnelle des controllers |
| `MicroKernelTrait` | configuration du kernel sans files séparés |

La frontière la plus utile à retenir passe entre deux classes voisines :
`Kernel` appartient au **component** HttpKernel, `MicroKernelTrait` appartient
au **bundle**. Le `Kernel` d'une application Symfony étend la première et
utilise le second.

### Ce que `MicroKernelTrait` fait réellement

Il **implémente** `registerBundles()` et `registerContainerConfiguration()` à
votre place — les deux methods que `KernelInterface` exige. En échange, il
appelle deux points d'extension : `configureContainer()` et `configureRoutes()`.

Il les appelle **par réflexion** (`ReflectionMethod::getClosure()`), ce qui lui
permet de lire la signature de `configureContainer()` : un premier parameter
typé `ContainerBuilder` reçoit le container et le loader, tout autre cas un
`ContainerConfigurator`.

Leur visibilité `private` possible ne doit rien à la réflexion. Le trait est
copié dans votre `Kernel`, qui appelle ses propres methods privées — exécuté :
un trait appelle sans réflexion une method privée de la class qui l'utilise.

## Pièges d'examen

**Le component fonctionne sans le bundle.** HttpKernel est autonome : le contrat
`handle()`, la class `Kernel`, les events et les resolvers sont dans le
component. On peut écrire un framework avec cela seul.

**FrameworkBundle ne réécrit rien ; il câble.** Listeners enregistrés,
resolvers tagués comme services, arbre de configuration `framework:`, commands
`bin/console`, routes internes. Chercher l'arbre de configuration dans le
component, c'est ne pas le trouver.

**`AbstractController` vient du bundle**, pas du component — c'est le même
partage. Le path le prouve :
`src/Symfony/Bundle/FrameworkBundle/Controller/AbstractController.php` existe,
le path équivalent sous `src/Symfony/Component/HttpKernel/` **n'existe pas**.

**`Kernel` porte `handle()` sans l'avoir déclaré.** `KernelInterface` étend
`HttpKernelInterface` ; chercher `handle()` dans la liste des methods de
`KernelInterface` ne le donne pas.

## Tips d'examen

**Deux questions pour trancher component / bundle.** Cela fonctionne-t-il sans
service container ? → component. Cela apparaît-il dans
`config/packages/framework.yaml` ou dans `bin/console` ? → bundle.

**Trois interfaces, une method chacune sauf la première.** `reboot()` et
`terminate()` sont à elles seules `RebootableInterface` et
`TerminableInterface`.

## Points clés

- Le component définit le contrat et le kernel ; le bundle le câble.
- `Kernel` implémente `KernelInterface`, `RebootableInterface` et
  `TerminableInterface`.
- `AbstractController`, `MicroKernelTrait` et l'arbre `framework:` viennent du
  bundle, pas du component.
- Sans FrameworkBundle, HttpKernel fonctionne toujours — l'inverse est faux.
- `KernelInterface` **étend** `HttpKernelInterface` : un `Kernel` porte
  `handle()` par inheritance.
- `MicroKernelTrait` implémente `registerBundles()` et
  `registerContainerConfiguration()`, et appelle `configureContainer()` /
  `configureRoutes()` **par réflexion** ; leur visibilité `private` tient à
  ce que le trait est copié dans la class, non à la réflexion.

## Sources officielles

- [Kernel, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/Kernel.php)
- [Building your own Framework with the MicroKernelTrait](https://github.com/symfony/symfony-docs/blob/8.0/configuration/micro_kernel_trait.rst)
- [`KernelInterface`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/KernelInterface.php)
- [`MicroKernelTrait` (FrameworkBundle)](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Kernel/MicroKernelTrait.php)
