---
id: CRS-722pscs6e2m0
official_item: OIT-46ry8d7dypmb
title: "PHP API up to PHP 8.4 version"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/php/php-src/PHP-8.4/UPGRADING"
    repository: "php/php-src"
    branch: "PHP-8.4"
    symbol_or_lines: "New Features > Core"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/php/doc-en/master/language/oop5/visibility.xml"
    repository: "php/doc-en"
    branch: "master"
    verified_at: "2026-09-01"
---

## Objectif

Situer une fonctionnalité du langage dans la bonne version de PHP, et
reconnaître ce que 8.4 apporte — Symfony 8.0 exigeant PHP 8.4 au minimum.

## Pourquoi la version compte ici

Symfony 8.0 déclare `"php": ">=8.4"`. Une question peut donc légitimement
porter sur une syntaxe 8.4, et une réponse qui « marcherait en 8.1 » n'est pas
pour autant correcte.

## Ce que 8.4 ajoute au cœur du langage

**Property hooks** — un accesseur défini sur la propriété elle-même, sans
getter ni setter séparés :

```php
class Person
{
    public string $fullName {
        get => $this->first.' '.$this->last;
        set => $this->first = explode(' ', $value)[0];
    }
}
```

**Visibilité asymétrique** — lecture et écriture peuvent avoir des portées
différentes :

```php
class Book
{
    public function __construct(
        public private(set) string $title,      // lisible partout, écrivable par la classe
        public protected(set) string $author,   // écrivable par la hiérarchie
    ) {}
}
```

**Objets paresseux** (`lazy objects`), initialisés au premier accès réel.

**`#[\Deprecated]`** — un attribut natif pour marquer une fonction, méthode ou
constante obsolète.

**`new` déréférençable** — `new Foo()->method()` est désormais valide sans
parenthèses englobantes.

## Rappel des versions antérieures

| Version | Apports les plus examinables |
|---|---|
| 8.0 | Arguments nommés, promotion de constructeur, `match`, types union, `?->`, `static` en type de retour |
| 8.1 | Enums, `readonly`, `never`, syntaxe de callable de première classe `foo(...)`, propriétés `final const` |
| 8.2 | `readonly` sur la classe entière, types DNF, propriétés dynamiques dépréciées |
| 8.3 | Constantes de classe typées, `#[\Override]`, `json_validate()` |
| 8.4 | Property hooks, visibilité asymétrique, objets paresseux, `#[\Deprecated]` |

## Pièges d'examen

**`readonly` n'est pas `private(set)`.** Une propriété `readonly` ne peut être
écrite **qu'une fois**, depuis la portée de déclaration. `private(set)` autorise
autant d'écritures que voulu, mais seulement depuis la classe.

**Les property hooks ne sont pas des propriétés calculées mises en cache.** Le
hook `get` s'exécute à chaque lecture.

**`#[\Override]` est 8.3, pas 8.4.** Il vérifie qu'une méthode surcharge bien
quelque chose ; sinon, erreur de compilation.

## Points clés

- Symfony 8.0 impose PHP 8.4 : la syntaxe 8.4 est dans le périmètre.
- 8.4 = property hooks, visibilité asymétrique, objets paresseux, `#[\Deprecated]`.
- `readonly` (une seule écriture) ≠ `private(set)` (écritures limitées à la classe).

## Sources officielles

- `php-src` branche `PHP-8.4`, fichier `UPGRADING`, section *New Features > Core*
- Manuel PHP — *Visibility*, *Property hooks*
