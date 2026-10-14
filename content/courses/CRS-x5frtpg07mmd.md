---
id: CRS-x5frtpg07mmd
official_item: OIT-rwa6m06crs1h
title: "Firewalls"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-02"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/security.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/security.rst"
    symbol_or_lines: 'The Firewall — "Only one firewall is active on each request: Symfony uses the pattern key"; lazy anonymous mode tip'
    branch: "8.0"
    verified_at: "2026-09-30"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/SecurityBundle/DependencyInjection/SecurityExtension.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/SecurityBundle/DependencyInjection/SecurityExtension.php"
    symbol_or_lines: "createFirewall() — security false returns an empty listener list; context key"
    branch: "8.0"
    verified_at: "2026-09-30"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Security/Http/Firewall/ContextListener.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Security/Http/Firewall/ContextListener.php"
    symbol_or_lines: "refreshUser() — the provider call a lazy firewall avoids"
    branch: "8.0"
    verified_at: "2026-09-30"
outcome_sections:
  OUT-xnh28hz1zchd: "Un seul firewall par Request"
  OUT-j6xr27ajqs3e: "Un seul firewall par Request"
  OUT-6dsw1jm1j7df: "Ce qu'un firewall porte"
  OUT-psq7sd74p14g: "`lazy`"
---

## Objectif

Comprendre ce qu'un firewall délimite, et la règle d'ordre qui décide lequel
s'applique.

## Un seul firewall par Request

C'est la règle centrale. Les firewalls sont essayés **dans l'ordre de
déclaration**, et **le premier dont le `pattern` correspond** prend la Request.
Les suivants ne sont jamais consultés.

```yaml
security:
    firewalls:
        dev:
            pattern: ^/(_profiler|_wdt|assets)/
            security: false
        api:
            pattern: ^/api
            stateless: true
        main:
            lazy: true
```

Deux conséquences pratiques. Le firewall **sans `pattern` correspond à tout** et
doit donc être déclaré **en dernier** — placé en premier, il capte tout sans
erreur de configuration. Et un firewall `dev` mal placé désactiverait la
security de toute l'application.

C'est la différence avec `access_control` : les deux prennent la première
correspondance, mais un firewall définit **comment on s'authentifie**, tandis
qu'`access_control` définit **ce qu'il faut pour entrer** — et c'est le firewall
qui l'applique.

## Ce qu'un firewall porte

- ses **authenticators** — form de connexion, token, `remember_me` ;
- son **provider** de users ;
- son **entry point**, ce qui se passe quand un anonyme doit s'identifier ;
- son **user checker** ;
- son caractère `stateless` ou non ;
- l'**`AccessListener`**, qui applique `access_control`.

Chaque firewall a **son propre contexte de session** : par défaut, se connecter
sur l'un ne connecte pas sur l'autre. La key `context` permet de partager
explicitement l'authentication entre deux firewalls. Exécuté avec
SecurityBundle 8.0.15, `alice` connectée sur `main`, puis `/admin/whoami` sur un
firewall `admin` avec le même cookie :

| Configuration | User vu sur `admin` |
|---|---|
| deux firewalls, sans `context` | `null` |
| `context: shared` sur les deux | `alice` |

## `lazy`

`lazy: true` diffère la lecture du token et le chargement du user
jusqu'à ce que quelque chose les demande. Exécuté, avec un provider qui
compte ses appels et une page `/plain` qui n'utilise pas le user, Request
portant le cookie d'`alice` :

| Firewall | Appels au provider sur `/plain` |
|---|---|
| `lazy: true` | aucun |
| sans `lazy` | `refreshUser(alice)` à chaque Request |

La documentation ajoute que le mode paresseux évite d'ouvrir la session quand
aucune authorization n'est demandée, ce qui garde la Request cachable. Dans cette
exécution, le header `Cache-Control` de `/plain` est le même dans les deux
modes ; l'effet mesuré est l'appel au provider évité.

**`lazy` est ignoré sur un firewall `stateless`.** Lu dans `SecurityExtension`
(8.0) : `$isLazy = !$firewall['stateless'] && $firewall['lazy']`. Exécuté avec
`lazy: true` : le contexte du firewall est un `LazyFirewallContext` avec état,
un `FirewallContext` ordinaire une fois `stateless: true` ajouté.

## `security: false`

Ce firewall n'a **aucun listener** : ni authentication, ni `AccessListener`.
C'est donc bien « tout le monde passe » — `access_control` ne s'y applique pas.
Exécuté : une règle `ROLE_ADMIN` sur les URL d'un tel firewall laisse un anonyme
obtenir 200. Réservé aux outils de développement et aux files statiques.

## Pièges d'examen

**Un seul firewall s'applique**, le premier qui correspond ; les autres sont
ignorés.

**Un firewall sans `pattern` attrape tout** et se déclare en dernier.

**Deux firewalls n'échangent pas leur authentication** sans `context` commun.

**`security: false` désactive aussi `access_control`** pour ses URL.

**`lazy` épargne le rechargement du user** sur les pages qui ne s'en
servent pas — et n'a aucun effet sur un firewall `stateless`.

## Points clés

- Premier `pattern` correspondant gagne ; sans `pattern`, il attrape tout.
- Le firewall porte authenticators, provider, entry point, user checker et
  `access_control`.
- Contexte de session par firewall, partageable par `context`.
- `lazy` diffère le token et le user jusqu'à ce qu'on les demande.

## Sources officielles

- [Security, « Firewalls »](https://github.com/symfony/symfony-docs/blob/8.0/security.rst)
- [SecurityBundle 8.0, `SecurityExtension`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/SecurityBundle/DependencyInjection/SecurityExtension.php)
- [Security HTTP 8.0, `ContextListener`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Security/Http/Firewall/ContextListener.php)
