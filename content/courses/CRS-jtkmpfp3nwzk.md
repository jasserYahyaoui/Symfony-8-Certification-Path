---
id: CRS-jtkmpfp3nwzk
official_item: OIT-dy7108w6bf4z
title: "Built-in services"
content_level: MINIMAL
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/service_container.rst"
    branch: "8.0"
    symbol_or_lines: "Fetching and using Services, debug:autowiring"
    verified_at: "2026-09-01"
---

## Objectif

Savoir d'où viennent les services que l'on n'a pas déclarés, et comment trouver
celui dont on a besoin — sans apprendre de liste par cœur.

## D'où ils viennent

Chaque bundle installé enregistre ses services dans le conteneur. FrameworkBundle
en apporte le gros : routeur, dispatcher d'événements, HttpKernel, sérialiseur,
validateur, client HTTP, cache, système de fichiers. TwigBundle ajoute Twig,
SecurityBundle la sécurité, et ainsi de suite.

C'est pourquoi `composer require` suffit : la recette active le bundle, le bundle
enregistre ses services, et ils deviennent injectables.

## Comment les trouver

Il n'y a rien à mémoriser ; il y a une commande :

```bash
php bin/console debug:autowiring          # ce qui s'injecte par type-hint
php bin/console debug:autowiring log      # filtré
php bin/console debug:container           # tous les identifiants
```

`debug:autowiring` est la bonne : elle liste les **types** utilisables comme
type-hint, ce qui est exactement la question qu'on se pose en écrivant un
constructeur.

## Quelques types courants

`RouterInterface`, `UrlGeneratorInterface`, `EventDispatcherInterface`,
`ValidatorInterface`, `SerializerInterface`, `HttpClientInterface`,
`LoggerInterface`, `TranslatorInterface`, `Environment` (Twig),
`ParameterBagInterface`, `RequestStack`, `CacheInterface`, `Filesystem`.

Le point commun se retient mieux que la liste : **on injecte une interface**,
pas une implémentation. C'est l'alias déclaré par le bundle qui fait le lien.

## Points clés

- Les services intégrés viennent des bundles installés, FrameworkBundle en tête.
- `debug:autowiring` répond à « que puis-je type-hinter ? » ; `debug:container`
  liste les identifiants.
- La convention est d'injecter une **interface**.

## Sources officielles

- [Service Container, « Fetching and using Services »](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/service_container.rst)
