---
id: CRS-brn6frfqpxxj
official_item: OIT-481gmkgbksnr
title: "Console component"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-26"
format: pedagogical
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
outcome_sections:
  OUT-b3610kc2cj3b: "Ce qu'il fait"
  OUT-jc4865ayfy98: "Le cycle d'exécution"
  OUT-a1qc1gqac172: "Le code de retour"
---

## Objectif

Situer le component, et connaître le contrat d'une command — dont la return value, qui n'est pas facultative, et le moment où elle peut poser des questions.

## L'essentiel

Le component Console transforme une class PHP en command de terminal, et il
est **autonome**. Le contrat d'une command tient en deux points : elle
**retourne un int**, qui devient l'exit code du process ; et elle ne peut
poser de questions que si l'entrée est **interactive** — ce qu'une option, pas
l'absence de terminal, décide.

## Le schéma

```text
bin/console app:import  →  Application : résout la command par son nom
  new Command()            configure() tourne DANS le constructor
  initialize()             préparer
  interact()               seulement si l'entrée est interactive (pas de -n, -q, --silent)
  __invoke() / execute()   faire le travail ; RETOURNER un int
  exit code                SUCCESS 0 · FAILURE 1 · INVALID 2 · > 255 → 255 · exception → son code, ou 1
```

## L'exemple : une command d'import

```php
#[AsCommand(name: 'app:import')]
class ImportCommand
{
    public function __invoke(SymfonyStyle $io, #[Argument] string $file): int
    {
        if (!is_file($file)) {
            $io->error('Fichier introuvable');

            return Command::INVALID;   // exit code 2 : mauvaise utilisation
        }
        // …
        return Command::SUCCESS;       // exit code 0
    }
}
```

## Ce qu'il fait

Analyse des arguments, aide générée, sortie colorée, exit code. `symfony/console`
s'installe seul dans n'importe quel projet PHP. Dans une application Symfony,
`bin/console` est l'entry point : une `Application` détient les commands, les
résout par leur nom, et gère `--help`, `--version`, `list` et la verbosity.

## Le code de retour

| La command… | Exit code |
|---|---|
| retourne `Command::SUCCESS` / `FAILURE` / `INVALID` | 0 / 1 / 2 |
| retourne `300` | **255** — plafonné par `Application::run()` |
| lève une exception de code `3` | 3 |
| lève une exception de code `0` ou négatif | 1 |
| ne retourne rien (`void`) | **255** — `TypeError` non capturée |

Un `cron` ou une CI ne lit pas l'output, il lit le code. `INVALID` signale une
**mauvaise utilisation** — un argument aberrant —, `FAILURE` un traitement qui a
échoué. Une `TypeError` n'est pas rattrapée : `Application` n'attrape par
défaut que les `Exception`.

## Le cycle d'exécution

| Method | Role | Obligatoire |
|---|---|---|
| `initialize()` | préparer, avant toute interaction | non |
| `interact()` | demander au user ce qui manque | non |
| `__invoke()` — ou `execute()` | faire le travail et retourner le code | **oui** |

- `configure()` n'en fait pas partie : elle tourne **pendant le constructor**,
  et seulement dans le style classique (une class qui étend `Command`).
- Une command invocable a des équivalents par attributes : `#[Ask]` sur un
  parameter de `__invoke()`, `#[Interact]` sur une method publique, appelée
  avant `__invoke()` et sautée comme `interact()`.
- `interact()` n'est appelée que si l'entrée est interactive : `-n`, `-q` ou
  `--silent` la rendent non interactive (`Application::configureIO()`). **L'absence de terminal ne suffit
  pas** : sous `cron`, sans `-n`, `interact()` tourne et la question reçoit sa
  default value.

## Pièges d'examen

**La method d'exécution doit retourner un int.** Ne rien retourner lève une
`TypeError`, que l'application ne rattrape pas.

**`SUCCESS` vaut 0**, comme la convention Unix ; un code au-delà de 255 sort à 255.

**Un `cron` n'est pas un mode non interactif** : sans `-n`, `interact()` tourne.

**`-q` coupe aussi l'interaction**, pas seulement l'output.

## Application

- Un import lancé par `cron` qui ne doit rien demander → `-n`, et un repli
  pour toute valeur obtenue dans `interact()`.
- Un argument négatif absurde → `return Command::INVALID;`, pas `FAILURE`.

## QCM d'entraînement

**Question 1.** La method `__invoke()` d'une command est déclarée `: void` et
ne retourne rien. Avec quel exit code le process se termine-t-il ?

- **A.** 0, comme `Command::SUCCESS`
- **B.** 1, comme `Command::FAILURE`
- **C.** 255
- **D.** 2, comme `Command::INVALID`

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Ne rien retourner lève une `TypeError`, qu'`Application`
ne rattrape pas : le process PHP sort en erreur fatale.

</details>

**Question 2.** Une command invocable déclare une method `#[Interact]`. Elle
est lancée avec `-n`. Que se passe-t-il ?

- **A.** La method `#[Interact]` est sautée, puis `__invoke()` tourne
- **B.** La method `#[Interact]` tourne, ses questions reçoivent `null`
- **C.** Une exception : l'interaction est obligatoire
- **D.** `__invoke()` est sautée aussi

<details>
<summary>Correction</summary>

**Bonne réponse : A.** `#[Interact]` est l'équivalent invocable d'`interact()` :
elle n'est appelée que si l'entrée est interactive, ce que `-n` empêche.

</details>

**Question 3.** Une command lève `new RuntimeException('x', -2)`. Quel exit
code voit le shell ?

- **A.** -2
- **B.** 254, le complément du code négatif
- **C.** 255
- **D.** 1

<details>
<summary>Correction</summary>

**Bonne réponse : D.** Une exception de code positif donne son code ; un code
nul ou négatif est remplacé par 1.

</details>

**Question 4.** L'import reçoit un file qui n'existe pas et retourne
`Command::INVALID`. Quel exit code le script de CI lit-il ?

- **A.** 1, comme tout échec
- **B.** 2
- **C.** 0 : la command s'est terminée normalement
- **D.** 255, le code des erreurs d'usage

<details>
<summary>Correction</summary>

**Bonne réponse : B.** `INVALID` vaut 2 et signale une mauvaise utilisation,
distincte de `FAILURE` (1), un traitement qui a échoué.

</details>

## Fiche express

**Si tu dois retenir seulement 6 choses :**

- Component **autonome** ; `bin/console` est l'entry point du framework.
- La command **retourne un int** : `SUCCESS` 0, `FAILURE` 1, `INVALID` 2.
- Plus de 255 → **255** ; exception → son code, ou **1** ; `void` → 255.
- Cycle : `initialize()`, `interact()`, `__invoke()` ; `configure()` dans le
  constructor.
- `interact()` sautée avec **`-n`, `-q`, `--silent`** — pas faute de terminal.
- Invocable : **`#[Ask]`** et **`#[Interact]`**.

Moyen mnémotechnique : **« Un int en sortie, une option pour se taire »**.

## Sources officielles

- [`Command`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Console/Command/Command.php)
- [`Application`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Console/Application.php)
- [`InvokableCommand`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Console/Command/InvokableCommand.php)
- [Console Commands](https://github.com/symfony/symfony-docs/blob/8.0/console.rst)
- [Console Input, « Interactive Input »](https://github.com/symfony/symfony-docs/blob/8.0/console/input.rst)

