---
id: CRS-d25bvr0097py
official_item: OIT-3y0b9gxyandm
title: "Compiler passes"
content_level: DEEP
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-28"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/service_container/compiler_passes.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/service_container/compiler_passes.rst"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    symbol_or_lines: "Kernel implementing CompilerPassInterface, Kernel::build(), Bundle::build(), tag naming convention"
    verified_at: "2026-10-28"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/DependencyInjection/Compiler/PassConfig.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Compiler/PassConfig.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "__construct() — TYPE_* stages, autoconfiguration at 100, ExtensionCompilerPass at -1000"
    verified_at: "2026-10-28"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpKernel/Kernel.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/Kernel.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "addCompilerPass($this, PassConfig::TYPE_BEFORE_OPTIMIZATION, -10000)"
    verified_at: "2026-10-28"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/DependencyInjection/Compiler/MergeExtensionConfigurationPass.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Compiler/MergeExtensionConfigurationPass.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "process() — each extension loads into a temporary container; the application's definitions are added back last"
    verified_at: "2026-10-28"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/DependencyInjection/Compiler/PriorityTaggedServiceTrait.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Compiler/PriorityTaggedServiceTrait.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "findAndSortTaggedServices()"
    verified_at: "2026-10-28"
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

## L'essentiel

Une passe modifie le container **pendant sa compilation** : elle manipule des
**definitions**, jamais des objects. Elle s'enregistre dans un bundle ou dans
le kernel, à une **étape** et une **priority** — et l'étape décide de ce
qu'elle voit encore. Elle ne s'exécute qu'une fois, à la compilation.

## Le schéma

```text
charger      services.yaml, puis load() de chaque extension, isolée ; l'application ré-appliquée en dernier
1. BEFORE_OPTIMIZATION (défaut)   100 autoconfiguration · 0 tes passes · -1000 extensions-passes · -10000 Kernel
2. OPTIMIZE                       autowiring, alias, decorators, parameters résolus
3. BEFORE_REMOVING                vide par défaut
4. REMOVE                         privés non référencés supprimés, inlining
5. AFTER_REMOVING                 le container final → vidage en PHP ; plus aucune passe ensuite
```

## Le fil rouge : une chaîne de handlers

```php
class HandlerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(HandlerChain::class)) {   // sinon : ServiceNotFoundException
            return;
        }
        $chain = $container->findDefinition(HandlerChain::class);
        foreach ($container->findTaggedServiceIds('app.handler') as $id => $tags) {
            $chain->addMethodCall('addHandler', [new Reference($id)]);
        }
    }
}
```

La passe ne construit aucun handler : elle **décrit** les appels que le
container fera plus tard.

## Ce qu'une passe est

Une class `CompilerPassInterface`, une method `process(ContainerBuilder)`.
Dans une passe, `getDefinition('priv')` rend une `Definition`, et
`initialized('priv')` vaut `false`.

| Method | Ce qu'elle fait |
|---|---|
| `has($id)` / `hasDefinition($id)` | definition **ou** alias / definition seulement |
| `getDefinition($id)` | la definition ; sur un **alias**, `ServiceNotFoundException` |
| `findDefinition($id)` | la definition, **en suivant les alias** |
| `findTaggedServiceIds($tag)` | `id => liste d'attributes`, dans l'ordre de déclaration, **sans tri** |
| `removeDefinition($id)` | retire le service |
| `$def->setArgument('$region', 'eu')` | fixe un argument, nommé ou par index |
| `$def->replaceArgument()` | exige un argument existant, sinon `OutOfBoundsException` |

Le tri par priority des services tagués se fait avec
`PriorityTaggedServiceTrait::findAndSortTaggedServices()`. Un tag de bundle se
préfixe : `acme_mailer.transport`.

## Où l'enregistrer

| Qui | Écriture | Priority |
|---|---|---|
| un bundle | `build()` : `$container->addCompilerPass(new HandlerPass())` | 0 |
| l'application, une class de passe | `Kernel::build()` : `addCompilerPass()` | 0 |
| une extension qui implémente l'interface | rien : exécutée d'office | -1000 |
| l'application, le kernel lui-même | `Kernel implements CompilerPassInterface` | -10000 |

## Les cinq étapes

`addCompilerPass($pass, $type, $priority)` : le type par défaut est
`PassConfig::TYPE_BEFORE_OPTIMIZATION`, la priority par défaut `0`. À type
égal, **la priority la plus haute passe en premier**.

## Ce que l'étape rend visible

| Passe | Résultat |
|---|---|
| ajoute une référence à un service privé en `TYPE_BEFORE_REMOVING` | conservé et injecté |
| la même en `TYPE_AFTER_REMOVING` | compilation réussie ; `ServiceNotFoundException` à l'**instanciation** du consommateur |
| lit un tag de `registerForAutoconfiguration()`, priority 0 | le tag est vu |
| la même, priority 200 | **rien** : l'autoconfiguration tourne à 100 |

Cache chaud, au boot suivant, aucune passe n'est appelée.

## Passe ou configuration

| Besoin | Outil |
|---|---|
| un argument de **ton** service | configuration (`arguments`, `bind`, `#[Autowire]`) |
| un argument d'un service **d'un bundle** | une passe : `setArgument()` |
| les services d'un tag, injectés ensemble | `#[AutowireIterator]` d'abord, une passe sinon |
| retirer un service d'un bundle tiers | une passe : `removeDefinition()` |

Redéclarer le service d'un bundle sous le même id remplace **toute** sa
definition : `MergeExtensionConfigurationPass` ré-applique celles de
l'application après celles des extensions.

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
- `findTaggedServiceIds()` **ne trie pas** par priority : l'ordre est celui des
  declarations. Le tri se fait avec `PriorityTaggedServiceTrait`.
- `getDefinition()` sur un **alias** lève `ServiceNotFoundException` ;
  `findDefinition()` suit l'alias.
- La method `load()` d'une extension ne voit ni les services des autres bundles
  ni ceux de l'application ; une passe les voit tous.

## Application

- Brancher tous les services `app.handler` sur `HandlerChain` → une passe,
  gardée par `has()`, qui ajoute un `addHandler()` par service tagué.
- Changer un argument du client d'un SDK tiers → `setArgument()` dans une
  passe, pas une redéclaration.

## QCM d'entraînement

**Question 1.** Dans `process()`, une passe appelle
`$container->getDefinition('mailer')`, alors que `mailer` est un **alias** vers
`app.mailer`. Que se passe-t-il ?

- **A.** La method rend la definition de `app.mailer`
- **B.** La method rend un object `Alias`, à convertir avant usage
- **C.** La method rend `null` : un alias n'a pas de definition propre
- **D.** `ServiceNotFoundException`

<details>
<summary>Correction</summary>

**Bonne réponse : D.** `getDefinition()` ne suit pas les alias ;
`findDefinition()` les suit, et A décrit son comportement.

</details>

**Question 2.** Trois services portent le tag `app.rule` avec les priorities
`-5`, `10` et `0`, déclarés dans cet ordre. Dans quel ordre
`findTaggedServiceIds('app.rule')` les rend-elle ?

- **A.** `10`, `0`, `-5` : la priority la plus haute d'abord
- **B.** `-5`, `10`, `0`
- **C.** `-5`, `0`, `10` : la priority la plus basse d'abord
- **D.** Dans l'ordre alphabétique des ids de service

<details>
<summary>Correction</summary>

**Bonne réponse : B.** La method ne trie pas : l'ordre de déclaration. A est
ce que rend `findAndSortTaggedServices()`.

</details>

**Question 3.** Dans `MyExtension::load()`, ton bundle appelle
`$container->hasDefinition('other.service')`, un service déclaré par **un autre
bundle**. Que rend-elle ?

- **A.** `false` : l'extension charge dans un container à part
- **B.** `true`, si l'autre bundle est déclaré avant dans `bundles.php`
- **C.** `true` dans tous les cas, toute la configuration étant chargée
- **D.** Une exception : `hasDefinition()` n'existe pas dans `load()`

<details>
<summary>Correction</summary>

**Bonne réponse : A.** Chaque extension charge seule ; une passe, elle, voit
les services de tous les bundles et de l'application.

</details>

**Question 4.** Le bundle `acme/sdk` déclare `acme.client`. Tu le redéclares
sous le même id dans `services.yaml`, avec un seul argument. Que devient la
definition ?

- **A.** Les deux fusionnent : ton argument remplace celui du bundle
- **B.** La definition du bundle gagne, les extensions chargeant en dernier
- **C.** Ta definition remplace toute celle du bundle
- **D.** La compilation échoue : un id ne se déclare qu'une fois

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Les definitions de l'application sont ré-appliquées en
dernier : arguments, tags et appels du bundle disparaissent.

</details>

## Fiche express

**Si tu dois retenir seulement 7 choses :**

- Une passe manipule des **definitions** ; elle tourne **une fois**.
- Défaut : **`TYPE_BEFORE_OPTIMIZATION`**, priority **0** ; plus haute = plus tôt.
- Autoconfiguration à **100** ; kernel à **-10000**.
- Référencer un privé **avant `TYPE_REMOVE`**, sinon erreur à l'instanciation.
- **`getDefinition()`** ne suit pas les alias, **`findDefinition()`** oui.
- **`findTaggedServiceIds()`** ne trie pas.
- Configuration pour **tes** services, passe pour ceux **des autres**.

Moyen mnémotechnique : **« Avant d'optimiser, avant de jeter »**.

## Sources officielles

- [How to Work with Compiler Passes](https://github.com/symfony/symfony-docs/blob/8.0/service_container/compiler_passes.rst)
- [`PassConfig`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Compiler/PassConfig.php)
- [HttpKernel 8.0, `Kernel`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/Kernel.php)
- [DependencyInjection 8.0, `MergeExtensionConfigurationPass`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Compiler/MergeExtensionConfigurationPass.php)
- [DependencyInjection 8.0, `PriorityTaggedServiceTrait`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Compiler/PriorityTaggedServiceTrait.php)

