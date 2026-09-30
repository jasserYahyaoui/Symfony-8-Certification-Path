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

Recevoir un petit conteneur restreint quand injecter tout est absurde, sans
retomber dans la récupération de services. Le cas particulier
d'`AbstractController` est traité dans son propre item (lot Controllers).

## Le cas légitime

Une classe qui dispatche vers dix gestionnaires n'en utilise **qu'un** par appel.
Les injecter tous les construirait tous, pour rien.

Un **service locator** est un conteneur minuscule, limité aux services déclarés,
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
`ServiceSubscriberInterface` (8.0). La clé `'logger'` donne un nom local au
service typé `LoggerInterface`.

## Ce que l'exécution montre

Exécuté dans une application FrameworkBundle 8.0.15 :

| Situation | Résultat |
|---|---|
| après construction du bus | aucun gestionnaire construit |
| `get(FooHandler::class)` | construit à ce moment, et **même instance** que celle du conteneur |
| `?App\P12\Missing` absent | `has()` rend `false`, rien n'échoue |
| le même, sans `?` | la compilation échoue : « has a dependency on a non-existent service "App\P12\Missing" » |
| `get()` d'un service non déclaré | `ServiceNotFoundException` : « even though it exists in the app's container, the container inside "App\P12\Bus" is a smaller service locator that only knows about the "App\P12\FooHandler" and "logger" services » |

Le préfixe `?` décide donc du moment de l'échec : sans lui, un service manquant
bloque la compilation ; avec lui, c'est à la classe de tester `has()`.

## Ce que cela n'est pas

Ce n'est **pas** l'injection du conteneur applicatif. Le locator ne contient
**que** ce que la classe a déclaré — le message ci-dessus le dit en toutes
lettres : ses dépendances restent lisibles et vérifiables à la compilation, ce
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
paresseuse : exécuté (voir *Tags*), les clés sont l'`index` de chaque service
quand il en a un, son identifiant sinon.

## Hériter

Quand une classe parente implémente déjà `ServiceSubscriberInterface`, la
sous-classe doit **fusionner** :

```php
return array_merge(parent::getSubscribedServices(), ['…']);
```

Oublier `parent::` retire silencieusement les services du parent. Exécuté : un
`ChildBus` qui ne déclare que `BarHandler` a un locator où
`has(FooHandler::class)` vaut `false` — aucune erreur avant l'usage.

## Pièges d'examen

**`getSubscribedServices()` est statique.**

**Le locator n'est pas le conteneur** : il est restreint à la liste déclarée, et
le dit dans son message d'erreur.

**Les services sont construits à la demande** — et partagés avec le conteneur.

**Sans `?`, un service manquant bloque la compilation** ; avec `?`, `has()`
rend `false`.

**En héritage, il faut fusionner avec `parent::`.**

## Points clés

- Un locator est un conteneur restreint et paresseux, pour « beaucoup de
  dépendances, une seule utilisée ».
- `ServiceSubscriberInterface` + `getSubscribedServices()` statique, ou
  `#[AutowireLocator]`.
- `?` rend un service facultatif.
- Les dépendances restent déclarées, donc vérifiables : ce n'est pas de la
  récupération de services.

## Sources officielles

- [Service Subscribers & Locators](https://github.com/symfony/symfony-docs/blob/8.0/service_container/service_subscribers_locators.rst)
- [Service Contracts, `ServiceSubscriberInterface`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Contracts/Service/ServiceSubscriberInterface.php)
- [DependencyInjection 8.0, `ServiceLocator`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/ServiceLocator.php)
