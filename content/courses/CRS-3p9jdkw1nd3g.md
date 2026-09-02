---
id: CRS-3p9jdkw1nd3g
official_item: OIT-v6wxp78gk42c
title: "Built-in helpers"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/components/console/helpers/map.rst.inc"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Console/Style/SymfonyStyle.php"
    branch: "8.0"
    symbol_or_lines: "SymfonyStyle"
    verified_at: "2026-09-01"
---

## Objectif

Savoir quels outils le composant fournit déjà, pour ne pas réécrire une barre de
progression ni une saisie interactive à la main.

## Prérequis

Les objets d'entrée et de sortie.

## Le catalogue

La documentation 8.0 en recense neuf :

| Helper | Ce qu'il fait |
|---|---|
| `QuestionHelper` | poser une question à l'utilisateur |
| `ProgressBar` | une barre de progression sur une tâche de longueur connue |
| `ProgressIndicator` | un indicateur d'activité quand la longueur est inconnue |
| `Table` | afficher des données en tableau |
| `Tree` | afficher une arborescence |
| `FormatterHelper` | blocs et sections colorés |
| `ProcessHelper` | lancer un processus externe en affichant son déroulé |
| `DebugFormatterHelper` | mettre en forme la sortie de ce processus |
| `Cursor` | déplacer le curseur dans le terminal |

Aucun n'est à connaître dans le détail. Ce qui compte à l'examen est de savoir
**qu'il existe** et à quelle famille de besoin il répond.

## Les questions

`QuestionHelper::ask()` prend l'entrée, la sortie et un objet question. Trois
types de questions existent :

```php
new Question('Nom du projet ?', 'defaut');
new ConfirmationQuestion('Continuer ?', false);
new ChoiceQuestion('Environnement ?', ['dev', 'prod'], 0);
```

`ConfirmationQuestion` accepte toute réponse commençant par `y` — donc `yeti`
répond « oui ». Son second argument est la valeur rendue quand l'utilisateur ne
saisit rien ; en son absence, la réponse par défaut est `true`.

Le point structurant : une question n'a de sens que si l'entrée est
**interactive**. Avec `--no-interaction`, la valeur par défaut est retenue sans
rien demander. C'est pourquoi la documentation place les questions dans
`interact()`, et pourquoi une valeur obtenue ainsi doit toujours avoir un repli.

## La progression

```php
$bar = new ProgressBar($output, 100);
$bar->start();
$bar->advance();
$bar->finish();
```

`ProgressBar` suppose un total connu. Quand il ne l'est pas — attendre une
réponse réseau — `ProgressIndicator` affiche une animation sans pourcentage.

## Le tableau

```php
(new Table($output))
    ->setHeaders(['ID', 'Nom'])
    ->setRows([[1, 'Alice'], [2, 'Bob']])
    ->render();
```

## `SymfonyStyle` plutôt que les helpers

En pratique, `SymfonyStyle` est la porte d'entrée recommandée : elle enveloppe
les helpers derrière une API courte et une présentation homogène.

```php
$io = new SymfonyStyle($input, $output);

$io->confirm('Continuer ?');
$io->choice('Environnement ?', ['dev', 'prod']);
$io->table(['ID', 'Nom'], $rows);
$io->progressStart(100);
$io->progressAdvance();
$io->progressFinish();
```

`progressIterate()` fait même le tour complet sur un itérable. La règle de
décision est simple : `SymfonyStyle` d'abord, le helper directement seulement
pour ce qu'elle n'expose pas.

## Y accéder sans `SymfonyStyle`

Une commande qui étend `Command` dispose du `HelperSet` :

```php
$helper = $this->getHelper('question');
```

Une commande invocable, qui n'étend rien, n'a pas cette méthode : elle instancie
le helper (`new QuestionHelper()`) ou passe par `SymfonyStyle`.

## Pièges d'examen

**Sans interaction, aucune question n'est posée** : la valeur par défaut
s'applique silencieusement.

**`ConfirmationQuestion` vaut `true` par défaut** si le second argument est omis.

**`ProgressBar` exige un total connu** ; sinon c'est `ProgressIndicator`.

**`getHelper()` vient de `Command`** — indisponible dans une commande invocable
qui ne l'étend pas.

## Points clés

- Neuf helpers documentés ; les connaître par leur usage, pas par leur API.
- Questions : `Question`, `ConfirmationQuestion`, `ChoiceQuestion`.
- `ProgressBar` pour un total connu, `ProgressIndicator` sinon.
- `SymfonyStyle` est la façade recommandée sur l'ensemble.

## Sources officielles

- [The Console Helpers](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/components/console/helpers/map.rst.inc)
- [Question Helper](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/components/console/helpers/questionhelper.rst)
- [`SymfonyStyle`, branche 8.0](https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Console/Style/SymfonyStyle.php)
