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

## Avant la règle : le problème à résoudre

Une application de tâches a besoin du même form à trois endroits :
création, modification, duplication. Chaque fois, il faut les mêmes fields
(`task`, `dueDate`), reliés aux mêmes properties d'une `Task`, avec une case
« j'accepte les conditions » qui, elle, ne correspond à **aucune** property.

Trois questions se posent :

- **où** décrire le form pour l'écrire une seule fois ;
- **comment** chaque field trouve « sa » property ;
- **que faire** d'un field qui n'a pas de property.

## Le fil rouge : un `TaskType`

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

## Le cycle complet en un schéma

```text
createForm(TaskType::class, $task)
     │
     ▼
buildForm()      add(NOM, TYPE, OPTIONS) pour chaque field
                 TYPE omis → type guessers (constraints, Doctrine, enum) → sinon TextType
     │
     ▼
data_class       déclarée, ou devinée de $task ; sans object : empty_data crée un Task neuf
     │
     ▼
lecture          NOM → PropertyAccess : getX(), isX(), hasX(), canX(), x(), puis $x publique
                 mapped: false → pas de lecture (sinon NoSuchPropertyException dès getForm())
                 property_path → un autre chemin ; inherit_data → les données du parent
```

## Deux endroits pour le construire

Dans un controller, pour un form jetable :

```php
$form = $this->createFormBuilder($task)
    ->add('task', TextType::class)
    ->add('save', SubmitType::class, ['label' => 'Créer'])
    ->getForm();
```

Dans une **class de type**, dès qu'il sert plus d'une fois — c'est la
recommandation officielle :

```php
class TaskType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('task', TextType::class)->add('dueDate', DateType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Task::class]);
    }
}
```

Le controller l'instancie ensuite par `$this->createForm(TaskType::class, $task)`.

## `add()` et ses trois arguments

`add(nom, type, options)`. Le **nom** n'est pas décoratif : c'est par lui que le
field est relié à une property de l'object.

Le type est facultatif : omis ou `null`, il est **deviné**. Mais pas d'après le
type PHP de la property : `FormFactory::createBuilderForProperty()` interroge
les *type guessers* enregistrés — constraints de validation, métadonnées
Doctrine ; `EnumFormTypeGuesser` propose `EnumType` pour une property typée par
une enum. Sans deviner, c'est `TextType`. Les options `required`,
`maxlength` et `pattern` sont devinées au passage. Sans `data_class`, rien n'est
deviné : `FormBuilder::add()` prend `TextType` directement (exécuté).

## `data_class`

Elle indique la class que le form manipule. Sa default value est
**devinée** de l'object passé : `createForm(TaskType::class, $task)` suffit à la
fixer à `Task`. Vérifié par exécution (`symfony/form` 8.0.15) :

| Création | `getData()` après submission |
|---|---|
| `data_class` déclarée, sans object | un `Task` neuf, instancié par `empty_data` |
| ni `data_class` ni object | un array, `{"task": "x"}` |
| un `Task` passé, sans `data_class` déclarée | `getConfig()->getDataClass()` vaut `Task` |

La déclarer reste recommandé : c'est elle qui fait instancier l'object quand
aucun n'est passé, et elle devient indispensable pour les forms imbriqués.

## La correspondance nom ↔ property

Pour lire un field nommé `dueDate`, PropertyAccess essaie, dans cet ordre
(`ReflectionExtractor::getReadInfo()`) : `getDueDate()`, `isDueDate()`,
`hasDueDate()`, `canDueDate()`, puis une method `dueDate()`, et seulement
ensuite la property publique `$dueDate`. Pour écrire, un `setDueDate()` ou la
property publique.

Exécuté avec PropertyAccess 8.0 : un object qui a à la fois `public $dueDate` et
`getDueDate()` est lu **par le getter** ; une method nue `dueDate()` est lue ;
une property privée sans accessor lève une `NoSuchPropertyException`.

## Le field non mappé

Un field qui n'a pas de property correspondante — une case « j'accepte les
conditions », un file téléversé — doit être déclaré `'mapped' => false` :

```php
->add('agreeTerms', CheckboxType::class, ['mapped' => false])
```

Sans cela, `getForm()` échoue dès la lecture des données initiales :
`NoSuchPropertyException`, « Can't get a way to read the property "agreeTerms"
in class "Task" » (exécuté). La valeur d'un field non mappé se lit par
`$form->get('agreeTerms')->getData()` — exécuté : `true` après une case
cochée.

## Ailleurs que sous son nom

Deux options écartent un field de la règle « même nom que la property » :

- **`property_path`** désigne le path lu et écrit : un field `title` avec
  `property_path: 'name'` sert `$author->name` ; `'profile.bio'` descend dans un
  object imbriqué.
- **`inherit_data: true`**, posé sur un type, fait travailler ses fields sur les
  données **du parent** : un `AddressType` réutilisé lit et écrit `city` sur
  l'`Author` lui-même, sans property `address`.

Exécuté sur Form 8.0.15 : `title` lit `old` dans `name` puis l'écrase, `bio`
atteint `profile.bio`, et `$form->get('address')->getData()` **est** l'`Author`.

## Concrètement dans ton application

- Un form réutilisé : une class `…Type` étendant `AbstractType`, avec
  `data_class`.
- Un form jetable dans une seule action : `createFormBuilder()`.
- Un field qui n'existe pas dans l'entité : `mapped: false`, et
  `$form->get('…')->getData()` pour le lire.
- Un field dont le nom diffère de la property : `property_path`.
- Un group de fields réutilisé sans object dédié (une adresse dans plusieurs
  entités) : un type avec `inherit_data: true`.

## À comprendre

- **Pourquoi le nom compte-t-il ?** Parce que le form ne connaît la property
  que par lui : c'est lui qui sert à lire et à écrire.
- **Pourquoi `data_class` si elle se devine ?** Parce que, sans object passé,
  il n'y a rien à deviner : seule la déclaration permet de créer l'object vide.
- **Pourquoi les accessors avant la property ?** Parce qu'un getter peut
  calculer ou protéger la valeur : PropertyAccess respecte l'encapsulation
  quand elle existe.

## À mémoriser

- Class de type pour un form réutilisé ; `createFormBuilder()` pour un form
  jetable.
- `add(nom, type, options)` ; type omis → guessers, sinon `TextType`.
- `data_class` : devinée de l'object, à déclarer quand même.
- Lecture : `get`, `is`, `has`, `can`, method du même nom, puis property
  publique.
- Field sans property : `mapped: false` ; sinon `NoSuchPropertyException` dès
  `getForm()`.
- `property_path` : un autre path ; `inherit_data` : les données du parent.
- Moyen mnémotechnique : **« Le nom est l'adresse »**.

## Pièges d'examen

**Le nom du field n'est pas décoratif.** Le renommer casse le binding.

**Les accessors passent avant la property publique** : un getter l'emporte.

**Le type omis est deviné par les *guessers***, pas par le type PHP ; à défaut,
`TextType`.

**`data_class` se devine de l'object passé**, mais seule sa déclaration fait
instancier l'object quand aucun n'est fourni.

**Un field sans property correspondante doit être déclaré non mappé.**

## Application : trois scénarios

**Scénario 1 — Le même form à trois endroits.** *Solution.* Un `TaskType`
avec `data_class: Task::class`, et `createForm(TaskType::class, $task)` dans
chaque action.

**Scénario 2 — La case des conditions.** `agreeTerms` n'existe pas dans
`Task`, et la page plante avant même de s'afficher. *Solution.*
`'mapped' => false` ; lire la case par `$form->get('agreeTerms')->getData()`.

**Scénario 3 — Un field renommé.** L'API publique veut un field `title`, l'entité
a `$name`. *Solution.* `->add('title', TextType::class, ['property_path' =>
'name'])`.

## QCM d'entraînement

Six questions sur la construction des forms. Choisis avant d'ouvrir la
correction.

**Question 1.** Un form jetable est construit par `createFormBuilder()`, sans object ni `data_class`. Son seul field, `age`, est ajouté sans préciser de class de type. Quelle class Symfony retient-il ?

- **A.** `IntegerType`, déduit du nom
- **B.** `TextType`, sans aucune devinette
- **C.** `NumberType`, le type le plus général pour un nombre
- **D.** Une erreur : le type est obligatoire sans `data_class`

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Sans `data_class`, `FormBuilder::add()` prend `TextType`
directement (exécuté).

A et C supposent une devinette par le nom ou le type, qui n'a pas lieu. D
invente une erreur.

</details>

**Question 2.** Un `Task` a `public $dueDate = 'prop'` et `getDueDate()` qui rend `'getter'`. Que lit le field `dueDate` ?

- **A.** `'prop'` : la property publique est lue directement
- **B.** Les deux, concaténées
- **C.** `'getter'` : l'accessor passe avant la property
- **D.** Une erreur d'ambiguïté

<details>
<summary>Correction</summary>

**Bonne réponse : C.** PropertyAccess essaie `getDueDate()` avant la property
publique (exécuté).

A est le piège : l'intuition « public = direct ». B et D inventent des
comportements.

</details>

**Question 3.** Un form déclare `data_class: Task::class`, mais aucun object n'est passé à `createForm()`. Après submission de `task = x`, que rend `getData()` ?

- **A.** Un nouveau `Task` dont `task` vaut `x`
- **B.** Un array `['task' => 'x']`
- **C.** `null` : aucun object n'a été fourni
- **D.** Une exception : `data_class` exige un object

<details>
<summary>Correction</summary>

**Bonne réponse : A.** `data_class` fait instancier l'object par `empty_data`
(exécuté).

B est le résultat **sans** `data_class` ni object. C et D inventent des
comportements.

</details>

**Question 4.** `TaskType` ajoute `agreeTerms` (une checkbox) alors que `Task` n'a pas cette property, sans autre option. Que se passe-t-il ?

- **A.** La case s'affiche, et sa valeur est ignorée à la submission
- **B.** La valeur est stockée dans une property dynamique `agreeTerms`
- **C.** Le field est automatiquement considéré comme non mappé
- **D.** `NoSuchPropertyException` dès la construction du form

<details>
<summary>Correction</summary>

**Bonne réponse : D.** Exécuté : « Can't get a way to read the property
"agreeTerms" … » dès `getForm()`. Il faut `'mapped' => false`.

A et C décrivent le comportement **avec** `mapped: false`. B invente une
property dynamique.

</details>

**Question 5.** Un field `title` doit lire et écrire `$author->name`. Quelle option ?

- **A.** `'mapped' => 'name'`
- **B.** `'property_path' => 'name'`
- **C.** `'inherit_data' => 'author.name'`
- **D.** `'data_class' => 'name'`

<details>
<summary>Correction</summary>

**Bonne réponse : B.** `property_path` désigne le path lu et écrit ; exécuté,
`title` lit puis écrase `name`.

A : `mapped` est un bool. C : `inherit_data` est un bool qui donne les
données du parent. D désigne une class.

</details>

**Question 6.** `AddressType` a `inherit_data: true` et est ajouté sous le nom `address` dans un form d'`Author`. Où va la ville soumise ?

- **A.** Dans une property `address` de l'`Author`, qui doit exister
- **B.** Dans un nouvel object `Address`
- **C.** Dans la property `city` de l'`Author` lui-même
- **D.** Nulle part : `inherit_data` rend le type non mappé

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Les fields du type travaillent sur les données du
parent ; exécuté, `$form->get('address')->getData()` **est** l'`Author`.

A est le piège : c'est le comportement sans `inherit_data`. B et D inventent
des comportements.

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

**En une phrase, pour tout reconstruire :**

```text
CLASS DE TYPE (data_class) → add(NOM, type, options) → NOM → accessor ou property
  ; pas de property ? mapped: false ; autre nom ? property_path
```

## Sources officielles

- [Forms, « Building Forms », « Form Type Guessing », « Unmapped Fields »](https://github.com/symfony/symfony-docs/blob/8.0/forms.rst)
- [Form 8.0, `FormFactory`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/FormFactory.php) et [`FormType`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/Extension/Core/Type/FormType.php)
- [PropertyInfo 8.0, `ReflectionExtractor`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/PropertyInfo/Extractor/ReflectionExtractor.php)
