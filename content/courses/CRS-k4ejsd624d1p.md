---
id: CRS-k4ejsd624d1p
official_item: OIT-gcs0jathkv9b
title: "Form type extensions"
content_level: MINIMAL
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-29"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/form/create_form_type_extension.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/form/create_form_type_extension.rst"
    anchor: "creating-a-form-type-extension"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Form/DependencyInjection/FormPass.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/DependencyInjection/FormPass.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "FormPass::processFormTypeExtensions()"
    verified_at: "2026-09-29"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/DependencyInjection/Compiler/PriorityTaggedServiceTrait.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Compiler/PriorityTaggedServiceTrait.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "findAndSortTaggedServices(), AsTaggedItem only with a default priority method"
    verified_at: "2026-09-29"
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
s'applique à plusieurs types. Un itérable **vide** est refusé à la compilation
du conteneur : exécuté, `FormPass` lève une `InvalidArgumentException`, « The
getExtendedTypes() method for service "e" does not return any extended
types. ».

## Ce qu'on redéfinit ensuite

Les mêmes points d'accroche que dans un type : `buildForm()` pour ajouter un
écouteur, `configureOptions()` pour déclarer une option supplémentaire,
`buildView()` et `finishView()` pour exposer une variable au gabarit.

## Qui est touché, et quand

Une extension suit l'**héritage** des types. Exécuté avec `symfony/form` 8.0.15,
une extension sur `TextType` qui déclare l'option `help_icon` :

| Champ | Résultat |
|---|---|
| `TextType` | option acceptée, variable de vue transmise |
| `EmailType`, qui hérite de `TextType` | option acceptée aussi |
| `IntegerType`, qui hérite de `FormType` | `UndefinedOptionsException` |

Étendre `FormType::class` touche donc tous les champs — **mais pas les
boutons** : `ButtonType` n'a pas de parent, et une extension de `FormType` ne
l'atteint pas.

L'ordre d'appel suit celui des types : pour un `EmailType`, `buildForm()` de
l'extension de `FormType` passe avant celui de l'extension de `TextType`. Chaque
extension s'exécute juste après le type qu'elle étend.

## L'enregistrement

Une extension est un service tagué `form.type_extension`. L'autoconfiguration
pose le tag toute seule pour une classe placée dans `src/`.

Plusieurs extensions d'un même type s'ordonnent par l'attribut **`priority`**
du tag : 0 par défaut, la plus haute passe la première. Exécuté avec
`FormPass` : trois extensions de priorités -5, 0 et 20 sont chargées dans
l'ordre 20, 0, -5.

La documentation précise que cet attribut exige de **déclarer le service
explicitement**. Le code le confirme : un attribut PHP `#[AsTaggedItem(priority:
10)]` sur une extension autoconfigurée est **ignoré** par `FormPass` — exécuté,
l'ordre reste celui de l'enregistrement.

`php bin/console debug:form` vérifie qu'une extension est bien enregistrée.

## Pièges d'examen

**Un type crée un champ ; une extension modifie ceux qui existent.** Pour
ajouter une option à un type fourni par Symfony, écrire un nouveau type ne sert
à rien.

**La méthode qui désigne les types étendus est statique**, retourne un itérable,
et ne peut pas être vide.

**Une extension atteint les types enfants**, pas les types frères : étendre
`TextType` touche `EmailType`, pas `IntegerType`.

**Étendre le type racine touche tous les champs, pas les boutons.**

**La priorité se règle sur le tag**, dans la configuration du service.

## Points clés

- Une extension modifie des types existants et leurs descendants.
- `getExtendedTypes()` est statique, seule obligatoire, jamais vide.
- `FormType::class` : tous les champs, aucun bouton.
- Tag `form.type_extension`, posé par autoconfiguration ; `priority` sur le
  tag, plus haute d'abord.

## Sources officielles

- [How to Create a Form Type Extension](https://github.com/symfony/symfony-docs/blob/8.0/form/create_form_type_extension.rst)
- [Form 8.0, `FormPass`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/DependencyInjection/FormPass.php)
- [DependencyInjection 8.0, `PriorityTaggedServiceTrait`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Compiler/PriorityTaggedServiceTrait.php)
