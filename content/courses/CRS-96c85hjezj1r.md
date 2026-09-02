---
id: CRS-96c85hjezj1r
official_item: OIT-dv5400dtksfg
title: "Verbosity levels"
content_level: MINIMAL
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Console/Output/OutputInterface.php"
    branch: "8.0"
    symbol_or_lines: "VERBOSITY_* constants"
    verified_at: "2026-09-01"
---

## Objectif

Adapter la quantité de sortie sans multiplier les options, et connaître les
six niveaux de Symfony 8.0.

## Les six niveaux

| Drapeau | Constante | Usage |
|---|---|---|
| `--silent` | `VERBOSITY_SILENT` | rien du tout, pas même les erreurs |
| `-q`, `--quiet` | `VERBOSITY_QUIET` | aucune sortie normale |
| *(défaut)* | `VERBOSITY_NORMAL` | le message utile |
| `-v` | `VERBOSITY_VERBOSE` | information supplémentaire |
| `-vv` | `VERBOSITY_VERY_VERBOSE` | détail, messages non essentiels |
| `-vvv` | `VERBOSITY_DEBUG` | tout, traces d'exception comprises |

`--silent` est le niveau le plus bas et il est **plus radical que `--quiet`** :
il supprime aussi les messages d'erreur.

## S'en servir

Le troisième argument de `writeln()` porte le niveau minimal :

```php
$output->writeln('détail utile au diagnostic', OutputInterface::VERBOSITY_VERBOSE);
```

Ou par un test, quand le calcul du message coûte cher :

```php
if ($output->isVerbose()) {
    $output->writeln($this->expensiveSummary());
}
```

`isQuiet()`, `isVerbose()`, `isVeryVerbose()`, `isDebug()` couvrent les autres
niveaux.

L'intérêt est qu'une seule commande sert au quotidien et au diagnostic : on ne
crée pas d'option `--debug` maison.

## Points clés

- Six niveaux ; `--silent` supprime même les erreurs, `-q` la sortie normale.
- `-v`, `-vv`, `-vvv` montent progressivement.
- Troisième argument de `writeln()`, ou `isVerbose()` et ses variantes.

## Sources officielles

- [`OutputInterface`, branche 8.0](https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Console/Output/OutputInterface.php)
- [Console Verbosity](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/console/verbosity.rst)
