---
id: CRS-rkq1d437dw5d
official_item: OIT-ehf5zdnmdb1j
title: "Deprecations best practices"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/contributing/code/conventions.rst"
    anchor: "contributing-code-conventions-deprecations"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
---

## Objectif

Savoir quand une fonctionnalité peut être dépréciée, comment la dépréciation est
signalée, et quand le code déprécié disparaît.

## La fenêtre

Deux règles encadrent le calendrier, et elles sont symétriques :

- une dépréciation ne peut être introduite que dans la **prochaine version
  mineure** du composant concerné — exceptionnellement dans une version
  antérieure encore maintenue, si elle est critique ;
- du code déprécié ne peut être **supprimé que dans la prochaine version
  majeure**, donc environ tous les deux ans.

Deux interdits en découlent : une **nouvelle** classe ne peut pas être
introduite dépréciée ni contenir des méthodes dépréciées, et une **nouvelle**
méthode ne peut pas être introduite dépréciée.

## Les deux signaux

Une dépréciation se déclare deux fois, à deux publics différents.

**Pour le lecteur**, une annotation PHPDoc, qui doit indiquer la version et,
autant que possible, le remplacement :

```php
/**
 * @deprecated since Symfony 5.1, use Replacement instead.
 */
```

Si le remplacement est dans un autre espace de noms, son nom pleinement qualifié
est obligatoire.

**Pour le code qui tourne**, un appel à `trigger_deprecation()`. La fonction
n'est pas dans le framework : elle est fournie par un paquet de contrats
minuscule, que toute bibliothèque peut ajouter sans dépendre de Symfony.

```bash
composer require symfony/deprecation-contracts
```

```php
trigger_deprecation('symfony/routing', '4.4', 'The "%s" class is deprecated, use "%s" instead.', Old::class, New::class);
```

Le premier argument est le paquet, le deuxième la version d'introduction, le
troisième un message formaté. Lorsque c'est une classe entière qui est
dépréciée, l'appel se place **après les déclarations `use`**, avant la classe.

## La trace écrite

Une dépréciation n'est complète que si elle est documentée aux trois endroits,
**dans la même pull request** :

1. le `CHANGELOG.md` du composant concerné ;
2. le `UPGRADE-<version mineure>.md` — la dépréciation ;
3. le `UPGRADE-<version majeure>.md` — la suppression à venir.

## Côté application

Cette mécanique a une conséquence pratique directe : monter d'abord jusqu'à la
**dernière version mineure** d'une branche majeure, y corriger toutes les
dépréciations signalées, puis passer à la majeure suivante — qui ne diffère que
par la suppression de ce qui était déprécié.

## Points clés

- Dépréciation en mineure, suppression en majeure suivante.
- Une nouvelle classe ou méthode ne peut pas naître dépréciée.
- `@deprecated` pour le lecteur, `trigger_deprecation()` pour l'exécution.
- `trigger_deprecation()` vient d'un paquet de contrats dédié, pas du framework.
- CHANGELOG + les deux UPGRADE, dans la même pull request.

## Sources officielles

- [Code Conventions, section « Deprecating Code »](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/contributing/code/conventions.rst)
