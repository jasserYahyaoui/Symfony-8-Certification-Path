---
id: CRS-21ec5kwrcmkm
official_item: OIT-vk57zg2wpep7
title: "Backward compatibility promise"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/contributing/code/bc.rst"
    anchor: "using-symfony-code"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
---

## Objectif

Savoir ce que la promesse de rétrocompatibilité garantit **à l'utilisateur de
Symfony**, et surtout ce qu'elle ne garantit pas.

## Le principe

La promesse suit le *semantic versioning* : seule une version **majeure** peut
casser la compatibilité. Une version mineure ajoute des fonctionnalités et peut
déprécier, jamais casser.

Le point important est que la promesse n'est pas globale : elle dépend de la
**manière** dont on utilise le code.

## Utiliser, étendre, implémenter

**Utiliser** est toujours couvert : typer un argument avec une classe ou une
interface Symfony, instancier une classe, appeler une méthode publique, lire une
propriété publique.

**Implémenter une interface** est couvert. Si votre classe implémente une
interface Symfony, Symfony s'engage à ne pas casser votre code.

**Étendre une classe** est couvert pour l'essentiel — accéder à une propriété
protégée, appeler ou surcharger une méthode publique ou protégée — avec deux
exceptions notables :

| Dans une classe que vous étendez… | Garanti ? |
|---|---|
| ajouter une **nouvelle propriété** | **non** |
| ajouter une **nouvelle méthode** | **non** |
| appeler une méthode privée par réflexion | **non** |
| accéder à une propriété privée par réflexion | **non** |

La raison est simple : si vous ajoutez `getFoo()` et que Symfony ajoute
`getFoo()` avec une autre signature dans une version mineure, la collision est
inévitable. Le risque vous appartient.

## Les trois exclusions

Sont **hors** de la promesse :

- ce qui porte `@internal` — classe, interface, trait, méthode, propriété — et
  tout ce qui vit dans un espace de noms `*\Tests\` ;
- les **fonctionnalités expérimentales** ;
- les traductions internes de sécurité et de validation.

Une rupture est également tolérée lorsqu'elle est nécessaire pour corriger une
faille de sécurité.

## Le piège des arguments nommés

Les **noms de paramètres** ne sont couverts par la promesse que pour les
**constructeurs de classes d'attribut**. Partout ailleurs, appeler une méthode
Symfony avec des arguments nommés (`$service->method(timeout: 5)`) peut casser
lors d'une montée de version mineure, puisque le nom du paramètre peut changer.

## final et @final

Le mot-clé `final` interdit l'extension. L'annotation `@final` marque la même
intention **sans l'imposer techniquement** : le code fonctionne, mais l'étendre
sort de la promesse. `@final since Symfony x.y` signale une transition — la
classe n'est pas encore considérée finale.

## Points clés

- Utiliser et implémenter : couverts. Étendre : couvert **sauf** ajout de
  propriété ou de méthode.
- `@internal`, expérimental et traductions internes sont hors promesse.
- Arguments nommés garantis uniquement pour les constructeurs d'attributs.
- `@final` marque l'intention ; `final` l'impose.

## Sources officielles

- [Our Backward Compatibility Promise](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/contributing/code/bc.rst)
