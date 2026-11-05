---
id: CRS-exs5dvtqa1as
official_item: OIT-qj4xfkhwdrx7
title: "Semantic configuration"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-28"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/bundles/configuration.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/bundles/configuration.rst"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    symbol_or_lines: "AbstractBundle::configure, loadExtension, ConfigurationInterface"
    verified_at: "2026-10-28"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/bundles/extension.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/bundles/extension.rst"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    symbol_or_lines: '"How to Load Service Configuration inside a Bundle" — the two ways: loading services in the main bundle class, or "Create an extension class to load the service configuration files"'
    verified_at: "2026-10-28"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/bundles/prepend_extension.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/bundles/prepend_extension.rst"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    symbol_or_lines: "config/* files override prepended settings; the bundle registered first takes priority"
    verified_at: "2026-10-28"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/TwigBundle/DependencyInjection/TwigExtension.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/TwigBundle/DependencyInjection/TwigExtension.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "getBundleTemplatePaths(), normalizeBundleName()"
    verified_at: "2026-10-28"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Config/Definition/ArrayNode.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Config/Definition/ArrayNode.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "Unrecognized option %s under %s"
    verified_at: "2026-10-28"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Config/Definition/Builder/NodeDefinition.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Config/Definition/Builder/NodeDefinition.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "defaultValue() and isRequired(): InvalidDefinitionException when both are set"
    verified_at: "2026-10-28"
outcome_sections:
  OUT-06ednnh0npmz: "Les deux moitiés : l'arbre et l'extension"
  OUT-qrtvpp33jqb2: "Une key inconnue est une erreur : ce que l'exécution montre"
  OUT-k2cfezp9wvf5: "La forme moderne : `AbstractBundle`, à la compilation"
  OUT-vmzkkhjtf67j: "Configurer un autre bundle : le prepend"
  OUT-bfyfkyqnw1hv: "Requis ou par défaut, pas les deux"
---

## Objectif

Comprendre ce qui transforme `framework: { … }` en services : un arbre qui
déclare et valide les keys, une extension qui les traduit en definitions.
Savoir quand ce code s'exécute, pourquoi une key inconnue est une erreur, et
comment un bundle configure un autre bundle sans priver l'application du dernier
mot.

## L'essentiel

Un bundle a deux moitiés : un **arbre** qui déclare et **valide** ses keys, une
**extension** qui traduit la configuration validée en services. Une key
inconnue est une erreur. Le tout ne s'exécute qu'à la **compilation**. Et un
bundle peut configurer un autre bundle par **prepend**, l'application gardant
le dernier mot.

## Le schéma

```text
config/packages/acme_social.yaml (+ ce que d'autres bundles ont prepend)
L'ARBRE        keys, types, défauts, required, bornes ; fusionne et VALIDE → sinon InvalidConfigurationException
L'EXTENSION    reçoit l'array validé et complété → enregistre acme_social.client avec $timeout
COMPILATION    → cache ; cache chaud : ni l'arbre ni l'extension ne s'exécutent
```

## Le fil rouge : un `AcmeSocialBundle`

```yaml
# config/packages/acme_social.yaml
acme_social:
    client_id: '%env(SOCIAL_CLIENT_ID)%'
    timeout: 10
```

## Les deux moitiés : l'arbre et l'extension

```php
public function getConfigTreeBuilder(): TreeBuilder
{
    $tree = new TreeBuilder('acme_social');            // la key racine
    $tree->getRootNode()
        ->children()
            ->integerNode('timeout')->defaultValue(30)->min(1)->end()
            ->scalarNode('client_id')->isRequired()->end()
        ->end();

    return $tree;
}
```

L'arbre (`ConfigurationInterface`) **déclare et valide** ; l'extension ne
valide rien : elle reçoit un array déjà complété (`timeout` vaut `30` s'il
manque) et enregistre les definitions.

## Une key inconnue est une erreur : ce que l'exécution montre

| Configuration de l'application | Résultat |
|---|---|
| `timout: 5` | `InvalidConfigurationException` : « Unrecognized option "timout" under "acme_social". Did you mean "timeout"? » |
| `timeout: 0` | « The value 0 is too small for path "acme_social.timeout"… » |
| aucune, `client_id` absent | « The child config "client_id" under "acme_social" must be configured. » |
| `client_id` seul | `timeout` vaut `30` |

L'extension traite une configuration vide même si l'application ne mentionne
pas le bundle : un node `isRequired()` sans valeur fait échouer la compilation.

## Requis ou par défaut, pas les deux

En Config 8.0, `isRequired()` et `defaultValue()` (ou `defaultNull()`) sur un
même node lèvent `InvalidDefinitionException`, « …cannot be required and have
a default value », dans les deux ordres, dès la construction de l'arbre.

## La forme moderne : `AbstractBundle`, à la compilation

```php
class AcmeSocialBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void { /* l'arbre */ }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->services()->set('acme_social.client', Client::class)->arg('$timeout', $config['timeout']);
    }
}
```

- `configure()` et `loadExtension()` ne tournent qu'à la **compilation** : ni
  Request, ni user.
- La key racine découle du nom du bundle : `AcmeSocialBundle` →
  `acme_social`.

## Configurer un autre bundle : le prepend

```php
public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
{
    $container->extension('framework', ['cache' => ['prefix_seed' => 'foo/bar']], prepend: true);
}
```

| Qui écrit la même key | Qui gagne |
|---|---|
| un bundle (prepend) contre l'application | **l'application** |
| deux bundles en prepend | **le premier enregistré** : `from-first` |

Les templates d'un bundle n'ont pas besoin de prepend : TwigBundle enregistre
seul leur dossier sous `@AcmeSocial`, le nom sans `Bundle`
(`TwigExtension::getBundleTemplatePaths()`).

## Pièges d'examen

**Une key inconnue est une erreur**, pas une valeur ignorée — avec suggestion.

**`loadExtension()` s'exécute à la compilation** : elle ne voit ni Request ni
état d'exécution.

**Le *prepend* passe avant l'application**, qui peut donc toujours surcharger ;
entre bundles, le premier enregistré gagne.

**`isRequired()` s'applique même si l'application ignore le bundle.**

**`isRequired()` et `defaultValue()` sur un même node : `InvalidDefinitionException`**,
dans les deux ordres.

## Application

- Une option du bundle est mal orthographiée → la compilation échoue et
  suggère la bonne key ; `debug:config framework` montre la configuration
  fusionnée d'une extension.
- Un bundle doit régler le cache de `framework` sans imposer sa valeur →
  `prependExtension()`.

## QCM d'entraînement

**Question 1.** Quelle key racine expose un bundle nommé `PaymentGatewayBundle` ?

- **A.** `payment_gateway`
- **B.** `PaymentGateway`
- **C.** `paymentgateway_bundle`
- **D.** `payment-gateway`

<details>
<summary>Correction</summary>

**Bonne réponse : A.** Le nom du bundle, privé du suffixe `Bundle`, puis passé
en snake_case, donne la key racine.

</details>

**Question 2.** `FirstBundle` puis `SecondBundle` prepend la même key de
`acme_social`, l'un `from-first`, l'autre `from-second`. L'application ne la
fixe pas. Quelle valeur gagne ?

- **A.** `from-second`, le dernier enregistré
- **B.** `from-first`
- **C.** Les deux, réunies dans un array
- **D.** Aucune : la compilation échoue sur le conflit

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Entre deux prepends, le premier bundle enregistré
l'emporte — à l'inverse de l'intuition.

</details>

**Question 3.** Sous quel espace de noms Twig trouve-t-on les templates
d'`AcmeSocialBundle` ?

- **A.** `@AcmeSocialBundle`
- **B.** `@acme_social`
- **C.** `@AcmeSocial`
- **D.** `@Acme`

<details>
<summary>Correction</summary>

**Bonne réponse : C.** TwigBundle enregistre le dossier de chaque bundle sous
son nom privé du suffixe `Bundle`.

</details>

**Question 4.** L'arbre déclare `->integerNode('timeout')->defaultValue(30)->min(1)`.
L'application écrit `timeout: 0`. Que se passe-t-il ?

- **A.** `timeout` est ramené à `1`, la borne basse
- **B.** `timeout` reprend sa default value, `30`
- **C.** La valeur est acceptée : `min()` n'est qu'un conseil
- **D.** La compilation échoue : valeur trop petite

<details>
<summary>Correction</summary>

**Bonne réponse : D.** L'arbre valide les bornes avant l'extension ; une valeur
hors bornes arrête la compilation.

</details>

## Fiche express

**Si tu dois retenir seulement 7 choses :**

- **L'arbre** déclare et valide ; **l'extension** enregistre les services.
- Key inconnue : **erreur**, avec suggestion.
- **`isRequired()`** vaut même si l'application ignore le bundle.
- `isRequired()` + `defaultValue()` : **`InvalidDefinitionException`**.
- `configure()`, `loadExtension()` : **à la compilation** seulement.
- Prepend : l'**application** gagne ; entre bundles, le **premier**.
- `AcmeSocialBundle` → `acme_social`, templates `@AcmeSocial`.

Moyen mnémotechnique : **« L'arbre promet, l'extension construit »**.

## Sources officielles

- [How to Create Friendly Configuration for a Bundle](https://github.com/symfony/symfony-docs/blob/8.0/bundles/configuration.rst)
- [How to Load Service Configuration inside a Bundle](https://github.com/symfony/symfony-docs/blob/8.0/bundles/extension.rst)
- [How to Simplify Configuration of Multiple Bundles](https://github.com/symfony/symfony-docs/blob/8.0/bundles/prepend_extension.rst)
- [TwigBundle 8.0, `TwigExtension`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/TwigBundle/DependencyInjection/TwigExtension.php)
- [Config 8.0, `Definition\ArrayNode`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Config/Definition/ArrayNode.php)
- [Config 8.0, `Definition\Builder\NodeDefinition`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Config/Definition/Builder/NodeDefinition.php)

