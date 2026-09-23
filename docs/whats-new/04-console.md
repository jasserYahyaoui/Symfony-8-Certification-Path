# Console en 8.0

## Objectif

Ce que la 8.0 change dans Console. Page d'enrichissement : hors périmètre
officiel, hors couverture.

## L'attribut devient le seul moyen de nommer une commande

Deux lignes du journal se répondent :

- *« Remove methods `Command::getDefaultName()` and
  `Command::getDefaultDescription()` in favor of the `#[AsCommand]` attribute »* ;
- *« Make `AsCommand` attribute class `final` »*.

La première ferme l'ancienne voie : redéfinir une méthode statique pour donner
son nom à une commande n'est plus possible. La seconde ferme la porte de côté :
on ne peut pas étendre l'attribut pour en fabriquer une variante maison.

Résultat : en 8.0, une commande se nomme par `#[AsCommand]`, et par rien d'autre.

## `add()` devient `addCommand()`

*« Remove deprecated `Application::add()` method in favor of
`Application::addCommand()` »*. C'est un simple renommage, mais il touche tout
code qui enregistre une commande à la main plutôt que par le conteneur.

## Deux ajouts

`OutputInterface` gagne **`isSilent()`** : le mode silencieux devient
interrogeable depuis la sortie, au même titre que les autres niveaux de
verbosité.

`ProgressIndicator::finish()` reçoit `$finishedIndicator`.

## Une contrainte de typage

*« Ensure closures set via `Command::setCode()` method have proper parameter and
return types »*. Une fermeture passée à `setCode()` doit désormais être typée
correctement. Du code qui marchait sans types ne passe plus.

## Pièges d'examen

**`getDefaultName()` n'existe plus.** Une proposition qui redéfinit cette
méthode statique décrit du code antérieur à la 8.0.

**`AsCommand` est `final`** : en hériter est impossible, donc toute réponse qui
propose un attribut dérivé est fausse.

**`add()` n'est pas dépréciée, elle est retirée** — ce n'est plus un
avertissement, c'est une erreur.

## Points clés

- `#[AsCommand]` est le seul moyen de nommer une commande, et l'attribut est
  `final`.
- `Application::add()` est remplacée par `addCommand()`.
- `OutputInterface` gagne `isSilent()`.
- Les fermetures de `setCode()` doivent être typées.

## Sources officielles

- [Console, journal 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Console/CHANGELOG.md)

## Flashcards

### Mémorisation

<details>
<summary>Comment nomme-t-on une commande en 8.0 ?</summary>

Par l'attribut **`#[AsCommand]`**, et par rien d'autre.

`getDefaultName()` et `getDefaultDescription()` sont retirées, et l'attribut est `final`.

</details>

<details>
<summary>Quelle méthode remplace `Application::add()` ?</summary>

**`Application::addCommand()`.**

Simple renommage, mais il touche tout enregistrement manuel de commande.

</details>

<details>
<summary>Quelle méthode la 8.0 ajoute-t-elle à `OutputInterface` ?</summary>

**`isSilent()`.**

Le mode silencieux devient interrogeable comme les autres niveaux de verbosité.

</details>

### Compréhension

<details>
<summary>Pourquoi rendre `AsCommand` `final` complète-t-il la suppression de `getDefaultName()` ?</summary>

La suppression ferme l'ancienne voie ; le `final` ferme la porte de côté, qui serait un attribut dérivé maison. Il ne reste qu'un chemin.

Les deux lignes du journal se répondent : prises ensemble, elles rendent l'attribut obligatoire.

</details>

<details>
<summary>Pourquoi exiger des types sur les fermetures passées à `setCode()` ?</summary>

Parce que le composant doit savoir quoi passer et quoi attendre en retour. Sans types, l'appel reposait sur une convention implicite.

Du code qui fonctionnait sans types cesse de fonctionner.

</details>

### Application

<details>
<summary>Une commande définit `protected static function getDefaultName(): string`. Que se passe-t-il en 8.0 ?</summary>

Plus rien ne l'appelle : la commande n'a pas de nom. Il faut poser `#[AsCommand]`.

L'échec est silencieux côté classe — la méthode reste syntaxiquement valide.

</details>

### Pièges

<details>
<summary>Piège : « `Application::add()` est dépréciée en 8.0. »</summary>

Non, elle est **retirée**. Ce n'est plus un avertissement, c'est une erreur.

Dépréciée en 7.x, supprimée en 8.0 : c'est la mécanique de toute la version.

</details>

<details>
<summary>Piège : « on peut étendre `#[AsCommand]` pour créer son propre attribut de commande. »</summary>

Non : la classe d'attribut est **`final`** en 8.0.

L'idée est naturelle et le code ne compile pas.

</details>
