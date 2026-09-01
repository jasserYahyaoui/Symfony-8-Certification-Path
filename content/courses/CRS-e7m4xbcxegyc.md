---
id: CRS-e7m4xbcxegyc
official_item: OIT-fhb3vz7xbt5h
title: "Anonymous functions and closures"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/php/doc-en/master/language/functions.xml"
    repository: "php/doc-en"
    branch: "master"
    symbol_or_lines: "Anonymous functions, Static anonymous functions, Arrow functions"
    verified_at: "2026-09-01"
---

## Objectif

Choisir entre `function () use (...)` et `fn () =>`, et savoir ce que chacune
capture.

## Fonction anonyme classique

La capture est **explicite** : rien n'entre dans la portée sans `use`.

```php
$factor = 3;

$triple = function (int $n) use ($factor): int {
    return $n * $factor;      // $factor copié à la DÉFINITION
};

$factor = 10;
$triple(5);                   // 15, pas 50
```

Avec `use (&$factor)`, la variable est capturée par référence et la valeur
finale serait utilisée.

## Fonction fléchée

La capture est **automatique et par valeur**. Il n'y a pas de `use`, et la
capture par référence est impossible.

```php
$factor = 3;
$triple = fn (int $n): int => $n * $factor;   // $factor capturé automatiquement
```

Une fonction fléchée n'a **qu'une seule expression**, dont la valeur est
renvoyée : pas d'accolades, pas de `return`, pas d'instructions multiples.

## Liaison de `$this`

Une closure définie dans une classe est **automatiquement liée** à l'instance,
donc `$this` y est disponible. Le mot-clé `static` empêche cette liaison :

```php
class Cart
{
    public function total(): callable
    {
        return fn () => $this->items;          // $this disponible
    }

    public function pure(): callable
    {
        return static fn () => 42;             // $this indisponible
    }
}
```

`Closure::bind()` et `Closure::bindTo()` permettent de relier une closure à un
autre objet — et échouent sur une closure `static`.

## Callable de première classe

```php
$fn = strlen(...);              // Closure vers la fonction
$fn = $this->render(...);       // Closure vers la méthode, $this lié
$fn = Article::create(...);     // Closure vers la méthode statique
```

## Pièges d'examen

**`use` capture à la définition, pas à l'appel.** Modifier la variable après
coup ne change rien, sauf capture par référence.

**Une fonction fléchée ne peut pas capturer par référence.** Il n'y a pas de
syntaxe `fn () use (&$x)`.

**`static function` ≠ méthode statique.** Sur une closure, `static` signifie
« non liée à `$this` ».

**Une closure est un objet.** `$closure instanceof \Closure` est vrai, et le
type `callable` accepte aussi des chaînes et des tableaux `[$obj, 'method']`.

## Points clés

- `function () use ($x)` : capture explicite, par valeur sauf `&$x`.
- `fn () => expr` : capture automatique par valeur, une seule expression.
- `$this` lié automatiquement dans une classe, sauf closure `static`.
- `foo(...)` produit une `Closure`.

## Sources officielles

- Manuel PHP — *Anonymous functions*, *Arrow functions*
