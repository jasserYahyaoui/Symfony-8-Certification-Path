---
id: CRS-d0nvctfthnrp
official_item: OIT-6xqn9ybksrbr
title: "Deployment best practices"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-02"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/deployment.rst"
    anchor: "common-post-deployment-tasks"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    verified_at: "2026-09-02"
---

## Objectif

Connaître les étapes qu'un déploiement Symfony comporte toujours, et les
commandes exactes qui les réalisent.

## Prérequis

Les environnements, et la configuration par variables d'environnement.

## Les quatre étapes du tronc commun

La documentation ne prescrit pas un outil ; elle énumère ce qu'un déploiement
comporte, quelle que soit la méthode :

1. **transférer le code** sur le serveur de production ;
2. **installer les dépendances**, par Composer, avant ou après le transfert ;
3. **exécuter les migrations** ou toute mise à jour de structure de données ;
4. **vider le cache**, et éventuellement le préchauffer.

Le reste — étiqueter une version, préparer une zone temporaire, lancer les
tests, vider un cache externe — est cité comme fréquent, non comme obligatoire.

## Les deux commandes à connaître exactement

```bash
composer install --no-dev --optimize-autoloader
```

Deux drapeaux, deux effets distincts :

- **`--no-dev`** n'installe pas les paquets de développement. C'est ce qui rend
  fatale la présence d'un `dump()` oublié : la dépendance qui le fournit n'est
  pas là ;
- **`--optimize-autoloader`** construit une *class map*, ce qui améliore
  nettement les performances de l'autochargeur.

```bash
APP_ENV=prod APP_DEBUG=0 php bin/console cache:clear
```

Les variables sont posées **sur la commande** : le cache dépend de
l'environnement, et vider celui de `dev` ne sert à rien en production.

## Les variables d'environnement en production

Deux options, et la documentation ne départage pas :

- de **vraies** variables d'environnement, posées par le serveur web,
  l'hébergeur ou la ligne de commande ;
- un fichier **`.env.prod.local`** contenant les valeurs de production.

Pour éviter de relire les fichiers `.env` à chaque requête :

```bash
composer dump-env prod
```

Cela produit un `.env.local.php` optimisé qui **prend le pas** sur les autres
fichiers de configuration. `composer dump-env prod --empty` génère la même
structure sans valeurs, pour une installation qui ne veut dépendre que de
l'environnement réel. Sans Composer sur le serveur, la commande Symfony
`dotenv:dump` fait la même chose.

## Vérifier les prérequis

Sur un poste de développement, la CLI Symfony les vérifie. Sur un serveur où on
ne veut pas l'installer :

```bash
composer require symfony/requirements-checker
```

## Le répertoire du projet

`kernel.project_dir` est calculé comme **le répertoire contenant le
`composer.json` principal**. Un déploiement qui n'embarque pas ce fichier doit
donc redéfinir `Kernel::getProjectDir()` — sans quoi les chemins de
l'application sont faux.

## Pièges d'examen

**`--no-dev` et `--optimize-autoloader` répondent à deux besoins différents** :
ne pas installer le superflu, et accélérer l'autochargement.

**`cache:clear` s'exécute avec `APP_ENV=prod`**, pas avec l'environnement
courant.

**`.env.local.php` l'emporte** sur les autres fichiers de configuration.

**`kernel.project_dir` vient de l'emplacement du `composer.json`.**

**Aucun outil n'est prescrit** : FTP, dépôt versionné, PaaS ou script de
déploiement sont tous cités.

## Points clés

- Quatre étapes : code, dépendances, migrations, cache.
- `composer install --no-dev --optimize-autoloader`.
- `APP_ENV=prod APP_DEBUG=0 php bin/console cache:clear`.
- Variables réelles ou `.env.prod.local` ; `composer dump-env prod` pour
  optimiser.
- `kernel.project_dir` = répertoire du `composer.json` principal.

## Sources officielles

- [How to Deploy a Symfony Application](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/deployment.rst)
