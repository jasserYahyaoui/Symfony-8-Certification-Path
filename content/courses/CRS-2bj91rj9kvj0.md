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
    anchor: "template-locations-and-namespaces"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
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

`composer require symfony/twig-bundle` installe les trois. C'est le bundle qui
rend Twig configurable depuis `config/packages/twig.yaml`.

## Où vivent les gabarits

Par défaut dans `templates/`, à la racine du projet. Rendre
`product/index.html.twig` lit donc `templates/product/index.html.twig`.

Ce répertoire se change par `twig.default_path`, et on peut en **ajouter**
d'autres par `twig.paths`, en leur donnant un espace de noms. Un bundle expose
ses propres gabarits sous `@NomDuBundle/...` — pour `AcmeBlogBundle`,
`@AcmeBlog/user/profile.html.twig`, le suffixe `Bundle` étant retiré.

## Les options qui changent le comportement

| Option | Effet |
|---|---|
| `default_path` | déplace le répertoire des gabarits |
| `paths` | ajoute des répertoires, avec un espace de noms |
| `globals` | déclare des variables disponibles dans **tous** les gabarits |
| `form_themes` | choisit le thème de rendu des formulaires |
| `strict_variables` | fait échouer l'accès à une variable inexistante au lieu de retourner `null` |

`strict_variables` mérite d'être connue : c'est elle qui décide si une faute de
frappe dans un nom de variable passe inaperçue ou lève une
`Twig\Error\RuntimeError`.

## Les commandes

- `php bin/console debug:twig` — filtres, fonctions, tests, globales
  disponibles, et les chemins de gabarits enregistrés ;
- `php bin/console lint:twig` — vérifie la syntaxe sans rendre.

## Points clés

- Moteur, bridge et bundle sont trois paquets distincts ; le bundle configure.
- Gabarits dans `templates/` par défaut, `twig.default_path` pour en changer.
- `twig.paths` ajoute des répertoires nommés ; `@NomDuBundle` cible un bundle.
- `strict_variables` transforme une variable absente en erreur.
- `debug:twig` inventorie, `lint:twig` valide.

## Sources officielles

- [Templates, emplacements et espaces de noms](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/templates.rst)
