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

Symfony suit le *semantic versioning* et publie selon un **modèle basé sur le
temps**, non sur le contenu.

| Type | Fréquence | Contenu |
|---|---|---|
| **correctif** (7.1.4) | environ tous les mois | corrections de bugs seulement |
| **mineure** (7.1 → 7.2) | tous les **six mois**, en **mai** et en **novembre** | corrections, nouvelles fonctionnalités, nouvelles dépréciations, aucune rupture |
| **majeure** (7.0 → 8.0) | tous les **deux ans**, en **novembre des années impaires** | peut rompre la compatibilité |

Il n'y a **aucune feuille de route écrite à l'avance** : le projet est piloté par
la communauté, et une fonctionnalité demandée n'est pas planifiée.

## Les six mois d'une version

Le cycle de six mois se découpe en deux phases :

- **quatre mois** de développement — ajout et amélioration de fonctionnalités ;
- **deux mois** de stabilisation — correction, préparation de la publication, et
  attente que l'écosystème suive.

Pendant la phase de développement, une fonctionnalité peut être retirée si elle
n'est pas prête à temps.

## LTS et durées de maintenance

Une branche majeure compte **cinq versions mineures** : X.0 à X.4. La
**dernière** — 5.4, 6.4, 7.4 — est la version *long-term support*. Les quatre
autres sont des versions standard.

| Type | Bugs corrigés pendant | Failles corrigées pendant |
|---|---|---|
| Standard | **8 mois** | **8 mois** |
| LTS | **3 ans** | **4 ans** |

L'asymétrie de la LTS est le détail qui se retient mal : la correction des bugs
s'arrête un an avant celle des failles de sécurité.

## Le développement en double

Une version majeure est développée **en parallèle** de la dernière mineure de la
branche précédente — 8.0 en même temps que 7.4. Les deux ont les **mêmes
fonctionnalités** ; elles ne diffèrent que par les dépréciations, présentes dans
l'ancienne, supprimées dans la nouvelle.

C'est ce qui rend la montée de version praticable : on monte jusqu'à la dernière
mineure, on corrige les dépréciations signalées, puis on passe à la majeure sans
autre travail.

## PHP

La version **minimale** de PHP est fixée pour chaque version majeure de Symfony.
La version **maximale** supportée est la dernière publiée : toutes les versions
de PHP sorties pendant la vie d'une version de Symfony sont supportées.

## Points clés

- Mineure tous les 6 mois (mai / novembre), majeure tous les 2 ans (novembre des
  années impaires), correctif mensuel.
- 4 mois de développement + 2 mois de stabilisation.
- 5 mineures par branche ; la dernière (X.4) est LTS.
- Standard : 8 mois / 8 mois. LTS : **3 ans de bugs, 4 ans de sécurité**.
- Aucune feuille de route définie à l'avance.

## Sources officielles

- [The Release Process](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/contributing/code/releases.rst)
