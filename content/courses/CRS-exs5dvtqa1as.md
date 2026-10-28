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

## Avant la règle : le problème à résoudre

Tu écris `framework: { csrf_protection: true }` dans `config/packages/` et la
protection CSRF existe. Personne n'a écrit la definition du service qui génère
les tokens. Comment une ligne de configuration devient-elle des services ?

Sans mécanisme dédié, configurer un bundle voudrait dire écrire ses definitions
de services à la main : connaître leurs ids, leurs classes, leurs arguments. Un
changement interne au bundle casserait toutes les applications.

La **semantic configuration** offre à la place un **langage** : quelques keys
lisibles, documentées, **validées**, que le bundle traduit lui-même en services.
L'application dit *quoi* ; le bundle sait *comment*.

## Le fil rouge : un `AcmeSocialBundle`

Un bundle qui appelle une API sociale. L'application doit pouvoir écrire :

```yaml
# config/packages/acme_social.yaml
acme_social:
    client_id: '%env(SOCIAL_CLIENT_ID)%'
    timeout: 10
```

Et le bundle doit en faire un service `acme_social.client` configuré avec ces
valeurs. On va voir les deux pièces qui le permettent, et ce qui se passe quand
l'application se trompe.

## Le cycle complet en un schéma

```text
config/packages/acme_social.yaml       (+ ce que d'autres bundles ont « prepend »)
        │
        ▼
L'ARBRE (ConfigurationInterface / AbstractBundle::configure)
   déclare : keys admises, types, default values, required, min/max
   fusionne les fichiers, VALIDE         ── key inconnue, valeur hors bornes, required absent
        │                                     → InvalidConfigurationException, compilation arrêtée
        ▼
L'EXTENSION (Extension::load / AbstractBundle::loadExtension)
   reçoit l'array VALIDÉ et complété (defaults)
   enregistre les definitions : acme_social.client avec $timeout = 10
        │
        ▼
COMPILATION du container → cache
   au démarrage suivant, cache chaud : ni l'arbre ni l'extension ne s'exécutent
```

## Les deux moitiés : l'arbre et l'extension

| Class | Ce qu'elle fait |
|---|---|
| `ConfigurationInterface` | **déclare** l'arbre : keys admises, types, default values, validation |
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

Lecture ligne par ligne :

- `new TreeBuilder('acme_social')` : le nom de la **key racine** ;
- `integerNode('timeout')` : un int, `defaultValue(30)` s'il est absent,
  `min(1)` en borne basse ;
- `scalarNode('client_id')->isRequired()` : obligatoire, sans default value.

L'extension, elle, ne valide rien : elle reçoit un array **déjà validé et
complété** (`timeout` vaut `30` si l'application ne l'a pas donné) et le
traduit en definitions de services.

## Une key inconnue est une erreur : ce que l'exécution montre

Une key absente de l'arbre provoque une **erreur au chargement**, pas un silence.
C'est le principal intérêt : la configuration est validée avant de servir.

Un `AcmeSocialBundle` portant cet arbre, chargé dans une application
FrameworkBundle 8.0.15 :

| Configuration de l'application | Résultat |
|---|---|
| `timout: 5` | `InvalidConfigurationException` : « Unrecognized option "timout" under "acme_social". Did you mean "timeout"? » |
| `timeout: 0` | « The value 0 is too small for path "acme_social.timeout". Should be greater than or equal to 1 » |
| aucune, `client_id` non fourni | « The child config "client_id" under "acme_social" must be configured. » |
| `client_id` fourni, rien d'autre | `timeout` vaut `30` |

La troisième ligne est le piège : l'extension traite une configuration vide même
quand l'application ne mentionne pas le bundle, donc un node `isRequired()` sans
valeur fait échouer la compilation.

## Requis ou par défaut, pas les deux

Un node est soit **obligatoire**, soit **pourvu d'une default value** — le
CHANGELOG de Config 8.0 l'impose. Exécuté sur Config 8.0.15 :
`isRequired()->defaultValue(1)`, l'ordre inverse et `isRequired()->defaultNull()`
lèvent tous trois une `InvalidDefinitionException` — « The node "x" cannot be
required and have a default value. » — dès la construction de l'arbre, avant
toute configuration d'application. `isRequired()` seul passe.

La logique est simple : une default value dit « si absent, prends ceci » ;
`isRequired()` dit « l'absence est une erreur ». Les deux ensemble se
contredisent, et 8.0 refuse la contradiction au lieu de laisser l'une gagner en
silence.

## La forme moderne : `AbstractBundle`, à la compilation

Un bundle étendant `AbstractBundle` porte les deux moitiés dans sa propre class :

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
container, jamais à l'exécution. Exécuté : au premier démarrage, les deux
methods sont appelées ; au second, cache chaud, aucune.

Conséquence : `loadExtension()` ne voit ni Request, ni user, ni état
d'exécution. Elle décide de la **forme** du container, une fois.

Le nom de la key racine découle du nom du bundle : `AcmeSocialBundle` donne
`acme_social` — exécuté, `getContainerExtension()->getAlias()` le confirme.

## Configurer un autre bundle : le prepend

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

Entre deux bundles qui prepend la même key, c'est l'inverse de l'intuition :
**le premier enregistré l'emporte** (`prepend_extension.rst`). Exécuté, avec
`FirstBundle` puis `SecondBundle` : `from-first`.

| Qui écrit la même key | Qui gagne |
|---|---|
| un bundle (prepend) contre l'application (`config/packages/`) | **l'application** |
| deux bundles en prepend | **le premier enregistré** dans `bundles.php` |

Les templates d'un bundle, eux, n'ont pas besoin de prepend : TwigBundle
enregistre de lui-même le dossier `templates/` ou `Resources/views/` de chaque
bundle, sous l'espace de son nom privé du suffixe `Bundle` — `@AcmeSocial` —
(`TwigExtension::getBundleTemplatePaths()`).

## Concrètement dans ton application

- Tu configures un bundle dans `config/packages/<key>.yaml` ; la key racine est
  le nom du bundle en snake_case sans `Bundle` (`framework`, `twig`, `security`).
- Une faute de frappe ? L'erreur arrive à la compilation, avec une suggestion
  (« Did you mean … ? ») : lis-la, elle donne la bonne key.
- Pour voir l'arbre complet d'un bundle et ses default values :
  `php bin/console config:dump-reference framework` ; pour la configuration
  effective, fusionnée : `php bin/console debug:config framework`.
- Tu écris un bundle : `AbstractBundle`, `configure()` pour l'arbre,
  `loadExtension()` pour les services, `prependExtension()` pour régler un autre
  bundle.

## À comprendre

- **Pourquoi deux moitiés ?** Pour séparer la **promesse** faite à
  l'application (les keys, stables, documentées) de la **mécanique** interne
  (les services, libres d'évoluer).
- **Pourquoi une key inconnue est-elle une erreur ?** Parce qu'une key ignorée
  en silence ferait croire qu'un réglage est actif alors qu'il ne l'est pas.
- **Pourquoi à la compilation ?** Parce que traduire la configuration en
  services ne dépend d'aucun appel : c'est fait une fois, puis mis en cache.
- **Pourquoi l'application gagne-t-elle contre un prepend ?** Parce que le
  prepend fournit des valeurs **par défaut** pour l'application, pas des
  valeurs imposées.

## À mémoriser

- `ConfigurationInterface` (ou `AbstractBundle::configure()`) **déclare et
  valide** ; l'extension (ou `loadExtension()`) **enregistre les services**.
- Key inconnue → `InvalidConfigurationException` « Unrecognized option … Did
  you mean … ? ».
- `isRequired()` s'applique **même si l'application ignore le bundle**.
- `isRequired()` + `defaultValue()` (ou `defaultNull()`) →
  `InvalidDefinitionException` en 8.0.
- `configure()` et `loadExtension()` : **compilation seulement** (cache chaud :
  aucun appel).
- Key racine : `AcmeSocialBundle` → `acme_social`.
- Prepend : **l'application gagne** ; entre bundles, **le premier enregistré**.
- Moyen mnémotechnique : **« L'arbre promet, l'extension construit »**.

## Pièges d'examen

**Une key inconnue est une erreur**, pas une valeur ignorée — avec suggestion.

**`loadExtension()` s'exécute à la compilation** : elle ne voit ni Request ni
état d'exécution.

**Le *prepend* passe avant l'application**, qui peut donc toujours surcharger ;
entre bundles, le premier enregistré gagne.

**`isRequired()` s'applique même si l'application ignore le bundle.**

**`isRequired()` et `defaultValue()` sur un même node : `InvalidDefinitionException`**,
dans les deux ordres.

## Application : trois scénarios

**Scénario 1 — La faute de frappe.** L'application écrit `acme_social: { timout: 5 }`.
*Diagnostic.* La compilation échoue : « Unrecognized option "timout" under
"acme_social". Did you mean "timeout"? ». *Correction.* La suggestion donne la
bonne key.

**Scénario 2 — Le bundle installé mais pas configuré.** Après `composer
require`, l'application plante avant toute page : « The child config
"client_id" under "acme_social" must be configured. ». *Diagnostic.* Un node
`isRequired()` s'applique même à une configuration vide. *Correction.* Créer
`config/packages/acme_social.yaml` avec `client_id`.

**Scénario 3 — Le réglage qui ne prend pas.** Un bundle prepend
`framework.cache.prefix_seed: foo/bar`, mais l'application en déclare un autre
dans `config/packages/framework.yaml`. *Diagnostic.* Le prepend passe avant
l'application, qui gagne. *Correction.* Si c'est voulu, rien ; sinon retirer la
valeur de l'application.

## QCM d'entraînement

Six questions sur la configuration des bundles. Engage-toi sur une lettre avant
de déplier la correction.

**Question 1.** Dans un bundle, quelle pièce refuse une key de configuration mal orthographiée ?

- **A.** L'extension, qui ignore les keys qu'elle n'utilise pas
- **B.** L'arbre déclaré par `ConfigurationInterface`, qui valide avant l'extension
- **C.** Le kernel, qui compare la configuration aux services existants avant de démarrer
- **D.** Aucune : une key inconnue est ignorée en silence

<details>
<summary>Correction</summary>

**Bonne réponse : B.** L'arbre déclare les keys admises ; une key absente lève
« Unrecognized option … Did you mean … ? » (exécuté), avant que l'extension ne
reçoive quoi que ce soit.

A est le piège : l'extension reçoit un array déjà validé, elle ne valide rien.
C invente une comparaison avec les services. D est l'erreur de fond : rien
n'est ignoré en silence.

</details>

**Question 2.** Un bundle vient d'être installé. Son arbre déclare `->scalarNode('api_key')->isRequired()`, et l'application n'a aucun file de configuration pour lui. Que se passe-t-il ?

- **A.** Rien : un bundle non configuré n'est pas traité
- **B.** `api_key` reçoit `null` par défaut
- **C.** La compilation échoue : « … must be configured. »
- **D.** Un avertissement est écrit dans le log, et le bundle est désactivé

<details>
<summary>Correction</summary>

**Bonne réponse : C.** L'extension traite une configuration vide même quand
l'application ne mentionne pas le bundle ; un node requis sans valeur fait donc
échouer la compilation (exécuté).

A est le piège : on imagine qu'un bundle non configuré est sauté. B confond avec
une default value, incompatible avec `isRequired()`. D invente une désactivation.

</details>

**Question 3.** Sur Symfony 8.0, un arbre contient `->integerNode('retries')->isRequired()->defaultValue(3)`. Que se passe-t-il ?

- **A.** `retries` vaut `3` si l'application ne le donne pas
- **B.** `isRequired()` l'emporte : l'application doit toujours fournir `retries`
- **C.** La dernière method appelée l'emporte, ici `defaultValue(3)`
- **D.** Une `InvalidDefinitionException` dès la construction de l'arbre

<details>
<summary>Correction</summary>

**Bonne réponse : D.** En 8.0, un node ne peut pas être requis et avoir une
default value ; exécuté, « The node … cannot be required and have a default
value. », dans les deux ordres, avant toute configuration d'application.

A et B choisissent chacun un gagnant, alors que 8.0 refuse la contradiction. C
invente une règle d'ordre : l'ordre inverse échoue aussi.

</details>

**Question 4.** Un `AbstractBundle` lit la Request courante dans `loadExtension()` pour choisir quels services enregistrer. Que se passe-t-il ?

- **A.** Cela fonctionne, mais seulement en environment `dev`
- **B.** Cela fonctionne : `loadExtension()` est appelée à chaque appel HTTP
- **C.** Il n'y a pas de Request : la method ne tourne qu'à la compilation
- **D.** Cela fonctionne si la Request est injectée par autowiring dans le bundle

<details>
<summary>Correction</summary>

**Bonne réponse : C.** `configure()` et `loadExtension()` ne sont appelées
qu'à la compilation ; exécuté, au second démarrage, cache chaud, aucune des
deux.

B est le piège : il confond le code du bundle avec celui d'un listener. A
invente une différence d'environment : en `dev`, la compilation est seulement
plus fréquente. D suppose un autowiring dans la class du bundle, qui n'est pas
un service.

</details>

**Question 5.** Le `prependExtension()` de ton bundle de partage fixe le texte du bouton à `Share` ; le file YAML du projet, lui, demande `Partager`. Avec quel texte l'extension est-elle chargée ?

- **A.** `Share` : la configuration d'un bundle l'emporte toujours sur celle du projet
- **B.** `Partager` : le prepend est placé avant, le projet garde le dernier mot
- **C.** Les deux valeurs, réunies dans un array de deux éléments
- **D.** Aucun : le conflit entre les deux sources arrête la compilation

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Ce qui est prepend passe **avant** : la configuration du
projet, traitée après, garde le dernier mot (exécuté avec une key scalaire :
la valeur du projet remplace celle du bundle).

A est le piège : « avant » veut dire « écrasé ensuite », pas « prioritaire ». C
invente une fusion en array d'une valeur scalaire. D invente un conflit.

</details>

**Question 6.** Quelle key racine expose un bundle nommé `PaymentGatewayBundle` ?

- **A.** `payment_gateway`
- **B.** `PaymentGateway`
- **C.** `paymentgateway_bundle`
- **D.** `payment-gateway`

<details>
<summary>Correction</summary>

**Bonne réponse : A.** La key racine découle du nom du bundle, en snake_case et
sans le suffixe `Bundle` : `AcmeSocialBundle` donne `acme_social` (exécuté,
`getContainerExtension()->getAlias()`).

B garde la casse de la class. C garde le suffixe. D utilise des tirets.

</details>

## Fiche express

**Si tu dois retenir seulement 6 choses :**

- **L'arbre promet, l'extension construit** : `ConfigurationInterface` /
  `configure()` déclare et valide ; l'extension / `loadExtension()` enregistre
  les services.
- **Key inconnue = erreur** au chargement, avec suggestion.
- `isRequired()` s'applique **même sans configuration** de l'application.
- 8.0 : **requis ou default, jamais les deux** (`InvalidDefinitionException`).
- Tout cela tourne **à la compilation** ; cache chaud, rien n'est rappelé.
- Prepend : **l'application gagne** ; entre bundles, **le premier enregistré** ;
  key racine `XxxYyyBundle` → `xxx_yyy`.

**En une phrase, pour tout reconstruire :**

```text
config/packages/<key>.yaml (+ prepends, perdants face à l'app)
  → ARBRE : déclare, fusionne, VALIDE (key inconnue, bornes, required)
  → EXTENSION : array validé → definitions de services
  → COMPILATION une fois, puis cache
```

## Sources officielles

- [How to Create Friendly Configuration for a Bundle](https://github.com/symfony/symfony-docs/blob/8.0/bundles/configuration.rst)
- [How to Load Service Configuration inside a Bundle](https://github.com/symfony/symfony-docs/blob/8.0/bundles/extension.rst)
- [How to Simplify Configuration of Multiple Bundles](https://github.com/symfony/symfony-docs/blob/8.0/bundles/prepend_extension.rst)
- [TwigBundle 8.0, `TwigExtension`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/TwigBundle/DependencyInjection/TwigExtension.php)
- [Config 8.0, `Definition\ArrayNode`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Config/Definition/ArrayNode.php)
- [Config 8.0, `Definition\Builder\NodeDefinition`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Config/Definition/Builder/NodeDefinition.php)
