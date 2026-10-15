# Console en 8.0

## Objectif

Ce que la 8.0 change dans Console. Page d'enrichissement : hors périmètre
officiel, hors couverture.

## L'attribute devient le seul moyen de nommer une command

Deux lignes du journal se répondent :

- *« Remove methods `Command::getDefaultName()` and
  `Command::getDefaultDescription()` in favor of the `#[AsCommand]` attribute »* ;
- *« Make `AsCommand` attribute class `final` »*.

La première ferme l'ancienne voie : redéfinir une method statique pour donner
son nom à une command n'est plus possible. La seconde ferme la porte de côté :
on ne peut pas étendre l'attribute pour en fabriquer une variante maison.

Résultat : en 8.0, une command se nomme par `#[AsCommand]`, et par rien d'autre.

## `add()` devient `addCommand()`

*« Remove deprecated `Application::add()` method in favor of
`Application::addCommand()` »*. C'est un simple renommage, mais il touche tout
code qui enregistre une command à la main plutôt que par le container.

## Deux ajouts

`OutputInterface` gagne **`isSilent()`** : le mode silencieux devient
interrogeable depuis l'output, au même titre que les autres niveaux de
verbosity.

`ProgressIndicator::finish()` reçoit `$finishedIndicator`.

## Une constraint de typage

*« Ensure closures set via `Command::setCode()` method have proper parameter and
return types »*. Exécuté sur 8.0.15 contre 7.4.21, la règle porte sur deux
points, et elle tombe **à l'exécution** (`run()`), pas à `setCode()` qui accepte
toute closure :

| Closure passée à `setCode()` | 7.4 | 8.0 |
|---|---|---|
| parameters non typés | `run()` rend 0 | `LogicException` : *must have a named type* |
| typée, ne rend pas un `int` | `run()` rend 0 | `TypeError` : *must return an integer value* |
| typée, `return 0` | 0 | 0 |

Un return type déclaré n'est pas exigé : c'est la **valeur** rendue qui doit
être un `int`.

## Pièges d'examen

**`getDefaultName()` n'existe plus.** Une proposition qui redéfinit cette
method statique décrit du code antérieur à la 8.0.

**`AsCommand` est `final`** : en hériter est impossible, donc toute proposition qui
propose un attribute dérivé est fausse.

**`add()` n'est pas dépréciée, elle est retirée** — ce n'est plus un
avertissement, c'est une erreur.

## Points clés

- `#[AsCommand]` est le seul moyen de nommer une command, et l'attribute est
  `final`.
- `Application::add()` est remplacée par `addCommand()`.
- `OutputInterface` gagne `isSilent()`.
- Une closure de `setCode()` doit typer ses parameters et rendre un `int` ; sinon `run()` échoue.

## Sources officielles

- [Console, journal 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Console/CHANGELOG.md)

## Flashcards

### Mémorisation

<details>
<summary>Comment nomme-t-on une command en 8.0 ?</summary>

Par l'attribute **`#[AsCommand]`**, et par rien d'autre.

`getDefaultName()` et `getDefaultDescription()` sont retirées, et l'attribute est `final`.

</details>

<details>
<summary>Quelle method remplace `Application::add()` ?</summary>

**`Application::addCommand()`.**

Simple renommage, mais il touche tout enregistrement manuel de command.

</details>

<details>
<summary>Quelle method la 8.0 ajoute-t-elle à `OutputInterface` ?</summary>

**`isSilent()`.**

Le mode silencieux devient interrogeable comme les autres niveaux de verbosity.

</details>

### Compréhension

<details>
<summary>Pourquoi rendre `AsCommand` `final` complète-t-il la suppression de `getDefaultName()` ?</summary>

La suppression ferme l'ancienne voie ; le `final` ferme la porte de côté, qui serait un attribute dérivé maison. Il ne reste qu'un path.

Les deux lignes du journal se répondent : prises ensemble, elles rendent l'attribute obligatoire.

</details>

<details>
<summary>Pourquoi exiger des types sur les closures passées à `setCode()` ?</summary>

Parce que le component doit savoir quoi passer et quoi attendre en retour. Sans types, l'appel reposait sur une convention implicite.

Du code qui fonctionnait sans types cesse de fonctionner.

</details>

### Application

<details>
<summary>Une command définit `protected static function getDefaultName(): string`. Que se passe-t-il en 8.0 ?</summary>

Plus rien ne l'appelle : la command n'a pas de nom. Il faut poser `#[AsCommand]`.

L'échec est silencieux côté class — la method reste syntaxiquement valide.

</details>

### Pièges

<details>
<summary>Piège : « `Application::add()` est dépréciée en 8.0. »</summary>

Non, elle est **retirée**. Ce n'est plus un avertissement, c'est une erreur.

Dépréciée en 7.x, supprimée en 8.0 : c'est la mécanique de toute la version.

</details>

<details>
<summary>Piège : « on peut étendre `#[AsCommand]` pour créer son propre attribute de command. »</summary>

Non : la class d'attribute est **`final`** en 8.0.

L'idée est naturelle et le code ne compile pas.

</details>
