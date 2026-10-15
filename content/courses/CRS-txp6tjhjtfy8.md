---
id: CRS-txp6tjhjtfy8
official_item: OIT-tsxxgp3ppj1n
title: "Form component"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-24"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/forms.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/forms.rst"
    anchor: "the-data-transformation-lifecycle"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Form/Extension/Core/Type/DateType.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/Extension/Core/Type/DateType.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "DateType::buildForm(), model and view transformers"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Form/Extension/Core/DataTransformer/DateTimeToArrayTransformer.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/Extension/Core/DataTransformer/DateTimeToArrayTransformer.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "DateTimeToArrayTransformer::transform()"
    verified_at: "2026-09-24"
outcome_sections:
  OUT-teejhc73n9se: "Le problème résolu"
  OUT-xysy1a4vx444: "Les trois couches de données"
  OUT-v7z96yxzjbvx: "Les deux sens"
  OUT-hqxn9f5pmxnp: "Les trois couches de données"
---

## Objectif

Comprendre ce que le component Form fait réellement : convertir un object
applicatif en fields HTML, et le path inverse. C'est ce modèle qui explique
tout le reste du lot.

## Le problème résolu

Un navigateur n'envoie que du texte. Une application manipule des objects. Le
component tient les deux bouts : il construit les fields à partir d'un object, et
reconstruit l'object à partir de ce que le navigateur renvoie.

Il est **autonome** : utilisable hors du framework, comme HttpFoundation.

## Les trois couches de données

C'est le modèle central, et il porte trois noms qu'il faut savoir distinguer :

| Couche | Contenu | Method |
|---|---|---|
| **Model data** | la donnée au format de l'application : un `DateTime`, un object métier. C'est ce qu'on passe à `createForm()` | `getData()` |
| **Normalized data** | une représentation intermédiaire, identique au modèle pour la plupart des types | `getNormData()` |
| **View data** | le format des fields HTML, essentiellement des strings, puisque c'est ce qu'un navigateur envoie | `getViewData()` |

Les **model transformers** relient modèle et normalisée ; les
**view transformers** relient normalisée et vue.

## L'exemple du `DateType` — et une erreur de la documentation

La documentation 8.0 illustre les couches avec un `DateType` rendu en trois
listes déroulantes et donne, pour la norm data, un array d'ints
`['year' => 2026, 'month' => 10, 'day' => 18]`. **Le code dit autre chose.**
`DateType` n'ajoute un model transformer que pour les formats d'entrée
autres que `datetime` ; son view transformer, `DateTimeToArrayTransformer`,
part d'un `DateTime`. Exécuté avec `symfony/form` 8.0.15 :

| Option `input` | Model data | Normalized data | View data |
|---|---|---|---|
| `datetime` (défaut) | `DateTime` | `DateTime` | `['year' => '2026', …]` |
| `array` | `['year' => 2026, …]` | `DateTime` | `['year' => '2026', …]` |
| `string` | `'2026-10-18'` | `DateTime` | `['year' => '2026', …]` |

La norm data d'un `DateType` est **toujours un `DateTime`** : c'est le
format pivot vers lequel chaque format d'entrée est ramené. L'array
d'ints est la donnée **modèle** quand `input` vaut `array`. La vue, elle, est
bien un array de strings.

Cet exemple montre à quoi sert la couche normalisée : elle rend le type
indépendant du format que l'application choisit pour ses données.

## Les deux sens

**Au rendu** : model data → model transformers → norm data
→ view transformers → view data → HTML.

**À la submission** : valeurs brutes de la Request → view transformers à
l'envers → norm data → model transformers à l'envers → donnée du
modèle, réécrite dans l'object.

Les mêmes transformers servent dans les deux sens ; c'est leur method
inverse qui est appelée à la submission.

Les events de form s'insèrent à des niveaux précis : `PRE_SUBMIT`
reçoit les valeurs brutes, `SUBMIT` la norm data, `POST_SUBMIT` la
donnée entièrement transformée — l'item *Form events* de ce lot.

## Quand cela devient visible

La plupart du temps ces couches restent invisibles. Elles apparaissent dans deux
situations : quand un field ne s'affiche pas ou ne se soumet pas comme prévu —
`getNormData()` et `getViewData()` servent alors au diagnostic —, et quand on
écrit un transformer — l'item *Data transformers* de ce lot.

## Pièges d'examen

**Trois couches de données, pas deux.** Modèle, normalisée, vue : la couche
intermédiaire est identique au modèle pour la plupart des types, ce qui la rend
facile à oublier.

**La norm data d'un `DateType` est un `DateTime`**, pas l'array
d'ints de l'exemple documenté.

**La vue est faite de strings.** Un navigateur n'envoie que du texte.

**Le component est autonome.** Ce n'est pas une fonctionnalité de
FrameworkBundle.

## Points clés

- Component autonome ; il traduit entre objects et fields HTML.
- Trois couches : model (`getData()`), normalized (`getNormData()`), view
  (`getViewData()`).
- Model transformers entre modèle et normalisée ; de vue entre
  normalisée et vue.
- Le rendu descend model → view ; la submission remonte view → model.
- `DateType` : normalisée = `DateTime`, quel que soit `input`.

## Sources officielles

- [Forms, « The Data Transformation Lifecycle » et « Accessing Form Data »](https://github.com/symfony/symfony-docs/blob/8.0/forms.rst)
- [Form 8.0, `DateType`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/Extension/Core/Type/DateType.php) et [`DateTimeToArrayTransformer`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/Extension/Core/DataTransformer/DateTimeToArrayTransformer.php)
