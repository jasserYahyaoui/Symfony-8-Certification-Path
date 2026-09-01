---
id: CRS-exs5dvtqa1as
official_item: OIT-qj4xfkhwdrx7
title: "Semantic configuration"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/bundles/configuration.rst"
    branch: "8.0"
    symbol_or_lines: "AbstractBundle::configure, loadExtension, ConfigurationInterface"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/bundles/extension.rst"
    branch: "8.0"
    verified_at: "2026-09-01"
---

## Objectif

Comprendre ce qui transforme `framework: { … }` en services, et comment un bundle
expose sa propre clé de configuration.

## Le problème résolu

Sans extension, configurer un bundle voudrait dire écrire ses définitions de
services à la main. La **configuration sémantique** offre à la place un langage :
quelques clés lisibles, validées, que le bundle traduit lui-même en services.

C'est pourquoi `framework.csrf_protection: true` suffit ; personne n'écrit la
définition du gestionnaire de jetons.

## Les deux moitiés

| Classe | Rôle |
|---|---|
| `ConfigurationInterface` | **déclare** l'arbre : clés admises, types, valeurs par défaut, validation |
| l'extension | **consomme** la configuration validée et enregistre les services |

L'arbre se décrit avec un `TreeBuilder` :

```php
public function getConfigTreeBuilder(): TreeBuilder
{
    $tree = new TreeBuilder('acme_social');
    $tree->getRootNode()
        ->children()
            ->integerNode('timeout')->defaultValue(30)->min(1)->end()
            ->scalarNode('client_id')->isRequired()->end()
        ->end();

    return $tree;
}
```

Une clé absente de l'arbre provoque une **erreur au chargement**, pas un silence.
C'est le principal intérêt : la configuration est validée avant de servir.

## La forme moderne

Un bundle étendant `AbstractBundle` porte les deux moitiés :

```php
class AcmeSocialBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void { /* l'arbre */ }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->services()
            ->set('acme_social.client', Client::class)
            ->arg('$timeout', $config['timeout']);
    }
}
```

`configure()` et `loadExtension()` ne sont appelées **qu'à la compilation** du
conteneur, jamais à l'exécution.

Le nom de la clé racine découle du nom du bundle : `AcmeSocialBundle` donne
`acme_social`.

## Configurer un autre bundle

`prependExtensionConfig()` — ou `prependExtension()` sur `AbstractBundle` —
permet à un bundle d'**ajouter de la configuration à un autre** avant que
celui-ci ne la traite. C'est ainsi qu'un bundle enregistre son chemin de
gabarits dans Twig sans que l'utilisateur l'écrive.

Ce qui est *prepend* est placé **avant** la configuration de l'application, donc
l'application garde le dernier mot.

## Pièges d'examen

**Une clé inconnue est une erreur**, pas une valeur ignorée.

**`loadExtension()` s'exécute à la compilation** : elle ne voit ni requête ni
état d'exécution.

**Le *prepend* passe avant l'application**, qui peut donc toujours surcharger.

## Points clés

- `ConfigurationInterface` + `TreeBuilder` déclarent et valident l'arbre ;
  l'extension le traduit en services.
- `AbstractBundle::configure()` et `loadExtension()`, appelées à la compilation.
- La clé racine dérive du nom du bundle.
- `prependExtensionConfig()` configure un autre bundle, sans priver l'application
  du dernier mot.

## Sources officielles

- [How to Create Friendly Configuration for a Bundle](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/bundles/configuration.rst)
- [How to Load Service Configuration inside a Bundle](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/bundles/extension.rst)
