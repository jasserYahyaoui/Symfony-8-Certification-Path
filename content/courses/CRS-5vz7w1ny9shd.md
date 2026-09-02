---
id: CRS-5vz7w1ny9shd
official_item: OIT-43pt66xsft9f
title: "PropertyAccess"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-02"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/components/property_access.rst"
    anchor: "usage"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    verified_at: "2026-09-02"
---

## Objectif

Lire et écrire une valeur désignée par un **chemin**, et prévoir ce qui se
passe quand ce chemin n'existe pas.

## Périmètre

Les conventions d'accesseurs par lesquelles un nom de champ se résout sur un
objet sont enseignées avec les **formulaires** (lot 07), qui les utilisent pour
lier un champ à une propriété ; elles ne sont pas reprises ici.

Le chemin de propriété porté par une violation de contrainte appartient au
**Validator** (lot 08) : c'est un libellé décrivant *où* la violation est
survenue, pas un chemin exécuté par ce composant.

## Deux notations, deux cibles

C'est la distinction fondatrice du composant :

```php
$propertyAccessor->getValue($person, '[first_name]');  // un tableau
$propertyAccessor->getValue($person, 'firstName');     // un objet
```

- Les **crochets** désignent un index de tableau.
- Le **point** désigne une propriété d'objet.

Les deux se combinent librement dans un même chemin :

```php
$propertyAccessor->setValue($person, 'children[0].firstName', 'Wouter');
// équivaut à $person->getChildren()[0]->firstName = 'Wouter'
```

## Le chemin absent : deux comportements opposés

C'est le piège central de l'item, et il ne se déduit pas.

| Cible | Chemin absent | Par défaut |
|---|---|---|
| index de tableau | `[age]` inexistant | rend **`null`** |
| propriété d'objet | `birthday` inexistante | **lève** `NoSuchPropertyException` |

Un tableau pardonne, un objet non. Les deux comportements se renversent, mais
seulement en passant par `PropertyAccess::createPropertyAccessorBuilder()` :

- `enableExceptionOnInvalidIndex()` fait lever le tableau ;
- `disableExceptionOnInvalidPropertyPath()` fait rendre `null` à l'objet.

## Demander avant d'appeler

`isReadable()` et `isWritable()` répondent si un chemin **pourrait** être lu ou
écrit, sans l'appeler :

```php
if ($propertyAccessor->isWritable($person, 'firstName')) {
    // ...
}
```

## Les méthodes magiques

Autre asymétrie : `__get()` est utilisée **par défaut**, tandis que `__call()`
doit être **activée** explicitement par le constructeur de l'accesseur.

## Les collections

Pour une propriété de collection, l'écriture passe par les méthodes d'ajout et
de retrait. Quand celles-ci ne portent pas les préfixes attendus, un extracteur
par réflexion configuré avec les préfixes réellement employés est fourni à
l'accesseur.

## Pièges d'examen

**Un index de tableau absent rend `null` ; une propriété d'objet absente lève.**
Les deux défauts sont opposés.

**Les crochets ne sont pas décoratifs** : ils choisissent la cible tableau.

**`__get()` marche seule ; `__call()` doit être activée.**

**`isReadable()` ne lit pas** — elle répond seulement si la lecture est possible.

## Points clés

- `[index]` pour un tableau, `.propriété` pour un objet, mélangeables.
- Défauts opposés sur chemin absent, renversables par le constructeur.
- `isReadable()` / `isWritable()` interrogent sans exécuter.
- `__get()` par défaut, `__call()` sur activation.

## Sources officielles

- [`components/property_access.rst`, branche 8.0](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/components/property_access.rst)
