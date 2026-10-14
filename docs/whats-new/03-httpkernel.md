# HttpKernel en 8.0

## Objectif

Ce que la 8.0 change dans le kernel. Page d'enrichissement : hors périmètre
officiel, hors couverture. Elle complète le partage composant / bundle traité
dans le parcours de révision.

## Une method de plus sur `KernelInterface`

Le journal l'annonce en une ligne : *« Add method `getShareDir()` to
`KernelInterface` »*.

C'est notable parce que le parcours de révision compte les methods de cette
interface : la 8.0 en ajoute une. Un décompte appris sur une version antérieure
est donc faux d'une unité.

## Les classes annotées à compiler disparaissent

Trois suppressions qui vont ensemble :

- `AddAnnotatedClassesToCachePass` ;
- `Extension::getAnnotatedClassesToCompile()` et
  `Extension::addAnnotatedClassesToCompile()` ;
- `Kernel::getAnnotatedClassesToCompile()` et `Kernel::setAnnotatedClassCache()`.

Tout le mécanisme qui pré-compilait un jeu de classes annotées dans le cache est
retiré d'un block, sans remplacement annoncé dans le journal.

## La serialization change de paire de methods

*« Replace `__sleep/wakeup()` by `__(un)serialize()` on kernels and data
collectors »*. Les kernels et les data collectors du profiler passent aux
methods modernes de serialization de PHP. Un collector maison qui déclare
encore `__sleep()` n'est plus appelé comme avant.

## Deux détails de signature

`ServicesResetter` devient **`final`** : on ne peut plus en hériter.

`ErrorListener::logException()` reçoit `$logChannel`, et
`DumpListener::configure()` reçoit `$event`.

## Pièges d'examen

**`KernelInterface` n'a pas le même nombre de methods qu'en 7.x** : la 8.0 en
ajoute une. Un décompte mémorisé ailleurs doit être revérifié sur la branche.

**`ServicesResetter` est `final`** : une proposition qui l'étend décrit du code
qui ne compile pas en 8.0.

**Le mécanisme des classes annotées n'est pas déplacé, il est supprimé.**
Chercher son remplaçant, c'est chercher ce que le journal n'annonce pas.

## Points clés

- `getShareDir()` est ajoutée à `KernelInterface`.
- Tout le dispositif des classes annotées à compiler est retiré.
- Kernels et collectors passent à `__serialize()` / `__unserialize()`.
- `ServicesResetter` devient `final`.

## Sources officielles

- [HttpKernel, journal 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/CHANGELOG.md)

## Flashcards

### Mémorisation

<details>
<summary>Quelle method la 8.0 ajoute-t-elle à `KernelInterface` ?</summary>

**`getShareDir()`.**

Un décompte des methods de l'interface appris sur une version antérieure est donc faux d'une unité.

</details>

<details>
<summary>Que remplacent `__serialize()` et `__unserialize()` sur les kernels et les collectors ?</summary>

**`__sleep()` et `__wakeup()`.**

Le changement touche aussi les data collectors du profiler.

</details>

### Compréhension

<details>
<summary>Pourquoi rendre `ServicesResetter` `final` est-il un changement cassant ?</summary>

Parce que tout code qui en héritait cesse de compiler. Rien n'est retiré de l'API, mais une liberté l'est.

`final` est une suppression de point d'extension, pas un détail de style.

</details>

<details>
<summary>Pourquoi la disparition des « classes annotées à compiler » n'a-t-elle pas de remplaçant annoncé ?</summary>

Parce que tout le dispositif est retiré, pas déplacé : la passe, les methods d'`Extension` et celles de `Kernel` partent ensemble.

Chercher l'équivalent, c'est chercher ce que le journal n'annonce pas.

</details>

### Application

<details>
<summary>Un data collector maison déclare `__sleep()`. Que faut-il faire en 8.0 ?</summary>

Le porter vers `__serialize()` / `__unserialize()` : c'est la paire que le composant utilise désormais.

Laisser `__sleep()` ne lève pas forcément, mais ne produit plus l'effet attendu.

</details>

<details>
<summary>Une class du projet étend `ServicesResetter`. Que se passe-t-il en 8.0 ?</summary>

Erreur fatale : la class est `final`. Il faut composer plutôt qu'hériter.

Le remplacement passe par l'implémentation de l'interface correspondante.

</details>

### Pièges

<details>
<summary>Piège : « la 8.0 ne fait que retirer, elle n'ajoute rien à HttpKernel. »</summary>

Faux : `getShareDir()` est ajoutée à `KernelInterface`, et deux listeners reçoivent un argument supplémentaire.

Le chapitre est dominé par les retraits, ce qui fait manquer les ajouts.

</details>

<details>
<summary>Piège : « `getAnnotatedClassesToCompile()` a été déplacée ailleurs. »</summary>

Non, elle est **retirée**, comme le compiler pass et les methods correspondantes de `Kernel`.

Un retrait groupé se lit facilement comme un déménagement.

</details>
