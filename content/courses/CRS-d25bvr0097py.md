---
id: CRS-d25bvr0097py
official_item: OIT-3y0b9gxyandm
title: "Compiler passes"
content_level: DEEP
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-26"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/service_container/compiler_passes.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/service_container/compiler_passes.rst"
    branch: "8.0"
    symbol_or_lines: "CompilerPassInterface, addCompilerPass, findTaggedServiceIds"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/DependencyInjection/Compiler/PassConfig.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Compiler/PassConfig.php"
    branch: "8.0"
    symbol_or_lines: "PassConfig::TYPE_*, addPass"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpKernel/Kernel.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/Kernel.php"
    symbol_or_lines: "addCompilerPass($this, PassConfig::TYPE_BEFORE_OPTIMIZATION, -10000)"
    branch: "8.0"
    verified_at: "2026-09-29"
outcome_sections:
  OUT-cqnfv3m8fn8q: "Ce qu'une passe est"
  OUT-gx5971krje0a: "Où l'enregistrer"
  OUT-avkzmraeybbg: "Les cinq étapes"
  OUT-5d052fcbnf0g: "Ce que l'étape rend visible"
  OUT-mpb2peyp9993: "Passe ou configuration"
---

## Objectif

Modifier le container pendant sa compilation : collecter des services tagués,
supprimer une definition, réécrire un argument. Et surtout savoir **quand** une
passe s'exécute, parce que l'étape choisie décide de ce qu'elle peut encore voir.

## Prérequis

Le container et sa compilation, et les tags.

## Ce qu'une passe est

Une class qui implémente `CompilerPassInterface`, donc une seule method :

```php
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class HandlerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(HandlerChain::class)) {
            return;
        }

        $chain = $container->findDefinition(HandlerChain::class);

        foreach ($container->findTaggedServiceIds('app.handler') as $id => $tags) {
            $chain->addMethodCall('addHandler', [new Reference($id)]);
        }
    }
}
```

Elle reçoit le `ContainerBuilder` — les **definitions**, pas les objects. Rien
n'est instancié : on manipule des descriptions. Exécuté avec
`symfony/dependency-injection` 8.0.15 : dans une passe, `getDefinition('priv')`
rend un object `Definition`, et `initialized('priv')` vaut `false`.

`findTaggedServiceIds()` retourne un array `identifier => liste d'attributes
de tag`, la liste parce qu'un service peut porter le même tag plusieurs fois.
Exécuté, un service tagué deux fois : `{"x":[{"a":1},{"a":2}]}`.

## Où l'enregistrer

Deux endroits, selon qui la possède :

```php
// un bundle : méthode build()
public function build(ContainerBuilder $container): void
{
    parent::build($container);
    $container->addCompilerPass(new HandlerPass());
}
```

```php
// l'application : le Kernel implémente lui-même l'interface
class Kernel extends BaseKernel implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void { /* … */ }
}
```

Le kernel n'est pas enregistré à la priority par défaut : `Kernel` l'ajoute en
`TYPE_BEFORE_OPTIMIZATION` avec la priority **-10000** (relu dans
`HttpKernel\Kernel`, 8.0.15), donc après les passes des bundles de la même étape.

La convention est de placer les passes d'un bundle dans
`DependencyInjection/Compiler/` et de les suffixer `Pass`.

## Les cinq étapes

`addCompilerPass()` prend un **type** et une **priority**. Les types sont les
constantes de `PassConfig`, dans cet ordre d'exécution :

| Constante | Moment | Passes du component, relues en 8.0.15 |
|---|---|---|
| `TYPE_BEFORE_OPTIMIZATION` | **le défaut** — tout est encore là | autoconfiguration, `instanceof`, à la priority 100 |
| `TYPE_OPTIMIZE` | résolution | `AutowirePass`, parameters, bindings, alias, decorators |
| `TYPE_BEFORE_REMOVING` | juste avant le nettoyage | aucune par défaut |
| `TYPE_REMOVE` | suppression | `RemoveUnusedDefinitionsPass`, inlining |
| `TYPE_AFTER_REMOVING` | après le nettoyage | paths chauds, préchargement |

Exécuté, sept passes enregistrées dans le désordre s'exécutent dans cet ordre :
priority 10, 0 puis -5 de l'étape par défaut, puis `optimize`, `beforeRemoving`,
`remove`, `after`. **La priority la plus haute passe en premier** ; elle vaut `0`
par défaut.

## Ce que l'étape rend visible

Le choix n'est pas décoratif. Quatre exécutions le montrent :

| Passe | Résultat |
|---|---|
| ajoute une référence à un service privé en `TYPE_BEFORE_REMOVING` | le service est conservé et injecté |
| la même en `TYPE_AFTER_REMOVING` | `compile()` et le vidage en PHP **passent** ; l'instanciation du consommateur lève `ServiceNotFoundException` — « The "priv" service or alias has been removed or inlined when the container was compiled » |
| lit un tag posé par `registerForAutoconfiguration()`, priority 0 | le service tagué est vu |
| la même, priority 200 | **rien** : l'autoconfiguration, à 100, n'a pas encore eu lieu |

Une passe qui **ajoute** une référence à un service privé doit donc s'exécuter
avant `TYPE_REMOVE` ; une passe qui veut observer le container final se place en
`TYPE_AFTER_REMOVING` ; et une priority élevée dans l'étape par défaut ne voit
pas encore les tags d'autoconfiguration.

## Passe ou configuration

La configuration décrit **ses propres** services. Une passe intervient sur ceux
des **autres** : collecter tous les services portant un tag, retirer une
definition posée par un bundle tiers, changer un argument que l'on ne contrôle
pas. Si la configuration suffit, elle est préférable — elle est lisible et ne
s'exécute pas à la compilation.

## Pièges d'examen

- Une passe manipule des **definitions**, jamais des instances.
- Le type par défaut est `TYPE_BEFORE_OPTIMIZATION`, priority `0`.
- Câbler un service privé **après** `TYPE_REMOVE` échoue : il a déjà disparu —
  et l'erreur n'arrive qu'à l'exécution, pas à la compilation.
- Priority haute = **plus tôt**, à type égal — au-delà de 100, avant
  l'autoconfiguration.
- La passe du kernel tourne à -10000, après les passes de bundle de son étape.
- Une passe s'exécute **une fois**, à la compilation — jamais par Request.
- `findTaggedServiceIds()` retourne une **liste** d'attributes par service, pas un
  seul jeu.

## Points clés

- `CompilerPassInterface::process(ContainerBuilder)` : une method, des definitions.
- Enregistrée par `Bundle::build()` ou par un `Kernel` qui implémente l'interface.
- Cinq étapes de `PassConfig` ; le défaut est `TYPE_BEFORE_OPTIMIZATION`.
- L'étape décide de ce qui existe encore — `TYPE_REMOVE` supprime les services
  privés non référencés.
- La configuration pour ses propres services ; la passe pour ceux des autres.

## Sources officielles

- [How to Work with Compiler Passes](https://github.com/symfony/symfony-docs/blob/8.0/service_container/compiler_passes.rst)
- [`PassConfig`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Compiler/PassConfig.php)
- [HttpKernel 8.0, `Kernel`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/Kernel.php)
