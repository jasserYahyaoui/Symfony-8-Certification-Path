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
---

## Objectif

Attacher des contraintes à une classe PHP, et savoir jusqu'où la validation
descend. Les portées — propriété, accesseur, classe — sont traitées dans
*Validation scopes*.

## Les quatre formats de déclaration

Les contraintes se déclarent en **attributs PHP**, en **YAML**, en **XML** ou en
**PHP**. Les quatre expriment exactement la même chose ; l'attribut est la forme
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

Une propriété **privée** est lue sans accesseur : exécuté avec
`symfony/validator` 8.0.15, `#[Assert\NotBlank]` sur une propriété privée vide
produit sa violation.

La forme PHP passe par une méthode **statique** `loadValidatorMetadata()` :

```php
public static function loadValidatorMetadata(ClassMetadata $metadata): void
{
    $metadata->addPropertyConstraint('name', new Assert\NotBlank());
}
```

Écrite comme méthode d'instance, elle n'est pas ignorée : exécuté,
`StaticMethodLoader` lève une `MappingException`, « The method
"NonStatic::loadValidatorMetadata()" should be static. ». Hors framework, cette
méthode n'est lue que si le validateur est construit avec
`addMethodMapping('loadValidatorMetadata')`.

## La validation ne descend pas toute seule

C'est le point central de cet item. Si `Author` porte un `Address`, valider
l'`Author` ne valide **pas** les contraintes de l'`Address`. Il faut le demander,
propriété par propriété :

```php
class Author
{
    #[Assert\Valid]
    private Address $address;
}
```

ou pour toute la classe, avec **`#[Assert\Cascade]`** posé sur la classe : chaque
propriété portant un objet est alors validée en profondeur. Exécuté :

| Déclaration | Violations d'une adresse invalide |
|---|---|
| rien | **aucune** |
| `#[Assert\Valid]` sur la propriété | `address.zipCode` |
| `#[Assert\Cascade]` sur la classe | `address.zipCode` |
| `#[Assert\Valid]` sur un tableau d'adresses | `addresses[0].zipCode`, `addresses[1].zipCode` |
| `#[Assert\Valid]`, propriété à `null` | aucune |

Sans cascade, l'objet imbriqué est ignoré silencieusement — aucune erreur,
simplement aucune violation. Et `Valid` ne rend pas la propriété obligatoire :
un `null` passe.

## L'héritage fusionne, il ne remplace pas

Quand une classe en étend une autre, le validateur applique **aussi** les
contraintes du parent. Redéclarer une contrainte sur la propriété de l'enfant ne
remplace pas celle du parent : les deux s'appliquent. Exécuté : parent
`NotBlank`, enfant `Length(min: 5)` — la valeur `'abc'` échoue sur `Length` ;
la même contrainte `NotBlank` redéclarée dans l'enfant produit **deux**
violations identiques.

Ce comportement ne se désactive pas. Le seul contournement documenté est de
placer les contraintes du parent et de l'enfant dans des **groupes** différents,
puis de choisir le groupe à la validation.

Ne pas confondre avec la fusion décrite dans *Framework overloading* : celle-ci
porte sur les fichiers de validation de **plusieurs bundles**, celle-là sur une
**hiérarchie de classes**.

## Contraindre une classe qu'on ne peut pas modifier

Symfony 8.0 documente `#[ExtendsValidationFor(Product::class)]`, posé sur une
classe séparée — souvent abstraite — dont les contraintes s'ajoutent à celles
de `Product`, comme si elles y étaient déclarées.

Hors framework, le lien se déclare au constructeur :
`addAttributeMappings([Product::class => [MyProductValidation::class]])`.
Exécuté : l'attribut seul ne suffit pas (aucune violation) ; avec la
correspondance, la contrainte `Length(min: 10)` s'applique. Une propriété
absente de la classe cible est refusée : la documentation annonce une
`MappingException` ; le composant seul, exécuté, lève une `ValidatorException`,
« Property "nope" does not exist in class "Product". ».

## Pièges d'examen

**Sans `Valid` ni `Cascade`, l'objet imbriqué est ignoré** — une validation qui
passe à tort.

**`Valid` n'impose pas la présence de l'objet** : `null` passe.

**Redéclarer une contrainte dans la fille ne remplace pas celle du parent** : les
deux s'appliquent, et le comportement ne se désactive pas.

**`loadValidatorMetadata()` est statique** ; non statique, elle lève une
`MappingException`.

## Points clés

- Quatre formats équivalents ; attribut usuel, `loadValidatorMetadata()`
  statique.
- Cascade : `#[Assert\Valid]` sur la propriété, ou `#[Assert\Cascade]` sur la
  classe.
- Sans cascade, l'objet imbriqué est ignoré sans erreur.
- Contraintes du parent **fusionnées**, jamais remplacées ; seuls les groupes
  permettent de s'en sortir.
- `#[ExtendsValidationFor]` contraint une classe qu'on ne possède pas.

## Sources officielles

- [Validation, « Validating Object With Inheritance » et « Extending Validation for a Class »](https://github.com/symfony/symfony-docs/blob/8.0/validation.rst)
- [Contrainte `Valid`](https://github.com/symfony/symfony-docs/blob/8.0/reference/constraints/Valid.rst) et [contrainte `Cascade`](https://github.com/symfony/symfony-docs/blob/8.0/reference/constraints/Cascade.rst)
- [Validator 8.0, `StaticMethodLoader`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Mapping/Loader/StaticMethodLoader.php)
