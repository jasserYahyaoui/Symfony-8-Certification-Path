---
id: CRS-fak8bf9014br
official_item: OIT-vj24gwq6r1r4
title: "Handling legacy deprecated code"
content_level: MINIMAL
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-02"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/setup/upgrade_minor.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/setup/upgrade_minor.rst"
    anchor: "upgrade-minor-symfony-code"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    verified_at: "2026-09-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/setup/upgrade_major.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/setup/upgrade_major.rst"
    branch: "8.0"
    symbol_or_lines: "these notices are shown in the web dev toolbar; no deprecation notices are shown"
    verified_at: "2026-10-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/reference/configuration/framework.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/reference/configuration/framework.rst"
    branch: "8.0"
    symbol_or_lines: "php_errors; log"
    verified_at: "2026-10-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/contributing/code/conventions.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/contributing/code/conventions.rst"
    branch: "8.0"
    symbol_or_lines: "Deprecating Code"
    verified_at: "2026-10-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Contracts/Deprecation/function.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Contracts/Deprecation/function.php"
    branch: "8.0"
    symbol_or_lines: "@trigger_error(..., \\E_USER_DEPRECATED)"
    verified_at: "2026-10-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/ErrorHandler/ErrorHandler.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/ErrorHandler/ErrorHandler.php"
    branch: "8.0"
    symbol_or_lines: "E_DEPRECATED and E_USER_DEPRECATED levels never throw"
    verified_at: "2026-10-02"
---
## Objectif

Savoir ce que devient, à l'exécution, du code qui appelle une fonctionnalité
dépréciée — et pourquoi rien ne casse.

## Périmètre

Deux limites, à avoir en tête. Le **PHPUnit Bridge** est hors périmètre
d'examen, alors que c'est lui qui, en pratique, fait échouer une suite sur les
dépréciations. Et le détail de la façon de **déclarer** une dépréciation
appartient à *Deprecations best practices* (Symfony Architecture), prérequis de
cette page.

## Rappel : reconnaître une dépréciation

Deux marqueurs, pour deux publics :

| Marqueur | Pour qui |
|---|---|
| l'annotation PHPDoc `@deprecated`, avec la version | le lecteur du code |
| l'appel `trigger_deprecation()` | le code qui tourne |

Et un calendrier : une dépréciation n'entre que dans une **mineure** (sauf cas
critique sur une version encore maintenue), jamais sur une classe ou une méthode
**nouvelle** ; le code déprécié ne disparaît qu'à la **majeure** suivante.

## Une notice, et elle est silencée

Le point de fond tient en une phrase : une dépréciation Symfony est une notice
`E_USER_DEPRECATED` **silencée**. Lu dans `trigger_deprecation()` (contrats
Symfony, branche 8.0) : l'appel est `@trigger_error(…, \E_USER_DEPRECATED)`,
et le message commence par `Since <paquet> <version>:`.

Silencée veut dire déclenchée avec l'opérateur `@`. Conséquences directes :

- **rien n'échoue** — ni la requête, ni la commande, ni le test ;
- **rien n'apparaît dans la sortie**, ni page ni console ;
- la notice n'existe que si **un gestionnaire d'erreurs la recueille**.

Exécuté sur PHP 8.4, `display_errors` actif :

| Situation | Ce qu'on voit |
|---|---|
| `trigger_deprecation()`, aucun gestionnaire | rien ; le script continue |
| `trigger_error(…, E_USER_DEPRECATED)` sans `@` | `Deprecated: …` dans la sortie |
| gestionnaire d'erreurs de Symfony 8.0 + journal | une entrée de niveau `info` : `User Deprecated: Since acme/pkg 1.2: …` |

Le gestionnaire de Symfony ne lève **jamais** d'exception pour une dépréciation
(`ErrorHandler.php`, 8.0 : *E_DEPRECATED and E_USER_DEPRECATED levels never
throw*). Le code déprécié fonctionne jusqu'à la majeure suivante : c'est la
promesse de rétrocompatibilité, pas un oubli.

## Les voir

**En développement, dans le navigateur.** La documentation 8.0
(*Upgrading a Major Version*) le dit : ces notices sont montrées dans la barre de
débogage. C'est le gestionnaire d'erreurs de Symfony qui les recueille et les
journalise.

**Dans le journal.** Symfony traite `E_DEPRECATED` et `E_USER_DEPRECATED` comme
journalisables : l'option `framework.php_errors.log` vaut `true` par défaut, et
accepte une table qui associe un niveau de journal à chaque niveau d'erreur PHP.
Une dépréciation devient alors une entrée lisible et dénombrable.

**Dans les tests.** La même page le dit : PHPUnit seul ne montre aucune notice
de dépréciation. Exécuté sur PHPUnit 11.5 : un test qui appelle
`trigger_deprecation()` passe, `OK (1 test, 1 assertion)`, même avec
`failOnDeprecation="true"` — la notice est silencée.

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
- **La notice est silencée** : rien dans la sortie, et sans gestionnaire
  d'erreurs, aucune trace.
- **En dev, la barre de débogage les montre** — parce que le gestionnaire de
  Symfony les recueille, pas parce qu'elles s'affichent.
- **Le code déprécié fonctionne** jusqu'à la majeure suivante.
- **On corrige avant la majeure**, pas après.

## Points clés

- `E_USER_DEPRECATED`, silencée : aucun échec, rien dans la sortie.
- Visible seulement si un gestionnaire d'erreurs la recueille : barre de
  débogage en dev, journal au niveau `info`.
- `composer update` → fichier `UPGRADE` → corrections progressives.
- Le PHPUnit Bridge, outil habituel, est hors périmètre d'examen.

## Sources officielles

- [Upgrading a Minor Version](https://github.com/symfony/symfony-docs/blob/8.0/setup/upgrade_minor.rst)
- [Upgrading a Major Version](https://github.com/symfony/symfony-docs/blob/8.0/setup/upgrade_major.rst)
- [Framework configuration reference](https://github.com/symfony/symfony-docs/blob/8.0/reference/configuration/framework.rst)
- [Conventions — Deprecating Code](https://github.com/symfony/symfony-docs/blob/8.0/contributing/code/conventions.rst)
- [`trigger_deprecation()`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Contracts/Deprecation/function.php)
- [`ErrorHandler`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/ErrorHandler/ErrorHandler.php)
