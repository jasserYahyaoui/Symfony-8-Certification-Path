---
id: CRS-3z96cp2s1dka
official_item: OIT-fr58jzaj6jtb
title: "Serializer"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-03"
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
    symbol_or_lines: "isCircularReference(); getAllowedAttributes()"
    verified_at: "2026-10-03"
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

## Deux étapes, jamais une

C'est le concept central : **les données passent toujours par un tableau**,
dans les deux sens.

| Étape | Rôle |
|---|---|
| **Normaliseurs** | objet ⇄ **tableau** |
| **Encodeurs** | tableau ⇄ **format** |

Un normaliseur décide *quelles propriétés* sont retenues, *quelle valeur* elles
portent et *quel nom* elles prennent. Un encodeur ne sait qu'une chose :
produire et lire un format.

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

## Renommer

`#[SerializedName]` change le nom d'un attribut dans le format produit, sans
toucher à la propriété PHP.

Il agit par le convertisseur de noms `MetadataAwareNameConverter`. Le service
`serializer` du framework le branche ; un `Serializer` construit à la main
**sans** lui ignore l'attribut. Exécuté : `fullName` sans convertisseur,
`full_name` avec.

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

**Tout passe par un tableau**, dans les deux sens : normaliseur puis encodeur.

**Plusieurs normaliseurs, un seul encodeur** par opération.

**Une propriété sans groupe disparaît** dès qu'un groupe est demandé.

**`circular_reference_limit` vaut `1` par défaut**, et relever le seuil ne
supprime pas l'exception d'un vrai cycle.

**Seules les propriétés publiques** sont lues directement ; une privée ne sort
que par un accesseur.

**`groups` à `Default` ne rend rien** : seul `*` reprend les propriétés sans
groupe.

## Points clés

- Normaliseur = objet ⇄ tableau ; encodeur = tableau ⇄ format.
- JSON, XML, CSV, YAML sont fournis.
- Sont retenus : les propriétés publiques, et les méthodes en `get`, `has`,
  `is`, `can`.
- `#[Ignore]` exclut partout, les groupes excluent au cas par cas.
- `#[SerializedName]` renomme dans le format seul.
- Référence circulaire : exception, seuil `1`, rappel possible.

## Sources officielles

- [`serializer.rst`, branche 8.0](https://github.com/symfony/symfony-docs/blob/8.0/serializer.rst)
- [`AbstractNormalizer`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Serializer/Normalizer/AbstractNormalizer.php)
