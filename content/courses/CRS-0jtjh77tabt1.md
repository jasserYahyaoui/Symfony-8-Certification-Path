---
id: CRS-0jtjh77tabt1
official_item: OIT-webdvbgbrfth
title: "Abstract classes"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-26"
official_sources:
  - url: "https://raw.githubusercontent.com/php/doc-en/master/language/oop5/abstract.xml"
    readable_url: "https://github.com/php/doc-en/blob/master/language/oop5/abstract.xml"
    symbol_or_lines: '"Classes defined as abstract cannot be instantiated, and any class that contains at least one abstract method or property must also be abstract"; abstract methods "declare the method''s signature and whether it is public or protected"; "As of PHP 8.4, an abstract class may declare an abstract property, either public or protected"; "An abstract property may be satisfied either by a standard property or by a property with defined hooks"'
    repository: "php/doc-en"
    branch: "master"
    verified_at: "2026-10-26"
  - url: "https://raw.githubusercontent.com/php/doc-en/master/language/oop5/traits.xml"
    readable_url: "https://github.com/php/doc-en/blob/master/language/oop5/traits.xml"
    repository: "php/doc-en"
    branch: "master"
    symbol_or_lines: 'Abstract Trait Members — "Public, protected, and private methods are supported. Prior to PHP 8.0.0, only public and protected abstract methods were supported"'
    verified_at: "2026-10-09"
  - url: "https://raw.githubusercontent.com/php/doc-en/master/language/oop5/interfaces.xml"
    readable_url: "https://github.com/php/doc-en/blob/master/language/oop5/interfaces.xml"
    repository: "php/doc-en"
    branch: "master"
    symbol_or_lines: '"Although they are supported, including constructors in interfaces is strongly discouraged"'
    verified_at: "2026-10-09"
outcome_sections:
  OUT-fs6z2g5dv4qq: "Règles"
  OUT-zt4gxw6enqcj: "Interface ou abstract class ?"
---

## Objectif

Écrire une abstract class correcte et savoir quand la préférer à une
interface.

## Règles

```php
abstract class Repository
{
    public function __construct(protected Connection $connection) {}

    abstract protected function table(): string;      // pas de corps

    public function findAll(): array                  // corps fourni
    {
        return $this->connection->query('SELECT * FROM '.$this->table());
    }
}
```

- Une abstract class **ne peut pas être instanciée**.
- Une method abstraite n'a **pas de corps** et peut être `public` ou
  `protected`, jamais `private` dans une class ; un trait, lui, admet
  `abstract private` depuis PHP 8.0 — exécuté.
- Une class contenant au moins une method — ou une property — abstraite
  **doit** être déclarée `abstract`.
- La class fille doit implémenter toutes les methods abstraites, sauf à être
  elle-même abstraite, avec une visibilité **égale ou plus permissive** et une signature compatible.
- Une abstract class peut avoir un constructor, des properties et des
  methods concrètes.

## Les properties abstraites — depuis PHP 8.4

Une abstract class peut exiger une **property** comme elle exige une method.
La property abstraite déclare un hook **sans corps** — `get;`, `set;` ou les
deux — qui dit ce que la class fille doit permettre :

```php
abstract class Shape
{
    abstract public string $label { get; }   // exigence : lisible en public

    public function describe(): string
    {
        return 'Forme : '.$this->label;
    }
}

final class Circle extends Shape
{
    public string $label = 'cercle';         // une property ordinaire suffit
}
```

Une property ordinaire, `readonly` ou hookée satisfait `{ get; }`. Les règles,
chacune exécutée avec PHP 8.4.19 :

- **Pas de hook, pas d'`abstract`** : `abstract public string $label;` lève
  « Only hooked properties may be declared abstract ».
- **Au moins un hook sans corps.** L'autre peut en avoir un, qui sert
  d'implémentation par défaut ; deux corps lèvent « Abstract property
  Shape::$label must specify at least one abstract hook ».
- **`public` ou `protected`, jamais `private`** : « Property hook cannot be both
  abstract and private ».
- **Elle compte comme une method abstraite.** Une fille qui ne la fournit pas
  lève « Class Circle contains 1 abstract method and must therefore be
  declared abstract or implement the remaining methods (Shape::$label::get) ».
- **La fille peut élargir, pas restreindre** : `protected` devient `public` sans
  erreur ; `protected` pour un `get` public lève « Access level to
  Circle::$label must be public (as in class Shape) ». Un `set` exige une
  écriture publique : une property `readonly`, implicitement `protected(set)`,
  ne le satisfait pas.

## Interface ou abstract class ?

| | Interface | Abstract class |
|---|---|---|
| Corps de method | Non | Oui |
| Properties d'état | Non | Oui |
| Constructor | Signature seulement, déconseillée | Oui |
| Visibilité d'une method abstraite | Publique uniquement | `public` ou `protected` ; une method concrète peut être `private` |
| Property exigée (PHP 8.4) | `public`, hooks sans corps | `public` ou `protected` ; un des hooks peut avoir un body |
| Nombre par class | Plusieurs | **Une seule** |

La règle pratique : une interface décrit **ce qu'un object sait faire** ; une
abstract class fournit **du comportement partagé**. Un besoin de constructor
ou d'état commun tranche pour l'abstract class.

## Pièges d'examen

**`abstract private` est une erreur fatale.** Une method abstraite privée
serait invisible depuis la class fille, donc impossible à implémenter.

**Une seule class parente.** Besoin de deux jeux de comportements partagés :
c'est un trait ou une composition, pas un second `extends`.

**`abstract` et `final` sont incompatibles** : l'un exige une extension,
l'autre l'interdit.

**Une property abstraite sans hook est une erreur fatale.**
`abstract public string $label;` ne compile pas : il faut `{ get; }`,
`{ set; }` ou les deux, dont au moins un sans corps.

## Points clés

- Non instanciable ; method abstraite sans corps, jamais `private`.
- PHP 8.4 : une property abstraite déclare un hook sans corps ; `public` ou
  `protected`, jamais `private`.
- Une seule abstract class héritée, plusieurs interfaces implémentées.
- Interface = contrat ; abstract class = comportement partagé.

## Sources officielles

- Manuel PHP — *Class Abstraction*
