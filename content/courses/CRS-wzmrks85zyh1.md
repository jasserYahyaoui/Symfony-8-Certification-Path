---
id: CRS-wzmrks85zyh1
official_item: OIT-ttwpe00f32q9
title: "Validation scopes"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/validation.rst"
    anchor: "constraint-targets"
    branch: "8.0"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/validation/custom_constraint.rst"
    anchor: "class-constraint-validator"
    branch: "8.0"
    verified_at: "2026-09-01"
---

## Objectif

Savoir sur quoi une contrainte peut être posée, et ce que chaque portée permet
que les autres ne permettent pas.

## Les trois cibles

Une contrainte s'applique à une **propriété**, à un **accesseur**, ou à la
**classe entière**.

| Cible | Ce qu'elle valide | Quand la choisir |
|---|---|---|
| propriété | la valeur stockée | le cas courant |
| accesseur | la valeur **retournée** par une méthode | la règle est calculée |
| classe | l'objet entier | la règle croise plusieurs propriétés |

## La propriété

```php
#[Assert\NotBlank]
private string $name;
```

La visibilité n'a aucune importance : le validateur lit la propriété par
réflexion, y compris `private`.

## L'accesseur

C'est la portée qui se retient mal. Une contrainte peut porter sur la valeur de
retour d'une méthode, à deux conditions : le nom commence par **`get`**, **`is`**
ou **`has`**, et la méthode ne prend pas d'argument. La **visibilité est libre** —
`private`, `protected` ou `public`.

```php
#[Assert\IsTrue(message: 'Le mot de passe ne peut pas reprendre le prénom.')]
public function isPasswordSafe(): bool
{
    return $this->firstName !== $this->password;
}
```

L'intérêt est de valider une règle qui n'existe dans aucune propriété : la valeur
est calculée au moment de la validation.

## La classe

Certaines contraintes s'appliquent à l'objet lui-même, `Callback` en tête. Une
contrainte personnalisée y accède en retournant `Constraint::CLASS_CONSTRAINT`
depuis sa méthode `getTargets()` ; par défaut, une contrainte vise une propriété
ou un accesseur.

C'est la seule portée qui voit **toutes** les propriétés à la fois, donc la seule
qui puisse exprimer une règle croisée — « la date de fin suit la date de début ».

## Pièges d'examen

**Un accesseur doit s'appeler `get…`, `is…` ou `has…`.** Une méthode nommée
autrement ne peut pas porter de contrainte, quelle que soit sa visibilité.

**La visibilité ne bloque rien**, ni sur une propriété ni sur un accesseur.

**Une règle qui croise deux propriétés n'est pas une contrainte de propriété** :
il lui faut la portée classe.

## Points clés

- Trois portées : propriété, accesseur, classe.
- L'accesseur exige un nom en `get`/`is`/`has` et aucun argument ; la visibilité
  est libre.
- La portée classe est la seule à voir l'objet entier ; `getTargets()` la déclare.
- Une contrainte de classe sert aux règles croisées.

## Sources officielles

- [Validation, « Constraint Targets »](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/validation.rst)
- [Custom Validation Constraint, « Class Constraint Validator »](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/validation/custom_constraint.rst)
