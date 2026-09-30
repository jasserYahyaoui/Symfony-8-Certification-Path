---
id: CRS-x5frtpg07mmd
official_item: OIT-rwa6m06crs1h
title: "Firewalls"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-30"
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
---

## Objectif

Comprendre ce qu'un pare-feu délimite, et la règle d'ordre qui décide lequel
s'applique.

## Un seul pare-feu par requête

C'est la règle centrale. Les pare-feux sont essayés **dans l'ordre de
déclaration**, et **le premier dont le `pattern` correspond** prend la requête.
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

Deux conséquences pratiques. Le pare-feu **sans `pattern` correspond à tout** et
doit donc être déclaré **en dernier** — placé en premier, il capte tout sans
erreur de configuration. Et un pare-feu `dev` mal placé désactiverait la
sécurité de toute l'application.

C'est la différence avec `access_control` : les deux prennent la première
correspondance, mais un pare-feu définit **comment on s'authentifie**, tandis
qu'`access_control` définit **ce qu'il faut pour entrer** — et c'est le pare-feu
qui l'applique.

## Ce qu'un pare-feu porte

- ses **authenticators** — formulaire de connexion, jeton, `remember_me` ;
- son **fournisseur** d'utilisateurs ;
- son **point d'entrée**, ce qui se passe quand un anonyme doit s'identifier ;
- son **user checker** ;
- son caractère `stateless` ou non ;
- l'**`AccessListener`**, qui applique `access_control`.

Chaque pare-feu a **son propre contexte de session** : par défaut, se connecter
sur l'un ne connecte pas sur l'autre. La clé `context` permet de partager
explicitement l'authentification entre deux pare-feux. Exécuté avec
SecurityBundle 8.0.15, `alice` connectée sur `main`, puis `/admin/whoami` sur un
pare-feu `admin` avec le même cookie :

| Configuration | Utilisateur vu sur `admin` |
|---|---|
| deux pare-feux, sans `context` | `null` |
| `context: shared` sur les deux | `alice` |

## `lazy`

`lazy: true` diffère la lecture du jeton et le chargement de l'utilisateur
jusqu'à ce que quelque chose les demande. Exécuté, avec un fournisseur qui
compte ses appels et une page `/plain` qui n'utilise pas l'utilisateur, requête
portant le cookie d'`alice` :

| Pare-feu | Appels au fournisseur sur `/plain` |
|---|---|
| `lazy: true` | aucun |
| sans `lazy` | `refreshUser(alice)` à chaque requête |

La documentation ajoute que le mode paresseux évite d'ouvrir la session quand
aucune autorisation n'est demandée, ce qui garde la requête cachable. Dans cette
exécution, l'en-tête `Cache-Control` de `/plain` est le même dans les deux
modes ; l'effet mesuré est l'appel au fournisseur évité.

## `security: false`

Ce pare-feu n'a **aucun écouteur** : ni authentification, ni `AccessListener`.
C'est donc bien « tout le monde passe » — `access_control` ne s'y applique pas.
Exécuté : une règle `ROLE_ADMIN` sur les URL d'un tel pare-feu laisse un anonyme
obtenir 200. Réservé aux outils de développement et aux fichiers statiques.

## Pièges d'examen

**Un seul pare-feu s'applique**, le premier qui correspond ; les autres sont
ignorés.

**Un pare-feu sans `pattern` attrape tout** et se déclare en dernier.

**Deux pare-feux n'échangent pas leur authentification** sans `context` commun.

**`security: false` désactive aussi `access_control`** pour ses URL.

**`lazy` épargne le rechargement de l'utilisateur** sur les pages qui ne s'en
servent pas.

## Points clés

- Premier `pattern` correspondant gagne ; sans `pattern`, il attrape tout.
- Le pare-feu porte authenticators, fournisseur, point d'entrée, user checker et
  `access_control`.
- Contexte de session par pare-feu, partageable par `context`.
- `lazy` diffère le jeton et l'utilisateur jusqu'à ce qu'on les demande.

## Sources officielles

- [Security, « Firewalls »](https://github.com/symfony/symfony-docs/blob/8.0/security.rst)
- [SecurityBundle 8.0, `SecurityExtension`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/SecurityBundle/DependencyInjection/SecurityExtension.php)
- [Security HTTP 8.0, `ContextListener`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Security/Http/Firewall/ContextListener.php)
