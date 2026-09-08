---
id: CRS-e4y7gtn5k4f0
official_item: OIT-vt0p9cacpkpd
title: "Interfaces"
content_level: MINIMAL
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/php/doc-en/master/language/oop5/interfaces.xml"
    symbol_or_lines: '"All methods declared in an interface must be public"; Constants — "It''s possible for interfaces to have constants. Interface constants work exactly like class constants"'
    repository: "php/doc-en"
    branch: "master"
    verified_at: "2026-09-01"
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

- Toutes les méthodes sont **implicitement `public` et abstraites**.
- Une classe peut implémenter **plusieurs** interfaces.
- Une interface peut **étendre plusieurs** interfaces (`extends A, B`).
- Les constantes sont autorisées.

## Ce qu'elle ne contient pas

Aucun corps de méthode, aucune méthode `private` ou `protected`, aucune
propriété **porteuse d'état**.

## Les constantes sont surchargeables — depuis PHP 8.1

C'est le point que l'habitude fait rater. Avant PHP 8.1, une constante
d'interface ne pouvait pas être redéfinie par la classe qui l'implémente.
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

C'est `final` sur la constante de l'interface qui interdit la surcharge, pas
l'interface elle-même.

## Les propriétés d'interface — depuis PHP 8.4

Une interface peut déclarer une propriété, et doit dire si elle est **lisible,
écrivable, ou les deux**. La déclaration ne porte que sur l'accès public :

```php
interface Named
{
    public string $name { get; }        // lisible
    public string $slug { get; set; }   // lisible et écrivable
}
```

Plusieurs membres de classe satisfont une telle déclaration : une propriété
publique ordinaire, ou une propriété virtuelle qui implémente le hook
correspondant.

**Une propriété `readonly` ne peut pas satisfaire une propriété d'interface
`set`** : elle ne s'écrit qu'une fois, depuis sa portée de déclaration. Elle
convient en revanche pour un `get` seul.

## Pièges d'examen

**La règle d'avant 8.1 est la réponse instinctive.** « Une constante d'interface
ne se surcharge pas » a été vrai pendant des années ; sur PHP 8.4 c'est faux,
sauf `final`.

**Déclarer une propriété n'est pas porter un état.** L'interface impose un
contrat d'accès ; c'est la classe qui décide s'il y a une valeur stockée
derrière.

**`readonly` ne satisfait pas un `set`.** Le réflexe « readonly est une
propriété publique, donc ça passe » échoue précisément sur la moitié écriture.

## Points clés

- Méthodes implicitement publiques et abstraites, pas de corps.
- Héritage multiple d'interfaces autorisé ; pas de propriété d'état.
- Constantes autorisées **et surchargeables depuis 8.1**, sauf `final`.
- Depuis 8.4, une interface déclare des propriétés en `get`, `set` ou les deux ;
  une propriété `readonly` ne satisfait pas un `set`.

La comparaison avec les classes abstraites est traitée sous l'item
*Abstract classes*, qui en est le propriétaire.

## Sources officielles

- Manuel PHP — *Object Interfaces*, sections *Constants* et *Properties*
- `php-src` branche `PHP-8.1`, fichier `UPGRADING` — modificateur `final` sur
  les constantes de classe (RFC `final_class_const`)
