---
id: CRS-am0qe9xa8vzk
official_item: OIT-qkcr4bat6dsb
title: "Process"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-03"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/components/process.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/components/process.rst"
    anchor: "usage"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    verified_at: "2026-09-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Process/Process.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Process/Process.php"
    branch: "8.0"
    symbol_or_lines: "__construct(); stop(); wait(); checkTimeout(); setIdleTimeout(); disableOutput()"
    verified_at: "2026-10-03"
outcome_sections:
  OUT-4h80hb7ky1wt: "Trois façons de lancer"
  OUT-vr32ta087n2a: "Array ou string"
  OUT-hsn9hab7spvy: "Les deux délais"
  OUT-ew0th9575tfe: "Les deux délais"
  OUT-z054mp760r1h: "Récupérer l'output"
---

## Objectif

Exécuter un programme externe, savoir quand l'appel rend la main, et savoir ce
qui l'interrompt.

## Périmètre

Ce composant lance un programme **externe**. Les commands de l'application,
leur entrée-sortie et leur cycle appartiennent au lot 12 (Console). Différer un
travail vers un worker appartient au lot 11 (Messenger). Les opérations sur les
files appartiennent au lot 21 (Filesystem). Le cycle du kernel HTTP
appartient au lot 03.

## Ce qu'il remplace

`Process` exécute une command dans un sous-processus en gérant les différences
entre systèmes **et l'échappement des arguments**. Il remplace `exec()`,
`passthru()`, `shell_exec()` et `system()`.

```php
$process = new Process(['ls', '-lsa']);
$process->run();

if (!$process->isSuccessful()) {
    throw new ProcessFailedException($process);
}

echo $process->getOutput();
```

## Array ou string

Le **array d'arguments est la forme recommandée** : il évite tout
échappement et laisse passer les signaux.

Une string ne se justifie que pour utiliser une fonctionnalité du shell —
redirect, exécution conditionnelle. Elle passe alors par la factory
`Process::fromShellCommandline()`, et **l'échappement comme la portabilité
deviennent votre affaire**. Les arguments variables passent par des variables
d'environment, dont la syntaxe de référence dépend du système ; la forme
`"${:NOM}"`, propre au composant, reste portable.

Exécuté sur Process 8.0.13 :

| Cas | Résultat |
|---|---|
| `new Process(['printf', '%s', 'a b;c $HOME'])` | `a b;c $HOME`, littéral |
| `fromShellCommandline('echo "${:MSG}"')`, `MSG` = `a b;c` | `a b;c`, littéral |
| `"${:NOPE}"` sans valeur fournie | `InvalidArgumentException` au lancement |

## Trois façons de lancer

| Appel | Rend la main | En cas d'échec |
|---|---|---|
| `run()` | à la fin du process | rend un code de sortie |
| `mustRun()` | à la fin du process | lève `ProcessFailedException` |
| `start()` | **immédiatement** | rien : à vous de vérifier |

Exécuté : pour une command qui sort avec `3`, `run()` rend `3` sans rien lever ;
`mustRun()` lève.

Après un `start()`, `isRunning()` interroge l'état et `wait()` **bloque**
jusqu'à la fin.

## Récupérer l'output

`getOutput()` rend **toute** l'output standard, `getErrorOutput()` toute la
sortie d'erreur. `getIncrementalOutput()` ne rend que **ce qui est arrivé
depuis le dernier appel**. Exécuté avec `echo one; sleep 0.3; echo two` :
`"one\n"` en cours d'exécution, puis `"two\n"` après `wait()`, tandis que
`getOutput()` rend les deux.

`disableOutput()` économise la mémoire, mais interdit ensuite `getOutput()`,
ses variantes incrémentales **et `setIdleTimeout()`** ; on ne peut ni l'activer
ni la désactiver pendant l'exécution. Un callback passé à `run()`
reste possible.

L'incompatibilité joue **dans les deux sens** — exécuté :

| Ordre | Résultat |
|---|---|
| `disableOutput()` puis `setIdleTimeout(5)` | `LogicException` |
| `setIdleTimeout(5)` puis `disableOutput()` | `LogicException` |
| `disableOutput()` pendant l'exécution | `RuntimeException` |

## Les variables d'environment

Un process **hérite de toutes les variables du système**. Pour en retirer
une, il faut la passer à `false` — pas la laisser de côté. Exécuté avec une
variable `P23VAR` définie dans le parent :

| Quatrième argument du constructeur | `P23VAR` dans l'enfant |
|---|---|
| absent | héritée |
| `['OTHER' => 'x']` | **toujours héritée** |
| `['P23VAR' => false]` | absente |

## Les deux délais

- `setTimeout()` borne la **durée totale**. Le défaut est de **60 secondes**.
- `setIdleTimeout()` borne le temps **depuis la dernière sortie produite**.

Les deux s'appliquent ensemble : le process est expiré dès que l'un est
dépassé, et l'expiration lève `ProcessTimedOutException`.

**Le piège** : sur un process lancé de façon asynchrone, le délai n'est pas
surveillé pour vous. C'est à l'appelant d'appeler `checkTimeout()`
régulièrement. Exécuté, `sleep 3` avec un délai d'une seconde, lu à 1,5 s :

| Appel | Résultat |
|---|---|
| `isRunning()` | `true` — toujours en cours |
| `getOutput()` | rien n'est levé |
| `checkTimeout()` | `ProcessTimedOutException` |
| `wait()` | `ProcessTimedOutException` : il vérifie le délai en attendant |

## Arrêter

`stop()` prend un délai (10 s par défaut) et un signal. Il envoie **d'abord
`SIGTERM`**, puis attend ; si le process tourne encore à l'échéance, il envoie
le signal passé, **`SIGKILL` par défaut**.

La documentation dit seulement que le signal par défaut est `SIGKILL` : c'est
celui de l'échéance. Lu dans `Process::stop()` (8.0) et exécuté : un `sleep 30`
s'arrête aussitôt, terminé par le signal `15` ; un script qui ignore `SIGTERM`
ne s'arrête qu'au bout du délai.

## Trouver un exécutable

`ExecutableFinder` rend le path absolu d'un exécutable ;
`PhpExecutableFinder` celui du binaire PHP.

## Pièges d'examen

**L'array d'arguments est la forme recommandée** ; la string transfère
l'échappement et la portabilité à l'appelant.

**Le délai par défaut est de 60 secondes**, et sur un process asynchrone il
faut appeler `checkTimeout()` soi-même.

**`disableOutput()` interdit aussi `setIdleTimeout()`.**

**Pour retirer une variable d'environment héritée, il faut la mettre à
`false`** — passer d'autres variables ne la retire pas.

**`stop()` commence par `SIGTERM`** ; `SIGKILL` n'arrive qu'à l'échéance.

**`wait()` vérifie le délai**, mais `isRunning()` et `getOutput()` non.

## Points clés

- `run()` et `mustRun()` attendent ; `start()` rend la main tout de suite.
- `mustRun()` lève, `run()` non.
- `getIncrementalOutput()` ne rend que le nouveau.
- Deux délais indépendants : total et inactivité.
- `stop()` envoie `SIGTERM`, puis `SIGKILL` à l'échéance.

## Sources officielles

- [`components/process.rst`, branche 8.0](https://github.com/symfony/symfony-docs/blob/8.0/components/process.rst)
- [`Process`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Process/Process.php)
