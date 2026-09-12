---
id: CRS-fak8bf9014br
official_item: OIT-vj24gwq6r1r4
title: "Handling legacy deprecated code"
content_level: MINIMAL
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-02"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/setup/upgrade_minor.rst"
    anchor: "upgrade-minor-symfony-code"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    verified_at: "2026-09-02"
---

## Objectif

Savoir ce que devient, à l'exécution, du code qui appelle une fonctionnalité
dépréciée — et pourquoi rien ne casse.

## Périmètre

Deux limites, à avoir en tête. Le **PHPUnit Bridge** est hors périmètre
d'examen, alors que c'est lui qui, en pratique, fait échouer une suite sur les
dépréciations. Et la façon de **déclarer** une dépréciation appartient à
*Deprecations best practices* (Symfony Architecture), prérequis de cette page,
qui ne la redit pas.

## Une notice, et elle est silencée

Le point de fond tient en une phrase : une dépréciation Symfony est une notice
`E_USER_DEPRECATED` **silencée**.

Silencée veut dire déclenchée avec l'opérateur `@`. Conséquences directes :

- **rien n'échoue** — ni la requête, ni la commande, ni le test ;
- **rien ne s'affiche** spontanément, même en développement ;
- la notice n'existe que si **un gestionnaire d'erreurs la recueille**.

Une application peut donc accumuler des centaines de dépréciations sans qu'aucun
signe n'apparaisse. Le code déprécié fonctionne jusqu'à la majeure suivante :
c'est la promesse de rétrocompatibilité, pas un oubli.

## Les voir

Il faut donc les faire remonter délibérément. Symfony traite `E_DEPRECATED` et
`E_USER_DEPRECATED` comme journalisables : la configuration associe un niveau de
journal à chaque niveau d'erreur PHP, et ces deux-là en font partie. Une
dépréciation devient alors une entrée de journal, lisible et dénombrable.

## Les corriger

La démarche que la documentation décrit pour une montée de version mineure :

1. `composer update "symfony/*"` ;
2. lire le fichier `UPGRADE` de la version atteinte, qui décrit les changements
   et les dépréciations ;
3. corriger le code en conséquence — **progressivement**, puisque rien n'est
   cassé pour l'instant.

L'ordre rend la montée sûre : atteindre la dernière mineure, y éteindre les
dépréciations, puis passer à la majeure — qui supprime ce qui était déprécié.

## Pièges d'examen

- **Une dépréciation ne fait échouer ni la requête ni le test** par elle-même.
- **La notice est silencée** : sans gestionnaire d'erreurs, elle est invisible.
- **Le code déprécié fonctionne** jusqu'à la majeure suivante.
- **On corrige avant de monter de majeure**, pas après.

## Points clés

- `E_USER_DEPRECATED`, silencée : aucun échec, aucun affichage spontané.
- Visible seulement si un gestionnaire d'erreurs la recueille.
- `composer update` → fichier `UPGRADE` → corrections progressives.
- Le PHPUnit Bridge, outil habituel, est hors périmètre d'examen.

## Sources officielles

- [Upgrading a Minor Version](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/setup/upgrade_minor.rst)
- [Framework configuration reference](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/reference/configuration/framework.rst)
