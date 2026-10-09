---
id: CRS-pm5kj5kh3gt2
official_item: OIT-r3qcmsehzex1
title: "Runtime"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-09"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/components/runtime.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/components/runtime.rst"
    anchor: "usage"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    verified_at: "2026-09-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Runtime/Internal/autoload_runtime.template"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Runtime/Internal/autoload_runtime.template"
    branch: "8.0"
    symbol_or_lines: "require $_SERVER[SCRIPT_FILENAME]; new $_SERVER[APP_RUNTIME]"
    verified_at: "2026-10-03"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Runtime/GenericRuntime.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Runtime/GenericRuntime.php"
    branch: "8.0"
    symbol_or_lines: "getArgument()"
    verified_at: "2026-10-03"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Runtime/GenericRuntime.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Runtime/GenericRuntime.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "getArgument(): $context is $_SERVER, plus $_ENV only when $_SERVER has no PATH"
    verified_at: "2026-10-09"
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
Composer** du composant. Avec `--no-plugins`, il n'est pas créé — exécuté :
fichier supprimé, `composer dump-autoload --no-plugins` ne le recrée pas.

Ce que le script retourne est contrôlé. Exécuté sur Runtime 8.0.14 :

| Le script rend | Résultat |
|---|---|
| `42` au lieu d'une fonction | `TypeError` : *callable object expected* |
| une fonction qui rend une chaîne | `TypeError` : *object expected* |
| une fonction qui rend une `Response` | la réponse est affichée |

## Les cinq étapes

La documentation 8.0 les énumère ainsi :

1. un `RuntimeInterface` est instancié ;
2. le script d'entrée est **inclus par le runtime**, donc **il s'exécute une
   seconde fois** ;
3. la fonction retournée est remise au runtime, qui **résout ses arguments** ;
4. la fonction est appelée pour obtenir l'application ;
5. le runtime exécute l'application.

Le code inverse les deux premières : `autoload_runtime.php` inclut d'abord le
script, puis instancie le runtime. Exécuté avec un runtime qui journalise son
constructeur : *script returns closure*, puis *runtime constructed*, puis
*closure called*, puis *app run*. Le code l'emporte.

**La seconde exécution est le piège.** Exécuté, avec une écriture dans un
journal avant et après le `require_once` :

| Ligne du script | Exécutions |
|---|---|
| **au-dessus** du `require_once` | **deux** |
| au-dessous | une seule |

La première exécution s'arrête dans `autoload_runtime.php`, qui finit par
`exit` ; la seconde trouve le fichier déjà chargé et continue. Un effet de bord
placé avant le `require_once` — écrire un fichier, incrémenter un compteur — se
produit donc deux fois. La documentation demande qu'il n'y en ait aucun.

**Charger `vendor/autoload.php` avant** désactive tout : le premier
`require_once` de `autoload_runtime.php` rend alors `true`, et le fichier
s'interrompt. Exécuté : le script va jusqu'au bout, l'application ne tourne
pas, code de sortie `0`, aucun message.

## Les arguments sont résolus par type *et* par nom

`array $context` vaut `$_SERVER` + `$_ENV`, selon la documentation. Lu dans
`GenericRuntime::getArgument()` (8.0) : `$_ENV` n'est ajouté que si `$_SERVER`
ne porte pas `PATH`. Pour les arguments communs aux deux runtimes — `array
$context`, `array $argv`, `array $request` — **le type et le nom de la variable
comptent tous les deux** : exécuté, `array $ctx` lève une
`InvalidArgumentException` qui énumère ces trois noms.

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

`SymfonyRuntime` charge les fichiers `.env`. Exécuté : sans `.env`, il lève une
`PathException` ; avec l'option `disable_dotenv`, il démarre, `APP_ENV` à `dev`
et `APP_DEBUG` à `1` par défaut.

Le choix se fait par la variable d'environnement `APP_RUNTIME` ou par
`extra.runtime.class` dans `composer.json`. Les options passent par
`APP_RUNTIME_OPTIONS` ou par `extra.runtime`.

## Pièges d'examen

**Le fichier d'entrée retourne une fonction ; il n'exécute pas
l'application.**

**Le script d'entrée est réexécuté** par le runtime : ce qui précède le
`require_once` tourne deux fois.

**`autoload.php` chargé avant `autoload_runtime.php`** : rien ne s'exécute, sans
erreur.

**Le type *et* le nom de l'argument sont significatifs.**

**`--no-plugins` empêche la création de `autoload_runtime.php`.**

## Points clés

- L'amorçage est abstrait pour rendre le fichier d'entrée générique.
- Cinq étapes ; le script est inclus à nouveau, avant même l'instanciation du
  runtime dans le code.
- `array $context` = `$_SERVER` + `$_ENV` selon la documentation ; le code
  n'ajoute `$_ENV` que si `$_SERVER` ne porte pas `PATH`.
- La nature de l'objet retourné choisit HTTP ou console.
- `APP_RUNTIME` ou `extra.runtime.class` sélectionne le runtime.

## Sources officielles

- [`components/runtime.rst`, branche 8.0](https://github.com/symfony/symfony-docs/blob/8.0/components/runtime.rst)
- [`autoload_runtime.template`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Runtime/Internal/autoload_runtime.template)
