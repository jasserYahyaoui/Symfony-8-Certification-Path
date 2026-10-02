---
id: CRS-brn6frfqpxxj
official_item: OIT-481gmkgbksnr
title: "Console component"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-02"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Console/Command/Command.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Console/Command/Command.php"
    branch: "8.0"
    symbol_or_lines: "SUCCESS, FAILURE, INVALID"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Console/Application.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Console/Application.php"
    branch: "8.0"
    symbol_or_lines: "run() — exception code as exit code, 255 cap; configureIO() — -n, -q, --silent set the input non-interactive"
    verified_at: "2026-10-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Console/Command/InvokableCommand.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Console/Command/InvokableCommand.php"
    branch: "8.0"
    symbol_or_lines: "__invoke() must return an integer; #[Interact] and #[Ask] interactions"
    verified_at: "2026-10-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/console/input.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/console/input.rst"
    branch: "8.0"
    symbol_or_lines: "Interactive Input — #[Ask], #[Interact]"
    verified_at: "2026-10-02"
---

## Objectif

Situer le composant, et connaître le contrat d'une commande — dont la valeur de
retour, qui n'est pas facultative, et le moment où elle peut poser des questions.

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

Exécuté avec Console 8.0.15, par un vrai processus :

| La commande… | Code de sortie |
|---|---|
| retourne `300` | **255** — plafonné par `Application::run()` |
| lève une exception de code `3` | **3** |
| lève une exception de code `0` ou négatif | **1** |
| ne retourne rien (`void`) | **255** — `TypeError` non capturée |

Le message de la `TypeError` : « The command "app:v" must return an integer
value in the "__invoke" method, but "null" was returned. » Elle n'est pas
rattrapée : `Application` n'attrape par défaut que les `Exception`, pas les
`Error`.

## Le cycle d'exécution

La documentation nomme **trois** méthodes de cycle de vie, dans cet ordre :

| Méthode | Rôle | Obligatoire |
|---|---|---|
| `initialize()` | préparer, avant toute interaction | non |
| `interact()` | demander à l'utilisateur ce qui manque | non |
| `__invoke()` — ou `execute()` | faire le travail et retourner le code | **oui** |

`configure()` n'appartient pas à cette liste : elle s'exécute **pendant le
constructeur** — exécuté : `ctor-start`, `configure`, `ctor-end` — et elle ne
concerne que le style classique (une classe qui étend `Command`).

`initialize()` et `interact()` sont des méthodes de `Command`. Une commande
invocable qui ne l'étend pas dispose d'équivalents par attributs : `#[Ask]` sur
un paramètre de `__invoke()`, `#[Interact]` sur une méthode publique. Exécuté :
la méthode `#[Interact]` tourne avant `__invoke()`, et elle est sautée comme
`interact()`.

## Quand `interact()` est sautée

`interact()` n'est appelée que si l'entrée est **interactive**. Lu dans
`Application::configureIO()` (8.0) : l'entrée devient non interactive avec
`--no-interaction` (`-n`), et aussi avec `--quiet` (`-q`) ou `--silent`.

**L'absence de terminal ne suffit pas.** Exécuté, sans TTY et entrée standard
vide — la situation d'un `cron` : `interact()` **est appelée**, la question
lit une fin de fichier et reçoit sa valeur par défaut. Seul `-n` (ou `-q`) l'a
évitée.

| Lancement | `interact()` | Valeur obtenue |
|---|---|---|
| sans option, entrée standard vide | appelée | la valeur par défaut de la question |
| sans option, entrée standard `bob` | appelée | `bob` |
| `-n` | **sautée** | rien |
| `-q` | **sautée** | rien |

Un script automatisé doit donc passer `-n`, et une valeur obtenue dans
`interact()` doit toujours avoir un repli.

## Pièges d'examen

**La méthode d'exécution doit retourner un entier.** Ne rien retourner lève une
`TypeError`, que l'application ne rattrape pas.

**`SUCCESS` vaut 0**, comme la convention Unix ; un code au-delà de 255 sort à 255.

**Un `cron` n'est pas un mode non interactif** : sans `-n`, `interact()` tourne.

**`-q` coupe aussi l'interaction**, pas seulement la sortie.

## Points clés

- Composant autonome ; `bin/console` en est le point d'entrée dans le framework.
- Trois méthodes de cycle de vie : `initialize()`, `interact()`, `__invoke()` ;
  `configure()` tourne dans le constructeur.
- La commande retourne `SUCCESS` (0), `FAILURE` (1) ou `INVALID` (2) ; une
  exception donne son code, ou 1.
- `interact()` est sautée avec `-n`, `-q` ou `--silent` — pas faute de terminal.

## Sources officielles

- [`Command`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Console/Command/Command.php)
- [`Application`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Console/Application.php)
- [`InvokableCommand`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Console/Command/InvokableCommand.php)
- [Console Commands](https://github.com/symfony/symfony-docs/blob/8.0/console.rst)
- [Console Input, « Interactive Input »](https://github.com/symfony/symfony-docs/blob/8.0/console/input.rst)
