---
id: CRS-jtkmpfp3nwzk
official_item: OIT-dy7108w6bf4z
title: "Built-in services"
content_level: MINIMAL
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-27"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/service_container.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/service_container.rst"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    symbol_or_lines: 'Fetching and using Services — "The moment you start a Symfony app, your container *already* contains many services"; "And each time you install a new bundle, you get access to even more"; debug:autowiring'
    verified_at: "2026-10-27"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/quick_tour/flex_recipes.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/quick_tour/flex_recipes.rst"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    symbol_or_lines: '"In addition to automatically enabling the feature in config/bundles.php"'
    verified_at: "2026-10-27"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/Command/DebugAutowiringCommand.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Command/DebugAutowiringCommand.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "argument search; option all (Show also services that are not aliased); No autowirable classes or interfaces found matching"
    verified_at: "2026-10-27"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/Resources/config/services.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Resources/config/services.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "alias(RequestStack::class, request_stack); alias(Filesystem::class, filesystem)"
    verified_at: "2026-10-27"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/FrameworkExtension.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/FrameworkExtension.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "load() — $loader->load('web.php'), load('services.php'), load('cache.php')…; readConfigEnabled()"
    verified_at: "2026-10-27"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/Configuration.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/Configuration.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "$enableIfStandalone — canBeDisabled when the package is installed; serializer, validator, http_client sections"
    verified_at: "2026-10-27"
outcome_sections:
  OUT-q8fq5v0gn6nh: "D'où viennent les services intégrés"
  OUT-gg02aw655bat: "Trouver le bon type : `debug:autowiring`"
---

## Objectif

Savoir d'où viennent les services que l'on n'a jamais déclarés, pourquoi l'un
d'eux peut manquer, et trouver le type à écrire dans un constructor avec la
bonne command, sans apprendre de liste par cœur.

## Avant la règle : le problème à résoudre

Tu écris ton premier service, et il a besoin de journaliser, de générer une URL,
de valider un object. Tu ne vas pas écrire toi-même un logger, un router ou un
validator : Symfony les fournit déjà. La documentation le dit ainsi : « The
moment you start a Symfony app, your container *already* contains many
services. »

Mais trois questions se posent tout de suite :

1. **D'où viennent-ils ?** Personne ne les a déclarés dans `services.yaml`.
2. **Pourquoi l'un d'eux manque-t-il parfois ?** Tu demandes `Twig\Environment`
   et la compilation échoue, alors que le package `twig/twig` est installé.
3. **Quel type écrire ?** Le service s'appelle `logger`, sa class est
   `Symfony\Component\HttpKernel\Log\Logger`… mais c'est un **autre** type qu'il
   faut écrire dans ton constructor.

Il n'y a rien à mémoriser dans ce chapitre, sauf un mécanisme et une command.

## Le fil rouge : un `InvoiceMailer`

Un service qui envoie une facture : il journalise, il génère l'URL de la facture,
et il rend un template.

```php
class InvoiceMailer
{
    public function __construct(
        private LoggerInterface $logger,              // Psr\Log\LoggerInterface
        private UrlGeneratorInterface $urls,          // Symfony\Component\Routing\Generator\…
        private Environment $twig,                    // Twig\Environment
    ) {
    }
}
```

Aucun de ces trois services n'est déclaré par toi. On va voir qui les fournit,
pourquoi ces trois **types** sont les bons, et comment tu les aurais trouvés
seul.

## Le cycle complet en un schéma

```text
composer require symfony/twig-bundle
   │
   ▼
RECIPE FLEX          active le bundle dans config/bundles.php (+ config, + dossiers)
   │
   ▼
BUNDLE               au build du container, son extension charge ses definitions
(TwigBundle,         (FrameworkBundle : web.php, services.php, cache.php…),
 FrameworkBundle…)   selon la configuration : une section désactivée n'enregistre rien
   │
   ▼
ALIAS DE TYPE        le bundle déclare sous quel TYPE chaque service s'injecte :
                     Psr\Log\LoggerInterface → logger · Twig\Environment → twig
   │
   ▼
TON CONSTRUCTOR      tu écris le type aliasé ; l'autowiring injecte le service
                     ← debug:autowiring te dit lequel écrire
```

## D'où viennent les services intégrés

Un service « intégré » est un service qu'un **bundle** enregistre dans le
container. Il n'y a pas de liste figée dans le kernel : chaque bundle installé
apporte les siens, et la documentation ajoute : « each time you install a new
bundle, you get access to even more! »

**FrameworkBundle** en apporte la plus grande part : router, dispatcher
d'events, HttpKernel, `RequestStack`, cache, filesystem, et, quand leurs
packages sont installés, serializer, validator, client HTTP, translator.
**TwigBundle** ajoute Twig, **SecurityBundle** la security, et ainsi de suite.

Le mécanisme, lu dans `FrameworkExtension::load()` (8.0) : l'extension du
bundle charge des files de definitions (`web.php`, `services.php`, `cache.php`…)
pendant la construction du container. Dans `Resources/config/services.php`, on
lit par exemple `->alias(RequestStack::class, 'request_stack')` et
`->alias(Filesystem::class, 'filesystem')` : le bundle déclare le service **et**
le type sous lequel il s'injecte.

### Pourquoi `composer require` suffit

La recipe Flex fait le lien. La documentation, à propos d'une recipe : « In
addition to automatically enabling the feature in `config/bundles.php` »… Le
bundle est activé, son extension tourne au prochain build du container, ses
services deviennent injectables. Tu n'écris rien dans `services.yaml`.

### Pourquoi un service peut manquer

Deux causes, toutes deux exécutées sur FrameworkBundle et TwigBundle 8.0.15 :

| Situation | `Twig\Environment` / `SerializerInterface` injectable ? |
|---|---|
| TwigBundle activé | `Twig\Environment` : **oui** |
| `twig/twig` installé, mais **sans** TwigBundle | **non** : « Cannot autowire service … references class "Twig\Environment" but no such service exists. » |
| `symfony/serializer` installé, section `framework.serializer` par défaut | `SerializerInterface` : **oui** |
| `symfony/serializer` installé, `framework.serializer.enabled: false` | **non** : le type disparaît de `debug:autowiring` (89 types au lieu de 98) |

La première ligne contre la deuxième : c'est le **bundle** qui fournit le
service, pas le package de la bibliothèque. La troisième contre la quatrième :
FrameworkBundle n'enregistre les services d'une section que si elle est
**activée**. Lu dans `Configuration.php` : pour le serializer, le validator ou
le client HTTP, la section est activée par défaut dès que le package est
installé (`canBeDisabled`), et désactivée sinon (`canBeEnabled`).

## Trouver le bon type : `debug:autowiring`

Il n'y a pas de liste à apprendre ; il y a une command qui répond exactement à
la question que tu te poses en écrivant un constructor : *quel type puis-je
écrire ?*

```bash
php bin/console debug:autowiring             # tous les types utilisables
php bin/console debug:autowiring log         # filtrés : ceux qui contiennent « log »
php bin/console debug:autowiring --all       # + les services sans alias de type
```

Chaque ligne donne le type et le service qu'il désigne. Exécuté :

```text
 Psr\Log\LoggerInterface - alias:logger
```

Ce que dit le code de `DebugAutowiringCommand` (8.0), et ce que montre
l'exécution :

| Écriture | Résultat |
|---|---|
| `debug:autowiring` | les types utilisables comme type-hint (98 sur une application minimale FrameworkBundle + TwigBundle) |
| `debug:autowiring log` | ne garde que les types qui **contiennent** « log » (recherche partielle) |
| `debug:autowiring zzzz` | « [ERROR] No autowirable classes or interfaces found matching "zzzz" », exit code 1 |
| `debug:autowiring RedirectController` | rien, mais l'indication « 1 more concrete service would be displayed when adding the "--all" option » |
| `debug:autowiring RedirectController --all` | `RedirectController` apparaît : `--all` ajoute les services enregistrés sous leur nom de class sans alias |

### Pourquoi pas `debug:container` ?

Parce que les deux commands ne répondent pas à la même question.

| | `debug:container` | `debug:autowiring` |
|---|---|---|
| Question | quels **services** existent ? | quel **type** écrire dans un constructor ? |
| Ce qu'elle liste | les identifiers (`logger`, `router`, `twig`…) | les types aliasés (`Psr\Log\LoggerInterface`…) |
| Utile pour | inspecter un service, ses tags, ses arguments | écrire une signature qui sera autowirée |

`logger` apparaît dans `debug:container`, mais ce nom ne t'aide pas à écrire ton
constructor : l'autowiring cherche un service nommé comme le **type** de
l'argument, et c'est l'alias `Psr\Log\LoggerInterface` qui porte ce nom.

### Le type aliasé, pas la class du service

Exécuté : un argument typé par la class concrète du logger échoue.

```text
argument typé Symfony\Component\HttpKernel\Log\Logger
→ Cannot autowire service "…": argument "$l" of method "__construct()" references class
  "Symfony\Component\HttpKernel\Log\Logger" but no such service exists.
  Try changing the type-hint to "Psr\Log\LoggerInterface" instead.
```

Le message propose lui-même le bon type quand un alias existe pour un type
parent. C'est la règle : **on injecte le type que le bundle a aliasé**, pas la
class qui se trouve derrière.

### Le plus souvent une interface… pas toujours

Les types aliasés sont souvent des interfaces (`LoggerInterface`,
`UrlGeneratorInterface`, `ValidatorInterface`…), mais pas toujours : des
**classes** sont aliasées telles quelles. Exécuté, tous injectables :

- `Symfony\Component\HttpFoundation\RequestStack` (alias de `request_stack`) ;
- `Symfony\Component\Filesystem\Filesystem` (alias de `filesystem`) ;
- `Twig\Environment` (alias de `twig`, avec TwigBundle).

Quelques types courants, tous présents dans `debug:autowiring` sur une
application FrameworkBundle + TwigBundle 8.0.15 (exécuté) :
`RouterInterface`, `UrlGeneratorInterface`, `EventDispatcherInterface`,
`ValidatorInterface`, `SerializerInterface`, `HttpClientInterface`,
`LoggerInterface`, `TranslatorInterface`, `Environment`,
`ParameterBagInterface`, `RequestStack`, `CacheInterface`, `Filesystem`.
Cette liste illustre ; elle ne s'apprend pas : la command la donne.

## Concrètement dans ton application

- Besoin d'un outil du framework ? Lance `debug:autowiring` avec un mot-clé
  (`log`, `cache`, `url`, `mailer`), copie le type, écris-le dans ton
  constructor.
- Un type manque dans la liste ? Vérifie d'abord le **bundle** (`config/bundles.php`),
  puis la **section de configuration** (`enabled: false` quelque part ?).
- Une erreur « Cannot autowire … no such service exists » ? Lis la fin du
  message : il propose souvent le type à écrire.
- Tu veux savoir **ce qu'est** un service (class, tags, arguments) ?
  `debug:container <id>`. Tu veux savoir **comment l'injecter** ?
  `debug:autowiring`.

## À comprendre

- **Pourquoi des bundles plutôt qu'une liste fixe ?** Parce que chaque
  application n'installe que ce dont elle a besoin : les services suivent les
  bundles installés et leur configuration.
- **Pourquoi un alias de type ?** Parce que l'autowiring cherche un service par
  le nom du type demandé ; le bundle publie donc ses services sous le nom du type
  à injecter (souvent une interface, pour que tu ne dépendes pas de la class
  concrète).
- **Pourquoi la bibliothèque seule ne suffit-elle pas ?** Parce que `twig/twig`
  fournit des classes, pas des services : c'est le bundle qui les enregistre et
  les configure.

## À mémoriser

- Les services intégrés viennent des **bundles** installés (FrameworkBundle en
  tête), activés dans `config/bundles.php` par la recipe Flex.
- Pas de bundle, ou section désactivée → pas de service.
- `debug:autowiring [mot]` : les **types** à écrire ; `--all` ajoute les services
  sans alias ; aucun résultat → « No autowirable classes or interfaces found
  matching », exit code 1.
- `debug:container` : les **identifiers** des services.
- On écrit le **type aliasé** (`Psr\Log\LoggerInterface`), jamais la class
  concrète du service.
- Pas toujours une interface : `RequestStack`, `Filesystem`, `Twig\Environment`.
- Moyen mnémotechnique : **« container = QUI existe, autowiring = QUOI écrire »**.

## Pièges d'examen

**`debug:container` et `debug:autowiring` ne répondent pas à la même
question.** Le premier liste **tous** les identifiers du container, le second
les types utilisables comme type-hint. Apparaître dans `debug:container` ne rend
pas un service injectable par son type : `logger` y figure, sa class concrète
ne s'injecte pas.

**On type-hinte le type aliasé, pas la class du service.** Viser la class
concrète de `logger` échoue ; `LoggerInterface` fonctionne.

**« Toujours une interface » est faux** : `RequestStack` ou `Filesystem`
s'injectent par leur class, parce que c'est elle que le bundle a aliasée.

**Un service intégré vient d'un bundle, pas du framework en bloc.** Retirer le
bundle retire ses services : sans TwigBundle, pas d'`Environment` à injecter.

**Une section désactivée n'enregistre aucun service.** Avec
`framework.serializer.enabled: false`, `SerializerInterface` n'est plus
injectable, même si `symfony/serializer` est installé.

## Application : trois scénarios

**Scénario 1 — Trouver le type pour le cache.** Tu veux mettre en cache le
résultat d'un calcul.

*Démarche.* `php bin/console debug:autowiring cache` liste les types qui
contiennent « cache », dont `Symfony\Contracts\Cache\CacheInterface`. Tu
l'écris dans ton constructor.

**Scénario 2 — L'erreur sur le logger.** Un collègue a écrit
`Symfony\Component\HttpKernel\Log\Logger $logger` dans un constructor, et la
compilation échoue.

*Diagnostic.* C'est la class concrète, pas le type aliasé. Le message
d'erreur le dit : « Try changing the type-hint to "Psr\Log\LoggerInterface"
instead. »

**Scénario 3 — Le serializer a disparu.** Après une refonte de la
configuration, `SerializerInterface` ne s'injecte plus, alors que le package
est toujours dans `composer.json`.

*Diagnostic.* La section `framework.serializer` a été désactivée
(`enabled: false`) : FrameworkBundle n'enregistre plus ses services. *Correction.*
Retirer cette ligne, ou la passer à `true`.

## QCM d'entraînement

Cinq questions courtes ; choisis une lettre avant de déplier la correction.

**Question 1.** En écrivant un constructor, tu cherches quel type écrire pour recevoir le service de cache du framework. Quelle command répond directement ?

- **A.** `debug:autowiring cache`, qui liste les types utilisables contenant « cache »
- **B.** `debug:container cache`, qui liste les identifiers contenant « cache »
- **C.** `debug:config framework cache`, qui affiche la configuration du cache
- **D.** `cache:pool:list`, qui énumère tous les pools de cache déclarés dans l'application

<details>
<summary>Correction</summary>

**Bonne réponse : A.** `debug:autowiring` répond à « quel type puis-je
écrire ? », et l'argument filtre par recherche partielle.

B est le piège : `debug:container` liste des **identifiers** de services ; un
identifier n'est pas forcément un type que l'autowiring reconnaît. C montre des
valeurs de configuration, pas des types. D liste des pools, sans dire quel type
écrire dans un constructor.

</details>

**Question 2.** Le package `twig/twig` est installé, mais pas TwigBundle. Un service demande `Twig\Environment`. Que se passe-t-il ?

- **A.** L'autowiring instancie `Twig\Environment` directement depuis `vendor/`
- **B.** Le service reçoit `null` et l'erreur apparaît au premier rendu de template
- **C.** FrameworkBundle fournit un `Twig\Environment` minimal par défaut
- **D.** La compilation échoue : aucun service ne porte ce type sans le bundle

<details>
<summary>Correction</summary>

**Bonne réponse : D.** Exécuté : « Cannot autowire service … references class
"Twig\Environment" but no such service exists. » C'est TwigBundle qui enregistre
le service `twig` et son alias de type.

A est le piège : l'autowiring n'instancie pas une class de bibliothèque qui
n'est pas un service. C invente une dependency entre bundles. B suppose une
erreur tardive : c'est la compilation qui échoue.

</details>

**Question 3.** Quel argument de constructor reçoit le logger du framework par autowiring ?

- **A.** `Symfony\Component\HttpKernel\Log\Logger $logger`, la class du service `logger`
- **B.** `Psr\Log\LoggerInterface $logger`
- **C.** `string $logger`, avec la valeur `'logger'` comme identifier du service visé
- **D.** `ContainerInterface $container`, puis `$container->get('logger')` dans le body

<details>
<summary>Correction</summary>

**Bonne réponse : B.** `debug:autowiring` affiche
`Psr\Log\LoggerInterface - alias:logger` : c'est ce type que le bundle a aliasé.

A est le piège : la class concrète n'est pas un nom de service, et la compilation
échoue en proposant elle-même `LoggerInterface`. C passe une string, que rien
ne transforme en service. D fait de la récupération de services, et `logger`
est privé : `get()` échouerait sur le container compilé.

</details>

**Question 4.** Quelle affirmation sur les types que propose `debug:autowiring` est exacte ?

- **A.** Ce sont toujours des interfaces, jamais des classes concrètes
- **B.** Ce sont tous les identifiers du container, présentés autrement et triés par type de class
- **C.** Ce sont des types aliasés, parfois des classes comme `RequestStack`
- **D.** Ce sont uniquement les services que tu as déclarés dans `services.yaml`

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Le bundle choisit le type sous lequel publier chaque
service : `LoggerInterface` pour le logger, mais la class `RequestStack` pour
`request_stack`, ou `Filesystem` pour `filesystem` (exécuté : tous injectables).

A est le piège « toujours une interface ». B confond avec `debug:container`. D
oublie que la majorité des types listés viennent des bundles.

</details>

**Question 5.** `symfony/serializer` est installé, mais `config/packages/framework.yaml` contient `serializer: { enabled: false }`. Que devient `SerializerInterface` ?

- **A.** Il reste injectable : la présence du package suffit à enregistrer le service
- **B.** Il est injectable seulement dans les controllers, pas dans les services
- **C.** Il n'est plus injectable : FrameworkBundle n'enregistre pas les services d'une section désactivée
- **D.** Il est remplacé par un serializer vide, qui renvoie toujours un array vide quelle que soit la donnée

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Exécuté : avec la section désactivée, le type disparaît
de `debug:autowiring`. La section est activée par défaut quand le package est
installé, mais on peut la désactiver.

A est le piège : le package fournit des classes ; c'est la section activée de
FrameworkBundle qui enregistre le service. B invente une règle propre aux
controllers. D invente un service de remplacement.

</details>

## Fiche express

**Si tu dois retenir seulement 5 choses :**

- Les services intégrés viennent des **bundles** (FrameworkBundle en tête),
  activés par la recipe Flex dans `config/bundles.php`.
- Pas de bundle ou section **désactivée** → pas de service, même avec la
  bibliothèque installée.
- **container = QUI existe, autowiring = QUOI écrire** : `debug:autowiring [mot]`
  donne les types ; `--all` ajoute les services sans alias.
- On écrit le **type aliasé** (`Psr\Log\LoggerInterface`), jamais la class
  concrète ; l'erreur d'autowiring le suggère elle-même.
- Souvent une interface, **pas toujours** : `RequestStack`, `Filesystem`,
  `Twig\Environment`.

**En une phrase, pour tout reconstruire :**

```text
composer require → RECIPE active le BUNDLE → le bundle enregistre ses SERVICES
  (si sa section est activée) sous des ALIAS DE TYPE → debug:autowiring te donne
  le type → tu l'écris dans ton constructor
```

## Sources officielles

- [Service Container, « Fetching and using Services »](https://github.com/symfony/symfony-docs/blob/8.0/service_container.rst)
- [Flex recipes, activation du bundle](https://github.com/symfony/symfony-docs/blob/8.0/quick_tour/flex_recipes.rst)
- [FrameworkBundle 8.0, `DebugAutowiringCommand`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Command/DebugAutowiringCommand.php)
- [FrameworkBundle 8.0, `Resources/config/services.php`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Resources/config/services.php)
- [FrameworkBundle 8.0, `FrameworkExtension`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/FrameworkExtension.php)
- [FrameworkBundle 8.0, `Configuration`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/Configuration.php)
