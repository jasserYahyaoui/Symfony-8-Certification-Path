---
id: CRS-awb7sd999c5j
official_item: OIT-942znmbjwvad
title: "Security Core, CSRF and PasswordHasher components"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-30"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/security.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/security.rst"
    symbol_or_lines: "Security — `composer require symfony/security-bundle`, the bundle that wires the components into the framework"
    branch: "8.0"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/security/csrf.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/security/csrf.rst"
    branch: "8.0"
    symbol_or_lines: "`composer require symfony/security-csrf` — the CSRF token generation and validation package"
    verified_at: "2026-09-07"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/security/passwords.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/security/passwords.rst"
    branch: "8.0"
    symbol_or_lines: "Password Hashing and Verification — `composer require symfony/password-hasher`, installed on its own"
    verified_at: "2026-09-07"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Security/Core/composer.json"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Security/Core/composer.json"
    symbol_or_lines: "require: password-hasher, contracts; http-foundation in require-dev only"
    branch: "8.0"
    verified_at: "2026-09-30"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Security/Csrf/composer.json"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Security/Csrf/composer.json"
    symbol_or_lines: "require: security-core"
    branch: "8.0"
    verified_at: "2026-09-30"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/PasswordHasher/composer.json"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/PasswordHasher/composer.json"
    symbol_or_lines: "require: php only"
    branch: "8.0"
    verified_at: "2026-09-30"
---

## Objectif

Séparer trois packages que l'on croit n'en former qu'un, et savoir lequel répond
de quoi — et lequel dépend de l'autre.

## Trois composants, trois responsabilités

| Package | Ce qu'il fournit |
|---|---|
| `symfony/security-core` | le modèle : `UserInterface`, tokens, voters, décision d'accès, hiérarchie des roles |
| `symfony/security-csrf` | la génération et la vérification de tokens CSRF |
| `symfony/password-hasher` | le hachage et la vérification des passwords |

À quoi s'ajoutent `security-http`, qui branche le modèle sur la Request HTTP —
firewalls, authenticators, entry points — et `SecurityBundle`, qui câble le
tout dans le framework et expose la key `security:` de configuration.

## Qui dépend de qui

Les responsabilités sont séparées ; les packages, eux, ne sont pas tous
indépendants. Lu dans les `composer.json` de la branche 8.0 :

| Package | `require` (hors PHP et contrats) |
|---|---|
| `password-hasher` | **rien** |
| `security-core` | `password-hasher` |
| `security-csrf` | `security-core` |
| `security-http` | `security-core`, `http-foundation`, `http-kernel`, `property-access` |
| `SecurityBundle` | les quatre, plus le framework |

Exécuté, une résolution Composer le confirme : `require symfony/password-hasher`
n'installe que lui ; `require symfony/security-csrf` tire aussi `security-core`
et `password-hasher`. La raison est modeste — `security-csrf` lève les exceptions
de `security-core` (`RuntimeException`, `InvalidArgumentException`).

## Pourquoi la séparation compte

`password-hasher` sert dans une application sans authentication. Exécuté, seul
dans un projet vide : `PasswordHasherFactory` avec l'algorithme `auto` produit un
hachage `$2y$13$…`, `verify()` rend `true` pour le bon password et `false`
sinon, et aucune class de `security-core` n'est chargée.

`security-csrf` protège un form **sans firewall** — c'est ce qu'utilise le
composant Form. Il installe `security-core`, mais n'exige ni authentication ni
configuration de sécurité. Aucun de ces packages ne dépend de Doctrine ni d'une
base de données.

C'est la réponse à une question d'examen classique : hacher un password
**n'exige pas** le système de sécurité complet.

## Ce qui vit dans `security-core`

- `UserInterface` et le contrat user ;
- les **tokens** (`TokenInterface`) et leur stockage ;
- l'**authorization** : `AccessDecisionManager`, `Voter`, stratégies ;
- la **hiérarchie des roles**.

Ce composant ne **dépend** pas de HTTP : `http-foundation` n'y figure qu'en
`require-dev`. Quelques classes optionnelles s'en servent quand il est présent —
`UsageTrackingTokenStorage` pour la session, `LazyResponseException` pour une
Response, `ExpressionVoter` pour exposer la Request. C'est `security-http` qui
traduit une Request en tentative d'authentication et une décision en Response.

## Pièges d'examen

**Le hachage n'est pas dans `security-core`** : il a son propre package,
`password-hasher` — dont `security-core` dépend.

**La protection CSRF n'est pas liée à l'authentication** : aucun firewall
requis. Mais `security-csrf` n'est pas un package isolé : il installe
`security-core`.

**Seul `password-hasher` s'installe vraiment seul.**

**`security-core` ne dépend pas de HTTP.** Firewalls et authenticators
appartiennent à `security-http`.

## Points clés

- `security-core` = modèle, tokens, authorization, roles ; aucune dependency à HTTP.
- `security-csrf` = tokens CSRF, sans firewall ; dépend de `security-core`.
- `password-hasher` = hachage ; ne dépend de rien.
- `security-http` relie le modèle à la Request ; `SecurityBundle` le configure.

## Sources officielles

- [Security](https://github.com/symfony/symfony-docs/blob/8.0/security.rst)
- [`security-core`, `composer.json` 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Security/Core/composer.json)
- [`security-csrf`, `composer.json` 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Security/Csrf/composer.json)
- [`password-hasher`, `composer.json` 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/PasswordHasher/composer.json)
