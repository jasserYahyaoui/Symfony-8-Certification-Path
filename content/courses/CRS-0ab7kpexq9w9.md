---
id: CRS-0ab7kpexq9w9
official_item: OIT-65sswf0qhw6c
title: "Input and Output objects"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Console/Input/InputInterface.php"
    branch: "8.0"
    symbol_or_lines: "InputInterface"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Console/Output/OutputInterface.php"
    branch: "8.0"
    symbol_or_lines: "OutputInterface"
    verified_at: "2026-09-01"
---

## Objectif

Lire l'entrée et écrire la sortie par les deux abstractions du composant, sans
jamais toucher `$argv`, `echo` ni `STDOUT` directement. C'est ce découplage qui
rend une commande testable.

## Prérequis

Les arguments et les options.

## `InputInterface` — lire

```php
$input->getArgument('username');   // une valeur positionnelle
$input->getOption('iterations');   // une valeur nommée
$input->hasOption('dry-run');      // l'option est-elle définie ?
$input->getArguments();            // toutes, en tableau
$input->isInteractive();           // un terminal répondra-t-il ?
```

`setArgument()` et `setOption()` existent aussi : c'est ainsi qu'`interact()`
complète une valeur manquante avant l'exécution.

Deux distinctions à ne pas confondre :

- `hasOption('x')` demande si l'option est **déclarée** dans la définition ;
  `getOption('x')` rend sa valeur, éventuellement la valeur par défaut.
- `hasParameterOption('--x')` regarde la **ligne de commande brute**, avant
  toute analyse. C'est ce qu'il faut quand l'option n'est pas encore déclarée —
  le cas de `--env` lu avant la construction du noyau.

## Les implémentations

| Classe | Source des valeurs |
|---|---|
| `ArgvInput` | `$argv` — l'exécution réelle en terminal |
| `ArrayInput` | un tableau PHP — les tests, et l'appel d'une commande depuis une autre |
| `StringInput` | une chaîne analysée comme une ligne de commande |

Une commande écrite contre `InputInterface` fonctionne avec les trois. C'est
exactement ce que `CommandTester` exploite.

## `OutputInterface` — écrire

```php
$output->write('sans retour à la ligne');
$output->writeln('avec retour à la ligne');
$output->writeln(['plusieurs', 'lignes']);
```

Trois modes de rendu contrôlent l'interprétation des balises :
`OUTPUT_NORMAL` les interprète, `OUTPUT_RAW` les laisse telles quelles,
`OUTPUT_PLAIN` les retire.

## Les balises de style

Le formateur en définit quatre par défaut :

| Balise | Rendu |
|---|---|
| `<info>` | vert |
| `<comment>` | jaune |
| `<question>` | noir sur cyan |
| `<error>` | blanc sur rouge |

```php
$output->writeln('<info>terminé</info>');
```

Une chaîne venant de l'utilisateur ou d'une base doit passer par
`OutputFormatter::escape()` : un `<` non échappé serait pris pour une balise.

## La sortie d'erreur

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

`SymfonyStyle` enveloppe l'entrée et la sortie et fournit une présentation
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

**Ne jamais utiliser `echo`** : la sortie échapperait à la verbosité, au
formateur et aux tests.

**`getErrorOutput()` n'appartient pas à `OutputInterface`** mais à
`ConsoleOutputInterface` — d'où le test de type.

**`hasOption()` interroge la définition**, pas la ligne de commande ; c'est
`hasParameterOption()` qui lit la ligne brute.

**Le nom passé à `getArgument()` est le nom kebab-case** déclaré, pas celui du
paramètre PHP.

## Points clés

- `InputInterface` : `getArgument`, `getOption`, `hasOption`, `isInteractive`.
- `ArgvInput`, `ArrayInput`, `StringInput` — même contrat, sources différentes.
- `OutputInterface` : `write`, `writeln`, verbosité, formateur.
- Quatre balises par défaut ; échapper ce qui vient de l'extérieur.
- `getErrorOutput()` pour `STDERR` ; `SymfonyStyle` pour tout le reste.

## Sources officielles

- [`InputInterface`, branche 8.0](https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Console/Input/InputInterface.php)
- [`OutputInterface`, branche 8.0](https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Console/Output/OutputInterface.php)
- [How to Style a Console Command](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/console/style.rst)
