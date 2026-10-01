---
id: CRS-6gc45etcssfb
official_item: OIT-s3jh7wg5km19
title: "Authenticators, Passports and Badges"
content_level: DEEP
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/security/custom_authenticator.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/security/custom_authenticator.rst"
    anchor: "security-passport"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Security/Http/Authenticator/AuthenticatorInterface.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Security/Http/Authenticator/AuthenticatorInterface.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "AuthenticatorInterface, lines 36-86"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Security/Http/Authenticator/Passport/Passport.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Security/Http/Authenticator/Passport/Passport.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "Passport::__construct, line 40"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Security/Http/Authentication/AuthenticatorManager.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Security/Http/Authentication/AuthenticatorManager.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "executeAuthenticator() — CheckPassportEvent, unresolved badge, createToken(), LoginSuccessEvent"
    verified_at: "2026-10-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Security/Http/EventListener/CheckRememberMeConditionsListener.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Security/Http/EventListener/CheckRememberMeConditionsListener.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "onSuccessfulLogin() — _remember_me, always_remember_me, enable()"
    verified_at: "2026-10-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/SecurityBundle/DependencyInjection/SecurityExtension.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/SecurityBundle/DependencyInjection/SecurityExtension.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "$isLazy = !stateless && lazy"
    verified_at: "2026-10-01"
---

## Objectif

Décrire le flux d'authentification de Symfony 8.0 en nommant correctement
chaque objet, savoir lequel construire selon qu'il y a ou non des identifiants
à vérifier, et diagnostiquer un badge qui n'a pas l'effet attendu.

## Prérequis

- Firewalls — `must-know` (topic Security, item *Firewalls*).
- Users et user providers — `must-know` (items *Users*, *Providers*).

## Explication pour débuter

Trois objets, trois rôles distincts :

- **Authenticator** — le *processus*. Il décide s'il prend la requête en charge
  et en extrait ce qu'il faut.
- **Passport** — le *dossier* qu'il constitue : qui prétend se connecter, et
  avec quelle preuve.
- **Badge** — une *pièce* de ce dossier. Chaque badge porte une information ou
  déclenche une vérification.

L'authenticator ne valide rien lui-même. Il remplit un dossier ; des écouteurs
vérifient ensuite chaque pièce.

## Explication technique

`AuthenticatorInterface` définit exactement cinq méthodes :

```php
public function supports(Request $request): ?bool;
public function authenticate(Request $request): Passport;
public function createToken(Passport $passport, string $firewallName): TokenInterface;
public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response;
public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response;
```

`authenticate()` ne renvoie pas un utilisateur ni un booléen : il renvoie un
**Passport**. `AbstractAuthenticator` fournit `createToken()` ; l'ancien nom
`createAuthenticatedToken()` n'existe plus.

## `supports()` : trois réponses

- `true` — l'authenticator prend la requête, `authenticate()` est appelé ;
- `false` — il est ignoré ;
- `null` — la documentation de l'interface : « authenticate() can be called
  **lazily** when accessing the token storage ».

`null` ne veut donc pas dire « redemande-moi » : il veut dire « authentifie
seulement si quelqu'un lit le jeton ». Exécuté avec SecurityBundle 8.0.15, un
authenticator qui journalise ses appels, et `/plain`, une page qui ne lit pas
l'utilisateur :

| Pare-feu | `supports()` | `/plain` | `/whoami` (lit le jeton) |
|---|---|---|---|
| `lazy: true`, avec état | `null` | **`authenticate()` jamais appelé** | appelé |
| `lazy: true`, avec état | `true` | appelé | appelé |
| sans `lazy` | `null` | appelé | appelé |
| `lazy: true`, **`stateless: true`** | `null` | **appelé** | appelé |

La dernière ligne s'explique dans `SecurityExtension` :
`$isLazy = !$firewall['stateless'] && $firewall['lazy']`. Sur un pare-feu sans
état, `lazy` est ignoré sans avertissement.

## Le flux

Lu dans `AuthenticatorManager::executeAuthenticator()` :

```text
supports()                     l'authenticator prend-il la main ?
authenticate()                 construit le Passport
CheckPassportEvent             les écouteurs résolvent les badges : utilisateur
                               chargé, mot de passe vérifié, CSRF, user checker
badge non résolu ?             → échec
createToken()                  le Passport devient un TokenInterface
AuthenticationSuccessEvent     user checker, après authentification
onAuthenticationSuccess()      la réponse de l'authenticator
LoginSuccessEvent              remember-me, migration du mot de passe
```

Remember-me et rehachage arrivent donc **après** `onAuthenticationSuccess()`.

## Passport ou SelfValidatingPassport

Le constructeur de `Passport` **exige** des identifiants :

```php
public function __construct(UserBadge $userBadge, CredentialsInterface $credentials, array $badges = [])
```

Il n'existe donc pas de `Passport` sans credentials. Quand il n'y a rien à
vérifier — jeton d'API déjà digne de confiance, en-tête signé en amont — c'est
`SelfValidatingPassport` qu'il faut construire ; son constructeur ne prend que
`UserBadge` et les badges.

```php
// Mot de passe à vérifier
return new Passport(
    new UserBadge($email),
    new PasswordCredentials($plaintextPassword),
);

// Rien à vérifier : le porteur du jeton est déjà authentifié
return new SelfValidatingPassport(new UserBadge($apiToken));
```

## Les badges

| Badge | Rôle |
|---|---|
| `UserBadge` | Porte l'identifiant utilisateur. **Obligatoire.** |
| `PasswordCredentials` | Mot de passe en clair, à vérifier par le hasher |
| `CustomCredentials` | Vérification par un callable, qui doit rendre exactement `true` |
| `PasswordUpgradeBadge` | Autorise le réencodage du mot de passe |
| `RememberMeBadge` | Demande le cookie « se souvenir de moi » |
| `CsrfTokenBadge` | Fait vérifier un jeton CSRF |
| `PreAuthenticatedUserBadge` | Utilisateur déjà authentifié en amont : `checkPreAuth()` n'est pas appelé |

`UserBadge` accepte un *user loader* en second argument, quand le chargement ne
doit pas passer par le fournisseur configuré. S'il rend `null`, `UserBadge`
lève `UserNotFoundException`. Un identifiant vide, ou de plus de 4096 octets,
est refusé dès la construction (`BadCredentialsException`).

## Diagnostiquer un badge

**Un badge non résolu fait échouer l'authentification.** Exécuté, un badge
maison dont `isResolved()` rend `false` : **401**, « Authentication failed:
Security badge "App\…\UnresolvedBadge" is not resolved, did you forget to
register the correct listeners? ».

**`RememberMeBadge` est désactivé à sa création.** Il faut à la fois le badge,
la clé `remember_me` sur le pare-feu, et son activation : paramètre
`_remember_me` dans la requête, `always_remember_me`, ou `enable()`. Exécuté :

| Pare-feu | Badge | `_remember_me` | Cookie `REMEMBERME` |
|---|---|---|---|
| sans `remember_me` | `enable()` | — | aucun |
| `remember_me` | non activé | absent | **effacé** |
| `remember_me` | non activé | `on` | posé |
| `remember_me` | `enable()` | absent | posé |
| `always_remember_me: true` | non activé | absent | posé |

## Pièges d'examen

**`supports()` = `null` n'est pas « redemande-moi »** : c'est l'authentification
paresseuse, et seulement sur un pare-feu `lazy` avec état.

**`RememberMeBadge` n'active rien à lui seul** : sans `remember_me` ni
activation, aucun cookie.

**Un badge non résolu est une panne**, pas une permission silencieuse.

**`getUser()` sur un passport sans `UserBadge` lève une `LogicException`** :
erreur de programmation, pas échec de connexion.

**`createToken()`, pas `createAuthenticatedToken()`.**

## Points clés

- `authenticate()` renvoie un `Passport`, jamais un utilisateur.
- `supports()` : `true`, `false`, ou `null` pour l'authentification paresseuse.
- `Passport` exige des credentials ; sans credentials, `SelfValidatingPassport`.
- `UserBadge` est le seul badge obligatoire.
- Un badge exprime une demande ; un badge non résolu fait échouer.

## Sources officielles

- [Custom Authenticators](https://github.com/symfony/symfony-docs/blob/8.0/security/custom_authenticator.rst)
- [Security HTTP 8.0, `AuthenticatorInterface`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Security/Http/Authenticator/AuthenticatorInterface.php)
- [Security HTTP 8.0, `AuthenticatorManager`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Security/Http/Authentication/AuthenticatorManager.php)
- [Security HTTP 8.0, `Passport`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Security/Http/Authenticator/Passport/Passport.php)
- [Security HTTP 8.0, `CheckRememberMeConditionsListener`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Security/Http/EventListener/CheckRememberMeConditionsListener.php)
- [SecurityBundle 8.0, `SecurityExtension`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/SecurityBundle/DependencyInjection/SecurityExtension.php)
