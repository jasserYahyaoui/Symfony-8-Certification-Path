---
id: CRS-vskyr5zdwr2t
official_item: OIT-kkhb3wd341ex
title: "Validation groups"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/validation/groups.rst"
    branch: "8.0"
    verified_at: "2026-09-01"
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

Une contrainte appartient à `Default` si elle ne déclare aucun groupe, **ou** si
elle déclare `Default` ou le nom de la classe.

## Valider avec un groupe

```php
$validator->validate($user);                             // Default
$validator->validate($user, null, ['registration']);     // registration seul
$validator->validate($user, null, ['Default', 'registration']);
```

Sans argument, **seul `Default` s'applique** — pas « toutes les contraintes ».
Une contrainte rangée dans un groupe personnalisé est donc invisible par défaut.

Dans un formulaire, la même chose s'écrit avec l'option `validation_groups` du
type.

## `Default` contre le nom de la classe

Les deux sont identiques… sauf sur les objets **imbriqués**. Valider un `User`
dans le groupe `Default` applique aussi les contraintes `Default` des classes
référencées et cascadées. Valider dans le groupe `User` n'applique que celles de
`User` lui-même.

La deuxième différence apparaît avec une séquence de groupes ; elle est traitée
dans l'item *Group sequence*.

## Pièges d'examen

**Sans groupe passé, seul `Default` est validé.** Une contrainte étiquetée
`registration` ne s'exécute jamais si personne ne demande ce groupe.

**`Default` et le nom de la classe ne sont pas interchangeables** dès qu'il y a
un objet imbriqué.

**Un groupe ne se déclare nulle part** : il existe dès qu'une contrainte le
nomme.

## Points clés

- Un groupe est une étiquette sur une contrainte, choisie à la validation.
- Trois groupes existent d'emblée : `Default`, le nom de la classe, et chaque
  groupe nommé.
- Par défaut, seul `Default` s'applique.
- `Default` descend dans les objets cascadés, le groupe du nom de classe non.

## Sources officielles

- [Validation Groups](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/validation/groups.rst)
