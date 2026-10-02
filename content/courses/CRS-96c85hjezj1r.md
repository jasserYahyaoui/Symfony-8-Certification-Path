---
id: CRS-96c85hjezj1r
official_item: OIT-dv5400dtksfg
title: "Verbosity levels"
content_level: MINIMAL
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-02"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Console/Output/OutputInterface.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Console/Output/OutputInterface.php"
    branch: "8.0"
    symbol_or_lines: "VERBOSITY_* constants"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Console/Output/OutputInterface.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Console/Output/OutputInterface.php"
    branch: "8.0"
    symbol_or_lines: "writeln(string|iterable $messages, int $options = 0); isSilent()"
    verified_at: "2026-10-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Console/Application.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Console/Application.php"
    branch: "8.0"
    symbol_or_lines: "configureIO() — options before SHELL_VERBOSITY"
    verified_at: "2026-10-02"
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
il supprime aussi les messages d'erreur. Exécuté avec Console 8.0.15, dans un
vrai processus, une commande qui écrit trois lignes puis lève une exception :

| Option | `normal` | `verbose-only` | `quiet-level` | erreur sur STDERR |
|---|---|---|---|---|
| aucune | oui | non | oui | oui |
| `-q` | non | non | **oui** | **oui** |
| `--silent` | non | non | non | **non** |
| `-v`, `-vvv` | oui | oui | oui | oui |

Un message marqué `VERBOSITY_QUIET` passe donc sous `-q` : c'est le seuil le
plus bas qu'un message puisse porter pour rester visible.

## S'en servir

Le **second** argument de `writeln()` — `$options`, le troisième de
`write()` — porte le niveau minimal :

```php
$output->writeln('détail utile au diagnostic', OutputInterface::VERBOSITY_VERBOSE);
```

Ou par un test, quand le calcul du message coûte cher :

```php
if ($output->isVerbose()) {
    $output->writeln($this->expensiveSummary());
}
```

`isSilent()`, `isQuiet()`, `isVerbose()`, `isVeryVerbose()`, `isDebug()`
couvrent les autres niveaux.

L'intérêt est qu'une seule commande sert au quotidien et au diagnostic : on ne
crée pas d'option `--debug` maison.

## Pièges d'examen

**`--silent` masque les erreurs, il ne les perd pas.** Elles restent écrites par
le logger Symfony ; seule la console se tait.

**`SHELL_VERBOSITY` fixe le niveau globalement, mais `-q` et `-v` l'emportent
sur lui.** La variable vaut `-2` pour `--silent`, `-1` pour `--quiet`, `0`, `1`,
`2`, `3` pour les suivants. Exécuté : `SHELL_VERBOSITY=1` seul affiche le
message verbeux ; avec `-q`, plus rien d'autre que `quiet-level`.

**`-q` et `--silent` rendent aussi l'entrée non interactive** : `interact()`
n'est pas appelée (voir *Console component*).

**Le niveau passé à `writeln()` est un seuil, pas un filtre exact.** Un
message marqué `VERBOSITY_VERBOSE` s'affiche à `-v`, `-vv` **et** `-vvv`.

**`writeln()` n'a que deux paramètres** : le niveau est le second.

## Points clés

- Six niveaux ; `--silent` supprime même les erreurs, `-q` la sortie normale.
- `-v`, `-vv`, `-vvv` montent progressivement.
- Second argument de `writeln()`, ou `isVerbose()` et ses variantes.

## Sources officielles

- [`OutputInterface`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Console/Output/OutputInterface.php)
- [Console Verbosity](https://github.com/symfony/symfony-docs/blob/8.0/console/verbosity.rst)
- [`Application::configureIO`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Console/Application.php)
