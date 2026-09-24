---
id: CRS-2bj91rj9kvj0
official_item: OIT-b9x8az2bx4t8
title: "TwigBundle"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/templates.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/templates.rst"
    anchor: "template-locations-and-namespaces"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/TwigBundle/DependencyInjection/Configuration.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/TwigBundle/DependencyInjection/Configuration.php"
    symbol_or_lines: "strict_variables, default_path, paths, globals, form_themes"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/TwigBundle/DependencyInjection/TwigExtension.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/TwigBundle/DependencyInjection/TwigExtension.php"
    symbol_or_lines: "getBundleTemplatePaths, normalizeBundleName"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bridge/Twig/Command/DebugCommand.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/Command/DebugCommand.php"
    symbol_or_lines: "debug:twig"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bridge/Twig/Command/LintCommand.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/Command/LintCommand.php"
    symbol_or_lines: "lint:twig"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
---

## Objectif

Savoir ce que TwigBundle ajoute à Twig, où il cherche les gabarits, et quelles
options de configuration comptent. La distinction composant / bridge / bundle
est traitée dans le lot Symfony Architecture.

## Trois paquets, trois rôles

| Paquet | Rôle |
|---|---|
| `twig/twig` | le moteur : la syntaxe, le compilateur, les filtres de base |
| `symfony/twig-bridge` | les extensions Twig qui exposent les composants Symfony — `path()`, `trans`, le rendu de formulaire |
| `symfony/twig-bundle` | l'intégration dans le framework : configuration, chemins, services |

`composer require symfony/twig-bundle` installe les trois : le bundle exige
`symfony/twig-bridge`, qui exige `twig/twig` (`^3.21|^4.0` en 8.0). C'est le
bundle qui rend Twig configurable depuis `config/packages/twig.yaml`.

## Où vivent les gabarits

Par défaut dans `templates/`, à la racine du projet — `twig.default_path` vaut
`%kernel.project_dir%/templates`. Rendre `product/index.html.twig` lit donc
`templates/product/index.html.twig`.

`twig.paths` **ajoute** des répertoires. La clé est le chemin, la valeur un
espace de noms facultatif : un chemin sans espace de noms rejoint l'espace
principal, un chemin nommé se référence en `@Nom/...`.

Un bundle expose ses gabarits sous `@NomDuBundle/...` — pour `AcmeBlogBundle`,
`@AcmeBlog/user/profile.html.twig`, le suffixe `Bundle` étant retiré.

## Surcharger le gabarit d'un bundle

`TwigExtension` (8.0) enregistre, pour chaque bundle, deux emplacements dans cet
ordre :

1. `templates/bundles/AcmeBlogBundle/`, s'il existe — le nom complet, suffixe
   compris ;
2. le répertoire du bundle, `Resources/views/` ou `templates/`.

Le premier gagne : poser `templates/bundles/AcmeBlogBundle/user/profile.html.twig`
remplace le gabarit du bundle sans toucher à son code. Le répertoire d'origine
reste accessible sous l'espace **`!AcmeBlog`**, ce qui permet à la surcharge
d'étendre l'original : `{% extends '@!AcmeBlog/user/profile.html.twig' %}`.

## Les options qui changent le comportement

| Option | Effet | Défaut (8.0) |
|---|---|---|
| `default_path` | déplace le répertoire des gabarits | `templates/` |
| `paths` | ajoute des répertoires, nommés ou non | aucun |
| `globals` | déclare des variables disponibles dans **tous** les gabarits | aucune |
| `form_themes` | choisit le thème de rendu des formulaires | `form_div_layout.html.twig` |
| `strict_variables` | fait échouer l'accès à une variable inexistante au lieu de retourner `null` | `%kernel.debug%` |
| `cache` | compile les gabarits en PHP dans le cache | activé |

`strict_variables` mérite d'être connue : elle décide si une faute de frappe
dans un nom de variable passe inaperçue ou lève une `Twig\Error\RuntimeError`.
Sa valeur par défaut suit `kernel.debug` : **stricte en `dev`, permissive en
`prod`**. Une erreur visible en développement peut donc rendre une chaîne vide
en production.

Dans `globals`, une valeur qui commence par `@` désigne un **service** ;
`@@` échappe le caractère et produit une chaîne qui commence par `@`.

## Les commandes

- `php bin/console debug:twig` — filtres, fonctions, tests, globales et chemins
  de gabarits ; avec un nom de gabarit en argument, le fichier qui le fournit ;
  `--filter` restreint la liste ;
- `php bin/console lint:twig` — vérifie la syntaxe sans rendre ;
  `--show-deprecations` traite les dépréciations comme des erreurs.

## Pièges d'examen

**Trois paquets, un seul à installer.** Le moteur, le pont et le bundle sont
distincts : c'est le bundle qui tire les deux autres, et c'est lui seul qui rend
Twig configurable.

**Le nom d'espace d'un bundle perd son suffixe ; le répertoire de surcharge le
garde.** `@AcmeBlog/...` d'un côté, `templates/bundles/AcmeBlogBundle/` de
l'autre.

**`strict_variables` n'a pas la même valeur partout.** Par défaut elle vaut
`kernel.debug`.

**Le répertoire par défaut se change, mais on peut aussi en ajouter.** Ce sont
deux options différentes : l'une remplace, l'autre complète.

## Points clés

- Moteur, bridge et bundle sont trois paquets distincts ; le bundle configure.
- Gabarits dans `templates/` par défaut, `twig.default_path` pour en changer.
- `twig.paths` ajoute des répertoires, avec ou sans espace de noms.
- Surcharge : `templates/bundles/NomDuBundle/` ; l'original sous `@!Nom`.
- `strict_variables` : `%kernel.debug%` par défaut.
- `debug:twig` inventorie, `lint:twig` valide.

## Sources officielles

- [Templates, emplacements et espaces de noms](https://github.com/symfony/symfony-docs/blob/8.0/templates.rst)
- [TwigBundle, `Configuration`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/TwigBundle/DependencyInjection/Configuration.php) et [`TwigExtension`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/TwigBundle/DependencyInjection/TwigExtension.php)
- [`debug:twig`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/Command/DebugCommand.php) et [`lint:twig`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/Command/LintCommand.php)
