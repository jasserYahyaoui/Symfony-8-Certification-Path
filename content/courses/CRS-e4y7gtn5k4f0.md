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

Aucune propriété, aucun corps de méthode, aucune méthode `private` ou
`protected`.

> Les *property hooks* de PHP 8.4 permettent à une interface de déclarer une
> propriété **abstraite** avec ses hooks, mais toujours pas une propriété
> porteuse d'état.

## Points clés

- Méthodes implicitement publiques et abstraites, pas de corps.
- Héritage multiple d'interfaces autorisé ; pas de propriété d'état.
- Constantes autorisées.

La comparaison avec les classes abstraites est traitée sous l'item
*Abstract classes*, qui en est le propriétaire.

## Sources officielles

- Manuel PHP — *Object Interfaces*
