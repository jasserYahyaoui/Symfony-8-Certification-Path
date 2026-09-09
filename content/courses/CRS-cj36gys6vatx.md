---
id: CRS-cj36gys6vatx
official_item: OIT-58r9dadx916v
title: "Data transformers"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/form/data_transformers.rst"
    anchor: "using-data-transformers"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
---

## Objectif

Écrire une conversion entre deux couches de données, dans le bon sens et au bon
niveau. Les trois couches sont définies dans l'item *Form component*.

## L'interface

`DataTransformerInterface` a deux méthodes, et leurs noms se lisent depuis le
**modèle** :

| Méthode | Sens | Appelée |
|---|---|---|
| `transform()` | modèle → vue | au rendu |
| `reverseTransform()` | vue → modèle | à la soumission |

C'est l'erreur classique : `reverseTransform()` n'est pas « l'inverse de ce qu'on
veut », c'est le chemin de retour, celui qui traite la donnée du navigateur.

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

## Signaler un échec

Quand la valeur soumise ne peut pas être convertie, le transformateur lève
`TransformationFailedException`. Le formulaire la traduit en **erreur de
validation** sur le champ concerné, ce qui rend `isValid()` faux — c'est le
comportement voulu, et non une exception qui remonte à l'utilisateur.

Un transformateur ne doit jamais lever autre chose pour une donnée invalide.

## Le cas de `null`

`transform()` reçoit `null` quand le formulaire est affiché vide. Elle doit le
traiter, généralement en retournant une chaîne vide, sans supposer qu'un objet
est toujours présent.

## Le raccourci

`CallbackTransformer` prend les deux fonctions en arguments de constructeur et
évite d'écrire une classe pour une conversion d'une ligne.

## Pièges d'examen

**Le sens des deux méthodes se lit depuis le modèle, pas depuis l'intuition.**
L'une va du modèle vers la vue et s'exécute au rendu ; l'autre fait le chemin de
retour et s'exécute à la soumission. Ce n'est pas « l'inverse de ce que je
veux ».

**Modèle et vue ne s'attachent pas au même endroit.** Convertir une saisie en
objet métier est une affaire de modèle ; changer un format d'affichage est une
affaire de vue. Se tromper de niveau donne un transformateur qui ne se déclenche
jamais.

**Un échec de conversion se signale par l'exception dédiée**, que le formulaire
traduit en erreur de validation sur le champ. Lever autre chose remonte comme
une erreur serveur.

## Points clés

- `transform()` va du modèle vers la vue, `reverseTransform()` fait le retour.
- `addModelTransformer()` entre modèle et normalisée ;
  `addViewTransformer()` entre normalisée et vue.
- `TransformationFailedException` devient une erreur de validation.
- `transform()` doit gérer `null`.
- `CallbackTransformer` pour les cas courts.

## Sources officielles

- [How to Use Data Transformers](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/form/data_transformers.rst)
