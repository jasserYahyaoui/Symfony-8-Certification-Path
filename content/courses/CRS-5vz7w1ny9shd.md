---
id: CRS-5vz7w1ny9shd
official_item: OIT-43pt66xsft9f
title: "PropertyAccess"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-09"
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
    symbol_or_lines: "isReadable(); isWritable(); getValue(); setValue(); __construct(int $magicMethodsFlags, int $throw) — THROW_ON_INVALID_INDEX, THROW_ON_INVALID_PROPERTY_PATH"
    verified_at: "2026-10-09"
outcome_sections:
  OUT-1mrdfqr9nk4q: "Deux notations, deux cibles"
  OUT-f3nt0swctvqd: "Le path absent : deux comportements opposés"
  OUT-bhjp8tarh8e7: "Le path absent : deux comportements opposés"
  OUT-6h3qaavna2z8: "Demander avant d'appeler"
  OUT-xd2zq134swyh: "Les methods magiques"
---

## Objectif

Lire et écrire une valeur désignée par un **path**, et prévoir ce qui se
passe quand ce path n'existe pas.

## Périmètre

Les conventions d'accessors par lesquelles un nom de field se résout sur un
object sont enseignées avec les **forms** (lot 07), qui les utilisent pour
lier un field à une property ; elles ne sont pas reprises ici.

Le path de property porté par une violation de constraint appartient au
**Validator** (lot 08) : c'est un libellé décrivant *où* la violation est
survenue, pas un path exécuté par ce component.

## Deux notations, deux cibles

C'est la distinction fondatrice du component :

```php
$propertyAccessor->getValue($person, '[first_name]');  // un tableau
$propertyAccessor->getValue($person, 'firstName');     // un objet
```

- Les **crochets** désignent un index de array.
- Le **point** désigne une property d'object.

Les deux se combinent librement dans un même path :

```php
$propertyAccessor->setValue($person, 'children[0].firstName', 'Wouter');
// équivaut à $person->getChildren()[0]->firstName = 'Wouter'
```

Se tromper de notation n'est pas toléré. Exécuté sur PropertyAccess 8.0.8 :

| Path | Cible | Résultat |
|---|---|---|
| `a` | array | `NoSuchPropertyException`, qui suggère d'écrire `[a]` |
| `[x]` | `stdClass` | `NoSuchIndexException` : l'object n'implémente pas `ArrayAccess` |

Écrire dans un array le modifie **par référence** : `setValue($a, '[y]', 2)`
ajoute la key à `$a` lui-même.

## Le path absent : deux comportements opposés

C'est le piège central de l'item, et il ne se déduit pas.

| Cible | Path absent | Par défaut |
|---|---|---|
| index de array | `[age]` inexistant | rend **`null`** |
| property d'object | `birthday` inexistante | **lève** `NoSuchPropertyException` |

Un array pardonne, un object non. Les deux comportements se renversent par le
accessor builder, `PropertyAccess::createPropertyAccessorBuilder()` :

- `enableExceptionOnInvalidIndex()` fait lever l'array, par une
  `NoSuchIndexException` ;
- `disableExceptionOnInvalidPropertyPath()` fait rendre `null` à l'object.

Ce n'est pas la seule voie : `new PropertyAccessor()` prend les mêmes réglages
en drapeaux — `THROW_ON_INVALID_INDEX`, `THROW_ON_INVALID_PROPERTY_PATH`,
`MAGIC_CALL` —, et une application les fixe sous `framework.property_access`.
Exécuté : avec le seul drapeau `THROW_ON_INVALID_INDEX`, l'index absent lève
une `NoSuchIndexException` et la property absente rend `null`.

## Le maillon `null`

Une property intermédiaire qui vaut `null` est un troisième cas, distinct du
path absent. Exécuté, `$person` à `null` sur un `Comment` :

| Path | Résultat |
|---|---|
| `person.firstname` | `UnexpectedTypeException` |
| `person?.firstname` | `null`, évaluation arrêtée |

L'operator `?` ne couvre **que** le `null` : si `person` est un object sans
`firstname`, `person?.firstname` lève toujours `NoSuchPropertyException`.

## Demander avant d'appeler

`isReadable()` et `isWritable()` répondent par un bool, sans lever, là où
`getValue()` et `setValue()` lèveraient :

```php
if ($propertyAccessor->isWritable($person, 'firstName')) {
    // ...
}
```

**Elles ne sont pas sans effet.** La documentation dit qu'`isReadable()` évite
d'appeler `getValue()` ; elle ne dit pas qu'elle évite le getter. Lu dans
`PropertyAccessor::isReadable()` (8.0) et exécuté avec des accessors qui
journalisent leurs appels :

| Appel | Methods de l'object appelées |
|---|---|
| `isReadable($person, 'firstName')` | `getFirstName()` |
| `isWritable($person, 'firstName')` | aucune |
| `isWritable($person, 'children[0].firstName')` | `getChildren()` |

`isReadable()` **lit** le path entier ; `isWritable()` lit tout sauf le
dernier maillon, qu'elle examine sans appeler le setter. Un getter coûteux ou à
effet de bord s'exécute donc.

## Les methods magiques

Autre asymétrie : `__get()` est utilisée **par défaut**, tandis que `__call()`
doit être **activée** explicitement par l'accessor builder,
`enableMagicCall()`. `__set()` est, lui aussi, actif par défaut.

Exécuté : sans `enableMagicCall()`, une lecture qui ne passerait que par
`__call()` lève `NoSuchPropertyException` ; avec, elle aboutit.

## Les collections

Pour une property de collection, l'écriture passe par les methods d'ajout et
de retrait. Quand celles-ci ne portent pas les préfixes attendus, un extractor
par réflexion configuré avec les préfixes réellement employés est fourni à
l'accessor.

Exécuté, sur un object qui possède aussi `setChildren()` :

| Ancienne liste → nouvelle | Methods appelées |
|---|---|
| `['a']` → `['b']` | `getChildren()`, `removeChild()`, `addChild()` |
| `['a']` → `['a', 'b']` | `getChildren()`, `addChild()` |

Le couple ajout/retrait **l'emporte sur le setter**, et seuls les éléments qui
changent sont touchés.

## Pièges d'examen

**Un index de array absent rend `null` ; une property d'object absente lève.**
Les deux défauts sont opposés.

**Les crochets ne sont pas décoratifs** : ils choisissent la cible array.

**`__get()` marche seule ; `__call()` doit être activée.**

**`isReadable()` appelle le getter** — elle ne fait qu'éviter l'exception.

**`?` arrête sur `null`**, pas sur une property absente.

## Points clés

- `[index]` pour un array, `.propriété` pour un object, mélangeables.
- Défauts opposés sur path absent, renversables par l'accessor builder.
- `isReadable()` / `isWritable()` rendent un bool au lieu de lever ;
  `isReadable()` exécute le getter.
- `?` rend `null` sur un maillon `null`.
- `__get()` par défaut, `__call()` sur activation.

## Sources officielles

- [`components/property_access.rst`, branche 8.0](https://github.com/symfony/symfony-docs/blob/8.0/components/property_access.rst)
- [`PropertyAccessor`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/PropertyAccess/PropertyAccessor.php)
