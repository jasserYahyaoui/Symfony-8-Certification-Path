---
id: CRS-6m32av3nr9eh
official_item: OIT-pk5s7cnhk776
title: "Release management and roadmap schedule"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/contributing/code/releases.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/contributing/code/releases.rst"
    anchor: "contributing-release-maintenance"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
---

## Objectif

Connaître le calendrier de publication de Symfony et les durées de maintenance.
Ce sont des chiffres : ils se retiennent ou ne se retiennent pas.

## Le modèle temporel

Symfony suit le *semantic versioning* et publie selon un modèle basé sur le
**temps**, non sur le contenu.

| Type | Fréquence | Contenu |
|---|---|---|
| **correctif** (7.1.4) | environ tous les mois | corrections de bugs seulement |
| **mineure** (7.1 → 7.2) | tous les **six mois**, en **mai** et en **novembre** | corrections, nouvelles fonctionnalités, nouvelles dépréciations, aucune rupture |
| **majeure** (7.0 → 8.0) | tous les **deux ans**, en **novembre des années impaires** | peut rompre la compatibilité |

**Aucune feuille de route n'est écrite à l'avance** : le projet est piloté par
la communauté, et une fonctionnalité demandée n'est pas planifiée.

## Les six mois d'une version

Deux phases : **quatre mois** de développement — ajout et amélioration de
fonctionnalités — puis **deux mois** de stabilisation : correction, préparation
de la publication, et attente que l'écosystème suive.

Pendant le développement, une fonctionnalité non prête à temps peut être
reportée au cycle suivant.

## LTS et durées de maintenance

**Depuis la branche 3.x**, une branche majeure compte **cinq versions
mineures** : X.0 à X.4. La
**dernière** — 5.4, 6.4, 7.4 — est la version *long-term support*. Les quatre
autres sont des versions standard.

| Type | Bugs corrigés pendant | Failles corrigées pendant |
|---|---|---|
| Standard | **8 mois** | **8 mois** |
| LTS | **3 ans** | **4 ans** |

L'asymétrie de la LTS se retient mal : les bugs cessent d'être corrigés un an
avant les failles.

## Les deux fenêtres de migration

Les durées de maintenance disent quand le support s'arrête. L'exposé des motifs
donne aussi le délai dont on **dispose pour migrer**, et ce ne sont pas les
mêmes chiffres :

| | Nouvelle version tous les… | Délai pour migrer |
|---|---|---|
| Standard | six mois | **deux mois** |
| LTS | deux ans | **un an** |

C'est le *dual maintenance mode* : les uns veulent les nouveautés vite, les
autres la stabilité, et chacun sait combien de temps il a.

## Le développement en double

Une majeure se développe **en parallèle** de la dernière mineure de la branche
précédente — 8.0 avec 7.4 — et les deux ont les **mêmes fonctionnalités**. Elles
ne diffèrent que par les dépréciations : présentes dans l'ancienne, supprimées
dans la nouvelle. C'est ce qui rend la montée praticable.

## PHP

La version **minimale** de PHP est fixée pour chaque version **majeure** de
Symfony, par **consensus de l'équipe centrale**, et documentée dans les
prérequis techniques.

La version **maximale** est la dernière publiée : toutes les versions de PHP
parues pendant la vie d'une version de Symfony sont supportées, **y compris les
nouvelles majeures de PHP**.

Deux précisions que la règle générale masque :

- **il existe une exception** — relever la version **mineure** minimale de PHP
  est possible dans une version **mineure** de Symfony, quand cela aide à
  corriger des problèmes importants ;
- pour une version de Symfony **hors support**, la dernière version de PHP
  supportée est celle en vigueur à la fin de vie. Les suivantes « peuvent
  fonctionner ou non ».

## Pièges d'examen

**Une LTS n'a pas une seule durée, elle en a deux.** Trois ans de correction de
bugs, **quatre** ans de correction de failles : la sécurité continue un an après
l'arrêt des bugs. Une version standard, elle, s'arrête à 8 mois pour les deux.

**Le calendrier est fixe, le contenu ne l'est pas.** Mineures en mai et
novembre, majeures en novembre des années impaires — mais **aucune feuille de
route** n'annonce leur contenu.

**8.0 et 7.4 ont les mêmes fonctionnalités.** Elles ne diffèrent que par les
dépréciations, présentes dans l'une, supprimées dans l'autre. Monter en version
majeure n'apporte donc rien de neuf par lui-même.

**« Le minimum PHP est fixé par majeure » souffre une exception.** Une mineure
de Symfony peut relever la **mineure** minimale de PHP si cela aide à corriger
des problèmes importants.

**Durée de maintenance ≠ délai de migration.** Une version standard est
maintenue 8 mois, mais le délai annoncé pour migrer est de **deux mois** ; une
LTS est maintenue 3 ou 4 ans, et le délai est d'**un an**.

## Tips d'examen

**Les chiffres vont par paires.** 4 + 2 mois dans un cycle ; 8 / 8 mois pour
une standard ; 3 / 4 ans pour une LTS ; 2 mois / 1 an pour migrer. Une question
qui ne donne qu'un seul nombre attend souvent l'autre.

**Mai et novembre**, et les majeures en **novembre des années impaires**.

## Points clés

- Mineure tous les 6 mois (mai / novembre), majeure tous les 2 ans (novembre des
  années impaires), correctif mensuel.
- 4 mois de développement + 2 mois de stabilisation.
- 5 mineures par branche ; la dernière (X.4) est LTS.
- Standard : 8 mois / 8 mois. LTS : **3 ans de bugs, 4 ans de sécurité**.
- Aucune feuille de route définie à l'avance.
- Délai pour migrer : **2 mois** en standard, **1 an** en LTS.
- Minimum PHP par majeure, **sauf** relèvement de mineure pour un correctif
  important ; maximum PHP = la dernière publiée.

## Sources officielles

- [The Release Process](https://github.com/symfony/symfony-docs/blob/8.0/contributing/code/releases.rst)
