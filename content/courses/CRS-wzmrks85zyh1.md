---
id: CRS-wzmrks85zyh1
official_item: OIT-ttwpe00f32q9
title: "Validation scopes"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-29"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/validation.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/validation.rst"
    anchor: "constraint-targets"
    branch: "8.0"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/validation/custom_constraint.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/validation/custom_constraint.rst"
    anchor: "class-constraint-validator"
    branch: "8.0"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Validator/Mapping/Loader/AttributeLoader.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Mapping/Loader/AttributeLoader.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "AttributeLoader, get|is|has method constraints"
    verified_at: "2026-09-29"
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
retour d'une méthode dont le nom commence par **`get`**, **`is`** ou **`has`**.
La **visibilité est libre** — `private`, `protected` ou `public`.

```php
#[Assert\IsTrue(message: 'Le mot de passe ne peut pas reprendre le prénom.')]
private function isPasswordSafe(): bool
{
    return $this->firstName !== $this->password;
}
```

L'intérêt est de valider une règle qui n'existe dans aucune propriété : la valeur
est calculée au moment de la validation.

Exécuté avec `symfony/validator` 8.0.15, `AttributeLoader` étant identique à la
branche 8.0 :

| Méthode annotée | Résultat |
|---|---|
| `private function isPasswordSafe()` | violation au chemin `passwordSafe` |
| `public function getFullName()` | violation au chemin `fullName` |
| `private function checkPasswordSafety()` | **`MappingException`** : « Constraints can only be added on methods beginning with "get", "is" or "has". » |
| `public function isOk(int $x)` | **`ArgumentCountError`** à la validation |
| `public static function isOk()` | violation : accepté |
| `public function ISOK()` | violation au chemin `oK` : le préfixe ignore la casse |

Le chemin de la violation est le nom **sans le préfixe**, première lettre en
minuscule. Un mauvais nom n'est donc pas ignoré en silence : la lecture des
métadonnées échoue. L'absence d'argument, elle, n'est pas vérifiée au
chargement ; c'est l'appel qui casse.

## La classe

Certaines contraintes s'appliquent à l'objet lui-même, `Callback` en tête. Une
contrainte personnalisée y accède en retournant `Constraint::CLASS_CONSTRAINT`
depuis sa méthode `getTargets()` ; par défaut, elle retourne
`Constraint::PROPERTY_CONSTRAINT`, qui couvre propriétés **et** accesseurs.
Exécuté : `NotBlank` retourne `'property'`, `Callback` les deux cibles.

C'est la seule portée qui voit **toutes** les propriétés à la fois, donc la seule
qui puisse exprimer une règle croisée — « la date de fin suit la date de
début ». Exécuté avec un `#[Assert\Callback('check')]` de classe qui rattache la
violation à `end` par `atPath('end')`.

## Pièges d'examen

**Un accesseur doit s'appeler `get…`, `is…` ou `has…`** ; sinon, une
`MappingException`, pas un oubli silencieux.

**La visibilité ne bloque rien**, ni sur une propriété ni sur un accesseur.

**Le chemin d'une violation d'accesseur perd le préfixe** : `isPasswordSafe`
donne `passwordSafe`.

**Une règle qui croise deux propriétés n'est pas une contrainte de propriété** :
il lui faut la portée classe.

## Points clés

- Trois portées : propriété, accesseur, classe.
- L'accesseur exige un nom en `get`/`is`/`has`, sans argument ; la visibilité
  est libre.
- Mauvais nom : `MappingException` ; argument requis : `ArgumentCountError`.
- La portée classe est la seule à voir l'objet entier ; `getTargets()` la
  déclare.

## Sources officielles

- [Validation, « Constraint Targets »](https://github.com/symfony/symfony-docs/blob/8.0/validation.rst)
- [Custom Validation Constraint, « Class Constraint Validator »](https://github.com/symfony/symfony-docs/blob/8.0/validation/custom_constraint.rst)
- [Validator 8.0, `AttributeLoader`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Mapping/Loader/AttributeLoader.php)
