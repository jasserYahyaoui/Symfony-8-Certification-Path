---
id: CRS-5vz7w1ny9shd
official_item: OIT-43pt66xsft9f
title: "PropertyAccess"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-03"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/components/property_access.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/components/property_access.rst"
    anchor: "usage"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    verified_at: "2026-09-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/PropertyAccess/PropertyAccessor.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/PropertyAccess/PropertyAccessor.php"
    branch: "8.0"
    symbol_or_lines: "isReadable(); isWritable(); getValue(); setValue()"
    verified_at: "2026-10-03"
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

Se tromper de notation n'est pas toléré. Exécuté sur PropertyAccess 8.0.8 :

| Chemin | Cible | Résultat |
|---|---|---|
| `a` | tableau | `NoSuchPropertyException`, qui suggère d'écrire `[a]` |
| `[x]` | `stdClass` | `NoSuchIndexException` : l'objet n'implémente pas `ArrayAccess` |

Écrire dans un tableau le modifie **par référence** : `setValue($a, '[y]', 2)`
ajoute la clé à `$a` lui-même.

## Le chemin absent : deux comportements opposés

C'est le piège central de l'item, et il ne se déduit pas.

| Cible | Chemin absent | Par défaut |
|---|---|---|
| index de tableau | `[age]` inexistant | rend **`null`** |
| propriété d'objet | `birthday` inexistante | **lève** `NoSuchPropertyException` |

Un tableau pardonne, un objet non. Les deux comportements se renversent, mais
seulement en passant par `PropertyAccess::createPropertyAccessorBuilder()` :

- `enableExceptionOnInvalidIndex()` fait lever le tableau, par une
  `NoSuchIndexException` ;
- `disableExceptionOnInvalidPropertyPath()` fait rendre `null` à l'objet.

## Le maillon `null`

Une propriété intermédiaire qui vaut `null` est un troisième cas, distinct du
chemin absent. Exécuté, `$person` à `null` sur un `Comment` :

| Chemin | Résultat |
|---|---|
| `person.firstname` | `UnexpectedTypeException` |
| `person?.firstname` | `null`, évaluation arrêtée |

L'opérateur `?` ne couvre **que** le `null` : si `person` est un objet sans
`firstname`, `person?.firstname` lève toujours `NoSuchPropertyException`.

## Demander avant d'appeler

`isReadable()` et `isWritable()` répondent par un booléen, sans lever, là où
`getValue()` et `setValue()` lèveraient :

```php
if ($propertyAccessor->isWritable($person, 'firstName')) {
    // ...
}
```

**Elles ne sont pas sans effet.** La documentation dit qu'`isReadable()` évite
d'appeler `getValue()` ; elle ne dit pas qu'elle évite le getter. Lu dans
`PropertyAccessor::isReadable()` (8.0) et exécuté avec des accesseurs qui
journalisent leurs appels :

| Appel | Méthodes de l'objet appelées |
|---|---|
| `isReadable($person, 'firstName')` | `getFirstName()` |
| `isWritable($person, 'firstName')` | aucune |
| `isWritable($person, 'children[0].firstName')` | `getChildren()` |

`isReadable()` **lit** le chemin entier ; `isWritable()` lit tout sauf le
dernier maillon, qu'elle examine sans appeler le setter. Un getter coûteux ou à
effet de bord s'exécute donc.

## Les méthodes magiques

Autre asymétrie : `__get()` est utilisée **par défaut**, tandis que `__call()`
doit être **activée** explicitement par le constructeur de l'accesseur,
`enableMagicCall()`. `__set()` est, lui aussi, actif par défaut.

Exécuté : sans `enableMagicCall()`, une lecture qui ne passerait que par
`__call()` lève `NoSuchPropertyException` ; avec, elle aboutit.

## Les collections

Pour une propriété de collection, l'écriture passe par les méthodes d'ajout et
de retrait. Quand celles-ci ne portent pas les préfixes attendus, un extracteur
par réflexion configuré avec les préfixes réellement employés est fourni à
l'accesseur.

Exécuté, sur un objet qui possède aussi `setChildren()` :

| Ancienne liste → nouvelle | Méthodes appelées |
|---|---|
| `['a']` → `['b']` | `getChildren()`, `removeChild()`, `addChild()` |
| `['a']` → `['a', 'b']` | `getChildren()`, `addChild()` |

Le couple ajout/retrait **l'emporte sur le setter**, et seuls les éléments qui
changent sont touchés.

## Pièges d'examen

**Un index de tableau absent rend `null` ; une propriété d'objet absente lève.**
Les deux défauts sont opposés.

**Les crochets ne sont pas décoratifs** : ils choisissent la cible tableau.

**`__get()` marche seule ; `__call()` doit être activée.**

**`isReadable()` appelle le getter** — elle ne fait qu'éviter l'exception.

**`?` arrête sur `null`**, pas sur une propriété absente.

## Points clés

- `[index]` pour un tableau, `.propriété` pour un objet, mélangeables.
- Défauts opposés sur chemin absent, renversables par le constructeur.
- `isReadable()` / `isWritable()` rendent un booléen au lieu de lever ;
  `isReadable()` exécute le getter.
- `?` rend `null` sur un maillon `null`.
- `__get()` par défaut, `__call()` sur activation.

## Sources officielles

- [`components/property_access.rst`, branche 8.0](https://github.com/symfony/symfony-docs/blob/8.0/components/property_access.rst)
- [`PropertyAccessor`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/PropertyAccess/PropertyAccessor.php)
