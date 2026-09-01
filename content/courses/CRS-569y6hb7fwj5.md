---
id: CRS-569y6hb7fwj5
official_item: OIT-9x3strrjdng7
title: "Built-in validation constraints"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/reference/constraints/map.rst.inc"
    branch: "8.0"
    symbol_or_lines: "liste des contraintes natives"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Validator/Constraints/NotBlankValidator.php"
    branch: "8.0"
    symbol_or_lines: "NotBlankValidator::validate"
    verified_at: "2026-09-01"
---

## Objectif

Reconnaître les familles de contraintes fournies et trancher entre celles qui se
ressemblent. Le catalogue complet ne se mémorise pas : `debug:validator` et la
référence le donnent.

## Les familles

La référence officielle range les contraintes natives en familles :

| Famille | Exemples |
|---|---|
| Basiques | `NotBlank`, `NotNull`, `IsNull`, `Blank`, `IsTrue`, `IsFalse`, `Type` |
| Chaînes | `Email`, `Length`, `Regex`, `Url`, `Uuid`, `Ip`, `Json`, `PasswordStrength` |
| Comparaison | `EqualTo`, `IdenticalTo`, `GreaterThan`, `Range`, `Unique` |
| Nombres | `Positive`, `PositiveOrZero`, `Negative`, `DivisibleBy` |
| Date | `Date`, `DateTime`, `Time`, `Timezone` |
| Choix | `Choice`, `Country`, `Language`, `Locale` |
| Fichier | `File`, `Image` |
| Structure | `Valid`, `All`, `Collection`, `Count`, `Callback`, `Sequentially`, `AtLeastOneOf`, `When`, `Compound` |

## Les distinctions qui décident

**`NotBlank` contre `NotNull`.** `NotNull` ne refuse que `null`. `NotBlank`
refuse `null`, la chaîne vide, `false` et le tableau vide — mais **laisse passer
la chaîne `'0'`**, cas particulier écrit dans le validateur. L'option
`allowNull` fait accepter `null` à `NotBlank`.

**`IsNull` et `Blank`** sont leurs symétriques : elles exigent une valeur
absente.

**`EqualTo` contre `IdenticalTo`** : `==` contre `===`. `'1'` satisfait
`EqualTo(1)`, pas `IdenticalTo(1)`.

**`Length` contre `Count`** : la première mesure une chaîne, la seconde une
collection.

## Les contraintes de structure

Elles ne testent pas une valeur, elles en organisent d'autres :

- `All` applique une contrainte à **chaque élément** d'un tableau ;
- `Collection` associe une contrainte à **chaque clé** d'un tableau ;
- `Sequentially` arrête à la **première** contrainte violée, ce qui évite
  d'exécuter une contrainte coûteuse sur une valeur déjà mal formée ;
- `AtLeastOneOf` réussit si **une** des contraintes réussit ;
- `When` n'applique une contrainte que si une expression est vraie ;
- `Compound` regroupe un jeu de contraintes réutilisable sous un seul nom.

## Pièges d'examen

**`NotBlank` n'est pas `NotNull`.** Une propriété qui doit seulement être
renseignée mais peut valoir `0` demande `NotNull`.

**`All` n'est pas `Collection`.** `All` traite les éléments uniformément ;
`Collection` décrit un tableau clé par clé.

**Sans `Sequentially`, toutes les contraintes s'exécutent** et cumulent leurs
violations sur la même propriété.

## Points clés

- Les familles se reconnaissent ; la liste exhaustive se consulte.
- `NotBlank` ⊃ `NotNull` ; `'0'` passe `NotBlank`.
- `EqualTo` = `==`, `IdenticalTo` = `===`.
- `All`, `Collection`, `Sequentially`, `AtLeastOneOf`, `When` organisent les
  autres contraintes.

## Sources officielles

- [Référence des contraintes](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/reference/constraints/map.rst.inc)
- [`NotBlankValidator`, branche 8.0](https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Validator/Constraints/NotBlankValidator.php)
