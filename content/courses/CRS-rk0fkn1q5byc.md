---
id: CRS-rk0fkn1q5byc
official_item: OIT-zgq6w4jqamvb
title: "Built-in commands"
content_level: MINIMAL
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/console.rst"
    branch: "8.0"
    verified_at: "2026-09-01"
---

## Objectif

Savoir quelles commandes sont déjà là, et laquelle sert à quoi. Aucune liste à
apprendre : `list` la donne.

## Les trouver

```bash
php bin/console                  # équivaut à list
php bin/console list debug       # un espace de noms
php bin/console help cache:clear
```

Les commandes sont groupées par **espace de noms**, séparé par deux-points :
`cache:clear`, `debug:router`, `make:entity`. Le préfixe dit d'où elle vient.

## Les familles

| Famille | Ce qu'elle fait |
|---|---|
| `debug:*` | **inspecter** l'application : conteneur, routes, événements, Twig, configuration |
| `cache:*` | vider et préchauffer le cache |
| `lint:*` | valider une syntaxe — YAML, Twig, XLIFF, conteneur |
| `secrets:*` | gérer le coffre de secrets |
| `make:*` | générer du code, si MakerBundle est installé |

Les `debug:*` sont celles qui reviennent le plus : elles répondent à « qu'est-ce
que Symfony a réellement compris de ma configuration ? », question qu'aucune
relecture de fichier ne tranche.

## L'abréviation

Un nom peut être abrégé tant qu'il reste **non ambigu** : `c:c` suffit pour
`cache:clear`. Une abréviation ambiguë provoque une erreur qui liste les
candidats.

## Points clés

- `list` et `help` sont les deux commandes de découverte.
- Espaces de noms : `debug:*` inspecte, `cache:*` vide, `lint:*` valide.
- Un nom s'abrège tant qu'il n'est pas ambigu.

## Sources officielles

- [Console Commands](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/console.rst)
