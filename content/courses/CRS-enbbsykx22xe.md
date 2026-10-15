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
outcome_sections:
  OUT-58qtgyk785j9: "Le contrat"
  OUT-km3tbkkw36s3: "Ce que l'exécution montre"
  OUT-18068akbjm47: "Les providers fournis"
  OUT-sy6g8zgjqqpj: "Provider et firewall"
---

## Objectif

Savoir d'où vient l'object user, et quelle method est appelée à quel
moment.

## Le contrat

Un provider charge un user à partir de son identifier. Trois methods :

| Method | Quand |
|---|---|
| `loadUserByIdentifier()` | à la **connexion** : trouver le user |
| `refreshUser()` | à **chaque Request suivante** d'un firewall avec état : recharger depuis la source |
| `supportsClass()` | dire si ce provider gère cette class |

`refreshUser()` est le point à comprendre. Le user n'est pas conservé tel
quel entre deux Requests : il est **rechargé**, puis **comparé** à celui du token.

## Ce que l'exécution montre

Un provider sur mesure, lisant ses users dans un file, déclaré
`id: App\P13\FileUserProvider` dans une application SecurityBundle 8.0.15 :

| Request | Methods appelées | Résultat |
|---|---|---|
| connexion HTTP Basic | `loadUserByIdentifier(alice)` | 200 |
| Request suivante, cookie de session | `refreshUser(alice)` | 200 |
| même Request après suppression d'`alice` | `refreshUser(alice)`, qui lève `UserNotFoundException` | **401** |
| même Request après ajout de `ROLE_EDITOR` à `alice` | `refreshUser(alice)` | token abandonné, `alice` **anonyme** |
| firewall sans état, deux Requests | `loadUserByIdentifier` à chaque fois, jamais `refreshUser` | 200 |

Un user supprimé n'est donc pas une erreur 500 : `ContextListener`
intercepte l'exception, journalise « Username could not be found in the
selected user provider », retire le token, et le user redevient anonyme.
Il ne détruit pas la session : il en **supprime la key du token**
(`$session->remove($this->sessionKey)`), les autres données restent.

Un role ajouté en base ne s'obtient donc pas en cours de session : il
**déconnecte**. `ContextListener::hasUserChanged()` abandonne le token quand le
password, les roles ou l'identifier diffèrent — ou, si la class implémente
`EquatableInterface`, quand `isEqualTo()` rend `false` ; celui d'`InMemoryUser`,
utilisé ici, compare aussi les roles.

## Les providers fournis

SecurityBundle 8.0 enregistre `memory` et `ldap` (`SecurityBundle::build()`), et
reconnaît `chain` et `id` dans sa configuration. `entity` vient du bridge
Doctrine (`EntityFactory`) et n'existe qu'avec l'intégration Doctrine. Exécuté,
sans elle : « Unrecognized option "entity" under "security.providers.users".
Available options are "chain", "id", "ldap", "memory". »

| Key | Source |
|---|---|
| `entity` | une entité Doctrine, par une property — avec l'intégration Doctrine |
| `memory` | des users écrits dans la configuration |
| `ldap` | un annuaire LDAP |
| `chain` | plusieurs providers essayés dans l'ordre |
| `id` | un service qui implémente `UserProviderInterface` |

```yaml
security:
    providers:
        app_user_provider:
            entity:
                class: App\Entity\User
                property: email
```

`memory` sert aux tests et aux prototypes ; les passwords y sont écrits en
clair ou pré-hachés dans la configuration.

## Provider et firewall

Un firewall utilise **un** provider. Quand plusieurs sont déclarés, chaque
firewall doit désigner le sien par la key `provider`, sinon la configuration est
ambiguë et échoue.

## Mettre à jour le hash

Un provider qui implémente `PasswordUpgraderInterface` reçoit le password
réencodé quand l'algorithme a changé. C'est ce qui rend la migration
transparente pour le user.

## Pièges d'examen

**`refreshUser()` s'exécute à chaque Request d'un firewall avec état**, pas
seulement à la connexion — et jamais sur un firewall sans état.

**Un user supprimé est déconnecté** au rechargement suivant : 401 ou
redirect, pas 500 ; la session survit, sans son token.

**Des roles modifiés en base déconnectent aussi** : le nouveau role ne s'obtient
qu'en se reconnectant.

**`entity` n'est pas fourni par SecurityBundle** : il faut l'intégration
Doctrine. `id` branche un provider maison.

**Plusieurs providers imposent de nommer celui du firewall.**

## Points clés

- `loadUserByIdentifier()` connecte, `refreshUser()` recharge à chaque Request
  avec état, et un user modifié perd son token.
- Keys : `memory`, `ldap`, `chain`, `id` ; `entity` avec Doctrine.
- Un firewall = un provider ; à plusieurs, il faut le désigner.
- `PasswordUpgraderInterface` rend la migration de hash transparente.

## Sources officielles

- [`UserProviderInterface`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Security/Core/User/UserProviderInterface.php)
- [User Providers](https://github.com/symfony/symfony-docs/blob/8.0/security/user_providers.rst)
- [Security HTTP 8.0, `ContextListener`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Security/Http/Firewall/ContextListener.php)
- [SecurityBundle 8.0, `SecurityBundle`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/SecurityBundle/SecurityBundle.php)
- [Doctrine Bridge 8.0, `EntityFactory`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Doctrine/DependencyInjection/Security/UserProvider/EntityFactory.php)
