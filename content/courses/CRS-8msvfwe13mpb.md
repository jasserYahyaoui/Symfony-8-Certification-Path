---
id: CRS-8msvfwe13mpb
official_item: OIT-fhpttc4c5x0k
title: "Forms creation"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-24"
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
---

## Objectif

Construire un form, le lier à un object, et savoir quand un field ne doit
pas l'être.

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
deviné : `FormBuilder::add()` prend `TextType` directement.

## `data_class`

Elle indique la class que le form manipule. Sa default value est
**devinée** de l'object passé : `createForm(TaskType::class, $task)` suffit à la
fixer à `Task`. Vérifié par exécution (`symfony/form` 8.0.15) :

| Création | `getData()` après soumission |
|---|---|
| `data_class` déclarée, sans object | un `Task` neuf, instancié par `empty_data` |
| ni `data_class` ni object | un array, `{"task": "x"}` |

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
une property privée sans accesseur lève une `NoSuchPropertyException`.

## Le field non mappé

Un field qui n'a pas de property correspondante — une case « j'accepte les
conditions », un file téléversé — doit être déclaré `'mapped' => false` :

```php
->add('agreeTerms', CheckboxType::class, ['mapped' => false])
```

Sans cela, `getForm()` échoue dès la lecture des données initiales :
`NoSuchPropertyException`. La valeur d'un field non mappé se lit par
`$form->get('agreeTerms')->getData()`.

## Pièges d'examen

**Le nom du field n'est pas décoratif.** Le renommer casse le binding.

**Les accesseurs passent avant la property publique** : un getter l'emporte.

**Le type omis est deviné par les *guessers***, pas par le type PHP ; à défaut,
`TextType`.

**`data_class` se devine de l'object passé**, mais seule sa déclaration fait
instancier l'object quand aucun n'est fourni.

**Un field sans property correspondante doit être déclaré non mappé.**

## Points clés

- Un form réutilisé se déclare en class de type.
- `add(nom, type, options)` ; type omis → *guessers*, sinon `TextType`.
- `data_class` : devinée de l'object, à déclarer quand même.
- Lecture : `get`/`is`/`has`/`can`, method du même nom, puis property.
- Field sans property : `mapped: false`, lu par `$form->get(...)->getData()`.

## Sources officielles

- [Forms, « Building Forms », « Form Type Guessing », « Unmapped Fields »](https://github.com/symfony/symfony-docs/blob/8.0/forms.rst)
- [Form 8.0, `FormFactory`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/FormFactory.php) et [`FormType`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/Extension/Core/Type/FormType.php)
- [PropertyInfo 8.0, `ReflectionExtractor`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/PropertyInfo/Extractor/ReflectionExtractor.php)
