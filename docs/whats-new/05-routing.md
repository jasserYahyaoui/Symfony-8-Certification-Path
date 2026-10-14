# Routing en 8.0

## Objectif

Ce que la 8.0 change dans le router. Page d'enrichissement : hors périmètre
officiel, hors couverture. Elle prolonge la page *Naming conventions* du
parcours de révision.

## Le format XML disparaît

*« Remove support for the XML configuration format »*. Les routes se déclarent
en attributes, en YAML ou en PHP. Un projet qui porte encore des files de
routes en XML ne démarre plus.

La même suppression apparaît dans DependencyInjection et, sous une autre forme,
dans Validator : le recul du XML est un mouvement d'ensemble de la 8.0, pas une
particularité du router.

## L'inheritance des annotations est soldé

Trois lignes qui vont ensemble :

- la property `AttributeClassLoader::$routeAnnotationClass` et la method
  `setRouteAnnotationClass()` sont retirées, au profit de
  `setRouteAttributeClass()` ;
- les alias de classes du namespace `Annotation` sont retirés ;
- les accesseurs des classes d'attributes sont retirés **au profit de properties
  publiques**.

Le dernier point est le plus facile à manquer : on ne lit plus un attribute de
route par un accesseur, on lit sa property.

## Un parameter mal typé devient une erreur

*« Providing a non-array `_query` parameter to `UrlGenerator` causes an
`InvalidParameterException` »*.

Avant, une valeur mal typée passait ; en 8.0 elle lève. C'est un changement de
comportement silencieux à l'écriture et bruyant à l'exécution — exactement le
genre de régression qu'une migration révèle en production si on ne l'a pas lue.

## Pièges d'examen

**Le XML n'est pas déconseillé, il est retiré.** Aucun repli, aucun
avertissement.

**Les attributes de route s'écrivent par properties publiques**, plus par
accesseurs.

**`_query` mal typé lève** au lieu d'être ignoré ou converti.

## Points clés

- Plus de format XML pour les routes.
- `setRouteAttributeClass()` remplace `setRouteAnnotationClass()`.
- Les classes d'attributes exposent des properties publiques, pas des accesseurs.
- Un `_query` non array lève une `InvalidParameterException`.

## Sources officielles

- [Routing, journal 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/CHANGELOG.md)

## Flashcards

### Mémorisation

<details>
<summary>Quel format de configuration de routes disparaît en 8.0 ?</summary>

Le **XML**. Restent les attributes, YAML et PHP.

La même suppression touche DependencyInjection, et Validator sous une autre forme.

</details>

<details>
<summary>Que remplace `setRouteAnnotationClass()` ?</summary>

**`setRouteAttributeClass()`.** La property `$routeAnnotationClass` disparaît avec elle.

Les alias de classes du namespace `Annotation` sont retirés également.

</details>

### Compréhension

<details>
<summary>Pourquoi les classes d'attributes de route n'ont-elles plus d'accesseurs ?</summary>

Parce qu'elles exposent des **properties publiques**. Un attribute est une structure de données lue par le loader, pas un object à encapsuler.

Le même mouvement existe dans Serializer.

</details>

<details>
<summary>Pourquoi faire lever un `_query` qui n'est pas un array plutôt que l'ignorer ?</summary>

Parce qu'ignorer produisait une URL silencieusement fausse. Lever déplace l'échec de la production vers le développement.

Le symptôme d'avant — une URL sans ses parameters — ne désignait pas sa cause.

</details>

### Application

<details>
<summary>Un projet porte encore `config/routes.xml`. Que se passe-t-il en 8.0 ?</summary>

Le format n'est plus supporté : il faut porter les routes en attributes, YAML ou PHP.

Aucun repli, aucun avertissement : le support est retiré.

</details>

<details>
<summary>Du code passe une string comme `_query` au générateur d'URL. Résultat en 8.0 ?</summary>

Une **`InvalidParameterException`**. En 7.x la valeur passait.

C'est un changement de comportement muet à l'écriture et bruyant à l'exécution.

</details>

### Pièges

<details>
<summary>Piège : « le XML est déconseillé pour les routes en 8.0. »</summary>

Plus que déconseillé : **retiré**. Il n'est plus lu du tout.

« Déconseillé » suggère à tort qu'un repli existe.

</details>

<details>
<summary>Piège : « on lit la valeur d'un attribute de route avec son accesseur. »</summary>

Non : les accesseurs sont retirés au profit de properties publiques.

Facile à manquer dans une ligne de journal qui en cite trois autres.

</details>
