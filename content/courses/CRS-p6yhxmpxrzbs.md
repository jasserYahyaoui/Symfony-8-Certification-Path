---
id: CRS-p6yhxmpxrzbs
official_item: OIT-78k7kbfpgfxt
title: "Cache"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-02"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/cache.rst"
    anchor: "cache-basic-usage"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    verified_at: "2026-09-02"
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

Le point à retenir sur les pools : **les clés de deux pools ne se heurtent
jamais**, même si les deux pools partagent le même stockage. C'est ce qui permet
d'en créer autant qu'on veut sans coordonner les noms de clés.

## Deux approches, une recommandée

Le composant en propose deux :

- les **Cache Contracts** — un rappel de recalcul, recommandé par la
  documentation : moins de code, et **protection contre la ruée par défaut** ;
- **PSR-6** — l'API générique à pools et items, utile quand une bibliothèque
  tierce l'exige.

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

Le second argument est un **appelable exécuté seulement en cas de manque**. En
cas de succès, il n'est pas appelé du tout — d'où l'absence d'écriture séparée :
la valeur et la façon de la produire sont déclarées au même endroit, et il
devient impossible de lire une clé sans savoir la reconstruire.

Dans une application Symfony, typer un argument avec `CacheInterface` injecte le
pool `cache.app` par autocâblage.

Le rappel offre deux capacités que l'examen peut viser :

- `$item->isHit()` **à l'intérieur** du rappel rend `true` lorsque la valeur est
  recalculée **en avance** sur son échéance, par le mécanisme anti-ruée ;
- un second paramètre `bool &$save`, **passé par référence** : le mettre à
  `false` empêche l'enregistrement de la valeur retournée. C'est la façon de ne
  pas cacher un résultat jugé douteux.

## La ruée sur le cache

Quand une clé coûteuse expire, toutes les requêtes simultanées se mettent à la
recalculer. Les Cache Contracts en protègent de deux manières, **sans rien
configurer** :

- un **verrou** : un seul processus PHP recalcule une clé donnée, par machine ;
- une **expiration anticipée probabiliste** : au lieu d'attendre l'échéance, un
  utilisateur au hasard se voit simuler un manque et recalcule, pendant que les
  autres continuent d'être servis depuis le cache.

Le troisième paramètre de `get()`, le **`beta`**, règle ce second mécanisme :
`1.0` par défaut, une valeur plus haute recalcule plus tôt, **`0` le désactive**
et **`INF` force un recalcul immédiat**.

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
meilleur disponible — fichiers PHP, ou APCu quand il est là.

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

**Il n'existe pas de `set()`** dans les Cache Contracts.

**Le rappel ne s'exécute qu'en cas de manque.**

**`$save` est passé par référence** ; le mettre à `false` empêche
l'enregistrement.

**`beta = 0` désactive** le recalcul anticipé, `INF` le force.

**Les clés de deux pools ne se heurtent jamais.**

**`cache.system` n'est pas un `cache.app` plus rapide** : il suppose des données
dérivables du code source.

## Points clés

- Item, pool, adaptateur, fournisseur ; les pools sont étanches entre eux.
- Cache Contracts recommandés : `get()` et `delete()`, pas de `set()`.
- Le rappel tourne au manque, expose `isHit()` et `&$save`.
- Anti-ruée intégré : verrou plus expiration anticipée réglée par `beta`.
- `cache.app` pour les données, `cache.system` pour ce qui dérive du code.
- `tag()` puis `invalidateTags()` pour effacer un groupe.

## Sources officielles

- [Cache](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/cache.rst)
