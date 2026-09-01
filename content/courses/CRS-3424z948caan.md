---
id: CRS-3424z948caan
official_item: OIT-ywrx9x3hg9z8
title: "Object Oriented Programming"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/php/doc-en/master/language/oop5/visibility.xml"
    repository: "php/doc-en"
    branch: "master"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/php/doc-en/master/language/oop5/late-static-bindings.xml"
    repository: "php/doc-en"
    branch: "master"
    verified_at: "2026-09-01"
---

## Objectif

Maîtriser les mécanismes objet que les autres items du syllabus ne couvrent
pas : visibilité, résolution `self`/`static`, immuabilité, promotion.

> Attributes, Interfaces, Abstract classes, Traits et Enums ont chacun leur
> propre item. Cette page ne les réexplique pas.

## Visibilité

`public`, `protected` (classe et descendants), `private` (classe déclarante
seule). Depuis PHP 8.4, la lecture et l'écriture peuvent différer :

```php
public private(set) string $title;   // lisible partout, écrivable par la classe
```

`private` est lié à la **classe déclarante**, pas à l'instance : deux instances
de la même classe accèdent mutuellement à leurs membres privés.

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

La promotion déclare la propriété, la type et l'affecte en une seule écriture.

## `readonly`

Une propriété `readonly` est initialisable **une seule fois, depuis la portée
de déclaration**. Depuis 8.2, la classe entière peut être `readonly`, ce qui
rend toutes ses propriétés `readonly`.

```php
$money = new Money(100, 'EUR');
// $money->amount = 200;  → Error: Cannot modify readonly property
```

Attention : `readonly` sur un objet interdit la **réaffectation**, pas la
mutation de l'objet référencé.

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

`self` se résout **à la classe où le code est écrit**. `static` se résout à la
classe **réellement appelée** : c'est la liaison statique tardive.

`parent::` cible la classe parente ; `$this::` équivaut à `static::`.

## Pièges d'examen

**`self` ne suit pas l'héritage.** Une factory écrite avec `new self()` renvoie
toujours la classe parente, même appelée depuis l'enfant.

**`private` bloque la surcharge.** Une méthode `private` redéfinie dans une
fille est une méthode différente, pas une surcharge — d'où `#[\Override]` en 8.3
pour vérifier une intention de surcharge.

**Une propriété `readonly` typée n'a pas de valeur par défaut** : elle ne peut
pas en avoir, ou elle serait déjà initialisée.

## Points clés

- `private` est lié à la classe déclarante, pas à l'instance.
- `self` = classe de déclaration ; `static` = classe appelée.
- `readonly` : une écriture, depuis la portée déclarante ; l'objet reste mutable.
- La promotion de constructeur déclare, type et affecte en une fois.

## Sources officielles

- Manuel PHP — *Visibility*, *Late Static Bindings*, *Readonly properties*
