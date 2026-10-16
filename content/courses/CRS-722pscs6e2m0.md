---
id: CRS-722pscs6e2m0
official_item: OIT-46ry8d7dypmb
title: "PHP API up to PHP 8.4 version"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-09"
official_sources:
  - url: "https://raw.githubusercontent.com/php/php-src/PHP-8.4/UPGRADING"
    readable_url: "https://github.com/php/php-src/blob/PHP-8.4/UPGRADING"
    repository: "php/php-src"
    branch: "PHP-8.4"
    symbol_or_lines: "New Features > Core"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/php/doc-en/master/language/oop5/visibility.xml"
    readable_url: "https://github.com/php/doc-en/blob/master/language/oop5/visibility.xml"
    symbol_or_lines: 'Visibility — "Class members declared public can be accessed everywhere. Members declared protected can be accessed ... Members declared as private may only be accessed by the class that defines the member"'
    repository: "php/doc-en"
    branch: "master"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/php/doc-en/master/language/oop5/property-hooks.xml"
    readable_url: "https://github.com/php/doc-en/blob/master/language/oop5/property-hooks.xml"
    repository: "php/doc-en"
    branch: "master"
    symbol_or_lines: 'Property hooks — "The value the expression evaluates to will be set on the backing value"; Virtual properties'
    verified_at: "2026-10-09"
  - url: "https://raw.githubusercontent.com/php/doc-en/master/language/oop5/properties.xml"
    readable_url: "https://github.com/php/doc-en/blob/master/language/oop5/properties.xml"
    repository: "php/doc-en"
    branch: "master"
    symbol_or_lines: 'Readonly properties — "As of PHP 8.4.0, readonly properties are implicitly protected(set), so may be set from child classes"'
    verified_at: "2026-10-09"
  - url: "https://raw.githubusercontent.com/php/doc-en/master/reference/array/functions/array-merge.xml"
    readable_url: "https://github.com/php/doc-en/blob/master/reference/array/functions/array-merge.xml"
    repository: "php/doc-en"
    branch: "master"
    symbol_or_lines: 'array_merge — "Values in the input arrays with numeric keys will be renumbered with incrementing keys starting from zero in the result array"'
    verified_at: "2026-10-16"
  - url: "https://raw.githubusercontent.com/php/doc-en/master/language/operators/array.xml"
    readable_url: "https://github.com/php/doc-en/blob/master/language/operators/array.xml"
    repository: "php/doc-en"
    branch: "master"
    symbol_or_lines: 'Array operators — "for keys that exist in both arrays, the elements from the left-hand array will be used"'
    verified_at: "2026-10-16"
  - url: "https://raw.githubusercontent.com/php/doc-en/master/reference/array/functions/array-filter.xml"
    readable_url: "https://github.com/php/doc-en/blob/master/reference/array/functions/array-filter.xml"
    repository: "php/doc-en"
    branch: "master"
    symbol_or_lines: 'array_filter — "Array keys are preserved, and may result in gaps if the array was indexed"'
    verified_at: "2026-10-16"
  - url: "https://raw.githubusercontent.com/php/doc-en/master/reference/array/functions/array-map.xml"
    readable_url: "https://github.com/php/doc-en/blob/master/reference/array/functions/array-map.xml"
    repository: "php/doc-en"
    branch: "master"
    symbol_or_lines: 'array_map — "The returned array will preserve the keys of the array argument if and only if exactly one array is passed"'
    verified_at: "2026-10-16"
  - url: "https://raw.githubusercontent.com/php/doc-en/master/language/operators/comparison.xml"
    readable_url: "https://github.com/php/doc-en/blob/master/language/operators/comparison.xml"
    repository: "php/doc-en"
    branch: "master"
    symbol_or_lines: 'Comparison operators — "Prior to PHP 8.0.0, if a string is compared to a number or a numeric string then the string was converted to a number"'
    verified_at: "2026-10-16"
  - url: "https://raw.githubusercontent.com/php/doc-en/master/reference/json/functions/json-decode.xml"
    readable_url: "https://github.com/php/doc-en/blob/master/reference/json/functions/json-decode.xml"
    repository: "php/doc-en"
    branch: "master"
    symbol_or_lines: 'json_decode — "null is returned if the json cannot be decoded"; associative'
    verified_at: "2026-10-16"
  - url: "https://raw.githubusercontent.com/php/doc-en/master/reference/array/functions/array-find.xml"
    readable_url: "https://github.com/php/doc-en/blob/master/reference/array/functions/array-find.xml"
    repository: "php/doc-en"
    branch: "master"
    symbol_or_lines: 'array_find — "If no matching element is found the function returns null"'
    verified_at: "2026-10-16"
  - url: "https://raw.githubusercontent.com/php/doc-en/master/reference/array/versions.xml"
    readable_url: "https://github.com/php/doc-en/blob/master/reference/array/versions.xml"
    repository: "php/doc-en"
    branch: "master"
    symbol_or_lines: 'array_is_list from "PHP 8 >= 8.1.0"; array_find, array_find_key, array_any, array_all from "PHP 8 >= 8.4.0"'
    verified_at: "2026-10-16"
outcome_sections:
  OUT-svjaxs75amyd: "Rappel des versions antérieures"
  OUT-ayahqn48ev7g: "Ce que 8.4 ajoute au cœur du langage"
  OUT-fr1x4bcak73j: "Rappel des versions antérieures"
  OUT-e7z5wvfwcsxk: "Arrays, comparaisons et JSON : ce que renvoie PHP"
  OUT-a1r0b9k9vxsn: "Functions ajoutées, par version"
---

## Objectif

Situer une fonctionnalité du langage dans la bonne version de PHP, et
reconnaître ce que 8.4 apporte — Symfony 8.0 exigeant PHP 8.4 au minimum.

## Pourquoi la version compte ici

Symfony 8.0 déclare `"php": ">=8.4"`. Une question peut donc légitimement
porter sur une syntaxe 8.4, et une Response qui « marcherait en 8.1 » n'est pas
pour autant correcte.

## Ce que 8.4 ajoute au cœur du langage

**Property hooks** — un accessor défini sur la property elle-même, sans
getter ni setter séparés :

```php
class Person
{
    public string $first = '';
    public string $last = '';

    public string $fullName {
        get => $this->first.' '.$this->last;
        set { [$this->first, $this->last] = explode(' ', $value, 2); }
    }
}
```

Aucun des deux hooks ne lit ni n'écrit `$this->fullName` : la property est
**virtuelle**, rien n'est stocké. Attention à la forme courte `set => expr` :
selon la documentation PHP, la valeur de l'expression est écrite **dans la
property elle-même**. Exécuté en PHP 8.4 avec `set => $this->first = …` :
`fullName` devient une property adossée, qui stocke le prénom.

**Visibilité asymétrique** — lecture et écriture peuvent avoir des scopes
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

**Objects lazy** (`lazy objects`), initialisés au premier accès réel.

**`#[\Deprecated]`** — un attribute natif pour marquer une function, method ou
constante obsolète.

**`new` déréférençable** — `new Foo()->method()` est désormais valide sans
parenthèses englobantes.

## Rappel des versions antérieures

| Version | Apports les plus examinables |
|---|---|
| 8.0 | Named arguments, promotion de constructor, `match`, types union, `?->`, `static` en return type |
| 8.1 | Enums, `readonly`, `never`, syntaxe de callable de première class `foo(...)`, constantes de class `final` |
| 8.2 | `readonly` sur la class entière, types DNF, properties dynamiques dépréciées |
| 8.3 | Constantes de class typées, `#[\Override]`, `json_validate()` |
| 8.4 | Property hooks, visibilité asymétrique, objects lazy, `#[\Deprecated]`, `new` déréférençable |

## Arrays, comparaisons et JSON : ce que renvoie PHP

Chaque ligne a été exécutée en PHP 8.4.

| Expression | Résultat |
|---|---|
| `array_merge(['a'], ['b'])` | `['a', 'b']` — keys numériques renumérotées |
| `['a'] + ['b']` | `['a']` — la key `0` existe déjà à gauche |
| `array_merge(['k' => 1], ['k' => 2])` | `['k' => 2]` — la dernière string key gagne |
| `['k' => 1] + ['k' => 2]` | `['k' => 1]` — `+` garde la gauche |
| `array_filter([1, 0, 2, null, '0', 'a'])` | `[0 => 1, 2 => 2, 5 => 'a']` — keys conservées, plus une list |
| `array_map($f, ['k' => 1])` | key `k` conservée |
| `array_map($f, $a, $b)` | keys réindexées dès deux arrays |
| `sort($list)` | `true` ; le tri se fait en place |

**PHP 8 compare un nombre et une string non numérique comme deux strings.**
`0 == 'a'` vaut `false` (il valait `true` avant 8.0), de même que `0 == ''`.
Deux strings numériques restent comparées comme des nombres : `'1' == '01'` et
`'10' == '1e1'` valent `true`. `in_array()` compare avec `==` sauf si son
troisième argument vaut `true`.

**`json_decode()`** renvoie un `stdClass` pour un object JSON, un array
associatif seulement avec `true` en second argument, et `null` sur un JSON
invalide — sauf avec `JSON_THROW_ON_ERROR`, qui lève une `JsonException`.

## Functions ajoutées, par version

| Version | Functions |
|---|---|
| 8.0 | `str_contains()`, `str_starts_with()`, `str_ends_with()` |
| 8.1 | `array_is_list()` |
| 8.3 | `json_validate()` |
| 8.4 | `array_find()`, `array_find_key()`, `array_any()`, `array_all()`, `mb_trim()`, `mb_ucfirst()` |

`array_find()` renvoie la première **valeur** acceptée, ou `null` ;
`array_find_key()` renvoie sa key. Le callback reçoit la valeur puis la key.
Sur un array vide, `array_all()` vaut `true` et `array_any()` `false`.

## Pièges d'examen

**`readonly` n'est pas `private(set)`.** Une property `readonly` ne peut être
écrite **qu'une fois** ; depuis PHP 8.4, elle est implicitement
`protected(set)`, donc une class enfant peut l'initialiser — exécuté.
`private(set)` autorise autant d'écritures que voulu, mais seulement depuis la
class.

**Les property hooks ne sont pas des properties calculées mises en cache.** Le
hook `get` s'exécute à chaque lecture.

**`#[\Override]` est 8.3, pas 8.4.** Il vérifie qu'une method surcharge bien
quelque chose ; sinon, erreur de compilation.

## Points clés

- Symfony 8.0 impose PHP 8.4 : la syntaxe 8.4 est dans le périmètre.
- 8.4 = property hooks, visibilité asymétrique, objects lazy, `#[\Deprecated]`.
- `readonly` (une seule écriture) ≠ `private(set)` (écritures limitées à la class).
- `array_merge` renumérote et laisse gagner la droite ; `+` garde la gauche.
- `array_filter` conserve les keys ; `0 == 'a'` vaut `false` depuis PHP 8.

## Sources officielles

- `php-src` branche `PHP-8.4`, file `UPGRADING`, section *New Features > Core*
- Manuel PHP — *Visibility*, *Property hooks*
- Manuel PHP — *array_merge*, *Array operators*, *array_filter*, *array_map*, *Comparison operators*, *json_decode*, *array_find*
