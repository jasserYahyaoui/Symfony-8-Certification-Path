---
id: CRS-6gc45etcssfb
official_item: OIT-s3jh7wg5km19
title: "Authenticators, Passports and Badges"
content_level: DEEP
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/security/custom_authenticator.rst"
    anchor: "security-passport"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Security/Http/Authenticator/AuthenticatorInterface.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "AuthenticatorInterface, lines 36-86"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Security/Http/Authenticator/Passport/Passport.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "Passport::__construct, line 40"
    verified_at: "2026-09-01"
---

## Objectif

Décrire le flux d'authentification de Symfony 8.0 en nommant correctement
chaque objet, et savoir lequel construire selon qu'il y a ou non des
identifiants à vérifier.

## Prérequis

- Firewalls — `must-know` (topic Security, item *Firewalls*).
- Users et user providers — `must-know` (items *Users*, *Providers*).

> **Livraison anticipée.** Cet item est publié avant le reste du topic Security.
> Les deux prérequis ci-dessus **ne sont pas encore disponibles** sur la
> plateforme : ils arrivent avec le lot Security. La page se lit seule, mais le
> parcours de révision recommandé les placera avant elle.

## Explication pour débuter

Trois objets, trois rôles distincts :

- **Authenticator** — le *processus*. Il décide s'il prend la requête en charge
  et en extrait ce qu'il faut.
- **Passport** — le *dossier* qu'il constitue : qui prétend se connecter, et
  avec quelle preuve.
- **Badge** — une *pièce* de ce dossier. Chaque badge porte une information ou
  déclenche une vérification.

L'authenticator ne valide rien lui-même. Il remplit un dossier ; le système de
sécurité vérifie ensuite chaque pièce.

## Explication technique

`AuthenticatorInterface` définit exactement cinq méthodes :

```php
public function supports(Request $request): ?bool;
public function authenticate(Request $request): Passport;
public function createToken(Passport $passport, string $firewallName): TokenInterface;
public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response;
public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response;
```

`supports()` renvoie un `?bool` à trois états, ce qui n'est pas un détail :

- `true` — cet authenticator prend la requête en charge ;
- `false` — il ne la prend pas en charge ;
- `null` — il *pourrait*, mais Symfony doit le rappeler à chaque requête plutôt
  que de mémoriser sa décision.

`authenticate()` ne renvoie pas un utilisateur ni un booléen : il renvoie un
**Passport**. C'est là que se joue la distinction la plus examinée.

## Le flux

```text
Request
  → supports()          l'authenticator prend-il la main ?
  → authenticate()      construit le Passport (UserBadge + credentials + badges)
  → résolution          chaque badge est vérifié ; le user provider charge l'utilisateur
  → createToken()       le Passport résolu devient un TokenInterface
  → onAuthenticationSuccess() / onAuthenticationFailure()
```

## Passport ou SelfValidatingPassport

Le constructeur de `Passport` **exige** des identifiants :

```php
public function __construct(
    UserBadge $userBadge,
    CredentialsInterface $credentials,
    array $badges = [],
)
```

Il n'existe donc pas de `Passport` sans credentials. Quand il n'y a rien à
vérifier — jeton d'API déjà digne de confiance, en-tête signé en amont — c'est
`SelfValidatingPassport` qu'il faut construire :

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
| `CustomCredentials` | Vérification arbitraire fournie par un callable |
| `PasswordUpgradeBadge` | Autorise le réencodage du mot de passe si l'algorithme a changé |
| `RememberMeBadge` | Rend la requête éligible au cookie « se souvenir de moi » |
| `CsrfTokenBadge` | Fait vérifier un jeton CSRF |
| `PreAuthenticatedUserBadge` | Marque l'utilisateur comme déjà authentifié en amont |

`UserBadge` accepte un *user loader* en second argument, lorsque le chargement
ne doit pas passer par le provider configuré :

```php
new UserBadge($email, fn (string $identifier): ?UserInterface
    => $this->repository->findOneByEmail($identifier));
```

## Pièges d'examen

**`RememberMeBadge` n'active rien à lui seul.** Il rend la requête *éligible*.
Sans `remember_me` configuré sur le firewall, aucun cookie n'est émis. Un badge
est une demande, pas une garantie.

**Un badge non résolu fait échouer l'authentification.** Ajouter un
`CsrfTokenBadge` sans que le jeton soit valide interrompt le flux — un badge
oublié dans un refactor est une panne, pas une permission silencieuse.

**`getUser()` lève une `LogicException`**, pas une exception d'authentification,
si le passport n'a pas de `UserBadge`. C'est une erreur de programmation, pas un
échec de connexion.

**`createToken()`, pas `createAuthenticatedToken()`.** L'ancien nom appartient
aux versions antérieures.

## Points clés

- `supports()` renvoie `?bool` ; `null` signifie « redemande-moi ».
- `authenticate()` renvoie un `Passport`, jamais un utilisateur.
- `Passport` exige des credentials ; sans credentials, c'est
  `SelfValidatingPassport`.
- `UserBadge` est le seul badge obligatoire.
- Un badge exprime une demande de vérification ; il ne l'accorde pas.

## Sources officielles

- `security/custom_authenticator.rst` (symfony-docs, branche 8.0, `eea05cb`)
- `AuthenticatorInterface`, `Passport` (symfony, branche 8.0, `6f841c0`)
