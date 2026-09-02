---
id: CRS-x8j99kkmpsng
official_item: OIT-d1qsxrtv25kk
title: "Web Profiler, Web Debug Toolbar and Data collectors"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-02"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/profiler.rst"
    anchor: "data-collectors"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    verified_at: "2026-09-02"
---

## Objectif

Comprendre qui collecte quoi, où c'est stocké, et par quels points d'entrée on y
accède — y compris quand la barre n'apparaît pas.

## Prérequis

Le cycle du noyau HTTP et ses événements ; le débogage du code.

## Trois pièces, trois rôles

| Pièce | Rôle |
|---|---|
| **Collecteurs de données** | ramassent l'information pendant la requête |
| **Profileur** | stocke ces données sous un **jeton**, et les ressert |
| **Barre de débogage** | affiche un résumé en bas de la page |

La barre est une **vue** sur le profileur, pas une source. Retirer la barre ne
supprime pas les données.

## Jamais en production

La documentation est catégorique : **ne jamais activer le profileur en
production** — cela ouvrirait des vulnérabilités majeures. Il s'installe en
dépendance de développement :

```bash
composer require --dev symfony/profiler-pack
```

## La barre n'apparaît pas toujours

Elle n'est injectée que dans les réponses **HTML**. Pour une réponse JSON — le
cas d'une API — rien ne s'affiche, et c'est normal.

L'accès passe alors par deux en-têtes de réponse :

| En-tête | Contenu |
|---|---|
| `X-Debug-Token` | le jeton du profil |
| `X-Debug-Token-Link` | l'URL directe du profil |

L'URL `/_profiler` liste tous les profils. C'est la réponse à « je n'ai pas de
barre, comment je vois le profil ? ».

## Le stockage n'est pas permanent

Pour limiter la place occupée, les profils sont supprimés
**probabilistiquement après 2 jours**. On ne s'appuie donc pas sur un profil
ancien.

## Y accéder par le code

```php
$profile = $profiler->loadProfileFromResponse($response);

$token   = $response->headers->get('X-Debug-Token');
$profile = $profiler->loadProfile($token);

$tokens  = $profiler->find('', '/admin/', 10, '', '', '');
```

Le service `profiler` s'autocâble sur le type `Profiler`. En production il
n'existe pas : un contrôleur qui le reçoit doit accepter `?Profiler` et tester
la valeur nulle avant d'appeler `enable()` ou `disable()`.

## Les collecteurs

Ce sont des services. La liste réellement active se lit :

```bash
php bin/console debug:container --tag=data_collector
```

Un collecteur implémente `DataCollectorInterface`, ou étend
`AbstractDataCollector`, qui fournit la propriété `$this->data`.

Trois méthodes, et une contrainte de moment pour chacune :

| Méthode | Rôle |
|---|---|
| `collect()` | **appelée une seule fois**, sur `kernel.response` |
| `reset()` | vide l'état entre deux requêtes |
| `getName()` | l'identifiant, unique dans l'application |

Deux points que l'examen peut viser :

**`collect()` ne rassemble pas, elle ramasse.** Elle est appelée une fois ; le
travail de mesure doit avoir été fait par un service pendant la requête. Écrire
la collecte dans `collect()` ne marche pas.

**Les données sont sérialisées.** Un objet non sérialisable — une connexion, une
ressource — ne peut pas être stocké tel quel.

Pour une donnée qui n'existe qu'après la réponse, on implémente
`LateDataCollectorInterface` et sa méthode `lateCollect()`, appelée juste avant
la sérialisation, pendant **`kernel.terminate`**.

Avec `autoconfigure`, le collecteur est pris en compte sans configuration ;
sinon il faut poser l'étiquette `data_collector`.

## Pièges d'examen

**Le profileur est un outil de développement** — jamais activé en production.

**La barre n'est injectée que dans du HTML** ; sinon, `X-Debug-Token-Link`.

**`collect()` est appelée une fois, sur `kernel.response`** ;
`lateCollect()` sur `kernel.terminate`.

**Les profils disparaissent après environ 2 jours.**

**`getName()` doit être unique** : c'est la clé d'accès au collecteur.

## Points clés

- Collecteurs → profileur → barre : trois rôles distincts, la barre n'est
  qu'une vue.
- Dépendance `--dev` ; jamais en production.
- Sans HTML, passer par `X-Debug-Token-Link` ou `/_profiler`.
- `collect()` ramasse sur `kernel.response`, `lateCollect()` sur
  `kernel.terminate`, les données sont sérialisées.

## Sources officielles

- [Profiler](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/profiler.rst)
