---
id: CRS-zsgx3tvpy1kn
official_item: OIT-p810t98zedem
title: "Built-in form types"
content_level: MINIMAL
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/reference/forms/types.rst"
    anchor: "supported-field-types"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
---

## Objectif

Reconnaître les familles de types fournies par Symfony et choisir le bon.

## Les familles

| Famille | Types |
|---|---|
| **Texte** | `TextType`, `TextareaType`, `EmailType`, `IntegerType`, `MoneyType`, `NumberType`, `PasswordType`, `PercentType`, `SearchType`, `UrlType`, `RangeType`, `TelType`, `ColorType` |
| **Choix** | `ChoiceType`, `EnumType`, `CountryType`, `LanguageType`, `LocaleType`, `TimezoneType`, `CurrencyType` |
| **Date et heure** | `DateType`, `DateTimeType`, `TimeType`, `DateIntervalType`, `BirthdayType`, `WeekType` |
| **Autres** | `CheckboxType`, `RadioType`, `FileType` |

À quoi s'ajoutent les types de groupement — `FormType`, `CollectionType`,
`RepeatedType` — les champs cachés, et les boutons `SubmitType`, `ResetType`,
`ButtonType`.

## Limites de périmètre

Deux entrées du catalogue officiel sortent du périmètre de l'examen
(`docs/syllabus/exclusions.yml`) : `EntityType`, qui relève de l'intégration
avec une base de données, et les champs Symfony UX.

## Ce qui distingue les familles

`ChoiceType` est le socle de toute la famille des choix : `CountryType`,
`LanguageType` et les autres en héritent en pré-remplissant la liste. Ses trois
options structurantes sont `choices`, `multiple` et `expanded` — c'est leur
combinaison qui décide du rendu :

| `multiple` | `expanded` | Rendu |
|---|---|---|
| `false` | `false` | liste déroulante simple |
| `false` | `true` | boutons radio |
| `true` | `false` | liste déroulante multiple |
| `true` | `true` | cases à cocher |

`RepeatedType` rend **deux champs** dont les valeurs doivent coïncider : c'est le
motif du mot de passe confirmé.

## Pièges d'examen

**Toute la famille des choix hérite d'un même type socle.** Pays, langue,
locale, devise et fuseau ne sont pas des types indépendants : ce sont le même
mécanisme avec une liste pré-remplie, et les options du socle valent pour eux.

**Deux entrées du catalogue officiel sont hors périmètre** : le type lié à une
base de données et les champs Symfony UX. Les reconnaître est utile, les réviser
ne l'est pas.

**Les boutons sont des types comme les autres**, ajoutés au formulaire de la
même façon que les champs.

## Points clés

- Quatre familles : texte, choix, date et heure, autres ; plus groupement et
  boutons.
- `ChoiceType` est le parent de la famille des choix.
- `multiple` et `expanded` décident du rendu d'un `ChoiceType`.
- `RepeatedType` produit deux champs à valeurs identiques.
- `EntityType` et les champs Symfony UX sont hors périmètre.

## Sources officielles

- [Form Types Reference](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/reference/forms/types.rst)
