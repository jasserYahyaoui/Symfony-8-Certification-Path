---
id: CRS-way4aktj2d5m
official_item: OIT-6wd8860brzfy
title: "Validator component"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Validator/Validator/ValidatorInterface.php"
    branch: "8.0"
    symbol_or_lines: "ValidatorInterface"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/validation.rst"
    anchor: "using-the-validator"
    branch: "8.0"
    verified_at: "2026-09-01"
---

## Objectif

Savoir ce que le composant valide, comment on l'appelle, et ce qu'il retourne.
Les contraintes elles-mêmes appartiennent aux items *Built-in validation
constraints* et *Custom callback validators*.

## Ce qu'il valide

Le Validator confronte une **valeur** à des **contraintes**. Le cas courant est
un objet dont les propriétés portent des contraintes, mais l'entrée peut aussi
être une valeur nue :

```php
$violations = $validator->validate($email, new Assert\Email());
```

C'est un composant **autonome** : hors framework, `Validation::createValidator()`
en construit un. Dans une application Symfony, on injecte
`ValidatorInterface` par autowiring.

## Les trois manières de valider

```php
$validator->validate($object);                                  // l'objet entier
$validator->validateProperty($object, 'email');                 // une propriété
$validator->validatePropertyValue(User::class, 'email', $value); // une valeur candidate
```

La troisième mérite d'être connue : elle valide une valeur **contre les
contraintes déclarées** pour cette propriété, **sans que l'objet la porte**.
Elle accepte d'ailleurs un nom de classe à la place d'une instance.

Chaque méthode accepte en dernier argument les groupes à appliquer.

## Ce qu'il retourne

Toujours une `ConstraintViolationListInterface` — **jamais un booléen**, et
jamais une exception. Une validation réussie retourne une liste **vide**.

```php
if (0 !== count($violations)) {
    // la liste est Countable et traversable
    foreach ($violations as $violation) {
        $violation->getMessage();
        $violation->getPropertyPath();   // 'email', 'address.city'
        $violation->getInvalidValue();
        $violation->getCode();
    }
}
```

Tester `if ($violations)` est donc faux : une liste vide reste un objet, donc
vraie. Le test correct porte sur `count()`.

## Pièges d'examen

**`validate()` ne lève rien.** Une donnée invalide produit une liste de
violations, pas une exception.

**Une liste vide est un objet.** Seul `count()` dit si la validation a réussi.

**`validatePropertyValue()` n'a pas besoin de l'objet peuplé** : elle teste une
valeur candidate contre les contraintes de la propriété.

## Points clés

- Le composant confronte une valeur à des contraintes ; il est autonome.
- `validate()`, `validateProperty()`, `validatePropertyValue()`.
- Retour : une `ConstraintViolationListInterface`, vide si tout est valide.
- Jamais de booléen, jamais d'exception : compter les violations.

## Sources officielles

- [ValidatorInterface, branche 8.0](https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Validator/Validator/ValidatorInterface.php)
- [Validation, « Using the Validator »](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/validation.rst)
