---
id: CRS-p6yhxmpxrzbs
official_item: OIT-78k7kbfpgfxt
title: "Cache"
content_level: DEEP
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-14"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/cache.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/cache.rst"
    anchor: "cache-basic-usage"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    verified_at: "2026-09-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Contracts/Cache/CacheTrait.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Contracts/Cache/CacheTrait.php"
    branch: "8.0"
    symbol_or_lines: "get(string $key, callable $callback, ?float $beta = null, ?array &$metadata = null)"
    verified_at: "2026-10-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Cache/Adapter/TagAwareAdapter.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Cache/Adapter/TagAwareAdapter.php"
    branch: "8.0"
    symbol_or_lines: "invalidateTags()"
    verified_at: "2026-10-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Cache/Adapter/AbstractAdapter.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Cache/Adapter/AbstractAdapter.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "createSystemCache(): PhpFilesAdapter alone, or ChainAdapter([ApcuAdapter, PhpFilesAdapter]) when APCu is supported"
    verified_at: "2026-10-09"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Cache/Traits/AbstractAdapterTrait.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Cache/Traits/AbstractAdapterTrait.php"
    branch: "8.0"
    symbol_or_lines: "getItem(): commits a deferred item of the same key; saveDeferred(); commit()"
    verified_at: "2026-10-14"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Cache/Psr16Cache.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Cache/Psr16Cache.php"
    branch: "8.0"
    symbol_or_lines: "Psr16Cache: PSR-16 over a PSR-6 pool; Adapter/Psr16Adapter the reverse"
    verified_at: "2026-10-14"
---

## Objectif

Mettre en cache une valeur coûteuse à calculer, et savoir pourquoi l'API de
Symfony n'a pas de méthode pour écrire.

## Périmètre

Trois items voisins portent le mot « cache » et ne se recouvrent pas :

| Item | Ce qu'il possède |
|---|---|
| *Caching* (HTTP) | les en-têtes du protocole |
| *HTTP Caching* (Miscellaneous) | le proxy inverse de Symfony |
| **cette page** | le **composant Cache** : mettre des valeurs en cache dans le code |

## Prérequis

L'injection de dépendances et l'autocâblage.

## Quatre mots

| Terme | Définition |
|---|---|
| **Item** | une unité stockée : une clé, une valeur |
| **Pool** | un dépôt logique d'items ; toutes les opérations passent par lui |
| **Adaptateur** | la classe qui implémente le stockage réel — fichiers, Redis… |
| **Fournisseur** | le service de connexion au stockage, notion propre au framework |

Les pools du framework ne se heurtent pas, même sur un stockage partagé :
chacun reçoit un **espace de noms** dérivé de son nom. Exécuté, deux
`FilesystemAdapter` construits avec le même espace de noms et le même dossier
**partagent** leurs clés.

## Deux approches, une recommandée

Le composant en propose deux :

- les **Cache Contracts** — un rappel de recalcul, recommandé par la
  documentation : moins de code, et **protection contre la ruée par défaut** ;
- **PSR-6** — l'API générique à pools et items, utile quand une bibliothèque
  tierce l'exige ;
- **PSR-16** — un cache « simple », sans objet item.

Les adaptateurs du composant parlent à la fois les Cache Contracts et PSR-6.
On choisit l'API selon le **contrat qu'attend le code consommateur**.

## L'API qui surprend

Les Cache Contracts ne définissent que **deux** méthodes : `get()` et
`delete()`.

**Il n'y a pas de `set()`** — et ce n'est pas un oubli : `get()` lit *et* écrit.

```php
use Symfony\Contracts\Cache\ItemInterface;

$value = $cache->get('my_cache_key', function (ItemInterface $item): string {
    $item->expiresAfter(3600);
    return $this->computeSomethingExpensive();
});

$cache->delete('my_cache_key');
```

Le second argument est un **appelable qui produit la valeur** : au manque, et
aussi sur une clé **présente** quand le recalcul anticipé l'élit ou que `beta`
vaut `INF`. Un succès ordinaire le saute. D'où l'absence d'écriture séparée :
la valeur et la façon de la produire sont déclarées au même endroit.

Dans une application Symfony, typer un argument avec `CacheInterface` injecte le
pool `cache.app` par autocâblage.

Le rappel offre deux capacités que l'examen peut viser :

- `$item->isHit()` **à l'intérieur** du rappel rend `true` lorsque la valeur est
  recalculée **en avance** sur son échéance, par le mécanisme anti-ruée ;
- un second paramètre `bool &$save`, **passé par référence** : le mettre à
  `false` empêche l'enregistrement de la valeur retournée. C'est la façon de ne
  pas cacher un résultat jugé douteux.

## PSR-6 : écrire soi-même

Ici, rien n'est écrit sans `save()`. Exécuté sur Cache 8.0.15, `FilesystemAdapter` :

| Opération | Effet |
|---|---|
| `getItem('k')` sur une clé absente | `isHit()` faux, `get()` rend `null` |
| `$item->set('x')` sans `save()` | rien n'est stocké |
| `set(null)` puis `save()` | `isHit()` **vrai**, `get()` rend `null` |
| `saveDeferred()` | invisible d'un autre pool jusqu'au `commit()` |
| `deleteItem('k')` | l'item disparaît |

`null` ne dit donc pas « absent » : seul `isHit()` le dit. Piège exécuté : un
`getItem()` du **même** pool sur une clé différée la valide aussitôt.
`ArrayAdapter`, lui, enregistre tout de suite : son `saveDeferred()` appelle
`save()`.

`expiresAfter()` et `expiresAt()` fixent l'échéance d'un item.

## PSR-16 et les ponts

`get('k', 'défaut')` rend le défaut au manque ; `has()` distingue un `null`
stocké d'une absence. `Psr16Cache` fait d'un pool PSR-6 un cache PSR-16 ;
`Psr16Adapter` fait l'inverse, et son résultat parle aussi les Cache Contracts.

## La ruée sur le cache

Quand une clé coûteuse expire, les requêtes simultanées la recalculent toutes.
Les Cache Contracts l'évitent **sans configuration** :

- un **verrou** : un seul processus PHP recalcule une clé donnée, par machine ;
- une **expiration anticipée probabiliste** : au lieu d'attendre l'échéance, un
  utilisateur au hasard se voit simuler un manque et recalcule, pendant que les
  autres continuent d'être servis depuis le cache.

Le troisième paramètre de `get()`, le **`beta`**, règle ce second mécanisme :
`1.0` par défaut, une valeur plus haute recalcule plus tôt, **`0` le désactive**
— sans désactiver le cache — et **`INF` force un recalcul immédiat**. Seul un
item doté d'une échéance peut être élu en avance.

## Les pools du framework

Deux pools existent toujours, et les confondre a des conséquences au
déploiement :

| Pool | Usage | Adaptateur par défaut |
|---|---|---|
| `cache.app` | données applicatives générales | `cache.adapter.filesystem` |
| `cache.system` | ce qui **dérive du code source** | `cache.adapter.system` |

`cache.system` est réservé aux entrées régénérables au préchauffage et qui ne
changent qu'avec le code — donc au déploiement, pas à l'exécution. `cache.app`
n'a pas besoin d'être vidé au déploiement ; la documentation recommande d'y
mettre Redis lorsque c'est possible, pour que les données survivent au
déploiement et soient partagées entre plusieurs serveurs.

`cache.adapter.system` n'est pas un stockage : il **choisit** dynamiquement le
meilleur disponible — fichiers PHP, chaînés derrière APCu quand il est là.

Les adaptateurs préconfigurés couvrent `apcu`, `array`, `filesystem`,
`memcached`, `pdo`, `psr6`, `redis` et `valkey`, ces deux derniers ayant une
variante optimisée pour les étiquettes.

## Invalider par étiquette

```php
$value = $pool->get('item_0', function (ItemInterface $item): string {
    $item->tag(['foo', 'bar']);
    return 'debug';
});

$pool->invalidateTags(['bar']);
```

`ItemInterface::tag()` attache une étiquette ; `invalidateTags()`, de
`TagAwareCacheInterface`, supprime **tous** les items qui la portent. C'est la
réponse au besoin « effacer plusieurs clés d'un coup » quand on ne connaît pas
la liste des clés.

## Pièges d'examen

**Il n'existe pas de `set()`** dans les Cache Contracts ; en PSR-6, `set()` sans
`save()` n'écrit rien.

**`null` n'est pas une absence** : `isHit()` en PSR-6, `has()` en PSR-16.

**`$save` est passé par référence** ; le mettre à `false` empêche
l'enregistrement.

**Le rappel ne tourne pas qu'au manque.** Exécuté : avec `beta` à `INF`, il
tourne sur une clé présente, et `isHit()` y rend `true`.

**`cache.system` n'est pas un `cache.app` plus rapide** : il suppose des données
dérivables du code source.

## Points clés

- Item, pool, adaptateur, fournisseur ; un espace de noms isole chaque pool.
- Cache Contracts recommandés : `get()` et `delete()`, pas de `set()`.
- Le rappel tourne au manque ou au recalcul anticipé, expose `isHit()` et `&$save`.
- Anti-ruée intégré : verrou plus expiration anticipée réglée par `beta`.
- `cache.app` pour les données, `cache.system` pour ce qui dérive du code.
- `tag()` puis `invalidateTags()` pour effacer un groupe.
- PSR-6 : `save()`, `saveDeferred()` + `commit()` ; PSR-16 : défaut et `has()`.

## Sources officielles

- [Cache](https://github.com/symfony/symfony-docs/blob/8.0/cache.rst)
