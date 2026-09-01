---
id: CRS-y3g0v9nmne3t
official_item: OIT-kqm5mxq4jnkj
title: "Voters and voting strategies"
content_level: DEEP
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/security/voters.rst"
    branch: "8.0"
    symbol_or_lines: "Voter, supports, voteOnAttribute, four strategies"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Security/Core/Authorization/Voter/VoterInterface.php"
    branch: "8.0"
    symbol_or_lines: "ACCESS_GRANTED, ACCESS_ABSTAIN, ACCESS_DENIED"
    verified_at: "2026-09-01"
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

## Les deux méthodes

```php
class PostVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, ['POST_EDIT', 'POST_VIEW'], true)
            && $subject instanceof Post;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        return match ($attribute) {
            'POST_EDIT' => $subject->getAuthor() === $user,
            'POST_VIEW' => $subject->isPublished() || $subject->getAuthor() === $user,
        };
    }
}
```

`supports()` filtre : il dit si ce votant a quelque chose à dire. Quand il
retourne `false`, le votant **s'abstient** — il ne refuse pas.

`voteOnAttribute()` ne s'exécute donc que sur ce que le votant comprend, et son
booléen devient accordé ou refusé.

Un votant est un service ordinaire : l'autoconfiguration le déclare, il n'y a
rien à écrire dans `services.yaml`.

## Trois valeurs, pas deux

Un vote vaut `ACCESS_GRANTED`, `ACCESS_DENIED` ou `ACCESS_ABSTAIN`. L'abstention
est le cas normal, et c'est ce qui permet à des votants indépendants de
coexister : chacun ne se prononce que sur son domaine.

## Les quatre stratégies

Tous les votants votent ; la stratégie agrège :

| Stratégie | Accès accordé si… |
|---|---|
| **`affirmative`** — le défaut | **au moins un** votant accorde |
| `consensus` | il y a **plus** d'accords que de refus |
| `unanimous` | **aucun** votant ne refuse |
| `priority` | le **premier** votant qui ne s'abstient pas tranche, par priorité de service |

C'est le point qui décide d'une réponse d'examen. Avec deux votants dont l'un
accorde et l'autre refuse : `affirmative` **accorde**, `unanimous` **refuse**,
`consensus` est à égalité et tranche par `allow_if_equal_granted_denied`, qui
vaut `true` par défaut — donc accorde.

## Les deux cas limites

**Égalité en `consensus`** : `allow_if_equal_granted_denied`, par défaut `true`.

**Tous abstenus**, quelle que soit la stratégie : `allow_if_all_abstain`, par
défaut **`false`** — donc refusé. Un attribut qu'aucun votant ne comprend est
donc refusé, pas accordé.

```yaml
security:
    access_decision_manager:
        strategy: unanimous
        allow_if_all_abstain: false
```

## Pièges d'examen

- `supports()` à `false` = **abstention**, pas refus.
- Le défaut est `affirmative` : **un seul** accord suffit, même si un autre refuse.
- `unanimous` ne veut pas dire « tous accordent » mais « **aucun ne refuse** » —
  des abstentions n'empêchent donc rien.
- Tous abstenus → refusé par défaut (`allow_if_all_abstain: false`).
- Un votant sans sujet ne peut pas décider et s'abstient.

## Points clés

- `supports()` filtre, `voteOnAttribute()` décide ; l'abstention est le cas normal.
- Trois valeurs de vote : accordé, refusé, abstention.
- Quatre stratégies ; `affirmative` par défaut, un accord suffit.
- `unanimous` = aucun refus ; `consensus` compte les voix.
- Cas limites : `allow_if_equal_granted_denied` (défaut `true`),
  `allow_if_all_abstain` (défaut `false`).

## Sources officielles

- [How to Use Voters to Check User Permissions](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/security/voters.rst)
- [`VoterInterface`, branche 8.0](https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Security/Core/Authorization/Voter/VoterInterface.php)
