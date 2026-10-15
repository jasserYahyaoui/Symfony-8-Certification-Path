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
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpKernel/Kernel.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/Kernel.php"
    symbol_or_lines: "getCacheDir, getBuildDir, getShareDir, getLogDir"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-18"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/Kernel/MicroKernelTrait.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Kernel/MicroKernelTrait.php"
    symbol_or_lines: "getCacheDir, getBuildDir, getShareDir, getLogDir, getEnvDir"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-18"
outcome_sections:
  OUT-gp7n5whkxyk0: "L'arborescence par défaut"
  OUT-yj8f9a5ncap4: "L'arborescence par défaut"
  OUT-yrbk1d6tvd4c: "Déplacer un directory"
  OUT-8rwmvym0a0qy: "Sous config/"
---

## Objectif

Connaître l'arborescence par défaut d'une application Symfony, le rôle de chaque
directory, et par quel mécanisme chacun se déplace.

## L'arborescence par défaut

| Directory | Role |
|---|---|
| `assets/` | sources front — JavaScript, CSS, images |
| `bin/` | exécutables du projet, dont `bin/console` |
| `config/` | configuration ; `packages/` par bundle, `routes/`, `services.yaml` |
| `public/` | **seul** directory exposé par le serveur web ; contient `index.php` |
| `src/` | le code de l'application, namespace `App\`, dont `Kernel.php` |
| `templates/` | les templates Twig |
| `tests/` | les tests |
| `translations/` | les catalogues de translation |
| `var/` | files générés à l'exécution : `cache/`, `log/` |
| `vendor/` | dependencies installées par Composer — jamais modifié à la main |
| `.env` | à la **racine**, pas dans `config/` |

Deux points structurent le reste. D'abord, **seul `public/` est servi** : tout
ce qui est ailleurs est hors d'atteinte du navigateur, ce qui est la raison
d'être de la séparation. Ensuite, `var/` est le seul directory dans lequel
l'application écrit à l'exécution.

## Sous config/

`config/packages/` contient un file par bundle configuré. Un sous-répertoire
nommé d'après un environment — `config/packages/test/` — ne s'applique que
dans cet environment, et vient surcharger la configuration commune.

## Déplacer un directory

Tous les directories ne se déplacent pas de la même façon, et c'est le point qui
se retient mal :

| Directory | Mécanisme |
|---|---|
| `bin/`, `config/`, `src/`, `public/` | key **`extra`** du `composer.json` : `bin-dir`, `config-dir`, `src-dir`, `public-dir` |
| `.env` | key `extra`, mais **imbriquée** : `extra.runtime.dotenv_path` |
| `vendor/` | key **`config`** du `composer.json` : `vendor-dir`. Pas `extra` |
| `templates/` | **`twig.default_path`** — ou `twig.paths` pour plusieurs |
| `translations/` | **`framework.translator.default_path`** — ou `.paths` pour plusieurs |
| `var/cache/`, `var/log/` | une method du kernel, ou une variable d'environment |

Il y a donc **trois** voies, pas deux : ce que Composer doit connaître avant que
PHP ne démarre (`extra`, et `config` pour `vendor/`), ce qu'un bundle configure
lui-même (`twig`, `framework`), et ce que seul le kernel connaît.

Deux de ces lignes ne se suffisent pas à elles-mêmes. Déplacer `src/` demande
**aussi** de corriger `autoload.psr-4` et de relancer `composer dump-autoload` :
la key `extra.src-dir` seule laisse l'autochargement pointer sur l'ancien
path. Et `public/` se **renomme** sans rien d'autre, mais se **déplace** en
corrigeant le path de `vendor/autoload_runtime.php` dans `public/index.php`.

## Les quatre accessors du kernel

`Kernel` en expose quatre, pas deux, et leurs default values ne sont pas
symétriques :

| Method | Défaut |
|---|---|
| `getCacheDir()` | `<projet>/var/cache/<environnement>` |
| `getBuildDir()` | délègue à `getCacheDir()` |
| `getShareDir(): ?string` | délègue à `getCacheDir()` |
| `getLogDir()` | `<projet>/var/log` — **sans** l'environment |

Le cache est cloisonné par environment, le log ne l'est pas. `getShareDir()`
sert aux montages partagés entre plusieurs frontaux ; c'est le seul accessor
qui peut rendre `null`, et le parameter de container `%kernel.share_dir%` n'est
alors pas enregistré.

## Les variables d'environment, et qui les lit

`APP_CACHE_DIR`, `APP_BUILD_DIR`, `APP_SHARE_DIR` et `APP_LOG_DIR` sont lues par
**`MicroKernelTrait`**, pas par `Kernel` : un kernel qui n'utilise pas le trait
les ignore.

Les trois premières passent par `getEnvDir()`, qui **ajoute
`/<environnement>`** au path reçu et résout un path relatif depuis la racine
du projet. `APP_LOG_DIR` est prise telle quelle. La documentation les présente
comme « le path complet du dossier » : la source dit autre chose, et la
source tranche.

## Tips d'examen

**Quatre keys plates sous `extra`, une imbriquée.** `bin-dir`, `config-dir`,
`src-dir`, `public-dir` déplacent des directories ; `runtime.dotenv_path`
déplace un file et se niche un cran plus bas.

**Cache avec environment, log sans.** Un seul mot à retenir, et il départage
deux default values qu'on suppose symétriques.

## Pièges d'examen

**Aucune key `extra` ne déplace `templates/`, `translations/` ni `vendor/`.**
Les deux premiers se déplacent par **configuration de bundle** —
`twig.default_path`, `framework.translator.default_path` — et `vendor/` par la
key **`config`** de Composer. `extra.templates-dir` n'existe pas.

**`extra.src-dir` seul ne suffit pas.** Sans `autoload.psr-4` corrigé et
`composer dump-autoload` relancé, l'autochargement cherche encore l'ancien
path.

**Les `APP_*_DIR` ne sont pas lues par `Kernel`.** C'est `MicroKernelTrait` qui
les lit ; et trois d'entre elles se voient ajouter l'environment, pas
`APP_LOG_DIR`.

**`config/packages/test/` ne s'ajoute pas à la configuration commune, il la
surcharge.** Un sous-répertoire d'environment n'est lu que dans cet
environment.

**`public/` est le seul directory servi.** Un file utile au navigateur mais
posé ailleurs est inaccessible, quelle que soit la configuration Symfony — c'est
le serveur web qui tranche, pas le framework.

## Points clés

- `public/` est le seul directory exposé ; `var/` le seul directory écrit.
- `src/` = `App\`, et contient `Kernel.php`.
- `config/packages/<env>/` surcharge la configuration pour un environment.
- Trois voies pour déplacer : `composer.json` (`extra`, et `config` pour
  `vendor/`) ; la configuration d'un bundle (`twig`, `framework`) ; le kernel
  (`getCacheDir()`, `getBuildDir()`, `getShareDir()`, `getLogDir()`) ou les
  `APP_*_DIR` que lit `MicroKernelTrait`.

## Sources officielles

- [Best Practices, « Use the Default Directory Structure »](https://github.com/symfony/symfony-docs/blob/8.0/best_practices.rst)
- [Override the Default Directory Structure](https://github.com/symfony/symfony-docs/blob/8.0/configuration/override_dir_structure.rst)
- [`Kernel` (component HttpKernel)](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/Kernel.php)
- [`MicroKernelTrait` (FrameworkBundle)](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Kernel/MicroKernelTrait.php)
