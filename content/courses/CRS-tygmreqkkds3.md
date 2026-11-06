---
id: CRS-tygmreqkkds3
official_item: OIT-qr7gnht5s847
title: "HttpKernel component and FrameworkBundle"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-07"
format: pedagogical
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

## L'essentiel

Le **component** HttpKernel est autonome : le contrat `handle()`, la class
`Kernel`, les events, les resolvers, les exceptions HTTP. **FrameworkBundle**
ne réécrit rien : il **câble** — listeners enregistrés, resolvers en services,
arbre `framework:`, commands, `AbstractController`, `MicroKernelTrait`. Une
question tranche : cela marche-t-il sans service container ? Oui → component.

## Le schéma

```text
component HttpKernel   HttpKernelInterface::handle() · abstract Kernel · 8 events · resolvers · NotFoundHttpException
  Kernel implements KernelInterface (extends HttpKernelInterface), RebootableInterface, TerminableInterface
  abstraites : registerBundles(), registerContainerConfiguration()
FrameworkBundle        câblage : RouterListener, ErrorListener… enregistrés · resolvers tagués
                       framework: · bin/console · /_error · AbstractController · MicroKernelTrait
MicroKernelTrait       implémente les deux abstraites → appelle configureContainer(), configureRoutes()
```

## L'exemple : le kernel d'une application

```php
namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;   // bundle
use Symfony\Component\HttpKernel\Kernel as BaseKernel;          // component

class Kernel extends BaseKernel
{
    use MicroKernelTrait;
}
```

## Ce que le component fournit

`HttpKernelInterface` et son contrat `handle(Request): Response` ; l'abstract
class `Kernel`, qui enregistre les bundles, construit le container et expose
`getEnvironment()`, `isDebug()`, `getProjectDir()`, `getCacheDir()`,
`getLogDir()` ; les huit events ; `ControllerResolverInterface`,
`ValueResolverInterface` et leurs implémentations ; les exceptions HTTP. Avec
cela seul, on écrit son propre framework.

## Trois interfaces, et ce que chacune apporte

**`KernelInterface` étend `HttpKernelInterface`** : un `Kernel` porte
`handle()` sans le déclarer.

| Interface | Ce qu'elle ajoute |
|---|---|
| `KernelInterface` | dix-sept methods — `boot()`, `shutdown()`, `registerBundles()`, `getBundle()`, `locateResource()`, les accessors de directories… et `handle()` par inheritance |
| `RebootableInterface` | `reboot(?string $warmupDir): void` |
| `TerminableInterface` | `terminate(Request, Response): void` |

## Ce que FrameworkBundle ajoute

| Apport | Effet |
|---|---|
| Listeners enregistrés | `RouterListener` sur `kernel.request`, `ErrorListener` sur `kernel.exception`… |
| Resolvers en services | tagués `controller.argument_value_resolver`, donc extensibles |
| Arbre `framework:` | `config/packages/framework.yaml` |
| Commands `bin/console` | `debug:router`, `cache:clear`… |
| Routes internes | dont la prévisualisation `/_error` |
| `AbstractController`, `MicroKernelTrait` | class de base, kernel sans files séparés |

Les classes des listeners viennent du component ; leur enregistrement, du
bundle.

`MicroKernelTrait` **implémente** `registerBundles()` et
`registerContainerConfiguration()`, les deux methods restées abstraites dans
`Kernel`, et appelle `configureContainer()` et `configureRoutes()` par
réflexion, ce qui lui permet de lire la signature de `configureContainer()`.
Que ces methods puissent être `private` ne doit rien à la réflexion : le trait
est copié dans votre `Kernel`, qui appelle ses propres methods privées.

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

## Application

- Chercher l'option `framework.session` → dans le bundle, pas le component.
- Un micro-framework maison sur HttpKernel seul → fournir `registerBundles()`
  et `registerContainerConfiguration()`.

## QCM d'entraînement

**Question 1.** Que déclare `RebootableInterface` ?

- **A.** `reboot(?string $warmupDir): void`
- **B.** `reboot(): bool`
- **C.** `restart()` et `reboot()`
- **D.** `boot()`, `shutdown()` puis `reboot()`

<details>
<summary>Correction</summary>

**Bonne réponse : A.** L'interface ne porte que cette method ; `boot()` et
`shutdown()` appartiennent à `KernelInterface`.

</details>

**Question 2.** La class `RouterListener` vient du component HttpKernel. Qui
l'enregistre sur `kernel.request` dans une application Symfony ?

- **A.** Le component, qui la dispatche lui-même
- **B.** FrameworkBundle
- **C.** Le component Routing
- **D.** L'application, dans `services.yaml`

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Le component fournit la class ; c'est le bundle qui
câble le listener dans le container et l'abonne à l'event.

</details>

**Question 3.** Dans un `Kernel` qui utilise `MicroKernelTrait`, qui implémente
`registerBundles()` ?

- **A.** La class `Kernel` du component
- **B.** L'application, obligatoirement
- **C.** `MicroKernelTrait`
- **D.** `KernelInterface`, par défaut

<details>
<summary>Correction</summary>

**Bonne réponse : C.** La method reste abstraite dans `Kernel` ; le trait du
bundle l'implémente, comme `registerContainerConfiguration()`.

</details>

**Question 4.** Combien de methods `KernelInterface` déclare-t-elle elle-même ?

- **A.** Une : `handle()`
- **B.** Dix-huit, dont `handle()` et `terminate()`
- **C.** Trois
- **D.** Dix-sept, sans `handle()`

<details>
<summary>Correction</summary>

**Bonne réponse : D.** `handle()` est héritée de `HttpKernelInterface`, que
l'interface étend ; elle n'apparaît pas parmi ses propres methods.

</details>

## Fiche express

**Si tu dois retenir seulement 6 choses :**

- **Component** : contrat, `Kernel`, events, resolvers ; il marche **seul**.
- **Bundle** : il **câble** — listeners, `framework:`, `bin/console`.
- `Kernel` : `KernelInterface`, **`RebootableInterface`**, **`TerminableInterface`**.
- `KernelInterface` **étend** `HttpKernelInterface` → `handle()` hérité.
- `AbstractController` et **`MicroKernelTrait`** : bundle.
- Le trait implémente `registerBundles()` et `registerContainerConfiguration()`.

Moyen mnémotechnique : **« Le component sait faire, le bundle branche »**.

## Sources officielles

- [Kernel, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/Kernel.php)
- [Building your own Framework with the MicroKernelTrait](https://github.com/symfony/symfony-docs/blob/8.0/configuration/micro_kernel_trait.rst)
- [`KernelInterface`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/KernelInterface.php)
- [`MicroKernelTrait` (FrameworkBundle)](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Kernel/MicroKernelTrait.php)

