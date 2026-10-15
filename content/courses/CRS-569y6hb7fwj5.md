---
id: CRS-569y6hb7fwj5
official_item: OIT-9x3strrjdng7
title: "Built-in validation constraints"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-15"
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
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Validator/Constraints/LengthValidator.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Constraints/LengthValidator.php"
    branch: "8.0"
    symbol_or_lines: "validate(): returns on null only"
    verified_at: "2026-10-15"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Validator/Constraints/EmailValidator.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Constraints/EmailValidator.php"
    branch: "8.0"
    symbol_or_lines: "validate(): returns on null and on the empty string"
    verified_at: "2026-10-15"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Validator/Constraints/CollectionValidator.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Constraints/CollectionValidator.php"
    branch: "8.0"
    symbol_or_lines: "missingFieldsMessage, extraFieldsMessage, Required, Optional"
    verified_at: "2026-10-15"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Validator/Constraints/Choice.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Constraints/Choice.php"
    branch: "8.0"
    symbol_or_lines: "__construct(): first parameter $options, an array is refused"
    verified_at: "2026-10-15"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Validator/CHANGELOG.md"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/CHANGELOG.md"
    branch: "8.0"
    symbol_or_lines: "8.0: Url requireTld defaults to true; options no longer configured implicitly"
    verified_at: "2026-10-15"
outcome_sections:
  OUT-zxecwdwyhgng: "Les familles"
  OUT-wkzdab0t8y8n: "Les distinctions qui décident"
  OUT-ca39zvmah4sj: "Les constraints de structure"
  OUT-8hwwtw34wdgt: "Les constraints de structure"
  OUT-kyt7yfamw0aq: "null et la string vide"
  OUT-bkrt4hz3td66: "Configurer une constraint en 8.0"
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

## null et la string vide

Une constraint de valeur **ignore `null`** : ce n'est pas son role de dire si la
valeur est obligatoire. Exécuté sur `symfony/validator` 8.0.15, `Length`,
`Email`, `Regex`, `Choice`, `Positive`, `Count`, `Url` et `Type` ne signalent
rien sur `null`. Seules `NotNull` et `NotBlank` le refusent.

La string vide n'est pas `null`, et les constraints ne la traitent pas toutes
de la même façon :

| Constraint sur `''` | Résultat |
|---|---|
| `Email`, `Regex`, `Url`, `Type('string')` | acceptée : le validator rend la main |
| `Length(min: 3)` | violation : la longueur 0 est mesurée |
| `Choice(choices: ['a'])` | violation : `''` n'est pas un choix |
| `Positive` | violation |

Un field obligatoire porte donc `NotBlank` **en plus** de sa constraint de
format : `Email` seule laisse passer `null` et `''`.

## Configurer une constraint en 8.0

Les options se passent en **named arguments**. Le CHANGELOG 8.0 retire la
configuration implicite par array. Piège : le premier parameter de `Choice` est
encore `$options`. `#[Assert\Choice(['draft', 'published'])]` lève donc une
`InvalidArgumentException` à la lecture des metadata ; il faut
`choices: ['draft', 'published']`.

Autre changement 8.0 : `requireTld` de `Url` vaut `true` par défaut. Exécuté,
`http://localhost` échoue avec `new Url()` et passe avec `requireTld: false`.

## Les constraints de structure

Elles ne testent pas une valeur, elles en organisent d'autres :

- `All` applique une constraint à **chaque élément** d'un array ;
- `Collection` associe une constraint à **chaque key** d'un array. Une key
  absente donne `This field is missing.` à `[key]`, une key en trop
  `This field was not expected.` — sauf `allowMissingFields` ou
  `allowExtraFields`. `Required` exige la key, pas une valeur non nulle ;
- `Sequentially` arrête à la **première** constraint violée : exécuté, `'ab'`
  contre `Length(min: 5)` puis `Email` donne une violation, deux sans
  `Sequentially` ;
- `AtLeastOneOf` réussit si **une** des constraints réussit ; si toutes
  échouent, il produit **une** violation qui les résume ;
- `When` n'applique une constraint que si une expression est vraie ;
- `Compound` regroupe un jeu de constraints réutilisable sous un seul nom ;
  toutes s'exécutent, comme une liste simple.

## Pièges d'examen

**`NotBlank` accepte `0`, `'0'` et `' '`.** Il refuse le vide au sens de PHP,
sauf ces valeurs. Pour une string dont le vide est légitime, c'est `NotNull`.

**`All` n'est pas `Collection`.** `All` traite les éléments uniformément ;
`Collection` décrit un array key par key.

**Sans `Sequentially`, toutes les constraints s'exécutent** et cumulent leurs
violations sur la même property.

**`DivisibleBy` est rangée en comparaison.**

**`Email` seule accepte `null` et `''`.** L'obligation vient de `NotBlank`.

**`Choice(['a', 'b'])` lève une exception en 8.0** : `choices:` est nommé.

## Points clés

- Les familles se reconnaissent ; la liste exhaustive se consulte.
- `NotBlank` refuse `null`, `''`, `false`, `[]` ; accepte `0`, `'0'`, `' '`.
- `EqualTo` = `==`, `IdenticalTo` = `===`.
- `All`, `Collection`, `Sequentially`, `AtLeastOneOf`, `When` organisent les
  autres constraints.
- `null` passe toute constraint de valeur ; `''` dépend de la constraint.
- 8.0 : named arguments ; `Url` exige un TLD par défaut.

## Sources officielles

- [Référence des constraints](https://github.com/symfony/symfony-docs/blob/8.0/reference/constraints/map.rst.inc)
- [`LengthValidator`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Constraints/LengthValidator.php), [`EmailValidator`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Constraints/EmailValidator.php), [`CollectionValidator`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Constraints/CollectionValidator.php), [`Choice`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Constraints/Choice.php) et le [CHANGELOG du Validator](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/CHANGELOG.md), branche 8.0
- [`NotBlankValidator`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Constraints/NotBlankValidator.php)
