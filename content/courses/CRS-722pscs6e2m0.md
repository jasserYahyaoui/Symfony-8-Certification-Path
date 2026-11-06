---
id: CRS-722pscs6e2m0
official_item: OIT-46ry8d7dypmb
title: "PHP API up to PHP 8.4 version"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-09"
format: pedagogical
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

## L'essentiel

Symfony 8.0 exige PHP 8.4 : une question peut porter sur une syntaxe 8.4. Il
faut savoir **dater** une fonctionnalité — 8.0 à 8.4 — et connaître les
résultats que PHP renvoie réellement sur les arrays, les comparaisons et le
JSON. Deux confusions reviennent : `readonly` contre `private(set)`, et
`array_merge()` contre `+`.

## Le schéma

```text
8.0  named arguments · promotion · match · union types · ?-> · static en return type · str_contains()
8.1  enums · readonly · never · foo(...) · final const · array_is_list()
8.2  readonly class · DNF types · properties dynamiques dépréciées
8.3  constantes de class typées · #[\Override] · json_validate()
8.4  property hooks · private(set) · lazy objects · #[\Deprecated] · new Foo()->m()
     array_find() · array_find_key() · array_any() · array_all() · mb_trim() · mb_ucfirst()
```

## L'exemple : un property hook

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

## Ce que 8.4 ajoute au cœur du langage

**Property hooks.** Un accessor défini sur la property elle-même. Ci-dessus,
aucun hook ne lit ni n'écrit `$this->fullName` : la property est **virtuelle**.
Avec la forme courte `set => expr`, la valeur de l'expression est écrite dans
la property : exécuté avec `set => $this->first = …`, `fullName` devient une property adossée, qui stocke le prénom. Le hook `get` s'exécute à chaque lecture.

**Visibilité asymétrique.** `public private(set) string $title` : lisible
partout, écrivable par la class ; `protected(set)` ouvre l'écriture à la
hiérarchie.

**Et aussi** : les objects lazy, initialisés au premier accès ; l'attribute
natif `#[\Deprecated]` ; `new Foo()->method()` sans parenthèses englobantes,
une erreur de syntaxe en 8.3.

## Rappel des versions antérieures

| Version | Apports les plus examinables |
|---|---|
| 8.0 | Named arguments, promotion de constructor, `match`, types union, `?->`, `static` en return type |
| 8.1 | Enums, `readonly`, `never`, callable de première class `foo(...)`, constantes de class `final` |
| 8.2 | `readonly` sur la class entière, types DNF, properties dynamiques dépréciées |
| 8.3 | Constantes de class typées, `#[\Override]`, `json_validate()` |

## Arrays, comparaisons et JSON : ce que renvoie PHP

| Expression | Résultat |
|---|---|
| `array_merge(['a'], ['b'])` | `['a', 'b']` — keys numériques renumérotées |
| `['a'] + ['b']` | `['a']` — la key `0` existe déjà à gauche |
| `array_merge(['k' => 1], ['k' => 2])` | `['k' => 2]` — la dernière string key gagne |
| `['k' => 1] + ['k' => 2]` | `['k' => 1]` — `+` garde la gauche |
| `array_filter([1, 0, 2, null, '0', 'a'])` | `[0 => 1, 2 => 2, 5 => 'a']` — keys conservées |
| `array_map($f, ['k' => 1])` | key `k` conservée ; réindexée dès deux arrays |

**PHP 8 compare un nombre et une string non numérique comme deux strings** :
`0 == 'a'` et `0 == ''` valent `false`. Deux strings numériques restent
comparées comme des nombres : `'1' == '01'` vaut `true`. `in_array()` compare
avec `==` sauf si son troisième argument vaut `true`. **`json_decode()`** rend
un `stdClass` pour un object JSON, un array avec `true` en second argument, et
`null` sur un JSON invalide — sauf avec `JSON_THROW_ON_ERROR`.

## Functions ajoutées, par version

| Version | Functions |
|---|---|
| 8.0 | `str_contains()`, `str_starts_with()`, `str_ends_with()` |
| 8.1 | `array_is_list()` |
| 8.3 | `json_validate()` |
| 8.4 | `array_find()`, `array_find_key()`, `array_any()`, `array_all()`, `mb_trim()`, `mb_ucfirst()` |

`array_find()` rend la première **valeur** acceptée, ou `null` ;
`array_find_key()` rend sa key. Le callback reçoit la valeur puis la key.

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

## Application

- Trouver le premier élément qui satisfait un test → `array_find()`, en 8.4.
- Fusionner des options par défaut sans écraser celles de l'appelant →
  `$options + $defaults`.

## QCM d'entraînement

**Question 1.** Que valent `array_all([], $f)` puis `array_any([], $f)` ?

- **A.** `true` et `false`
- **B.** `false` et `false`
- **C.** `false` et `true`
- **D.** `true` et `true`

<details>
<summary>Correction</summary>

**Bonne réponse : A.** Sur un array vide, aucune valeur ne contredit le test,
donc `array_all()` vaut `true`, et aucune ne le satisfait, donc `array_any()` vaut `false`.

</details>

**Question 2.** Que rend `array_map(fn ($x) => $x * 2, ['k' => 1])` ?

- **A.** `[0 => 2]`
- **B.** `['k' => 2]`
- **C.** `['k' => 1]`, inchangé
- **D.** `[2]`

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Avec un seul array, les keys sont conservées ; c'est à
partir de deux arrays que le résultat est réindexé.

</details>

**Question 3.** Sans option, que rend `json_decode('{bad')` ?

- **A.** `false`
- **B.** Une `JsonException`
- **C.** `null`
- **D.** Un `stdClass` vide

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Un JSON invalide donne `null` en silence ; il faut le
drapeau `JSON_THROW_ON_ERROR` pour obtenir une exception.

</details>

**Question 4.** Depuis quelle version `new Foo()->bar()` est-il valide sans
parenthèses autour de `new Foo()` ?

- **A.** PHP 8.0
- **B.** PHP 8.2
- **C.** Jamais : il faut `(new Foo())`
- **D.** PHP 8.4

<details>
<summary>Correction</summary>

**Bonne réponse : D.** PHP 8.3 refuse encore cette écriture par une erreur de
syntaxe ; c'est PHP 8.4 qui l'accepte, le minimum de Symfony 8.0.

</details>

## Fiche express

**Si tu dois retenir seulement 7 choses :**

- Symfony 8.0 → **PHP 8.4** : sa syntaxe est au programme.
- 8.4 : **hooks**, **`private(set)`**, lazy objects, **`#[\Deprecated]`**, `new X()->m()`.
- `#[\Override]` et `json_validate()` : **8.3** ; enums et `readonly` : **8.1**.
- `readonly` : **une** écriture ; `private(set)` : écritures illimitées, dans la class.
- `array_merge()` : la **droite** gagne ; `+` : la **gauche**.
- `array_filter()` garde les keys ; `0 == 'a'` vaut **`false`**.
- `array_find()` rend une valeur ; `array_find_key()` une key.

Moyen mnémotechnique : **« merge écoute la droite, plus garde la gauche »**.

## Sources officielles

- `php-src` branche `PHP-8.4`, file `UPGRADING`, section *New Features > Core*
- Manuel PHP — *Visibility*, *Property hooks*
- Manuel PHP — *array_merge*, *Array operators*, *array_filter*, *array_map*, *Comparison operators*, *json_decode*, *array_find*

