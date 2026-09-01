---
id: CRS-8hwpzyk1hrq9
official_item: OIT-3qgn13f7zvqx
title: "Authorization"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Security/Core/Authorization/Voter/VoterInterface.php"
    branch: "8.0"
    symbol_or_lines: "ACCESS_GRANTED, ACCESS_ABSTAIN, ACCESS_DENIED"
    verified_at: "2026-09-01"
---

## Objectif

Savoir qui décide qu'un accès est accordé, et par quel chemin. Les votants
eux-mêmes et les stratégies ont leur propre item.

## La question posée

L'autorisation répond à **« avez-vous le droit ? »**, une fois l'identité
connue. Elle s'exprime toujours de la même façon :

```php
$this->denyAccessUnlessGranted('ROLE_ADMIN');
$this->isGranted('EDIT', $post);
if ($security->isGranted('POST_EDIT', $post)) { }
```

```html
{% if is_granted('ROLE_ADMIN') %}
```

## Le chemin

`isGranted($attribute, $subject)` délègue à l'`AccessDecisionManager`. Celui-ci
interroge **tous les votants**, chacun retournant l'une de trois valeurs :
accordé, refusé, ou **abstention**. Une **stratégie** transforme ces votes en
décision unique.

Le point à retenir est que le framework ne compare rien lui-même : un rôle est
vérifié par un votant comme le reste. `ROLE_ADMIN` n'est pas traité
différemment de `EDIT` — seul le votant qui répond change.

## Deux familles d'attributs

| Attribut | Portée |
|---|---|
| `ROLE_*` | ce que l'utilisateur **est** |
| `IS_AUTHENTICATED_FULLY`, `IS_AUTHENTICATED_REMEMBERED`, `PUBLIC_ACCESS` | l'**état** de l'authentification |
| un verbe métier — `EDIT`, `PUBLISH` | ce que l'utilisateur peut faire **sur un objet** |

La troisième famille est la raison d'être des votants : un rôle ne peut pas
exprimer « l'auteur peut modifier son propre article ».

## Le sujet

Le second argument d'`isGranted()` est l'objet concerné. C'est lui qui permet à
un votant de décider au cas par cas ; sans lui, la question ne porte que sur
l'utilisateur.

## Refuser

`denyAccessUnlessGranted()` lève une `AccessDeniedException`. Le pare-feu la
transforme en **403** si l'utilisateur est connu, ou déclenche l'entrée en
authentification — souvent une **redirection vers la connexion** — s'il ne l'est
pas. Le code de statut dépend donc de l'authentification, pas de l'autorisation.

## Pièges d'examen

**Un rôle passe par un votant**, comme n'importe quel attribut.

**Le statut n'est pas toujours 403** : un anonyme est redirigé vers la
connexion, un utilisateur connu reçoit 403.

**Sans sujet, un votant d'objet ne peut rien décider** et s'abstient.

## Points clés

- `isGranted($attribute, $subject)` interroge l'`AccessDecisionManager`.
- Tous les votants votent ; une stratégie tranche.
- Trois familles d'attributs : rôle, état d'authentification, verbe métier.
- 403 pour un utilisateur connu, redirection vers la connexion pour un anonyme.

## Sources officielles

- [`VoterInterface`, branche 8.0](https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Security/Core/Authorization/Voter/VoterInterface.php)
- [Security](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/security.rst)
