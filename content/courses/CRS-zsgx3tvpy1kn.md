---
id: CRS-zsgx3tvpy1kn
official_item: OIT-p810t98zedem
title: "Built-in form types"
content_level: MINIMAL
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-25"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/reference/forms/types.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/reference/forms/types.rst"
    anchor: "supported-field-types"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Form/Extension/Core/Type/ChoiceType.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/Extension/Core/Type/ChoiceType.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "ChoiceType::configureOptions(), choices, multiple, expanded"
    verified_at: "2026-09-25"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Form/Extension/Core/Type/RepeatedType.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/Extension/Core/Type/RepeatedType.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "RepeatedType::buildForm(), first_name, second_name, invalid_message"
    verified_at: "2026-09-25"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Form/Extension/Core/Type/ButtonType.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/Extension/Core/Type/ButtonType.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "ButtonType::getParent() returns null"
    verified_at: "2026-09-25"
---

## Objectif

Reconnaître les types fournis par Symfony, choisir le bon, et savoir de qui
chacun hérite.

## Le catalogue

| Famille (documentation) | Types |
|---|---|
| **Texte** | `TextType`, `TextareaType`, `EmailType`, `IntegerType`, `MoneyType`, `NumberType`, `PasswordType`, `PercentType`, `SearchType`, `UrlType`, `RangeType`, `TelType`, `ColorType` |
| **Choix** | `ChoiceType`, `EnumType`, `CountryType`, `LanguageType`, `LocaleType`, `TimezoneType`, `CurrencyType` |
| **Date et heure** | `DateType`, `DateTimeType`, `TimeType`, `DateIntervalType`, `BirthdayType`, `WeekType` |
| **Autres** | `CheckboxType`, `RadioType`, `FileType` |
| **UID** | `UuidType`, `UlidType` |
| **Groupes** | `CollectionType`, `RepeatedType` |
| **Caché** | `HiddenType` |
| **Boutons** | `ButtonType`, `ResetType`, `SubmitType` |
| **Base** | `FormType` |

Les types UID exigent le composant Uid. Exécuté sans lui : le formulaire se
construit et se rend, puis la soumission lève une `Error`, classe `Uuid`
introuvable.

## Limites de périmètre

Deux entrées du catalogue officiel sortent du périmètre de l'examen
(`docs/syllabus/exclusions.yml`) : `EntityType`, qui relève de l'intégration
avec une base de données, et les champs Symfony UX.

## Une famille n'est pas une lignée

Les familles rangent la documentation ; `getParent()` décide de l'héritage.
Relevé sur les 38 types de `symfony/form` 8.0.15 :

| Parent | Types |
|---|---|
| `TextType` | `EmailType`, `PasswordType`, `TextareaType`, `SearchType`, `UrlType`, `TelType`, `ColorType`, `RangeType` |
| `FormType` | `IntegerType`, `NumberType`, `MoneyType`, `PercentType`, `DateType`, `CheckboxType`, `ChoiceType`… |
| `ChoiceType` | `CountryType`, `LanguageType`, `LocaleType`, `TimezoneType`, `CurrencyType`, `EnumType` |
| `DateType` | `BirthdayType` |
| `CheckboxType` | `RadioType` |
| aucun | `FormType`, `ButtonType` |

Les quatre types numériques sont rangés sous « texte » mais n'héritent **pas**
de `TextType`. Exécuté : un bloc `text_widget` personnalisé change le rendu d'un
`TextType`, et laisse intact un `IntegerType`, rendu en
`<input type="number">`.

## La famille des choix

`ChoiceType` est le parent des six autres : leurs options sont les siennes. Ses
trois options structurantes sont `choices`, `multiple` et `expanded`. Rendu
réel :

| `multiple` | `expanded` | Rendu |
|---|---|---|
| `false` | `false` | `<select>` |
| `false` | `true` | boutons radio |
| `true` | `false` | `<select multiple>` |
| `true` | `true` | cases à cocher |

Dans `choices`, la **clé est le libellé**, la **valeur est la donnée**. Exécuté
avec `['France' => 'fr']` : soumettre `fr` donne la donnée `'fr'` ; soumettre
`France` rend le formulaire invalide, « The selected choice is invalid. ».

`EnumType` exige l'option `class` : sans elle, `MissingOptionsException`.

## `RepeatedType`

Il crée **deux enfants**, `first` et `second` par défaut, du type donné par
l'option `type`. Exécuté : si les valeurs coïncident, la donnée est **une seule
valeur** ; sinon le formulaire est invalide, avec « The values do not match. »
porté par le champ répété lui-même, et la donnée est `null`.

## Les boutons

`ButtonType` n'a **pas de parent** : les boutons ne descendent pas de
`FormType`. Exécuté, avec une extension de type visant `FormType` : elle
s'applique au champ texte, **pas** au `SubmitType`, dont les préfixes de bloc
sont `["button", "submit", …]`.

Un bouton s'ajoute comme un champ, mais c'est un `SubmitButton` : il ne porte
pas de donnée — absent de `getData()` — et `isClicked()` dit s'il a servi à
soumettre.

## Pièges d'examen

**La famille « texte » n'est pas la lignée de `TextType`** : les types
numériques héritent directement de `FormType`.

**Toute la famille des choix hérite de `ChoiceType`.**

**Les clés de `choices` sont les libellés.**

**Un bouton n'est pas un `FormType`** : une extension de `FormType` ne le
touche pas.

**`RepeatedType` rend deux champs mais une seule donnée.**

## Points clés

- Catalogue : texte, choix, date et heure, autres, UID, groupes, caché,
  boutons, base.
- Famille documentaire ≠ parent ; `IntegerType` hérite de `FormType`.
- `multiple` × `expanded` : quatre rendus ; `choices` : libellé ⇒ valeur.
- `RepeatedType` : `first`, `second`, une donnée, erreur sur le champ répété.
- `ButtonType` sans parent ; `EntityType` et Symfony UX hors périmètre.

## Sources officielles

- [Form Types Reference](https://github.com/symfony/symfony-docs/blob/8.0/reference/forms/types.rst)
- [Form 8.0, `ChoiceType`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/Extension/Core/Type/ChoiceType.php), [`RepeatedType`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/Extension/Core/Type/RepeatedType.php) et [`ButtonType`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/Extension/Core/Type/ButtonType.php)
