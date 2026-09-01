---
id: CRS-ax2v94pgbk0g
official_item: OIT-d3yp0sq36xrx
title: "PHP object validation"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/validation.rst"
    anchor: "validating-object-with-inheritance"
    branch: "8.0"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/reference/constraints/Valid.rst"
    branch: "8.0"
    verified_at: "2026-09-01"
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

La forme PHP passe par une méthode **statique** `loadValidatorMetadata()` :

```php
public static function loadValidatorMetadata(ClassMetadata $metadata): void
{
    $metadata->addPropertyConstraint('name', new Assert\NotBlank());
}
```

C'est la forme à reconnaître : elle apparaît dans la documentation dès qu'un
exemple ne peut pas être écrit en attribut.

## La validation ne descend pas toute seule

C'est le point central de cet item. Si `Author` porte un `Address`, valider
l'`Author` ne valide **pas** les contraintes de l'`Address`. Il faut le demander :

```php
class Author
{
    #[Assert\Valid]
    private Address $address;
}
```

`Valid` est la contrainte de **cascade**. Sans elle, l'objet imbriqué est ignoré,
silencieusement — aucune erreur, simplement aucune violation.

Le chemin de propriété des violations reflète alors l'imbrication :
`getPropertyPath()` retourne `address.zipCode`.

## L'héritage fusionne, il ne remplace pas

Quand une classe en étend une autre, le validateur applique **aussi** les
contraintes du parent. Redéclarer une contrainte sur la propriété de l'enfant ne
remplace pas celle du parent : les deux s'appliquent.

Ce comportement ne se désactive pas. Le seul contournement documenté est de
placer les contraintes du parent et de l'enfant dans des **groupes** différents,
puis de choisir le groupe à la validation.

Ne pas confondre avec la fusion décrite dans *Framework overloading* : celle-ci
porte sur les fichiers de validation de **plusieurs bundles**, celle-là sur une
**hiérarchie de classes**. Les deux fusionnent, pour des raisons différentes.

## Pièges d'examen

**Sans `#[Assert\Valid]`, l'objet imbriqué est ignoré** — aucune erreur, aucune
violation, une validation qui passe à tort.

**Redéclarer une contrainte dans la fille ne remplace pas celle du parent** :
les deux s'appliquent, et le comportement ne se désactive pas.

**`loadValidatorMetadata()` est statique.** Écrite comme méthode d'instance,
elle n'est jamais appelée.

## Points clés

- Quatre formats équivalents ; attribut usuel, `loadValidatorMetadata()` statique.
- Un objet imbriqué n'est validé que si la propriété porte `#[Assert\Valid]`.
- Sans cascade, l'objet imbriqué est ignoré sans erreur.
- Les contraintes du parent sont **fusionnées**, jamais remplacées ; seuls les
  groupes permettent de s'en sortir.

## Sources officielles

- [Validation, « Validating Object With Inheritance »](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/validation.rst)
- [Contrainte `Valid`](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/reference/constraints/Valid.rst)
