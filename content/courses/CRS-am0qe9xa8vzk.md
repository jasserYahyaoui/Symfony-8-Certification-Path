---
id: CRS-am0qe9xa8vzk
official_item: OIT-qkcr4bat6dsb
title: "Process"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-02"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/components/process.rst"
    anchor: "usage"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    verified_at: "2026-09-02"
---

## Objectif

Exécuter un programme externe, savoir quand l'appel rend la main, et savoir ce
qui l'interrompt.

## Périmètre

Ce composant lance un programme **externe**. Les commandes de l'application,
leur entrée-sortie et leur cycle appartiennent au lot 12 (Console). Différer un
travail vers un worker appartient au lot 11 (Messenger). Les opérations sur les
fichiers appartiennent au lot 21 (Filesystem). Le cycle du noyau HTTP
appartient au lot 03.

## Ce qu'il remplace

`Process` exécute une commande dans un sous-processus en gérant les différences
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

## Tableau ou chaîne

Le **tableau d'arguments est la forme recommandée** : il évite tout
échappement et laisse passer les signaux.

Une chaîne ne se justifie que pour utiliser une fonctionnalité du shell —
redirection, exécution conditionnelle. Elle passe alors par la fabrique
`Process::fromShellCommandline()`, et **l'échappement comme la portabilité
deviennent votre affaire**. Les arguments variables passent par des variables
d'environnement, dont la syntaxe de référence dépend du système ; la forme
`"${:NOM}"`, propre au composant, reste portable.

## Trois façons de lancer

| Appel | Rend la main | En cas d'échec |
|---|---|---|
| `run()` | à la fin du processus | rend un code de sortie |
| `mustRun()` | à la fin du processus | lève `ProcessFailedException` |
| `start()` | **immédiatement** | rien : à vous de vérifier |

Après un `start()`, `isRunning()` interroge l'état et `wait()` **bloque**
jusqu'à la fin.

## Récupérer la sortie

`getOutput()` rend **toute** la sortie standard, `getErrorOutput()` toute la
sortie d'erreur. `getIncrementalOutput()` ne rend que **ce qui est arrivé
depuis le dernier appel**.

`disableOutput()` économise la mémoire, mais interdit ensuite `getOutput()`,
ses variantes incrémentales **et `setIdleTimeout()`** ; on ne peut ni l'activer
ni la désactiver pendant l'exécution. Une fonction de rappel passée à `run()`
reste possible.

## Les variables d'environnement

Un processus **hérite de toutes les variables du système**. Pour en retirer
une, il faut la passer à `false` — pas la laisser de côté.

## Les deux délais

- `setTimeout()` borne la **durée totale**. Le défaut est de **60 secondes**.
- `setIdleTimeout()` borne le temps **depuis la dernière sortie produite**.

Les deux s'appliquent ensemble : le processus est expiré dès que l'un est
dépassé, et l'expiration lève `ProcessTimedOutException`.

**Le piège** : sur un processus lancé de façon asynchrone, le délai n'est pas
surveillé pour vous. C'est à l'appelant d'appeler `checkTimeout()`
régulièrement.

## Arrêter

`stop()` prend un délai et un signal. Le signal envoyé par défaut est
**`SIGKILL`**.

## Trouver un exécutable

`ExecutableFinder` rend le chemin absolu d'un exécutable ;
`PhpExecutableFinder` celui du binaire PHP.

## Pièges d'examen

**Le tableau d'arguments est la forme recommandée** ; la chaîne transfère
l'échappement et la portabilité à l'appelant.

**Le délai par défaut est de 60 secondes**, et sur un processus asynchrone il
faut appeler `checkTimeout()` soi-même.

**`disableOutput()` interdit aussi `setIdleTimeout()`.**

**Pour retirer une variable d'environnement héritée, il faut la mettre à
`false`.**

## Points clés

- `run()` et `mustRun()` attendent ; `start()` rend la main tout de suite.
- `mustRun()` lève, `run()` non.
- `getIncrementalOutput()` ne rend que le nouveau.
- Deux délais indépendants : total et inactivité.
- `stop()` envoie `SIGKILL` par défaut.

## Sources officielles

- [`components/process.rst`, branche 8.0](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/components/process.rst)
