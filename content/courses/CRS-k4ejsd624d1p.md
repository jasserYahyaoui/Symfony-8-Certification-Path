---
id: CRS-k4ejsd624d1p
official_item: OIT-gcs0jathkv9b
title: "Form type extensions"
content_level: MINIMAL
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/form/create_form_type_extension.rst"
    anchor: "creating-a-form-type-extension"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
---

## Objectif

Modifier un type de champ **existant** — y compris un type fourni par Symfony ou
par un bundle tiers — sans le remplacer.

## Le principe

Une extension étend `AbstractTypeExtension` et s'applique à tous les champs des
types qu'elle désigne, partout dans l'application. C'est la façon d'ajouter une
option à `FileType` ou une classe CSS à tous les `TextType` sans toucher à un
seul formulaire.

C'est la différence avec un type personnalisé : un type crée un nouveau champ,
une extension modifie ceux qui existent déjà.

## La seule méthode obligatoire

```php
class ImageTypeExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        return [FileType::class];
    }
}
```

`getExtendedTypes()` est **statique**, retourne un itérable, et c'est la seule
méthode que l'on doive implémenter. Elle en retourne plusieurs si l'extension
s'applique à plusieurs types.

Étendre `FormType::class` touche **tous** les champs, puisque tous en héritent.

## Ce qu'on redéfinit ensuite

Les mêmes points d'accroche que dans un type : `buildForm()` pour ajouter un
écouteur, `configureOptions()` pour déclarer une option supplémentaire,
`buildView()` pour exposer une variable au gabarit.

## L'enregistrement

Une extension est un service tagué `form.type_extension`. L'autoconfiguration
pose le tag toute seule pour une classe placée dans `src/`.

## Points clés

- Une extension modifie des types existants ; un type en crée un nouveau.
- `getExtendedTypes()` est statique et seule obligatoire.
- Étendre `FormType::class` affecte tous les champs.
- Service tagué `form.type_extension`, posé par autoconfiguration.

## Sources officielles

- [How to Create a Form Type Extension](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/form/create_form_type_extension.rst)
