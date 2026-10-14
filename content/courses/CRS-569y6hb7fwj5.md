---
id: CRS-569y6hb7fwj5
official_item: OIT-9x3strrjdng7
title: "Built-in validation constraints"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-29"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/reference/constraints/map.rst.inc"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/reference/constraints/map.rst.inc"
    branch: "8.0"
    symbol_or_lines: "liste des contraintes natives"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Validator/Constraints/NotBlankValidator.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Constraints/NotBlankValidator.php"
    branch: "8.0"
    symbol_or_lines: "NotBlankValidator::validate"
    verified_at: "2026-09-01"
outcome_sections:
  OUT-zxecwdwyhgng: "Les familles"
  OUT-wkzdab0t8y8n: "Les distinctions qui décident"
  OUT-ca39zvmah4sj: "Les constraints de structure"
  OUT-8hwwtw34wdgt: "Les constraints de structure"
---

## Objectif

Reconnaître les familles de constraints fournies et trancher entre celles qui se
ressemblent. Le catalogue complet ne se mémorise pas : `debug:validator` et la
référence le donnent.

## Les familles

La référence officielle (`map.rst.inc`, 8.0) range les constraints natives
ainsi :

| Famille | Exemples |
|---|---|
| Basiques | `NotBlank`, `NotNull`, `IsNull`, `Blank`, `IsTrue`, `IsFalse`, `Type` |
| Strings | `Email`, `Length`, `Regex`, `Url`, `Uuid`, `Ip`, `Json`, `PasswordStrength`, `WordCount` |
| Comparaison | `EqualTo`, `IdenticalTo`, `GreaterThan`, `Range`, `DivisibleBy`, `Unique` |
| Nombres | `Positive`, `PositiveOrZero`, `Negative`, `NegativeOrZero` |
| Date | `Date`, `DateTime`, `Time`, `Timezone`, `Week` |
| Choix | `Choice`, `Country`, `Language`, `Locale` |
| File | `File`, `Image`, `Video` |
| Financières et autres nombres | `Iban`, `Bic`, `CardScheme`, `Currency`, `Isbn`, `Luhn` |
| Autres | `Valid`, `All`, `Collection`, `Count`, `Callback`, `Sequentially`, `AtLeastOneOf`, `When`, `Compound`, `Cascade` |

`DivisibleBy` est une constraint de **comparaison**, pas de nombres. Une famille
propre à la base de données existe aussi : elle sort du périmètre.

## Les distinctions qui décident

**`NotBlank` contre `NotNull`.** `NotNull` ne refuse que `null`. `NotBlank`
teste `false === $value || (!$value && '0' != $value)` : il refuse `null`, la
string vide, `false` et l'array vide. Exécuté avec `symfony/validator`
8.0.15 :

| Valeur | `NotBlank` | `NotNull` |
|---|---|---|
| `null` | refusée | refusée |
| `''`, `false`, `[]` | refusées | acceptées |
| `'0'`, `0`, `0.0` | **acceptées** | acceptées |
| `' '` | **acceptée** | acceptée |

L'int `0` passe donc `NotBlank` : en PHP 8, `'0' != 0` est faux. Une string
d'espaces passe aussi, sauf avec `normalizer: 'trim'`. L'option `allowNull` fait
accepter `null` à `NotBlank`.

**`IsNull` et `Blank`** sont leurs symétriques : elles exigent une valeur
absente.

**`EqualTo` contre `IdenticalTo`** : `==` contre `===`. Exécuté : `'1'`
satisfait `EqualTo(1)`, pas `IdenticalTo(1)`.

**`Length` contre `Count`** : la première mesure une string, en caractères —
`'été'` fait 3 —, la seconde une collection.

## Les constraints de structure

Elles ne testent pas une valeur, elles en organisent d'autres :

- `All` applique une constraint à **chaque élément** d'un array ;
- `Collection` associe une constraint à **chaque key** d'un array ;
- `Sequentially` arrête à la **première** constraint violée : exécuté, `'ab'`
  contre `Length(min: 5)` puis `Email` donne une violation, deux sans
  `Sequentially` ;
- `AtLeastOneOf` réussit si **une** des constraints réussit ; si toutes
  échouent, il produit **une** violation qui les résume ;
- `When` n'applique une constraint que si une expression est vraie ;
- `Compound` regroupe un jeu de constraints réutilisable sous un seul nom.

## Pièges d'examen

**`NotBlank` accepte `0`, `'0'` et `' '`.** Il refuse le vide au sens de PHP,
sauf ces valeurs. Pour une string dont le vide est légitime, c'est `NotNull`.

**`All` n'est pas `Collection`.** `All` traite les éléments uniformément ;
`Collection` décrit un array key par key.

**Sans `Sequentially`, toutes les constraints s'exécutent** et cumulent leurs
violations sur la même property.

**`DivisibleBy` est rangée en comparaison.**

## Points clés

- Les familles se reconnaissent ; la liste exhaustive se consulte.
- `NotBlank` refuse `null`, `''`, `false`, `[]` ; accepte `0`, `'0'`, `' '`.
- `EqualTo` = `==`, `IdenticalTo` = `===`.
- `All`, `Collection`, `Sequentially`, `AtLeastOneOf`, `When` organisent les
  autres constraints.

## Sources officielles

- [Référence des constraints](https://github.com/symfony/symfony-docs/blob/8.0/reference/constraints/map.rst.inc)
- [`NotBlankValidator`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Constraints/NotBlankValidator.php)
