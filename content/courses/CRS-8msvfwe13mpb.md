---
id: CRS-8msvfwe13mpb
official_item: OIT-fhpttc4c5x0k
title: "Forms creation"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/forms.rst"
    anchor: "building-forms"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
---

## Objectif

Construire un formulaire, le lier à un objet, et savoir quand un champ ne doit
pas l'être.

## Deux endroits pour le construire

Dans un contrôleur, pour un formulaire jetable :

```php
$form = $this->createFormBuilder($task)
    ->add('task', TextType::class)
    ->add('save', SubmitType::class, ['label' => 'Créer'])
    ->getForm();
```

Dans une **classe de type**, dès qu'il sert plus d'une fois — c'est la
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

Le contrôleur l'instancie ensuite par `$this->createForm(TaskType::class, $task)`.

## `add()` et ses trois arguments

`add(nom, type, options)`. Le **nom** n'est pas décoratif : c'est par lui que le
champ est relié à une propriété de l'objet.

Le type est facultatif — omis, Symfony le devine d'après le type de la
propriété. L'expliciter reste plus lisible et plus sûr.

## `data_class`

Déclarée dans `configureOptions()`, elle indique la classe que le formulaire
manipule. Elle conditionne le fait que `getData()` retourne un objet plutôt
qu'un tableau.

## La correspondance nom ↔ propriété

Un champ nommé `dueDate` cherche, sur l'objet, dans cet ordre : une propriété
publique `$dueDate`, puis `getDueDate()`, `isDueDate()`, `hasDueDate()`, et un
`setDueDate()` pour écrire. Le composant PropertyAccess fait ce travail.

## Le champ non mappé

Un champ qui n'a pas de propriété correspondante — une case « j'accepte les
conditions », un fichier téléversé — doit être déclaré `'mapped' => false` :

```php
->add('agreeTerms', CheckboxType::class, ['mapped' => false])
```

Sans cela, le formulaire cherche une propriété inexistante et échoue. La valeur
d'un champ non mappé se lit par `$form->get('agreeTerms')->getData()`.

## Points clés

- Un formulaire réutilisé se déclare en classe de type, pas dans le contrôleur.
- `add(nom, type, options)` ; le nom relie le champ à la propriété.
- `data_class` déclare l'objet manipulé.
- Un champ sans propriété doit être `mapped: false`, et se lit par
  `$form->get(...)->getData()`.

## Sources officielles

- [Forms, « Building Forms »](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/forms.rst)
