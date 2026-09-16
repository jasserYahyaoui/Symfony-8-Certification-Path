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
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/best_practices.rst"
    anchor: "use-the-default-directory-structure"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/configuration/override_dir_structure.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/configuration/override_dir_structure.rst"
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
| `bin/`, `config/`, `src/`, `public/` | clé **`extra`** du `composer.json` : `bin-dir`, `config-dir`, `src-dir`, `public-dir` — et rien d'autre |
| `vendor/` | clé **`config`** du `composer.json` : `vendor-dir`. Pas `extra` |
| `templates/` | option **`twig.default_path`** |
| `translations/` | option **`framework.translator.default_path`** |
| `var/cache/` | `getCacheDir()` sur le `Kernel`, ou la variable d'environnement `APP_CACHE_DIR` |
| `var/log/` | `getLogDir()` sur le `Kernel`, ou `APP_LOG_DIR` |

Il y a donc **trois** voies, pas deux : ce que Composer doit connaître avant que
PHP ne démarre (`extra`, et `config` pour `vendor/`), ce qu'un bundle configure
lui-même (`twig`, `framework`), et ce que seul le noyau connaît (`getCacheDir()`,
`getLogDir()`).

## Pièges d'examen

**`extra` ne déplace que quatre répertoires.** `bin-dir`, `config-dir`,
`src-dir`, `public-dir` : la liste est close. `templates/` et `translations/`
se déplacent par **configuration de bundle** — `twig.default_path` et
`framework.translator.default_path` — et `vendor/` par la clé **`config`** de
Composer, pas `extra`. Une clé `extra.templates-dir` n'existe pas.

**Le cache et les logs ont deux voies chacun.** `getCacheDir()` et `getLogDir()`
sur le `Kernel`, mais aussi les variables d'environnement `APP_CACHE_DIR` et
`APP_LOG_DIR`, que la documentation présente à égalité.

**`config/packages/test/` ne s'ajoute pas à la configuration commune, il la
surcharge.** Un sous-répertoire d'environnement n'est lu que dans cet
environnement.

**`public/` est le seul répertoire servi.** Un fichier utile au navigateur mais
posé ailleurs est inaccessible, quelle que soit la configuration Symfony — c'est
le serveur web qui tranche, pas le framework.

## Points clés

- `public/` est le seul répertoire exposé ; `var/` le seul répertoire écrit.
- `src/` = `App\`, et contient `Kernel.php`.
- `config/packages/<env>/` surcharge la configuration pour un environnement.
- Trois voies pour déplacer : `extra` (quatre répertoires) et `config`
  (`vendor/`) dans `composer.json` ; `twig.default_path` et
  `framework.translator.default_path` en configuration ; `getCacheDir()` /
  `getLogDir()` ou `APP_CACHE_DIR` / `APP_LOG_DIR` pour `var/`.

## Sources officielles

- [Best Practices, « Use the Default Directory Structure »](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/best_practices.rst)
- [Override the Default Directory Structure](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/configuration/override_dir_structure.rst)
