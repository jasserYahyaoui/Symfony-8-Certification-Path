---
id: CRS-0a0d5bp6769e
official_item: OIT-gkhcbtygef69
title: "Service locators"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-28"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/service_container/service_subscribers_locators.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/service_container/service_subscribers_locators.rst"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    symbol_or_lines: "ServiceSubscriberInterface, optional services, AutowireLocator, ServiceLocator definition, indexing, ServiceMethodsSubscriberTrait, testing a subscriber"
    verified_at: "2026-10-28"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Contracts/Service/ServiceSubscriberInterface.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Contracts/Service/ServiceSubscriberInterface.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "public static function getSubscribedServices(): array"
    verified_at: "2026-10-28"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/DependencyInjection/ServiceLocator.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/ServiceLocator.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "createNotFoundException() — is a smaller service locator that"
    verified_at: "2026-10-28"
outcome_sections:
  OUT-3c8n410rjew2: "Le cas légitime : beaucoup de dependencies, une seule utilisée"
  OUT-treyye0w8xe4: "Ce que cela n'est pas : le container"
  OUT-g6613nkxjesh: "Hériter : fusionner avec le parent"
  OUT-7s1prxwgdhe5: "La forme courte : `#[AutowireLocator]`"
---

## Objectif

Recevoir un petit container restreint quand injecter tout est absurde, sans
retomber dans la récupération de services. Le cas particulier
d'`AbstractController` est traité dans son propre item (lot Controllers).

## L'essentiel

Une class qui dispatche vers dix handlers n'en utilise qu'un par appel. Un
**service locator** est un petit container limité aux services qu'elle
**déclare**, qui ne construit chacun qu'au `get()`. Ce n'est pas le container
de l'application : ses dependencies restent lisibles et vérifiées à la
compilation.

## Le schéma

```text
compilation   getSubscribedServices() lue (statique) : manquant sans ? → échec ; avec ? → absent du locator
injection     le bus reçoit un ServiceLocator : aucun handler construit
get('Foo')    construit Foo maintenant — la même instance que le container
get('Bar')    non déclaré → ServiceNotFoundException « …is a smaller service locator… »
```

## Le fil rouge : un CommandBus

```php
class CommandBus implements ServiceSubscriberInterface
{
    public function __construct(private ContainerInterface $locator) {}

    public static function getSubscribedServices(): array
    {
        return [
            'App\Handler\FooHandler',
            '?App\Handler\BarHandler',       // facultatif
            'logger' => LoggerInterface::class,
        ];
    }

    public function handle(Command $c): void
    {
        $this->locator->get($c->handlerId())->handle($c);
    }
}
```

## Le cas légitime : beaucoup de dependencies, une seule utilisée

| Situation | Résultat |
|---|---|
| après construction du bus | aucun handler construit |
| `get(FooHandler::class)` | construit maintenant, **même instance** que dans le container |
| `?…\Missing` absent | `has()` rend `false` ; absent de `getProvidedServices()` et de `count()` |
| le même, **sans** `?` | la compilation échoue : « …has a dependency on a non-existent service… » |
| `get()` d'un service non déclaré | « even though it exists in the app's container, the container inside … is a smaller service locator… » |

- `getSubscribedServices()` est **statique** ; une key (`'logger'`) donne un
  nom local.
- Le locator est aussi un `ServiceProviderInterface` : `getProvidedServices()`,
  `count()`, `foreach`.
- Viser un autre service derrière une key : un tag
  `container.service_subscriber` avec `key` et `id`, ou un `SubscribedService`
  avec `new Autowire(service: …)`.

## Ce que cela n'est pas : le container

| | Container injecté | Service locator |
|---|---|---|
| contenu | tout | la liste déclarée |
| services privés | inaccessibles | accessibles s'ils sont déclarés |
| dependency manquante | à l'exécution | à la **compilation** (sans `?`) |

## La forme courte : `#[AutowireLocator]`

```php
public function __construct(
    #[AutowireLocator([
        'foo' => FooHandler::class,
        'optionalBaz' => '?'.BazHandler::class,
        BarHandler::class,
    ])]
    private ContainerInterface $handlers,
) {}
```

- Sans implémenter l'interface. `#[AutowireLocator('app.handler')]` le
  construit depuis un **tag** : keys = `index` du service, sinon son
  identifier.
- En YAML : `!tagged_locator app.handler`, ou un service `ServiceLocator`
  déclaré à la main.
- `ServiceMethodsSubscriberTrait` : une method `#[SubscribedService]` par
  service, de key `__METHOD__`.

## Hériter : fusionner avec le parent

```php
return array_merge(parent::getSubscribedServices(), ['…']);
```

Un `ChildBus` qui oublie `parent::` perd en silence les services du parent :
`has(FooHandler::class)` vaut `false`, sans erreur avant l'usage.

## Pièges d'examen

**`getSubscribedServices()` est statique.**

**Le locator n'est pas le container** : il est restreint à la liste déclarée, et
le dit dans son message d'erreur.

**Les services sont construits à la demande** — et partagés avec le container.

**Sans `?`, un service manquant bloque la compilation** ; avec `?`, `has()`
rend `false`.

**En inheritance, il faut fusionner avec `parent::`.**

**Un service facultatif absent disparaît du locator** : exécuté,
`getProvidedServices()` ne le liste pas et `count()` ne le compte pas.

## Application

- Un exporteur à douze formats, un seul utilisé par appel → un locator, pas
  douze arguments.
- Un handler optionnel → `?` dans la liste, et `has()` avant `get()`.

## QCM d'entraînement

**Question 1.** `getSubscribedServices()` déclare `'App\Pdf\PdfRenderer'`, sans
`?`, et aucun service de ce nom n'existe. Quand l'erreur apparaît-elle ?

- **A.** À la compilation du container
- **B.** Au premier `get()` du service, seulement s'il est appelé
- **C.** Jamais : `has()` rend `false`, à la class de vérifier
- **D.** À la construction du subscriber, quand le locator est injecté

<details>
<summary>Correction</summary>

**Bonne réponse : A.** Sans `?`, la dependency est obligatoire et vérifiée à la
compilation ; C décrit le cas avec `?`.

</details>

**Question 2.** Les handlers sont des services **privés**. Le bus les déclare
dans `getSubscribedServices()`. Son locator peut-il les rendre ?

- **A.** Non : un service privé n'est jamais récupérable
- **B.** Oui, puisqu'ils sont déclarés
- **C.** Seulement s'ils sont rendus publics
- **D.** Seulement s'ils sont préfixés par `?`

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Le container de l'application ne les rend pas, mais le
locator contient tout ce que la class a déclaré, privés compris.

</details>

**Question 3.** Avec `ServiceMethodsSubscriberTrait`, sous quelle key une
method `#[SubscribedService]` retrouve-t-elle son service ?

- **A.** Le nom court de la method, `router`
- **B.** Le return type de la method
- **C.** `__METHOD__`, soit `Classe::method`
- **D.** L'identifier du service dans le container

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Le locator ne connaît que la key `Classe::method` ; dans
un trait d'aide, on écrit `__CLASS__.'::'.__FUNCTION__`.

</details>

**Question 4.** Un subscriber déclare `[PdfRenderer::class,
'?App\Pdf\Watermarker']`, et `Watermarker` n'existe pas. Que rend
`getProvidedServices()` ?

- **A.** Les deux keys, `Watermarker` marquée absente
- **B.** Une erreur : un service facultatif manque
- **C.** Un array vide tant qu'aucun `get()` n'a eu lieu
- **D.** Seulement la key de `PdfRenderer`

<details>
<summary>Correction</summary>

**Bonne réponse : D.** Un service facultatif absent disparaît du locator : ni
`getProvidedServices()` ni `count()` ne le comptent.

</details>

## Fiche express

**Si tu dois retenir seulement 6 choses :**

- **`getSubscribedServices()`** est statique.
- Locator ≠ container : la **liste déclarée**, privés compris.
- Construit **au `get()`**, partagé avec le container.
- Sans `?` : échec à la **compilation** ; avec `?` : `has()` faux.
- **`#[AutowireLocator]`** : sans l'interface, depuis une liste ou un tag.
- Héritage : **`array_merge(parent::…)`**.

Moyen mnémotechnique : **« Déclarer, puis demander »** — rien n'est construit
avant le `get()`.

## Sources officielles

- [Service Subscribers & Locators](https://github.com/symfony/symfony-docs/blob/8.0/service_container/service_subscribers_locators.rst)
- [Service Contracts, `ServiceSubscriberInterface`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Contracts/Service/ServiceSubscriberInterface.php)
- [DependencyInjection 8.0, `ServiceLocator`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/ServiceLocator.php)

