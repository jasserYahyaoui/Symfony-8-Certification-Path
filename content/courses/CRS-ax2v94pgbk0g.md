---
id: CRS-ax2v94pgbk0g
official_item: OIT-d3yp0sq36xrx
title: "PHP object validation"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-29"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/validation.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/validation.rst"
    anchor: "validating-object-with-inheritance"
    branch: "8.0"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/reference/constraints/Valid.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/reference/constraints/Valid.rst"
    symbol_or_lines: '"This constraint is used to enable validation on objects that are embedded as properties on an object being validated. This allows you to validate an object and all sub-objects associated with it"'
    branch: "8.0"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/reference/constraints/Cascade.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/reference/constraints/Cascade.rst"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    symbol_or_lines: "Cascade constraint"
    verified_at: "2026-09-29"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Validator/Mapping/Loader/StaticMethodLoader.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Mapping/Loader/StaticMethodLoader.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "StaticMethodLoader::loadClassMetadata(), should be static"
    verified_at: "2026-09-29"
outcome_sections:
  OUT-c85m458ahp7g: "Les quatre formats de déclaration"
  OUT-mqjbbf32kjca: "La validation ne descend pas toute seule"
  OUT-wmvdnjq75f68: "L'inheritance fusionne, il ne remplace pas"
---

## Objectif

Attacher des constraints à une class PHP, et savoir jusqu'où la validation
descend. Les scopes — property, accesseur, class — sont traités dans
*Validation scopes*.

## Les quatre formats de déclaration

Les constraints se déclarent en **attributes PHP**, en **YAML**, en **XML** ou en
**PHP**. Les quatre expriment exactement la même chose ; l'attribute est la forme
usuelle.

```php
use Symfony\Component\Validator\Constraints as Assert;

class Author
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 3)]
    private string $name;
}
```

Une property **privée** est lue sans accesseur : exécuté avec
`symfony/validator` 8.0.15, `#[Assert\NotBlank]` sur une property privée vide
produit sa violation.

La forme PHP passe par une method **statique** `loadValidatorMetadata()` :

```php
public static function loadValidatorMetadata(ClassMetadata $metadata): void
{
    $metadata->addPropertyConstraint('name', new Assert\NotBlank());
}
```

Écrite comme method d'instance, elle n'est pas ignorée : exécuté,
`StaticMethodLoader` lève une `MappingException`, « The method
"NonStatic::loadValidatorMetadata()" should be static. ». Hors framework, cette
method n'est lue que si le validator est construit avec
`addMethodMapping('loadValidatorMetadata')`.

## La validation ne descend pas toute seule

C'est le point central de cet item. Si `Author` porte un `Address`, valider
l'`Author` ne valide **pas** les constraints de l'`Address`. Il faut le demander,
property par property :

```php
class Author
{
    #[Assert\Valid]
    private Address $address;
}
```

ou pour toute la class, avec **`#[Assert\Cascade]`** posé sur la class : chaque
property portant un object est alors validée en profondeur. Exécuté :

| Déclaration | Violations d'une adresse invalide |
|---|---|
| rien | **aucune** |
| `#[Assert\Valid]` sur la property | `address.zipCode` |
| `#[Assert\Cascade]` sur la class | `address.zipCode` |
| `#[Assert\Valid]` sur un array d'adresses | `addresses[0].zipCode`, `addresses[1].zipCode` |
| `#[Assert\Valid]`, property à `null` | aucune |

Sans cascade, l'object imbriqué est ignoré silencieusement — aucune erreur,
simplement aucune violation. Et `Valid` ne rend pas la property obligatoire :
un `null` passe.

## L'inheritance fusionne, il ne remplace pas

Quand une class en étend une autre, le validator applique **aussi** les
constraints du parent. Redéclarer une constraint sur la property de l'enfant ne
remplace pas celle du parent : les deux s'appliquent. Exécuté : parent
`NotBlank`, enfant `Length(min: 5)` — la valeur `'abc'` échoue sur `Length` ;
la même constraint `NotBlank` redéclarée dans l'enfant produit **deux**
violations identiques.

Ce comportement ne se désactive pas. Le seul contournement documenté est de
placer les constraints du parent et de l'enfant dans des **groups** différents,
puis de choisir le group à la validation.

Ne pas confondre avec la fusion décrite dans *Framework overloading* : celle-ci
porte sur les files de validation de **plusieurs bundles**, celle-là sur une
**hiérarchie de classes**.

## Contraindre une class qu'on ne peut pas modifier

Symfony 8.0 documente `#[ExtendsValidationFor(Product::class)]`, posé sur une
class séparée — souvent abstraite — dont les constraints s'ajoutent à celles
de `Product`, comme si elles y étaient déclarées.

Hors framework, le lien se déclare au constructeur :
`addAttributeMappings([Product::class => [MyProductValidation::class]])`.
Exécuté : l'attribute seul ne suffit pas (aucune violation) ; avec la
correspondance, la constraint `Length(min: 10)` s'applique. Une property
absente de la class cible est refusée : la documentation annonce une
`MappingException` ; le composant seul, exécuté, lève une `ValidatorException`,
« Property "nope" does not exist in class "Product". ».

## Pièges d'examen

**Sans `Valid` ni `Cascade`, l'object imbriqué est ignoré** — une validation qui
passe à tort.

**`Valid` n'impose pas la présence de l'object** : `null` passe.

**Redéclarer une constraint dans la fille ne remplace pas celle du parent** : les
deux s'appliquent, et le comportement ne se désactive pas.

**`loadValidatorMetadata()` est statique** ; non statique, elle lève une
`MappingException`.

## Points clés

- Quatre formats équivalents ; attribute usuel, `loadValidatorMetadata()`
  statique.
- Cascade : `#[Assert\Valid]` sur la property, ou `#[Assert\Cascade]` sur la
  class.
- Sans cascade, l'object imbriqué est ignoré sans erreur.
- Constraints du parent **fusionnées**, jamais remplacées ; seuls les groups
  permettent de s'en sortir.
- `#[ExtendsValidationFor]` contraint une class qu'on ne possède pas.

## Sources officielles

- [Validation, « Validating Object With Inheritance » et « Extending Validation for a Class »](https://github.com/symfony/symfony-docs/blob/8.0/validation.rst)
- [Constraint `Valid`](https://github.com/symfony/symfony-docs/blob/8.0/reference/constraints/Valid.rst) et [constraint `Cascade`](https://github.com/symfony/symfony-docs/blob/8.0/reference/constraints/Cascade.rst)
- [Validator 8.0, `StaticMethodLoader`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Mapping/Loader/StaticMethodLoader.php)
