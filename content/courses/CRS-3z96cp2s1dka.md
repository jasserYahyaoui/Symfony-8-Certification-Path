---
id: CRS-3z96cp2s1dka
official_item: OIT-fr58jzaj6jtb
title: "Serializer"
content_level: DEEP
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-14"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/serializer.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/serializer.rst"
    anchor: "the-serialization-process-normalizers-and-encoders"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    verified_at: "2026-09-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Serializer/Normalizer/AbstractNormalizer.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Serializer/Normalizer/AbstractNormalizer.php"
    branch: "8.0"
    symbol_or_lines: "isCircularReference(); getAllowedAttributes() — returns false (no filtering) without a class metadata factory"
    verified_at: "2026-10-09"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Serializer/Normalizer/NormalizerInterface.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Serializer/Normalizer/NormalizerInterface.php"
    branch: "8.0"
    symbol_or_lines: "normalize() — @return array|string|int|float|bool|\\ArrayObject|null"
    verified_at: "2026-10-13"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/Resources/config/serializer.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Resources/config/serializer.php"
    branch: "8.0"
    symbol_or_lines: "serializer.normalizer.object: priority -1000, receives property_info"
    verified_at: "2026-10-14"
outcome_sections:
  OUT-g6j22cqpx1ga: "Deux étapes, deux responsabilités"
  OUT-a95xx41fw6hn: "Pièges d'examen"
  OUT-f4j1y7n6y64g: "Ce qui est pris par défaut"
  OUT-r6x291jsb0qt: "Choisir selon le contexte"
  OUT-4bynfsdjvrc5: "Les références circulaires"
  OUT-hkvn3vxj4fyb: "Quel normalizer agit ?"
  OUT-m8wfdy7569p8: "Dénormaliser"
  OUT-cf0sd5w3fzhe: "Le contexte"
---

## Objectif

Savoir en quoi se décompose une serialization, et prévoir ce qui figure ou non
dans le résultat.

## Périmètre

Le syllabus exclut les **bridges vers des services tiers** : les formats fournis
par des projets extérieurs ne sont pas au programme.

L'accès aux valeurs par path appartient au lot 24 (PropertyAccess) ; il est
ici seulement l'outil sur lequel s'appuie la normalization d'objects. La
serialization des messages transportés appartient au lot 11 (Messenger).

## Deux étapes, deux responsabilités

| Étape | Role |
|---|---|
| **Normalizers** | valeur PHP ⇄ **représentation normalisée** |
| **Encoders** | représentation normalisée ⇄ **format** |

Un normalizer décide *quelles properties* sont retenues, *quelle valeur* elles
portent et *quel nom* elles prennent. Un encoder ne sait qu'une chose :
produire et lire un format.

**La représentation normalisée n'est pas toujours un array.**
`NormalizerInterface::normalize()` déclare `array|string|int|float|bool|\ArrayObject|null`.
Exécuté sur Serializer 8.0.15 :

| Valeur normalisée | Résultat | JSON |
|---|---|---|
| object ordinaire, par `ObjectNormalizer` | array | `{"name":"Ada"}` |
| `DateTimeImmutable`, par `DateTimeNormalizer` | **string** RFC 3339 | `"2026-01-02T03:04:05+00:00"` |
| enum *backed* `'H'`, par `BackedEnumNormalizer` | **string** `'H'` | `"H"` |
| l'int `42` | `42`, sans normalizer | `42` |

À la lecture, le decoder rend ce que porte le format : `"H"` reste une string,
que le denormalizer change en `Suit::Hearts`.

Le service par défaut compose une **liste triée de normalizers** et **un seul
encoder**, celui du format demandé.

`ObjectNormalizer` est le principal : il travaille par réflexion et s'appuie sur
le component d'accès aux properties.

Encoders fournis par défaut : **JSON, XML, CSV, YAML**. Exécuté, l'array
`['k' => 'v']` donne `{"k":"v"}`, `<response><k>v</k></response>`, `k` puis `v`
sur deux lignes, et `{ k: v }`. Un format sans encoder lève une
`UnsupportedFormatException`.

## Ce qui est pris par défaut

`ObjectNormalizer` lit l'object par le component d'accès aux properties : les
properties **publiques**, et les methods dont le nom commence par `get`, `has`,
`is` ou `can`, préfixe retiré. Exécuté sur Serializer 8.0.15 :

| Membre | Dans l'output |
|---|---|
| property publique | oui |
| property privée avec `getWithGetter()` | oui, sous `withGetter` |
| `isActive()`, `hasKids()`, `canFly()` | oui : `active`, `kids`, `fly` |
| property protégée ou privée sans accessor | **non** |
| `fetchThing()` | non : préfixe inconnu |

`#[Ignore]` exclut définitivement une property ou une method.

## Choisir selon le contexte

Là où `#[Ignore]` exclut partout, les **groups** excluent *ici* et gardent
*ailleurs* :

```php
$json = $serializer->serialize($person, 'json', ['groups' => 'public-view']);
```

Une property sans group déclaré **ne sort pas** quand un group est demandé.
Exécuté, avec `name` et `fullName` dans `public-view`, `age` dans `admin-view`,
`email` sans group :

| Contexte | Sortie |
|---|---|
| aucun group | les quatre |
| `'groups' => 'public-view'` | `name`, `fullName` |
| `'groups' => '*'` | les quatre |
| `'groups' => ['Default']` | **rien** |

Il n'y a pas de group `Default` implicite, contrairement au Validator : `*`
est la seule valeur qui reprend les properties sans group.

Groups et `#[Ignore]` sont des **métadonnées** : le service `serializer` du
framework les lit, mais un `ObjectNormalizer` construit sans fabrique de
métadonnées les ignore. Exécuté : `groups` à `public-view` rend alors toutes les
properties, y compris celle marquée `#[Ignore]`.

## Renommer

`#[SerializedName]` change le nom d'un attribute dans le format produit, sans
toucher à la property PHP.

Il agit par le convertisseur de noms `MetadataAwareNameConverter`. Le service
`serializer` du framework le branche ; un `Serializer` construit à la main
**sans** lui ignore l'attribute. Exécuté : `fullName` sans convertisseur,
`full_name` avec.

## Quel normalizer agit ?

Le `Serializer` interroge ses normalizers **dans l'ordre** et confie la valeur
au **premier** dont `supportsNormalization()` répond vrai. `getSupportedTypes()`
déclare les types visés, pour éviter les appels inutiles.

Exécuté avec un `MoneyNormalizer` qui rend `"12.50 EUR"` :

| Ordre | `price` dans le JSON |
|---|---|
| `[MoneyNormalizer, ObjectNormalizer]` | `"12.50 EUR"` |
| `[ObjectNormalizer, MoneyNormalizer]` | `{"cents":1250,"currency":"EUR"}` |

`ObjectNormalizer` accepte presque tout object : placé devant, il avale aussi les
dates. Le framework l'enregistre avec la priority **−1000**, si bien qu'un
normalizer de l'application, autoconfiguré, passe avant lui.

## Dénormaliser

Le contrôle des types vient d'un **extractor de types**, que le service du
framework reçoit. Exécuté sur une property `int $qty` :

| JSON | Sans extractor | Avec extractor |
|---|---|---|
| `{"qty":"7"}` | `7` : PHP convertit | `NotNormalizableValueException` |
| `{"qty":"x"}` | `NotNormalizableValueException` | idem |

`COLLECT_DENORMALIZATION_ERRORS` rassemble les erreurs dans une
`PartialDenormalizationException` au lieu de s'arrêter à la première.
`#[MapRequestPayload]` dénormalise avec cette option : les erreurs collectées y
deviennent des violations, comme celles d'une constraint.

`OBJECT_TO_POPULATE` remplit un object existant : exécuté, `{"qty":7}` change
`qty` et laisse `note` intact, et c'est bien le même object qui est rendu.

## Le contexte

Le troisième argument règle **un** appel : `skip_null_values` retire les
properties nulles, `datetime_format` change le format des dates — exécuté,
`Y-m-d` rend `"2026-01-02"`.

## Les références circulaires

Deux objects qui se pointent l'un l'autre feraient boucler la normalization. Le
component lève une `CircularReferenceException`.

**Le seuil est réglable** par la key `circular_reference_limit` du contexte :
c'est le nombre de fois qu'un même object est sérialisé avant d'être considéré
comme circulaire. **Sa default value est `1`.**

Un callback peut remplacer l'exception — utile pour rendre un
identifier plutôt que l'object entier.

Exécuté, `Org` et `Mem` qui se pointent :

| Contexte | Résultat |
|---|---|
| par défaut | `CircularReferenceException` (*configured limit: 1*) |
| `circular_reference_limit` à `2` | toujours l'exception, plus tard |
| `circular_reference_handler` qui rend `'ref'` | `{"members":[{"org":"ref","name":"m"}]}` |
| un même object dans deux properties sœurs | aucune exception |

Le compteur suit **la branche en cours**, pas le document entier : un object
répété côte à côte n'est pas circulaire.

## Pièges d'examen

**Normalizer puis encoder**, dans les deux sens — mais l'étape du milieu
n'est un array que pour un object : une date ou une enum deviennent une string.

**Plusieurs normalizers, un seul encoder** par opération ; le **premier**
normalizer qui accepte la valeur l'emporte.

**`"7"` pour un `int`** n'est refusé qu'avec un extractor de types.

**Une property sans group disparaît** dès qu'un group est demandé — si le
normalizer dispose de ses métadonnées.

**`circular_reference_limit` vaut `1` par défaut**, et relever le seuil ne
supprime pas l'exception d'un vrai cycle.

**`groups` à `Default` ne rend rien** : seul `*` reprend les properties sans
group.

## Points clés

- Normalizer = valeur ⇄ représentation normalisée (array pour un object,
  string pour une date ou une enum) ; encoder = représentation ⇄ format.
- `#[Ignore]` exclut partout, les groups excluent au cas par cas.
- `#[SerializedName]` renomme dans le format seul.
- Ordre des normalizers ; types contrôlés par l'extractor ; contexte par appel.
- Référence circulaire : exception, seuil `1`, rappel possible.

## Sources officielles

- [`serializer.rst`, branche 8.0](https://github.com/symfony/symfony-docs/blob/8.0/serializer.rst)
- [`AbstractNormalizer`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Serializer/Normalizer/AbstractNormalizer.php)
