---
id: CRS-f9pen0bdyrr9
official_item: OIT-5xvjqa4203xe
title: "Form types (built-in and custom)"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/form/create_custom_field_type.rst"
    anchor: "creating-a-custom-field-type"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
---

## Objectif

Écrire un type de champ, et comprendre l'héritage de types qui le rend court.

## Ce qu'est un type

Un type décrit un champ ou un formulaire entier — la distinction n'existe pas :
un formulaire **est** un type, dont les champs sont d'autres types. C'est ce qui
permet d'imbriquer un formulaire dans un autre en l'ajoutant comme un champ.

Un type personnalisé étend `AbstractType`.

## Les quatre méthodes

| Méthode | Rôle |
|---|---|
| `buildForm()` | ajoute les champs enfants |
| `configureOptions()` | déclare les options et leurs valeurs par défaut |
| `getParent()` | désigne le type dont on hérite |
| `getBlockPrefix()` | fixe le préfixe des blocs Twig utilisés au rendu |

Aucune n'est obligatoire : un type qui ne fait qu'imposer des options n'implémente
que `configureOptions()` et `getParent()`.

## `getParent()`, la clé de la brièveté

Le type parent fournit tout : ses champs, ses options, son rendu. Le type
personnalisé se contente d'en modifier une partie.

```php
class ShippingType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => ['Standard' => 'standard', 'Express' => 'express'],
            'expanded' => true,
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
```

Sans `getParent()`, le parent implicite est `FormType`, la racine de tous les
types.

## Composer plutôt qu'étendre

Un type peut aussi être **utilisé** comme champ d'un autre :
`->add('shipping', ShippingType::class)`. C'est la voie normale ; `getParent()`
sert à *spécialiser* un type, pas à l'imbriquer.

## L'enregistrement

Un type est un service ordinaire. L'autoconfiguration le détecte : il n'y a rien
à déclarer tant qu'il vit dans `src/`. Il peut donc recevoir des dépendances par
son constructeur.

## Points clés

- Un formulaire est un type ; un champ aussi.
- `buildForm`, `configureOptions`, `getParent`, `getBlockPrefix` — aucune
  obligatoire.
- `getParent()` hérite d'un type existant ; le défaut implicite est `FormType`.
- Étendre spécialise, ajouter compose : ce ne sont pas la même chose.
- Autoconfiguré comme service, donc injectable.

## Sources officielles

- [How to Create a Custom Form Field Type](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/form/create_custom_field_type.rst)
