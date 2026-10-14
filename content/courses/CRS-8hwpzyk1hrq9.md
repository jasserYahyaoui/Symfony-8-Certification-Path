---
id: CRS-8hwpzyk1hrq9
official_item: OIT-3qgn13f7zvqx
title: "Authorization"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-30"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Security/Core/Authorization/Voter/VoterInterface.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Security/Core/Authorization/Voter/VoterInterface.php"
    branch: "8.0"
    symbol_or_lines: "ACCESS_GRANTED, ACCESS_ABSTAIN, ACCESS_DENIED"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Security/Core/Authorization/AccessDecisionManager.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Security/Core/Authorization/AccessDecisionManager.php"
    symbol_or_lines: "decide(), collectResults() — votes yielded one at a time"
    branch: "8.0"
    verified_at: "2026-09-30"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Security/Core/Authorization/Strategy/AffirmativeStrategy.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Security/Core/Authorization/Strategy/AffirmativeStrategy.php"
    symbol_or_lines: "decide() returns true on the first ACCESS_GRANTED"
    branch: "8.0"
    verified_at: "2026-09-30"
outcome_sections:
  OUT-vsg2j46ctc7h: "Le path"
  OUT-67x1qs1y7bjk: "Deux familles d'attributes"
  OUT-rf2bzpb84d4y: "Refuser"
---

## Objectif

Savoir qui décide qu'un accès est accordé, et par quel path. Les voters
eux-mêmes et les stratégies ont leur propre item.

## La question posée

L'authorization répond à **« avez-vous le droit ? »**, une fois l'identité
connue. Elle s'exprime toujours de la même façon :

```php
$this->denyAccessUnlessGranted('ROLE_ADMIN');
$this->isGranted('EDIT', $post);
if ($security->isGranted('POST_EDIT', $post)) { }
```

```html
{% if is_granted('ROLE_ADMIN') %}
```

## Le path

`isGranted($attribute, $subject)` délègue à l'`AccessDecisionManager`. Celui-ci
interroge les voters, chacun retournant l'une de trois valeurs : accordé,
refusé, ou **abstention**. Une **stratégie** transforme ces votes en décision
unique.

Les voters ne sont pas forcément **tous** interrogés : l'`AccessDecisionManager`
leur demande leur vote **un par un** (un générateur), et la stratégie peut
s'arrêter avant la fin. Exécuté avec SecurityBundle 8.0.15, stratégie par défaut
`affirmative`, deux voters supportant `EDIT` — le premier accorde, le second
refuserait : le journal ne montre que `A.supports(EDIT)`, `A.vote`. Le second
n'a pas même été consulté. `AffirmativeStrategy::decide()` rend `true` au
premier vote favorable.

Le point à retenir reste que le framework ne compare rien lui-même : un role est
vérifié par un voter comme le reste. `ROLE_ADMIN` n'est pas traité
différemment de `EDIT` — seul le voter qui répond change.

## Deux familles d'attributes

| Attribute | Portée |
|---|---|
| `ROLE_*` | ce que le user **est** |
| `IS_AUTHENTICATED_FULLY`, `IS_AUTHENTICATED_REMEMBERED`, `PUBLIC_ACCESS` | l'**état** de l'authentication |
| un verbe métier — `EDIT`, `PUBLISH` | ce que le user peut faire **sur un object** |

La troisième famille est la raison d'être des voters : un role ne peut pas
exprimer « l'auteur peut modifier son propre article ».

## Le sujet

Le second argument d'`isGranted()` est l'object concerné. C'est lui qui permet à
un voter de décider au cas par cas ; sans lui, la question ne porte que sur
le user.

## Refuser

`denyAccessUnlessGranted()` lève une `AccessDeniedException`. Le firewall la
transforme selon que le user est connu ou non. Exécuté, sur la même action
exigeant `ROLE_ADMIN` :

| Situation | Response |
|---|---|
| anonyme, firewall en `http_basic` | **401**, `WWW-Authenticate: Basic realm="Secured Area"` |
| anonyme, firewall en `form_login` | **302** vers la page de connexion |
| `alice` connue, sans `ROLE_ADMIN` | **403** — « Access Denied. The user doesn't have ROLE_ADMIN. » |

Le status code dépend donc de l'authentication — et, pour un anonyme, du
**entry point** du firewall —, pas de l'authorization.

## Pièges d'examen

**Un role passe par un voter**, comme n'importe quel attribute.

**Tous les voters ne votent pas toujours** : en `affirmative`, le premier
« accordé » arrête la consultation.

**Le statut n'est pas toujours 403** : un anonyme reçoit la Response du point
d'entrée — redirect pour un form, 401 pour HTTP Basic.

**Sans sujet, un voter d'object ne peut rien décider** et s'abstient.

## Points clés

- `isGranted($attribute, $subject)` interroge l'`AccessDecisionManager`.
- Les voters sont consultés un par un ; la stratégie peut conclure avant la fin.
- Trois familles d'attributes : role, état d'authentication, verbe métier.
- 403 pour un user connu ; pour un anonyme, la Response de l'entry point.

## Sources officielles

- [`VoterInterface`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Security/Core/Authorization/Voter/VoterInterface.php)
- [`AccessDecisionManager`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Security/Core/Authorization/AccessDecisionManager.php)
- [`AffirmativeStrategy`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Security/Core/Authorization/Strategy/AffirmativeStrategy.php)
- [Security](https://github.com/symfony/symfony-docs/blob/8.0/security.rst)
