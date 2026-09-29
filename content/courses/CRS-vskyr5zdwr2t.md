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
---

## Objectif

N'appliquer qu'une partie des contraintes d'une classe, et connaître les deux
groupes que Symfony crée sans qu'on les déclare.

## Le besoin

La même classe sert à plusieurs moments : inscription, puis modification du
profil. Les règles ne sont pas les mêmes. Un **groupe** est une étiquette posée
sur une contrainte ; à la validation, on choisit les étiquettes à appliquer.

```php
class User
{
    #[Assert\Email(groups: ['registration'])]
    private string $email;

    #[Assert\Length(min: 2)]
    private string $city;
}
```

## Les groupes implicites

C'est ce qui se rate. Cette classe définit **trois** groupes, dont deux que
personne n'a écrits :

| Groupe | Contenu |
|---|---|
| `Default` | les contraintes sans groupe explicite — ici `city` |
| `User` | **le nom de la classe** ; les contraintes de `User` dans `Default` |
| `registration` | les contraintes explicitement étiquetées — ici `email` |

Exécuté avec `symfony/validator` 8.0.15 : une contrainte **sans groupe** reçoit
les groupes `["Default", "User"]` ; déclarée `groups: ['Default']`, elle reçoit
aussi `["Default", "User"]`.

## Un écart entre la documentation et le code

`groups.rst` (8.0) range dans `Default` les contraintes « sans groupe, ou
configurées avec le nom de la classe ou la chaîne `Default` ». Pour le nom de la
classe, le code dit autre chose. Exécuté : une contrainte déclarée
`groups: ['User']` a pour seuls groupes `["User"]`, et **ne s'exécute pas**
quand on valide `Default` ; elle s'exécute avec le groupe `User`. La relation ne
vaut que dans un sens : ce qui est dans `Default` est dans `User`, pas
l'inverse.

## Valider avec un groupe

```php
$validator->validate($user);                             // Default
$validator->validate($user, null, ['registration']);     // registration seul
$validator->validate($user, null, ['Default', 'registration']);
```

Sans argument, **seul `Default` s'applique** — pas « toutes les contraintes ».
Une contrainte rangée dans un groupe personnalisé est donc invisible par défaut.
Le troisième argument accepte aussi une chaîne seule.

Dans un formulaire, la même chose s'écrit avec l'option `validation_groups` du
type.

## `Default` contre le nom de la classe

Les deux sont identiques… sauf sur les objets **imbriqués**. Exécuté avec un
`User` dont la propriété `address` porte `#[Assert\Valid]` :

| Groupe demandé | Violations |
|---|---|
| `Default` | `city`, `address.zip` |
| `User` | `city`, sans `address.zip` |
| `registration` | `email`, `address.street` |

Valider dans `Default` descend dans les contraintes `Default` de l'`Address` ;
valider dans `User` ne le fait pas, car le groupe transmis à l'`Address` reste
`User`. Un groupe nommé, lui, traverse la cascade : `address.street`, étiquetée
`registration`, est atteinte.

La deuxième différence apparaît avec une séquence de groupes ; elle est traitée
dans l'item *Group sequence*.

## Pièges d'examen

**Sans groupe passé, seul `Default` est validé.** Une contrainte étiquetée
`registration` ne s'exécute jamais si personne ne demande ce groupe.

**`Default` et le nom de la classe ne sont pas interchangeables** dès qu'il y a
un objet imbriqué.

**Une contrainte étiquetée du seul nom de la classe n'est pas dans `Default`**,
quoi qu'en dise la documentation.

**Un groupe ne se déclare nulle part** : il existe dès qu'une contrainte le
nomme.

## Points clés

- Un groupe est une étiquette sur une contrainte, choisie à la validation.
- Trois groupes existent d'emblée : `Default`, le nom de la classe, et chaque
  groupe nommé.
- Par défaut, seul `Default` s'applique.
- `Default` descend dans les objets cascadés, le groupe du nom de classe non.

## Sources officielles

- [Validation Groups](https://github.com/symfony/symfony-docs/blob/8.0/validation/groups.rst)
- [Validator 8.0, `Constraint::addImplicitGroupName()`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Constraint.php)
