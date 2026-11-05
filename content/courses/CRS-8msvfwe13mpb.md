---
id: CRS-8msvfwe13mpb
official_item: OIT-fhpttc4c5x0k
title: "Forms creation"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-28"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/forms.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/forms.rst"
    anchor: "building-forms"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Form/FormFactory.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/FormFactory.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "FormFactory::createBuilderForProperty()"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Form/FormBuilder.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/FormBuilder.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "FormBuilder::add()"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Form/Extension/Core/Type/FormType.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/Extension/Core/Type/FormType.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "FormType::configureOptions(), data_class, empty_data"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/PropertyInfo/Extractor/ReflectionExtractor.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/PropertyInfo/Extractor/ReflectionExtractor.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "ReflectionExtractor::getReadInfo()"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/reference/forms/types/options/property_path.rst.inc"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/reference/forms/types/options/property_path.rst.inc"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    symbol_or_lines: "property_path"
    verified_at: "2026-10-16"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/form/inherit_data_option.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/form/inherit_data_option.rst"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    symbol_or_lines: "inherit_data"
    verified_at: "2026-10-16"
outcome_sections:
  OUT-nafaf5j5xy59: "Deux endroits pour le construire"
  OUT-5dydayznyx68: "`add()` et ses trois arguments"
  OUT-04d4xdarmk8w: "`data_class`"
  OUT-75g8bz40n09y: "La correspondance nom ↔ property"
  OUT-pe7s3cdg6fgb: "Le field non mappé"
  OUT-7gbvq4n8vsgp: "Ailleurs que sous son nom"
---

## Objectif

Construire un form, le lier à un object, et savoir quand un field ne doit
pas l'être.

## L'essentiel

Un form se décrit dans une **class de type** dès qu'il sert plus d'une fois.
Chaque `add()` relie un field à une property **par son nom** ; `data_class`
dit quelle class le form manipule. Un field sans property est déclaré
`mapped: false`, ou relié ailleurs par `property_path`.

## Le schéma

```text
createForm(TaskType::class, $task)
buildForm()    add(NOM, TYPE, OPTIONS) ; TYPE omis → guessers (constraints, Doctrine, enum) → sinon TextType
data_class     déclarée, ou devinée de $task ; sans object : empty_data crée un Task neuf
lecture        NOM → getX(), isX(), hasX(), canX(), x(), puis $x publique
               mapped: false → pas de lecture ; property_path → un autre chemin ; inherit_data → le parent
```

## L'exemple : un `TaskType`

```php
class TaskType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('task', TextType::class)
            ->add('dueDate', DateType::class)
            ->add('agreeTerms', CheckboxType::class, ['mapped' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Task::class]);
    }
}
```

## Deux endroits pour le construire

| Besoin | Où |
|---|---|
| un form jetable, une seule action | `$this->createFormBuilder($task)->add(…)->getForm()` |
| un form réutilisé (recommandé) | une class `AbstractType`, puis `$this->createForm(TaskType::class, $task)` |

## `add()` et ses trois arguments

`add(nom, type, options)`. Le **nom** relie le field à une property.

Le type est facultatif : omis ou `null`, il est **deviné** par les *type
guessers* — constraints de validation, métadonnées Doctrine,
`EnumFormTypeGuesser` pour une property typée par une enum —, pas d'après le
type PHP. À défaut, `TextType`. Sans `data_class`, rien n'est deviné :
`TextType` directement.

## `data_class`

| Création | Résultat |
|---|---|
| un `Task` passé, sans `data_class` déclarée | `getDataClass()` vaut `Task` : devinée |
| `data_class` déclarée, sans object | `getData()` rend un `Task` neuf, instancié par `empty_data` |
| ni `data_class` ni object | `getData()` rend un array, `{"task": "x"}` |

La déclarer reste recommandé : elle seule fait instancier l'object quand aucun
n'est passé.

## La correspondance nom ↔ property

Pour lire `dueDate`, PropertyAccess essaie : `getDueDate()`, `isDueDate()`,
`hasDueDate()`, `canDueDate()`, une method `dueDate()`, et seulement ensuite
la property publique. Pour écrire : `setDueDate()` ou la property publique.
Une property privée sans accessor lève une `NoSuchPropertyException`.

## Le field non mappé

Une case « j'accepte les conditions », un file téléversé : pas de property,
donc `'mapped' => false`. Sans cela, `getForm()` échoue :
`NoSuchPropertyException`, « Can't get a way to read the property "agreeTerms"
in class "Task" ». Sa valeur se lit par `$form->get('agreeTerms')->getData()`.

## Ailleurs que sous son nom

- **`property_path`** : un field `title` avec `property_path: 'name'` sert
  `$author->name` ; `'profile.bio'` descend dans un object imbriqué.
- **`inherit_data: true`** sur un type : ses fields travaillent sur les données
  **du parent** — un `AddressType` écrit `city` sur l'`Author` lui-même.

## Pièges d'examen

**Le nom du field n'est pas décoratif.** Le renommer casse le binding.

**Les accessors passent avant la property publique** : un getter l'emporte.

**Le type omis est deviné par les *guessers***, pas par le type PHP ; à défaut,
`TextType`.

**`data_class` se devine de l'object passé**, mais seule sa déclaration fait
instancier l'object quand aucun n'est fourni.

**Un field sans property correspondante doit être déclaré non mappé.**

## Application

- **La case des conditions** n'a pas de property → `'mapped' => false`, lue par
  `$form->get('agreeTerms')->getData()`.
- L'API publique veut `title`, l'entité a `$name` → `'property_path' => 'name'`.

## QCM d'entraînement

**Question 1.** `ProductType` a `data_class: Product`, où `public float $price`
ne porte ni constraint ni mapping Doctrine. `->add('price')`, sans type : quel
type obtient le field ?

- **A.** `NumberType`, d'après le type PHP
- **B.** `TextType`
- **C.** `MoneyType`, pour un prix
- **D.** Une exception : le type est obligatoire

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Les guessers lisent constraints, Doctrine et enums, pas
le type PHP de la property ; faute d'indice, c'est `TextType`.

</details>

**Question 2.** Même form, `public Status $status`, où `Status` est une enum.
`->add('status')`, sans type : quel type obtient le field ?

- **A.** `EnumType`
- **B.** `ChoiceType` avec des choix vides
- **C.** `TextType`, comme sans indice
- **D.** Une exception : une enum doit être déclarée

<details>
<summary>Correction</summary>

**Bonne réponse : A.** `EnumFormTypeGuesser` propose `EnumType` pour une
property typée par une enum ; les autres guessers n'ont rien à dire.

</details>

**Question 3.** `TaskType` ne déclare pas `data_class`, et le controller fait
`createForm(TaskType::class, $task)`. Que vaut `getConfig()->getDataClass()` ?

- **A.** `null` : l'option n'est pas déclarée
- **B.** `stdClass`
- **C.** `Task`
- **D.** Une exception à la création

<details>
<summary>Correction</summary>

**Bonne réponse : C.** La default value de `data_class` est devinée de l'object
passé ; c'est sans object qu'elle reste à déclarer.

</details>

**Question 4.** `agreeTerms` est déclaré `'mapped' => false` et la case est
cochée. Comment le controller lit-il sa valeur après la submission ?

- **A.** `$form->getData()->agreeTerms`
- **B.** Impossible : un field non mappé est jeté
- **C.** `$form->getConfig()->getOption('agreeTerms')`
- **D.** `$form->get('agreeTerms')->getData()`

<details>
<summary>Correction</summary>

**Bonne réponse : D.** Le field garde sa propre valeur, `true` ici ; seul
l'object lié ne la reçoit pas.

</details>

## Fiche express

**Si tu dois retenir seulement 6 choses :**

- Form réutilisé : **class de type** ; jetable : `createFormBuilder()`.
- `add(nom, type, options)` : le **nom** relie le field à la property.
- Type omis : **guessers**, sinon `TextType` (toujours `TextType` sans
  `data_class`).
- `data_class` devinée de l'object, mais seule sa déclaration crée l'object
  vide.
- Lecture : **accessors d'abord** (`get`, `is`, `has`, `can`, `x()`), puis
  property publique.
- Field sans property : **`mapped: false`** ; autre path : `property_path` ;
  données du parent : `inherit_data`.

Moyen mnémotechnique : **« Le nom est l'adresse »**.

## Sources officielles

- [Forms, « Building Forms », « Form Type Guessing », « Unmapped Fields »](https://github.com/symfony/symfony-docs/blob/8.0/forms.rst)
- [Form 8.0, `FormFactory`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/FormFactory.php) et [`FormType`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/Extension/Core/Type/FormType.php)
- [PropertyInfo 8.0, `ReflectionExtractor`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/PropertyInfo/Extractor/ReflectionExtractor.php)

