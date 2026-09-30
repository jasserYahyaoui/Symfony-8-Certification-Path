---
id: CRS-exs5dvtqa1as
official_item: OIT-qj4xfkhwdrx7
title: "Semantic configuration"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-29"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/bundles/configuration.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/bundles/configuration.rst"
    branch: "8.0"
    symbol_or_lines: "AbstractBundle::configure, loadExtension, ConfigurationInterface"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/bundles/extension.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/bundles/extension.rst"
    symbol_or_lines: '"How to Load Service Configuration inside a Bundle" — the two ways: loading services in the main bundle class, or "Create an extension class to load the service configuration files"'
    branch: "8.0"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/bundles/prepend_extension.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/bundles/prepend_extension.rst"
    symbol_or_lines: "config/* files override prepended settings; the bundle registered first takes priority"
    branch: "8.0"
    verified_at: "2026-09-29"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/TwigBundle/DependencyInjection/TwigExtension.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/TwigBundle/DependencyInjection/TwigExtension.php"
    symbol_or_lines: "getBundleTemplatePaths(), normalizeBundleName()"
    branch: "8.0"
    verified_at: "2026-09-29"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Config/Definition/ArrayNode.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Config/Definition/ArrayNode.php"
    symbol_or_lines: "Unrecognized option %s under %s"
    branch: "8.0"
    verified_at: "2026-09-29"
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

## Ce que l'exécution montre

Un `AcmeSocialBundle` portant cet arbre, chargé dans une application
FrameworkBundle 8.0.15 :

| Configuration de l'application | Résultat |
|---|---|
| `timout: 5` | `InvalidConfigurationException` : « Unrecognized option "timout" under "acme_social". Did you mean "timeout"? » |
| `timeout: 0` | « The value 0 is too small for path "acme_social.timeout". Should be greater than or equal to 1 » |
| aucune, `client_id` non fourni | « The child config "client_id" under "acme_social" must be configured. » |
| `client_id` fourni, rien d'autre | `timeout` vaut `30` |

La troisième ligne est le piège : l'extension traite une configuration vide même
quand l'application ne mentionne pas le bundle, donc un nœud `isRequired()` sans
valeur fait échouer la compilation.

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
conteneur, jamais à l'exécution. Exécuté : au premier démarrage, les deux
méthodes sont appelées ; au second, cache chaud, aucune.

Le nom de la clé racine découle du nom du bundle : `AcmeSocialBundle` donne
`acme_social` — exécuté, `getContainerExtension()->getAlias()` le confirme.

## Configurer un autre bundle

`prependExtensionConfig()` — ou `prependExtension()` sur `AbstractBundle` —
permet à un bundle d'**ajouter de la configuration à un autre** avant que
celui-ci ne la traite :

```php
public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
{
    $container->extension('framework', ['cache' => ['prefix_seed' => 'foo/bar']], prepend: true);
}
```

Ce qui est *prepend* est placé **avant** la configuration de l'application, donc
l'application garde le dernier mot. Exécuté : un `label` prepend est remplacé par
celui de `config/packages/acme_social.yaml`.

Entre deux bundles qui prepend la même clé, c'est l'inverse de l'intuition :
**le premier enregistré l'emporte** (`prepend_extension.rst`). Exécuté, avec
`FirstBundle` puis `SecondBundle` : `from-first`.

Les gabarits d'un bundle, eux, n'ont pas besoin de prepend : TwigBundle
enregistre de lui-même le dossier `templates/` ou `Resources/views/` de chaque
bundle, sous l'espace de son nom privé du suffixe `Bundle` — `@AcmeSocial` —
(`TwigExtension::getBundleTemplatePaths()`).

## Pièges d'examen

**Une clé inconnue est une erreur**, pas une valeur ignorée — avec suggestion.

**`loadExtension()` s'exécute à la compilation** : elle ne voit ni requête ni
état d'exécution.

**Le *prepend* passe avant l'application**, qui peut donc toujours surcharger ;
entre bundles, le premier enregistré gagne.

**`isRequired()` s'applique même si l'application ignore le bundle.**

## Points clés

- `ConfigurationInterface` + `TreeBuilder` déclarent et valident l'arbre ;
  l'extension le traduit en services.
- `AbstractBundle::configure()` et `loadExtension()`, appelées à la compilation.
- La clé racine dérive du nom du bundle.
- `prependExtensionConfig()` configure un autre bundle, sans priver l'application
  du dernier mot.

## Sources officielles

- [How to Create Friendly Configuration for a Bundle](https://github.com/symfony/symfony-docs/blob/8.0/bundles/configuration.rst)
- [How to Load Service Configuration inside a Bundle](https://github.com/symfony/symfony-docs/blob/8.0/bundles/extension.rst)
- [How to Simplify Configuration of Multiple Bundles](https://github.com/symfony/symfony-docs/blob/8.0/bundles/prepend_extension.rst)
- [TwigBundle 8.0, `TwigExtension`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/TwigBundle/DependencyInjection/TwigExtension.php)
