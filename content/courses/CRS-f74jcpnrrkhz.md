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
    symbol_or_lines: '"provide" key (psr/*-implementation entries)'
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/components/psr7.rst"
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
autoritative : elle déclare les interfaces standard que le framework fournit.

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

## Points clés

- Implémentées : PSR-3, 6, 11, 13, 14, 16, 18, 20.
- Suivies : PSR-4 (autoload) et PSR-12 (style).
- PSR-7 / PSR-17 : **non natif**, via `symfony/psr-http-message-bridge` plus une
  implémentation tierce.
- Le conteneur est PSR-11 ; le dispatcher est PSR-14.

## Sources officielles

- [composer.json de symfony/symfony, clé `provide`](https://raw.githubusercontent.com/symfony/symfony/8.0/composer.json)
- [The PSR-7 Bridge](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/components/psr7.rst)
