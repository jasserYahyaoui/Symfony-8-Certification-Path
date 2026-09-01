---
id: CRS-tygmreqkkds3
official_item: OIT-qr7gnht5s847
title: "HttpKernel component and FrameworkBundle"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpKernel/Kernel.php"
    symbol_or_lines: "abstract class Kernel implements KernelInterface, RebootableInterface, TerminableInterface"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/configuration/micro_kernel_trait.rst"
    anchor: "building-your-own-framework-with-the-microkerneltrait"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
---

## Objectif

Séparer ce que le **composant** HttpKernel fournit de ce que **FrameworkBundle**
y ajoute. Le déroulé d'une requête et le catalogue des événements sont traités
dans le lot Symfony Architecture ; on regarde ici le partage des rôles.

## Ce que le composant fournit

HttpKernel est autonome. Il contient :

- `HttpKernelInterface` et son unique contrat `handle(Request): Response` ;
- la classe abstraite `Kernel`, qui implémente `KernelInterface`,
  `RebootableInterface` et `TerminableInterface` — c'est elle qui enregistre les
  bundles, construit le conteneur et expose `getEnvironment()`, `isDebug()`,
  `getProjectDir()`, `getCacheDir()`, `getLogDir()` ;
- les huit événements, les interfaces de résolution (`ControllerResolverInterface`,
  `ValueResolverInterface`) et leurs implémentations par défaut ;
- les exceptions HTTP, dont `NotFoundHttpException`.

Avec cela seul, on peut écrire son propre framework : c'est le sens de la
phrase « le composant est le cœur de n'importe quelle application ».

## Ce que FrameworkBundle ajoute

FrameworkBundle est le **câblage**. Il ne réécrit rien du composant ; il
l'installe dans un conteneur de services et lui donne des collaborateurs :

| Apport | Effet |
|---|---|
| Écouteurs enregistrés | `RouterListener` sur `kernel.request`, `ErrorListener` sur `kernel.exception`, et les autres |
| Résolveurs déclarés comme services | tagués `controller.argument_value_resolver`, donc extensibles |
| Arbre de configuration `framework:` | ce que l'on écrit dans `config/packages/framework.yaml` |
| Commandes `bin/console` | `debug:router`, `debug:event-dispatcher`, `cache:clear`… |
| Routes internes | dont `_error/{statusCode}`, la prévisualisation des pages d'erreur |
| `AbstractController` | la classe de base optionnelle des contrôleurs |
| `MicroKernelTrait` | configuration du noyau sans fichiers séparés |

La frontière la plus utile à retenir passe entre deux classes voisines :
`Kernel` appartient au **composant** HttpKernel, `MicroKernelTrait` appartient
au **bundle**. Le `Kernel` d'une application Symfony étend la première et
utilise le second.

## Points clés

- Le composant définit le contrat et le noyau ; le bundle le câble.
- `Kernel` implémente `KernelInterface`, `RebootableInterface` et
  `TerminableInterface`.
- `AbstractController`, `MicroKernelTrait` et l'arbre `framework:` viennent du
  bundle, pas du composant.
- Sans FrameworkBundle, HttpKernel fonctionne toujours — l'inverse est faux.

## Sources officielles

- [Kernel, branche 8.0](https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpKernel/Kernel.php)
- [Building your own Framework with the MicroKernelTrait](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/configuration/micro_kernel_trait.rst)
