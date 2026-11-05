---
id: CRS-ax2v94pgbk0g
official_item: OIT-d3yp0sq36xrx
title: "PHP object validation"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-28"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/validation.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/validation.rst"
    anchor: "validating-object-with-inheritance"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    symbol_or_lines: "Validating Object With Inheritance, Extending Validation for a Class"
    verified_at: "2026-10-28"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/reference/constraints/Valid.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/reference/constraints/Valid.rst"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    symbol_or_lines: '"This constraint is used to enable validation on objects that are embedded as properties on an object being validated"; "the Valid constraint does not use the Default group"; traverse'
    verified_at: "2026-10-28"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/reference/constraints/Cascade.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/reference/constraints/Cascade.rst"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    symbol_or_lines: "Cascade constraint, exclude option, no groups option"
    verified_at: "2026-10-28"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Validator/Mapping/Loader/StaticMethodLoader.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Mapping/Loader/StaticMethodLoader.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "StaticMethodLoader::loadClassMetadata(), should be static"
    verified_at: "2026-10-28"
outcome_sections:
  OUT-c85m458ahp7g: "Les quatre formats de déclaration"
  OUT-mqjbbf32kjca: "La validation ne descend pas toute seule"
  OUT-wmvdnjq75f68: "L'inheritance fusionne, il ne remplace pas"
---

## Objectif

Attacher des constraints à une class PHP, et savoir jusqu'où la validation
descend. Les scopes — property, accessor, class — sont traités dans
*Validation scopes*.

## L'essentiel

Les constraints d'une class se déclarent en attributes, YAML, XML ou PHP. La
validation **ne descend pas** toute seule dans un object imbriqué : il faut
`#[Assert\Valid]` ou `#[Assert\Cascade]`. Et les constraints d'un parent
**s'ajoutent** à celles de l'enfant, sans jamais être remplacées.

## Le schéma

```text
validate($author)
1. constraints de la class + celles de TOUS ses parents — fusionnées
2. objects imbriqués : rien → ignorés ; #[Valid] sur la property → validés (null passe) ;
                       #[Cascade] sur la class → toutes les properties typées par une class
3. groups : Valid ne dépend pas de Default ; le group demandé est transmis à l'object imbriqué
```

## Le fil rouge : un auteur et son adresse

```php
class Address
{
    #[Assert\NotBlank]
    public string $zipCode = '';
}

class Author
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 3)]
    private string $name;

    public Address $address;      // sans Valid : l'adresse n'est pas validée
}
```

## Les quatre formats de déclaration

| Format | Où | Hors framework, à activer |
|---|---|---|
| attributes | sur la class | `enableAttributeMapping()` |
| PHP | `loadValidatorMetadata()`, **statique** | `addMethodMapping('loadValidatorMetadata')` |
| YAML, XML | `config/validator/` | `addYamlMapping()`, `addXmlMapping()` |

- Une property **privée** est lue sans accessor.
- `loadValidatorMetadata()` non statique : `StaticMethodLoader` lève une
  `MappingException`, « …should be static. »

## La validation ne descend pas toute seule

| Déclaration | Violations d'une adresse invalide |
|---|---|
| rien | **aucune** |
| `#[Assert\Valid]` sur la property | `address.zipCode` |
| `#[Assert\Cascade]` sur la class | `address.zipCode` |
| `#[Assert\Valid]` sur un array d'adresses | `addresses[0].zipCode`, `addresses[1].zipCode` |
| `#[Assert\Valid]`, property à `null` | aucune : `NotNull` en plus pour l'exiger |

- `Cascade` ne suit que les properties **typées par une class** ;
  `exclude: ['address']` en retire ; il n'a pas d'option `groups` —
  `new Assert\Cascade(groups: ['A'])` : « Unknown named parameter $groups ».
- `Valid` n'utilise **pas** le group `Default` : validé avec le group
  `Registration`, il descend et transmet ce group — une `Address` dont `street`
  porte `NotBlank(groups: ['Registration'])` signale `address.street`, pas
  `address.zipCode`.

## L'inheritance fusionne, il ne remplace pas

Parent `NotBlank`, enfant `Length(min: 5)` : `'abc'` échoue sur `Length`, et
une valeur vide sur les deux. La même constraint redéclarée dans l'enfant
produit **deux** violations identiques. Cela ne se désactive pas ; seuls des
groups différents séparent le parent de l'enfant.

## Contraindre une class qu'on ne peut pas modifier

`#[ExtendsValidationFor(Product::class)]`, sur une class séparée, ajoute ses
constraints à celles de `Product`. Hors framework, le lien se déclare aussi :
`addAttributeMappings([Product::class => [MyProductValidation::class]])` —
l'attribute seul n'a aucun effet.

## Pièges d'examen

**Sans `Valid` ni `Cascade`, l'object imbriqué est ignoré** — une validation qui
passe à tort.

**`Valid` n'impose pas la présence de l'object** : `null` passe.

**Redéclarer une constraint dans la fille ne remplace pas celle du parent** : les
deux s'appliquent, et le comportement ne se désactive pas.

**`loadValidatorMetadata()` est statique** ; non statique, elle lève une
`MappingException`.

**`Cascade` ne descend que dans les properties typées par une class** : une
property sans type qui contient un object n'est pas validée.

**`Valid` n'appartient pas au group `Default`** : il descend aussi quand on
valide un autre group, et y transmet ce group.

## Application

- Une facture valide malgré un client à l'email faux → `#[Assert\Valid]` sur
  `$customer`, ou `#[Assert\Cascade]` sur la class.
- Une adresse obligatoire et valide → `#[Assert\NotNull]` **et**
  `#[Assert\Valid]`.

## QCM d'entraînement

**Question 1.** `#[Assert\Cascade]` est posé sur `Shipment`, dont la property
`public $box;` (sans type) contient un `Box` invalide. Que se passe-t-il ?

- **A.** `box` est validé, comme toute property qui contient un object
- **B.** La lecture des metadata échoue : la property doit être typée
- **C.** `Cascade` lève une exception, faute de `groups`
- **D.** `box` est ignoré

<details>
<summary>Correction</summary>

**Bonne réponse : D.** `Cascade` ne descend que dans les properties typées par
une class ; une property sans type est ignorée, sans erreur.

</details>

**Question 2.** Que fait `new Assert\Cascade(groups: ['A'])` ?

- **A.** Il valide en cascade dans le group `A` seulement
- **B.** Une `Error` : parameter inconnu
- **C.** Il ignore l'option et valide dans `Default`
- **D.** Une `MappingException` à la lecture des metadata

<details>
<summary>Correction</summary>

**Bonne réponse : B.** `Cascade` n'a pas d'option `groups` : le named argument
inconnu est refusé par PHP lui-même.

</details>

**Question 3.** Hors framework, une class porte
`#[ExtendsValidationFor(Product::class)]`, et le validator n'a reçu que
`enableAttributeMapping()`. Ses constraints s'appliquent-elles à `Product` ?

- **A.** Non : il faut aussi `addAttributeMappings()`
- **B.** Oui : l'attribute suffit
- **C.** Oui, mais seulement avec `#[Assert\Cascade]`
- **D.** Une exception : la class cible n'est pas modifiable

<details>
<summary>Correction</summary>

**Bonne réponse : A.** Hors framework, la correspondance `Product` → class
d'extension se déclare sur le builder ; sans elle, aucune violation.

</details>

**Question 4.** `Author` porte `#[Assert\Cascade(exclude: ['address'])]`, et son
adresse est invalide. Combien de violations pour l'adresse ?

- **A.** Une : `exclude` ne vise que les arrays
- **B.** Une exception : `exclude` exige `Valid`
- **C.** Aucune
- **D.** Toutes celles de l'adresse

<details>
<summary>Correction</summary>

**Bonne réponse : C.** `exclude` retire la property de la cascade : l'adresse
n'est pas validée, comme sans aucune déclaration.

</details>

## Fiche express

**Si tu dois retenir seulement 7 choses :**

- Quatre formats : **attributes**, YAML, XML, **PHP** statique.
- La validation **ne descend pas** : `#[Valid]` ou `#[Cascade]`.
- **`Valid`** : `null` passe ; il transmet le group demandé.
- **`Cascade`** : properties typées seulement, `exclude`, pas de `groups`.
- Parent et enfant **fusionnent** ; une constraint redéclarée compte deux fois.
- `loadValidatorMetadata()` non statique : **`MappingException`**.
- **`#[ExtendsValidationFor]`** pour une class qu'on ne peut pas modifier.

Moyen mnémotechnique : **« Déclarer, Descendre, Hériter »** — et rien ne
descend sans qu'on le dise.

## Sources officielles

- [Validation, « Validating Object With Inheritance » et « Extending Validation for a Class »](https://github.com/symfony/symfony-docs/blob/8.0/validation.rst)
- [Constraint `Valid`](https://github.com/symfony/symfony-docs/blob/8.0/reference/constraints/Valid.rst) et [constraint `Cascade`](https://github.com/symfony/symfony-docs/blob/8.0/reference/constraints/Cascade.rst)
- [Validator 8.0, `StaticMethodLoader`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Mapping/Loader/StaticMethodLoader.php)

