---
id: CRS-0jtjh77tabt1
official_item: OIT-webdvbgbrfth
title: "Abstract classes"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-09"
official_sources:
  - url: "https://raw.githubusercontent.com/php/doc-en/master/language/oop5/abstract.xml"
    readable_url: "https://github.com/php/doc-en/blob/master/language/oop5/abstract.xml"
    symbol_or_lines: '"Classes defined as abstract cannot be instantiated, and any class that contains at least one abstract method or property must also be abstract"; abstract methods "declare the method''s signature and whether it is public or protected"'
    repository: "php/doc-en"
    branch: "master"
    verified_at: "2026-09-01"
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
- Une class contenant au moins une method abstraite **doit** être déclarée
  `abstract`.
- La class fille doit implémenter toutes les methods abstraites, sauf à être
  elle-même abstraite, avec une visibilité **égale ou plus permissive** et une signature compatible.
- Une abstract class peut avoir un constructeur, des properties et des
  methods concrètes.

## Interface ou abstract class ?

| | Interface | Abstract class |
|---|---|---|
| Corps de method | Non | Oui |
| Properties d'état | Non | Oui |
| Constructeur | Signature seulement, déconseillée | Oui |
| Visibilité | Publique uniquement | `public` ou `protected` |
| Nombre par class | Plusieurs | **Une seule** |

La règle pratique : une interface décrit **ce qu'un object sait faire** ; une
abstract class fournit **du comportement partagé**. Un besoin de constructeur
ou d'état commun tranche pour l'abstract class.

## Pièges d'examen

**`abstract private` est une erreur fatale.** Une method abstraite privée
serait invisible depuis la class fille, donc impossible à implémenter.

**Une seule class parente.** Besoin de deux jeux de comportements partagés :
c'est un trait ou une composition, pas un second `extends`.

**`abstract` et `final` sont incompatibles** : l'un exige une extension,
l'autre l'interdit.

## Points clés

- Non instanciable ; method abstraite sans corps, jamais `private`.
- Une seule abstract class héritée, plusieurs interfaces implémentées.
- Interface = contrat ; abstract class = comportement partagé.

## Sources officielles

- Manuel PHP — *Class Abstraction*
