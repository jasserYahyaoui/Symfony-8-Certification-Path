---
id: CRS-x5frtpg07mmd
official_item: OIT-rwa6m06crs1h
title: "Firewalls"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/security.rst"
    branch: "8.0"
    verified_at: "2026-09-01"
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
doit donc être déclaré **en dernier**. Et un pare-feu `dev` mal placé
désactiverait la sécurité de toute l'application.

C'est la différence avec `access_control` : les deux prennent la première
correspondance, mais un pare-feu définit **comment on s'authentifie**, tandis
qu'`access_control` définit **ce qu'il faut pour entrer**.

## Ce qu'un pare-feu porte

- ses **authenticators** — formulaire de connexion, jeton, `remember_me` ;
- son **fournisseur** d'utilisateurs ;
- son **point d'entrée**, ce qui se passe quand un anonyme doit s'identifier ;
- son **user checker** ;
- son caractère `stateless` ou non.

Chaque pare-feu a **son propre contexte de session** : par défaut, se connecter
sur l'un ne connecte pas sur l'autre. La clé `context` permet de partager
explicitement l'authentification entre deux pare-feux.

## `lazy`

`lazy: true` diffère le chargement de l'utilisateur jusqu'à ce que quelque chose
le demande. Une page publique n'ouvre alors pas la session, ce qui la garde
cachable.

## `security: false`

Ce pare-feu ne fait aucune authentification. Ce n'est pas « tout le monde
passe » au sens de l'autorisation : c'est « le système de sécurité ne s'occupe
pas de ces URL ».

## Pièges d'examen

**Un seul pare-feu s'applique**, le premier qui correspond ; les autres sont
ignorés.

**Un pare-feu sans `pattern` attrape tout** et se déclare en dernier.

**Deux pare-feux n'échangent pas leur authentification** sans `context` commun.

## Points clés

- Premier `pattern` correspondant gagne ; sans `pattern`, il attrape tout.
- Le pare-feu porte authenticators, fournisseur, point d'entrée, user checker.
- Contexte de session par pare-feu, partageable par `context`.
- `lazy` évite d'ouvrir la session sur les pages publiques.

## Sources officielles

- [Security, « Firewalls »](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/security.rst)
