---
id: CRS-0jtjh77tabt1
official_item: OIT-webdvbgbrfth
title: "Abstract classes"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/php/doc-en/master/language/oop5/abstract.xml"
    repository: "php/doc-en"
    branch: "master"
    verified_at: "2026-09-01"
---

## Objectif

Écrire une classe abstraite correcte et savoir quand la préférer à une
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

- Une classe abstraite **ne peut pas être instanciée**.
- Une méthode abstraite n'a **pas de corps** et peut être `public` ou
  `protected`, jamais `private`.
- Une classe contenant au moins une méthode abstraite **doit** être déclarée
  `abstract`.
- La classe fille doit implémenter toutes les méthodes abstraites, avec une
  visibilité **égale ou plus permissive** et une signature compatible.
- Une classe abstraite peut avoir un constructeur, des propriétés et des
  méthodes concrètes.

## Interface ou classe abstraite ?

| | Interface | Classe abstraite |
|---|---|---|
| Corps de méthode | Non | Oui |
| Propriétés d'état | Non | Oui |
| Constructeur | Non | Oui |
| Visibilité | Publique uniquement | `public` ou `protected` |
| Nombre par classe | Plusieurs | **Une seule** |

La règle pratique : une interface décrit **ce qu'un objet sait faire** ; une
classe abstraite fournit **du comportement partagé**. Un besoin de constructeur
ou d'état commun tranche pour la classe abstraite.

## Pièges d'examen

**`abstract private` est une erreur fatale.** Une méthode abstraite privée
serait invisible depuis la classe fille, donc impossible à implémenter.

**Une seule classe parente.** Besoin de deux jeux de comportements partagés :
c'est un trait ou une composition, pas un second `extends`.

**`abstract` et `final` sont incompatibles** : l'un exige une extension,
l'autre l'interdit.

## Points clés

- Non instanciable ; méthode abstraite sans corps, jamais `private`.
- Une seule classe abstraite héritée, plusieurs interfaces implémentées.
- Interface = contrat ; classe abstraite = comportement partagé.

## Sources officielles

- Manuel PHP — *Class Abstraction*
