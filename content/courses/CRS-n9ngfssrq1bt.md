---
id: CRS-n9ngfssrq1bt
official_item: OIT-gkh08ztwdme1
title: "Users"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Security/Core/User/UserInterface.php"
    branch: "8.0"
    symbol_or_lines: "getRoles, getUserIdentifier"
    verified_at: "2026-09-01"
---

## Objectif

Écrire une classe utilisateur conforme à Symfony 8.0, et connaître le contrat
minimal — plus court qu'on ne le croit.

## `UserInterface`, en 8.0

Deux méthodes. C'est tout :

```php
interface UserInterface
{
    public function getRoles(): array;
    public function getUserIdentifier(): string;
}
```

`eraseCredentials()` **n'existe plus** dans cette version : la méthode a été
dépréciée puis retirée. Une classe qui l'implémente encore ne provoque pas
d'erreur, mais rien ne l'appelle.

`getUserIdentifier()` retourne l'identifiant **d'affichage et de recherche** —
courriel, nom de connexion — pas nécessairement la clé primaire.

`getRoles()` doit toujours retourner au moins un rôle ; la convention est
d'ajouter `ROLE_USER` à la liste stockée.

## Le mot de passe est une interface séparée

Un utilisateur n'a pas forcément de mot de passe : un jeton d'API, un
fournisseur d'identité externe s'en passent. Le mot de passe vit donc dans une
autre interface :

```php
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function getPassword(): ?string { return $this->password; }
}
```

C'est cette interface que le système de hachage attend, et sa présence est ce
qui rend l'authentification par mot de passe possible.

## Comparer deux utilisateurs

À chaque requête, l'utilisateur est rechargé et **comparé** à celui du jeton. Par
défaut la comparaison porte sur l'identifiant et le mot de passe. Pour changer la
règle — déconnecter l'utilisateur si ses rôles changent, par exemple — la classe
implémente `EquatableInterface` et sa méthode `isEqualTo()`.

## Interdire un compte

Un compte désactivé ou expiré ne se refuse pas dans `getRoles()` : c'est le rôle
d'un **user checker**, qui lève une exception dédiée avant que
l'authentification n'aboutisse.

## Pièges d'examen

**`UserInterface` n'a que deux méthodes en Symfony 8.0** ; `eraseCredentials()`
a été retirée.

**`getPassword()` vient de `PasswordAuthenticatedUserInterface`**, pas de
`UserInterface`.

**`getUserIdentifier()` n'est pas l'identifiant de base de données.**

**Un compte désactivé relève d'un user checker**, pas des rôles.

## Points clés

- `UserInterface` : `getRoles()` et `getUserIdentifier()`, rien d'autre.
- Le mot de passe est porté par `PasswordAuthenticatedUserInterface`.
- `EquatableInterface` change la comparaison faite à chaque requête.
- Les comptes désactivés relèvent d'un user checker.

## Sources officielles

- [`UserInterface`, branche 8.0](https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Security/Core/User/UserInterface.php)
- [Security](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/security.rst)
