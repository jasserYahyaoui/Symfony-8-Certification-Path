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
---

## Objectif

Comprendre ce que le composant Form fait réellement : convertir un objet
applicatif en champs HTML, et le chemin inverse. C'est ce modèle qui explique
tout le reste du lot.

## Le problème résolu

Un navigateur n'envoie que du texte. Une application manipule des objets. Le
composant tient les deux bouts : il construit les champs à partir d'un objet, et
reconstruit l'objet à partir de ce que le navigateur renvoie.

Il est **autonome** : utilisable hors du framework, comme HttpFoundation.

## Les trois couches de données

C'est le modèle central, et il porte trois noms qu'il faut savoir distinguer :

| Couche | Contenu | Méthode |
|---|---|---|
| **Model data** | la donnée au format de l'application : un `DateTime`, un objet métier. C'est ce qu'on passe à `createForm()` | `getData()` |
| **Normalized data** | une représentation intermédiaire, identique au modèle pour la plupart des types | `getNormData()` |
| **View data** | le format des champs HTML, essentiellement des chaînes, puisque c'est ce qu'un navigateur envoie | `getViewData()` |

Les **transformateurs de modèle** relient modèle et normalisée ; les
**transformateurs de vue** relient normalisée et vue.

## L'exemple du `DateType` — et une erreur de la documentation

La documentation 8.0 illustre les couches avec un `DateType` rendu en trois
listes déroulantes et donne, pour la donnée normalisée, un tableau d'entiers
`['year' => 2026, 'month' => 10, 'day' => 18]`. **Le code dit autre chose.**
`DateType` n'ajoute un transformateur de modèle que pour les formats d'entrée
autres que `datetime` ; son transformateur de vue, `DateTimeToArrayTransformer`,
part d'un `DateTime`. Exécuté avec `symfony/form` 8.0.15 :

| Option `input` | Model data | Normalized data | View data |
|---|---|---|---|
| `datetime` (défaut) | `DateTime` | `DateTime` | `['year' => '2026', …]` |
| `array` | `['year' => 2026, …]` | `DateTime` | `['year' => '2026', …]` |
| `string` | `'2026-10-18'` | `DateTime` | `['year' => '2026', …]` |

La donnée normalisée d'un `DateType` est **toujours un `DateTime`** : c'est le
format pivot vers lequel chaque format d'entrée est ramené. Le tableau
d'entiers est la donnée **modèle** quand `input` vaut `array`. La vue, elle, est
bien un tableau de chaînes.

Cet exemple montre à quoi sert la couche normalisée : elle rend le type
indépendant du format que l'application choisit pour ses données.

## Les deux sens

**Au rendu** : donnée du modèle → transformateurs de modèle → donnée normalisée
→ transformateurs de vue → donnée de vue → HTML.

**À la soumission** : valeurs brutes de la requête → transformateurs de vue à
l'envers → donnée normalisée → transformateurs de modèle à l'envers → donnée du
modèle, réécrite dans l'objet.

Les mêmes transformateurs servent dans les deux sens ; c'est leur méthode
inverse qui est appelée à la soumission.

Les événements de formulaire s'insèrent à des niveaux précis : `PRE_SUBMIT`
reçoit les valeurs brutes, `SUBMIT` la donnée normalisée, `POST_SUBMIT` la
donnée entièrement transformée — l'item *Form events* de ce lot.

## Quand cela devient visible

La plupart du temps ces couches restent invisibles. Elles apparaissent dans deux
situations : quand un champ ne s'affiche pas ou ne se soumet pas comme prévu —
`getNormData()` et `getViewData()` servent alors au diagnostic —, et quand on
écrit un transformateur — l'item *Data transformers* de ce lot.

## Pièges d'examen

**Trois couches de données, pas deux.** Modèle, normalisée, vue : la couche
intermédiaire est identique au modèle pour la plupart des types, ce qui la rend
facile à oublier.

**La donnée normalisée d'un `DateType` est un `DateTime`**, pas le tableau
d'entiers de l'exemple documenté.

**La vue est faite de chaînes.** Un navigateur n'envoie que du texte.

**Le composant est autonome.** Ce n'est pas une fonctionnalité de
FrameworkBundle.

## Points clés

- Composant autonome ; il traduit entre objets et champs HTML.
- Trois couches : model (`getData()`), normalized (`getNormData()`), view
  (`getViewData()`).
- Transformateurs de modèle entre modèle et normalisée ; de vue entre
  normalisée et vue.
- Le rendu descend model → view ; la soumission remonte view → model.
- `DateType` : normalisée = `DateTime`, quel que soit `input`.

## Sources officielles

- [Forms, « The Data Transformation Lifecycle » et « Accessing Form Data »](https://github.com/symfony/symfony-docs/blob/8.0/forms.rst)
- [Form 8.0, `DateType`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/Extension/Core/Type/DateType.php) et [`DateTimeToArrayTransformer`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/Extension/Core/DataTransformer/DateTimeToArrayTransformer.php)
