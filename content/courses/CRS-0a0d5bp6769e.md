---
id: CRS-0a0d5bp6769e
official_item: OIT-gkhcbtygef69
title: "Service locators"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/service_container/service_subscribers_locators.rst"
    branch: "8.0"
    symbol_or_lines: "ServiceSubscriberInterface, getSubscribedServices, AutowireLocator"
    verified_at: "2026-09-01"
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

`getSubscribedServices()` est **statique**. Le préfixe `?` rend un service
facultatif : absent, `has()` retourne `false` au lieu d'échouer à la compilation.

## Ce que cela n'est pas

Ce n'est **pas** l'injection du conteneur applicatif. Le locator ne contient
**que** ce que la classe a déclaré : ses dépendances restent lisibles et
vérifiables à la compilation, ce qui est précisément ce que la récupération de
services fait perdre.

## La forme courte

`#[AutowireLocator]` évite d'implémenter l'interface :

```php
public function __construct(
    #[AutowireLocator(['App\Handler\FooHandler', 'App\Handler\BarHandler'])]
    private ContainerInterface $locator,
) {}
```

Un locator peut aussi être construit à partir d'un **tag**, ce qui donne une
table de correspondance paresseuse indexée par `index` — voir *Tags*.

## Hériter

Quand une classe parente implémente déjà `ServiceSubscriberInterface`, la
sous-classe doit **fusionner** :

```php
return array_merge(parent::getSubscribedServices(), ['…']);
```

Oublier `parent::` retire silencieusement les services du parent.

## Pièges d'examen

**`getSubscribedServices()` est statique.**

**Le locator n'est pas le conteneur** : il est restreint à la liste déclarée.

**Les services sont construits à la demande** — c'est tout l'intérêt.

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

- [Service Subscribers & Locators](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/service_container/service_subscribers_locators.rst)
