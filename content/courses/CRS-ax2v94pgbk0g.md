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

## Avant la règle : le problème à résoudre

Un achat en ligne (`Order`) a un client, et le client a une adresse de livraison.
Le code postal de l'adresse est vide. On valide l'achat… et le Validator ne
signale **rien**.

Ce n'est pas un bug : par défaut, valider un object ne valide **que cet
object**. Les objects qu'il contient sont des valeurs comme les autres ; le
Validator ne les ouvre pas tant qu'on ne le lui demande pas.

Ce cours répond à trois questions :

- **où** écrire les constraints d'une class ;
- **comment** faire descendre la validation dans les objects imbriqués ;
- **ce qui se passe** quand une class hérite des constraints d'une autre.

## Le fil rouge : un auteur et son adresse

```php
use Symfony\Component\Validator\Constraints as Assert;

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

    public Address $address;
}
```

L'adresse a un code postal vide. Valider l'`Author` le verra-t-il ?

## Le cycle complet en un schéma

```text
validate($author)
     │
     ▼
1. LES CONSTRAINTS DE LA CLASS   (attributes, YAML, XML ou loadValidatorMetadata())
   + celles de TOUS ses parents  ── fusionnées, jamais remplacées
     │
     ▼
2. LES OBJECTS IMBRIQUÉS ?
   ── rien                         → ignorés, sans erreur
   ── #[Assert\Valid] sur la property   → validés (null passe)
   ── #[Assert\Cascade] sur la class    → toutes les properties typées par une class
     │
     ▼
3. LES GROUPS
   ── Valid ne dépend pas du group Default : il descend même quand on valide un autre group
   ── le group demandé est transmis à l'object imbriqué
```

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

Une property **privée** est lue sans accessor : exécuté avec
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
`addMethodMapping('loadValidatorMetadata')` — exécuté : sans elle, 0
violation ; avec elle, 1.

| Format | Où | Hors framework, à activer |
|---|---|---|
| attributes | sur la class | `enableAttributeMapping()` |
| PHP | `loadValidatorMetadata()` statique | `addMethodMapping('loadValidatorMetadata')` |
| YAML, XML | `config/validator/` dans une application | `addYamlMapping()`, `addXmlMapping()` |

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
un `null` passe. Pour exiger l'adresse **et** la valider, on combine
`#[Assert\NotNull]` et `#[Assert\Valid]`.

### Ce que `Cascade` couvre, et ce qu'il ne couvre pas

- Il ne descend que dans les properties **typées par une class**. Exécuté : une
  property sans type qui contient pourtant une `Address` invalide n'est pas
  validée ; la property typée `Address`, si.
- L'option `exclude` en retire certaines : exécuté,
  `#[Assert\Cascade(exclude: ['address'])]` rend 0 violation.
- Il n'a **pas** d'option `groups` (documentation 8.0) : exécuté,
  `new Assert\Cascade(groups: ['A'])` lève `Error`, « Unknown named parameter
  $groups ».

### `Valid` et les groups

La documentation 8.0 le signale : contrairement aux autres constraints,
`Valid` **n'utilise pas** le group `Default`. Il s'applique donc même quand on
valide un autre group, et ce group est transmis à l'object imbriqué. Exécuté :
une `Address` dont `street` porte `NotBlank(groups: ['Registration'])`, validée
via `Valid` avec le group `Registration`, signale `address.street` — et pas
`address.zipCode`, qui n'est que dans `Default`.

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

Hors framework, le lien se déclare sur le builder :
`addAttributeMappings([Product::class => [MyProductValidation::class]])`.
Exécuté : l'attribute seul ne suffit pas (aucune violation) ; avec la
correspondance, la constraint `Length(min: 10)` s'applique. Une property
absente de la class cible est refusée : la documentation annonce une
`MappingException` ; le component seul, exécuté, lève une `ValidatorException`,
« Property "nope" does not exist in class "Product". ».

## Concrètement dans ton application

- Les constraints sur l'entité ou le DTO, en attributes : la forme usuelle.
- Un object imbriqué qui a ses propres règles : `#[Assert\Valid]` sur la
  property ; s'il doit aussi exister, `#[Assert\NotNull]` en plus.
- Une class composée de plusieurs objects à valider : `#[Assert\Cascade]`, avec
  des properties **typées**.
- Une class d'un bundle tiers : `#[ExtendsValidationFor]`.
- Une class fille qui doit assouplir une règle du parent : impossible par
  redéclaration ; passer par les groups.

## À comprendre

- **Pourquoi la validation ne descend-elle pas ?** Parce qu'un object imbriqué
  peut être partagé, énorme, ou validé ailleurs : descendre doit être un choix.
- **Pourquoi `Valid` laisse-t-il passer `null` ?** Parce qu'il dit « valide ce
  qui est là », pas « il doit y avoir quelque chose » : c'est le travail de
  `NotNull`.
- **Pourquoi l'inheritance fusionne-t-il ?** Parce qu'une fille **est** un parent :
  elle doit respecter ses règles, et peut en ajouter.
- **Pourquoi `loadValidatorMetadata()` est-elle statique ?** Parce que les
  metadata décrivent la **class**, lues sans instance.

## À mémoriser

- Quatre formats : attributes, YAML, XML, `loadValidatorMetadata()` **statique**.
- Sans `Valid` ni `Cascade`, un object imbriqué est **ignoré**.
- `#[Assert\Valid]` sur la property ; `#[Assert\Cascade]` sur la class
  (properties typées, option `exclude`, pas de `groups`).
- `Valid` : `null` passe ; array → `addresses[0].zipCode`.
- `Valid` ne dépend pas du group `Default` : il descend avec le group demandé.
- Parent + enfant : constraints **fusionnées**, doublons compris.
- Moyen mnémotechnique : **« Déclarer, Descendre, Hériter »** — et rien ne
  descend sans qu'on le dise.

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

## Application : trois scénarios

**Scénario 1 — L'achat valide à tort.** `Order` contient un `Customer`
invalide, et `validate($order)` rend une liste vide. *Solution.*
`#[Assert\Valid]` sur `Order::$customer` (et `#[Assert\NotNull]` si le client
est obligatoire), ou `#[Assert\Cascade]` sur `Order` avec des properties typées.

**Scénario 2 — Un `PremiumUser` plus exigeant.** Le parent impose
`NotBlank` sur `$name`, la fille veut `Length(min: 5)`. *Solution.* Déclarer
seulement `Length(min: 5)` dans la fille : les deux s'appliquent. Redéclarer
`NotBlank` produirait une violation en double.

**Scénario 3 — Une entité d'un bundle.** Tu dois imposer un nom d'au moins dix
caractères à `Product`, que tu ne peux pas modifier. *Solution.* Une class
abstraite `ProductValidation` avec `#[ExtendsValidationFor(Product::class)]` et
`#[Assert\Length(min: 10)]` sur `$name`.

## QCM d'entraînement

Six questions sur la validation d'objects. Choisis avant d'ouvrir la
correction.

**Question 1.** `Invoice` contient `public Customer $customer`, sans attribute. `Customer::$email` porte `#[Assert\Email]` et vaut `x`. Que rend `validate($invoice)` ?

- **A.** Une violation `customer.email`
- **B.** Une exception : l'object imbriqué n'est pas configuré
- **C.** Une liste vide : la validation ne descend pas
- **D.** Une violation `email`, sans préfixe

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Sans `#[Assert\Valid]` sur la property ni
`#[Assert\Cascade]` sur la class, l'object imbriqué est ignoré, sans erreur
(exécuté).

A est le piège : c'est le résultat **avec** `Valid`. B invente une exception. D
invente un path.

</details>

**Question 2.** `Invoice::$customer` porte `#[Assert\Valid]` et vaut `null`. Combien de violations pour `customer` ?

- **A.** Une, « This value should not be null. »
- **B.** Aucune : `null` passe
- **C.** Une exception, car `Valid` attend un object
- **D.** Une, « This value should not be blank. »

<details>
<summary>Correction</summary>

**Bonne réponse : B.** `Valid` n'impose pas la présence de l'object : il
cascade dans ce qui est là ; exécuté, `null` passe. Pour exiger l'object, on ajoute `#[Assert\NotNull]`.

A et D décrivent `NotNull` et `NotBlank`, absents ici. C invente une exception.

</details>

**Question 3.** Le parent déclare `#[Assert\NotBlank]` sur `$title` ; la fille redéclare `$title` avec `#[Assert\NotBlank]` aussi. Combien de violations pour un titre vide ?

- **A.** Deux, identiques
- **B.** Une : la déclaration de la fille remplace celle du parent
- **C.** Une : le Validator dédoublonne les constraints identiques
- **D.** Aucune : la redéclaration annule la constraint du parent

<details>
<summary>Correction</summary>

**Bonne réponse : A.** Les constraints du parent sont fusionnées avec celles de
la fille, jamais remplacées (exécuté sur `$name` : deux violations identiques).

B est le piège : l'intuition de la surcharge en PHP. C invente un
dédoublonnage. D invente une annulation.

</details>

**Question 4.** `#[Assert\Cascade]` est posé sur `Shipment`, dont la property `public $box;` (sans type) contient un `Box` invalide. Que se passe-t-il ?

- **A.** `box` est validé, comme toute property qui contient un object
- **B.** La compilation des metadata échoue : la property doit être typée
- **C.** `Cascade` lève une exception, faute de `groups`
- **D.** `box` est ignoré : il n'a pas de type

<details>
<summary>Correction</summary>

**Bonne réponse : D.** `Cascade` ne suit que les properties typées par une
class. Exécuté : une property sans type n'est pas validée, une property typée
l'est.

A est le piège : c'est le contenu au moment de la validation qui semble
compter, mais `Cascade` se décide sur le type déclaré. B et C inventent des
erreurs.

</details>

**Question 5.** Dans `Author`, la method qui déclare les constraints en PHP a été écrite comme une method d'instance : le mot-clé `static` manque. Que se passe-t-il quand un `Author` est validé ?

- **A.** La method est ignorée, et les constraints aussi
- **B.** Une `MappingException`
- **C.** Le Validator instancie la class pour l'appeler
- **D.** Ça fonctionne : le mot-clé `static` est facultatif

<details>
<summary>Correction</summary>

**Bonne réponse : B.** La method doit être statique. Exécuté : « The method
"…::loadValidatorMetadata()" should be static. ».

A est le piège : la method n'est pas ignorée, elle fait échouer. C et D
inventent un comportement.

</details>

**Question 6.** `Profile::$address` porte `#[Assert\Valid]`. `Address::$street` porte `#[Assert\NotBlank(groups: ['Registration'])]`, et `Address::$zip` un `NotBlank` sans group. Les deux sont vides. Quelles violations rend `validate($profile, null, ['Registration'])` ?

- **A.** Aucune : `Valid` est dans `Default`, qui n'est pas demandé
- **B.** `address.street` et `address.zip`
- **C.** `address.street` seulement
- **D.** `address.zip` seulement

<details>
<summary>Correction</summary>

**Bonne réponse : C.** `Valid` n'utilise pas le group `Default` : il descend,
et transmet le group `Registration` à l'adresse. `zip`, seulement dans
`Default`, n'est pas vérifié (exécuté).

A est le piège : la documentation 8.0 précise justement que `Valid` échappe au
group `Default`. B et D se trompent sur le group transmis.

</details>

## Fiche express

**Si tu dois retenir seulement 7 choses :**

- Constraints en attributes, YAML, XML ou `loadValidatorMetadata()`
  **statique** (sinon `MappingException`).
- Un object imbriqué est **ignoré** sans `#[Assert\Valid]` (property) ou
  `#[Assert\Cascade]` (class).
- `Valid` laisse passer `null` : `NotNull` en plus pour l'exiger.
- `Cascade` : properties **typées** seulement, `exclude` possible, pas de
  `groups`.
- `Valid` échappe au group `Default` et transmet le group demandé.
- Parent + fille : constraints **fusionnées**, doublons compris ; seuls les
  groups permettent de s'en sortir.
- Class tierce : `#[ExtendsValidationFor(Product::class)]`.

**En une phrase, pour tout reconstruire :**

```text
DÉCLARER (4 formats) → FUSIONNER avec les parents → DESCENDRE seulement si Valid / Cascade
  → avec le group demandé, Valid compris
```

## Sources officielles

- [Validation, « Validating Object With Inheritance » et « Extending Validation for a Class »](https://github.com/symfony/symfony-docs/blob/8.0/validation.rst)
- [Constraint `Valid`](https://github.com/symfony/symfony-docs/blob/8.0/reference/constraints/Valid.rst) et [constraint `Cascade`](https://github.com/symfony/symfony-docs/blob/8.0/reference/constraints/Cascade.rst)
- [Validator 8.0, `StaticMethodLoader`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Mapping/Loader/StaticMethodLoader.php)
