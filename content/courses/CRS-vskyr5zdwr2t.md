---
id: CRS-vskyr5zdwr2t
official_item: OIT-kkhb3wd341ex
title: "Validation groups"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-29"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/validation/groups.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/validation/groups.rst"
    symbol_or_lines: '"How to Apply only a Subset of all Your Validation Constraints (Validation Groups)"; and "Constraints in the Default group of a class are the constraints that have either no explicit group configured or that are configured to a group equal to the class name or the string Default"'
    branch: "8.0"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Validator/Constraint.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Constraint.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "Constraint::addImplicitGroupName()"
    verified_at: "2026-09-29"
outcome_sections:
  OUT-yme605pqpcbw: "Les groups implicites"
  OUT-s73ykpqy5sch: "Valider avec un group"
  OUT-vnsvr71xs30c: "`Default` contre le nom de la class"
---

## Objectif

N'appliquer qu'une partie des constraints d'une class, et connaître les deux
groups que Symfony crée sans qu'on les déclare.

## Le besoin

La même class sert à plusieurs moments : inscription, puis modification du
profil. Les règles ne sont pas les mêmes. Un **group** est un tag posé
sur une constraint ; à la validation, on choisit les tags à appliquer.

```php
class User
{
    #[Assert\Email(groups: ['registration'])]
    private string $email;

    #[Assert\Length(min: 2)]
    private string $city;
}
```

## Les groups implicites

C'est ce qui se rate. Cette class définit **trois** groups, dont deux que
personne n'a écrits :

| Group | Contenu |
|---|---|
| `Default` | les constraints sans group explicite — ici `city` |
| `User` | **le nom de la class** ; les constraints de `User` dans `Default` |
| `registration` | les constraints explicitement étiquetées — ici `email` |

Exécuté avec `symfony/validator` 8.0.15 : une constraint **sans group** reçoit
les groups `["Default", "User"]` ; déclarée `groups: ['Default']`, elle reçoit
aussi `["Default", "User"]`.

## Un écart entre la documentation et le code

`groups.rst` (8.0) range dans `Default` les constraints « sans group, ou
configurées avec le nom de la class ou la string `Default` ». Pour le nom de la
class, le code dit autre chose. Exécuté : une constraint déclarée
`groups: ['User']` a pour seuls groups `["User"]`, et **ne s'exécute pas**
quand on valide `Default` ; elle s'exécute avec le group `User`. La relation ne
vaut que dans un sens : ce qui est dans `Default` est dans `User`, pas
l'inverse.

## Valider avec un group

```php
$validator->validate($user);                             // Default
$validator->validate($user, null, ['registration']);     // registration seul
$validator->validate($user, null, ['Default', 'registration']);
```

Sans argument, **seul `Default` s'applique** — pas « toutes les constraints ».
Une constraint rangée dans un group personnalisé est donc invisible par défaut.
Le troisième argument accepte aussi une string seule.

Dans un form, la même chose s'écrit avec l'option `validation_groups` du
type.

## `Default` contre le nom de la class

Les deux sont identiques… sauf sur les objects **imbriqués**. Exécuté avec un
`User` dont la property `address` porte `#[Assert\Valid]` :

| Group demandé | Violations |
|---|---|
| `Default` | `city`, `address.zip` |
| `User` | `city`, sans `address.zip` |
| `registration` | `email`, `address.street` |

Valider dans `Default` descend dans les constraints `Default` de l'`Address` ;
valider dans `User` ne le fait pas, car le group transmis à l'`Address` reste
`User`. Un group nommé, lui, traverse la cascade : `address.street`, étiquetée
`registration`, est atteinte.

La deuxième différence apparaît avec une group sequence ; elle est traitée
dans l'item *Group sequence*.

## Pièges d'examen

**Sans group passé, seul `Default` est validé.** Une constraint étiquetée
`registration` ne s'exécute jamais si personne ne demande ce group.

**`Default` et le nom de la class ne sont pas interchangeables** dès qu'il y a
un object imbriqué.

**Une constraint étiquetée du seul nom de la class n'est pas dans `Default`**,
quoi qu'en dise la documentation.

**Un group ne se déclare nulle part** : il existe dès qu'une constraint le
nomme.

## Points clés

- Un group est un tag sur une constraint, choisi à la validation.
- Trois groups existent d'emblée : `Default`, le nom de la class, et chaque
  group nommé.
- Par défaut, seul `Default` s'applique.
- `Default` descend dans les objects cascadés, le group du nom de class non.

## Sources officielles

- [Validation Groups](https://github.com/symfony/symfony-docs/blob/8.0/validation/groups.rst)
- [Validator 8.0, `Constraint::addImplicitGroupName()`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Constraint.php)
