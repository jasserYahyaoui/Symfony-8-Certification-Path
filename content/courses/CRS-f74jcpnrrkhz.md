---
id: CRS-f74jcpnrrkhz
official_item: OIT-kcj9a5846b1s
title: "Framework interoperability and PSRs"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/composer.json"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/composer.json"
    symbol_or_lines: '"provide" key (psr/*-implementation entries)'
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/components/psr7.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/components/psr7.rst"
    anchor: "the-psr-7-bridge"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
---

## Objectif

Savoir quelles recommandations du PHP-FIG Symfony **implémente**, lesquelles il
**suit**, et laquelle il ne supporte que par un pont.

## Les PSR implémentées

La clé `provide` du `composer.json` de `symfony/symfony` est la réponse
autoritative. Elle compte **quinze** entrées, et les PSR n'en sont qu'une part :
**huit** `psr/*`, **cinq** contrats Symfony, **deux** HTTPlug.

| PSR | Objet | Composant Symfony |
|---|---|---|
| **PSR-3** | Logger | intégration de journalisation |
| **PSR-6** | Cache | Cache |
| **PSR-11** | Container | DependencyInjection |
| **PSR-13** | Link | WebLink |
| **PSR-14** | Event Dispatcher | EventDispatcher |
| **PSR-16** | Simple Cache | Cache |
| **PSR-18** | HTTP Client | HttpClient |
| **PSR-20** | Clock | Clock |

Concrètement : le conteneur de services de Symfony **est** un
`Psr\Container\ContainerInterface`, et son dispatcher d'événements **est** un
`Psr\EventDispatcher\EventDispatcherInterface`. Une bibliothèque tierce qui type
contre ces interfaces fonctionne sans adaptateur.

### Les versions déclarées ne sont pas les mêmes

`provide` ne nomme pas seulement l'interface, il dit **quelles versions majeures**
Symfony satisfait. Elles diffèrent, et c'est vérifiable ligne à ligne :

| Entrée | Versions |
|---|---|
| `psr/log-implementation` | `1.0`, `2.0`, `3.0` |
| `psr/simple-cache-implementation` | `1.0`, `2.0`, `3.0` |
| `psr/cache-implementation` | `2.0`, `3.0` — **pas** `1.0` |
| `psr/container-implementation`, `psr/link-implementation` | `1.0`, `2.0` |
| `psr/clock-implementation`, `psr/event-dispatcher-implementation`, `psr/http-client-implementation` | `1.0` seulement |

## Les deux autres familles de `provide`

**Les contrats Symfony** — `symfony/cache-implementation`,
`event-dispatcher`, `http-client`, `service`, `translation`. Ce sont des
abstractions publiées à part, dans des paquets minuscules et sans dépendance.
Une bibliothèque qui exige `symfony/service-implementation` fonctionne avec
Symfony sans en dépendre — la même logique que le paquet de contrats qui porte
`trigger_deprecation()`, traité dans la page sur les dépréciations.

**HTTPlug** — `php-http/client-implementation` et
`php-http/async-client-implementation`, déclarés à `*`. C'est l'abstraction
client HTTP qui précède PSR-18 ; HttpClient satisfait les deux.

## Les PSR suivies

Deux recommandations ne s'implémentent pas — elles se respectent.

- **PSR-4**, autoloading : c'est elle qui fait correspondre `src/` à l'espace de
  noms `App\`.
- **PSR-12**, style de code : les standards de codage de Symfony sont fondés sur
  PSR-12 et PSR-4.

## Le cas PSR-7

**PSR-7** (messages HTTP) et **PSR-17** (fabriques de messages) ne sont **pas**
implémentées nativement. HttpFoundation a son propre modèle `Request` /
`Response`, antérieur à PSR-7 et mutable là où PSR-7 est immuable.

L'interopérabilité passe donc par un **pont** dédié,
`symfony/psr-http-message-bridge`, qui convertit dans les deux sens. Le pont ne
fournit pas d'implémentation PSR-7 : il faut lui en installer une, par exemple
`nyholm/psr7`.

C'est la distinction à tenir : pour les autres PSR, Symfony **est** l'implémentation ;
pour PSR-7, il faut convertir.

## Pièges d'examen

**PSR-7 n'est pas implémentée.** C'est la seule de la liste qui demande un
pont — `symfony/psr-http-message-bridge` — **plus** une implémentation tierce,
que le pont ne fournit pas. Pour toutes les autres, Symfony *est*
l'implémentation.

**Implémentée et suivie ne sont pas la même chose.** PSR-4 et PSR-12 se
respectent, elles ne s'implémentent pas : elles ne figurent pas dans la clé
`provide`.

**Le conteneur *est* un `Psr\Container\ContainerInterface`.** Il n'y a pas
d'adaptateur à écrire pour une bibliothèque qui type contre PSR-11 ou PSR-14.

**`provide` ne contient pas que des PSR.** Sur quinze entrées, sept ne sont pas
des `psr/*` : cinq contrats Symfony et deux HTTPlug.

**PSR-6 est déclarée à partir de `2.0`.** Toutes les PSR implémentées ne
couvrent pas les mêmes versions majeures : trois pour PSR-3 et PSR-16, une
seule pour PSR-20, PSR-14 et PSR-18.

## Tips d'examen

**Trois verbes pour trois statuts.** Symfony **implémente** (PSR-3, 6, 11, 13,
14, 16, 18, 20), **suit** (PSR-4, PSR-12), **ponte** (PSR-7 / PSR-17).

**Le nom du paquet virtuel donne la réponse.** `psr/<nom>-implementation` dans
`provide` ⇒ implémentée. Absent ⇒ pas implémentée, quoi qu'en dise l'intuition.

## Points clés

- Implémentées : PSR-3, 6, 11, 13, 14, 16, 18, 20.
- Suivies : PSR-4 (autoload) et PSR-12 (style).
- PSR-7 / PSR-17 : **non natif**, via `symfony/psr-http-message-bridge` plus une
  implémentation tierce.
- Le conteneur est PSR-11 ; le dispatcher est PSR-14.
- `provide` : **15** entrées — 8 `psr/*`, 5 contrats Symfony, 2 HTTPlug.
- Les versions déclarées diffèrent : `1.0|2.0|3.0` pour PSR-3, `1.0` seul pour
  PSR-20.

## Sources officielles

- [composer.json de symfony/symfony, clé `provide`](https://github.com/symfony/symfony/blob/8.0/composer.json)
- [The PSR-7 Bridge](https://github.com/symfony/symfony-docs/blob/8.0/components/psr7.rst)
