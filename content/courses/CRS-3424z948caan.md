---
id: CRS-3424z948caan
official_item: OIT-ywrx9x3hg9z8
title: "Object Oriented Programming"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-26"
format: pedagogical
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
  OUT-2tvh0svv8jhg: "Promotion de constructor"
---

## Objectif

Maîtriser les mécanismes object que les autres items du syllabus ne couvrent
pas : visibilité, résolution `self`/`static`, immuabilité, promotion.

> Attributes, Interfaces, Abstract classes, Traits et Enums ont chacun leur
> propre item. Cette page ne les réexplique pas.

## L'essentiel

Quatre mécanismes object reviennent à l'examen. La **visibilité** est liée à la
class, pas à l'instance, et PHP 8.4 sépare lecture et écriture
(`private(set)`). La **promotion** déclare et affecte en une ligne. `readonly`
interdit la réaffectation, pas la mutation. Et surtout : `self` est figé à la
class où le code est écrit, `static` suit la class appelée.

## Le schéma

```text
visibilité   public · protected (class + descendants) · private (class déclarante, toutes instances)
PHP 8.4      public private(set) string $title  → lecture partout, écriture dans la class
promotion    __construct(public readonly int $amount)  → property déclarée, typée, affectée
readonly     une seule écriture ; 8.4 : protected(set) implicite → une fille peut l'initialiser
             pas de default value · object référencé toujours mutable · readonly class (8.2)
self         class où le code est écrit         new self()   → Parent
static       class réellement appelée (LSB)      new static() → Child    $this:: ≡ static::
```

## L'exemple

```php
class ParentClass
{
    public static function createSelf(): self     { return new self(); }
    public static function createStatic(): static { return new static(); }
}

final class ChildClass extends ParentClass {}

ChildClass::createSelf();     // ParentClass — self est figé à la déclaration
ChildClass::createStatic();   // ChildClass  — static suit l'appelant
```

## Visibilité

`public`, `protected` (class et descendants), `private` (class déclarante
seule). `private` est lié à la **class**, pas à l'instance : une instance lit
les membres privés d'une autre instance de la même class. Depuis PHP 8.4, la
lecture et l'écriture peuvent différer :

```php
public private(set) string $title;   // lisible partout, écrivable par la classe
```

## Promotion de constructor

```php
final class Money
{
    public function __construct(
        public readonly int $amount,
        public readonly string $currency,
    ) {}
}
```

La promotion déclare la property, la type et l'affecte en une seule écriture.

## `readonly`

Une property `readonly` est initialisable **une seule fois**, et ne peut pas
avoir de default value. Depuis PHP 8.4, elle est implicitement `protected(set)` :
une class fille peut donc l'initialiser. Depuis 8.2, une `readonly class` rend
toutes ses properties `readonly`. `readonly` interdit la **réaffectation**, pas
la mutation de l'object référencé.

## `self` contre `static`

`self` se résout **à la class où le code est écrit**. `static` se résout à la
class **réellement appelée** : c'est le *late static binding*. `parent::`
cible la class parente ; `$this::` équivaut à `static::`.

## Pièges d'examen

**`self` ne suit pas l'inheritance.** Une factory écrite avec `new self()` renvoie
toujours la class parente, même appelée depuis l'enfant.

**`private` bloque la surcharge.** Une method `private` redéfinie dans une
fille est une method différente, pas une surcharge — d'où `#[\Override]` en 8.3
pour vérifier une intention de surcharge.

**Une property `readonly` typée n'a pas de default value** : elle ne peut
pas en avoir, ou elle serait déjà initialisée.

## Application

- Une factory héritable → `new static()` et return type `static`.
- Un value object immuable → promotion `public readonly`, ou `readonly class`.

## QCM d'entraînement

**Question 1.** Une method de `Money` lit `$other->secret`, property `private`
d'une **autre** instance de `Money`. Que se passe-t-il ?

- **A.** Une `Error` : membre privé
- **B.** La valeur est lue
- **C.** `null`
- **D.** Un warning, puis `null`

<details>
<summary>Correction</summary>

**Bonne réponse : B.** La visibilité est contrôlée par class, pas par
instance : le code de `Money` accède aux membres privés de tout object `Money`.

</details>

**Question 2.** La property est déclarée `public private(set) string $title`.
Un code extérieur écrit `$post->title = 'x'`. Que se passe-t-il ?

- **A.** L'écriture réussit
- **B.** Un warning
- **C.** Une `Error`
- **D.** L'écriture est ignorée en silence

<details>
<summary>Correction</summary>

**Bonne réponse : C.** La lecture est publique mais l'écriture est réservée à
la class : PHP refuse la modification depuis le scope global.

</details>

**Question 3.** `Bag` promeut `public readonly ArrayObject $items`. Le code
appelle `$bag->items->append(1)`. Que se passe-t-il ?

- **A.** Réussit : l'object référencé reste mutable
- **B.** Une `Error` : property readonly
- **C.** L'appel est ignoré
- **D.** Un clone est créé, modifié, puis jeté sans erreur

<details>
<summary>Correction</summary>

**Bonne réponse : A.** `readonly` empêche de réaffecter `$items`, pas de
modifier l'object qu'elle référence ; seule une réaffectation lèverait.

</details>

**Question 4.** Dans `readonly class Point`, la property promue `public int $x`
est-elle readonly ?

- **A.** Non, seulement celles marquées
- **B.** Seulement si elle n'est pas promue
- **C.** Une erreur de compilation
- **D.** Oui, toutes le deviennent

<details>
<summary>Correction</summary>

**Bonne réponse : D.** Depuis PHP 8.2, le mot-clé posé sur la class rend
chacune de ses properties readonly, promues ou non.

</details>

## Fiche express

**Si tu dois retenir seulement 6 choses :**

- `private` = **class** déclarante, pas instance.
- PHP 8.4 : **`private(set)`** sépare lecture et écriture.
- Promotion : déclare, type et **affecte** en une fois.
- `readonly` : **une** écriture, pas de défaut, object **mutable** ; `protected(set)` implicite en 8.4.
- **`self`** = class écrite ; **`static`** = class appelée.
- `$this::` ≡ `static::` ; `readonly class` (8.2) : tout readonly.

Moyen mnémotechnique : **« self se souvient, static regarde qui appelle »**.

## Sources officielles

- Manuel PHP — *Visibility*, *Late Static Bindings*, *Readonly properties*

