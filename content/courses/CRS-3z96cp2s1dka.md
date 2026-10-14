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
---

## Objectif

Savoir en quoi se décompose une sérialisation, et prévoir ce qui figure ou non
dans le résultat.

## Périmètre

Le syllabus exclut les **ponts vers des services tiers** : les formats fournis
par des projets extérieurs ne sont pas au programme.

L'accès aux valeurs par chemin appartient au lot 24 (PropertyAccess) ; il est
ici seulement l'outil sur lequel s'appuie la normalisation d'objets. La
sérialisation des messages transportés appartient au lot 11 (Messenger).

## Deux étapes, deux responsabilités

| Étape | Rôle |
|---|---|
| **Normaliseurs** | valeur PHP ⇄ **représentation normalisée** |
| **Encodeurs** | représentation normalisée ⇄ **format** |

Un normaliseur décide *quelles propriétés* sont retenues, *quelle valeur* elles
portent et *quel nom* elles prennent. Un encodeur ne sait qu'une chose :
produire et lire un format.

**La représentation normalisée n'est pas toujours un tableau.**
`NormalizerInterface::normalize()` déclare `array|string|int|float|bool|\ArrayObject|null`.
Exécuté sur Serializer 8.0.15 :

| Valeur normalisée | Résultat | JSON |
|---|---|---|
| objet ordinaire, par `ObjectNormalizer` | tableau | `{"name":"Ada"}` |
| `DateTimeImmutable`, par `DateTimeNormalizer` | **chaîne** RFC 3339 | `"2026-01-02T03:04:05+00:00"` |
| enum *backed* `'H'`, par `BackedEnumNormalizer` | **chaîne** `'H'` | `"H"` |
| l'entier `42` | `42`, sans normaliseur | `42` |

À la lecture, le décodeur rend ce que porte le format : `"H"` reste une chaîne,
que le dénormaliseur change en `Suit::Hearts`.

Le service par défaut compose une **liste triée de normaliseurs** et **un seul
encodeur**, celui du format demandé.

`ObjectNormalizer` est le principal : il travaille par réflexion et s'appuie sur
le composant d'accès aux propriétés.

Encodeurs fournis par défaut : **JSON, XML, CSV, YAML**. Exécuté, le tableau
`['k' => 'v']` donne `{"k":"v"}`, `<response><k>v</k></response>`, `k` puis `v`
sur deux lignes, et `{ k: v }`. Un format sans encodeur lève une
`UnsupportedFormatException`.

## Ce qui est pris par défaut

`ObjectNormalizer` lit l'objet par le composant d'accès aux propriétés : les
propriétés **publiques**, et les méthodes dont le nom commence par `get`, `has`,
`is` ou `can`, préfixe retiré. Exécuté sur Serializer 8.0.15 :

| Membre | Dans la sortie |
|---|---|
| propriété publique | oui |
| propriété privée avec `getWithGetter()` | oui, sous `withGetter` |
| `isActive()`, `hasKids()`, `canFly()` | oui : `active`, `kids`, `fly` |
| propriété protégée ou privée sans accesseur | **non** |
| `fetchThing()` | non : préfixe inconnu |

`#[Ignore]` exclut définitivement une propriété ou une méthode.

## Choisir selon le contexte

Là où `#[Ignore]` exclut partout, les **groupes** excluent *ici* et gardent
*ailleurs* :

```php
$json = $serializer->serialize($person, 'json', ['groups' => 'public-view']);
```

Une propriété sans groupe déclaré **ne sort pas** quand un groupe est demandé.
Exécuté, avec `name` et `fullName` dans `public-view`, `age` dans `admin-view`,
`email` sans groupe :

| Contexte | Sortie |
|---|---|
| aucun groupe | les quatre |
| `'groups' => 'public-view'` | `name`, `fullName` |
| `'groups' => '*'` | les quatre |
| `'groups' => ['Default']` | **rien** |

Il n'y a pas de groupe `Default` implicite, contrairement au Validator : `*`
est la seule valeur qui reprend les propriétés sans groupe.

Groupes et `#[Ignore]` sont des **métadonnées** : le service `serializer` du
framework les lit, mais un `ObjectNormalizer` construit sans fabrique de
métadonnées les ignore. Exécuté : `groups` à `public-view` rend alors toutes les
propriétés, y compris celle marquée `#[Ignore]`.

## Renommer

`#[SerializedName]` change le nom d'un attribut dans le format produit, sans
toucher à la propriété PHP.

Il agit par le convertisseur de noms `MetadataAwareNameConverter`. Le service
`serializer` du framework le branche ; un `Serializer` construit à la main
**sans** lui ignore l'attribut. Exécuté : `fullName` sans convertisseur,
`full_name` avec.

## Quel normaliseur agit ?

Le `Serializer` interroge ses normaliseurs **dans l'ordre** et confie la valeur
au **premier** dont `supportsNormalization()` répond vrai. `getSupportedTypes()`
déclare les types visés, pour éviter les appels inutiles.

Exécuté avec un `MoneyNormalizer` qui rend `"12.50 EUR"` :

| Ordre | `price` dans le JSON |
|---|---|
| `[MoneyNormalizer, ObjectNormalizer]` | `"12.50 EUR"` |
| `[ObjectNormalizer, MoneyNormalizer]` | `{"cents":1250,"currency":"EUR"}` |

`ObjectNormalizer` accepte presque tout objet : placé devant, il avale aussi les
dates. Le framework l'enregistre avec la priorité **−1000**, si bien qu'un
normaliseur de l'application, autoconfiguré, passe avant lui.

## Dénormaliser

Le contrôle des types vient d'un **extracteur de types**, que le service du
framework reçoit. Exécuté sur une propriété `int $qty` :

| JSON | Sans extracteur | Avec extracteur |
|---|---|---|
| `{"qty":"7"}` | `7` : PHP convertit | `NotNormalizableValueException` |
| `{"qty":"x"}` | `NotNormalizableValueException` | idem |

`COLLECT_DENORMALIZATION_ERRORS` rassemble les erreurs dans une
`PartialDenormalizationException` au lieu de s'arrêter à la première.

`OBJECT_TO_POPULATE` remplit un objet existant : exécuté, `{"qty":7}` change
`qty` et laisse `note` intact, et c'est bien le même objet qui est rendu.

## Le contexte

Le troisième argument règle **un** appel : `skip_null_values` retire les
propriétés nulles, `datetime_format` change le format des dates — exécuté,
`Y-m-d` rend `"2026-01-02"`.

## Les références circulaires

Deux objets qui se pointent l'un l'autre feraient boucler la normalisation. Le
composant lève une `CircularReferenceException`.

**Le seuil est réglable** par la clé `circular_reference_limit` du contexte :
c'est le nombre de fois qu'un même objet est sérialisé avant d'être considéré
comme circulaire. **Sa valeur par défaut est `1`.**

Une fonction de rappel peut remplacer l'exception — utile pour rendre un
identifiant plutôt que l'objet entier.

Exécuté, `Org` et `Mem` qui se pointent :

| Contexte | Résultat |
|---|---|
| par défaut | `CircularReferenceException` (*configured limit: 1*) |
| `circular_reference_limit` à `2` | toujours l'exception, plus tard |
| `circular_reference_handler` qui rend `'ref'` | `{"members":[{"org":"ref","name":"m"}]}` |
| un même objet dans deux propriétés sœurs | aucune exception |

Le compteur suit **la branche en cours**, pas le document entier : un objet
répété côte à côte n'est pas circulaire.

## Pièges d'examen

**Normaliseur puis encodeur**, dans les deux sens — mais l'étape du milieu
n'est un tableau que pour un objet : une date ou une enum deviennent une chaîne.

**Plusieurs normaliseurs, un seul encodeur** par opération ; le **premier**
normaliseur qui accepte la valeur l'emporte.

**`"7"` pour un `int`** n'est refusé qu'avec un extracteur de types.

**Une propriété sans groupe disparaît** dès qu'un groupe est demandé — si le
normaliseur dispose de ses métadonnées.

**`circular_reference_limit` vaut `1` par défaut**, et relever le seuil ne
supprime pas l'exception d'un vrai cycle.

**`groups` à `Default` ne rend rien** : seul `*` reprend les propriétés sans
groupe.

## Points clés

- Normaliseur = valeur ⇄ représentation normalisée (tableau pour un objet,
  chaîne pour une date ou une enum) ; encodeur = représentation ⇄ format.
- `#[Ignore]` exclut partout, les groupes excluent au cas par cas.
- `#[SerializedName]` renomme dans le format seul.
- Ordre des normaliseurs ; types contrôlés par l'extracteur ; contexte par appel.
- Référence circulaire : exception, seuil `1`, rappel possible.

## Sources officielles

- [`serializer.rst`, branche 8.0](https://github.com/symfony/symfony-docs/blob/8.0/serializer.rst)
- [`AbstractNormalizer`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Serializer/Normalizer/AbstractNormalizer.php)
