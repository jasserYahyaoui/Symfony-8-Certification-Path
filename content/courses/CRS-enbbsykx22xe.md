---
id: CRS-enbbsykx22xe
official_item: OIT-45p7535dk4s2
title: "Providers"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-30"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Security/Core/User/UserProviderInterface.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Security/Core/User/UserProviderInterface.php"
    branch: "8.0"
    symbol_or_lines: "loadUserByIdentifier, refreshUser, supportsClass"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/security/user_providers.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/security/user_providers.rst"
    branch: "8.0"
    symbol_or_lines: "User Providers"
    verified_at: "2026-09-30"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Security/Http/Firewall/ContextListener.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Security/Http/Firewall/ContextListener.php"
    branch: "8.0"
    symbol_or_lines: "refreshUser() — UserNotFoundException, hasUserChanged(); session->remove(sessionKey)"
    verified_at: "2026-09-30"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/SecurityBundle/SecurityBundle.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/SecurityBundle/SecurityBundle.php"
    branch: "8.0"
    symbol_or_lines: "build() — addUserProviderFactory(InMemoryFactory), addUserProviderFactory(LdapFactory)"
    verified_at: "2026-09-30"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bridge/Doctrine/DependencyInjection/Security/UserProvider/EntityFactory.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Doctrine/DependencyInjection/Security/UserProvider/EntityFactory.php"
    branch: "8.0"
    symbol_or_lines: "EntityFactory implements UserProviderFactoryInterface"
    verified_at: "2026-09-30"
---

## Objectif

Savoir d'où vient l'objet utilisateur, et quelle méthode est appelée à quel
moment.

## Le contrat

Un fournisseur charge un utilisateur à partir de son identifiant. Trois méthodes :

| Méthode | Quand |
|---|---|
| `loadUserByIdentifier()` | à la **connexion** : trouver l'utilisateur |
| `refreshUser()` | à **chaque requête suivante** d'un pare-feu avec état : recharger depuis la source |
| `supportsClass()` | dire si ce fournisseur gère cette classe |

`refreshUser()` est le point à comprendre. L'utilisateur n'est pas conservé tel
quel entre deux requêtes : il est **rechargé**, puis **comparé** à celui du jeton.

## Ce que l'exécution montre

Un fournisseur sur mesure, lisant ses utilisateurs dans un fichier, déclaré
`id: App\P13\FileUserProvider` dans une application SecurityBundle 8.0.15 :

| Requête | Méthodes appelées | Résultat |
|---|---|---|
| connexion HTTP Basic | `loadUserByIdentifier(alice)` | 200 |
| requête suivante, cookie de session | `refreshUser(alice)` | 200 |
| même requête après suppression d'`alice` | `refreshUser(alice)`, qui lève `UserNotFoundException` | **401** |
| même requête après ajout de `ROLE_EDITOR` à `alice` | `refreshUser(alice)` | jeton abandonné, `alice` **anonyme** |
| pare-feu sans état, deux requêtes | `loadUserByIdentifier` à chaque fois, jamais `refreshUser` | 200 |

Un utilisateur supprimé n'est donc pas une erreur 500 : `ContextListener`
intercepte l'exception, journalise « Username could not be found in the
selected user provider », retire le jeton, et l'utilisateur redevient anonyme.
Il ne détruit pas la session : il en **supprime la clé du jeton**
(`$session->remove($this->sessionKey)`), les autres données restent.

Un rôle ajouté en base ne s'obtient donc pas en cours de session : il
**déconnecte**. `ContextListener::hasUserChanged()` abandonne le jeton quand le
mot de passe, les rôles ou l'identifiant diffèrent — ou, si la classe implémente
`EquatableInterface`, quand `isEqualTo()` rend `false` ; celui d'`InMemoryUser`,
utilisé ici, compare aussi les rôles.

## Les fournisseurs fournis

SecurityBundle 8.0 enregistre `memory` et `ldap` (`SecurityBundle::build()`), et
reconnaît `chain` et `id` dans sa configuration. `entity` vient du pont
Doctrine (`EntityFactory`) et n'existe qu'avec l'intégration Doctrine. Exécuté,
sans elle : « Unrecognized option "entity" under "security.providers.users".
Available options are "chain", "id", "ldap", "memory". »

| Clé | Source |
|---|---|
| `entity` | une entité Doctrine, par une propriété — avec l'intégration Doctrine |
| `memory` | des utilisateurs écrits dans la configuration |
| `ldap` | un annuaire LDAP |
| `chain` | plusieurs fournisseurs essayés dans l'ordre |
| `id` | un service qui implémente `UserProviderInterface` |

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

**`refreshUser()` s'exécute à chaque requête d'un pare-feu avec état**, pas
seulement à la connexion — et jamais sur un pare-feu sans état.

**Un utilisateur supprimé est déconnecté** au rechargement suivant : 401 ou
redirection, pas 500 ; la session survit, sans son jeton.

**Des rôles modifiés en base déconnectent aussi** : le nouveau rôle ne s'obtient
qu'en se reconnectant.

**`entity` n'est pas fourni par SecurityBundle** : il faut l'intégration
Doctrine. `id` branche un fournisseur maison.

**Plusieurs fournisseurs imposent de nommer celui du pare-feu.**

## Points clés

- `loadUserByIdentifier()` connecte, `refreshUser()` recharge à chaque requête
  avec état, et un utilisateur modifié perd son jeton.
- Clés : `memory`, `ldap`, `chain`, `id` ; `entity` avec Doctrine.
- Un pare-feu = un fournisseur ; à plusieurs, il faut le désigner.
- `PasswordUpgraderInterface` rend la migration de hachage transparente.

## Sources officielles

- [`UserProviderInterface`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Security/Core/User/UserProviderInterface.php)
- [User Providers](https://github.com/symfony/symfony-docs/blob/8.0/security/user_providers.rst)
- [Security HTTP 8.0, `ContextListener`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Security/Http/Firewall/ContextListener.php)
- [SecurityBundle 8.0, `SecurityBundle`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/SecurityBundle/SecurityBundle.php)
- [Doctrine Bridge 8.0, `EntityFactory`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Doctrine/DependencyInjection/Security/UserProvider/EntityFactory.php)
