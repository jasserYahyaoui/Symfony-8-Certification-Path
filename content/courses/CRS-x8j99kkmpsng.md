---
id: CRS-x8j99kkmpsng
official_item: OIT-d1qsxrtv25kk
title: "Web Profiler, Web Debug Toolbar and Data collectors"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-02"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/profiler.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/profiler.rst"
    anchor: "data-collectors"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    verified_at: "2026-09-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpKernel/EventListener/ProfilerListener.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/EventListener/ProfilerListener.php"
    branch: "8.0"
    symbol_or_lines: "onKernelResponse(); onKernelTerminate()"
    verified_at: "2026-10-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpKernel/Profiler/FileProfilerStorage.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/Profiler/FileProfilerStorage.php"
    branch: "8.0"
    symbol_or_lines: "random_int(1, 10); removeExpiredProfiles()"
    verified_at: "2026-10-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/WebProfilerBundle/EventListener/WebDebugToolbarListener.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/WebProfilerBundle/EventListener/WebDebugToolbarListener.php"
    branch: "8.0"
    symbol_or_lines: "injectToolbar()"
    verified_at: "2026-10-02"
outcome_sections:
  OUT-rvstpkee1rj6: "Trois pièces, trois roles"
  OUT-zexxpeg1gehg: "La barre n'apparaît pas toujours"
  OUT-q8696dzgbpts: "Les collectors"
---

## Objectif

Comprendre qui collecte quoi, où c'est stocké, et par quels entry points on y
accède — y compris quand la barre n'apparaît pas.

## Prérequis

Le cycle du kernel HTTP et ses events ; le debug du code.

## Trois pièces, trois roles

| Pièce | Role |
|---|---|
| **Data collectors** | ramassent l'information pendant la Request |
| **Profiler** | stocke ces données sous un **token**, et les ressert |
| **Debug toolbar** | affiche un résumé en bas de la page |

La barre est une **vue** sur le profiler, pas une source. Retirer la barre ne
supprime pas les données.

## Jamais en production

La documentation est catégorique : **ne jamais activer le profiler en
production** — cela ouvrirait des vulnérabilités majeures. Il s'installe en
dependency de développement :

```bash
composer require --dev symfony/profiler-pack
```

## La barre n'apparaît pas toujours

Elle n'est injectée que dans les Responses **HTML**. Pour une Response JSON — le
cas d'une API — rien ne s'affiche, et c'est normal. Exécuté sur
WebProfilerBundle 8.0.15, profiler actif :

| Response | Barre injectée | `X-Debug-Token-Link` |
|---|---|---|
| HTML avec `</body>` | oui | présent |
| JSON | non | présent |
| redirect 302 | non | présent |
| HTML sans tag `</body>` | non | présent |
| HTML demandé en XHR | non | présent |

L'injection se fait juste avant `</body>` : sans ce tag, il n'y a pas
d'endroit où l'insérer. **Le profil, lui, existe dans tous les cas.**

L'accès passe alors par deux headers de Response :

| Header | Contenu |
|---|---|
| `X-Debug-Token` | le token du profil |
| `X-Debug-Token-Link` | l'URL directe du profil |

L'URL `/_profiler` liste tous les profils. C'est la solution à « je n'ai pas de
barre, comment je vois le profil ? ».

## Le stockage n'est pas permanent

Pour limiter la place occupée, les profils sont supprimés
**probabilistiquement après 2 jours**. On ne s'appuie donc pas sur un profil
ancien.

Lu dans `FileProfilerStorage` (8.0) : à chaque écriture d'un nouveau profil, une
chance sur dix (`random_int(1, 10)`) déclenche la purge des profils de plus de
`2 * 86400` secondes. Un profil ancien peut donc survivre tant que peu de
Requests sont profilées.

## Y accéder par le code

```php
$profile = $profiler->loadProfileFromResponse($response);

$token   = $response->headers->get('X-Debug-Token');
$profile = $profiler->loadProfile($token);

$tokens  = $profiler->find('', '/admin/', 10, '', '', '');
```

Le service `profiler` s'autocâble sur le type `Profiler`. En production il
n'existe pas : un controller qui le reçoit doit accepter `?Profiler` et tester
la valeur nulle avant d'appeler `enable()` ou `disable()`.

## Les collectors

Ce sont des services. La liste réellement active se lit :

```bash
php bin/console debug:container --tag=data_collector
```

Un collector implémente `DataCollectorInterface`, ou étend
`AbstractDataCollector`, qui fournit la property `$this->data`.

Trois methods, et une constraint de moment pour chacune :

| Method | Role |
|---|---|
| `collect()` | **appelée une seule fois**, sur `kernel.response` |
| `reset()` | vide l'état entre deux Requests |
| `getName()` | l'identifier, unique dans l'application |

Deux points que l'examen peut viser :

**`collect()` ne rassemble pas, elle ramasse.** Elle est appelée une fois ; le
travail de mesure doit avoir été fait par un service pendant la Request. Écrire
la collecte dans `collect()` ne marche pas.

**Les données sont sérialisées.** Un object non sérialisable — une connexion, une
ressource — ne peut pas être stocké tel quel.

Pour une donnée qui n'existe qu'après la Response, on implémente
`LateDataCollectorInterface` et sa method `lateCollect()`, appelée juste avant
la serialization, pendant **`kernel.terminate`**.

Lu dans `ProfilerListener` (8.0) : `onKernelResponse()` appelle
`Profiler::collect()`, donc chaque `collect()` ; `onKernelTerminate()` appelle
`Profiler::saveProfile()`, qui appelle chaque `lateCollect()` puis écrit le
profil.

Avec `autoconfigure`, le collector est pris en compte sans configuration ;
sinon il faut poser le tag `data_collector`.

## Pièges d'examen

**Le profiler est un outil de développement** — jamais activé en production.

**La barre n'est injectée que dans du HTML qui a un `</body>`**, hors
redirect et hors XHR ; sinon, `X-Debug-Token-Link`, toujours présent.

**`collect()` est appelée une fois, sur `kernel.response`** ;
`lateCollect()` sur `kernel.terminate`.

**Les profils disparaissent après environ 2 jours** — purge déclenchée une
fois sur dix, à l'écriture d'un profil.

**`getName()` doit être unique** : c'est la clé d'accès au collector.

## Points clés

- Collectors → profiler → barre : trois roles distincts, la barre n'est
  qu'une vue.
- Dependency `--dev` ; jamais en production.
- Sans HTML, passer par `X-Debug-Token-Link` ou `/_profiler`.
- `collect()` ramasse sur `kernel.response`, `lateCollect()` sur
  `kernel.terminate`, les données sont sérialisées.

## Sources officielles

- [Profiler](https://github.com/symfony/symfony-docs/blob/8.0/profiler.rst)
- [`ProfilerListener`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/EventListener/ProfilerListener.php)
- [`FileProfilerStorage`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/Profiler/FileProfilerStorage.php)
- [`WebDebugToolbarListener`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/WebProfilerBundle/EventListener/WebDebugToolbarListener.php)
