---
id: CRS-zsgx3tvpy1kn
official_item: OIT-p810t98zedem
title: "Built-in form types"
content_level: MINIMAL
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-29"
format: pedagogical
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
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Form/Extension/Validator/Type/RepeatedTypeValidatorExtension.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/Extension/Validator/Type/RepeatedTypeValidatorExtension.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "configureOptions(): error_mapping '.' => first_name"
    verified_at: "2026-10-29"
outcome_sections:
  OUT-0a3jcmk5066b: "Le catalogue"
  OUT-41xcqcjzrmm1: "La famille des choix"
  OUT-zq43jp3a97gg: "La famille des choix"
  OUT-pesfrvtg5qj0: "Limites de périmètre"
---

## Objectif

Reconnaître les types fournis par Symfony, choisir le bon, et savoir de qui
chacun hérite — c'est la lignée, pas la famille, qui décide du rendu.

## L'essentiel

Symfony fournit une quarantaine de types, rangés par **familles** dans la
documentation. Mais c'est la **lignée** — `getParent()` — qui décide du rendu
et des extensions : les types numériques, rangés sous « texte », n'héritent
pas de `TextType`, et les boutons ne descendent pas de `FormType`.

## Le schéma

```mermaid
---
title: Les lignées des types natifs
---
flowchart TD
  accTitle: L'inheritance des types de form natifs de Symfony 8.0
  accDescr: FormType est la racine de presque tous les types. TextType hérite de FormType, et huit types héritent de TextType, dont EmailType et PasswordType. Les types numériques comme IntegerType héritent directement de FormType. ChoiceType hérite de FormType et est le parent de CountryType, EnumType et des autres types de choix. DateType est le parent de BirthdayType, CheckboxType celui de RadioType. ButtonType n'a pas de parent et est celui de SubmitType et ResetType.
  F["FormType"] --> T["TextType"]
  T --> TE["EmailType, PasswordType, TextareaType, UrlType…"]
  F --> N["IntegerType, NumberType, MoneyType, PercentType"]
  F --> C["ChoiceType"]
  C --> CC["CountryType, EnumType, LanguageType…"]
  F --> D["DateType"] --> B["BirthdayType"]
  F --> CB["CheckboxType"] --> R["RadioType"]
  BT["ButtonType, sans parent"] --> SB["SubmitType, ResetType"]
```

## L'exemple : l'inscription

```php
$builder
    ->add('age', IntegerType::class)                                           // parent : FormType
    ->add('country', CountryType::class)                                       // parent : ChoiceType
    ->add('plainPassword', RepeatedType::class, ['type' => PasswordType::class]) // deux enfants
    ->add('save', SubmitType::class);                                          // parent : ButtonType
```

## Le catalogue

| Famille (documentation) | Types |
|---|---|
| **Texte** | `TextType`, `TextareaType`, `EmailType`, `IntegerType`, `MoneyType`, `NumberType`, `PasswordType`, `PercentType`, `SearchType`, `UrlType`, `RangeType`, `TelType`, `ColorType` |
| **Choix** | `ChoiceType`, `EnumType`, `CountryType`, `LanguageType`, `LocaleType`, `TimezoneType`, `CurrencyType` |
| **Date et heure** | `DateType`, `DateTimeType`, `TimeType`, `DateIntervalType`, `BirthdayType`, `WeekType` |
| **Autres** | `CheckboxType`, `RadioType`, `FileType` |
| **UID** | `UuidType`, `UlidType` |
| **Groups** | `CollectionType`, `RepeatedType` |
| **Caché** | `HiddenType` |
| **Boutons** | `ButtonType`, `ResetType`, `SubmitType` |
| **Base** | `FormType` |

Les types UID exigent le component Uid : sans lui, la submission lève une
`Error`.

## Limites de périmètre

`EntityType`, qui relève de la base de données, et les fields Symfony UX
sortent du périmètre de l'examen.

## Une famille n'est pas une lignée

| Parent | Types |
|---|---|
| `TextType` | `EmailType`, `PasswordType`, `TextareaType`, `SearchType`, `UrlType`, `TelType`, `ColorType`, `RangeType` |
| `FormType` | `TextType`, `IntegerType`, `NumberType`, `MoneyType`, `PercentType`, `ChoiceType`, `DateType`, `CheckboxType`, `RepeatedType`… |
| `ChoiceType` | `CountryType`, `LanguageType`, `LocaleType`, `TimezoneType`, `CurrencyType`, `EnumType` |
| `DateType` / `CheckboxType` | `BirthdayType` / `RadioType` |
| `ButtonType` | `SubmitType`, `ResetType` |

Seuls `FormType` et `ButtonType` n'ont pas de parent. Un `text_widget`
personnalisé laisse donc intact un `IntegerType`, rendu en
`<input type="number">`.

## La famille des choix

| `multiple` | `expanded` | Rendu |
|---|---|---|
| `false` | `false` | `<select>` |
| `false` | `true` | radio buttons |
| `true` | `false` | `<select multiple>` |
| `true` | `true` | checkboxes |

- Dans `choices`, la **key est le libellé**, la **valeur est la donnée** :
  avec `['France' => 'fr']`, soumettre `France` donne « The selected choice is
  invalid. ».
- `EnumType` exige l'option `class` : sans elle, `MissingOptionsException`.

## `RepeatedType`

Deux enfants, `first` et `second`, du type donné par `type`. Valeurs égales :
**une seule donnée**. Sinon : « The values do not match. », et la donnée vaut
`null`. Dans une application complète, `RepeatedTypeValidatorExtension`
redirige cette erreur vers **`first`** par `error_mapping`.

## Les boutons

`ButtonType` n'a pas de parent : une extension de `FormType` ne touche pas un
`SubmitType`. Un bouton est un `SubmitButton` : absent de `getData()`, et
`isClicked()` dit s'il a servi ; `getClickedButton()` le rend.

## Pièges d'examen

**La famille « texte » n'est pas la lignée de `TextType`** : les types
numériques héritent directement de `FormType`.

**Toute la famille des choix hérite de `ChoiceType`.**

**Les keys de `choices` sont les libellés.**

**Un bouton n'est pas un `FormType`** : une extension de `FormType` ne le
touche pas.

**`RepeatedType` rend deux fields mais une seule donnée.**

**L'erreur « The values do not match. » s'affiche sur `first`** dans une
application complète : l'extension Validator l'y redirige.

## Application

- Un theme `text_widget` qui oublie l'âge → `IntegerType` hérite de
  `FormType` : personnaliser aussi `integer_widget`.
- « The values do not match. » jamais affiché →
  `form_errors(form.plainPassword.first)`.

## QCM d'entraînement

**Question 1.** La documentation range `IntegerType` dans la famille
« texte ». Quel est son parent, selon `getParent()` ?

- **A.** `TextType`, comme sa famille
- **B.** `NumberType`, le type numérique général
- **C.** `FormType`
- **D.** Aucun : il est autonome

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Les quatre types numériques héritent directement de
`FormType` ; leur famille ne range que la documentation.

</details>

**Question 2.** Une type extension vise `FormType::class` et ajoute un
attribute. Le form contient un `TextType` et un `SubmitType`. Lesquels sont
touchés ?

- **A.** Les deux : tout descend de `FormType`
- **B.** Le `TextType` seulement
- **C.** Le `SubmitType` seulement
- **D.** Aucun : il faut viser chaque type

<details>
<summary>Correction</summary>

**Bonne réponse : B.** `SubmitType` descend de `ButtonType`, qui n'a pas de
parent : une extension de `FormType` ne l'atteint pas.

</details>

**Question 3.** Dans une application complète, un `RepeatedType` nommé `pw`
reçoit deux valeurs différentes. Sur quel form l'erreur « The values do not
match. » est-elle posée ?

- **A.** Sur `pw`, le field répété
- **B.** Sur `pw.second`, la confirmation
- **C.** Sur le form racine
- **D.** Sur `pw.first`

<details>
<summary>Correction</summary>

**Bonne réponse : D.** `RepeatedTypeValidatorExtension` la redirige vers le
premier enfant par `error_mapping` ; vide, elle revient sur `pw`.

</details>

**Question 4.** Un développeur écrit `->add('suit', EnumType::class)`, sans
autre option. Que se passe-t-il ?

- **A.** Une `MissingOptionsException`
- **B.** Un `<select>` vide
- **C.** L'enum est devinée d'après la property
- **D.** Un `TextType` à la place

<details>
<summary>Correction</summary>

**Bonne réponse : A.** `EnumType` exige l'option `class` : « The required
option "class" is missing. ».

</details>

## Fiche express

**Si tu dois retenir seulement 6 choses :**

- **Famille ≠ lignée** : les types numériques héritent de `FormType`.
- Toute la famille des choix hérite de **`ChoiceType`**.
- `multiple` / `expanded` : select, radio, select multiple, checkboxes.
- `choices` : **key = libellé**, valeur = donnée.
- `RepeatedType` : deux fields, **une donnée** ; erreur sur **`first`**.
- Boutons : **`ButtonType`**, sans parent ; hors de `getData()`.

Moyen mnémotechnique : **« La famille range, le parent décide »**.

## Sources officielles

- [Form Types Reference](https://github.com/symfony/symfony-docs/blob/8.0/reference/forms/types.rst)
- [Form 8.0, `ChoiceType`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/Extension/Core/Type/ChoiceType.php), [`RepeatedType`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/Extension/Core/Type/RepeatedType.php) et [`ButtonType`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/Extension/Core/Type/ButtonType.php)
- [Form 8.0, `RepeatedTypeValidatorExtension`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/Extension/Validator/Type/RepeatedTypeValidatorExtension.php)

