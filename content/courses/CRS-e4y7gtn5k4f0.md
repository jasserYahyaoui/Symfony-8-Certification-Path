---
id: CRS-e4y7gtn5k4f0
official_item: OIT-vt0p9cacpkpd
title: "Interfaces"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-09"
official_sources:
  - url: "https://raw.githubusercontent.com/php/doc-en/master/language/oop5/interfaces.xml"
    readable_url: "https://github.com/php/doc-en/blob/master/language/oop5/interfaces.xml"
    symbol_or_lines: '"All methods declared in an interface must be public"; Constants — "It''s possible for interfaces to have constants. Interface constants work exactly like class constants"; Properties — "The interface declaration applies only to public read and write access" and "an interface property that is settable may not be readonly"'
    repository: "php/doc-en"
    branch: "master"
    verified_at: "2026-10-09"
  - url: "https://raw.githubusercontent.com/php/doc-en/master/language/oop5/properties.xml"
    readable_url: "https://github.com/php/doc-en/blob/master/language/oop5/properties.xml"
    repository: "php/doc-en"
    branch: "master"
    symbol_or_lines: 'Readonly properties — "As of PHP 8.4.0, readonly properties are implicitly protected(set), so may be set from child classes"'
    verified_at: "2026-10-09"
outcome_sections:
  OUT-6tzwvmfwj408: "Ce qu'une interface contient"
  OUT-5vqwe44nsee5: "Ce qu'une interface contient"
  OUT-7vt306wrfkb7: "Les constants sont surchargeables — depuis PHP 8.1"
  OUT-ynfsf3qdn2mj: "Les properties d'interface — depuis PHP 8.4"
---

## Objectif

Reconnaître ce qu'une interface peut et ne peut pas déclarer.

## Ce qu'une interface contient

```php
interface Cacheable
{
    const int DEFAULT_TTL = 3600;   // constantes autorisées

    public function getCacheKey(): string;
    public function getTtl(): int;
}

final class Article implements Cacheable, Stringable
{
    public function getCacheKey(): string { return 'article'; }
    public function getTtl(): int { return self::DEFAULT_TTL; }
    public function __toString(): string { return $this->getCacheKey(); }
}
```

- Toutes les methods sont **implicitement `public` et abstraites**.
- Une class peut implémenter **plusieurs** interfaces.
- Une interface peut **étendre plusieurs** interfaces (`extends A, B`).
- Les constants sont autorisées.

## Ce qu'elle ne contient pas

Aucun corps de method, aucune method `private` ou `protected`, aucune
property **porteuse d'état**.

## Les constants sont surchargeables — depuis PHP 8.1

C'est le point que l'habitude fait rater. Avant PHP 8.1, une constant
d'interface ne pouvait pas être redéfinie par la class qui l'implémente.
Depuis 8.1, elle le peut — et Symfony 8.0 exige PHP 8.4 :

```php
interface Cacheable
{
    const int DEFAULT_TTL = 3600;
    final const int VERSION = 2;   // celle-ci, non
}

class Article implements Cacheable
{
    const int DEFAULT_TTL = 60;    // autorisé depuis 8.1
    // const int VERSION = 3;      // erreur fatale : la constante est final
}
```

C'est `final` sur la constant de l'interface qui interdit la surcharge, pas
l'interface elle-même.

## Les properties d'interface — depuis PHP 8.4

Une interface peut déclarer une property, et doit dire si elle est **lisible,
écrivable, ou les deux**. La déclaration ne porte que sur l'accès public :

```php
interface Named
{
    public string $name { get; }        // lisible
    public string $slug { get; set; }   // lisible et écrivable
}
```

Plusieurs membres de class satisfont une telle déclaration : une property
publique ordinaire, ou une property virtuelle qui implémente le hook
correspondant.

**Une property `readonly` ne peut pas satisfaire une property d'interface
`set`** : la déclaration d'interface porte sur l'écriture **publique**, et une
property `readonly` est implicitement `protected(set)` depuis PHP 8.4. Pour la
même raison, `public private(set)` ne la satisfait pas non plus. Exécuté :
« Set access level of C::$name must be omitted (as in class I) » dans les deux
cas. `readonly` convient en revanche pour un `get` seul.

## Pièges d'examen

**La règle d'avant 8.1 est la Response instinctive.** « Une constant d'interface
ne se surcharge pas » a été vrai pendant des années ; sur PHP 8.4 c'est faux,
sauf `final`.

**Déclarer une property n'est pas porter un état.** L'interface impose un
contrat d'accès ; c'est la class qui décide s'il y a une valeur stockée
derrière.

**`readonly` ne satisfait pas un `set`.** Le réflexe « readonly est une
property publique, donc ça passe » échoue précisément sur la moitié écriture.

## Points clés

- Methods implicitement publiques et abstraites, pas de corps.
- Inheritance multiple d'interfaces autorisé ; pas de property d'état.
- Constantes autorisées **et surchargeables depuis 8.1**, sauf `final`.
- Depuis 8.4, une interface déclare des properties en `get`, `set` ou les deux ;
  une property `readonly` ne satisfait pas un `set`.

La comparaison avec les abstract classes est traitée sous l'item
*Abstract classes*, qui en est le propriétaire.

## Sources officielles

- Manuel PHP — *Object Interfaces*, sections *Constants* et *Properties*
- `php-src` branche `PHP-8.1`, file `UPGRADING` — modificateur `final` sur
  les constants de class (RFC `final_class_const`)
