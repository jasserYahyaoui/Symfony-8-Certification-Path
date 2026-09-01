---
id: CRS-yc9fry0vz4gh
official_item: OIT-bvnvx2b6yt2y
title: "Exception and error handling"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/php/doc-en/master/language/predefined/throwable.xml"
    repository: "php/doc-en"
    branch: "master"
    verified_at: "2026-09-01"
---

## Objectif

Naviguer la hiérarchie `Throwable` et rattraper le bon type — sans rattraper
trop.

## La hiérarchie

```text
Throwable  (interface, étend Stringable)
├── Error                    problèmes du moteur
│   ├── TypeError
│   ├── ValueError
│   ├── ArithmeticError → DivisionByZeroError
│   ├── ArgumentCountError
│   └── AssertionError
└── Exception                conditions applicatives
    ├── RuntimeException  → OutOfBoundsException, UnexpectedValueException…
    ├── LogicException    → InvalidArgumentException, DomainException…
    └── ErrorException
```

Depuis PHP 7, la plupart des erreurs fatales sont devenues des `Error`, donc
**rattrapables** — mais elles ne descendent pas d'`Exception`.

## La conséquence pratique

```php
try {
    $result = 1 % 0;
} catch (Exception $e) {
    // JAMAIS atteint : DivisionByZeroError est un Error, pas une Exception
}

try {
    $result = 1 % 0;
} catch (DivisionByZeroError $e) {   // correct
    // ...
} catch (Throwable $e) {             // filet le plus large
    // ...
}
```

`catch (Exception $e)` ne rattrape **pas** les `Error`. Pour tout attraper, il
faut `Throwable`.

## Écrire ses propres exceptions

```php
final class PaymentDeclined extends \RuntimeException
{
}
```

**Une classe PHP ne peut pas implémenter `Throwable` directement.** Le moteur
l'interdit : il faut étendre `Exception` ou `Error`. Une interface de marquage
propre à l'application, elle, reste possible :

```php
interface DomainFailure {}
final class PaymentDeclined extends \RuntimeException implements DomainFailure {}
```

## Multi-catch et finally

```php
try {
    // ...
} catch (TypeError | ValueError $e) {   // types unis
    // ...
} catch (\Throwable) {                  // capture sans variable (8.0+)
    // ...
} finally {
    // exécuté dans tous les cas, y compris après un return
}
```

## Pièges d'examen

**`Error` n'étend pas `Exception`.** Les deux implémentent `Throwable`, et
c'est leur seul point commun dans la hiérarchie.

**`finally` s'exécute même après un `return`** dans le `try` — et un `return`
dans le `finally` écrase celui du `try`.

**L'ordre des `catch` compte** : le premier bloc compatible gagne. Placer
`catch (Throwable)` en premier rend les suivants inatteignables.

## Points clés

- `Throwable` est l'interface racine ; `Error` et `Exception` sont deux
  branches sœurs.
- `catch (Exception)` laisse passer les `Error`.
- Une classe ne peut pas implémenter `Throwable` directement.
- `finally` s'exécute toujours ; l'ordre des `catch` est significatif.

## Sources officielles

- Manuel PHP — *Throwable*, *Predefined Exceptions*
