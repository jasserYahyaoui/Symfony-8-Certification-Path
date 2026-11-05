---
id: CRS-156khyjssm25
official_item: OIT-p3p7te94qc1n
title: "Code organization"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
format: pedagogical
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

## L'essentiel

Une application Symfony a une arborescence par défaut où deux directories
structurent tout : **`public/`**, le seul servi par le serveur web, et
**`var/`**, le seul où l'application écrit. Chaque directory se déplace, mais
par **trois** voies différentes : `composer.json`, la configuration d'un bundle,
ou le kernel.

## Le schéma

```text
public/ (servi, index.php)   src/ (App\, Kernel.php)   config/ (packages/<env>/ surcharge)   var/ (cache/, log/)
déplacer  bin/ config/ src/ public/   → composer.json extra.{bin,config,src,public}-dir
          .env                        → extra.runtime.dotenv_path (imbriquée)
          vendor/                     → composer.json config.vendor-dir (pas extra)
          templates/ · translations/  → twig.default_path · framework.translator.default_path
          var/cache/ · var/log/       → Kernel::getCacheDir()… ou APP_*_DIR (MicroKernelTrait)
```

## L'exemple : sortir le cache de `var/`

```php
// src/Kernel.php
public function getCacheDir(): string
{
    return '/tmp/app/cache/'.$this->environment;
}
```

## L'arborescence par défaut

| Directory | Role |
|---|---|
| `assets/` | sources front — JavaScript, CSS, images |
| `bin/` | exécutables, dont `bin/console` |
| `config/` | configuration ; `packages/` par bundle, `routes/`, `services.yaml` |
| `public/` | **seul** directory exposé par le serveur web ; contient `index.php` |
| `src/` | le code, namespace `App\`, dont `Kernel.php` |
| `templates/`, `translations/`, `tests/` | templates Twig, catalogues, tests |
| `var/` | files générés à l'exécution : `cache/`, `log/` |
| `vendor/` | dependencies Composer — jamais modifié à la main |
| `.env` | à la **racine**, pas dans `config/` |

## Sous config/

`config/packages/` contient un file par bundle configuré. Un sous-répertoire
d'environment — `config/packages/test/` — n'est lu que dans cet environment, et
**surcharge** la configuration commune.

## Déplacer un directory

Trois voies : ce que Composer doit connaître avant que PHP ne démarre (`extra`,
et `config.vendor-dir`), ce qu'un bundle configure (`twig.default_path` ou
`twig.paths`, `framework.translator.default_path`), et ce que seul le kernel
connaît. `.env` passe par `extra.runtime.dotenv_path`. Déplacer `src/` demande
**aussi** de corriger `autoload.psr-4` et de relancer `composer dump-autoload` ;
déplacer `public/` demande de corriger, dans `index.php`, le path de
`vendor/autoload_runtime.php`.

## Les quatre accessors du kernel

| Method | Défaut |
|---|---|
| `getCacheDir()` | `<projet>/var/cache/<environnement>` |
| `getBuildDir()` | délègue à `getCacheDir()` |
| `getShareDir(): ?string` | délègue à `getCacheDir()` ; peut rendre `null` |
| `getLogDir()` | `<projet>/var/log` — **sans** l'environment |

`APP_CACHE_DIR`, `APP_BUILD_DIR`, `APP_SHARE_DIR` et `APP_LOG_DIR` sont lues par
**`MicroKernelTrait`**, pas par `Kernel`. Les trois premières passent par
`getEnvDir()`, qui ajoute `/<environnement>` et résout un path relatif depuis
la racine du projet ; `APP_LOG_DIR` est prise telle quelle. `APP_SHARE_DIR` à
`false` fait rendre `null` à `getShareDir()` : `%kernel.share_dir%` n'est alors
pas enregistré.

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

## Application

- Templates répartis dans deux directories → `twig.paths`.
- Un cache sur un disque rapide, par environment → `APP_CACHE_DIR`, avec
  `MicroKernelTrait`.

## QCM d'entraînement

**Question 1.** Avec `MicroKernelTrait`, `APP_SHARE_DIR` vaut `false`. Que rend
`getShareDir()` ?

- **A.** `null`
- **B.** Le directory du cache, comme par défaut
- **C.** La string `false`, prise comme un path
- **D.** Une exception : la valeur n'est pas un path

<details>
<summary>Correction</summary>

**Bonne réponse : A.** La valeur booléenne fausse désactive le directory
partagé ; le parameter `%kernel.share_dir%` n'est alors pas enregistré.

</details>

**Question 2.** Un kernel qui **n'utilise pas** `MicroKernelTrait` tourne avec
`APP_CACHE_DIR=/data/cache`, en `prod`. Où va le cache ?

- **A.** Dans `/data/cache/prod`
- **B.** Dans `/data/cache`, tel quel
- **C.** Dans `<projet>/var/cache/prod`
- **D.** Nulle part : une exception au démarrage

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Ces variables sont lues par le trait, pas par `Kernel` ;
sans lui, `getCacheDir()` garde sa default value.

</details>

**Question 3.** Quelle key du `composer.json` déplace le file `.env` ?

- **A.** `extra.dotenv-dir`
- **B.** `extra.runtime.dotenv_path`
- **C.** `config.dotenv-path`, à côté de `vendor-dir`
- **D.** Aucune : `.env` reste à la racine

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Elle vit sous `extra`, comme les keys des directories,
mais imbriquée d'un cran, dans la section du component Runtime.

</details>

**Question 4.** Avec `MicroKernelTrait`, `APP_BUILD_DIR` vaut `var/build`
(relatif), en `prod`. Que rend `getBuildDir()` ?

- **A.** `var/build`, relatif au directory courant
- **B.** `<projet>/var/build`
- **C.** `/var/build/prod`, depuis la racine du disque
- **D.** `<projet>/var/build/prod`

<details>
<summary>Correction</summary>

**Bonne réponse : D.** `getEnvDir()` résout un path relatif depuis la racine du
projet, puis lui ajoute le nom de l'environment.

</details>

## Fiche express

**Si tu dois retenir seulement 6 choses :**

- **`public/`** seul servi ; **`var/`** seul écrit.
- `config/packages/<env>/` **surcharge**, dans cet environment seulement.
- `extra.*-dir` pour `bin`, `config`, `src`, `public` ; **`config.vendor-dir`**
  pour `vendor/`.
- Templates et translations : **configuration de bundle**, pas `extra`.
- Cache **avec** l'environment, log **sans**.
- `APP_*_DIR` : lues par **`MicroKernelTrait`** ; trois reçoivent
  l'environment.

Moyen mnémotechnique : **« Composer, bundle, kernel : trois voies »**.

## Sources officielles

- [Best Practices, « Use the Default Directory Structure »](https://github.com/symfony/symfony-docs/blob/8.0/best_practices.rst)
- [Override the Default Directory Structure](https://github.com/symfony/symfony-docs/blob/8.0/configuration/override_dir_structure.rst)
- [`Kernel` (component HttpKernel)](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/Kernel.php)
- [`MicroKernelTrait` (FrameworkBundle)](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Kernel/MicroKernelTrait.php)

