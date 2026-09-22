# Security en 8.0

## Objectif

Ce que la 8.0 change dans la sécurité. Page d'enrichissement : hors périmètre
officiel, hors couverture.

## `eraseCredentials()` disparaît des deux interfaces

*« Remove `UserInterface::eraseCredentials()` and
`TokenInterface::eraseCredentials()` »*.

C'est la suppression la plus large du composant, parce que **toute** classe
utilisateur écrite avant la 8.0 implémentait cette méthode — souvent avec un
corps vide, précisément parce que l'interface l'exigeait sans qu'on ait rien à
effacer.

Le guide de migration ne propose pas une méthode de remplacement mais un
**emplacement** de remplacement : effacer les données sensibles depuis
`__serialize()`, en retirant la propriété concernée du tableau retourné.

Le déplacement est logique : l'effacement n'avait de sens qu'au moment où
l'objet part en session, c'est-à-dire au moment de la sérialisation.

## Le cookie remember-me maigrit

Trois lignes cohérentes entre elles :

- *« Remove the user FQCN from the remember-me cookie »* ;
- `PersistentTokenInterface::getClass()` et `RememberMeDetails::getUserFqcn()`
  sont retirées ;
- en étendant `RememberMeDetails`, le paramètre `$userFqcn` doit être **retiré**
  de la signature du constructeur.

Le nom pleinement qualifié de la classe utilisateur ne voyage donc plus chez le
client. Une classe qui redéfinit le constructeur sans supprimer ce paramètre ne
correspond plus au parent.

## Un argument sur la décision d'accès

`AccessDecisionStrategyInterface::decide()` reçoit `$accessDecision`. Une
stratégie maison écrite pour la 7.x n'implémente plus l'interface telle qu'elle
est.

## Pièges d'examen

**`eraseCredentials()` n'est pas dépréciée, elle n'existe plus** sur les deux
interfaces. La déclarer n'est pas une erreur en soi, mais plus rien ne l'appelle.

**Le remplacement n'est pas une autre méthode** : c'est `__serialize()`.

**Le cookie remember-me ne porte plus le FQCN** de la classe utilisateur, et
trois éléments d'API disparaissent avec lui.

## Points clés

- `eraseCredentials()` retirée de `UserInterface` et de `TokenInterface` ;
  effacer via `__serialize()`.
- Le FQCN utilisateur quitte le cookie remember-me.
- `getClass()` et `getUserFqcn()` sont retirées ; `$userFqcn` quitte le
  constructeur de `RememberMeDetails`.
- `decide()` reçoit `$accessDecision`.

## Sources officielles

- [UPGRADE 8.0, section Security](https://github.com/symfony/symfony/blob/8.0/UPGRADE-8.0.md)

## Flashcards

### Mémorisation

<details>
<summary>De quelles interfaces `eraseCredentials()` est-elle retirée en 8.0 ?</summary>

De **`UserInterface`** et de **`TokenInterface`**.

Toute classe utilisateur écrite avant la 8.0 l'implémentait, souvent avec un corps vide.

</details>

<details>
<summary>Que ne porte plus le cookie remember-me en 8.0 ?</summary>

Le **nom pleinement qualifié** de la classe utilisateur.

`PersistentTokenInterface::getClass()` et `RememberMeDetails::getUserFqcn()` sont retirées avec lui.

</details>

### Compréhension

<details>
<summary>Pourquoi effacer les données sensibles depuis `__serialize()` plutôt que dans une méthode dédiée ?</summary>

Parce que l'effacement n'avait de sens qu'au moment où l'objet part en session — c'est-à-dire à la sérialisation. Le guide déplace l'opération là où elle compte.

Le remplacement n'est pas une méthode, c'est un emplacement.

</details>

<details>
<summary>Pourquoi retirer le FQCN du cookie remember-me améliore-t-il la sécurité ?</summary>

Parce qu'il révélait au client un détail d'implémentation du serveur — le nom exact d'une classe applicative.

Trois éléments d'API disparaissent ensemble, ce qui montre que c'est une décision, pas un nettoyage.

</details>

### Application

<details>
<summary>Une entité `User` déclare `public function eraseCredentials(): void {}`. Faut-il la retirer en 8.0 ?</summary>

Rien ne l'appelle plus. Si elle effaçait réellement une propriété, l'opération doit être portée dans `__serialize()`.

Un corps vide ne casse rien ; un corps utile devient du code mort.

</details>

<details>
<summary>Une classe étend `RememberMeDetails` et redéfinit le constructeur avec `$userFqcn`. Que faire ?</summary>

Retirer ce paramètre de la signature : le parent ne le prend plus.

Le guide de migration montre le avant/après explicitement.

</details>

### Pièges

<details>
<summary>Piège : « `eraseCredentials()` est dépréciée en 8.0. »</summary>

Non, elle est **retirée** des deux interfaces.

La déclarer reste syntaxiquement valide — ce qui rend l'erreur invisible.

</details>

<details>
<summary>Piège : « une stratégie de décision d'accès écrite pour la 7.x implémente encore l'interface. »</summary>

Non : `decide()` reçoit `$accessDecision` en 8.0.

Un argument ajouté à une interface casse toutes ses implémentations.

</details>
