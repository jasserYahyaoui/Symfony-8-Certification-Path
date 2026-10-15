---
id: CRS-0ab7kpexq9w9
official_item: OIT-65sswf0qhw6c
title: "Input and Output objects"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-02"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Console/Input/InputInterface.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Console/Input/InputInterface.php"
    branch: "8.0"
    symbol_or_lines: "InputInterface"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Console/Output/OutputInterface.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Console/Output/OutputInterface.php"
    branch: "8.0"
    symbol_or_lines: "OutputInterface"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Console/Output/Output.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Console/Output/Output.php"
    branch: "8.0"
    symbol_or_lines: "write() — OUTPUT_PLAIN is strip_tags(format())"
    verified_at: "2026-10-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Console/Input/ArgvInput.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Console/Input/ArgvInput.php"
    branch: "8.0"
    symbol_or_lines: "hasParameterOption(), getParameterOption() — raw tokens"
    verified_at: "2026-10-02"
outcome_sections:
  OUT-eea5s77r44aw: "`InputInterface` — lire"
  OUT-b3v4c3hz8gha: "`OutputInterface` — écrire"
  OUT-8pz0624za51p: "L'output d'erreur"
---

## Objectif

Lire l'entrée et écrire l'output par les deux abstractions du component, sans
jamais toucher `$argv`, `echo` ni `STDOUT` directement. C'est ce découplage qui
rend une command testable.

## Prérequis

Les arguments et les options.

## `InputInterface` — lire

```php
$input->getArgument('username');   // une valeur positionnelle
$input->getOption('iterations');   // une valeur nommée
$input->hasOption('dry-run');      // l'option est-elle définie ?
$input->getArguments();            // toutes, en tableau
$input->isInteractive();           // faux avec -n, -q ou --silent
```

`setArgument()` et `setOption()` existent aussi : c'est ainsi qu'`interact()`
complète une valeur manquante avant l'exécution.

Deux distinctions à ne pas confondre :

- `hasOption('x')` demande si l'option est **déclarée** dans la definition ;
  `getOption('x')` rend sa valeur, éventuellement la default value.
- `hasParameterOption('--x')` regarde la **ligne de command brute**, avant
  toute analyse. C'est ce qu'il faut quand l'option n'est pas encore déclarée —
  le cas de `--env` lu avant la construction du kernel.

Exécuté avec Console 8.0.15, une option `--dry-run` déclarée :

| Entrée | `hasOption('dry-run')` | `getOption('dry-run')` |
|---|---|---|
| `--dry-run` | `true` | `true` |
| rien | **`true`** | `false` |

Et `ArgvInput(['bin', '--env=prod'])` **sans definition** :
`hasParameterOption('--env')` vaut `true`, `getParameterOption('--env')` vaut
`'prod'`.

`isInteractive()` ne teste pas le terminal : l'entrée n'est non interactive
qu'avec `-n`, `-q` ou `--silent` (voir *Console component*).

## Les implémentations

| Class | Source des valeurs |
|---|---|
| `ArgvInput` | `$argv` — l'exécution réelle en terminal |
| `ArrayInput` | un array PHP — les tests, et l'appel d'une command depuis une autre |
| `StringInput` | une string analysée comme une ligne de command |

Une command écrite contre `InputInterface` fonctionne avec les trois. C'est
exactement ce que `CommandTester` exploite.

## `OutputInterface` — écrire

```php
$output->write('sans retour à la ligne');
$output->writeln('avec retour à la ligne');
$output->writeln(['plusieurs', 'lignes']);
```

Trois modes de rendu contrôlent l'interprétation des tags :
`OUTPUT_NORMAL` les interprète, `OUTPUT_RAW` les laisse telles quelles,
`OUTPUT_PLAIN` les interprète **puis** applique `strip_tags()` au résultat.
Exécuté, `'<info>ok</info> <script>x</script>'` :

| Mode | Sortie décorée | Sortie non décorée |
|---|---|---|
| `OUTPUT_NORMAL` | `ok` en vert, `<script>x</script>` intact | `ok <script>x</script>` |
| `OUTPUT_PLAIN` | `ok` **toujours en vert**, puis `x` | `ok x` |
| `OUTPUT_RAW` | `<info>ok</info>` tel quel | — |

`OUTPUT_PLAIN` ne retire donc pas les couleurs d'un output décoré : il
retire les tags qui restent après le formatage.

## Les tags de style

Le formateur en définit quatre par défaut :

| Tag | Rendu |
|---|---|
| `<info>` | vert |
| `<comment>` | jaune |
| `<question>` | noir sur cyan |
| `<error>` | blanc sur rouge |

```php
$output->writeln('<info>terminé</info>');
```

Une string venant du user ou d'une base doit passer par
`OutputFormatter::escape()`. Exécuté : sans escaping, `<info>x</info>` venu
du user s'affiche `x`, le tag interprété ; échappé, il s'affiche
tel quel. Un tag inconnu — `<script>` — passe intacte dans les deux cas.

## L'output d'erreur

`ConsoleOutput` implémente `ConsoleOutputInterface`, qui ajoute
`getErrorOutput()`. Ce second flux écrit sur **`STDERR`** :

```php
if ($output instanceof ConsoleOutputInterface) {
    $output->getErrorOutput()->writeln('<error>échec</error>');
}
```

L'intérêt est opérationnel : `php bin/console app:export > data.csv` ne doit pas
mélanger les messages de diagnostic aux données redirigées.

## `SymfonyStyle`, la façade

`SymfonyStyle` enveloppe l'entrée et l'output et fournit une présentation
conforme aux conventions Symfony :

```php
$io = new SymfonyStyle($input, $output);
$io->title('Import');
$io->success('42 lignes importées');
$io->error('fichier introuvable');
$name = $io->ask('Nom ?');
$io->table(['id', 'nom'], $rows);
```

Elle expose aussi `getErrorStyle()`, qui rend la même API branchée sur `STDERR`.

## Pièges d'examen

**Ne jamais utiliser `echo`** : l'output échapperait à la verbosity, au
formateur et aux tests.

**`getErrorOutput()` n'appartient pas à `OutputInterface`** mais à
`ConsoleOutputInterface` — d'où le test de type.

**`hasOption()` interroge la definition**, pas la ligne de command : il vaut
`true` même quand l'option n'a pas été passée. C'est `hasParameterOption()`
qui lit la ligne brute.

**`OUTPUT_PLAIN` garde les couleurs** d'un output décoré.

**Le nom passé à `getArgument()` est le nom kebab-case** déclaré, pas celui du
parameter PHP.

## Points clés

- `InputInterface` : `getArgument`, `getOption`, `hasOption`, `isInteractive`.
- `ArgvInput`, `ArrayInput`, `StringInput` — même contrat, sources différentes.
- `OutputInterface` : `write`, `writeln`, verbosity, formateur.
- Quatre tags par défaut ; échapper ce qui vient de l'extérieur.
- `getErrorOutput()` pour `STDERR` ; `SymfonyStyle` pour tout le reste.

## Sources officielles

- [`InputInterface`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Console/Input/InputInterface.php)
- [`OutputInterface`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Console/Output/OutputInterface.php)
- [`Output`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Console/Output/Output.php)
- [`OutputFormatter`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Console/Formatter/OutputFormatter.php)
- [How to Style a Console Command](https://github.com/symfony/symfony-docs/blob/8.0/console/style.rst)
