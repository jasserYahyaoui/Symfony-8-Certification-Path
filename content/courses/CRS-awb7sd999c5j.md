---
id: CRS-awb7sd999c5j
official_item: OIT-942znmbjwvad
title: "Security Core, CSRF and PasswordHasher components"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/security.rst"
    branch: "8.0"
    verified_at: "2026-09-01"
---

## Objectif

Séparer trois paquets que l'on croit n'en former qu'un, et savoir lequel répond
de quoi.

## Trois composants, trois responsabilités

| Paquet | Ce qu'il fournit |
|---|---|
| `symfony/security-core` | le modèle : `UserInterface`, jetons, votants, décision d'accès, hiérarchie des rôles |
| `symfony/security-csrf` | la génération et la vérification de jetons CSRF |
| `symfony/password-hasher` | le hachage et la vérification des mots de passe |

À quoi s'ajoutent `security-http`, qui branche le modèle sur la requête HTTP —
pare-feux, authenticators, points d'entrée — et `SecurityBundle`, qui câble le
tout dans le framework et expose la clé `security:` de configuration.

## Pourquoi la séparation compte

Chacun s'installe seul. `password-hasher` sert dans une application sans
authentification ; `security-csrf` protège un formulaire sans pare-feu — c'est
d'ailleurs ce qu'utilise le composant Form. Aucun des trois ne dépend de
Doctrine ni d'une base de données.

C'est la réponse à une question d'examen classique : hacher un mot de passe
**n'exige pas** le système de sécurité complet.

## Ce qui vit dans `security-core`

- `UserInterface` et le contrat utilisateur ;
- les **jetons** (`TokenInterface`) et leur stockage ;
- l'**autorisation** : `AccessDecisionManager`, `Voter`, stratégies ;
- la **hiérarchie des rôles**.

Ce composant ne sait rien de HTTP. C'est `security-http` qui traduit une requête
en tentative d'authentification et une décision en réponse.

## Pièges d'examen

**Le hachage n'est pas dans `security-core`** : il a son propre paquet,
`password-hasher`.

**La protection CSRF n'est pas liée à l'authentification** : le composant CSRF
s'utilise seul, et le composant Form s'en sert sans pare-feu.

**`security-core` ignore HTTP.** Pare-feux et authenticators appartiennent à
`security-http`.

## Points clés

- `security-core` = modèle, jetons, autorisation, rôles ; aucun lien avec HTTP.
- `security-csrf` = jetons CSRF, utilisable seul.
- `password-hasher` = hachage, utilisable seul.
- `security-http` relie le modèle à la requête ; `SecurityBundle` le configure.

## Sources officielles

- [Security](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/security.rst)
