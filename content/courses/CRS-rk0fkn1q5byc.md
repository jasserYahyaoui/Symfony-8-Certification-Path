---
id: CRS-rk0fkn1q5byc
official_item: OIT-zgq6w4jqamvb
title: "Built-in commands"
content_level: MINIMAL
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-02"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/console.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/console.rst"
    symbol_or_lines: 'Console Commands — "The Symfony framework provides lots of commands through the bin/console script"; "the list command to view all available commands in the application"'
    branch: "8.0"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Console/Application.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Console/Application.php"
    branch: "8.0"
    symbol_or_lines: "find() — abbreviation per segment and ambiguity; default command list"
    verified_at: "2026-10-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Runtime/SymfonyRuntime.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Runtime/SymfonyRuntime.php"
    branch: "8.0"
    symbol_or_lines: "APP_ENV defaults to dev; --env|-e and --no-debug"
    verified_at: "2026-10-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/FrameworkExtension.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/FrameworkExtension.php"
    branch: "8.0"
    symbol_or_lines: "console.command.xliff_lint removed without the Translation component"
    verified_at: "2026-10-02"
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

`list` est la **commande par défaut** de l'`Application` : `bin/console` sans
argument l'exécute — il n'affiche pas une aide et ne signale aucune erreur.

Les commandes sont groupées par **espace de noms**, séparé par deux-points :
`cache:clear`, `debug:router`. Le préfixe dit **sur quoi** la commande agit —
le cache, le routage, le conteneur —, pas le bundle qui l'a enregistrée.

## Les familles

| Famille | Ce qu'elle fait |
|---|---|
| `debug:*` | **inspecter** l'application : conteneur, routes, événements, Twig, configuration |
| `cache:*` | vider, préchauffer, gérer les pools |
| `lint:*` | valider une syntaxe — YAML, Twig, XLIFF, conteneur |
| `secrets:*` | gérer le coffre de secrets |
| `make:*` | générer du code, si MakerBundle est installé |

La liste **dépend des composants installés**. Relevé sur une application
FrameworkBundle 8.0.15 avec SecurityBundle, TwigBundle et Messenger : 9
`debug:*` (dont `debug:firewall` et `debug:messenger`), 7 `cache:*`, 3 `lint:*`
— `lint:container`, `lint:twig`, `lint:yaml`, pas de `lint:xliff` sans le
composant Translation —, et cinq commandes sans espace de noms : `about`,
`completion`, `help`, `list`, `_complete`.

Les `debug:*` sont celles qui reviennent le plus : elles répondent à « qu'est-ce
que Symfony a réellement compris de ma configuration ? », question qu'aucune
relecture de fichier ne tranche.

## L'abréviation

Un nom peut être abrégé **segment par segment** tant qu'il reste **non
ambigu**. Exécuté sur la même application :

| Saisie | Résultat |
|---|---|
| `c:c` | `cache:clear` |
| `d:r` | `debug:router` |
| `l:y` | `lint:yaml` |
| `deb:c` | erreur : « Command "deb:c" is ambiguous. Did you mean one of these? » — `debug:config`, `debug:container` |

## L'environnement

Une commande s'exécute dans l'environnement de `APP_ENV`, `dev` par défaut — lu
dans `SymfonyRuntime` (8.0). Les options globales `--env` (`-e`) et
`--no-debug` le changent pour un lancement :

```bash
php bin/console cache:clear --env=prod
```

## Pièges d'examen

**Une commande vise `dev` sans `APP_ENV` ni `--env`.** Un `cache:clear` lancé
sans rien ne vide pas le cache de `prod`.

**`debug:*` lit le conteneur compilé, pas les fichiers de configuration.** C'est
précisément son intérêt : il montre ce que Symfony a retenu, pas ce qu'on croit
avoir écrit.

**`bin/console` sans argument exécute `list`.**

**Le préfixe n'indique pas le bundle d'origine** : il nomme le domaine.

## Points clés

- `list` et `help` sont les deux commandes de découverte ; `list` est la
  commande par défaut.
- Espaces de noms : `debug:*` inspecte, `cache:*` vide, `lint:*` valide ; la
  liste dépend des composants installés.
- Un nom s'abrège par segment tant qu'il n'est pas ambigu.
- `--env` et `--no-debug` changent l'environnement d'un lancement.

## Sources officielles

- [Console Commands](https://github.com/symfony/symfony-docs/blob/8.0/console.rst)
- [`Application`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Console/Application.php)
- [`SymfonyRuntime`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Runtime/SymfonyRuntime.php)
