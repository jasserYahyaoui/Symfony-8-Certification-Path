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
---

## Objectif

Connaître l'arborescence par défaut d'une application Symfony, le rôle de chaque
répertoire, et par quel mécanisme chacun se déplace.

## L'arborescence par défaut

| Répertoire | Rôle |
|---|---|
| `assets/` | sources front — JavaScript, CSS, images |
| `bin/` | exécutables du projet, dont `bin/console` |
| `config/` | configuration ; `packages/` par bundle, `routes/`, `services.yaml` |
| `public/` | **seul** répertoire exposé par le serveur web ; contient `index.php` |
| `src/` | le code de l'application, espace de noms `App\`, dont `Kernel.php` |
| `templates/` | les gabarits Twig |
| `tests/` | les tests |
| `translations/` | les catalogues de traduction |
| `var/` | fichiers générés à l'exécution : `cache/`, `log/` |
| `vendor/` | dépendances installées par Composer — jamais modifié à la main |
| `.env` | à la **racine**, pas dans `config/` |

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
| `bin/`, `config/`, `src/`, `public/` | clé **`extra`** du `composer.json` : `bin-dir`, `config-dir`, `src-dir`, `public-dir` |
| `.env` | clé `extra`, mais **imbriquée** : `extra.runtime.dotenv_path` |
| `vendor/` | clé **`config`** du `composer.json` : `vendor-dir`. Pas `extra` |
| `templates/` | **`twig.default_path`** — ou `twig.paths` pour plusieurs |
| `translations/` | **`framework.translator.default_path`** — ou `.paths` pour plusieurs |
| `var/cache/`, `var/log/` | une méthode du noyau, ou une variable d'environnement |

Il y a donc **trois** voies, pas deux : ce que Composer doit connaître avant que
PHP ne démarre (`extra`, et `config` pour `vendor/`), ce qu'un bundle configure
lui-même (`twig`, `framework`), et ce que seul le noyau connaît.

Deux de ces lignes ne se suffisent pas à elles-mêmes. Déplacer `src/` demande
**aussi** de corriger `autoload.psr-4` et de relancer `composer dump-autoload` :
la clé `extra.src-dir` seule laisse l'autochargement pointer sur l'ancien
chemin. Et `public/` se **renomme** sans rien d'autre, mais se **déplace** en
corrigeant le chemin de `vendor/autoload_runtime.php` dans `public/index.php`.

## Les quatre accesseurs du noyau

`Kernel` en expose quatre, pas deux, et leurs valeurs par défaut ne sont pas
symétriques :

| Méthode | Défaut |
|---|---|
| `getCacheDir()` | `<projet>/var/cache/<environnement>` |
| `getBuildDir()` | délègue à `getCacheDir()` |
| `getShareDir(): ?string` | délègue à `getCacheDir()` |
| `getLogDir()` | `<projet>/var/log` — **sans** l'environnement |

Le cache est cloisonné par environnement, le log ne l'est pas. `getShareDir()`
sert aux montages partagés entre plusieurs frontaux ; c'est le seul accesseur
qui peut rendre `null`, et le paramètre de conteneur `%kernel.share_dir%` n'est
alors pas enregistré.

## Les variables d'environnement, et qui les lit

`APP_CACHE_DIR`, `APP_BUILD_DIR`, `APP_SHARE_DIR` et `APP_LOG_DIR` sont lues par
**`MicroKernelTrait`**, pas par `Kernel` : un noyau qui n'utilise pas le trait
les ignore.

Les trois premières passent par `getEnvDir()`, qui **ajoute
`/<environnement>`** au chemin reçu et résout un chemin relatif depuis la racine
du projet. `APP_LOG_DIR` est prise telle quelle. La documentation les présente
comme « le chemin complet du dossier » : la source dit autre chose, et la
source tranche.

## Tips d'examen

**Quatre clés plates sous `extra`, une imbriquée.** `bin-dir`, `config-dir`,
`src-dir`, `public-dir` déplacent des répertoires ; `runtime.dotenv_path`
déplace un fichier et se niche un cran plus bas.

**Cache avec environnement, log sans.** Un seul mot à retenir, et il départage
deux valeurs par défaut qu'on suppose symétriques.

## Pièges d'examen

**Aucune clé `extra` ne déplace `templates/`, `translations/` ni `vendor/`.**
Les deux premiers se déplacent par **configuration de bundle** —
`twig.default_path`, `framework.translator.default_path` — et `vendor/` par la
clé **`config`** de Composer. `extra.templates-dir` n'existe pas.

**`extra.src-dir` seul ne suffit pas.** Sans `autoload.psr-4` corrigé et
`composer dump-autoload` relancé, l'autochargement cherche encore l'ancien
chemin.

**Les `APP_*_DIR` ne sont pas lues par `Kernel`.** C'est `MicroKernelTrait` qui
les lit ; et trois d'entre elles se voient ajouter l'environnement, pas
`APP_LOG_DIR`.

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
- Trois voies pour déplacer : `composer.json` (`extra`, et `config` pour
  `vendor/`) ; la configuration d'un bundle (`twig`, `framework`) ; le noyau
  (`getCacheDir()`, `getBuildDir()`, `getShareDir()`, `getLogDir()`) ou les
  `APP_*_DIR` que lit `MicroKernelTrait`.

## Sources officielles

- [Best Practices, « Use the Default Directory Structure »](https://github.com/symfony/symfony-docs/blob/8.0/best_practices.rst)
- [Override the Default Directory Structure](https://github.com/symfony/symfony-docs/blob/8.0/configuration/override_dir_structure.rst)
- [`Kernel` (composant HttpKernel)](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/Kernel.php)
- [`MicroKernelTrait` (FrameworkBundle)](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Kernel/MicroKernelTrait.php)
