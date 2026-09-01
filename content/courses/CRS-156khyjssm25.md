---
id: CRS-156khyjssm25
official_item: OIT-p3p7te94qc1n
title: "Code organization"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/best_practices.rst"
    anchor: "use-the-default-directory-structure"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/configuration/override_dir_structure.rst"
    anchor: "override-the-cache-directory"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
---

## Objectif

Connaître l'arborescence par défaut d'une application Symfony, le rôle de chaque
répertoire, et par quel mécanisme chacun se déplace.

## L'arborescence par défaut

| Répertoire | Rôle |
|---|---|
| `bin/` | exécutables du projet, dont `bin/console` |
| `config/` | configuration ; `packages/` par bundle, `routes/`, `services.yaml` |
| `public/` | **seul** répertoire exposé par le serveur web ; contient `index.php` |
| `src/` | le code de l'application, espace de noms `App\`, dont `Kernel.php` |
| `templates/` | les gabarits Twig |
| `tests/` | les tests |
| `translations/` | les catalogues de traduction |
| `var/` | fichiers générés à l'exécution : `cache/`, `log/` |
| `vendor/` | dépendances installées par Composer — jamais modifié à la main |

Deux points structurent le reste. D'abord, **seul `public/` est servi** : tout
ce qui est ailleurs est hors d'atteinte du navigateur, ce qui est la raison
d'être de la séparation. Ensuite, `var/` est le seul répertoire dans lequel
l'application écrit à l'exécution.

## Sous config/

`config/packages/` contient un fichier par bundle configuré. Un sous-répertoire
nommé d'après un environnement — `config/packages/test/` — ne s'applique que
dans cet environnement, et vient surcharger la configuration commune.

## Déplacer un répertoire

Tous les répertoires ne se déplacent pas de la même façon, et c'est le point qui
se retient mal :

| Répertoire | Mécanisme |
|---|---|
| `bin/`, `config/`, `src/`, `public/`, `vendor/`, `translations/`, `templates/` | clé `extra` du `composer.json` (`bin-dir`, `config-dir`, `src-dir`, `public-dir`…) |
| `var/cache/` | méthode `getCacheDir()` de la classe `Kernel` |
| `var/log/` | méthode `getLogDir()` de la classe `Kernel` |

Autrement dit : ce que Composer doit connaître se déclare dans `composer.json` ;
ce que seul le noyau doit connaître se surcharge en PHP dans le `Kernel`.

## Points clés

- `public/` est le seul répertoire exposé ; `var/` le seul répertoire écrit.
- `src/` = `App\`, et contient `Kernel.php`.
- `config/packages/<env>/` surcharge la configuration pour un environnement.
- Répertoires déplacés par `extra` dans `composer.json`, sauf cache et logs,
  déplacés par `getCacheDir()` et `getLogDir()`.

## Sources officielles

- [Best Practices, « Use the Default Directory Structure »](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/best_practices.rst)
- [Override the Default Directory Structure](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/configuration/override_dir_structure.rst)
