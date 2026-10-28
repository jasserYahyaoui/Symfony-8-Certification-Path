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

## Avant la règle : le problème à résoudre

Tu écris un bundle de notifications. Il fournit un `HandlerChain`, et chaque
application qui l'installe ajoute ses propres handlers, tagués `app.handler`.

Ton bundle doit donc, **avant que le container soit figé**, trouver tous les
services tagués — y compris ceux déclarés par l'application et par d'autres
bundles — et les brancher sur `HandlerChain`. Or l'extension de ton bundle ne peut
pas le faire : exécuté avec DependencyInjection 8.0.15, dans `load()`, une
extension ne voit **ni** les services des autres bundles **ni** ceux de
l'application (`hasDefinition()` rend `false` pour les deux).

Il faut un code qui s'exécute **après** le chargement de toute la configuration,
sur le container complet, mais **avant** qu'il soit compilé en PHP. C'est une
**compiler pass**.

## Le fil rouge : une chaîne de handlers

```php
class HandlerChain
{
    private array $handlers = [];

    public function addHandler(HandlerInterface $handler): void
    {
        $this->handlers[] = $handler;
    }
}
```

La passe devra ajouter un appel `addHandler(...)` par service tagué. Elle ne
construit **aucun** handler : elle **décrit** les appels que le container fera
plus tard.

## Le cycle complet en un schéma

```text
CHARGER          services.yaml de l'app, puis load() de chaque extension
                 (chaque extension seule, dans un container temporaire)
     │           ── les definitions de l'app sont ré-appliquées en dernier
     ▼
1. BEFORE_OPTIMIZATION   (défaut)  priority 100 : autoconfiguration, _instanceof
                                    priority 0  : tes passes, par défaut
                                    -1000 : extensions qui sont aussi des passes
                                    -10000 : le Kernel, s'il implémente l'interface
2. OPTIMIZE              autowiring, alias, decorators, parameters résolus
3. BEFORE_REMOVING       (vide par défaut)
4. REMOVE                suppression des services privés non référencés, inlining
5. AFTER_REMOVING        le container final
     │
     ▼
VIDAGE en PHP (cache)  ── ensuite, plus aucune passe ne tourne : ni par Request,
                          ni au boot suivant tant que le cache est valide
```

## Ce qu'une passe est

Une class qui implémente `CompilerPassInterface`, donc une seule method :

```php
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

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
rend un object `Definition`, et `initialized('priv')` vaut `false`. Exécuté
aussi : la passe ci-dessus produit un `HandlerChain` qui reçoit `H1` puis `H2`.

### La garde `has()`

Sans elle, la passe casse la compilation quand le service est absent — par
exemple désactivé par la configuration. Exécuté : `findDefinition('chain')` sur
un container sans `chain` lève `ServiceNotFoundException`, « You have requested
a non-existent service "chain" ».

### Les methods utiles

| Method | Ce qu'elle fait |
|---|---|
| `has($id)` | vrai pour une definition **ou** un alias |
| `hasDefinition($id)` | vrai pour une definition seulement — exécuté : `false` sur un alias |
| `getDefinition($id)` | la definition ; sur un **alias**, `ServiceNotFoundException` (exécuté) |
| `findDefinition($id)` | la definition, **en suivant les alias** |
| `findTaggedServiceIds($tag)` | `id => liste d'attributes de tag` |
| `removeDefinition($id)` | retire le service (exécuté : `has()` devient `false`) |
| `$def->addMethodCall()` | ajoute un appel après construction |
| `$def->setArgument('$region', 'eu')` | fixe un argument, nommé ou par index (exécuté : `eu`) |

`replaceArgument()` exige que l'argument existe déjà. Exécuté sur une
definition sans arguments : `OutOfBoundsException`, « Cannot replace arguments
for class … if none have been configured yet ».

### Les tags dans une passe

`findTaggedServiceIds()` retourne un array `identifier => liste d'attributes de
tag`, la liste parce qu'un service peut porter le même tag plusieurs fois.
Exécuté, un service tagué deux fois : `{"x":[{"a":1},{"a":2}]}`.

Elle **ignore la priority**. Exécuté : trois services déclarés `low` (-5),
`high` (10), `mid` (0) sortent dans cet ordre de déclaration. Pour les trier,
`PriorityTaggedServiceTrait::findAndSortTaggedServices()` les rend dans l'ordre
`high`, `mid`, `low` — c'est ce que font les passes du framework.

Pour les tags d'un bundle, la documentation 8.0 recommande le préfixe du bundle,
en minuscules avec des underscores : `acme_mailer.transport` pour un tag
« transport » d'AcmeMailerBundle.

## Où l'enregistrer

Selon qui possède la passe, trois endroits documentés en 8.0 :

```php
// 1. un bundle : sa method build()
class MyBundle extends AbstractBundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new HandlerPass());
    }
}
```

```php
// 2. l'application : le Kernel implémente lui-même l'interface
class Kernel extends BaseKernel implements CompilerPassInterface
{
    use MicroKernelTrait;

    public function process(ContainerBuilder $container): void { /* … */ }
}
```

```php
// 3. l'application, avec des classes de passe séparées : Kernel::build()
class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    protected function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new CustomPass());
    }
}
```

Une class de bundle peut aussi implémenter l'interface et s'ajouter elle-même
(`addCompilerPass($this)` dans `build()`). Et une **extension** qui implémente
`CompilerPassInterface` n'a besoin d'aucun enregistrement : relu dans
`PassConfig` 8.0, `ExtensionCompilerPass` l'exécute à la priority -1000.

Le kernel n'est pas enregistré à la priority par défaut : `Kernel` l'ajoute en
`TYPE_BEFORE_OPTIMIZATION` avec la priority **-10000** (relu dans
`HttpKernel\Kernel`, 8.0.15), donc après les passes des bundles de la même étape.

Exécuté, dans une application avec un bundle qui enregistre deux passes :

| Ordre | Passe | Priority |
|---|---|---|
| 1 | passe du bundle, par `build()` | 0 |
| 2 | passe ajoutée par `Kernel::build()` | 0 |
| 3 | extension qui est aussi une passe | -1000 |
| 4 | `Kernel::process()` | -10000 |
| 5 | passe du bundle, priority explicite -20000 | -20000 |

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

Une passe ne s'exécute qu'à la **compilation**. Exécuté : au second boot, cache
chaud, aucune passe n'est appelée.

## Passe ou configuration

La configuration décrit **ses propres** services. Une passe intervient sur ceux
des **autres** : collecter tous les services portant un tag, retirer une
definition posée par un bundle tiers, changer un argument que l'on ne contrôle
pas. Si la configuration suffit, elle est préférable — elle est lisible et ne
s'exécute pas à la compilation.

Redéclarer le service d'un bundle sous le même id dans `services.yaml` est
possible, mais remplace **toute** la definition. Exécuté : un service
`other.service` déclaré par un bundle et redéclaré par l'application devient
celui de l'application — `MergeExtensionConfigurationPass` ré-applique les
definitions de l'application après celles des extensions. Les arguments, tags et
appels du bundle disparaissent avec, et les évolutions futures du bundle ne
l'atteignent plus. Pour changer **un** argument, la passe est l'outil.

| Besoin | Outil |
|---|---|
| un argument de **ton** service | configuration (`arguments`, `bind`, `#[Autowire]`) |
| un argument d'un service **d'un bundle** | une passe : `setArgument()` |
| tous les services d'un tag, injectés ensemble | `#[AutowireIterator]` d'abord ; une passe si l'injection ne suffit pas (appels de method, validation) |
| retirer un service d'un bundle tiers | une passe : `removeDefinition()` |

## Concrètement dans ton application

- Une passe applicative : le `Kernel` implémente `CompilerPassInterface`, ou
  `Kernel::build()` ajoute une class de passe.
- Une passe de bundle : `build()` du bundle, class dans
  `DependencyInjection/Compiler/`.
- Toujours une garde : `if (!$container->has(...)) return;`.
- Collecter un tag avec ordre : `PriorityTaggedServiceTrait`, pas
  `findTaggedServiceIds()` seul.
- Une passe qui ajoute une référence : l'étape par défaut, avant
  `TYPE_REMOVE`.
- Une passe qui lit les tags d'autoconfiguration : priority ≤ 100 dans l'étape
  par défaut (0 suffit).
- Pour voir le résultat : `php bin/console debug:container <id>`, qui affiche
  toujours la ligne « Arguments » en 8.0 (exécuté ; l'option `--show-arguments`
  n'existe plus).

## À comprendre

- **Pourquoi des definitions et pas des objects ?** Parce que le container n'est
  pas encore construit : la passe écrit le plan de construction, pas
  l'immeuble.
- **Pourquoi une passe voit-elle tout, et pas une extension ?** Parce que chaque
  extension charge sa configuration dans un container temporaire, isolé ; les
  passes tournent ensuite, sur le container fusionné.
- **Pourquoi l'étape compte-t-elle ?** Parce que chaque étape transforme le
  container : après `TYPE_REMOVE`, les services privés inutilisés n'existent
  plus ; avant 100, l'autoconfiguration n'a pas posé ses tags.
- **Pourquoi aucun coût par Request ?** Parce que le container compilé est
  vidé en PHP et mis en cache : les passes ne sont rejouées que si le cache est
  reconstruit.

## À mémoriser

- `CompilerPassInterface::process(ContainerBuilder $container)` ; des
  **definitions**, jamais des instances.
- Enregistrer : `Bundle::build()`, `Kernel` qui implémente l'interface (-10000),
  `Kernel::build()`, ou extension-passe (-1000).
- Ordre : `BEFORE_OPTIMIZATION` (défaut), `OPTIMIZE`, `BEFORE_REMOVING`,
  `REMOVE`, `AFTER_REMOVING`.
- **Priority haute = plus tôt** ; défaut `0` ; autoconfiguration à `100`.
- Référencer un service privé : **avant** `TYPE_REMOVE`.
- `getDefinition()` ne suit pas les alias ; `findDefinition()` les suit.
- `findTaggedServiceIds()` : une **liste** d'attributes par service, **sans
  tri** par priority.
- Moyen mnémotechnique : **« Avant d'optimiser, avant de jeter »** — la plupart
  des passes ont leur place dans l'étape par défaut.

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

## Application : trois scénarios

**Scénario 1 — Brancher les handlers sur `HandlerChain`.** *Solution.* Une
`HandlerPass` dans l'étape par défaut, enregistrée par `build()` du bundle :
garde `has()`, `findDefinition()`, puis un `addMethodCall('addHandler', [new
Reference($id)])` par service tagué. Si l'ordre compte, trie avec
`PriorityTaggedServiceTrait`.

**Scénario 2 — Changer un argument d'un service tiers.** Le bundle
`acme/sdk` déclare `acme.client` avec la région `us` ; tu veux `eu`.
*Solution.* Une passe dans le `Kernel` : `$container->findDefinition('acme.client')
->setArgument('$region', 'eu')`. Redéclarer `acme.client` dans `services.yaml`
écraserait toute la definition du bundle.

**Scénario 3 — La passe ne voit rien.** Ta passe, enregistrée avec la priority
`500` dans l'étape par défaut, lit le tag posé par
`registerForAutoconfiguration()` et ne trouve aucun service. *Solution.* À
`500`, elle tourne avant l'autoconfiguration (100). Laisse la priority à `0`.

## QCM d'entraînement

Six questions sur les compiler passes. Note ta lettre avant d'ouvrir la
correction.

**Question 1.** Dans `process()`, une passe appelle `$container->getDefinition('mailer')`, alors que `mailer` est un **alias** vers `app.mailer`. Que se passe-t-il ?

- **A.** La method rend la definition de `app.mailer`, comme pour un service
- **B.** La method rend un object `Alias`, à convertir avant usage
- **C.** La method rend `null`, car un alias n'a pas de definition propre
- **D.** `ServiceNotFoundException` : elle ne suit pas les alias

<details>
<summary>Correction</summary>

**Bonne réponse : D.** Exécuté : `getDefinition()` sur un alias lève « You have
requested a non-existent service » ; `findDefinition()` suit l'alias et rend la
definition de `app.mailer`.

A décrit `findDefinition()`. B invente un retour d'`Alias` (c'est `getAlias()`).
C invente un retour `null`.

</details>

**Question 2.** Trois services portent le tag `app.rule` avec les priorities `-5`, `10` et `0`, déclarés dans cet ordre. Dans quel ordre `findTaggedServiceIds('app.rule')` les rend-il ?

- **A.** `10`, `0`, `-5` : la priority la plus haute d'abord
- **B.** `-5`, `10`, `0` : l'ordre de déclaration, sans tri
- **C.** `-5`, `0`, `10` : la priority la plus basse d'abord
- **D.** Dans l'ordre alphabétique des ids de service

<details>
<summary>Correction</summary>

**Bonne réponse : B.** `findTaggedServiceIds()` ne trie pas (exécuté). Le tri par
priority est le travail de `PriorityTaggedServiceTrait::findAndSortTaggedServices()`,
qui rendrait A.

A est le piège : c'est l'ordre d'un `#[AutowireIterator]`, pas de cette method.
C inverse la règle. D invente un tri.

</details>

**Question 3.** Une passe ajoute, en `TYPE_AFTER_REMOVING`, une référence vers un service privé que personne n'utilisait. Quand l'erreur apparaît-elle ?

- **A.** À la compilation : `compile()` refuse une référence vers un service supprimé
- **B.** Au vidage du container en PHP, qui ne trouve pas la definition
- **C.** À l'instanciation du consommateur : `ServiceNotFoundException`
- **D.** Jamais : le service est recréé à la demande, puisqu'il est référencé

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Le service a été supprimé en `TYPE_REMOVE`. Exécuté :
`compile()`, le vidage et la construction du container passent ; `get()` du
consommateur lève « The "priv" service or alias has been removed or inlined when
the container was compiled ».

A et B placent l'erreur trop tôt. D invente une recréation.

</details>

**Question 4.** L'extension `MyExtension::load()` de ton bundle appelle `$container->hasDefinition('other.service')`, un service déclaré par **un autre bundle**. Que rend-elle ?

- **A.** `false` : l'extension charge dans un container à part
- **B.** `true`, si l'autre bundle est déclaré avant le tien dans `bundles.php`
- **C.** `true` dans tous les cas, puisque toute la configuration est chargée
- **D.** Une exception : `hasDefinition()` n'est pas disponible dans `load()`

<details>
<summary>Correction</summary>

**Bonne réponse : A.** Exécuté : dans `load()`, ni les services des autres
bundles ni ceux de l'application ne sont visibles. Une passe, elle, voit le
container fusionné — c'est pour cela qu'on en écrit une.

B est le piège : l'ordre des bundles ne change rien à cet isolement. C ignore le
container temporaire. D invente une restriction.

</details>

**Question 5.** Une application, sans bundle propre, veut enregistrer une class `CustomPass`. Quelle écriture est documentée en 8.0 ?

- **A.** Taguer `CustomPass` avec `container.compiler_pass` dans `services.yaml`
- **B.** Ajouter `CustomPass` dans `Kernel::build()` avec `addCompilerPass()`
- **C.** Déclarer `CustomPass` sous une key `compiler_passes` de `framework`
- **D.** Nommer la class `*Pass` dans `src/`, la découverte s'en charge

<details>
<summary>Correction</summary>

**Bonne réponse : B.** La documentation 8.0 montre `protected function
build(ContainerBuilder $container)` dans le `Kernel`. L'autre forme documentée
est un `Kernel` qui implémente lui-même `CompilerPassInterface`.

A : un tag ne peut pas enregistrer la passe qui doit justement façonner la
compilation. C invente une key. D confond la découverte de services et
l'enregistrement de passes.

</details>

**Question 6.** Le bundle `acme/sdk` déclare `acme.client`, dont tu veux changer un seul argument. Tu le redéclares sous le même id dans `services.yaml`, avec ce seul argument. Que se passe-t-il ?

- **A.** Les deux definitions fusionnent : ton argument remplace celui du bundle
- **B.** La definition du bundle gagne, car les extensions chargent en dernier
- **C.** La compilation échoue : un id ne peut pas être déclaré deux fois
- **D.** Ta definition remplace toute celle du bundle

<details>
<summary>Correction</summary>

**Bonne réponse : D.** Exécuté : la definition de l'application gagne ;
`MergeExtensionConfigurationPass` la ré-applique après les extensions, et elle
remplace tout. Une passe avec `setArgument()` change l'argument seul.

A invente une fusion. B est le piège : les extensions chargent après, mais les
definitions de l'application sont ré-appliquées en dernier. C invente une
interdiction.

</details>

## Fiche express

**Si tu dois retenir seulement 8 choses :**

- Une passe = `process(ContainerBuilder)` ; elle édite des **definitions**, rien
  n'est instancié.
- Elle voit **tout** le container fusionné ; une extension, dans `load()`, ne
  voit que le sien.
- Enregistrer : `Bundle::build()`, `Kernel::build()`, ou un `Kernel` qui
  implémente l'interface (-10000).
- Étapes : **BEFORE_OPTIMIZATION** (défaut) → OPTIMIZE → BEFORE_REMOVING →
  REMOVE → AFTER_REMOVING.
- **Priority haute = plus tôt** ; l'autoconfiguration est à 100.
- Référencer un service privé : **avant REMOVE**, sinon erreur à l'exécution.
- `findDefinition()` suit les alias ; `findTaggedServiceIds()` ne trie pas.
- Configuration pour **tes** services ; passe pour ceux des **autres**.

**En une phrase, pour tout reconstruire :**

```text
CHARGER (extensions isolées) → PASSES (default 100 / 0 / -1000 / -10000 → optimize → remove → after)
  → VIDER en PHP → plus aucune passe avant la prochaine reconstruction du cache
```

## Sources officielles

- [How to Work with Compiler Passes](https://github.com/symfony/symfony-docs/blob/8.0/service_container/compiler_passes.rst)
- [`PassConfig`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Compiler/PassConfig.php)
- [HttpKernel 8.0, `Kernel`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/Kernel.php)
- [DependencyInjection 8.0, `MergeExtensionConfigurationPass`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Compiler/MergeExtensionConfigurationPass.php)
- [DependencyInjection 8.0, `PriorityTaggedServiceTrait`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Compiler/PriorityTaggedServiceTrait.php)
