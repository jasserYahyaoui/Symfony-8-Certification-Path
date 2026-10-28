---
id: CRS-txp6tjhjtfy8
official_item: OIT-tsxxgp3ppj1n
title: "Form component"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-28"
format: pedagogical
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

## Avant la règle : le problème à résoudre

Une tâche a un intitulé et une échéance : `Task { string $title; DateTime
$dueDate; }`. Pour la modifier dans un navigateur, il faut :

- **afficher** un field texte avec l'intitulé, et trois listes déroulantes
  (année, mois, jour) préremplies avec l'échéance ;
- **recevoir** ce que le navigateur renvoie — uniquement des strings :
  `'2026'`, `'10'`, `'18'` ;
- **reconstruire** un `DateTime` et le remettre dans la `Task`.

Écrire ces conversions à la main pour chaque field, dans les deux sens, est
répétitif et fragile. Le component Form les fait pour toi, à travers **trois
couches** de données.

## Le fil rouge : l'échéance d'une tâche

```php
$form = $formFactory->create(DateType::class, new \DateTime('2026-10-18'), [
    'widget' => 'choice',
]);

$form->getData();      // DateTime(2026-10-18)          — model
$form->getNormData();  // DateTime(2026-10-18)          — normalized
$form->getViewData();  // ['year' => '2026', 'month' => '10', 'day' => '18'] — view
```

## Le cycle complet en un schéma

```text
RENDU          model data ──model transformers──► norm data ──view transformers──► view data ──► HTML
               (getData())                         (getNormData())                 (getViewData(), des strings)

SUBMISSION     Request ──► view data ──view transformers (reverse)──► norm data
                          ──model transformers (reverse)──► model data ──► réécrite dans l'object

EVENTS         PRE_SUBMIT : valeurs brutes │ SUBMIT : norm data │ POST_SUBMIT : tout transformé
```

## Le problème résolu

Un navigateur n'envoie que du texte. Une application manipule des objects. Le
component tient les deux bouts : il construit les fields à partir d'un object, et
reconstruit l'object à partir de ce que le navigateur renvoie.

Il est **autonome** : utilisable hors du framework, comme HttpFoundation.
Exécuté : `Forms::createFormFactory()`, sans kernel ni FrameworkBundle, suffit à
créer et soumettre un form avec `symfony/form` 8.0.15.

## Les trois couches de données

C'est le modèle central, et il porte trois noms qu'il faut savoir distinguer :

| Couche | Contenu | Method |
|---|---|---|
| **Model data** | la donnée au format de l'application : un `DateTime`, un object métier. C'est ce qu'on passe à `createForm()` | `getData()` |
| **Normalized data** | une représentation intermédiaire, identique au modèle pour la plupart des types | `getNormData()` |
| **View data** | le format des fields HTML, essentiellement des strings, puisque c'est ce qu'un navigateur envoie | `getViewData()` |

Les **model transformers** relient modèle et normalisée ; les
**view transformers** relient normalisée et vue.

Exécuté sur un `IntegerType` : model `42`, normalized `42`, view `"42"` — la
couche intermédiaire est identique au modèle, et la vue est une string.

### L'exemple du `DateType` — et une erreur de la documentation

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
| `datetime_immutable` | `DateTimeImmutable` | `DateTime` | `['year' => '2026', …]` |

La norm data d'un `DateType` est **toujours un `DateTime`** — même quand le
modèle est un `DateTimeImmutable` : c'est le format pivot vers lequel chaque
format d'entrée est ramené. L'array d'ints est la donnée **modèle** quand
`input` vaut `array`. La vue, elle, est bien un array de strings.

Cet exemple montre à quoi sert la couche normalisée : elle rend le type
indépendant du format que l'application choisit pour ses données.

## Les deux sens

**Au rendu** : model data → model transformers → norm data
→ view transformers → view data → HTML.

**À la submission** : valeurs brutes de la Request → view transformers à
l'envers → norm data → model transformers à l'envers → model data, réécrite dans l'object.

Exécuté : un `DateType` soumis avec `['year' => '2026', 'month' => '10', 'day'
=> '18']` rend une model data `DateTime(2026-10-18)`.

Les mêmes transformers servent dans les deux sens ; c'est leur method
inverse qui est appelée à la submission.

Les events de form s'insèrent à des niveaux précis : `PRE_SUBMIT`
reçoit les valeurs brutes, `SUBMIT` la norm data, `POST_SUBMIT` la
donnée entièrement transformée — l'item *Form events* de ce lot.

## Concrètement dans ton application

La plupart du temps ces couches restent invisibles. Elles apparaissent dans deux
situations :

- quand un field ne s'affiche pas ou ne se soumet pas comme prévu —
  `getNormData()` et `getViewData()` servent alors au diagnostic ;
- quand on écrit un transformer — l'item *Data transformers* de ce lot. Un
  object métier dans l'entité, un simple texte dans le widget : c'est un
  **model** transformer.

| Tu veux… | Couche |
|---|---|
| la donnée à enregistrer | model (`getData()`) |
| comprendre une conversion de format | normalized (`getNormData()`) |
| voir ce que le widget HTML reçoit | view (`getViewData()`) |

## À comprendre

- **Pourquoi trois couches et pas deux ?** Parce qu'un même type doit accepter
  plusieurs formats applicatifs (`DateTime`, string, array) sans réécrire sa
  conversion vers le HTML : la couche normalisée est le **pivot**.
- **Pourquoi la vue est-elle faite de strings ?** Parce qu'un form HTML
  ne transmet que du texte.
- **Pourquoi des transformers réversibles ?** Parce que le même field doit
  s'afficher **et** se soumettre : une conversion à l'aller, l'inverse au
  retour.

## À mémoriser

- Trois couches : **model** (`getData()`), **normalized** (`getNormData()`),
  **view** (`getViewData()`).
- Model transformers : model ↔ norm ; view transformers : norm ↔ view.
- Rendu : model → view ; submission : view → model.
- `DateType` : norm = `DateTime` quel que soit `input`, même
  `datetime_immutable`.
- Le component est autonome : `Forms::createFormFactory()`.
- Moyen mnémotechnique : **« Métier, Pivot, Texte »** — model, normalized,
  view.

## Pièges d'examen

**Trois couches de données, pas deux.** Modèle, normalisée, vue : la couche
intermédiaire est identique au modèle pour la plupart des types, ce qui la rend
facile à oublier.

**La norm data d'un `DateType` est un `DateTime`**, pas l'array
d'ints de l'exemple documenté.

**La vue est faite de strings.** Un navigateur n'envoie que du texte.

**Le component est autonome.** Ce n'est pas une fonctionnalité de
FrameworkBundle.

**Un `DateTimeImmutable` en model devient un `DateTime` en norm data** : le
pivot du `DateType` est mutable, quel que soit le format de l'application.

## Application : trois scénarios

**Scénario 1 — Le diagnostic.** Une date s'affiche vide alors que l'entité en
contient une. *Solution.* Comparer `getData()`, `getNormData()` et
`getViewData()` : la couche où la valeur disparaît désigne le transformer
fautif.

**Scénario 2 — La date stockée en string.** L'entité garde l'échéance comme
`'2026-10-18'`. *Solution.* `DateType` avec `input: 'string'` : le model reste
une string, le norm devient un `DateTime`, la vue un array de strings.

**Scénario 3 — Un tag saisi en texte.** L'entité porte un object `Tag`, le
widget un simple field texte. *Solution.* Un **model** transformer, entre
l'object et la string normalisée.

## QCM d'entraînement

Six questions sur les couches de données. Note ton choix avant d'ouvrir la
correction.

**Question 1.** Un `DateType` à trois listes déroulantes, avec l'option `input` par défaut, porte l'échéance du 18 octobre 2026. Que rend `getNormData()` ?

- **A.** `['year' => 2026, 'month' => 10, 'day' => 18]`, comme dans la documentation
- **B.** `['year' => '2026', 'month' => '10', 'day' => '18']`
- **C.** Un object `DateTime`
- **D.** La string `'2026-10-18'`

<details>
<summary>Correction</summary>

**Bonne réponse : C.** La norm data d'un `DateType` est toujours un `DateTime`
(exécuté).

A est le piège : c'est l'exemple de la documentation, contredit par le code.
B est la **vue**. D est la model data avec `input: 'string'`.

</details>

**Question 2.** `createForm(TaskType::class, $task)` reçoit `$task`. À quelle couche appartient-il ?

- **A.** La couche vue
- **B.** La couche modèle
- **C.** La couche normalisée
- **D.** À aucune : seule la Request porte des données

<details>
<summary>Correction</summary>

**Bonne réponse : B.** L'object passé à `createForm()` et celui que rend
`getData()` sont la model data.

A et C décrivent les couches converties. D ignore les données initiales.

</details>

**Question 3.** Un `IntegerType` contient `42`. Que rend `getViewData()` ?

- **A.** La string `"42"`
- **B.** L'int `42`
- **C.** Un array `['value' => 42]`
- **D.** `null`, tant que le form n'est pas soumis

<details>
<summary>Correction</summary>

**Bonne réponse : A.** La vue est faite de strings ; exécuté, model `42`, norm
`42`, view `"42"`.

B est le piège : c'est la model et la norm data. C et D inventent des formats.

</details>

**Question 4.** Un `DateType` est déclaré avec `input: 'datetime_immutable'`. Quelle est sa norm data ?

- **A.** Un `DateTimeImmutable`, comme le modèle
- **B.** Une string au format `Y-m-d`
- **C.** Un array d'ints
- **D.** Un `DateTime` mutable

<details>
<summary>Correction</summary>

**Bonne réponse : D.** Exécuté : model `DateTimeImmutable`, norm `DateTime`.
Le pivot du `DateType` ne dépend pas du format applicatif.

A est le piège : le norm n'est pas le modèle. B et C décrivent d'autres
couches ou d'autres options.

</details>

**Question 5.** À la submission, dans quel ordre les couches sont-elles reconstruites ?

- **A.** Modèle, puis normalisée, puis vue
- **B.** Normalisée, puis vue, puis modèle
- **C.** Vue, puis normalisée, puis modèle
- **D.** Les trois en même temps, par le même transformer

<details>
<summary>Correction</summary>

**Bonne réponse : C.** La Request remplit la vue ; les view transformers
inverses donnent la norm data ; les model transformers inverses, la model data.

A est l'ordre du **rendu**. B et D inventent des ordres.

</details>

**Question 6.** Le component Form peut-il être utilisé sans FrameworkBundle ?

- **A.** Oui : `Forms::createFormFactory()` suffit
- **B.** Non : il dépend du service container
- **C.** Non : il exige Twig pour le rendu et la submission
- **D.** Oui, mais seulement pour le rendu, pas pour la submission

<details>
<summary>Correction</summary>

**Bonne réponse : A.** Exécuté : un form créé par
`Forms::createFormFactory()` se soumet et rend ses trois couches.

B et C confondent le component et son intégration. D invente une limite.

</details>

## Fiche express

**Si tu dois retenir seulement 6 choses :**

- Le Form traduit **objects ↔ fields HTML**, dans les deux sens ; il est
  **autonome**.
- Trois couches : **model**, **normalized**, **view**.
- `getData()`, `getNormData()`, `getViewData()`.
- Model transformers : model ↔ norm ; view transformers : norm ↔ view.
- Rendu : model → view ; submission : view → model.
- `DateType` : norm = **`DateTime`**, quel que soit `input`.

**En une phrase, pour tout reconstruire :**

```text
MÉTIER (getData) ⇄ model transformers ⇄ PIVOT (getNormData) ⇄ view transformers ⇄ TEXTE (getViewData)
```

## Sources officielles

- [Forms, « The Data Transformation Lifecycle » et « Accessing Form Data »](https://github.com/symfony/symfony-docs/blob/8.0/forms.rst)
- [Form 8.0, `DateType`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/Extension/Core/Type/DateType.php) et [`DateTimeToArrayTransformer`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/Extension/Core/DataTransformer/DateTimeToArrayTransformer.php)
