---
id: CRS-d25bvr0097py
official_item: OIT-3y0b9gxyandm
title: "Compiler passes"
content_level: DEEP
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/service_container/compiler_passes.rst"
    branch: "8.0"
    symbol_or_lines: "CompilerPassInterface, addCompilerPass, findTaggedServiceIds"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/DependencyInjection/Compiler/PassConfig.php"
    branch: "8.0"
    symbol_or_lines: "PassConfig::TYPE_*, addPass"
    verified_at: "2026-09-01"
---

## Objectif

Modifier le conteneur pendant sa compilation : collecter des services tagués,
supprimer une définition, réécrire un argument. Et surtout savoir **quand** une
passe s'exécute, parce que l'étape choisie décide de ce qu'elle peut encore voir.

## Prérequis

Le conteneur et sa compilation, et les tags.

## Ce qu'une passe est

Une classe qui implémente `CompilerPassInterface`, donc une seule méthode :

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

Elle reçoit le `ContainerBuilder` — les **définitions**, pas les objets. Rien
n'est instancié : on manipule des descriptions.

`findTaggedServiceIds()` retourne un tableau `identifiant => liste d'attributs
de tag`, la liste parce qu'un service peut porter le même tag plusieurs fois.

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

La convention est de placer les passes d'un bundle dans
`DependencyInjection/Compiler/` et de les suffixer `Pass`.

## Les cinq étapes

`addCompilerPass()` prend un **type** et une **priorité**. Les types sont les
constantes de `PassConfig`, dans cet ordre d'exécution :

| Constante | Moment |
|---|---|
| `TYPE_BEFORE_OPTIMIZATION` | **le défaut** — tout est encore là |
| `TYPE_OPTIMIZE` | résolution : alias, paramètres, autowiring |
| `TYPE_BEFORE_REMOVING` | juste avant le nettoyage |
| `TYPE_REMOVE` | suppression des services privés inutilisés |
| `TYPE_AFTER_REMOVING` | après le nettoyage |

Le choix n'est pas décoratif. Une passe qui **ajoute** une référence à un service
privé doit s'exécuter **avant** `TYPE_REMOVE`, sinon le service qu'elle vient de
câbler aura déjà été supprimé comme inutilisé. Inversement, une passe qui veut
observer le conteneur final doit se placer en `TYPE_AFTER_REMOVING`.

À type égal, la **priorité la plus haute s'exécute en premier** ; elle vaut `0`
par défaut.

## Passe ou configuration

La configuration décrit **ses propres** services. Une passe intervient sur ceux
des **autres** : collecter tous les services portant un tag, retirer une
définition posée par un bundle tiers, changer un argument que l'on ne contrôle
pas. Si la configuration suffit, elle est préférable — elle est lisible et ne
s'exécute pas à la compilation.

## Pièges d'examen

- Une passe manipule des **définitions**, jamais des instances.
- Le type par défaut est `TYPE_BEFORE_OPTIMIZATION`.
- Câbler un service privé **après** `TYPE_REMOVE` échoue : il a déjà disparu.
- Priorité haute = **plus tôt**, à type égal.
- Une passe s'exécute **une fois**, à la compilation — jamais par requête.
- `findTaggedServiceIds()` retourne une **liste** d'attributs par service, pas un
  seul jeu.

## Points clés

- `CompilerPassInterface::process(ContainerBuilder)` : une méthode, des définitions.
- Enregistrée par `Bundle::build()` ou par un `Kernel` qui implémente l'interface.
- Cinq étapes de `PassConfig` ; le défaut est `TYPE_BEFORE_OPTIMIZATION`.
- L'étape décide de ce qui existe encore — `TYPE_REMOVE` supprime les services
  privés non référencés.
- La configuration pour ses propres services ; la passe pour ceux des autres.

## Sources officielles

- [How to Work with Compiler Passes](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/service_container/compiler_passes.rst)
- [`PassConfig`, branche 8.0](https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/DependencyInjection/Compiler/PassConfig.php)
