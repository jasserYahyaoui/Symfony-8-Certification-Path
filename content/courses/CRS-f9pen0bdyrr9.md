---
id: CRS-f9pen0bdyrr9
official_item: OIT-5xvjqa4203xe
title: "Form types (built-in and custom)"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-24"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/form/create_custom_field_type.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/form/create_custom_field_type.rst"
    anchor: "creating-a-custom-field-type"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Form/AbstractType.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/AbstractType.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "AbstractType defaults"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Form/ResolvedFormType.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/ResolvedFormType.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "ResolvedFormType::buildForm(), getOptionsResolver()"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Form/Util/StringUtil.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/Util/StringUtil.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "StringUtil::fqcnToBlockPrefix()"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Form/FormRenderer.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/FormRenderer.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "FormRenderer::searchAndRenderBlock()"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/reference/forms/types/collection.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/reference/forms/types/collection.rst"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    symbol_or_lines: "allow_add, allow_delete, delete_empty, prototype, prototype_name"
    verified_at: "2026-10-16"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/reference/forms/types/options/by_reference.rst.inc"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/reference/forms/types/options/by_reference.rst.inc"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    symbol_or_lines: "by_reference"
    verified_at: "2026-10-16"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Form/Extension/Core/EventListener/ResizeFormListener.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/Extension/Core/EventListener/ResizeFormListener.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "preSubmit(): rows added only with allowAdd, removed only with allowDelete"
    verified_at: "2026-10-16"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Form/Extension/Core/DataAccessor/PropertyPathAccessor.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/Extension/Core/DataAccessor/PropertyPathAccessor.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "setValue(): skips writing an object back when by_reference and the value is the same instance"
    verified_at: "2026-10-16"
outcome_sections:
  OUT-kevx5cz60q4s: "Ce qu'est un type"
  OUT-0ztsd5vjbe72: "Les six methods"
  OUT-dstzcbzx0kt7: "`getParent()`, la key de la brièveté"
  OUT-tq1etfz8r98x: "Composer plutôt qu'étendre"
  OUT-q996f75mz363: "Une collection de types"
---

## Objectif

Écrire un type de field, et comprendre l'inheritance de types qui le rend court.

## Ce qu'est un type

Un type décrit un field ou un form entier — la distinction n'existe pas :
un form **est** un type, dont les fields sont d'autres types. C'est ce qui
permet d'imbriquer un form dans un autre en l'ajoutant comme un field.

Un type personnalisé étend `AbstractType`.

## Les six methods

| Method | Role | Défaut dans `AbstractType` |
|---|---|---|
| `buildForm()` | ajoute les fields enfants | vide |
| `configureOptions()` | déclare les options et leurs default values | vide |
| `getParent()` | désigne le type dont on hérite | `FormType::class` |
| `getBlockPrefix()` | fixe le préfixe des blocks Twig | dérivé du nom de class |
| `buildView()` | passe des variables à la vue | vide |
| `finishView()` | ajuste la vue une fois les enfants construits | vide |

Aucune n'est obligatoire : un type qui ne fait qu'imposer des options n'implémente
que `configureOptions()` et `getParent()`.

## `getParent()`, la key de la brièveté

Le type parent fournit tout : ses fields, ses options, son rendu. Le type
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

Sans `getParent()` redéfini, `AbstractType` rend `FormType::class`, la racine de
tous les types ; `FormType` seul n'a pas de parent.

## L'ordre d'exécution

`ResolvedFormType` enchaîne, pour chaque method, **parent d'abord, puis le
type, puis ses extensions**. Pour les options, il **clone** le resolver du
parent avant d'appeler `configureOptions()` : un type hérite des options de ses
ancêtres et peut en changer les défauts — ici `expanded` passe à `true`.

## Le préfixe de block

`getBlockPrefix()` dérive le préfixe du nom de class, suffixe `Type` retiré, en
*snake case* : `ShippingType` → `shipping`, `DeliveryAddressType` →
`delivery_address`. La vue reçoit la liste de toute la lignée. Exécuté avec
`symfony/form` 8.0.15, pour un field `shipping` d'un form `order` :

```text
["form", "choice", "shipping", "_order_shipping"]
```

Le rendu cherche d'abord le block le plus spécifique, puis remonte la lignée :
c'est ce qui permet de ne thématiser que ce qui change.

## Composer plutôt qu'étendre

Un type peut aussi être **utilisé** comme field d'un autre :
`->add('shipping', ShippingType::class)`. C'est la voie normale ; `getParent()`
sert à *spécialiser* un type, pas à l'imbriquer.

## Une collection de types

`CollectionType` répète un même type, `entry_type`, pour chaque élément d'un
array ou d'une collection. Exécuté sur Form 8.0.15 avec les tags `['a', 'b']` :

| Soumis | Sans option | Avec l'option |
|---|---|---|
| `['a', 'b', 'c']` | ignoré, et l'extension Validator signale « This form should not contain extra fields. » | `allow_add` : `['a', 'b', 'c']` |
| `['a']` | `['a', null]` : l'élément manquant devient `null` | `allow_delete` : `['a']` |
| `['a', '']` | avec `allow_delete` : `['a', null]` | `delete_empty` en plus : `['a']` |

`allow_add` donne aussi un **prototype** : la vue porte `vars.prototype`, un
field nommé `__name__` (`prototype_name` le change) que le JavaScript copie en
remplaçant ce marqueur. Sans `allow_add`, pas de prototype.

**`by_reference`** compte dès que la collection est un object — exécuté avec un
`ArrayObject`. À `true`, le défaut, la collection est modifiée
**sur place** : ni setter, ni adder, ni remover n'est appelé. À `false`,
`removeTag()` puis `addTag()` sont appelés s'ils existent, sinon `setTags()` avec
une nouvelle instance. Un adder qui pose l'autre côté d'une relation n'est donc
appelé qu'avec `by_reference: false`.

## L'enregistrement

Un type est un service ordinaire. L'autoconfiguration le détecte : il n'y a rien
à déclarer tant qu'il vit dans `src/`. Il peut donc recevoir des dependencies par
son constructor.

## Pièges d'examen

**Un form est un type ; la distinction field / form n'existe pas.**

**Aucune des methods d'un type n'est obligatoire.**

**Sans parent déclaré, le parent est la racine de tous les types**, pas
« aucun ».

**Le parent passe avant l'enfant** : son `buildForm()` s'exécute d'abord, ses
options sont reprises puis modifiables.

**Sans `allow_add`, un élément en trop n'est pas ajouté** ; sans `allow_delete`,
un élément absent devient `null` au lieu de disparaître.

**`by_reference: true` n'appelle jamais `addTag()`** sur une collection object.

## Points clés

- Un form est un type ; un field aussi.
- Six methods, toutes facultatives ; `getParent()` vaut `FormType` par défaut.
- Ordre : parent, type, extensions ; resolver d'options cloné du parent.
- Préfixe de block dérivé du nom de class ; la vue porte toute la lignée.
- Étendre spécialise, ajouter compose.
- `CollectionType` : `allow_add` (et le prototype `__name__`), `allow_delete`,
  `delete_empty` ; `by_reference: false` pour passer par les adders.

## Sources officielles

- [How to Create a Custom Form Field Type](https://github.com/symfony/symfony-docs/blob/8.0/form/create_custom_field_type.rst)
- [Form 8.0, `AbstractType`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/AbstractType.php) et [`ResolvedFormType`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/ResolvedFormType.php)
