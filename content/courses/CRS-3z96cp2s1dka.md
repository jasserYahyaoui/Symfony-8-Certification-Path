---
id: CRS-3z96cp2s1dka
official_item: OIT-fr58jzaj6jtb
title: "Serializer"
content_level: DEEP
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-14"
format: pedagogical
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

## L'essentiel

Une serialization se fait en **deux étapes** : un **normalizer** change la
valeur PHP en représentation normalisée, un **encoder** change celle-ci en
format. Le normalizer décide de tout ce qui compte à l'examen — quelles
properties sortent, sous quel nom, avec quelle valeur ; l'encoder ne sait que
produire et lire un format. Les bridges vers des services tiers sont hors
programme.

## Le schéma

```text
serialize()     valeur ─normalizer──▶ représentation normalisée ──encoder──▶ JSON / XML / CSV / YAML
deserialize()   valeur ◀denormalizer── représentation normalisée ◀─decoder── format
                 │ liste triée : le PREMIER qui accepte la valeur agit       un seul encoder : celui du format
                 │ ObjectNormalizer : properties publiques + get/has/is/can
                 │ #[Ignore] partout · groups au cas par cas · #[SerializedName] renomme
                 │ contexte = 3e argument, pour UN appel
```

## L'exemple : une vue publique

```php
$json = $serializer->serialize($person, 'json', ['groups' => 'public-view']);

$order = $serializer->deserialize('{"qty":7}', Order::class, 'json', [
    AbstractNormalizer::OBJECT_TO_POPULATE => $existingOrder,
]);
```

## Deux étapes, deux responsabilités

La représentation normalisée **n'est pas toujours un array** :

| Valeur | Normalizer | JSON |
|---|---|---|
| object ordinaire | `ObjectNormalizer` → array | `{"name":"Ada"}` |
| `DateTimeImmutable` | `DateTimeNormalizer` → **string** RFC 3339 | `"2026-01-02T03:04:05+00:00"` |
| enum *backed* `'H'` | `BackedEnumNormalizer` → **string** | `"H"` |
| l'int `42` | aucun | `42` |

Le service par défaut compose une **liste triée de normalizers** et **un seul
encoder** par opération. Encoders fournis : **JSON, XML, CSV, YAML** —
`['k' => 'v']` donne `{"k":"v"}`, `<response><k>v</k></response>`, `k` puis `v`
sur deux lignes, `{ k: v }`. Un format sans encoder lève une
`UnsupportedFormatException`.

## Ce qui est pris par défaut

`ObjectNormalizer` lit l'object par l'accès aux properties :

| Membre | Dans l'output |
|---|---|
| property publique | oui |
| property privée avec `getWithGetter()` | oui, sous `withGetter` |
| `isActive()`, `hasKids()`, `canFly()` | oui : `active`, `kids`, `fly` |
| property protégée ou privée sans accessor | **non** |
| `fetchThing()` | non : préfixe inconnu |

`#[Ignore]` exclut une property ou une method **partout**.

## Choisir selon le contexte

Les **groups** excluent *ici* et gardent *ailleurs*. Avec `name` et `fullName`
dans `public-view`, `age` dans `admin-view`, `email` sans group :

| Contexte | Sortie |
|---|---|
| aucun group | les quatre |
| `'groups' => 'public-view'` | `name`, `fullName` |
| `'groups' => '*'` | les quatre |
| `'groups' => ['Default']` | **rien** |

Pas de group `Default` implicite, contrairement au Validator : `*` seul reprend
les properties sans group.

Groups, `#[Ignore]` et `#[SerializedName]` (qui renomme dans le format seul)
sont des **métadonnées**. Le service `serializer` du framework les lit ; un
`ObjectNormalizer` construit sans fabrique de métadonnées les ignore — groups
demandés, toutes les properties sortent, y compris celle marquée `#[Ignore]`.
De même, sans `MetadataAwareNameConverter`, `fullName` n'est pas renommé.

## Quel normalizer agit ?

Le `Serializer` confie la valeur au **premier** normalizer dont
`supportsNormalization()` répond vrai ; `getSupportedTypes()` évite les appels
inutiles.

| Ordre | `price` dans le JSON |
|---|---|
| `[MoneyNormalizer, ObjectNormalizer]` | `"12.50 EUR"` |
| `[ObjectNormalizer, MoneyNormalizer]` | `{"cents":1250,"currency":"EUR"}` |

Le framework enregistre `ObjectNormalizer` à la priority **−1000** : un
normalizer de l'application, autoconfiguré, passe avant lui.

## Dénormaliser

Le decoder rend ce que porte le format (`"H"` reste une string) ; le
denormalizer en fait `Suit::Hearts`. Le contrôle des types vient d'un
**extractor de types**, que le service du framework reçoit :

| JSON vers `int $qty` | Sans extractor | Avec extractor |
|---|---|---|
| `{"qty":"7"}` | `7` : PHP convertit | `NotNormalizableValueException` |
| `{"qty":"x"}` | `NotNormalizableValueException` | idem |

`COLLECT_DENORMALIZATION_ERRORS` rassemble les erreurs dans une
`PartialDenormalizationException` ; `#[MapRequestPayload]` l'active et en fait
des violations. `OBJECT_TO_POPULATE` remplit **le même** object : `{"qty":7}`
change `qty` et laisse `note` intact.

## Le contexte

Le troisième argument règle **un** appel : `skip_null_values` retire les
properties nulles, `datetime_format` à `Y-m-d` rend `"2026-01-02"`.

## Les références circulaires

Deux objects qui se pointent lèvent une `CircularReferenceException`.
`circular_reference_limit` — combien de fois un object est sérialisé avant
d'être jugé circulaire — vaut **`1`** par défaut.

| Contexte | Résultat |
|---|---|
| par défaut | `CircularReferenceException` |
| limite à `2` | l'exception, plus tard |
| `circular_reference_handler` qui rend `'ref'` | `{"members":[{"org":"ref","name":"m"}]}` |
| un même object dans deux properties sœurs | aucune exception |

Le compteur suit **la branche en cours**, pas le document entier.

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

## Application

- Une API publique et une API d'administration sur la même entité → deux
  groups, `public-view` et `admin-view`.
- Un PATCH qui ne change qu'un field → `OBJECT_TO_POPULATE`.

## QCM d'entraînement

**Question 1.** Que produit `$serializer->serialize(Suit::Hearts, 'json')`
pour une enum *backed* de valeur `'H'` ?

- **A.** `{"name":"Hearts","value":"H"}`
- **B.** `"Hearts"`, le nom du case
- **C.** `"H"`
- **D.** Une `UnsupportedFormatException`

<details>
<summary>Correction</summary>

**Bonne réponse : C.** `BackedEnumNormalizer` rend la valeur du case, une
string ; l'étape du milieu n'est donc pas un array, et le JSON est `"H"`.

</details>

**Question 2.** Un `Serializer` construit à la main, sans extractor de types,
dénormalise `{"qty":"x"}` vers une property `int $qty`. Résultat ?

- **A.** `qty` vaut `0`
- **B.** Une `NotNormalizableValueException`
- **C.** `qty` vaut `null`, l'erreur est ignorée
- **D.** `qty` vaut `"x"` : le type n'est pas contrôlé

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Sans extractor, PHP tente la conversion : `"7"`
deviendrait `7`, mais `"x"` n'a aucune valeur entière et l'appel lève.

</details>

**Question 3.** Que donne l'encoder CSV pour l'array `['k' => 'v']` ?

- **A.** `k,v` sur une seule ligne
- **B.** `v` seul, les keys sont perdues
- **C.** `k` puis `v`, sur deux lignes
- **D.** `"k";"v"`, entre guillemets

<details>
<summary>Correction</summary>

**Bonne réponse : C.** La première ligne porte les headers, tirés des keys ;
la suivante porte les valeurs, une ligne par enregistrement.

</details>

**Question 4.** Un `Serializer` reçoit `[MoneyNormalizer, ObjectNormalizer]`.
`MoneyNormalizer` rend `"12.50 EUR"`. Que vaut `price` dans le JSON ?

- **A.** `"12.50 EUR"`
- **B.** `{"cents":1250,"currency":"EUR"}`
- **C.** Les deux, fusionnés dans un object
- **D.** Une exception : deux normalizers acceptent

<details>
<summary>Correction</summary>

**Bonne réponse : A.** Le premier normalizer qui accepte la valeur l'emporte ;
dans l'ordre inverse, `ObjectNormalizer` aurait rendu l'object de ses
properties.

</details>

## Fiche express

**Si tu dois retenir seulement 8 choses :**

- **Normalizer** (valeur ⇄ représentation) puis **encoder** (⇄ format).
- Représentation : array pour un object, **string** pour une date ou une enum.
- Plusieurs normalizers, **un** encoder ; le **premier** qui accepte agit.
- `ObjectNormalizer` : public + `get`/`has`/`is`/`can`.
- `#[Ignore]` partout ; **groups** au cas par cas ; `Default` ne rend rien,
  `*` tout.
- Métadonnées lues par le service du framework, pas par un normalizer nu.
- `"7"` vers `int` : refusé **seulement** avec l'extractor de types.
- Cycle : exception, limite **`1`**, handler pour rendre un identifier.

Moyen mnémotechnique : **« Normaliser décide, encoder écrit »**.

## Sources officielles

- [`serializer.rst`, branche 8.0](https://github.com/symfony/symfony-docs/blob/8.0/serializer.rst)
- [`AbstractNormalizer`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Serializer/Normalizer/AbstractNormalizer.php)

