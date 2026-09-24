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
---

## Objectif

Écrire un type de champ, et comprendre l'héritage de types qui le rend court.

## Ce qu'est un type

Un type décrit un champ ou un formulaire entier — la distinction n'existe pas :
un formulaire **est** un type, dont les champs sont d'autres types. C'est ce qui
permet d'imbriquer un formulaire dans un autre en l'ajoutant comme un champ.

Un type personnalisé étend `AbstractType`.

## Les six méthodes

| Méthode | Rôle | Défaut dans `AbstractType` |
|---|---|---|
| `buildForm()` | ajoute les champs enfants | vide |
| `configureOptions()` | déclare les options et leurs valeurs par défaut | vide |
| `getParent()` | désigne le type dont on hérite | `FormType::class` |
| `getBlockPrefix()` | fixe le préfixe des blocs Twig | dérivé du nom de classe |
| `buildView()` | passe des variables à la vue | vide |
| `finishView()` | ajuste la vue une fois les enfants construits | vide |

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

Sans `getParent()` redéfini, `AbstractType` rend `FormType::class`, la racine de
tous les types ; `FormType` seul n'a pas de parent.

## L'ordre d'exécution

`ResolvedFormType` enchaîne, pour chaque méthode, **parent d'abord, puis le
type, puis ses extensions**. Pour les options, il **clone** le résolveur du
parent avant d'appeler `configureOptions()` : un type hérite des options de ses
ancêtres et peut en changer les défauts — ici `expanded` passe à `true`.

## Le préfixe de bloc

`getBlockPrefix()` dérive le préfixe du nom de classe, suffixe `Type` retiré, en
*snake case* : `ShippingType` → `shipping`, `DeliveryAddressType` →
`delivery_address`. La vue reçoit la liste de toute la lignée. Exécuté avec
`symfony/form` 8.0.15, pour un champ `shipping` d'un formulaire `order` :

```text
["form", "choice", "shipping", "_order_shipping"]
```

Le rendu cherche d'abord le bloc le plus spécifique, puis remonte la lignée :
c'est ce qui permet de ne thématiser que ce qui change.

## Composer plutôt qu'étendre

Un type peut aussi être **utilisé** comme champ d'un autre :
`->add('shipping', ShippingType::class)`. C'est la voie normale ; `getParent()`
sert à *spécialiser* un type, pas à l'imbriquer.

## L'enregistrement

Un type est un service ordinaire. L'autoconfiguration le détecte : il n'y a rien
à déclarer tant qu'il vit dans `src/`. Il peut donc recevoir des dépendances par
son constructeur.

## Pièges d'examen

**Un formulaire est un type ; la distinction champ / formulaire n'existe pas.**

**Aucune des méthodes d'un type n'est obligatoire.**

**Sans parent déclaré, le parent est la racine de tous les types**, pas
« aucun ».

**Le parent passe avant l'enfant** : son `buildForm()` s'exécute d'abord, ses
options sont reprises puis modifiables.

## Points clés

- Un formulaire est un type ; un champ aussi.
- Six méthodes, toutes facultatives ; `getParent()` vaut `FormType` par défaut.
- Ordre : parent, type, extensions ; résolveur d'options cloné du parent.
- Préfixe de bloc dérivé du nom de classe ; la vue porte toute la lignée.
- Étendre spécialise, ajouter compose.

## Sources officielles

- [How to Create a Custom Form Field Type](https://github.com/symfony/symfony-docs/blob/8.0/form/create_custom_field_type.rst)
- [Form 8.0, `AbstractType`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/AbstractType.php) et [`ResolvedFormType`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/ResolvedFormType.php)
