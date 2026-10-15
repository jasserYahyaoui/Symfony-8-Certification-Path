---
id: CRS-yvhg325ax38y
official_item: OIT-xh63g15rz6n3
title: "Configuration"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-30"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/security.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/security.rst"
    branch: "8.0"
    symbol_or_lines: "security.yaml configuration tree"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/SecurityBundle/DependencyInjection/SecurityExtension.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/SecurityBundle/DependencyInjection/SecurityExtension.php"
    symbol_or_lines: "createFirewall() — security false returns an empty listener list; security.access_listener"
    branch: "8.0"
    verified_at: "2026-09-30"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/reference/configuration/security.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/reference/configuration/security.rst"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    symbol_or_lines: "expose_security_errors"
    verified_at: "2026-10-15"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Security/Http/Authentication/AuthenticatorManager.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Security/Http/Authentication/AuthenticatorManager.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "isSensitiveException(); a sensitive exception becomes BadCredentialsException"
    verified_at: "2026-10-15"
outcome_sections:
  OUT-mrdkwy4adq6q: "Ce qui décide de quoi"
  OUT-wnw02nsyvmyg: "Les deux règles d'ordre"
  OUT-d69mg4gn25dt: "`security: false` éteint tout"
  OUT-7x0agpczf39n: "Vérifier"
  OUT-h0ct6qp4249p: "Ce que l'échec révèle"
---

## Objectif

Lire un `security.yaml` et savoir quelle section répond de quoi. Le détail de
chaque section a son propre item.

## Les quatre sections

```yaml
security:
    password_hashers:                 # comment les mots de passe sont hachés
        App\Entity\User: { algorithm: auto }

    providers:                        # d'où viennent les utilisateurs
        app_users:
            entity: { class: App\Entity\User, property: email }

    firewalls:                        # comment on s'authentifie
        dev:  { pattern: ^/(_profiler|_wdt)/, security: false }
        main: { lazy: true, provider: app_users, form_login: ~ }

    access_control:                   # ce qu'il faut pour entrer
        - { path: ^/admin, roles: ROLE_ADMIN }
```

La lecture se fait dans cet ordre parce qu'il suit une dependency : les
users viennent d'un provider, un firewall utilise un provider, et
`access_control` s'applique **à l'intérieur** du firewall qui a pris la Request.

## Ce qui décide de quoi

| Section | Question |
|---|---|
| `password_hashers` | comment un password est stocké et vérifié |
| `providers` | d'où le user est chargé |
| `firewalls` | **comment** on prouve son identité |
| `access_control` | **ce qu'il faut** pour atteindre une URL |
| `role_hierarchy` | quels roles en impliquent d'autres |

## Les deux règles d'ordre

Elles se ressemblent et se confondent :

- dans `firewalls`, le **premier `pattern`** correspondant prend la Request ;
- dans `access_control`, la **première règle** correspondante s'applique.

Dans les deux cas, les entrées suivantes sont ignorées, et l'entrée sans pattern
doit venir en dernier. Exécuté avec SecurityBundle 8.0.15 :

| Configuration | Résultat |
|---|---|
| `main` sans pattern déclaré **avant** `api` (`^/api`, sans état) | aucune erreur ; `/api/fw` est pris par `main`, qui pose un cookie de session |
| `^/rules` en `PUBLIC_ACCESS` avant `^/rules/a` en `ROLE_ADMIN` | `/rules/a` répond 200 à un anonyme |

Aucune des deux erreurs d'ordre n'est signalée : l'effet est silencieux.

## `security: false` éteint tout

Un firewall `security: false` n'a **aucun listener** — ni authentication, ni
access control. `SecurityExtension` le traite à part et rend une liste
de listeners vide ; or c'est l'`AccessListener` du firewall qui applique
`access_control`. Exécuté : firewall `free` (`^/free`, `security: false`) et une
règle `access_control` exigeant `ROLE_ADMIN` sur `^/free` — `/free/page` répond
**200** à un anonyme, sans token.

La règle est donc morte. C'est l'usage du firewall `dev` : laisser passer les
outils de développement, pas les protéger.

## Plusieurs providers

Dès qu'il y en a deux, chaque firewall doit nommer le sien. Exécuté, sans key
`provider` : « Not configuring explicitly the provider for the "http_basic"
authenticator on "main" firewall is ambiguous as there is more than one
registered provider. »

## Ce que l'échec révèle

Un message « ce user n'existe pas » dit à un attaquant quels identifiers sont
valides. L'option **`security.expose_security_errors`** — à la racine de
`security`, pas dans un firewall — règle ce qui sort ;
`hide_user_not_found`, son ancêtre, n'existe plus en 8.0.

| Valeur | User inconnu | Statut du compte (bloqué, expiré) |
|---|---|---|
| `none` (défaut) | masqué | masqué |
| `account_status` | masqué | montré, si le password était correct (documentation 8.0) |
| `all` | montré | montré |

Lu dans `AuthenticatorManager::isSensitiveException()` (8.0) : une exception
jugée sensible est remplacée par `BadCredentialsException('Bad credentials.')`
avant d'atteindre l'authenticator. Seules ces deux familles sont jamais
masquées — `UserNotFoundException` et `AccountStatusException`, sauf sa
variante `CustomUserMessageAccountStatusException` ; un token CSRF invalide,
par exemple, reste visible.

## Vérifier

```bash
php bin/console debug:config security
php bin/console debug:firewall main
```

`debug:config` affiche la configuration **résolue**, default values
comprises — ce qui répond mieux que la relecture du file. `debug:firewall`
liste les firewalls et détaille celui qu'on nomme.

## Pièges d'examen

**`firewalls` et `access_control` ont chacun leur règle de première
correspondance**, et ce sont deux décisions différentes.

**Un firewall `security: false` désactive aussi `access_control`** pour ses URL :
aucun listener, aucune règle appliquée.

**Un firewall sans pattern déclaré en premier capte tout, sans erreur.**

**Plusieurs providers imposent de nommer celui de chaque firewall.**

**Par défaut, un user inconnu et un mauvais password donnent la même erreur** :
`expose_security_errors` vaut `none`.

## Points clés

- Quatre sections : hash, providers, firewalls, access control.
- Firewall = comment on s'authentifie ; `access_control` = ce qu'il faut pour
  entrer, appliqué par le firewall.
- Première correspondance dans les deux listes ; le fourre-tout en dernier.
- `security: false` : ni authentication ni `access_control`.
- `debug:config security` montre la configuration réellement appliquée.
- `expose_security_errors` : `none` par défaut, `account_status`, `all`.

## Sources officielles

- [Security](https://github.com/symfony/symfony-docs/blob/8.0/security.rst)
- [SecurityBundle 8.0, `SecurityExtension`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/SecurityBundle/DependencyInjection/SecurityExtension.php)
