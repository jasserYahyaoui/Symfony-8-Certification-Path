---
id: CRS-pm5kj5kh3gt2
official_item: OIT-r3qcmsehzex1
title: "Runtime"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-02"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/components/runtime.rst"
    anchor: "usage"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    verified_at: "2026-09-02"
---

## Objectif

Comprendre ce qui s'exécute entre le chargement de l'autoload et la remise
d'une requête au noyau, et pourquoi le fichier d'entrée **retourne** au lieu
d'exécuter.

## Périmètre

Le cycle de la requête HTTP — du contrôleur frontal jusqu'à l'envoi de la
réponse — appartient au lot 03, et l'organisation des répertoires du projet
au lot 03 également. Les points d'entrée de console appartiennent au lot 12.
Cet item couvre l'abstraction d'amorçage elle-même.

## Le fichier d'entrée ne lance rien

```php
require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context): Kernel {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
```

Le fichier **retourne une fonction**. Il ne construit pas la réponse, ne
l'envoie pas, et n'appelle rien.

`vendor/autoload_runtime.php` est produit automatiquement par le **plugin
Composer** du composant. Avec `--no-plugins`, il n'est pas créé.

## Les cinq étapes

1. un `RuntimeInterface` est instancié ;
2. le script d'entrée est **inclus par le runtime**, donc **il s'exécute une
   seconde fois** ;
3. la fonction retournée est remise au runtime, qui **résout ses arguments** ;
4. la fonction est appelée pour obtenir l'application ;
5. le runtime exécute l'application.

**L'étape 2 est le piège** : le script tourne deux fois. Tout effet de bord
qu'il produirait — écrire un fichier, ouvrir une connexion, incrémenter un
compteur — se produirait deux fois. La documentation l'écrit explicitement.

## Les arguments sont résolus par type *et* par nom

`array $context` vaut `$_SERVER` + `$_ENV`. Pour les arguments communs aux
deux runtimes, **le type et le nom de la variable comptent tous les deux** :
renommer `$context` casse la résolution.

`SymfonyRuntime` accepte en plus une requête créée depuis les superglobales,
ainsi que les interfaces d'entrée et de sortie de console.

## Un même fichier, deux natures d'application

C'est **ce que la fonction retourne** qui décide :

- un noyau HTTP → le runtime exécute une application HTTP ;
- une application de console → le runtime exécute une application en ligne de
  commande.

## Choisir le runtime

`SymfonyRuntime` est le défaut et convient à un serveur en PHP-FPM.
`GenericRuntime` s'appuie sur les superglobales de PHP.

Le choix se fait par la variable d'environnement `APP_RUNTIME` ou par
`extra.runtime.class` dans `composer.json`. Les options passent par
`APP_RUNTIME_OPTIONS` ou par `extra.runtime`.

## Pièges d'examen

**Le fichier d'entrée retourne une fonction ; il n'exécute pas
l'application.**

**Le script d'entrée est réexécuté** par le runtime : aucun effet de bord.

**Le type *et* le nom de l'argument sont significatifs.**

**`--no-plugins` empêche la création de `autoload_runtime.php`.**

## Points clés

- L'amorçage est abstrait pour rendre le fichier d'entrée générique.
- Cinq étapes ; la seconde inclut à nouveau le script.
- `array $context` = `$_SERVER` + `$_ENV`.
- La nature de l'objet retourné choisit HTTP ou console.
- `APP_RUNTIME` ou `extra.runtime.class` sélectionne le runtime.

## Sources officielles

- [`components/runtime.rst`, branche 8.0](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/components/runtime.rst)
