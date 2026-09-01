---
id: CRS-yvhg325ax38y
official_item: OIT-xh63g15rz6n3
title: "Configuration"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/security.rst"
    branch: "8.0"
    symbol_or_lines: "security.yaml configuration tree"
    verified_at: "2026-09-01"
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

La lecture se fait dans cet ordre parce qu'il suit une dépendance : les
utilisateurs viennent d'un fournisseur, un pare-feu utilise un fournisseur, et
`access_control` s'applique après qu'un pare-feu a authentifié.

## Ce qui décide de quoi

| Section | Question |
|---|---|
| `password_hashers` | comment un mot de passe est stocké et vérifié |
| `providers` | d'où l'utilisateur est chargé |
| `firewalls` | **comment** on prouve son identité |
| `access_control` | **ce qu'il faut** pour atteindre une URL |
| `role_hierarchy` | quels rôles en impliquent d'autres |

## Les deux règles d'ordre

Elles se ressemblent et se confondent :

- dans `firewalls`, le **premier `pattern`** correspondant prend la requête ;
- dans `access_control`, la **première règle** correspondante s'applique.

Dans les deux cas, les entrées suivantes sont ignorées, et l'entrée sans motif
doit venir en dernier.

## Vérifier

```bash
php bin/console debug:config security
php bin/console debug:firewall main
```

`debug:config` affiche la configuration **résolue**, valeurs par défaut
comprises — ce qui répond mieux que la relecture du fichier.

## Pièges d'examen

**`firewalls` et `access_control` ont chacun leur règle de première
correspondance**, et ce sont deux décisions différentes.

**Un pare-feu `security: false` ne désactive pas `access_control`** : il
désactive l'authentification pour ces URL.

**Plusieurs fournisseurs imposent de nommer celui de chaque pare-feu.**

## Points clés

- Quatre sections : hachage, fournisseurs, pare-feux, contrôle d'accès.
- Pare-feu = comment on s'authentifie ; `access_control` = ce qu'il faut pour entrer.
- Première correspondance dans les deux listes ; le fourre-tout en dernier.
- `debug:config security` montre la configuration réellement appliquée.

## Sources officielles

- [Security](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/security.rst)
