---
id: CRS-wzmrks85zyh1
official_item: OIT-ttwpe00f32q9
title: "Validation scopes"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-26"
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
outcome_sections:
  OUT-tgzkeywcr2c5: "Les trois cibles"
  OUT-wdhsjrrpmggk: "L'accessor"
  OUT-v2t6031e9x7s: "La class"
---

## Objectif

Savoir sur quoi une constraint peut être posée, et ce que chaque scope permet
que les autres ne permettent pas.

## Les trois cibles

Une constraint s'applique à une **property**, à un **accessor**, ou à la
**class entière**.

| Cible | Ce qu'elle valide | Quand la choisir |
|---|---|---|
| property | la valeur stockée | le cas courant |
| accessor | la valeur **retournée** par une method | la règle est calculée |
| class | l'object entier | la règle croise plusieurs properties |

## La property

```php
#[Assert\NotBlank]
private string $name;
```

La visibilité n'a aucune importance : le validator lit la property par
réflexion, y compris `private`.

## L'accessor

C'est le scope qui se retient mal. Une constraint peut porter sur la return value d'une method dont le nom commence par **`get`**, **`is`** ou **`has`**.
La **visibilité est libre** — `private`, `protected` ou `public`.

```php
#[Assert\IsTrue(message: 'Le mot de passe ne peut pas reprendre le prénom.')]
private function isPasswordSafe(): bool
{
    return $this->firstName !== $this->password;
}
```

L'intérêt est de valider une règle qui n'existe dans aucune property : la valeur
est calculée au moment de la validation.

Exécuté avec `symfony/validator` 8.0.15, `AttributeLoader` étant identique à la
branche 8.0 :

| Method annotée | Résultat |
|---|---|
| `private function isPasswordSafe()` | violation au path `passwordSafe` |
| `public function getFullName()` | violation au path `fullName` |
| `private function checkPasswordSafety()` | **`MappingException`** : « Constraints can only be added on methods beginning with "get", "is" or "has". » |
| `public function isOk(int $x)` | **`ArgumentCountError`** à la validation |
| `public static function isOk()` | violation : accepté |
| `public function ISOK()` | violation au path `oK` : le préfixe ignore la casse |

Le path de la violation est le nom **sans le préfixe**, première lettre en
minuscule. Un mauvais nom n'est donc pas ignoré en silence : la lecture des
métadonnées échoue. L'absence d'argument, elle, n'est pas vérifiée au
chargement ; c'est l'appel qui casse.

## La class

Certaines constraints s'appliquent à l'object lui-même, `Callback` en tête. Une
constraint personnalisée y accède en retournant `Constraint::CLASS_CONSTRAINT`
depuis sa method `getTargets()` ; par défaut, elle retourne
`Constraint::PROPERTY_CONSTRAINT`, qui couvre properties **et** accessors.
Exécuté : `NotBlank` retourne `'property'`, `Callback` les deux cibles.

C'est la seule portée qui voit **toutes** les properties à la fois, donc la seule
qui puisse exprimer une règle croisée — « la date de fin suit la date de
début ». Exécuté avec un `#[Assert\Callback('check')]` de class qui rattache la
violation à `end` par `atPath('end')`.

## Pièges d'examen

**Un accessor doit s'appeler `get…`, `is…` ou `has…`** ; sinon, une
`MappingException`, pas un oubli silencieux.

**La visibilité ne bloque rien**, ni sur une property ni sur un accessor.

**Le path d'une violation d'accessor perd le préfixe** : `isPasswordSafe`
donne `passwordSafe`.

**Une règle qui croise deux properties n'est pas une constraint de property** :
il lui faut le scope class.

## Points clés

- Trois portées : property, accessor, class.
- L'accessor exige un nom en `get`/`is`/`has`, sans argument ; la visibilité
  est libre.
- Mauvais nom : `MappingException` ; argument requis : `ArgumentCountError`.
- Le scope class est le seul à voir l'object entier ; `getTargets()` le
  déclare.

## Sources officielles

- [Validation, « Constraint Targets »](https://github.com/symfony/symfony-docs/blob/8.0/validation.rst)
- [Custom Validation Constraint, « Class Constraint Validator »](https://github.com/symfony/symfony-docs/blob/8.0/validation/custom_constraint.rst)
- [Validator 8.0, `AttributeLoader`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Mapping/Loader/AttributeLoader.php)
