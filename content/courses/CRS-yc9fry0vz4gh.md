---
id: CRS-yc9fry0vz4gh
official_item: OIT-bvnvx2b6yt2y
title: "Exception and error handling"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-09"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/php/doc-en/master/language/predefined/throwable.xml"
    readable_url: "https://github.com/php/doc-en/blob/master/language/predefined/throwable.xml"
    symbol_or_lines: '"Throwable is the base interface for any object that can be thrown via a throw statement, including Error and Exception"'
    repository: "php/doc-en"
    branch: "master"
    verified_at: "2026-09-01"
outcome_sections:
  OUT-xwdtmfm9hzsc: "La hiérarchie"
  OUT-c799q9ew2xxg: "La conséquence pratique"
  OUT-km64zadyg6pf: "Écrire ses propres exceptions"
  OUT-xrckdfqa6x5n: "Multi-catch et finally"
---

## Objectif

Naviguer la hiérarchie `Throwable` et rattraper le bon type — sans rattraper
trop.

## L'essentiel

`Throwable` est la racine, avec deux branches **sœurs** : `Error`, pour les
problèmes de l'engine, et `Exception`, pour les conditions applicatives. Donc
`catch (Exception)` laisse passer une `Error` ; seul `Throwable` attrape tout.
Une class ne peut pas implémenter `Throwable` directement. Le premier `catch`
compatible gagne, et `finally` s'exécute même après un `return`.

## Le schéma

```text
Throwable  (interface, étend Stringable)
├── Error                    problèmes du moteur
│   ├── TypeError → ArgumentCountError
│   ├── ValueError
│   ├── ArithmeticError → DivisionByZeroError
│   └── AssertionError
└── Exception                conditions applicatives
    ├── RuntimeException  → OutOfBoundsException, UnexpectedValueException…
    ├── LogicException    → InvalidArgumentException, DomainException…
    └── ErrorException
```

## L'exemple

```php
try {
    $result = intdiv(1, 0);
} catch (Exception $e) {
    // JAMAIS atteint : DivisionByZeroError est une Error, pas une Exception
} catch (DivisionByZeroError $e) {
    // atteint
} finally {
    // exécuté dans tous les cas, sauf exit()
}
```

## La hiérarchie

Depuis PHP 7, la plupart des erreurs fatales sont devenues des `Error`, donc
**rattrapables** — mais elles ne descendent pas d'`Exception`.
`ErrorException`, malgré son nom, est une `Exception`.

## La conséquence pratique

`catch (Exception $e)` ne rattrape **pas** les `Error`. Pour tout attraper, il
faut `Throwable`. Et l'ordre compte : le premier block compatible gagne, donc
un `catch (Throwable)` placé en tête rend les suivants inatteignables.

## Écrire ses propres exceptions

```php
interface DomainFailure extends \Throwable {}
final class PaymentDeclined extends \RuntimeException implements DomainFailure {}
```

**Une class ne peut pas implémenter `Throwable` directement** : il faut étendre
`Exception` ou `Error`. Une interface de marquage, elle, peut étendre
`Throwable`, et `catch (DomainFailure $e)` rattrape alors toutes les
exceptions qui l'implémentent, quelle que soit leur branche SPL.

## Multi-catch et finally

`catch (TypeError | ValueError $e)` unit des types ; depuis PHP 8.0,
`catch (\Throwable)` s'écrit sans variable. Le block `finally` est
exécuté après le try et les catch, y compris après un `return` — et un `return`
dans le `finally` écrase celui du `try`. Il ne s'exécute pas après `exit()`.

## Pièges d'examen

**`Error` n'étend pas `Exception`.** Les deux implémentent `Throwable`, et
c'est leur seul point commun dans la hiérarchie.

**`finally` s'exécute même après un `return`** dans le `try` — et un `return`
dans le `finally` écrase celui du `try`. Pas après un `exit()`, en revanche :
exécuté, le script s'arrête sans passer par le `finally`.

**L'ordre des `catch` compte** : le premier block compatible gagne. Placer
`catch (Throwable)` en premier rend les suivants inatteignables.

## Application

- Une division par zéro possible → `catch (DivisionByZeroError)`, pas
  `catch (Exception)`.
- Une famille d'erreurs de librairie → interface de marquage qui étend
  `\Throwable`.

## QCM d'entraînement

**Question 1.** Quelle est la class parente directe d'`ArgumentCountError` ?

- **A.** `TypeError`
- **B.** `Error`
- **C.** `Exception`
- **D.** `ValueError`

<details>
<summary>Correction</summary>

**Bonne réponse : A.** Un mauvais nombre d'arguments est un cas particulier
d'erreur de type ; la class descend donc de `TypeError`, elle-même une `Error`.

</details>

**Question 2.** Le code exécute `intdiv(1, 0)` dans un `try` suivi de
`catch (TypeError | ValueError $e)`. Que se passe-t-il ?

- **A.** Rattrapée par le multi-catch
- **B.** Non rattrapée : `DivisionByZeroError`
- **C.** Rattrapée comme `ValueError`
- **D.** Un warning, puis le script continue normalement

<details>
<summary>Correction</summary>

**Bonne réponse : B.** L'erreur levée descend d'`ArithmeticError`, branche
distincte des deux types listés : elle traverse ce block.

</details>

**Question 3.** `ErrorException` est-elle une `Error` ?

- **A.** Oui, d'où son nom
- **B.** Elle hérite des deux branches à la fois
- **C.** Non : elle étend `Exception`
- **D.** Ni l'un ni l'autre

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Malgré son nom, elle se trouve dans la branche
applicative ; `catch (Exception $e)` la rattrape donc.

</details>

**Question 4.** Le block `catch (\Throwable)`, sans variable, est-il valide en
PHP 8.4 ?

- **A.** Non : la variable est obligatoire
- **B.** Seulement pour `Throwable`
- **C.** Seulement avec `finally`
- **D.** Oui, depuis PHP 8.0

<details>
<summary>Correction</summary>

**Bonne réponse : D.** Depuis PHP 8.0, la variable du `catch` est facultative,
quel que soit le type attrapé, quand le code n'utilise pas l'exception.

</details>

## Fiche express

**Si tu dois retenir seulement 6 choses :**

- `Throwable` → **`Error`** et **`Exception`**, deux branches sœurs.
- `catch (Exception)` laisse passer les **`Error`** ; `Throwable` attrape tout.
- `ErrorException` est une **`Exception`**.
- Pas d'`implements Throwable` sur une class ; une **interface** peut l'étendre.
- Premier `catch` compatible **gagne**.
- `finally` : après `return`, **pas** après `exit()` ; son `return` gagne.

Moyen mnémotechnique : **« Exception pour l'appli, Error pour l'engine »**.

## Sources officielles

- Manuel PHP — *Throwable*, *Predefined Exceptions*

