---
id: CRS-enbbsykx22xe
official_item: OIT-45p7535dk4s2
title: "Providers"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Security/Core/User/UserProviderInterface.php"
    branch: "8.0"
    symbol_or_lines: "loadUserByIdentifier, refreshUser, supportsClass"
    verified_at: "2026-09-01"
---

## Objectif

Savoir d'où vient l'objet utilisateur, et quelle méthode est appelée à quel
moment.

## Le contrat

Un fournisseur charge un utilisateur à partir de son identifiant. Trois méthodes :

| Méthode | Quand |
|---|---|
| `loadUserByIdentifier()` | à la **connexion** : trouver l'utilisateur |
| `refreshUser()` | à **chaque requête suivante** : recharger depuis la source |
| `supportsClass()` | dire si ce fournisseur gère cette classe |

`refreshUser()` est le point à comprendre. L'utilisateur n'est pas conservé tel
quel entre deux requêtes : il est **rechargé**, ce qui garantit que des données
modifiées en base sont prises en compte. Un utilisateur supprimé fait échouer le
rechargement, et la session est invalidée.

## Les fournisseurs fournis

| Type | Source |
|---|---|
| `entity` | une entité Doctrine, par une propriété |
| `memory` | des utilisateurs écrits dans la configuration |
| `ldap` | un annuaire LDAP |
| `chain` | plusieurs fournisseurs essayés dans l'ordre |

```yaml
security:
    providers:
        app_user_provider:
            entity:
                class: App\Entity\User
                property: email
```

`memory` sert aux tests et aux prototypes ; les mots de passe y sont écrits en
clair ou pré-hachés dans la configuration.

## Fournisseur et pare-feu

Un pare-feu utilise **un** fournisseur. Quand plusieurs sont déclarés, chaque
pare-feu doit désigner le sien par la clé `provider`, sinon la configuration est
ambiguë et échoue.

## Mettre à jour le hachage

Un fournisseur qui implémente `PasswordUpgraderInterface` reçoit le mot de passe
réencodé quand l'algorithme a changé. C'est ce qui rend la migration
transparente pour l'utilisateur.

## Pièges d'examen

**`refreshUser()` s'exécute à chaque requête**, pas seulement à la connexion :
c'est là que se paient les requêtes de base de données.

**Un utilisateur supprimé en base est déconnecté** au rechargement suivant.

**Plusieurs fournisseurs imposent de nommer celui du pare-feu.**

## Points clés

- `loadUserByIdentifier()` connecte, `refreshUser()` recharge à chaque requête.
- Quatre types fournis : `entity`, `memory`, `ldap`, `chain`.
- Un pare-feu = un fournisseur ; à plusieurs, il faut le désigner.
- `PasswordUpgraderInterface` rend la migration de hachage transparente.

## Sources officielles

- [`UserProviderInterface`, branche 8.0](https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Security/Core/User/UserProviderInterface.php)
- [User Providers](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/security/user_providers.rst)
