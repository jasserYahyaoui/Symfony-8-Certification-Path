---
id: CRS-0a0d5bp6769e
official_item: OIT-gkhcbtygef69
title: "Service locators"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-30"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/service_container/service_subscribers_locators.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/service_container/service_subscribers_locators.rst"
    branch: "8.0"
    symbol_or_lines: "ServiceSubscriberInterface, getSubscribedServices, AutowireLocator"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Contracts/Service/ServiceSubscriberInterface.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Contracts/Service/ServiceSubscriberInterface.php"
    symbol_or_lines: "public static function getSubscribedServices(): array"
    branch: "8.0"
    verified_at: "2026-09-30"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/DependencyInjection/ServiceLocator.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/ServiceLocator.php"
    symbol_or_lines: "createNotFoundException() — is a smaller service locator that"
    branch: "8.0"
    verified_at: "2026-09-30"
---

## Objectif

Recevoir un petit container restreint quand injecter tout est absurde, sans
retomber dans la récupération de services. Le cas particulier
d'`AbstractController` est traité dans son propre item (lot Controllers).

## Le cas légitime

Une class qui dispatche vers dix handlers n'en utilise **qu'un** par appel.
Les injecter tous les construirait tous, pour rien.

Un **service locator** est un container minuscule, limité aux services déclarés,
qui ne construit chacun qu'au moment où on le demande.

```php
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

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

`getSubscribedServices()` est **statique** — c'est la signature de
`ServiceSubscriberInterface` (8.0). La key `'logger'` donne un nom local au
service typé `LoggerInterface`.

## Ce que l'exécution montre

Exécuté dans une application FrameworkBundle 8.0.15 :

| Situation | Résultat |
|---|---|
| après construction du bus | aucun handler construit |
| `get(FooHandler::class)` | construit à ce moment, et **même instance** que celle du container |
| `?App\P12\Missing` absent | `has()` rend `false`, rien n'échoue |
| le même, sans `?` | la compilation échoue : « has a dependency on a non-existent service "App\P12\Missing" » |
| `get()` d'un service non déclaré | `ServiceNotFoundException` : « even though it exists in the app's container, the container inside "App\P12\Bus" is a smaller service locator that only knows about the "App\P12\FooHandler" and "logger" services » |

Le préfixe `?` décide donc du moment de l'échec : sans lui, un service manquant
bloque la compilation ; avec lui, c'est à la class de tester `has()`.

## Ce que cela n'est pas

Ce n'est **pas** l'injection du container applicatif. Le locator ne contient
**que** ce que la class a déclaré — le message ci-dessus le dit en toutes
lettres : ses dependencies restent lisibles et vérifiables à la compilation, ce
qui est précisément ce que la récupération de services fait perdre.

## La forme courte

`#[AutowireLocator]` évite d'implémenter l'interface :

```php
public function __construct(
    #[AutowireLocator(['App\Handler\FooHandler', 'App\Handler\BarHandler'])]
    private ContainerInterface $locator,
) {}
```

Un locator peut aussi être construit à partir d'un **tag** —
`#[AutowireLocator('app.handler')]` —, ce qui donne une table de correspondance
paresseuse : exécuté (voir *Tags*), les keys sont l'`index` de chaque service
quand il en a un, son identifier sinon.

## Hériter

Quand une class parente implémente déjà `ServiceSubscriberInterface`, la
sous-classe doit **fusionner** :

```php
return array_merge(parent::getSubscribedServices(), ['…']);
```

Oublier `parent::` retire silencieusement les services du parent. Exécuté : un
`ChildBus` qui ne déclare que `BarHandler` a un locator où
`has(FooHandler::class)` vaut `false` — aucune erreur avant l'usage.

## Pièges d'examen

**`getSubscribedServices()` est statique.**

**Le locator n'est pas le container** : il est restreint à la liste déclarée, et
le dit dans son message d'erreur.

**Les services sont construits à la demande** — et partagés avec le container.

**Sans `?`, un service manquant bloque la compilation** ; avec `?`, `has()`
rend `false`.

**En inheritance, il faut fusionner avec `parent::`.**

## Points clés

- Un locator est un container restreint et paresseux, pour « beaucoup de
  dependencies, une seule utilisée ».
- `ServiceSubscriberInterface` + `getSubscribedServices()` statique, ou
  `#[AutowireLocator]`.
- `?` rend un service facultatif.
- Les dependencies restent déclarées, donc vérifiables : ce n'est pas de la
  récupération de services.

## Sources officielles

- [Service Subscribers & Locators](https://github.com/symfony/symfony-docs/blob/8.0/service_container/service_subscribers_locators.rst)
- [Service Contracts, `ServiceSubscriberInterface`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Contracts/Service/ServiceSubscriberInterface.php)
- [DependencyInjection 8.0, `ServiceLocator`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/ServiceLocator.php)
