---
id: CRS-way4aktj2d5m
official_item: OIT-6wd8860brzfy
title: "Validator component"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-29"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Validator/Validator/ValidatorInterface.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Validator/ValidatorInterface.php"
    branch: "8.0"
    symbol_or_lines: "ValidatorInterface"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/validation.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/validation.rst"
    anchor: "using-the-validator"
    branch: "8.0"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Validator/ValidatorBuilder.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/ValidatorBuilder.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "ValidatorBuilder::enableAttributeMapping(), default false"
    verified_at: "2026-09-29"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/components/validator/resources.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/components/validator/resources.rst"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    symbol_or_lines: "Loading Resources, attribute mapping"
    verified_at: "2026-09-29"
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

Dans une application Symfony, on injecte `ValidatorInterface` par autowiring ;
le framework configure la lecture des contraintes.

## Hors framework : le piège des attributs

C'est un composant **autonome**, mais `Validation::createValidator()` construit
un validateur **qui ne lit pas les attributs** : `ValidatorBuilder` démarre avec
la lecture des attributs désactivée. Exécuté avec `symfony/validator` 8.0.15,
sur un objet portant `#[Assert\NotBlank]` et `#[Assert\Email]` avec des valeurs
invalides :

| Validateur | Violations |
|---|---|
| `Validation::createValidator()` | **0** |
| `Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator()` | 2 |

Aucune erreur ne signale l'oubli : l'objet paraît simplement valide. Les
contraintes passées en argument, elles, s'appliquent dans les deux cas.

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

Exécuté : `validateProperty()` sur une propriété **inexistante** ne lève rien et
retourne une liste vide.

## Ce qu'il retourne

Toujours une `ConstraintViolationListInterface` — **jamais un booléen**. Une
validation réussie retourne une liste **vide**. La classe concrète est
`ConstraintViolationList` : `Countable`, parcourable, accessible par indice
(`$violations[0]`), et convertible en chaîne pour le débogage.

```php
if (0 !== count($violations)) {
    foreach ($violations as $violation) {
        $violation->getMessage();
        $violation->getPropertyPath();   // 'email', 'address.city'
        $violation->getInvalidValue();
        $violation->getCode();           // un UUID propre à la contrainte
    }
}
```

Exécuté : la violation d'un objet imbriqué par `#[Assert\Valid]` porte le chemin
`address.city` ; le code d'`Email` est `bd79c0ab-ddba-46cc-a703-a7a4b08de310`.

Tester `if ($violations)` est faux : exécuté, une liste vide convertie en booléen
vaut `true`. Le test correct porte sur `count()`.

## Donnée invalide ou configuration invalide

Une **donnée** invalide ne lève rien, même quand son type ne convient pas.
Exécuté : un tableau soumis à `Email`, ou un objet soumis à `Length`, produit la
violation « This value should be of type string. ».

Deux cas lèvent, en revanche :

- une **contrainte** mal configurée, dès sa construction : `new
  Assert\Length()` sans `min` ni `max` lève `MissingOptionsException` ;
- une valeur **scalaire ou `null` sans contrainte** : `validate('x')` lève une
  `RuntimeException`, « Cannot validate values of type "string" automatically.
  Please provide a constraint. ». Un objet ou un tableau sans contrainte donne,
  lui, une liste vide.

## Deux raccourcis de `Validation`

| Méthode | Retour pour une valeur invalide |
|---|---|
| `Validation::createIsValidCallable(...)` | une fonction qui retourne `false` |
| `Validation::createCallable(...)` | une fonction qui **lève** `ValidationFailedException` |

Exécuté avec `Email` sur `'x'`. Ils servent là où l'on attend une fonction, par
exemple `setAllowedValues()` d'OptionsResolver.

## Pièges d'examen

**`validate()` ne lève rien pour une donnée invalide**, même mal typée ; une
contrainte mal configurée ou un scalaire sans contrainte, si.

**Une liste vide est un objet.** Seul `count()` dit si la validation a réussi.

**`createValidator()` ignore les attributs** : il faut
`enableAttributeMapping()`.

**`validatePropertyValue()` n'a pas besoin de l'objet peuplé** : elle teste une
valeur candidate contre les contraintes de la propriété.

## Points clés

- Le composant confronte une valeur à des contraintes ; il est autonome.
- Hors framework, `enableAttributeMapping()` pour lire les attributs.
- `validate()`, `validateProperty()`, `validatePropertyValue()`.
- Retour : une `ConstraintViolationListInterface`, vide si tout est valide.
- Donnée invalide : violation ; contrainte invalide ou scalaire sans
  contrainte : exception.

## Sources officielles

- [ValidatorInterface, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Validator/ValidatorInterface.php)
- [Validation, « Using the Validator »](https://github.com/symfony/symfony-docs/blob/8.0/validation.rst)
- [Loading Resources](https://github.com/symfony/symfony-docs/blob/8.0/components/validator/resources.rst) et [Validator 8.0, `ValidatorBuilder`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/ValidatorBuilder.php)
