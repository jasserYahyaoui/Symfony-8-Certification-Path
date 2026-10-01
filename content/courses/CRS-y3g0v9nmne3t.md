---
id: CRS-y3g0v9nmne3t
official_item: OIT-kqm5mxq4jnkj
title: "Voters and voting strategies"
content_level: DEEP
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/security/voters.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/security/voters.rst"
    branch: "8.0"
    symbol_or_lines: "Voter, supports, voteOnAttribute, four strategies"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Security/Core/Authorization/Voter/VoterInterface.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Security/Core/Authorization/Voter/VoterInterface.php"
    branch: "8.0"
    symbol_or_lines: "ACCESS_GRANTED, ACCESS_ABSTAIN, ACCESS_DENIED"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Security/Core/Authorization/Voter/Voter.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Security/Core/Authorization/Voter/Voter.php"
    branch: "8.0"
    symbol_or_lines: "voteOnAttribute(string, mixed, TokenInterface, ?Vote $vote = null)"
    verified_at: "2026-10-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Security/Core/Authorization/Strategy/UnanimousStrategy.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Security/Core/Authorization/Strategy/UnanimousStrategy.php"
    branch: "8.0"
    symbol_or_lines: "decide() — first denial; all abstain: allowIfAllAbstainDecisions"
    verified_at: "2026-10-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Security/Core/CHANGELOG.md"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Security/Core/CHANGELOG.md"
    branch: "8.0"
    symbol_or_lines: "8.0 — Add argument $vote to VoterInterface::vote() and Voter::voteOnAttribute()"
    verified_at: "2026-10-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/SecurityBundle/DependencyInjection/MainConfiguration.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/SecurityBundle/DependencyInjection/MainConfiguration.php"
    branch: "8.0"
    symbol_or_lines: "access_decision_manager — strategy, strategy_service, allow_if_*"
    verified_at: "2026-10-01"
---

## Objectif

Écrire une décision d'autorisation qui dépend de l'objet, et savoir comment les
votes de plusieurs votants sont agrégés — parce que la stratégie choisie change
la réponse à votes identiques.

## Prérequis

L'autorisation et son point d'entrée `isGranted()`.

## Pourquoi un votant

Un rôle décrit ce qu'un utilisateur **est**. Il ne peut pas exprimer « l'auteur
peut modifier son propre article », qui dépend de l'article. Un **votant** reçoit
l'attribut *et* l'objet, et décide au cas par cas.

## Les deux méthodes, en 8.0

```php
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PostVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, ['POST_EDIT', 'POST_VIEW'], true)
            && $subject instanceof Post;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if ('POST_EDIT' === $attribute && $subject->getAuthor() !== $user) {
            $vote?->addReason('Only the author may edit this post.');

            return false;
        }

        return 'POST_VIEW' !== $attribute || $subject->isPublished() || $subject->getAuthor() === $user;
    }
}
```

Le quatrième paramètre, `?Vote $vote = null`, est **obligatoire dans la
signature** en 8.0 : le CHANGELOG de Security Core 8.0 l'ajoute à
`VoterInterface::vote()` et à `Voter::voteOnAttribute()`. Exécuté, une classe
qui déclare encore l'ancienne signature à trois paramètres ne se charge pas :
« Declaration of PostVoter::voteOnAttribute(…) must be compatible with
Voter::voteOnAttribute(…, ?Vote $vote = null) » — erreur fatale.

`Vote::addReason()` explique un refus. Exécuté avec SecurityBundle 8.0.15 :
`denyAccessUnlessGranted('POST_EDIT', …)` répond 403 avec « Access Denied. Only
the author may edit this post. »

`supports()` filtre : il dit si ce votant a quelque chose à dire. Quand il
retourne `false`, le votant **s'abstient** — il ne refuse pas.
`voteOnAttribute()` ne s'exécute que sur ce que le votant comprend, et son
booléen devient accordé ou refusé.

Un votant est un service ordinaire : l'autoconfiguration le déclare. Exécuté,
le votant ci-dessus a voté sans une ligne dans `services.yaml`.

## Trois valeurs, pas deux

Un vote vaut `ACCESS_GRANTED` (1), `ACCESS_ABSTAIN` (0) ou `ACCESS_DENIED` (-1).
L'abstention est le cas normal, et c'est ce qui permet à des votants
indépendants de coexister : chacun ne se prononce que sur son domaine.

## Les quatre stratégies

`security.access_decision_manager.strategy` accepte `affirmative` (défaut),
`consensus`, `unanimous` et `priority` ; une stratégie maison passe par
`strategy_service`.

Les votants ne votent **pas tous** : l'`AccessDecisionManager` les interroge un
par un, et la stratégie s'arrête dès que sa décision est acquise. Exécuté sur
`AccessDecisionManager` 8.0.15, votants dans l'ordre indiqué, avec le journal
des votants réellement interrogés :

| Votes | `affirmative` | `consensus` | `unanimous` | `priority` |
|---|---|---|---|---|
| accordé, refusé | **accordé** (1 interrogé) | accordé (2) | **refusé** (2) | accordé (1) |
| refusé, accordé | accordé (2) | accordé (2) | refusé (1) | **refusé** (1) |
| abstention ×3 | refusé | refusé | **refusé** | refusé |
| accordé, abstention | accordé (1) | accordé (2) | accordé (2) | accordé (1) |
| abstention, refusé, accordé | accordé (3) | accordé (3) | refusé (2) | refusé (2) |
| accordé, refusé, refusé | accordé (1) | **refusé** (3) | refusé (2) | accordé (1) |

Ce que le tableau montre :

- `affirmative` accorde au **premier** accord ; `unanimous` refuse au **premier**
  refus ; `priority` suit le **premier** votant qui ne s'abstient pas ;
  seul `consensus` interroge tout le monde, puisqu'il compte.
- `priority` dépend de l'**ordre** des votants — la priorité de leur service.
- `consensus` tranche une égalité par `allow_if_equal_granted_denied`, défaut
  `true` : accordé.

## Les deux cas limites

**Égalité en `consensus`** : `allow_if_equal_granted_denied`, défaut `true`.

**Tous abstenus**, quelle que soit la stratégie : `allow_if_all_abstain`, défaut
**`false`** — donc refusé. Cela vaut **aussi** pour `unanimous` : trois
abstentions et aucun refus donnent un refus, pas un accord. Un attribut qu'aucun
votant ne comprend — une faute de frappe — est donc refusé.

```yaml
security:
    access_decision_manager:
        strategy: unanimous
        allow_if_all_abstain: false
```

## Pièges d'examen

- `supports()` à `false` = **abstention**, pas refus.
- L'ancienne signature de `voteOnAttribute()` est une **erreur fatale** en 8.0.
- Le défaut est `affirmative` : **un seul** accord suffit, même si un autre refuse.
- `unanimous` = « aucun refus **et** au moins un accord » ; si tous s'abstiennent,
  `allow_if_all_abstain` décide, et refuse par défaut.
- Les votants ne sont pas tous interrogés : seule `consensus` les consulte tous.
- `priority` dépend de l'ordre des votants.

## Points clés

- `supports()` filtre, `voteOnAttribute(…, ?Vote $vote = null)` décide ;
  `addReason()` explique un refus.
- Trois valeurs de vote : 1, 0, -1.
- Quatre stratégies ; `affirmative` par défaut, un accord suffit.
- Cas limites : `allow_if_equal_granted_denied` (défaut `true`),
  `allow_if_all_abstain` (défaut `false`, `unanimous` compris).

## Sources officielles

- [How to Use Voters to Check User Permissions](https://github.com/symfony/symfony-docs/blob/8.0/security/voters.rst)
- [Security Core 8.0, `Voter`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Security/Core/Authorization/Voter/Voter.php)
- [Security Core 8.0, `UnanimousStrategy`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Security/Core/Authorization/Strategy/UnanimousStrategy.php)
- [Security Core 8.0, CHANGELOG](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Security/Core/CHANGELOG.md)
- [SecurityBundle 8.0, `MainConfiguration`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/SecurityBundle/DependencyInjection/MainConfiguration.php)
