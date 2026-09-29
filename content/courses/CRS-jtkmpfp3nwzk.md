---
id: CRS-jtkmpfp3nwzk
official_item: OIT-dy7108w6bf4z
title: "Built-in services"
content_level: MINIMAL
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-29"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/service_container.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/service_container.rst"
    branch: "8.0"
    symbol_or_lines: "Fetching and using Services, debug:autowiring"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/quick_tour/flex_recipes.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/quick_tour/flex_recipes.rst"
    symbol_or_lines: "In addition to automatically enabling the feature in config/bundles.php"
    branch: "8.0"
    verified_at: "2026-09-29"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/Command/DebugAutowiringCommand.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Command/DebugAutowiringCommand.php"
    symbol_or_lines: "argument search; option all (Show also services that are not aliased); No autowirable classes or interfaces found matching"
    branch: "8.0"
    verified_at: "2026-09-29"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/Resources/config/services.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Resources/config/services.php"
    symbol_or_lines: "alias(RequestStack::class, request_stack); alias(Filesystem::class, filesystem)"
    branch: "8.0"
    verified_at: "2026-09-29"
---

## Objectif

Savoir d'où viennent les services que l'on n'a pas déclarés, et comment trouver
celui dont on a besoin — sans apprendre de liste par cœur.

## D'où ils viennent

Chaque bundle installé enregistre ses services dans le conteneur. FrameworkBundle
en apporte le gros : routeur, dispatcher d'événements, HttpKernel, sérialiseur,
validateur, client HTTP, cache, système de fichiers. TwigBundle ajoute Twig,
SecurityBundle la sécurité, et ainsi de suite.

C'est pourquoi `composer require` suffit : la recette Flex active le bundle dans
`config/bundles.php`, le bundle enregistre ses services, et ils deviennent
injectables.

## Comment les trouver

Il n'y a rien à mémoriser ; il y a une commande :

```bash
php bin/console debug:autowiring          # ce qui s'injecte par type-hint
php bin/console debug:autowiring log      # filtré
php bin/console debug:autowiring --all    # + services non aliasés
php bin/console debug:container           # tous les identifiants
```

`debug:autowiring` est la bonne : elle liste les **types** utilisables comme
type-hint, ce qui est exactement la question qu'on se pose en écrivant un
constructeur. Chaque ligne donne le type et le service visé :
`Psr\Log\LoggerInterface - alias:logger`.

Son seul argument est un filtre de recherche partielle ; sans résultat, la
commande répond « No autowirable classes or interfaces found matching … ».
`--all` ajoute les services enregistrés sous leur nom de classe sans alias, comme
`RedirectController`.

## Quelques types courants

`RouterInterface`, `UrlGeneratorInterface`, `EventDispatcherInterface`,
`ValidatorInterface`, `SerializerInterface`, `HttpClientInterface`,
`LoggerInterface`, `TranslatorInterface`, `Environment` (Twig),
`ParameterBagInterface`, `RequestStack`, `CacheInterface`, `Filesystem`.

Tous figurent dans la sortie de `debug:autowiring` d'une application
FrameworkBundle + TwigBundle 8.0.15, exécutée.

Le point commun se retient mieux que la liste : **on injecte le type que le
bundle a déclaré comme alias**. C'est le plus souvent une interface, mais pas
toujours : `RequestStack`, `Filesystem` et `Twig\Environment` sont des
**classes**, aliasées telles quelles (`->alias(Filesystem::class,
'filesystem')` dans FrameworkBundle).

## Ce que l'exécution montre

| Constructeur typé avec | Résultat |
|---|---|
| `Psr\Log\LoggerInterface` | injecté : alias vers `logger` |
| `Symfony\Component\HttpKernel\Log\Logger`, la classe de `logger` | échec : « no such service exists. Try changing the type-hint to "Psr\Log\LoggerInterface" instead. » |
| `Filesystem`, `RequestStack`, `Twig\Environment` | injectés |
| `Twig\Environment`, sans TwigBundle | échec : « no such service exists » |

L'erreur d'autowiring suggère elle-même le bon type quand un alias existe.

## Pièges d'examen

**`debug:container` et `debug:autowiring` ne répondent pas à la même
question.** Le premier liste **tous** les identifiants du conteneur, le second
les types utilisables comme type-hint. Apparaître dans `debug:container` ne rend
pas un service injectable par son type : `logger` y figure, sa classe concrète
ne s'injecte pas.

**On type-hinte le type aliasé, pas la classe du service.** Viser la classe
concrète de `logger` échoue ; `LoggerInterface` fonctionne.

**« Toujours une interface » est faux** : `RequestStack` ou `Filesystem`
s'injectent par leur classe, parce que c'est elle que le bundle a aliasée.

**Un service intégré vient d'un bundle, pas du framework en bloc.** Retirer le
bundle retire ses services : sans TwigBundle, pas d'`Environment` à injecter.

## Points clés

- Les services intégrés viennent des bundles installés, FrameworkBundle en tête.
- `debug:autowiring` répond à « que puis-je type-hinter ? » ; `debug:container`
  liste les identifiants.
- On injecte le type aliasé — le plus souvent une interface, parfois une classe.

## Sources officielles

- [Service Container, « Fetching and using Services »](https://github.com/symfony/symfony-docs/blob/8.0/service_container.rst)
- [Flex recipes, activation du bundle](https://github.com/symfony/symfony-docs/blob/8.0/quick_tour/flex_recipes.rst)
- [FrameworkBundle 8.0, `DebugAutowiringCommand`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Command/DebugAutowiringCommand.php)
- [FrameworkBundle 8.0, `Resources/config/services.php`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Resources/config/services.php)
