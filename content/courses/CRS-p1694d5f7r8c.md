---
id: CRS-p1694d5f7r8c
official_item: OIT-79f4n087b66c
title: "Service container"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-27"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/service_container.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/service_container.rst"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    symbol_or_lines: 'Fetching and using Services — "if you never ask for the service, it''s *never* constructed"; "the same instance is returned each time you ask for it"; Public Versus Private Services — "Every service defined is private by default"'
    verified_at: "2026-10-27"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/service_container/alias_private.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/service_container/alias_private.rst"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    symbol_or_lines: 'Marking Services as Public / Private — #[Autoconfigure(public: true)]; "Private services are special because they allow the container to optimize"; Aliasing — alias, public alias, shortcut app.mailer: ''@App\Mail\PhpMailer'', #[AsAlias]'
    verified_at: "2026-10-27"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/service_container/autowiring.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/service_container/autowiring.rst"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    symbol_or_lines: 'Working with Interfaces — "To fix that, add an alias"; tip "if only one service is discovered that implements an interface, configuring the alias is not mandatory and Symfony will automatically create one"'
    verified_at: "2026-10-27"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/testing.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/testing.rst"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    symbol_or_lines: '"The container from static::getContainer() is actually a special test container. It gives you access to both the public services and the non-removed private services"; config/services_test.yaml'
    verified_at: "2026-10-27"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpKernel/Kernel.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/Kernel.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "getContainerClass() — kernel class with _ + environment + Debug + Container; initializeContainer() — includes the cached class, checks freshness only in debug"
    verified_at: "2026-10-27"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/Kernel/MicroKernelTrait.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Kernel/MicroKernelTrait.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "configureContainer() — imports services.yaml, then {services}_<environment>.yaml"
    verified_at: "2026-10-27"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/Command/ContainerDebugCommand.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Command/ContainerDebugCommand.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "configure() — argument name; options show-hidden, tag, tags, parameter, parameters, types, env-var, env-vars, format, raw, deprecations; no show-private"
    verified_at: "2026-10-27"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/Console/Descriptor/TextDescriptor.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Console/Descriptor/TextDescriptor.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: 'describeContainerServices() — title "Symfony Container Hidden Services"; "if ($showHidden xor ''.'' === ($serviceId[0] ?? null))" filters out the other services'
    verified_at: "2026-10-27"
outcome_sections:
  OUT-4mx2cr2ydxtf: "Ce que le container contient : des definitions"
  OUT-rx9sxdh2gjgm: "Public ou privé : trois questions"
  OUT-21rjj7sevj21: "L'alias : un second identifier"
  OUT-8hr9dpyd6r1k: "La compilation : pourquoi la configuration n'est pas relue"
---

## Objectif

Savoir ce que le container contient et quand il construit un object, ce que
public et privé changent, à quoi sert un alias, et pourquoi une modification de
configuration reste sans effet tant que le container n'est pas recompilé. La
déclaration des services elle-même est traitée dans *Services registration*.

## L'essentiel

Le container garde des **definitions**, pas des objects : un service est
construit au **premier usage**, puis partagé. Il est **privé** par défaut —
injectable, mais pas récupérable par `get()`. Un **alias** rend une interface
injectable. Et le container est **compilé** en une class PHP : sans debug, la
configuration n'est pas relue.

## Le schéma

```text
configuration   services.yaml (+ services_<env>.yaml) → une definition par class de src/, privée
compilation     autowiring, alias résolus, privés retirés ou inlinés, tout figé — rien n'est construit
cache           var/cache/prod/App_KernelProdContainer.php : une class PHP, la configuration n'est plus relue
exécution       premier usage → l'object est construit ; usages suivants → la même instance
```

## Le fil rouge : `PhpMailer`, `MailerInterface` et la `Newsletter`

```php
namespace App\Mail;

interface MailerInterface {}
class PhpMailer implements MailerInterface {}

class Newsletter
{
    public function __construct(private MailerInterface $mailer) {}
}
```

La `Newsletter` demande une interface ; c'est `PhpMailer` qui doit arriver.

## Ce que le container contient : des definitions

- Une definition est le plan d'un service : class, arguments, calls, tags,
  visibilité. **Compiler ne construit rien** ; « if you never ask for the
  service, it's *never* constructed ».
- **Partagé** par défaut : deux services qui reçoivent `PhpMailer` reçoivent
  le même object. `shared: false` (`setShared(false)`) en donne un neuf à
  chaque fois.
- L'identifier d'un service découvert dans `src/` est son **FQCN** ; c'est ce
  qui rend l'autowiring possible.

## Public ou privé : trois questions

| Question | Privé (le défaut) | Public |
|---|---|---|
| **Injectable** dans un autre service ? | **oui** | oui |
| Récupérable par **`get()`** une fois compilé ? | **non** | oui |
| **Retirable** ou inlinable par la compilation ? | **oui** | non |

- `get()` d'un service privé : `ServiceNotFoundException`, « …has been removed
  or inlined when the container was compiled » ; `has()` rend `false`.
- Rendre public : `public: true`, `#[Autoconfigure(public: true)]`, ou un
  **alias** public.
- Privé par défaut : performance, et des erreurs tôt — un service obtenu par
  injection l'est par une référence que la compilation vérifie.
- En test, `static::getContainer()` atteint les privés **non retirés** ; un
  privé retiré se rend public dans `config/services_test.yaml`.

## L'alias : un second identifier

```yaml
services:
    app.mailer: { alias: App\Mail\PhpMailer, public: true }
    App\Mail\MailerInterface: '@App\Mail\PhpMailer'      # rend l'interface injectable
```

- Un alias ne crée aucun object, et il est **privé par défaut** ;
  `#[AsAlias(id: 'app.mailer', public: true)]` l'écrit sur la class.
- Aucun service ne s'appelle `MailerInterface` : l'alias du même nom y
  remédie. Si **une seule** class de `src/` implémente l'interface, Symfony le
  crée seul ; **deux** implémentations font échouer la compilation, « Cannot
  autowire service … You should maybe alias this interface ».

## La compilation : pourquoi la configuration n'est pas relue

| Kernel | Class compilée, dans `var/cache/<env>/` |
|---|---|
| `App\Kernel`, `prod`, sans debug | `App_KernelProdContainer` |
| `App\Kernel`, `test`, debug | `App_KernelTestDebugContainer` |

- **Debug** : le kernel vérifie la fraîcheur du cache et recompile si une
  source a changé.
- **Sans debug** : il inclut le file sans vérifier ; une modification de
  `services.yaml` attend `php bin/console cache:clear`.
- `services_<env>.yaml` s'**ajoute** à `services.yaml`, il ne le remplace pas.

## Inspecter le container

- `debug:container` liste les services, **privés compris** : il n'existe pas
  de `--show-private`.
- `--show-hidden` n'affiche **que** les services internes, dont l'identifier
  commence par un point ; le titre devient
  « Symfony Container Hidden Services ».
- `debug:autowiring Mailer` liste les **types** autowirables ; `--all` ajoute
  les services concrets.

## Pièges d'examen

**Un service privé est injectable.** « Privé » ne restreint que
`$container->get()` sur le container compilé. Un énoncé qui conclut qu'un
service privé ne peut pas être passé en argument à un autre service se trompe.

**Privé est le défaut.** Un `get()` qui échoue sur un service de `src/` parfaitement
enregistré et injecté ailleurs n'indique ni une erreur de déclaration ni un
container non compilé : le service est simplement privé.

**Le container ne construit rien à la compilation.** Les objects naissent au
premier usage, puis sont réutilisés. Un énoncé qui affirme que tous les
services sont instanciés au démarrage ou à `compile()` est faux.

**Deux injections, une instance.** Un service partagé (le défaut) est le même
object pour tous ceux qui le reçoivent. Il faut `shared: false` pour obtenir des
instances distinctes.

**L'alias d'interface n'est automatique qu'avec une seule implémentation.**
Ajouter une seconde class qui implémente l'interface fait échouer la
compilation (« Cannot autowire service … You should maybe alias this
interface ») : il faut déclarer l'alias.

**Un alias est privé par défaut, lui aussi.** Créer un alias ne donne pas accès
à un service par `get()` : il faut que l'alias soit public.

**La configuration n'est pas relue en production.** Le container est une class
PHP compilée dans `var/cache/<env>/` ; sans debug, le kernel ne vérifie pas sa
fraîcheur. Une modification de `services.yaml` reste sans effet tant que le
cache n'est pas vidé.

**`--show-private` n'existe pas, et `--show-hidden` ne fait pas ce que son nom
suggère.** Les services privés sont listés par défaut ; `--show-hidden`
affiche uniquement les services internes, ceux dont l'identifier commence par
un point.

## Application

- `get(ReportExporter::class)` échoue alors que le service est injecté
  ailleurs → il est privé : l'injecter.
- Une seconde class implémente `MailerInterface` et la compilation échoue →
  déclarer l'alias de l'interface.

## QCM d'entraînement

**Question 1.** Quelle class porte le container compilé de `App\Kernel`, en
environment `prod`, sans debug ?

- **A.** `App_KernelProdDebugContainer`
- **B.** `App_KernelProdContainer`
- **C.** `AppKernelContainer`
- **D.** `ProdContainer`, dans le namespace du kernel

<details>
<summary>Correction</summary>

**Bonne réponse : B.** La class du kernel (`\` remplacés par `_`), puis
l'environment, puis `Debug` seulement en debug, puis `Container`.

</details>

**Question 2.** `src/` contient `PhpMailer` et `SmtpMailer`, qui implémentent
`MailerInterface`, et une `Newsletter` qui la demande. Aucun alias. Que se
passe-t-il ?

- **A.** La compilation échoue et le message propose de déclarer un alias
- **B.** Le container choisit la première class par ordre alphabétique
- **C.** Le container injecte les deux implémentations dans un array itérable
- **D.** La `Newsletter` reçoit `null`, et l'erreur n'apparaît qu'au premier envoi

<details>
<summary>Correction</summary>

**Bonne réponse : A.** L'alias automatique n'existe qu'avec une seule
implémentation ; avec deux, l'autowiring ne peut pas choisir.

</details>

**Question 3.** Que fait `php bin/console debug:container --show-hidden` ?

- **A.** Il ajoute les services privés aux publics, seuls listés par défaut
- **B.** Il affiche tous les services, internes et ordinaires compris, dans une seule liste
- **C.** Il n'affiche que les services dont l'identifier commence par un point
- **D.** Il échoue : l'option a été remplacée par `--show-private` en 8.0

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Les services ordinaires disparaissent de la liste ; les
privés, eux, sont déjà listés sans option.

</details>

**Question 4.** Dans un functional test, `get(App\Tax\VatCalculator::class)`
lève `ServiceNotFoundException` : le service est privé et rien ne l'injecte.
Quelle correction ?

- **A.** Le rendre public dans `config/services.yaml`, pour tous les environments
- **B.** Remplacer `static::getContainer()` par `$client->getContainer()`
- **C.** Appeler `compile()` à nouveau dans le test
- **D.** Le déclarer public dans `config/services_test.yaml`

<details>
<summary>Correction</summary>

**Bonne réponse : D.** Le container de test n'expose que les privés non
retirés ; le rendre public en test seulement l'empêche d'être retiré.

</details>

## Fiche express

**Si tu dois retenir seulement 7 choses :**

- Des **definitions** ; l'object au **premier usage**, puis **partagé**.
- Identifier par défaut : le **FQCN**.
- **Privé par défaut** : injectable, pas de `get()`, retirable.
- **Alias** d'interface : automatique avec **une seule** implémentation.
- Un alias est privé par défaut, lui aussi.
- Compilé en `App_Kernel` + env + (`Debug`) + `Container` ; sans debug,
  **`cache:clear`**.
- `debug:container` liste les privés ; **`--show-hidden`** = les internes seuls.

Moyen mnémotechnique : **« I-G-R : Injectable, pas de Get, Retirable »**.

## Sources officielles

- [Service Container, « Fetching and using Services », « Public Versus Private Services »](https://github.com/symfony/symfony-docs/blob/8.0/service_container.rst)
- [How to Create Service Aliases and Mark Services as Public](https://github.com/symfony/symfony-docs/blob/8.0/service_container/alias_private.rst)
- [Defining Services Dependencies Automatically (Autowiring), « Working with Interfaces »](https://github.com/symfony/symfony-docs/blob/8.0/service_container/autowiring.rst)
- [Testing, `static::getContainer()`](https://github.com/symfony/symfony-docs/blob/8.0/testing.rst)
- [HttpKernel 8.0, `Kernel`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/Kernel.php)
- [FrameworkBundle 8.0, `MicroKernelTrait`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Kernel/MicroKernelTrait.php)
- [FrameworkBundle 8.0, `ContainerDebugCommand`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Command/ContainerDebugCommand.php)
- [FrameworkBundle 8.0, `TextDescriptor`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Console/Descriptor/TextDescriptor.php)

