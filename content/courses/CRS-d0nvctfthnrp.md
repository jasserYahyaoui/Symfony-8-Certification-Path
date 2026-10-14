---
id: CRS-d0nvctfthnrp
official_item: OIT-6xqn9ybksrbr
title: "Deployment best practices"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-02"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/deployment.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/deployment.rst"
    anchor: "common-post-deployment-tasks"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    verified_at: "2026-09-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpKernel/Kernel.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/Kernel.php"
    branch: "8.0"
    symbol_or_lines: "getProjectDir()"
    verified_at: "2026-10-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/ErrorHandler/composer.json"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/ErrorHandler/composer.json"
    branch: "8.0"
    symbol_or_lines: "\"symfony/var-dumper\": \"^7.4|^8.0\" under require"
    verified_at: "2026-10-02"
---

## Objectif

Connaître les étapes qu'un deployment Symfony comporte toujours, et les
commands exactes qui les réalisent.

## Prérequis

Les environments, et la configuration par variables d'environment.

## Les quatre étapes du tronc commun

La documentation ne prescrit pas un outil ; elle énumère ce qu'un deployment
comporte, quelle que soit la method :

1. **transférer le code** sur le serveur de production ;
2. **installer les dependencies**, par Composer, avant ou après le transfert ;
3. **exécuter les migrations** ou toute mise à jour de structure de données ;
4. **vider le cache**, et éventuellement le préchauffer.

Le reste — étiqueter une version, préparer une zone temporaire, lancer les
tests, vider un cache externe — est cité comme fréquent, non comme obligatoire.

## Les deux commands à connaître exactement

```bash
composer install --no-dev --optimize-autoloader
```

Deux drapeaux, deux effets distincts :

- **`--no-dev`** n'installe pas les packages de développement ;
- **`--optimize-autoloader`** construit une *class map*, ce qui améliore
  nettement les performances de l'autochargeur.

```bash
APP_ENV=prod APP_DEBUG=0 php bin/console cache:clear
```

Les variables sont posées **sur la command** : le cache dépend de
l'environment, et vider celui de `dev` ne sert à rien en production.

Même logique pour `composer install` : si cette étape échoue sur une erreur
*class not found*, la documentation indique d'exporter `APP_ENV=prod` avant de
la relancer, pour que les scripts `post-install-cmd` s'exécutent en `prod`.

### `--no-dev` et un `dump()` oublié

On lit souvent que `--no-dev` rend fatal un `dump()` oublié, la function
n'étant plus installée. **C'est faux dans une application Symfony 8.0.** Lu dans
les `composer.json` de la branche 8.0 : FrameworkBundle exige
`symfony/error-handler`, qui exige `symfony/var-dumper` en dependency ordinaire.
Le composant reste donc installé avec `--no-dev` : `dump()` existe en
production, et écrit son HTML dans la Response au lieu de lever une erreur. Le
détail exécuté est sur la page *Code debugging*.

## Les variables d'environment en production

Deux options, et la documentation ne départage pas :

- de **vraies** variables d'environment, posées par le serveur web,
  l'hébergeur ou la ligne de command ;
- un file **`.env.prod.local`** contenant les valeurs de production.

Pour éviter de relire les files `.env` à chaque Request :

```bash
composer dump-env prod
```

Cela produit un `.env.local.php` optimisé qui **prend le pas** sur les autres
files de configuration. `composer dump-env prod --empty` génère la même
structure sans valeurs, pour une installation qui ne veut dépendre que de
l'environment réel. Sans Composer sur le serveur, la command Symfony
`dotenv:dump` fait la même chose.

## Vérifier les prérequis

Sur un poste de développement, la CLI Symfony les vérifie. Sur un serveur où on
ne veut pas l'installer :

```bash
composer require symfony/requirements-checker
```

## Le directory du projet

`kernel.project_dir` est calculé comme **le directory contenant le
`composer.json` principal**. Un deployment qui n'embarque pas ce file doit
donc redéfinir `Kernel::getProjectDir()` — sans quoi les paths de
l'application sont faux.

Lu dans `Kernel::getProjectDir()` (8.0) : la method part du directory du
file de la class kernel et remonte jusqu'au premier `composer.json`. Si elle
n'en trouve aucun, elle **ne lève pas d'erreur** : elle rend le directory du
kernel. Exécuté, kernel dans `app/src/` :

| `composer.json` présent | `getProjectDir()` |
|---|---|
| dans `app/` | `app` |
| nulle part | `app/src` — silencieusement faux |

## Pièges d'examen

**`--no-dev` et `--optimize-autoloader` répondent à deux besoins différents** :
ne pas installer le superflu, et accélérer l'autochargement.

**`cache:clear` s'exécute avec `APP_ENV=prod`**, pas avec l'environment
courant.

**`.env.local.php` l'emporte** sur les autres files de configuration.

**`kernel.project_dir` vient de l'emplacement du `composer.json`** ; sans lui,
le directory du kernel est rendu sans erreur.

**`--no-dev` ne retire pas `dump()`** d'une application FrameworkBundle :
VarDumper arrive par ErrorHandler.

**Aucun outil n'est prescrit** : FTP, dépôt versionné, PaaS ou script de
deployment sont tous cités.

## Points clés

- Quatre étapes : code, dependencies, migrations, cache.
- `composer install --no-dev --optimize-autoloader`.
- `APP_ENV=prod APP_DEBUG=0 php bin/console cache:clear`.
- Variables réelles ou `.env.prod.local` ; `composer dump-env prod` pour
  optimiser.
- `kernel.project_dir` = directory du `composer.json` principal.

## Sources officielles

- [How to Deploy a Symfony Application](https://github.com/symfony/symfony-docs/blob/8.0/deployment.rst)
- [`Kernel::getProjectDir()`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/Kernel.php)
- [`composer.json` d'ErrorHandler, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/ErrorHandler/composer.json)
