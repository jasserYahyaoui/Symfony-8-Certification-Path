---
id: CRS-cj36gys6vatx
official_item: OIT-58r9dadx916v
title: "Data transformers"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-25"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/form/data_transformers.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/form/data_transformers.rst"
    anchor: "using-data-transformers"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Form/DataTransformerInterface.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/DataTransformerInterface.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "DataTransformerInterface::transform(), reverseTransform()"
    verified_at: "2026-09-25"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Form/FormConfigBuilder.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/FormConfigBuilder.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "addModelTransformer() prepends, addViewTransformer() appends"
    verified_at: "2026-09-25"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Form/Extension/Core/EventListener/TransformationFailureListener.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/Extension/Core/EventListener/TransformationFailureListener.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "convertTransformationFailureToFormError(), invalid_message"
    verified_at: "2026-09-25"
---

## Objectif

Écrire une conversion entre deux couches de données, dans le bon sens et au bon
niveau. Les trois couches sont définies dans l'item *Form component*.

## L'interface

`DataTransformerInterface` a deux méthodes, et leurs noms se lisent depuis le
**modèle** :

| Méthode | Sens | Appelée |
|---|---|---|
| `transform()` | modèle → vue | quand les données sont posées sur le formulaire |
| `reverseTransform()` | vue → modèle | à la soumission |

C'est l'erreur classique : `reverseTransform()` n'est pas « l'inverse de ce qu'on
veut », c'est le chemin de retour, celui qui traite la donnée du navigateur.

`transform()` n'est **pas** appelée au rendu. Exécuté avec `symfony/form`
8.0.15 et des transformateurs qui journalisent : `getForm()` sur des données
initiales les appelle, `createView()` n'appelle rien. Après une soumission
réussie, les `transform()` des transformateurs de vue sont rappelées pour
resynchroniser la vue.

## Deux niveaux d'attachement

```php
$builder->get('issue')->addModelTransformer($transformer);
$builder->get('issue')->addViewTransformer($transformer);
```

| Méthode | Convertit entre |
|---|---|
| `addModelTransformer()` | donnée du **modèle** et donnée normalisée |
| `addViewTransformer()` | donnée **normalisée** et donnée de vue |

Le choix dépend de ce que l'on convertit. Transformer un numéro saisi en objet
métier est une affaire de modèle ; changer le format d'affichage d'une valeur
déjà normalisée est une affaire de vue.

## L'ordre d'une chaîne

`FormConfigBuilder::addViewTransformer()` **ajoute à la fin** ;
`addModelTransformer()` **insère en tête**. Exécuté avec `M1`, `M2`, `V1`, `V2`
ajoutés dans cet ordre :

```text
données posées : M2 → M1 → V1 → V2
soumission     : V2 → V1 → M1 → M2   (reverseTransform)
```

Le dernier transformateur de modèle ajouté est donc le plus proche du modèle.

## Signaler un échec

Quand la valeur soumise ne peut pas être convertie, le transformateur lève
`TransformationFailedException`. Exécuté : le champ n'est plus
**synchronisé** (`isSynchronized()` à `false`), sa donnée vaut `null`, sa donnée
de vue garde la saisie `'42'`, et le formulaire est invalide avec
« This value is not valid. ».

Ce message vient de l'option **`invalid_message`** du champ ; celui de
l'exception n'est jamais montré. `setInvalidMessage()` sur l'exception fixe un
message public, mais seulement quand l'extension Validator est active — c'est
`FormValidator` qui le lit. Exécuté sans elle : l'option `invalid_message`
s'applique.

Toute autre exception **sort** de `submit()` : exécuté, une
`InvalidArgumentException` levée dans `reverseTransform()` remonte telle quelle.

## Valeurs vides

La documentation demande que `transform()` rende, pour `null`, l'équivalent vide
du type **cible** : chaîne vide, `0` pour un entier, `0.0` pour un flottant. Un
transformateur qui produit une chaîne rend donc `''`. Retourner `null` quand
même ne casse pas un champ texte : exécuté, sa variable de vue `value` vaut
`''`.

Dans l'autre sens, la convention de l'interface est que `reverseTransform()`
retourne `null` pour une chaîne vide. Exécuté sur un champ texte laissé vide :
un transformateur de **vue** reçoit `''`, un transformateur de **modèle** reçoit
déjà `null`.

## Le raccourci

`CallbackTransformer` prend les deux fonctions en arguments de constructeur —
`transform` puis `reverseTransform` — et évite d'écrire une classe pour une
conversion d'une ligne.

## Un exemple documenté qui ignore le vide

L'exemple `CallbackTransformer` de `data_transformers.rst` (8.0) convertit des
tags par `implode(', ', …)` et `explode(', ', …)`, sans traiter `null`. Exécuté
avec PHP 8.4 :

| Situation | Résultat |
|---|---|
| tags `['a', 'b']` posés | vue `'a, b'` |
| tags `null` posés | `TypeError` levée par `implode()` |
| champ soumis vide | dépréciation d'`explode()` sur `null`, donnée `['']` |

Traiter le vide dans chaque fonction suffit : `null` donne `''` dans un sens,
`[]` dans l'autre.

## Pièges d'examen

**`transform()` s'exécute quand les données sont posées, pas au rendu.**

**Le sens des deux méthodes se lit depuis le modèle.** L'une va vers la vue,
l'autre fait le chemin de retour à la soumission.

**Modèle et vue ne s'attachent pas au même endroit**, et les transformateurs de
modèle s'empilent en tête.

**Un échec se signale par l'exception dédiée** ; tout autre type sort de
`submit()`. Le message affiché est `invalid_message`, pas celui de l'exception.

**Pour `null`, la valeur rendue suit le type cible** : `''` pour une chaîne.

**L'exemple documenté des tags échoue sur `null`** : `TypeError`.

## Points clés

- `transform()` : modèle → vue, à la pose des données ; `reverseTransform()` :
  retour, à la soumission.
- `addModelTransformer()` insère en tête ; `addViewTransformer()` ajoute à la
  fin.
- `TransformationFailedException` : champ non synchronisé, erreur
  `invalid_message`.
- `setInvalidMessage()` exige l'extension Validator.
- `CallbackTransformer` pour les cas courts.

## Sources officielles

- [How to Use Data Transformers](https://github.com/symfony/symfony-docs/blob/8.0/form/data_transformers.rst)
- [Form 8.0, `DataTransformerInterface`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/DataTransformerInterface.php) et [`FormConfigBuilder`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/FormConfigBuilder.php)
- [Form 8.0, `TransformationFailureListener`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/Extension/Core/EventListener/TransformationFailureListener.php)
