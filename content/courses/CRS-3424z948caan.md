---
id: CRS-3424z948caan
official_item: OIT-ywrx9x3hg9z8
title: "Object Oriented Programming"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-09"
official_sources:
  - url: "https://raw.githubusercontent.com/php/doc-en/master/language/oop5/visibility.xml"
    readable_url: "https://github.com/php/doc-en/blob/master/language/oop5/visibility.xml"
    symbol_or_lines: 'Property Visibility; and Asymmetric Property Visibility — "a different scope for reading (get) and writing (set) ... provided it is not more permissive than the default visibility"'
    repository: "php/doc-en"
    branch: "master"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/php/doc-en/master/language/oop5/late-static-bindings.xml"
    readable_url: "https://github.com/php/doc-en/blob/master/language/oop5/late-static-bindings.xml"
    symbol_or_lines: 'Limitations of self:: — "Static references to the current class like self:: or __CLASS__ are resolved using the class in which the function belongs, as in where it was defined"; and the introduction, static:: "can be used to reference the called class in the context of static inheritance"'
    repository: "php/doc-en"
    branch: "master"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/php/doc-en/master/language/oop5/properties.xml"
    readable_url: "https://github.com/php/doc-en/blob/master/language/oop5/properties.xml"
    repository: "php/doc-en"
    branch: "master"
    symbol_or_lines: 'Readonly properties — "As of PHP 8.4.0, readonly properties are implicitly protected(set), so may be set from child classes"'
    verified_at: "2026-10-09"
outcome_sections:
  OUT-zqwbp11av4pr: "`self` contre `static`"
  OUT-qcb24vqg8pkt: "Visibilité"
  OUT-2tvh0svv8jhg: "Promotion de constructeur"
---

## Objectif

Maîtriser les mécanismes object que les autres items du syllabus ne couvrent
pas : visibilité, résolution `self`/`static`, immuabilité, promotion.

> Attributes, Interfaces, Abstract classes, Traits et Enums ont chacun leur
> propre item. Cette page ne les réexplique pas.

## Visibilité

`public`, `protected` (class et descendants), `private` (class déclarante
seule). Depuis PHP 8.4, la lecture et l'écriture peuvent différer :

```php
public private(set) string $title;   // lisible partout, écrivable par la classe
```

`private` est lié à la **class déclarante**, pas à l'instance : deux instances
de la même class accèdent mutuellement à leurs membres privés.

## Promotion de constructeur

```php
final class Money
{
    public function __construct(
        private readonly int $amount,
        private readonly string $currency,
    ) {}
}
```

La promotion déclare la property, la type et l'affecte en une seule écriture.

## `readonly`

Une property `readonly` est initialisable **une seule fois**. Depuis PHP 8.4,
elle est implicitement `protected(set)` : une class fille peut donc
l'initialiser — exécuté. Depuis 8.2, la class entière peut être `readonly`, ce qui
rend toutes ses properties `readonly`.

```php
$money = new Money(100, 'EUR');
// $money->amount = 200;  → Error: Cannot modify readonly property
```

Attention : `readonly` sur un object interdit la **réaffectation**, pas la
mutation de l'object référencé.

## `self` contre `static`

C'est la distinction la plus examinée de cet item.

```php
class ParentClass
{
    public static function createSelf(): self   { return new self(); }
    public static function createStatic(): static { return new static(); }
}

final class ChildClass extends ParentClass {}

ChildClass::createSelf();     // ParentClass  — self est figé à la déclaration
ChildClass::createStatic();   // ChildClass   — static suit l'appelant
```

`self` se résout **à la class où le code est écrit**. `static` se résout à la
class **réellement appelée** : c'est le binding statique tardive.

`parent::` cible la class parente ; `$this::` équivaut à `static::`.

## Pièges d'examen

**`self` ne suit pas l'inheritance.** Une factory écrite avec `new self()` renvoie
toujours la class parente, même appelée depuis l'enfant.

**`private` bloque la surcharge.** Une method `private` redéfinie dans une
fille est une method différente, pas une surcharge — d'où `#[\Override]` en 8.3
pour vérifier une intention de surcharge.

**Une property `readonly` typée n'a pas de default value** : elle ne peut
pas en avoir, ou elle serait déjà initialisée.

## Points clés

- `private` est lié à la class déclarante, pas à l'instance.
- `self` = class de déclaration ; `static` = class appelée.
- `readonly` : une seule écriture, depuis la class ou une fille
  (`protected(set)` implicite en 8.4) ; l'object reste mutable.
- La promotion de constructeur déclare, type et affecte en une fois.

## Sources officielles

- Manuel PHP — *Visibility*, *Late Static Bindings*, *Readonly properties*
