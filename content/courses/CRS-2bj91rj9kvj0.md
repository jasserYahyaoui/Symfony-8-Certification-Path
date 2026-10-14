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

Savoir ce que TwigBundle ajoute à Twig, où il cherche les templates, et quelles
options de configuration comptent. La distinction composant / bridge / bundle
est traitée dans le lot Symfony Architecture.

## Trois packages, trois roles

| Package | Role |
|---|---|
| `twig/twig` | le moteur : la syntaxe, le compilateur, les filters de base |
| `symfony/twig-bridge` | les extensions Twig qui exposent les composants Symfony — `path()`, `trans`, le rendu de form |
| `symfony/twig-bundle` | l'intégration dans le framework : configuration, paths, services |

`composer require symfony/twig-bundle` installe les trois : le bundle exige
`symfony/twig-bridge`, qui exige `twig/twig` (`^3.21|^4.0` en 8.0). C'est le
bundle qui rend Twig configurable depuis `config/packages/twig.yaml`.

## Où vivent les templates

Par défaut dans `templates/`, à la racine du projet — `twig.default_path` vaut
`%kernel.project_dir%/templates`. Rendre `product/index.html.twig` lit donc
`templates/product/index.html.twig`.

`twig.paths` **ajoute** des directories. La key est le path, la valeur un
namespace facultatif : un path sans namespace rejoint l'espace
principal, un path nommé se référence en `@Nom/...`.

Un bundle expose ses templates sous `@NomDuBundle/...` — pour `AcmeBlogBundle`,
`@AcmeBlog/user/profile.html.twig`, le suffixe `Bundle` étant retiré.

## Surcharger le template d'un bundle

`TwigExtension` (8.0) enregistre, pour chaque bundle, deux emplacements dans cet
ordre :

1. `templates/bundles/AcmeBlogBundle/`, s'il existe — le nom complet, suffixe
   compris ;
2. le directory du bundle, `Resources/views/` ou `templates/`.

Le premier gagne : poser `templates/bundles/AcmeBlogBundle/user/profile.html.twig`
remplace le template du bundle sans toucher à son code. Le directory d'origine
reste accessible sous l'espace **`!AcmeBlog`**, ce qui permet à la surcharge
d'étendre l'original : `{% extends '@!AcmeBlog/user/profile.html.twig' %}`.

## Les options qui changent le comportement

| Option | Effet | Défaut (8.0) |
|---|---|---|
| `default_path` | déplace le directory des templates | `templates/` |
| `paths` | ajoute des directories, nommés ou non | aucun |
| `globals` | déclare des variables disponibles dans **tous** les templates | aucune |
| `form_themes` | choisit le thème de rendu des forms | `form_div_layout.html.twig` |
| `strict_variables` | fait échouer l'accès à une variable inexistante au lieu de retourner `null` | `%kernel.debug%` |
| `cache` | compile les templates en PHP dans le cache | activé |

`strict_variables` mérite d'être connue : elle décide si une faute de frappe
dans un nom de variable passe inaperçue ou lève une `Twig\Error\RuntimeError`.
Sa default value suit `kernel.debug` : **stricte en `dev`, permissive en
`prod`**. Une erreur visible en développement peut donc rendre une string vide
en production.

Dans `globals`, une valeur qui commence par `@` désigne un **service** ;
`@@` échappe le caractère et produit une string qui commence par `@`.

## Les commands

- `php bin/console debug:twig` — filters, functions, tests, globales et paths
  de templates ; avec un nom de template en argument, le file qui le fournit ;
  `--filter` restreint la liste ;
- `php bin/console lint:twig` — vérifie la syntaxe sans rendre ;
  `--show-deprecations` traite les deprecations comme des erreurs.

## Pièges d'examen

**Trois packages, un seul à installer.** Le moteur, le pont et le bundle sont
distincts : c'est le bundle qui tire les deux autres, et c'est lui seul qui rend
Twig configurable.

**Le nom d'espace d'un bundle perd son suffixe ; le directory de surcharge le
garde.** `@AcmeBlog/...` d'un côté, `templates/bundles/AcmeBlogBundle/` de
l'autre.

**`strict_variables` n'a pas la même valeur partout.** Par défaut elle vaut
`kernel.debug`.

**Le directory par défaut se change, mais on peut aussi en ajouter.** Ce sont
deux options différentes : l'une remplace, l'autre complète.

## Points clés

- Moteur, bridge et bundle sont trois packages distincts ; le bundle configure.
- Templates dans `templates/` par défaut, `twig.default_path` pour en changer.
- `twig.paths` ajoute des directories, avec ou sans namespace.
- Surcharge : `templates/bundles/NomDuBundle/` ; l'original sous `@!Nom`.
- `strict_variables` : `%kernel.debug%` par défaut.
- `debug:twig` inventorie, `lint:twig` valide.

## Sources officielles

- [Templates, emplacements et namespaces](https://github.com/symfony/symfony-docs/blob/8.0/templates.rst)
- [TwigBundle, `Configuration`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/TwigBundle/DependencyInjection/Configuration.php) et [`TwigExtension`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/TwigBundle/DependencyInjection/TwigExtension.php)
- [`debug:twig`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/Command/DebugCommand.php) et [`lint:twig`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/Command/LintCommand.php)
