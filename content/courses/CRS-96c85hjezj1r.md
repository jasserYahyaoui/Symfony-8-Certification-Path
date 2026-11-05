---
id: CRS-96c85hjezj1r
official_item: OIT-dv5400dtksfg
title: "Verbosity levels"
content_level: MINIMAL
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-26"
format: pedagogical
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
outcome_sections:
  OUT-8fna7evcc7x4: "Les six niveaux"
  OUT-wv0q7kcb8q1t: "Les six niveaux"
---

## Objectif

Adapter la quantité de sortie sans multiplier les options, et connaître les
six niveaux de Symfony 8.0.

## L'essentiel

Une seule command sert au quotidien et au diagnostic : chaque message porte
un **seuil** de verbosity, et le user choisit le niveau par une option. Six
niveaux en 8.0, de `--silent`, qui tait même les erreurs, à `-vvv`, qui
montre tout.

## Le schéma

```text
--silent  <  -q  <  (défaut)  <  -v  <  -vv  <  -vvv
SILENT       QUIET   NORMAL      VERBOSE  VERY_VERBOSE  DEBUG
un message marqué VERBOSE s'affiche à -v, -vv ET -vvv : c'est un seuil
SHELL_VERBOSITY : -2 … 3, réglage global ; -q et -v l'emportent
```

## L'exemple : une command qui échoue

```php
$output->writeln('normal');
$output->writeln('verbose-only', OutputInterface::VERBOSITY_VERBOSE);
$output->writeln('quiet-level', OutputInterface::VERBOSITY_QUIET);
throw new \RuntimeException('boom');
```

Exécuté dans un vrai process :

| Option | `normal` | `verbose-only` | `quiet-level` | erreur sur STDERR |
|---|---|---|---|---|
| aucune | oui | non | oui | oui |
| `-q` | non | non | **oui** | **oui** |
| `--silent` | non | non | non | **non** |
| `-v`, `-vvv` | oui | oui | oui | oui |

## Les six niveaux

| Drapeau | Constante | Usage |
|---|---|---|
| `--silent` | `VERBOSITY_SILENT` | rien du tout, pas même les erreurs |
| `-q`, `--quiet` | `VERBOSITY_QUIET` | aucun output normal |
| *(défaut)* | `VERBOSITY_NORMAL` | le message utile |
| `-v` | `VERBOSITY_VERBOSE` | information supplémentaire |
| `-vv` | `VERBOSITY_VERY_VERBOSE` | détail, messages non essentiels |
| `-vvv` | `VERBOSITY_DEBUG` | tout, traces d'exception comprises |

- Le seuil se passe en **second** argument de `writeln()` (`$options`), le
  troisième de `write()`.
- Quand le message coûte cher à calculer : `if ($output->isVerbose())`, et
  `isSilent()`, `isQuiet()`, `isVeryVerbose()`, `isDebug()`.
- Pas d'option `--debug` maison : les niveaux existent déjà.

## Pièges d'examen

**`--silent` masque les erreurs, il ne les perd pas.** Elles restent écrites par
le logger Symfony ; seule la console se tait.

**`SHELL_VERBOSITY` fixe le niveau globalement, mais `-q` et `-v` l'emportent
sur lui.** La variable vaut `-2` pour `--silent`, `-1` pour `--quiet`, `0`, `1`,
`2`, `3` pour les suivants. Exécuté : `SHELL_VERBOSITY=1` seul affiche le
message verbeux ; avec `-q`, plus rien d'autre que `quiet-level`.

**`-q` et `--silent` rendent aussi l'entrée non interactive** : `interact()`
n'est pas appelée (voir *Console component*).

**Le niveau passé à `writeln()` est un seuil, pas un filter exact.** Un
message marqué `VERBOSITY_VERBOSE` s'affiche à `-v`, `-vv` **et** `-vvv`.

**`writeln()` n'a que deux parameters** : le niveau est le second.

## Application

- Un résumé coûteux, utile seulement au diagnostic →
  `if ($output->isVerbose())`.
- Un message qui doit rester visible même sous `-q` →
  `OutputInterface::VERBOSITY_QUIET`.

## QCM d'entraînement

**Question 1.** Une command écrit
`writeln('quiet-level', OutputInterface::VERBOSITY_QUIET)` et elle est lancée
avec `-q`. Le message s'affiche-t-il ?

- **A.** Oui
- **B.** Non : `-q` coupe tout l'output
- **C.** Non : il faudrait `-v`
- **D.** Seulement sur STDERR

<details>
<summary>Correction</summary>

**Bonne réponse : A.** `VERBOSITY_QUIET` est le seuil le plus bas qu'un
message puisse porter pour rester visible : il passe sous `-q`.

</details>

**Question 2.** La même command lève une exception, lancée avec `--silent`.
Le message d'erreur apparaît-il sur STDERR ?

- **A.** Oui : une erreur s'affiche toujours
- **B.** Oui, mais sans la trace
- **C.** Non
- **D.** Seulement avec `SHELL_VERBOSITY=3`

<details>
<summary>Correction</summary>

**Bonne réponse : C.** `--silent` est plus radical que `-q` : il tait aussi
les erreurs à la console, que le logger reste libre d'écrire.

</details>

**Question 3.** `SHELL_VERBOSITY=1` est défini, et la command est lancée avec
`-q`. Qu'affiche-t-elle parmi `normal`, `verbose-only` et `quiet-level` ?

- **A.** Les trois : la variable l'emporte
- **B.** `normal` et `verbose-only`
- **C.** Rien
- **D.** `quiet-level` seulement

<details>
<summary>Correction</summary>

**Bonne réponse : D.** L'option passée en ligne de command l'emporte sur la
variable d'environment : `-q` ne laisse que le seuil `QUIET`.

</details>

**Question 4.** Un message est écrit avec `VERBOSITY_VERBOSE`. Avec quelles
options s'affiche-t-il ?

- **A.** `-v` seulement
- **B.** `-v`, `-vv` et `-vvv`
- **C.** `-vv` et `-vvv`
- **D.** Toujours, sauf sous `-q`

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Le niveau passé à `writeln()` est un seuil, pas un
filter exact : tout niveau supérieur l'affiche aussi.

</details>

## Fiche express

**Si tu dois retenir seulement 5 choses :**

- Six niveaux : `--silent`, `-q`, normal, `-v`, `-vv`, `-vvv`.
- **`--silent`** tait même les erreurs ; **`-q`** seulement l'output normal.
- Le niveau de `writeln()` est un **seuil** (second argument).
- **`isVerbose()`** et ses variantes pour un message coûteux.
- `SHELL_VERBOSITY` règle le défaut ; **`-q` et `-v` l'emportent**.

Moyen mnémotechnique : **« Un seuil, pas un filter »**.

## Sources officielles

- [`OutputInterface`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Console/Output/OutputInterface.php)
- [Console Verbosity](https://github.com/symfony/symfony-docs/blob/8.0/console/verbosity.rst)
- [`Application::configureIO`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Console/Application.php)

