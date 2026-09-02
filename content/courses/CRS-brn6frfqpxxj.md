---
id: CRS-brn6frfqpxxj
official_item: OIT-481gmkgbksnr
title: "Console component"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Console/Command/Command.php"
    branch: "8.0"
    symbol_or_lines: "SUCCESS, FAILURE, INVALID"
    verified_at: "2026-09-01"
---

## Objectif

Situer le composant, et connaître le contrat d'une commande — dont la valeur de
retour, qui n'est pas facultative.

## Ce qu'il fait

Le composant Console transforme une classe PHP en commande de terminal :
analyse des arguments, aide générée, sortie colorée, code de sortie. Il est
**autonome** : `symfony/console` s'installe seul dans n'importe quel projet PHP.

Dans une application Symfony, `bin/console` est le point d'entrée, et
FrameworkBundle y branche les commandes du framework et celles de `src/`.

## L'application et les commandes

Une `Application` détient des commandes, les résout par leur nom, et exécute
celle qui correspond. Elle gère aussi ce qui est commun : `--help`, `--version`,
`list`, les niveaux de verbosité, le code de sortie.

## Le code de retour

Une commande **retourne un entier**, et cet entier devient le code de sortie du
processus :

```php
return Command::SUCCESS;   // 0
return Command::FAILURE;   // 1
return Command::INVALID;   // 2
```

C'est le point à retenir. Un script d'intégration continue ou un `cron` ne lit
pas la sortie : il lit le code. Une commande qui échoue mais retourne `SUCCESS`
est un échec invisible.

`INVALID` se distingue de `FAILURE` : elle signale une **mauvaise utilisation** —
un argument aberrant — plutôt qu'un traitement qui a échoué.

## Le cycle d'exécution

La documentation nomme **trois** méthodes de cycle de vie, dans cet ordre :

| Méthode | Rôle | Obligatoire |
|---|---|---|
| `initialize()` | préparer, avant toute interaction | non |
| `interact()` | demander à l'utilisateur ce qui manque | non |
| `__invoke()` — ou `execute()` | faire le travail et retourner le code | **oui** |

`configure()` n'appartient pas à cette liste : elle ne s'exécute pas au moment
du lancement mais **à la fin du constructeur**, et elle ne concerne que le style
classique (une classe qui étend `Command`).

`interact()` n'est pas appelée si l'entrée n'est pas interactive — `--no-interaction`,
ou une exécution par `cron`. C'est pourquoi une valeur obtenue là doit toujours
avoir un repli.

`initialize()` et `interact()` sont des méthodes de `Command` : une commande
invocable qui ne l'étend pas n'en dispose pas.

## Pièges d'examen

**La méthode d'exécution doit retourner un entier.** Ne rien retourner produit
une erreur.

**`SUCCESS` vaut 0**, comme la convention Unix ; les autres codes signalent un
problème.

**`interact()` est sautée en mode non interactif** : un script automatisé ne
répondra à aucune question.

## Points clés

- Composant autonome ; `bin/console` en est le point d'entrée dans le framework.
- Trois méthodes de cycle de vie : `initialize()`, `interact()`, `__invoke()`.
- La commande retourne `SUCCESS` (0), `FAILURE` (1) ou `INVALID` (2).
- `interact()` ne s'exécute pas sans terminal interactif.

## Sources officielles

- [`Command`, branche 8.0](https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Console/Command/Command.php)
- [Console Commands](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/console.rst)
